# Roadmap de integração AdminPriston ↔ Valhalla Source

> Documento de execução para conectar o painel Laravel ao runtime C++ e aos
> bancos SQL Server do projeto `vallhala-2.0-Source`.
>
> Estado da análise: 24/09/2026. Caminhos abaixo são relativos à source, salvo
> indicação contrária.

## 1. Objetivo

Transformar o AdminPriston em uma ferramenta administrativa operacional, segura
e auditável para o Valhalla, mantendo o servidor C++ como autoridade de
gameplay. O painel deve publicar configurações somente através de contratos que
o servidor reconhece, com validação, backup, rollback e reload controlado.

Os repositórios continuam independentes. A integração ocorre por SQL Server,
pela fila `PainelDB.dbo.ConfigReloadRequest`, por arquivos lidos do source ou
cliente e pelos contratos C++.

## 2. Diagnóstico atual

### O que já existe no painel

| Área | Estado | Evidência |
|---|---|---|
| Stack | Laravel 12, PHP 8.3+, Blade, Tailwind, Vite | `README.md`, `composer.json` |
| SQL | conexões `gameserver`, `userdb`, `shopcoin` | `config/database.php`, `config/valhalla.php` |
| Catálogo | 21 tabelas de itens via `GameItem::queryTable()` | `config/valhalla.php`, `GameItem` |
| Arquivos | `items.h`, ícones, drops e `.ini` de skills | repositories de catálogo/assets/skills |
| Escritas | skin, raridade, relíquias, skills e ajuste de coins | Controllers/Repositories |
| Auditoria | SQLite local `valhalla_audits` | migration local e `ConfigAudit` |
| Reload | fila `ConfigReloadRequest`, best-effort | repositories de escrita |
| Demo | `VALHALLA_DEMO_MODE=true` por padrão | `ValhallaDatabase`, `DemoCatalog` |
| Testes | smoke das páginas e testes de domínio | `docs/ARQUITETURA.md` |

### Bloqueios atuais

- Não existe autenticação efetiva nem middleware `auth` nas rotas `/painel`.
- O operador é o literal `operador`; não há RBAC granular.
- O painel não deve executar migrations nos bancos `GameServer`, `UserDB`
  ou `ShopCoin`; o schema é responsabilidade da source.
- O poller C++ precisa reconhecer cada literal de `Resource`; uma fila sem
  consumidor é uma falha silenciosa.
- A maioria das migrations SQL está marcada como **Não auditada**.
- Skin ainda não é reaplicada retroativamente a itens já presentes na mochila.
- Loja de Coins está somente leitura.
- PvP, Arnold/Gandalf e partes de Battle Royale dependem de contratos C++ ainda
  não prontos para administração geral.
- O snapshot de raridade de 21/09/2026 está em perfil de TESTE.

## 3. Mapa de integração

| Origem | Uso | Regra |
|---|---|---|
| SQLite local | usuários, jobs/cache e auditoria local | migrations Laravel permitidas |
| `GameServer` | itens, drops, monstros, raridade, relíquias e skills SQL | aplicar `docs/sql/*-up.sql`; nunca Laravel migrate |
| `UserDB` | contas, Coins e Time | transação, saldo mínimo e idempotência |
| `ShopCoin` | abas e itens da loja | CRUD após contrato de publicação |
| `PainelDB` | auditoria, versões, locks e reload | confirmar schema e consumidor C++ |
| `ClanDB` | clãs, membros, pedidos e baú | integração futura; migrations não auditadas |
| `ServerDB` | Battle Royale, custody e ledgers | fora do primeiro corte |
| `items.h` | identidade/nome/categoria | somente leitura |
| `.ini` de skills | configuração legada | backup byte a byte e reload `skill_ini` |
| arquivos do cliente | ícones e meshes | somente leitura |

## 4. SQL e backups identificados

### Snapshots

Em `docs/sql/snapshot/`, geração de 21/09/2026:

- `snapshot-gameserver-20260921.sql`: catálogo, monstros/drops, raridade,
  relíquias e skills/config do `GameServer`;
- `snapshot-paineldb-20260921.sql`: auditoria, versões, locks e reload;
- `snapshot-shopcoin-20260921.sql`: abas e itens da loja;
- `snapshot-serverdb-20260921.sql`: Battle Royale;
- `snapshot-gamedb-logdb-20260921.sql`: pet authority e banimentos.

São snapshots de dados, não schema. A restauração documentada é: restaurar o
backup base `.bak` em ambiente isolado, executar os `*-up.sql` em ordem e só
depois aplicar os snapshots. O snapshot de raridade é explicitamente TESTE;
produção exige decisão manual via `docs/sql/32-rarity-perfil-producao-up.sql`.

Também existe `BackupLimpoValhalla.rar` na raiz da source, mas seu conteúdo
não deve ser considerado backup SQL validado sem restauração isolada e
evidência.

### Migrations prioritárias

| Ordem | Script | Efeito | Status documental |
|---:|---|---|---|
| 1 | `20-config-audit-up.sql` | auditoria/versionamento | não auditada |
| 2 | `21-config-reload-skill-resource-up.sql` | recurso `skill_ini` | não auditada |
| 3 | `22-item-skin-code-up.sql` | `SkinCode` nas tabelas de item | não auditada |
| 4 | `23-skill-balance-up.sql` + seed 24 | skills em SQL | validar Blocos C/D |
| 5 | `25-config-reload-skill-sql-resource-up.sql` | recurso `skill_sql` | não auditada |
| 6 | `30-rarity-chance-schema-up.sql` | grupos/chances/modificadores | não auditada |
| 7 | `31-reliquia-schema-up.sql` | `ReliquiaDef/ReliquiaBonus` | não auditada |
| 8 | `35-rarity-bonus-up.sql` | `RarityBonus` + seed | não auditada |
| 9 | `36-skill-tier5-seed-up.sql` | Tier 5 dormente | decisão explícita |

O registro oficial é `docs/REGISTRO-MIGRATIONS.md`. Script criado não é
evidência de aplicação; registrar ambiente, data e saída do executor ou query
de round-trip.

## 5. Contratos obrigatórios

### Publicação

Toda escrita administrativa deve:

1. autenticar o operador e verificar permissão;
2. carregar estado e versão atual;
3. validar as regras de domínio do C++;
4. mostrar diff e exigir confirmação;
5. adquirir lock/versionamento;
6. criar snapshot/backup;
7. gravar em transação;
8. registrar auditoria com operador, IP, motivo, antes/depois e resultado;
9. inserir pedido de reload com `Resource` documentado;
10. acompanhar sucesso, erro ou timeout;
11. oferecer rollback da versão anterior.

### Resources de reload

| Resource | Escopo |
|---|---|
| `rarity_group` | grupos/faixas de raridade |
| `rarity_chance` | chances |
| `rarity_mod` | modificadores |
| `relic_def` | definição de relíquia |
| `relic_bonus` | bônus de relíquia |
| `skill_ini` | skills em arquivo |
| `skill_sql` | skills/cooldown SQL |
| `item_skin` | skin no catálogo |

Confirmar no código/build da source que o poller ou endpoint consome cada
resource e atualiza o status da fila antes de liberar produção.

## 6. Roadmap executável

### Fase 0 — baseline e segurança

- [ ] Criar ambientes local, staging e produção.
- [ ] Configurar DSNs/ODBC e testar `php artisan valhalla:probe`.
- [ ] Manter `VALHALLA_DEMO_MODE=true` até os gates de staging.
- [ ] Restaurar `BackupLimpoValhalla.rar` ou `.bak` somente isoladamente.
- [ ] Auditar tabelas, colunas, constraints e permissões via `sys.*`.
- [ ] Completar `docs/REGISTRO-MIGRATIONS.md` com evidências.
- [ ] Implementar autenticação, sessões, CSRF e RBAC: `viewer`,
      `skill_editor`, `rarity_editor`, `relic_editor`, `economy_admin`
      e `super_admin`.
- [ ] Remover operador hardcoded e exigir motivo para alterações sensíveis.
- [ ] Adicionar rate limit, allowlist de rede e proteção contra repetição.

**Saída:** painel autenticado, somente leitura em staging e diagnóstico dos
bancos sem alterações.

### Fase 1 — infraestrutura de publicação

- [ ] Concluir `ConfigVersion`, `ConfigSnapshot`, `ConfigLock` e
      `ConfigReloadRequest`, conforme `docs/174-contrato-config-fase1.md`.
- [ ] Fazer auditoria principal no `PainelDB`; manter cópia local como apoio.
- [ ] Criar serviço comum de diff, confirmação, lock, retry, timeout e rollback.
- [ ] Expor estados `pending`, `running`, `succeeded`, `failed` e
      `expired`.
- [ ] Confirmar o consumidor no `OnSever.cpp` e testar round-trip por resource.
- [ ] Criar exportação/backup automático antes da publicação.

**Saída:** uma publicação de teste pode ser feita e revertida end-to-end.

### Fase 2 — raridade e relíquias

- [ ] Aplicar/auditar migrations 30 e 31.
- [ ] Comparar banco real com `snapshot-gameserver-20260921.sql`.
- [ ] Implementar validações dos docs 174: denominador 10.000.000, Common
      como resto, grupos válidos, limites, slots 0..11 e slot 11 bloqueado.
- [ ] Implementar diff por grupo/slot, lock e rollback.
- [ ] Liberar `rarity_group`, `rarity_chance`, `rarity_mod`, `relic_def`
      e `relic_bonus` apenas após validar reload.

### Fase 3 — skills

- [ ] Manter edição de `.ini` com backup byte a byte durante a transição.
- [ ] Validar os inventários CSV de skills e o de-para de arrays.
- [ ] Aplicar migrations 23–25 somente após validar loader e pendências de 176.
- [ ] Implementar `SkillDefinition`, parâmetros, dez valores por parâmetro e
      cooldown/duração/intervalo separados.
- [ ] Manter Tier 5 dormente até concluir persistência, UI, loader e seed.
- [ ] Testar classes, níveis, negativos, NaN/infinito, duplicatas, reload e
      reconexão.

### Fase 4 — bônus de raridade, catálogo e skins

- [ ] Aplicar/auditar migration 35 e conferir banda `gte103`.
- [ ] Implementar `StatCode` e `ItemLevelBand` como vocabulário fechado.
- [ ] Comparar o seed com `DefaultRarityBonus()` do C++.
- [ ] Aplicar/auditar migration 22 antes de liberar skin.
- [ ] Corrigir no C++ o chamador do re-carimbo de itens já existentes.
- [ ] Testar proteção `WV`, identidade `Code`, categoria, ícone, rollback e
      reload `item_skin`.

### Fase 5 — economia, contas e loja

- [ ] Completar CRUD de `CoinShop`, abas e itens com publicação/versionamento.
- [ ] Implementar Coins/Time com `int64`, saldo mínimo, transação única,
      idempotency key e auditoria.
- [ ] Separar leitura de conta das operações administrativas de alto risco.
- [ ] Planejar VIP somente após `VipEntitlement` e ledger transacionais.
- [ ] Testar concorrência, retry, reconnect, overflow e rollback.

### Fase 6 — clãs e recursos avançados

- [ ] Auditar migrations `docs/sql/clan/001..011` em `ClanDB`.
- [ ] Validar PKs, unicidade, FKs, roles, pedidos, baú, mark blob e journal.
- [ ] Criar painel de clãs só após criação/cobrança/reconciliação atômicas.
- [ ] Adiar baú, custódia e inventário até o journal persistente estar aplicado.
- [ ] Tratar Battle Royale, SoD/Bellatra, VIP, PvP e Arnold/Gandalf como
      projetos próprios, seguindo seus contratos C++.

## 7. Critérios de aceite

Uma fase só termina quando:

- schema aplicado e evidenciado no registro;
- painel lê o banco correto, sem fixture silenciosa;
- C++ e painel validam os mesmos limites;
- publicação gera diff, snapshot, auditoria e reload;
- servidor confirma sucesso ou expõe erro recuperável;
- rollback restaura o estado anterior;
- existem testes de concorrência, retry, reconexão e falha parcial;
- operador e permissão aparecem na auditoria;
- cliente, source e configuração são compatíveis;
- staging foi validado antes de produção.

## 8. Próximo incremento recomendado

Implementar primeiro a Fase 0 junto da Fase 1 mínima: autenticação/RBAC,
auditoria real no `PainelDB`, registro de migrations e serviço de publicação
com lock/versionamento. Depois, validar em staging um recurso de baixo risco
(`rarity_group` ou `relic_def`) com snapshot e rollback.

Não habilitar ainda escrita real de skills, skins, moedas, clãs ou Battle
Royale. O código atual fornece a UI e parte dos repositories, mas ainda não
garante governança e confirmação de publicação end-to-end.

## 9. Fontes analisadas

### Painel

`README.md`, `docs/ARQUITETURA.md`, `config/valhalla.php`,
`routes/web.php`, `ValhallaDatabase`, `ItemSkinRepository`,
`RarityRepository`, `RelicRepository`, `SkillFileRepository`,
`CoinAdminRepository` e migrations locais.

### Source

`docs/00-ambiente.md`, `docs/README.md`, `docs/PENDENCIAS.md`,
`169-roadmap-painel-bonus-skills-cooldown-raridade-reliquias.md`,
`171-projeto-laravel-painel-balanceamento.md`,
`173-sod-seguranca-painel.md`, contratos 174/175/176/179,
auditorias e retomadas de clã 167/168, levantamentos 24/120,
`186-memoria-registro-migrations.md`, `REGISTRO-MIGRATIONS.md`,
`docs/sql/snapshot/README.md`, snapshots de 21/09/2026,
scripts SQL 20–36, migrations `docs/sql/clan/001..011` e
`docs/item-migration/`.

