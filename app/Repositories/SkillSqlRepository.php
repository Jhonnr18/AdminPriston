<?php

namespace App\Repositories;

use App\Models\GameServer\SkillCooldown;
use App\Models\GameServer\SkillDefinition;
use App\Models\GameServer\SkillLevelValue;
use App\Models\GameServer\SkillParameterDef;
use App\Services\ConfigPublicationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class SkillSqlRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function definitions(): array
    {
        if ($this->database->usingFixtures()) {
            return [];
        }

        return SkillDefinition::query()
            ->orderBy('ClassId')->orderBy('Tier')->orderBy('SkillIndex')
            ->get()
            ->map(fn ($skill) => [
                'code' => (int) $skill->SkillCode,
                'class_id' => (int) $skill->ClassId,
                'tier' => (int) $skill->Tier,
                'index' => (int) $skill->SkillIndex,
                'name' => (string) $skill->Name,
                'required_level' => (int) $skill->RequiredLevel,
                'max_level' => (int) $skill->MaxLevel,
                'active' => (bool) $skill->Active,
            ])->all();
    }

    public function parameters(int $skillCode): array
    {
        if ($this->database->usingFixtures()) {
            return [];
        }

        $parameters = SkillParameterDef::query()
            ->where('SkillCode', $skillCode)
            ->orderBy('Parameter')
            ->get();

        return $parameters->map(function ($parameter) use ($skillCode) {
            $values = SkillLevelValue::query()
                ->where('SkillCode', $skillCode)
                ->where('Parameter', $parameter->Parameter)
                ->orderBy('SkillLevel')
                ->pluck('Value', 'SkillLevel');

            $cooldowns = SkillCooldown::query()
                ->where('SkillCode', $skillCode)
                ->orderBy('SkillLevel')
                ->pluck('CooldownMs', 'SkillLevel');

            return [
                'parameter' => (string) $parameter->Parameter,
                'display_name' => (string) $parameter->DisplayNamePt,
                'cpp_type' => (string) $parameter->CppType,
                'unit' => (string) $parameter->Unit,
                'min' => $parameter->MinAllowed !== null ? (float) $parameter->MinAllowed : null,
                'max' => $parameter->MaxAllowed !== null ? (float) $parameter->MaxAllowed : null,
                'values' => $values->mapWithKeys(fn ($value, $level) => [(int) $level => (float) $value])->all(),
                'cooldowns' => $cooldowns->mapWithKeys(fn ($value, $level) => [(int) $level => (int) $value])->all(),
            ];
        })->all();
    }

    public function updateParameter(
        int $skillCode,
        string $parameter,
        array $values,
        string $operator,
        ?string $ip,
        ?string $reason = null,
    ): array {
        if (count($values) !== 10) {
            throw new DomainException('Cada parâmetro SQL precisa de exatamente 10 níveis.');
        }

        $definition = SkillDefinition::query()->where('SkillCode', $skillCode)->first();
        $parameterDef = SkillParameterDef::query()
            ->where('SkillCode', $skillCode)->where('Parameter', $parameter)->first();
        if (! $definition || ! $parameterDef) {
            throw new DomainException('Skill ou parâmetro não encontrado.');
        }

        $normalized = [];
        foreach (array_values($values) as $index => $value) {
            if (! is_numeric($value) || ! is_finite((float) $value)) {
                throw new DomainException('Os 10 valores precisam ser numéricos e finitos.');
            }

            $number = (float) $value;
            if ((string) $parameterDef->CppType === 'short' && ($number < -32768 || $number > 32767)) {
                throw new DomainException('O valor excede o intervalo short (-32768..32767).');
            }
            if (str_ends_with((string) $parameterDef->Parameter, 'UseMana') && $number < 0) {
                throw new DomainException('Parâmetros de mana não podem ser negativos.');
            }
            if ($parameterDef->MinAllowed !== null && $number < (float) $parameterDef->MinAllowed) {
                throw new DomainException('O valor está abaixo do mínimo permitido para o parâmetro.');
            }
            if ($parameterDef->MaxAllowed !== null && $number > (float) $parameterDef->MaxAllowed) {
                throw new DomainException('O valor está acima do máximo permitido para o parâmetro.');
            }
            $normalized[$index + 1] = $number;
        }

        $before = SkillLevelValue::query()
            ->where('SkillCode', $skillCode)->where('Parameter', $parameter)
            ->orderBy('SkillLevel')->pluck('Value', 'SkillLevel')
            ->map(fn ($value) => (float) $value)->all();

        DB::connection('gameserver')->transaction(function () use ($skillCode, $parameter, $normalized): void {
            foreach ($normalized as $level => $value) {
                SkillLevelValue::query()->updateOrCreate(
                    ['SkillCode' => $skillCode, 'SkillLevel' => $level, 'Parameter' => $parameter],
                    ['Value' => $value],
                );
            }
        });

        $versionId = $this->publication->recordMutation(
            'skill_sql',
            $skillCode.':'.$parameter,
            ['values' => $before],
            ['values' => $normalized],
            $operator,
            $ip,
            $reason,
        );
        $this->queueReload($operator, $versionId);

        return ['skill_code' => $skillCode, 'parameter' => $parameter, 'values' => $normalized];
    }

    public function updateCooldown(
        int $skillCode,
        int $level,
        int $cooldownMs,
        string $operator,
        ?string $ip,
        ?string $reason = null,
    ): void {
        if ($level < 1 || $level > 10 || $cooldownMs < 0 || $cooldownMs > 600000) {
            throw new DomainException('Cooldown exige nível 1..10 e valor entre 0 e 600000 ms.');
        }
        if (! SkillDefinition::query()->where('SkillCode', $skillCode)->exists()) {
            throw new DomainException('Skill não encontrada.');
        }

        $before = SkillCooldown::query()
            ->where('SkillCode', $skillCode)->where('SkillLevel', $level)->value('CooldownMs');
        SkillCooldown::query()->updateOrCreate(
            ['SkillCode' => $skillCode, 'SkillLevel' => $level],
            ['CooldownMs' => $cooldownMs],
        );

        $versionId = $this->publication->recordMutation(
            'skill_sql',
            $skillCode.':cooldown:'.$level,
            ['cooldown_ms' => $before !== null ? (int) $before : null],
            ['cooldown_ms' => $cooldownMs],
            $operator,
            $ip,
            $reason,
        );
        $this->queueReload($operator, $versionId);
    }

    private function queueReload(string $operator, ?int $versionId): void
    {
        if ($versionId !== null) {
            try {
                $this->publication->queueReload('skill_sql', $versionId, $operator);
                return;
            } catch (\Throwable) {
                // fallback abaixo mantém compatibilidade com instalações antigas.
            }
        } elseif ($this->publication->queueReloadBestEffort('skill_sql', $operator) !== null) {
            return;
        }

        try {
            DB::connection('gameserver')->insert(
                "INSERT INTO PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy, Status) VALUES (?, 0, ?, 'pending')",
                ['skill_sql', $operator],
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Falha ao enfileirar reload SQL de skill.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
