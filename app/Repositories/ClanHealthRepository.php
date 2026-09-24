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
            'constraints' => [],
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

        try {
            $keys = collect(DB::connection('clandb')->select(
                "SELECT kc.name FROM sys.key_constraints kc WHERE kc.parent_object_id IN (SELECT object_id FROM sys.tables)"
            ))->map(fn ($row) => (string) $row->name)->all();
            $foreignKeys = collect(DB::connection('clandb')->select(
                "SELECT name FROM sys.foreign_keys"
            ))->map(fn ($row) => (string) $row->name)->all();
            $uniqueIndexes = collect(DB::connection('clandb')->select(
                "SELECT i.name FROM sys.indexes i JOIN sys.tables t ON t.object_id = i.object_id WHERE i.is_unique = 1"
            ))->map(fn ($row) => (string) $row->name)->all();
            $expected = [
                'PK_CL', 'PK_UL', 'PK_ClanMarkData', 'PK_ClanJoinRequest',
                'PK_ClanJoinRule', 'PK_ClanChestItem', 'PK_ClanChestLog',
                'PK_ClanChestMutationJournal', 'FK_UL_CL', 'FK_ClanJoinRequest_CL',
                'FK_ClanJoinRule_CL', 'FK_ClanChestItem_CL', 'FK_CCMJ_Clan',
                'UQ_CL_ClanName_Active', 'UQ_UL_ChName_Active', 'UX_UL_Leader',
                'UX_UL_SubLead', 'UX_CCI_Identity', 'UX_ClanMarkData_ClanId',
            ];
            foreach ($expected as $name) {
                $result['constraints'][] = [
                    'name' => $name,
                    'exists' => in_array($name, $keys, true)
                        || in_array($name, $foreignKeys, true)
                        || in_array($name, $uniqueIndexes, true),
                ];
            }
        } catch (Throwable $e) {
            $result['warnings'][] = 'Falha ao auditar constraints: '.$e->getMessage();
        }

        if (! ($result['tables']['ClanChestMutationJournal']['exists'] ?? false)) {
            $result['warnings'][] = 'Journal do baú ausente: custódia permanece bloqueada.';
        }

        return $result;
    }
}
