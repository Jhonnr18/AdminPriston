# AdminPriston — arquitetura e funcionamento

Documento de orientação pra quem (humano ou agente) for mexer neste repo pela
primeira vez. Não é um tutorial de setup (isso já está no `README.md` da
raiz) — é o retrato de como o projeto é organizado, por que ele é organizado
assim, e onde cada coisa fica.

## O que é este projeto

Painel administrativo web do servidor privado de Priston Tale **Valhalla**.
Laravel 12 / PHP 8.3+ / Blade / Tailwind 4 / Vite. É um repositório **git
independente** do source C++ do jogo (`vallhala-2.0-Source`, em
`D:\valhalla\vallhala-2.0-Source`) — os dois só se comunicam através de três
bancos SQL Server compartilhados e de uma pasta de arquivos do cliente lida
em modo leitura. Nenhum dos dois repositórios importa código do outro.

O painel administra, por cima das mesmas tabelas/arquivos que o servidor de
jogo em C++ já lê: catálogo de itens (skin, consulta), drops de monstro,
skills (`.ini`), raridade, relíquias, loja de coins, NPCs/lojas, saldo de
coins/tempo de jogador. PvP e recompensas (Arnold/Gandalf) são **stubs
estáticos** — o painel mostra por que essas telas não são editáveis hoje em
vez de fingir que são.

## Como rodar

Ver `README.md`. Resumo: `composer install`, `.env` (por padrão
`VALHALLA_DEMO_MODE=true`, roda sem SQL Server nenhum), `npm run build`,
`php artisan serve`, abrir `/painel`. **Nunca rodar `php artisan migrate`**
contra `gameserver`/`userdb`/`shopcoin` — só existe migration pra tabelas
locais (ver seção de bancos).

## Camadas

```
Controller (app/Http/Controllers/Admin/*)
    → Repository (app/Repositories/*)
        → Model Eloquent (app/Models/{GameServer,ShopCoin,UserDB}/*)
            → SQL Server (gameserver | userdb | shopcoin)
        → ou arquivo direto (ItemsHRepository lê items.h, SkillFileRepository lê .ini)
```

Controller nunca fala com Model direto — sempre por um Repository. Isso é o
que permite cada Repository decidir sozinho se responde com dado real ou
com fixture de demo (próxima seção), sem o Controller precisar saber.

Views (`resources/views/admin/*.blade.php`) são renderizadas
server-side; a única camada de interatividade JS é um modal genérico
(`resources/js/app.js`), sem framework front-end de verdade — ver seção
"Frontend".

## O mecanismo central: modo demo (`ValhallaDatabase`)

`app/Repositories/ValhallaDatabase.php` é injetado em quase todo Repository
e decide, por request, se o app fala com SQL Server de verdade ou devolve
fixtures fixas:

- `demo(): bool` — lê `config('valhalla.demo_mode')` (env
  `VALHALLA_DEMO_MODE`, **default `true`**).
- `gameserverOnline()` / `userdbOnline()` / `shopcoinOnline()` — cada uma
  memoizada por request e cacheada 30s (`Cache::remember`) por conexão. Se
  `demo()` for `true`, nem tenta conectar. Isso evita que um SQL Server fora
  do ar cause timeout de ODBC em toda request.
- **`usingFixtures(): bool` = `demo() || ! gameserverOnline()`** — é essa
  flag, não `demo()` sozinha, que a maioria dos Repository de leitura
  checa. Ou seja: mesmo com `VALHALLA_DEMO_MODE=false`, se o container
  Docker do SQL Server estiver fora do ar, o painel cai pra fixture em vez
  de dar 500. Repository de escrita (`RelicRepository`, `CoinAdminRepository`)
  em geral são mais estritos: checam `demo() || ! xOnline()` e lançam
  `DomainException` em vez de simular — **exceção**: `ItemSkinRepository`
  simula a escrita mesmo em modo fixture (ver seção da feature de skin),
  porque o objetivo dela é dar pra testar a UI sem precisar de SQL Server.
- `statusLabel()` / `warning()` — texto mostrado na barra superior do
  layout (`DEMO (fixtures)` / `SQL Server` / `SQL OFFLINE` + banner de
  troubleshooting em português quando modo real não consegue conectar).

Toda fixture usada em modo demo vive centralizada em
`app/Support/DemoCatalog.php` (KPIs, família de arma WA/WV, monstros,
skills de exemplo, contagem por tabela de item) — é a única fonte de
verdade de "como a UI se parece com zero SQL Server".

CLI equivalente pra diagnosticar conexão sem abrir o navegador:
`php artisan valhalla:probe` (`app/Console/Commands/ValhallaProbeCommand.php`).

## O padrão de escrita/publicação

Toda ação que grava algo segue o mesmo molde — o exemplo mais claro é
`RelicRepository::updateDef()`:

1. Validação de domínio lançando `DomainException` com mensagem em
   português explicando a regra de negócio (não é `ValidationException` do
   Laravel — essas regras vêm do que o servidor C++ realmente aceita, não
   de forma HTML).
2. Se `usingFixtures()`/offline: lança `DomainException` (ou, no caso da
   feature de skin, simula — ver abaixo) em vez de fingir que gravou.
3. `DB::connection('gameserver')->transaction(...)`, com `lockForUpdate()`
   na(s) linha(s) afetada(s).
4. Grava o valor novo.
5. `ConfigAudit::query()->create([...])` — operador, IP, action, resource,
   target_key, value_before/value_after (JSON), note, result. Tabela
   `valhalla_audits`, **na conexão default (sqlite local)** — independe do
   GameServer estar disponível, então auditoria nunca se perde por causa de
   um SQL Server fora do ar.
6. `queueReload($resource, $operator)` — best-effort, nunca derruba a
   transação principal se falhar (só loga `Log::warning`): insere em
   `PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy,
   Status)` na conexão `gameserver`. O servidor C++ tem um poller em
   `SrcServer/src/Server/SrcServer/OnSever.cpp` (`~linha 6397`, fora deste
   repo) que lê essa tabela a cada tick, despacha pro loader certo conforme
   o literal de `Resource` (`rarity_group`, `relic_def`, `skill_ini`,
   `item_skin`, ...) e marca a linha como `succeeded`. **O literal de
   `Resource` usado em cada `queueReload()` é um contrato com o C++** — mudar
   o texto sem avisar o time do source quebra o reload silenciosamente.

Todo Controller de escrita segue o mesmo formato de resposta a erro:
captura `DomainException` e devolve JSON `{error: mensagem}` com HTTP 422,
ou (nas telas de formulário tradicional) redireciona de volta com a
mensagem em `session`.

**Nenhuma autenticação está ligada ainda.** Todo operador é o literal
`'operador'` hardcoded no Controller (não vem de usuário logado — não há
`auth` middleware em nenhuma rota `/painel/*`, apesar de existir um model
`User`/`Authenticatable` padrão do Laravel, não usado por nada). Isso é uma
ferramenta interna de operador único hoje, não um descuido — mas é
relevante saber antes de assumir que auditoria por operador distingue
pessoas de verdade.

## Bancos e conexões

| Conexão | Banco real | Uso | Migration? |
|---|---|---|---|
| `sqlite` (default) | `database/database.sqlite` | `users`, `cache`, `jobs`, **`valhalla_audits`** (auditoria de todo o painel) | Sim — as 4 migrations em `database/migrations/` são só pra esta conexão |
| `gameserver` | SQL Server `GameServer` (Docker `valhalla_pt_sqlserver`, `127.0.0.1,1437` por padrão) | Itens (21 tabelas — `Weapons`, `Armor`, ..., ver `config('valhalla.item_tables')`), monstros/drops, raridade, relíquias, NPCs/lojas, **`PainelDB.dbo.ConfigReloadRequest`** | **Nunca.** Schema é dono do time C++. |
| `userdb` | SQL Server `UserDB` | Contas de jogador (`Users` — saldo de coin/tempo) | **Nunca.** |
| `shopcoin` | SQL Server `ShopCoin` | Loja de coins (`CoinShop`/`CoinShopTab`/`CoinShopItem`) | **Nunca.** |

As três conexões externas usam DSN ODBC explícito em
`config/database.php` (`'odbc' => true` + `odbc_datasource_name` montado à
mão) porque builds PHP comuns de XAMPP/Laragon no Windows trazem
`PDO_ODBC`, não `pdo_sqlsrv` — sem isso o driver `sqlsrv` do Laravel não
conecta. `login_timeout => 5` propositalmente curto (some com o `30s` de
cache do `ValhallaDatabase::cachedPing` acima pra falhar rápido).

Duas fontes de dado **não são banco**, são arquivo lido direto do disco (via
config, nunca copiado pra dentro deste repo):

- `Shared\items.h` do source C++ (`VALHALLA_ITEMS_H_PATH`) — parseado por
  `ItemsHRepository` (regex, cache 600s por `mtime` do arquivo).
- `.ini` de skill (`VALHALLA_SKILLS_PATH`) — lido/escrito por
  `SkillFileRepository`, byte a byte (sem `mb_*`/regex Unicode — comentário
  no próprio arquivo explica que os `.ini` podem ter bytes não-UTF-8, igual
  o aviso de encoding do repo do source).

## O padrão de tabela dinâmica: `GameItem`

Não existe uma Model por tabela de item. `app/Models/GameServer/GameItem.php`
tem `$table = 'Weapons'` só como default, e o método estático
`GameItem::queryTable(string $table): Builder` troca a tabela em runtime
(`$instance->setTable($table)`) e devolve um query builder novo. É assim
que uma única classe cobre as ~21 tabelas de `config('valhalla.item_tables')`
(`Weapons`, `Armor`, `Robes`, `Shields`, `Boots`, `Gloves`, `Amulets`,
`Rings`, `Bracelets`, `Sheltoms`, `Costumes`, `Crystals`, `Forces`,
`Brincos`, `Potions`, `Premiuns`, `Craft`, `QuestItems`, `Asas`, `ArmorT`,
`MagicWeapons`). `$guarded = []` (mass-assignable total) porque as colunas
variam por tabela.

`config('valhalla.families')` mapeia prefixo alfa (`WA`, `OR`, `DA`, ...)
→ `{table, label[, protected]}` — é o que permite achar em qual das 21
tabelas um código como `WA101` mora sem precisar consultar todas. Só `WV`
(punho da Artista Marcial) tem `protected: true` — telas de edição em
massa devem respeitar isso (`config('valhalla.protect_wv')`, default
`true`).

## Como um ícone de item chega na tela

Cadeia completa, do banco até o `<img>`:

1. `GameItem::queryTable($table)->where('Code', $code)` dá o `Code` alfa
   (ex. `WA101`).
2. `ItemsHRepository::catalog()[$code]` (parse de `items.h`) dá `name` e
   `folder` (`Weapon`, `Accessory`, ...).
3. `ClientAssetRepository::iconUrl($code)` monta a URL — sempre devolve
   `route('media.icon', $code)`, **nunca consulta o disco na hora de montar
   a URL** (só resolve caminho real quando alguém de fato pede a imagem,
   porque a pasta do cliente costuma estar num disco OneDrive lento).
4. `MediaController::icon($code)` valida o formato do código e 301-redireciona
   pra `/icon.php?c=$code`.
5. **`public/icon.php`** é um script PHP solto, fora do ciclo de vida do
   Laravel (sem bootstrap do framework — comentário no arquivo explica que
   isso é de propósito, framework inteiro por ícone seria lento demais no
   `artisan serve` do Windows, e ícone é pedido aos montes, um por item/chip
   na tela). Ele lê o `.env` sozinho (sem dotenv), acha a pasta certa por
   prefixo do código (mesmo mapa de `ClientAssetRepository::foldersForPrefix`,
   mas duplicado standalone), serve `it{CODE}.bmp` e copia pra
   `public/icons/{CODE}.bmp` como cache local (`Cache-Control: max-age=604800`).
   Item sem ícone: 404 puro.
6. `resources/views/components/item-icon.blade.php` (versão sem JS) e
   `app.js::iconHtml()` (versão JS) ambos apontam direto pra `/icon.php`,
   não pro redirect — com fallback visual (placeholder colorido com as
   2 letras do prefixo) via `onerror` quando o ícone 404.

Mesma lógica pro modelo 3D de chão (`ClientAssetRepository::dropMeshPath`),
mas **isso não tem endpoint de serving** — só é usado hoje pra saúde do
catálogo (`missing_drop`), não pra visualização, porque `.smd` não é um
formato que dá pra jogar num `<img>`/navegador sem conversão.

## Feature: troca de skin de item

Implementada nesta sessão (2026-09-19), em conjunto com uma mudança no
source C++ (`vallhala-2.0-Source`, fora deste repo) que adiciona um campo
`SkinCode` a `sITEMINFO`/`TRANS_ITEM` e uma coluna `SkinCode` no fim de cada
uma das 21 tabelas de item (migration `docs/sql/22-item-skin-code-{up,down}.sql`
**no repo do source**, ainda não aplicada em nenhum SQL Server).

Ideia central: nunca mudar o `Code`/identidade de um item (isso quebraria
checksum anti-tamper, drop table, itens já no inventário de jogadores) —
em vez disso, um item aponta pra um **código alfa alternativo já compilado
no cliente** (`items.h`) só pra fins de render. `SkinCode` vazio = usa a
aparência do próprio `Code`.

`app/Repositories/ItemSkinRepository.php`:

- `availableSkinsFor(string $code)` — lista candidatos a skin pro item: todo
  código de `ItemsHRepository::catalog()` cuja pasta (`Weapon`/`Accessory`/...)
  bate com a do item original, excluindo o próprio código. Se a categoria do
  item original não dá pra determinar, devolve lista vazia em vez de
  arriscar sugerir algo incompatível.
- `currentSkin(string $table, string $code)` — leitura auxiliar pra UI;
  nunca lança, devolve `null` em qualquer cenário de falha (demo, item
  inexistente, coluna ainda não migrada no banco real).
- `setSkin(string $table, string $code, ?string $skinCode, operator, ip)` —
  o método de escrita. Validações, em ordem: tabela válida
  (`config('valhalla.item_tables')`) → item original existe → skin não é
  igual ao original → **skin existe em `items.h`** (nunca aceita código
  inventado — só reaproveita aparência já compilada no cliente) → categoria
  compatível (`assertCompatibleCategory`, mesmo mecanismo de pasta de
  `availableSkinsFor`; não bloqueia se a categoria de um dos lados for
  indeterminável, já que essa é só uma camada de sanidade — **a validação
  final de verdade é no servidor C++**, que faz a mesma checagem de novo
  comparando `Class`/`ItemFilePath` em `sItem[]`).
- Em modo fixture: `applySimulated()` — grava auditoria mesmo assim
  (`result: 'simulated'`, nota explicando o motivo) e enfileira reload
  best-effort, mas não toca em nenhuma linha de banco real. É a única
  escrita do projeto que se comporta assim de propósito — o objetivo é dar
  pra validar a UI inteira (diff, confirmação, atualização de ícone) sem
  precisar de SQL Server.
- Em modo real: `applyReal()` segue o molde padrão (transação + lock +
  `UPDATE ... SET SkinCode` + auditoria + `queueReload('item_skin', ...)`).
  Se a coluna `SkinCode` ainda não existir na tabela (migration do source
  C++ não aplicada), captura a `QueryException` de "invalid column name" e
  devolve uma `DomainException` apontando pro arquivo de migration certo —
  nunca deixa vazar uma exception genérica de SQL pra UI.
- **Contrato com o C++**: o literal `'item_skin'` passado pra
  `queueReload()` é reconhecido pelo poller em `OnSever.cpp` do repo do
  source, que despacha pra `GameServer::readItemsFromDB()`. Mudar esse
  literal aqui sem avisar quebra o reload sem erro visível.

UI: `ItemController::show()` inclui `skin.current`/`skin.available` no JSON
do modal de item existente; `ItemController::updateSkin()`
(`POST /painel/itens/{table}/{code}/skin`) aplica. `app.js::renderSkinSection()`
+ `applySkin()` cuidam do lado do cliente: dropdown de candidatos, preview
antes/depois, `window.confirm()` antes de enviar, e um aviso visual distinto
quando a resposta vem com `simulated: true`.

**Pendência conhecida do lado C++** (não deste repo, mas relevante pra saber
o estado real da feature): o re-carimbo de `SkinCode` em itens *já salvos*
na mochila de um jogador (pra skin nova valer também pra quem já tem o item)
foi implementado dentro de `rsRECORD_DBASE::ResotrRecordData()`, mas essa
função **não tem nenhum chamador no código atual** — o fluxo de login real
passa por outro caminho. Ou seja: hoje a skin nova vale pro catálogo (o que
`readItemsFromDB()` carrega), mas ainda não é replicada pra itens
específicos que já estão na mochila de alguém até esse ponto ser conectado
no C++.

## Frontend

- `resources/views/layouts/admin.blade.php` — layout único, sidebar
  "Jogo" (overview/itens/familia/drops/skills/pvp/npcs/coin-shop/recompensas/
  raridade/reliquias) + "Administração" (coins/servidor), barra de status
  do banco no topo, banners de erro/aviso/sucesso.
- `resources/js/app.js` (vanilla JS, sem framework) — um modal genérico
  (`#vlh-modal`) reaproveitado por item/monstro/NPC: qualquer elemento com
  `data-vlh-modal="item|monster|npc"` + `data-vlh-id` abre o modal e busca
  JSON do endpoint certo. Dentro do modal de monstro/NPC, cada item da
  lista de drop/venda é ele mesmo um trigger de `data-vlh-modal="item"` —
  dá pra navegar item → (via chip) sem sair do modal. É aqui que vive a
  seção de skin (`renderSkinSection`/`applySkin`).
- **Livewire está instalado (`composer.json`, `@livewireStyles`/
  `@livewireScripts` no layout) mas não é usado em lugar nenhum** — zero
  `wire:`/`@livewire`/componente Livewire no projeto inteiro. É peso morto
  carregado em toda página; se algum dia for removido de verdade, também dá
  pra tirar essas duas diretivas do layout.
- Raridade/relíquias/skills/coins são formulários HTML tradicionais
  (POST + redirect, recarrega a página) — só item/monstro/NPC têm
  interatividade JS.
- `public/vlh-modal.css` é carregado por `<link>` direto, fora do pipeline
  do Vite (mesmo espírito do `icon.php`: coisas que têm que ser simples e
  rápidas ficam fora do framework/bundler).

## Testes

`php artisan test` — `tests/Feature/PanelPagesTest.php` (smoke test de toda
rota em modo demo, incluindo o fluxo de skin) e
`tests/Unit/ItemSkinRepositoryTest.php` +
`tests/Unit/CatalogRulesTest.php` (regras de negócio isoladas: matemática
de raridade, parse de drop, parse de skill, regras da skin).

Há uma falha pré-existente e conhecida, **não relacionada a esta feature**:
`CatalogRulesTest::test_drop_tokens_split_on_space_and_keep_gold_air`
instancia `DropRepository` passando `app(ItemRepository::class)` onde o
construtor pede `ItemsHRepository` (`app/Repositories/DropRepository.php`)
— troca de classe parecida, não uma regressão desta sessão.

## Pontos em aberto / dívidas conhecidas

- Sem autenticação real (`operador` hardcoded, sem `auth` middleware) —
  ok pra ferramenta interna de operador único, mas não serve pra múltiplos
  admins com atribuição de verdade.
- Sem sistema de permissão granular (`skill_editor`/`rarity_editor`/etc.
  mencionados nos docs de planejamento do source nunca foram implementados
  aqui — todo Controller de escrita tem um `// TODO` no lugar).
- `OverviewRepository` tem chaves de cache pra `missing_icon_count`/
  `missing_drop_count` mas nenhum comando/job popula elas hoje — aparecem
  como `'…'` até alguém escrever esse job.
- PvP e Recompensas são stubs estáticos de propósito (dependem de mudança
  no C++ — `kLegacyPvpDamageScalePercent` hardcoded, e
  `Gandalf::cRewardHandler` desativado — não é bug deste painel).
- Loja de Coins (`CoinShopController`) é só leitura — não tem CRUD ainda,
  mesmo o Repository (`CoinShopRepository`) já existindo.
- A skin de item, hoje, só é reaplicada em itens que ainda vão ser
  carregados do catálogo pelo servidor C++, não retroativamente na mochila
  de jogador existente (ver seção da feature acima).

## Convenções pra quem for mexer aqui

1. Repository de leitura: sempre cheque `usingFixtures()` primeiro e tenha
   um caminho de `DemoCatalog`. Repository de escrita: valide com
   `DomainException` em português, feche a transação com auditoria +
   `queueReload()`, e decida conscientemente se ele deve ou não simular em
   modo fixture (a maioria não deve — `ItemSkinRepository` é a exceção
   deliberada).
2. Nunca escreva SQL de `ALTER`/`CREATE`/migration contra `gameserver`/
   `userdb`/`shopcoin` a partir deste repo — o schema desses bancos é do
   time do source C++ (`vallhala-2.0-Source/docs/sql/`).
3. Se uma tela nova precisa de reload no servidor de jogo, escolha um
   literal de `Resource` novo e curto, e **avise/documente do lado do
   source C++** que o poller de `OnSever.cpp` precisa reconhecer esse
   literal — sem isso o botão "publicar" não quebra visivelmente, só nunca
   aplica de verdade no jogo.
4. Ícone/modelo de item nunca são copiados pra dentro deste repo — sempre
   resolvidos ao vivo a partir de `VALHALLA_CLIENT_ITEMS_ROOT`/
   `VALHALLA_ITEMS_H_PATH` (pasta e arquivo do repo do source, lidos, nunca
   escritos por este projeto).
5. Se for adicionar validação de compatibilidade de categoria de item em
   algum lugar novo, reaproveite `ClientAssetRepository::foldersForPrefix()`
   (pública) em vez de duplicar o mapa de prefixo → pasta pela terceira vez
   (já existe uma cópia solta em `public/icon.php` por necessidade de ficar
   fora do bootstrap do Laravel — não crie uma quarta).
