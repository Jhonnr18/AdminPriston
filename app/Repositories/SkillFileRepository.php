<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
use App\Support\DemoCatalog;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ConfigPublicationService;
use InvalidArgumentException;
use Throwable;

class SkillFileRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function valuesPerParameter(): int
    {
        return (int) config('valhalla.skill_values_per_parameter', 10);
    }

    /**
     * @return array<string, string>
     */
    public function files(): array
    {
        return config('valhalla.skill_files', []);
    }

    /**
     * @return list<array{key: string, label: string, values: list<string>}>
     */
    public function parse(string $contents): array
    {
        $expected = $this->valuesPerParameter();
        $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
        $pendingComment = '';
        $parameters = [];

        foreach ($lines as $line) {
            $trim = trim($line);

            if ($trim === '' || str_starts_with($trim, '[')) {
                $pendingComment = '';
                continue;
            }

            if (str_starts_with($trim, ';')) {
                $pendingComment = trim(ltrim($trim, ';'));
                continue;
            }

            if (! preg_match('/^([A-Za-z0-9_]+)\s*=\s*(.+)$/', $trim, $match)) {
                continue;
            }

            $values = preg_split('/[,\s]+/', trim($match[2]), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $parameters[] = [
                'key' => $match[1],
                'label' => $pendingComment !== '' ? $pendingComment : $match[1],
                'values' => array_slice(array_values($values), 0, $expected),
                'count' => count($values),
                'valid' => count($values) === $expected && $this->areNumeric($values),
            ];
            $pendingComment = '';
        }

        return $parameters;
    }

    /**
     * @param  list<string|int|float>  $values
     */
    public function assertTenValues(array $values): void
    {
        if (count($values) !== $this->valuesPerParameter()) {
            throw new InvalidArgumentException(
                'Cada parâmetro de skill deve ter exatamente '.$this->valuesPerParameter().' valores.'
            );
        }

        if (! $this->areNumeric($values)) {
            throw new InvalidArgumentException('Os valores de skill precisam ser numéricos.');
        }
    }

    /**
     * Edita os 10 valores de um parâmetro dentro de um .ini de skill já
     * deployado, substituindo só a linha do parâmetro (comentários, seções
     * e demais parâmetros ficam bit-a-bit idênticos). Faz backup antes de
     * escrever e enfileira reload pro poller do GameServer C++.
     *
     * O conteúdo é tratado como bytes opacos (sem mb_*, sem regex /u) porque
     * os .ini podem ter comentários fora de UTF-8 — não é seguro decodificar.
     *
     * @param  list<string|int|float>  $values
     * @return array{file: string, key: string, values: list<string|int|float>, backup: string}
     */
    public function save(
        string $file,
        string $key,
        array $values,
        string $operator,
        ?string $ip,
        ?string $reason = null,
    ): array
    {
        $files = $this->files();
        if (! array_key_exists($file, $files)) {
            throw new DomainException('Arquivo de skill desconhecido.');
        }

        try {
            $this->assertTenValues($values);
        } catch (InvalidArgumentException $e) {
            throw new DomainException($e->getMessage());
        }

        $path = rtrim((string) config('valhalla.skills_path'), '\\/').DIRECTORY_SEPARATOR.$file;
        if (! is_file($path)) {
            throw new DomainException('Arquivo de skill não encontrado no servidor: '.$path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new DomainException('Falha ao ler o arquivo de skill: '.$path);
        }

        // Divide preservando os terminadores de linha originais (podem ser
        // mistos) pra poder reescrever o arquivo byte-a-byte via implode.
        $lines = preg_split('/(\r\n|\n|\r)/', $contents, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$contents];

        $lineIndex = null;
        $originalValuesPart = null;
        for ($i = 0; $i < count($lines); $i += 2) {
            $trim = trim($lines[$i]);
            if (! preg_match('/^([A-Za-z0-9_]+)\s*=\s*(.+)$/', $trim, $match)) {
                continue;
            }
            if ($match[1] !== $key) {
                continue;
            }
            $lineIndex = $i;
            $originalValuesPart = $match[2];
            break;
        }

        if ($lineIndex === null || $originalValuesPart === null) {
            throw new DomainException('Parâmetro '.$key.' não encontrado em '.$file.'.');
        }

        $oldValues = preg_split('/[,\s]+/', trim($originalValuesPart), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Reconstrói a lista de valores usando exatamente o mesmo separador
        // (vírgula, espaço, ou combinação) que já existia entre cada par de
        // valores na linha original — não assume ", " nem nenhum outro fixo.
        $tokens = preg_split('/([,\s]+)/', $originalValuesPart, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $values = array_values($values);
        $newValueIndex = 0;
        $rebuiltValuesPart = '';
        foreach ($tokens as $idx => $token) {
            if ($idx % 2 === 0) {
                $rebuiltValuesPart .= array_key_exists($newValueIndex, $values) ? (string) $values[$newValueIndex] : $token;
                $newValueIndex++;
            } else {
                $rebuiltValuesPart .= $token;
            }
        }

        $rawLine = $lines[$lineIndex];
        $eqPos = strpos($rawLine, '=');
        $valueStartPos = $eqPos !== false ? strpos($rawLine, $originalValuesPart, $eqPos) : false;
        if ($valueStartPos === false) {
            throw new DomainException('Falha ao localizar valores de '.$key.' em '.$file.' para substituição segura.');
        }

        $prefix = substr($rawLine, 0, $valueStartPos);
        $suffix = substr($rawLine, $valueStartPos + strlen($originalValuesPart));
        $lines[$lineIndex] = $prefix.$rebuiltValuesPart.$suffix;

        $newContents = implode('', $lines);

        $backupPath = $path.'.bak-'.date('YmdHis');
        if (! copy($path, $backupPath)) {
            throw new DomainException('Falha ao criar backup antes de salvar.');
        }

        if (file_put_contents($path, $newContents, LOCK_EX) === false) {
            throw new DomainException('Falha ao escrever o arquivo de skill: '.$path);
        }

        ConfigAudit::query()->create([
            'operator' => $operator,
            'ip' => $ip,
            'action' => 'skill.ini.update',
            'resource' => 'skill_ini',
            'target_key' => $file.':'.$key,
            'value_before' => implode(',', $oldValues),
            'value_after' => implode(',', $values),
            'note' => 'backup: '.basename($backupPath).($reason ? '; motivo: '.$reason : ''),
            'result' => 'ok',
        ]);

        $versionId = $this->publication->recordMutation(
            'skill_ini',
            $file.':'.$key,
            [
                'sha256' => hash('sha256', $contents),
                'content_base64' => base64_encode($contents),
                'values' => $oldValues,
            ],
            [
                'sha256' => hash('sha256', $newContents),
                'content_base64' => base64_encode($newContents),
                'values' => array_map('strval', $values),
            ],
            $operator,
            $ip,
            $reason,
        );
        $this->queueReload($operator, $versionId);

        return [
            'file' => $file,
            'key' => $key,
            'values' => $values,
            'backup' => $backupPath,
        ];
    }

    /**
     * Enfileira um pedido de reload pro poller do GameServer C++. Best-effort:
     * o .ini já foi escrito e a auditoria já foi gravada, então uma falha
     * aqui não pode desfazer o save — o operador ainda pode rodar
     * /reload_skills manualmente.
     */
    private function queueReload(string $operator, ?int $versionId = null): void
    {
        if ($versionId !== null) {
            try {
                $this->publication->queueReload('skill_ini', $versionId, $operator);
                return;
            } catch (Throwable $e) {
                Log::warning('Falha ao enfileirar reload versionado de skill.', [
                    'version_id' => $versionId,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($this->publication->queueReloadBestEffort('skill_ini', $operator) !== null) {
            return;
        }

        try {
            DB::connection('gameserver')->insert(
                "INSERT INTO PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy, Status) VALUES (?, 0, ?, 'pending')",
                ['skill_ini', $operator],
            );
        } catch (Throwable $e) {
            Log::warning('Falha ao enfileirar reload de skill: '.$e->getMessage());
        }
    }

    /**
     * @return array{file: string, label: string, parameters: list<array<string, mixed>>, selected: ?array<string, mixed>}
     */
    public function page(?string $file = null, ?string $key = null): array
    {
        $files = $this->files();
        $file ??= array_key_first($files) ?: 'Lutador.ini';
        if (! array_key_exists($file, $files)) {
            $file = array_key_first($files) ?: 'Lutador.ini';
        }

        $path = rtrim((string) config('valhalla.skills_path'), '\\/').DIRECTORY_SEPARATOR.$file;
        $parameters = [];

        if (is_file($path)) {
            $parameters = $this->parse((string) file_get_contents($path));
        } elseif ($this->database->usingFixtures()) {
            $demo = DemoCatalog::skillParams();
            $parameters = [[
                'key' => $demo['key'],
                'label' => $demo['label'],
                'values' => array_map('strval', $demo['levels']),
                'count' => 10,
                'valid' => true,
            ]];
        }

        $selected = $parameters[0] ?? null;
        if ($key) {
            $selected = collect($parameters)->firstWhere('key', $key) ?? $selected;
        }

        return [
            'file' => $file,
            'label' => $files[$file] ?? $file,
            'files' => $files,
            'parameters' => $parameters,
            'selected' => $selected,
            'missing_file' => ! is_file($path) && ! $this->database->usingFixtures(),
            'path' => $path,
        ];
    }

    /**
     * @param  list<string|int|float>  $values
     */
    private function areNumeric(array $values): bool
    {
        foreach ($values as $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                return false;
            }
        }

        return true;
    }
}
