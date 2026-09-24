# Fase 6 — clãs

## Entrega inicial

- Auditoria alinhada ao Clan Core moderno da source:
  ClanWindow -> ClanProtocol -> OnSever -> ClanRuntime -> SqlClanRepository ->
  ClanDB.
- CL/UL tratados como storage persistido compatível, não como o fluxo legado
  WebDB/ASP.
- Conexão clandb separada no painel.
- Tela somente leitura de saúde do ClanDB.
- Inventário esperado das migrations 001–011.
- Verificação de existência e contagem das tabelas de clã.
- Auditoria de PKs, FKs e índices de unicidade esperados.
- Status derivado das migrations: aplicada, pendente ou validação manual para
  as migrations que alteram dados, como 007a.
- Alertas para ausência de pedidos de entrada e do
  ClanChestMutationJournal.
- Nenhuma escrita de clã, baú, custódia ou inventário foi habilitada.

O código moderno está marcado pela source como CODE COMPLETE /
OPERATIONAL VALIDATION PENDING. Isso exige separar implementação existente de
prova de migration aplicada, build, deployment e teste in-game.

## Critério para liberar escrita

Aplicar e auditar as migrations na ordem definida pela source, confirmar
PKs/uniqueness/FKs, validar o journal e executar testes de reconciliação antes
de criar operações administrativas no painel.
