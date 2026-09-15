<?php

return [
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
        'D:\\valhalla\\Server\\Skills'
    ),

    'icon_filename_tpl' => env('VALHALLA_ICON_TPL', 'it{code}.bmp'),
    'drop_mesh_tpl' => env('VALHALLA_DROP_TPL', 'it{dorp}.smd'),

    'icon_subfolders' => array_filter(array_map(
        'trim',
        explode(',', env('VALHALLA_ICON_SUBFOLDERS', 'Weapon,Defense,Accessory,DropItem'))
    )),

    'protect_wv' => env('VALHALLA_PROTECT_WV', true),

    /*
    | Modo demo: usa CSVs em database/fixtures quando o SQL Server não responde.
    */
    'demo_mode' => env('VALHALLA_DEMO_MODE', true),
];
