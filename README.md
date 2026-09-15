# AdminPriston

Painel administrativo web do servidor privado **Valhalla / Priston Tale**.

Projeto **independente** do source C++ (`vallhala-2.0-Source`). Não clone este app para dentro da pasta do servidor.

- Stack: **Laravel 12** · PHP 8.3+ · **Livewire 4** · Blade · Tailwind 4 · Vite  
- Spec UI: protótipo `Valhalla Catálogo` → cole em `prototype/import/` (ver [prototype/IMPORT.md](prototype/IMPORT.md))  
- Docs de arquitetura: no repo do source, pasta `docs/painel-admin/`

## Setup

```bash
git clone https://github.com/Jhonnr18/AdminPriston.git
cd AdminPriston
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build   # opcional se public/build já vier no clone
php artisan serve
```

Abra http://127.0.0.1:8000/painel

Por padrão `VALHALLA_DEMO_MODE=true` (fixtures, sem SQL Server).

## Configuração (.env)

```env
APP_NAME=AdminPriston
VALHALLA_DEMO_MODE=true

VALHALLA_DB_HOST=127.0.0.1
VALHALLA_DB_PORT=1437
VALHALLA_DB_DATABASE=GameServer
VALHALLA_DB_USERNAME=sa
VALHALLA_DB_PASSWORD=

VALHALLA_CLIENT_ITEMS_ROOT="D:\valhalla\Game\image\Sinimage\Items"
VALHALLA_CLIENT_DROPITEM_ROOT="D:\valhalla\Game\image\Sinimage\Items\DropItem"
VALHALLA_ITEMS_H_PATH="D:\valhalla\vallhala-2.0-Source\Shared\items.h"
VALHALLA_SKILLS_PATH="D:\valhalla\Server\Skills"

VALHALLA_ICON_TPL=it{code}.bmp
VALHALLA_DROP_TPL=it{dorp}.smd
VALHALLA_ICON_SUBFOLDERS=Weapon,Defense,Accessory,DropItem
VALHALLA_PROTECT_WV=true
```

**Não** rode `php artisan migrate` contra o `GameServer`.

## Telas

| Rota | Tela |
|---|---|
| `/painel` | Visão geral |
| `/painel/itens` | Itens |
| `/painel/familia?prefix=WA` | Linha da família |
| `/painel/drops` | Drops |
| `/painel/skills` | Skills |
| `/painel/pvp` | PvP (bloqueado até patch) |
| `/painel/npcs` | NPCs e lojas |
| `/painel/coin-shop` | Loja de coins |
| `/painel/recompensas` | Arnold (off) |
| `/painel/raridade` | Raridade |
| `/painel/reliquias` | Relíquias |
| `/painel/coins` | Coins e Time |
| `/painel/servidor` | Settings |

## Importar o HTML do Downloads

Copie `C:\Users\agtic15\Downloads\Valhalla Catálogo Prototipo HTML\*` → `prototype/import/`.
