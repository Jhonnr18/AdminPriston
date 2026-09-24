<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Throwable;

class ClanHealthRepository
{
    private const MIGRATIONS = [
        '001_clan_core_pk.sql', '002_clan_name_unique.sql',
        '003_clan_membership_constraints.sql', '004_clan_mark_data.sql',
        '005_clan_fk_ul_cl.sql', '006_clan_state_columns.sql',
        '007_clan_role_uniqueness.sql', '007a_normalize_permi.sql',
        '008_clan_join_requests.sql', '009_clan_chest.sql',
        '010_clan_mark_blob.sql', '011_clan_chest_mutation_journal.sql',
    ];

    public function __construct(private readonly ValhallaDatabase $database) {}

    public function snapshot(): array
    {
        $tables = ['CL', 'UL', 'ClanMarkData', 'ClanJoinRequest', 'ClanJoinRule', 'ClanChestItem', 'ClanChestLog', 'ClanChestMutationJournal'];
        $result = [
            'online' => ! $this->database->demo() && $this->database->clanDbOnline(),
            'migrations' => array_map(fn (string $name) => ['name' => $name, 'status' => 'not_verified'], self::MIGRATIONS),
            'tables' => [],
            'warnings' => [],
        ];

        if (! $result['online']) {
            $result['warnings'][] = 'ClanDB offline ou modo demo; nenhuma escrita foi executada.';
            return $result;
        }

        foreach ($tables as $table) {
            try {
                $exists = DB::connection('clandb')->table('INFORMATION_SCHEMA.TABLES')
                    ->where('TABLE_SCHEMA', 'dbo')->where('TABLE_NAME', $table)->exists();
                $result['tables'][$table] = [
                    'exists' => $exists,
                    'rows' => $exists ? (int) DB::connection('clandb')->table($table)->count() : null,
                ];
            } catch (Throwable $e) {
                $result['tables'][$table] = ['exists' => false, 'rows' => null];
                $result['warnings'][] = 'Falha ao consultar '.$table.': '.$e->getMessage();
            }
        }

        if (! ($result['tables']['ClanChestMutationJournal']['exists'] ?? false)) {
            $result['warnings'][] = 'Journal do baú ausente: custódia permanece bloqueada.';
        }

        return $result;
    }
}
