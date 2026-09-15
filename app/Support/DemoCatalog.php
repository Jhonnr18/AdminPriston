<?php

namespace App\Support;

class DemoCatalog
{
    /**
     * Dados de demonstração alinhados ao protótipo / docs do Valhalla.
     * Substituídos por SQL Server quando VALHALLA_DEMO_MODE=false e a conexão sobe.
     */
    public static function kpis(): array
    {
        return [
            ['label' => 'Itens no banco', 'value' => '1.022', 'hint' => '995 códigos', 'route' => 'itens'],
            ['label' => 'Sem items.h', 'value' => '15', 'hint' => 'CODE=0', 'route' => 'itens', 'tone' => 'danger'],
            ['label' => 'Sem ícone', 'value' => '26', 'hint' => 'BMP ausente', 'route' => 'itens', 'tone' => 'warn'],
            ['label' => 'Sem modelo de chão', 'value' => '64', 'hint' => 'DropItem', 'route' => 'itens', 'tone' => 'warn'],
            ['label' => 'Nomes repetidos', 'value' => 'WV ×27', 'hint' => 'Punho da Serpente', 'route' => 'familia', 'tone' => 'warn'],
            ['label' => 'Monstros', 'value' => '331', 'hint' => 'drops no boot', 'route' => 'drops'],
        ];
    }

    public static function problems(): array
    {
        return [
            ['code' => 'OS120', 'name' => 'Magic Lucidy', 'reason' => 'sem ícone', 'kind' => 'Sheltom'],
            ['code' => 'SA101', 'name' => 'Elevar', 'reason' => 'sem items.h / CODE=0', 'kind' => 'Amulet'],
            ['code' => 'WV101', 'name' => 'Punho da Serpente', 'reason' => 'nome placeholder (protegido)', 'kind' => 'Weapon'],
            ['code' => 'DB134', 'name' => 'Botas 7D', 'reason' => 'sem modelo de chão', 'kind' => 'Boots'],
        ];
    }

    public static function weaponFamily(string $prefix = 'WA'): array
    {
        $wa = [
            ['code' => 'WA101', 'db' => 'Stone Axe', 'header' => 'Stone Axe', 'level' => 1, 'spec' => 1],
            ['code' => 'WA102', 'db' => 'Steel Axe', 'header' => 'Steel Axe', 'level' => 1, 'spec' => 1],
            ['code' => 'WA103', 'db' => 'Battle Axe', 'header' => 'Battle Axe', 'level' => 7, 'spec' => 1],
            ['code' => 'WA104', 'db' => 'War Axe', 'header' => 'War Axe', 'level' => 10, 'spec' => 1],
            ['code' => 'WA105', 'db' => 'Machado 105', 'header' => 'DoubleSidedWarAxe', 'level' => 16, 'spec' => 1],
            ['code' => 'WA106', 'db' => 'Bat Axe', 'header' => 'Bat Axe', 'level' => 22, 'spec' => 1],
            ['code' => 'WA107', 'db' => 'Mechanic Axe', 'header' => 'Mechanic Axe', 'level' => 30, 'spec' => 1],
            ['code' => 'WA108', 'db' => 'Double Head Axe', 'header' => 'Double Head Axe', 'level' => 37, 'spec' => 1],
            ['code' => 'WA109', 'db' => 'Great Axe', 'header' => 'Great Axe', 'level' => 44, 'spec' => 1],
            ['code' => 'WA110', 'db' => 'Machado 105', 'header' => 'Diamond Axe', 'level' => 50, 'spec' => 1],
            ['code' => 'WA111', 'db' => 'Jagged Axe', 'header' => 'Jagged Axe', 'level' => 55, 'spec' => 1],
            ['code' => 'WA112', 'db' => 'Cleaver', 'header' => 'Cleaver', 'level' => 60, 'spec' => 1],
        ];

        $wv = [
            ['code' => 'WV101', 'db' => 'Punho da Serpente', 'header' => 'Fist Snake Brace', 'level' => 1, 'spec' => 11, 'protected' => true],
            ['code' => 'WV102', 'db' => 'Punho da Serpente', 'header' => 'Enas Snake Brace', 'level' => 1, 'spec' => 11, 'protected' => true],
            ['code' => 'WV103', 'db' => 'Punho da Serpente', 'header' => 'Megas Snake Brace', 'level' => 7, 'spec' => 11, 'protected' => true],
            ['code' => 'WV104', 'db' => 'Punho da Serpente', 'header' => 'Neos Snake Brace', 'level' => 10, 'spec' => 11, 'protected' => true],
            ['code' => 'WV105', 'db' => 'Punho da Serpente', 'header' => 'Thermos Snake Brace', 'level' => 16, 'spec' => 11, 'protected' => true],
            ['code' => 'WV106', 'db' => 'Punho da Serpente', 'header' => 'Ptera Snake Brace', 'level' => 22, 'spec' => 11, 'protected' => true],
            ['code' => 'WV107', 'db' => 'Punho da Serpente', 'header' => 'Skotad Snake Brace', 'level' => 30, 'spec' => 11, 'protected' => true],
            ['code' => 'WV108', 'db' => 'Punho da Serpente', 'header' => 'Kako Snake Brace', 'level' => 37, 'spec' => 11, 'protected' => true],
            ['code' => 'WV109', 'db' => 'Punho da Serpente', 'header' => 'Almonia Snake Brace', 'level' => 44, 'spec' => 11, 'protected' => true],
            ['code' => 'WV110', 'db' => 'Punho da Serpente', 'header' => 'Mekane Snake Brace', 'level' => 50, 'spec' => 11, 'protected' => true],
        ];

        return strtoupper($prefix) === 'WV' ? $wv : $wa;
    }

    public static function monsters(): array
    {
        return [
            ['name' => 'Bargon', 'level' => 50, 'drop_id' => 12, 'rows' => 4, 'boss' => false],
            ['name' => 'Dark Stalker', 'level' => 70, 'drop_id' => 147, 'rows' => 6, 'boss' => false],
            ['name' => 'Tulla', 'level' => 100, 'drop_id' => 88, 'rows' => 8, 'boss' => true],
            ['name' => 'Draxos', 'level' => 110, 'drop_id' => 91, 'rows' => 5, 'boss' => true],
        ];
    }

    public static function dropRows(string $monster = 'Bargon'): array
    {
        return [
            ['id' => 1, 'chance' => 4000, 'items' => [
                ['code' => 'WA108', 'name' => 'Double Head Axe'],
                ['code' => 'DA110', 'name' => 'Synthethic Armor'],
            ]],
            ['id' => 2, 'chance' => 2500, 'items' => [
                ['code' => 'Gold', 'name' => 'Gold', 'gold_min' => 100, 'gold_max' => 300],
            ]],
            ['id' => 3, 'chance' => 2000, 'items' => [
                ['code' => 'Air', 'name' => 'Nada'],
            ]],
            ['id' => 4, 'chance' => 1500, 'items' => [
                ['code' => 'OS101', 'name' => 'Lucidy'],
            ]],
        ];
    }

    public static function itemTables(): array
    {
        return [
            'Weapons' => 328, 'Armor' => 30, 'Robes' => 28, 'Shields' => 55,
            'Boots' => 32, 'Gloves' => 30, 'Amulets' => 29, 'Rings' => 28,
            'Bracelets' => 31, 'Sheltoms' => 23, 'MagicWeapons' => 0, 'ArmorT' => 0,
            'Costumes' => 32, 'Premiuns' => 255, 'Potions' => 12, 'Asas' => 0,
        ];
    }

    public static function skillParams(): array
    {
        return [
            'key' => 'TA1S1A',
            'label' => 'Escudo Extremo — Block Rate',
            'levels' => [5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        ];
    }
}
