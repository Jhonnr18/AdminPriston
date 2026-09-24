<?php

namespace App\Repositories;

use App\Models\GameServer\RarityBonus;
use App\Services\ConfigPublicationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class RarityBonusRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function all(): array
    {
        if ($this->database->usingFixtures()) {
            return [];
        }

        return RarityBonus::query()
            ->orderBy('Rarity')->orderBy('ItemLevelBand')->orderBy('StatCode')
            ->get()
            ->map(fn ($row) => [
                'rarity' => (int) $row->Rarity,
                'band' => (string) $row->ItemLevelBand,
                'stat' => (string) $row->StatCode,
                'value' => (float) $row->Value,
            ])->all();
    }

    public function update(
        int $rarity,
        string $band,
        string $stat,
        float|int|string $value,
        string $operator,
        ?string $ip,
        ?string $reason = null,
    ): array {
        $bands = array_keys((array) config('valhalla.rarity_bonus_bands'));
        $stats = array_keys((array) config('valhalla.rarity_bonus_stats'));
        $number = (float) $value;

        if ($rarity < 2 || $rarity > 5) {
            throw new DomainException('Bônus de raridade só pode ser editado para raridades 2 a 5; Common não possui bônus.');
        }
        if (! in_array($band, $bands, true)) {
            throw new DomainException('Faixa de nível de item inválida.');
        }
        if (! in_array($stat, $stats, true)) {
            throw new DomainException('StatCode não pertence ao vocabulário fechado da source.');
        }
        if (! is_finite($number) || $number < 0) {
            throw new DomainException('O bônus precisa ser um número finito maior ou igual a zero.');
        }
        if ($band === 'all' && str_starts_with($stat, 'Bracelet')) {
            throw new DomainException('Stats legados de bracelete exigem a faixa lt103 ou gte103.');
        }
        if ($band !== 'all' && ! str_starts_with($stat, 'Bracelet')) {
            throw new DomainException('Stats gerais de raridade usam somente a faixa all.');
        }

        $row = RarityBonus::query()
            ->where('Rarity', $rarity)->where('ItemLevelBand', $band)->where('StatCode', $stat)
            ->first();
        $before = $row ? ['value' => (float) $row->Value] : null;

        DB::connection('gameserver')->transaction(function () use ($rarity, $band, $stat, $number): void {
            RarityBonus::query()->updateOrCreate(
                ['Rarity' => $rarity, 'ItemLevelBand' => $band, 'StatCode' => $stat],
                ['Value' => $number],
            );
        });

        $after = ['value' => $number];
        $versionId = $this->publication->recordMutation(
            'rarity_bonus',
            $rarity.':'.$band.':'.$stat,
            $before ?? [],
            $after,
            $operator,
            $ip,
            $reason,
        );
        $this->queueReload($operator, $versionId);

        return [
            'rarity' => $rarity,
            'band' => $band,
            'stat' => $stat,
            'value' => $number,
        ];
    }

    private function queueReload(string $operator, ?int $versionId): void
    {
        if ($versionId !== null) {
            try {
                $this->publication->queueReload('rarity_bonus', $versionId, $operator);
                return;
            } catch (\Throwable) {
                // fallback legado abaixo
            }
        } elseif ($this->publication->queueReloadBestEffort('rarity_bonus', $operator) !== null) {
            return;
        }

        try {
            DB::connection('gameserver')->insert(
                "INSERT INTO PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy, Status) VALUES (?, 0, ?, 'pending')",
                ['rarity_bonus', $operator],
            );
        } catch (\Throwable) {
            // O reload HTTP já foi tentado pelo serviço central.
        }
    }
}
