<?php

namespace App\Repositories;

class ServerConfigRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'host' => config('valhalla.sqlsrv.host'),
            'port' => config('valhalla.sqlsrv.port'),
            'database' => config('valhalla.sqlsrv.database'),
            'userdb' => config('valhalla.userdb.database'),
            'shopcoin' => config('valhalla.shopcoin.database'),
            'client_items' => config('valhalla.client_items_root'),
            'dropitem' => config('valhalla.client_dropitem_root'),
            'items_h' => config('valhalla.items_h_path'),
            'skills' => config('valhalla.skills_path'),
            'icon_tpl' => config('valhalla.icon_filename_tpl'),
            'drop_tpl' => config('valhalla.drop_mesh_tpl'),
            'subfolders' => config('valhalla.icon_subfolders'),
            'protect_wv' => config('valhalla.protect_wv'),
            'demo' => $this->database->demo(),
            'gameserver_online' => $this->database->gameserverOnline(),
            'userdb_online' => $this->database->userdbOnline(),
            'shopcoin_online' => $this->database->shopcoinOnline(),
            'status' => $this->database->statusLabel(),
            // Evita is_dir() em pastas OneDrive a cada request (muito lento).
            'items_h_exists' => @is_file((string) config('valhalla.items_h_path')),
            'skills_exists' => null,
            'icons_exists' => null,
        ];
    }
}
