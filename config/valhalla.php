<?php

return [
    'server_reload' => [
        'url' => env('VALHALLA_SERVER_API_URL'),
        'token' => env('VALHALLA_SERVER_API_TOKEN'),
        'timeout' => (int) env('VALHALLA_SERVER_API_TIMEOUT', 5),
        'paths' => [
            'rarity_group' => '/api/rarity/reload',
            'rarity_mod' => '/api/rarity/reload',
            'rarity_bonus' => '/api/rarity/reload',
            'relic_def' => '/api/relic/reload',
            'relic_bonus' => '/api/relic/reload',
            'skill_ini' => '/api/skill/reload',
            'skill_sql' => '/api/skill/reload',
            'item_skin' => '/api/item/reload',
            'shop_coin' => '/api/shop/reload',
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | SQL Server (GameServer) — não use migrate nisso
    |--------------------------------------------------------------------------
    */
    'sqlsrv' => [
        'host' => env('VALHALLA_DB_HOST', '127.0.0.1'),
        'port' => env('VALHALLA_DB_PORT', '1437'),
        'database' => env('VALHALLA_DB_DATABASE', 'GameServer'),
        'username' => env('VALHALLA_DB_USERNAME', 'sa'),
        'password' => env('VALHALLA_DB_PASSWORD', ''),
        'encrypt' => env('VALHALLA_DB_ENCRYPT', 'yes'),
        'trust_server_certificate' => env('VALHALLA_DB_TRUST_CERT', true),
    ],

    'userdb' => [
        'database' => env('VALHALLA_USERDB_DATABASE', 'UserDB'),
    ],

    'shopcoin' => [
        'database' => env('VALHALLA_SHOPCOIN_DATABASE', 'ShopCoin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pastas do cliente / source (Windows do operador)
    |--------------------------------------------------------------------------
    */
    'client_items_root' => env(
        'VALHALLA_CLIENT_ITEMS_ROOT',
        'D:\\valhalla\\Game\\image\\Sinimage\\Items'
    ),
    'client_dropitem_root' => env(
        'VALHALLA_CLIENT_DROPITEM_ROOT',
        'D:\\valhalla\\Game\\image\\Sinimage\\Items\\DropItem'
    ),
    'items_h_path' => env(
        'VALHALLA_ITEMS_H_PATH',
        'D:\\valhalla\\vallhala-2.0-Source\\Shared\\items.h'
    ),
    'skills_path' => env(
        'VALHALLA_SKILLS_PATH',
        'D:\\valhalla\\Server\\Server\\Skills'
    ),

    'icon_filename_tpl' => env('VALHALLA_ICON_TPL', 'it{code}.bmp'),
    'drop_mesh_tpl' => env('VALHALLA_DROP_TPL', 'it{dorp}.smd'),

    'icon_subfolders' => array_filter(array_map(
        'trim',
        explode(',', env('VALHALLA_ICON_SUBFOLDERS', 'Weapon,Defense,Accessory,DropItem,Potion,Premium,Quest,Wing,Event'))
    )),

    'protect_wv' => env('VALHALLA_PROTECT_WV', true),

    /*
    | Modo demo: usa fixtures em App\Support\DemoCatalog, sem SQL Server.
    */
    'demo_mode' => env('VALHALLA_DEMO_MODE', true),

    'rarity_denominator' => 10_000_000,
    'relic_slot_count' => 12,
    'relic_locked_slot' => 11,
    'rarity_bonus_bands' => [
        'all' => 'Todos os níveis',
        'lt103' => 'Abaixo do nível 103',
        'gte103' => 'Nível 103 ou superior',
    ],
    'rarity_bonus_stats' => [
        'WeaponDamagePct' => 'Dano de arma (%)',
        'CriticalHit' => 'Crítico',
        'DefenseAbsorbPct' => 'Absorção/defesa (%)',
        'ShieldBlock' => 'Bloqueio de escudo',
        'GloveAttackPower' => 'Ataque da luva',
        'BootSpeed' => 'Velocidade da bota',
        'AccessoryLife' => 'Vida de acessórios',
        'AccessoryRegenPct' => 'Regeneração de acessórios (%)',
        'BraceletBlock' => 'Bloqueio de bracelete',
        'BraceletAttackRating' => 'Ataque de bracelete',
        'BraceletDefence' => 'Defesa de bracelete',
        'BraceletPotionSpace' => 'Espaço de poções',
    ],
    'skill_values_per_parameter' => 10,

    'item_tables' => [
        'Weapons',
        'Armor',
        'Robes',
        'Shields',
        'Boots',
        'Gloves',
        'Amulets',
        'Rings',
        'Bracelets',
        'Sheltoms',
        'Costumes',
        'Crystals',
        'Forces',
        'Brincos',
        'Potions',
        'Premiuns',
        'Craft',
        'QuestItems',
        'Asas',
        'ArmorT',
        'MagicWeapons',
    ],

    'families' => [
        'WA' => ['table' => 'Weapons', 'label' => 'Machado'],
        'WC' => ['table' => 'Weapons', 'label' => 'Garra'],
        'WH' => ['table' => 'Weapons', 'label' => 'Martelo'],
        'WM' => ['table' => 'Weapons', 'label' => 'Cajado'],
        'WP' => ['table' => 'Weapons', 'label' => 'Foice'],
        'WS' => ['table' => 'Weapons', 'label' => 'Arco / Espada'],
        'WT' => ['table' => 'Weapons', 'label' => 'Javelin'],
        'WD' => ['table' => 'Weapons', 'label' => 'Adaga'],
        'WN' => ['table' => 'Weapons', 'label' => 'Phantom'],
        'WV' => ['table' => 'Weapons', 'label' => 'Punho (Marcial)', 'protected' => true],
        'DA' => ['table' => 'Armor', 'label' => 'Armadura'],
        'DA2' => ['table' => 'Robes', 'label' => 'Robe'],
        'DS' => ['table' => 'Shields', 'label' => 'Escudo'],
        'DB' => ['table' => 'Boots', 'label' => 'Bota'],
        'DG' => ['table' => 'Gloves', 'label' => 'Luva'],
        'OA' => ['table' => 'Amulets', 'label' => 'Amuleto'],
        'OR' => ['table' => 'Rings', 'label' => 'Anel'],
        'OB' => ['table' => 'Bracelets', 'label' => 'Bracelete'],
        'OS' => ['table' => 'Sheltoms', 'label' => 'Sheltom'],
        'CA' => ['table' => 'Costumes', 'label' => 'Fantasia'],
        'OE' => ['table' => 'Brincos', 'label' => 'Brinco'],
        'GP' => ['table' => 'Crystals', 'label' => 'Cristal'],
        'FO' => ['table' => 'Forces', 'label' => 'Força'],
        'PL' => ['table' => 'Potions', 'label' => 'Poção'],
        'BI' => ['table' => 'Premiuns', 'label' => 'Premium'],
        'PR' => ['table' => 'Craft', 'label' => 'Craft'],
        'QT' => ['table' => 'QuestItems', 'label' => 'Quest'],
    ],

    'primary_specs' => [
        1 => 'Fighter',
        2 => 'Pike',
        3 => 'Archer',
        4 => 'Marcial',
        5 => 'Assassin',
        6 => 'Mage',
        7 => 'Priestess',
        8 => 'Knight',
        9 => 'Atalanta',
        10 => 'Shaman',
        11 => 'Comum',
    ],

    'rarity_labels' => [
        0 => 'None',
        1 => 'Common',
        2 => 'Uncommon',
        3 => 'Rare',
        4 => 'Epic',
        5 => 'Legendary',
    ],

    'relic_bonus_types' => [
        0 => 'None',
        1 => 'Dano mín',
        2 => 'Dano máx',
        3 => 'Dano %',
        4 => 'Absorção',
        5 => 'Defesa',
        6 => 'Crítico',
        7 => 'Attack Rating',
        8 => 'Vel. ataque',
        9 => 'Vel. movimento',
        10 => 'Bloqueio',
        11 => 'Alcance',
        12 => 'Peso',
        13 => 'HP %',
        14 => 'MP %',
        15 => 'STM %',
        16 => 'Regen HP %',
        17 => 'Regen MP %',
        18 => 'Regen STM %',
        19 => 'Res. fogo',
        20 => 'Res. gelo',
        21 => 'Res. raio',
        22 => 'Res. veneno',
        23 => 'Res. bionic',
        24 => 'Res. terra',
        25 => 'Res. água',
        26 => 'Res. vento',
        27 => 'Visão',
        28 => 'Espaço poção',
        29 => 'HP flat',
        30 => 'MP flat',
        31 => 'STM flat',
        32 => 'Strength',
        33 => 'Spirit',
        34 => 'Talent',
        35 => 'Dexterity',
        36 => 'Health',
        37 => 'Evasão',
    ],

    'skill_files' => [
        'Mecanico.ini' => 'Mecânico',
        'Lutador.ini' => 'Lutador',
        'Pike.ini' => 'Pike',
        'Arqueira.ini' => 'Arqueira',
        'Assassina.ini' => 'Assassina',
        'Guerreira.ini' => 'Guerreira',
        'Cavaleiro.ini' => 'Cavaleiro',
        'Atalanta.ini' => 'Atalanta',
        'Sacerdotisa.ini' => 'Sacerdotisa',
        'Mago.ini' => 'Mago',
        'Xama.ini' => 'Xamã',
    ],

    'npc_sell_types' => [
        1 => 'Ataque',
        2 => 'Defesa',
        3 => 'Diversos',
    ],
];
