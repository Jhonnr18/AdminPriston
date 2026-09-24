# Fase 6 — clãs

## Entrega inicial

- Conexão clandb separada no painel.
- Tela somente leitura de saúde do ClanDB.
- Inventário esperado das migrations 001–011.
- Verificação de existência e contagem das tabelas de clã.
- Auditoria de PKs, FKs e índices de unicidade esperados.
- Alertas para ausência de pedidos de entrada e do
  ClanChestMutationJournal.
- Nenhuma escrita de clã, baú, custódia ou inventário foi habilitada.

## Critério para liberar escrita

Aplicar e auditar as migrations na ordem definida pela source, confirmar
PKs/uniqueness/FKs, validar o journal e executar testes de reconciliação antes
de criar operações administrativas no painel.
