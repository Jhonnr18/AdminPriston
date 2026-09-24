# Fase 3 — Skills

## Implementado no painel

- Edição legada dos \`.ini\` preservando os bytes do arquivo e criando backup
  integral antes da escrita.
- Validação de exatamente dez níveis por parâmetro.
- Bloqueio de valores não numéricos, NaN e infinito.
- Registro de motivo, operador, IP, hash e conteúdo base64 anterior/novo no
  snapshot de publicação.
- Catálogo SQL de \`SkillDefinition\`, \`SkillParameterDef\`,
  \`SkillLevelValue\` e \`SkillCooldown\`.
- Edição dos dez valores SQL de um parâmetro com validação de \`short\`,
  limites declarados, mana não negativa e cooldown entre 0 e 600000 ms.
- Versionamento e reload por \`skill_ini\` e \`skill_sql\`.
- Tier 5 não é ativado nem publicado automaticamente.

## Homologação necessária

As migrations 23–25, o loader SQL C++ e o consumidor de
\`ConfigReloadRequest\` precisam estar instalados no ambiente de destino antes
da primeira publicação SQL. Sem esse contrato, o painel mantém fallback de
reload legado e registra a falha no log; não altera o comportamento do jogo
por conta própria.
