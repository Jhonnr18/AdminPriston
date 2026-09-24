# Fase 5 — economia, contas e loja

## Implementado

- CRUD de CoinShop, abas e itens.
- Transação com lock no ShopCoin.
- Auditoria, snapshot, versionamento e reload da Coin Shop.
- Ajuste administrativo de Coins e Time.
- Valores persistidos como inteiros de 64 bits no ledger local e no UserDB.
- Saldo mínimo zero.
- Chave idempotente obrigatória para novas operações de saldo.
- Repetição da mesma chave retorna o resultado original e não reaplica o delta.

## Fora desta fase

- Loja de Time: a source não possui comando/API de reload equivalente.
- VIP: depende de VipEntitlement e ledger transacional próprios.
- Operações de saldo não disparam reload de configuração, pois UserCoin e
  UserTime são estado de conta lido diretamente do UserDB.

## Homologação

Executar a migration local do ledger antes de habilitar ajustes reais e testar
concorrência, retry, reconnect, overflow e falha parcial com UserDB acessível.
