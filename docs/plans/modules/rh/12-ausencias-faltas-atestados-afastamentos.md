# 12 — Ausências: Faltas, Atestados e Afastamentos

> Como o módulo trata as três faces de uma **ausência**: a **falta/ocorrência** (ausência pontual — um dia/algumas horas), o **atestado** (documento que justifica/abona, com **workflow de análise**) e o **afastamento** (período — já modelado em [06](06-linha-do-tempo.md)). Este documento acrescenta o **atestado como entidade com máquina de estados** e a **falta/ocorrência**, e descreve como os três se relacionam. O **schema é definido em [01](01-modelo-de-dominio.md)** (§C2 afastamentos, §C3 atestados, §C4 ocorrências, §4.2 enums, §10 permissões — fonte de verdade).
>
> Pacote: `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · views `rh::` · **PostgreSQL 16** · multi-tenant por `empresa_id`. Decisão de modelagem em [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md).

Relacionados: [01](01-modelo-de-dominio.md) · [04 §5](04-catalogos-configuraveis.md) · [05](05-organograma-acl-hierarquica.md) · [06](06-linha-do-tempo.md) · [07](07-jornada-horas-extras-folha.md) · [adrs/ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md)

---

## 1. Três conceitos relacionados (não confundir)

O cliente pediu "controle de faltas, atestados e afastamentos". São **três coisas distintas** que se cruzam:

| Conceito               | É um…                        | Tabela ([01](01-modelo-de-dominio.md)) | Onde é documentado                              |
| ---------------------- | ---------------------------- | -------------------------------------- | ----------------------------------------------- |
| **Falta / ocorrência** | **fato pontual** (dia/horas) | `ocorrencias` (§C4)                    | **este doc** (§3)                               |
| **Atestado**           | **documento** que justifica  | `atestados` (§C3)                      | **este doc** (§2) — entidade com workflow       |
| **Afastamento**        | **período** (dias→meses)     | `funcionario_afastamentos` (§C2)       | **[06 §5](06-linha-do-tempo.md)** (já modelado) |

A relação entre eles, em uma frase:

> Uma **falta** é o fato ("não veio sexta"). Um **atestado** é o documento que pode **abonar** essa falta (ou abonar horas, ou — se longo — **virar afastamento**). Um **afastamento** é o período de ausência formalizado (férias, INSS, licença).

```
                          ┌─────────────► abona HORAS de um dia ──► ocorrencia (abonada)
   atestado (aprovado) ───┼─────────────► abona DIAS ────────────► ocorrencia(s) (justificada/abonada)
                          └─────────────► > 15 dias ─────────────► afastamento INSS (06 §5)  [suspende contrato]
```

**Fronteira deste doc com o [06](06-linha-do-tempo.md):** o **afastamento** (a tabela `funcionario_afastamentos`, as Actions `RegistrarAfastamentoAction`/`EncerrarAfastamentoAction`, a conciliação de `status` e os eventos `inicio_afastamento`/`fim_afastamento` na linha do tempo) **permanece no [06](06-linha-do-tempo.md)** — não é reescrito aqui. Este documento acrescenta **atestado** e **falta/ocorrência** e mostra como eles **alimentam** o afastamento de [06](06-linha-do-tempo.md).

---

## 2. Atestado como entidade com workflow

### 2.1 Por que entidade, e não "só um anexo"

Na fundação ([06 §5](06-linha-do-tempo.md)), "atestado" aparecia apenas como **anexo** de um afastamento (`tipos_afastamento.exige_atestado` + `Anexo`). Isso cobre o atestado que **já virou** afastamento, mas **não** cobre o ciclo real: um atestado **chega** (por vários canais), **espera análise**, é **aprovado ou rejeitado**, e só então **abona** algo. Modelá-lo como **entidade com máquina de estados** (`atestados`, [01 §C3](01-modelo-de-dominio.md)) dá o que o anexo não dá: **origem**, **status**, **quem analisou**, **o que abonou**. Decisão e alternativas em [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md).

### 2.2 Estrutura (ref. [01 §C3](01-modelo-de-dominio.md))

`atestados` (`[E][S][A][Anx]`) — campos-chave (schema completo em [01 §C3](01-modelo-de-dominio.md)):

| Coluna                                                                  | Papel                                                                         |
| ----------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `tipo`                                                                  | natureza (médico/odontológico/acompanhante) — domínio leve                    |
| `data_emissao` / `data_inicio`                                          | data do atestado / início do período coberto                                  |
| `dias_abonados` / `minutos_abonados`                                    | quanto abona (dias inteiros ou parte do dia — convenção [01 §0], minutos)     |
| `cid`                                                                   | **dado de saúde — LGPD art. 11** (`encrypted` + `rh.atestados.ver_cid`, §2.6) |
| `anexo_id`                                                              | imagem/PDF do atestado (`Anexo` do core, disco privado)                       |
| `status`                                                                | enum `StatusAtestado` (§2.4)                                                  |
| `origem`                                                                | enum `OrigemAtestado` (§2.3)                                                  |
| `afastamento_id`                                                        | preenchido **quando** o atestado gera afastamento (§5)                        |
| `registrado_por` / `analisado_por` / `analisado_em` / `motivo_rejeicao` | trilha de quem lançou e quem analisou                                         |

### 2.3 Origem — os canais de entrada (`OrigemAtestado`)

Um atestado chega por caminhos diferentes; a coluna `origem` registra qual ([01 §4.2](01-modelo-de-dominio.md)):

| `origem`             | Quem lança                                                      | Nasce em                 | Observação                                                              |
| -------------------- | --------------------------------------------------------------- | ------------------------ | ----------------------------------------------------------------------- |
| `portal_colaborador` | o próprio colaborador (self)                                    | `pendente`               | envia foto/PDF pelo portal ([05 §9](05-organograma-acl-hierarquica.md)) |
| `gestor`             | o gestor da subárvore ([05](05-organograma-acl-hierarquica.md)) | `pendente`               | recebeu por WhatsApp/papel e registra para o RH conciliar               |
| `rh`                 | RH/admin                                                        | `pendente` ou `aprovado` | o RH pode já aprovar ao lançar (se tem `aprovar`)                       |
| `importacao`         | carga em lote                                                   | `pendente`               | evolução — importação de atestados ([11](11-importacao-exportacao.md))  |

> A coluna `origem` é o que dá sentido aos rótulos de UI "enviar" (colaborador) vs "registrar" (gestor/RH) — ambos são `criar` no nível de permissão ([01 §10](01-modelo-de-dominio.md)). Quando o RH lança **já aprovado** (`origem=rh` + `status=aprovado`), a Action de criação aplica o **efeito de abono** (§4) na mesma transação — um atestado lançado-aprovado não fica sem efeito.

### 2.4 Máquina de estados (`StatusAtestado`)

Cinco estados. A fonte da máquina é `StatusAtestado::podeTransicionarPara()` / `isFinal()` ([01 §4.2](01-modelo-de-dominio.md)); a matriz abaixo é a **definição canônica** das transições (espelhada no [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md) §Decisão). Diagrama:

```
                analisar                 aprovar
   [pendente] ───────────► [em_analise] ─────────► [aprovado] ──estornar──► [estornado]  ⊗ terminal
       │  │                     │                 (dispara efeitos §5)
       │  │ aprovar (direto)    │ rejeitar
       │  └─────────────────────┴───────────────► [aprovado]
       │ rejeitar (direto)
       └────────────────────────────────────────► [rejeitado]  ⊗ terminal (exige motivo_rejeicao)
```

**Transições válidas** (verbo → permissão de [01 §10](01-modelo-de-dominio.md); tudo conferido no servidor por Action + Policy, §2.6):

| De ↓ \ Para → | `em_analise`  | `aprovado`   | `rejeitado`   | `estornado`   |
| ------------- | ------------- | ------------ | ------------- | ------------- |
| `pendente`    | ✅ `analisar` | ✅ `aprovar` | ✅ `rejeitar` | ❌            |
| `em_analise`  | —             | ✅ `aprovar` | ✅ `rejeitar` | ❌            |
| `aprovado`    | ❌            | —            | ❌            | ✅ `estornar` |
| `rejeitado`   | ❌            | ❌           | — (terminal)  | ❌            |
| `estornado`   | ❌            | ❌           | ❌            | — (terminal)  |

**Transições proibidas (explícitas) e o que fazer no lugar:**

- **`aprovado → rejeitado`** — proibido. `rejeitar` só sai de `pendente`/`em_analise`. Aprovou por engano? Use **`estornar`** (`aprovado → estornado`), que reverte o efeito (§4).
- **`aprovado → em_analise` / `aprovado → pendente`** — proibido. De `aprovado` só se sai por `estornar`; "reanalisar" = estornar e lançar **novo** atestado.
- **`pendente|em_analise → estornado`** — proibido: não há efeito a estornar antes da aprovação (rejeitar/deletar é o caminho).
- **`rejeitado → *` e `estornado → *`** — proibido: ambos são **terminais absolutos**. Correção = **novo atestado** (mesma disciplina append-only da linha do tempo — [06 §3](06-linha-do-tempo.md)).

**Estados:**

- **`pendente`** — recém-criado, aguardando o RH (enviado pelo colaborador ou registrado pelo gestor).
- **`em_analise`** — o RH/gestor "pegou" para conferir (passo **opcional**; pode-se aprovar/rejeitar direto de `pendente`).
- **`aprovado`** — ao entrar, a Action aplica o **efeito** (§5): abona horas, abona dias ou gera afastamento. **Não é terminal absoluto**: reversível **só** por `estornar` (→ `estornado`). É "final operacional" (`isFinal()` não admite aprovar/rejeitar de novo), com o estorno como única exceção controlada.
- **`rejeitado`** — **terminal absoluto**; exige `motivo_rejeicao`; não abona nada.
- **`estornado`** — **terminal absoluto**; resultado de `estornar` um `aprovado`: reverte o abono (a ocorrência abonada volta a injustificada / o afastamento gerado é encerrado), preservando a trilha. Espelha `horas_extras.cancelada`.

Transições implementadas por Actions (`AnalisarAtestadoAction`, `AprovarAtestadoAction`, `RejeitarAtestadoAction`, `EstornarAtestadoAction`) e guardadas por permissão (§2.6), seguindo o padrão de máquina de estados das horas extras ([07 §5](07-jornada-horas-extras-folha.md)). Toda transição fora da matriz lança exceção de domínio (transição inválida) — testada em §11.

### 2.5 Papéis e responsabilidades (× ACL hierárquica)

Quem faz o quê, sempre sob a **ACL de subárvore** ([05](05-organograma-acl-hierarquica.md)):

| Papel (posição)    | Pode                                                                                          | Escopo (ACL [05](05-organograma-acl-hierarquica.md))                                 |
| ------------------ | --------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| **Colaborador**    | **enviar** o próprio atestado (`portal_colaborador`), **acompanhar** o status                 | só **a si mesmo** (auto-visibilidade — [05 §8.6](05-organograma-acl-hierarquica.md)) |
| **Gestor / líder** | **registrar** atestado de subordinado, **conferir**                                           | sua **subárvore**                                                                    |
| **RH / admin**     | **analisar**, **aprovar**, **rejeitar**, **conciliar** (gerar abono/afastamento), **ver CID** | empresa toda (`rh.funcionarios.ver_todos`)                                           |
| **Super-admin**    | bypass                                                                                        | tudo                                                                                 |

A análise/aprovação é, por padrão, do **RH**; um cliente pode delegar a aprovação ao **gestor** da subárvore (mesma mecânica de aprovação restrita à cadeia das horas extras — [07](07-jornada-horas-extras-folha.md)).

### 2.6 Permissões e LGPD do atestado

- Permissões ([01 §10](01-modelo-de-dominio.md)): `rh.atestados.{listar, criar, editar, analisar, aprovar, rejeitar, estornar, deletar, restaurar, excluir_permanente}` **+ `rh.atestados.ver_cid`**.
- **`cid` é dado de saúde** (LGPD art. 11) — **mesmo rigor do `cid` de afastamento**, conforme a **matriz única de dados sensíveis** ([01 §8.1](01-modelo-de-dominio.md) · [06 §5.3](06-linha-do-tempo.md)): `encrypted`, fora de auditoria, **mascarado** sem `rh.atestados.ver_cid`. O colaborador, ao enviar, **pode** informar o CID, mas **não** o vê de volta mascarado de outros; o gestor vê o atestado mas **não** o CID sem a permissão.
- Tudo conferido no **servidor** (Policy + escopo), nunca só na UI.

---

## 3. Faltas / ocorrências

### 3.1 Estrutura (ref. [01 §C4](01-modelo-de-dominio.md))

`ocorrencias` (`[E][S][A]`) registra a ausência **pontual** — campos-chave (schema em [01 §C4](01-modelo-de-dominio.md)):

| Coluna                | Papel                                                                             |
| --------------------- | --------------------------------------------------------------------------------- |
| `data`                | o dia da ocorrência                                                               |
| `tipo`                | enum `TipoOcorrencia`: `falta` (dia), `atraso`, `saida_antecipada`                |
| `minutos`             | duração (atraso/saída; falta de dia inteiro pode ficar null) — convenção [01 §0]  |
| `justificada`         | houve justificativa aceita?                                                       |
| `abonada`             | foi abonada (não desconta)? — tipicamente por atestado aprovado (§4)              |
| `atestado_id`         | FK→`atestados` quando o abono veio de um atestado                                 |
| `tipo_afastamento_id` | classificação trabalhista (lê as flags de [04 §5](04-catalogos-configuraveis.md)) |

### 3.2 Classificação: justificada / injustificada / abonada

A classificação é **derivada** das flags (sem coluna de status):

- **Injustificada** = `NOT justificada AND NOT abonada` — conta como falta (impacta frequência/DSR — §6).
- **Justificada** = `justificada = true` — aceita (ex.: atestado, motivo legal); pode ou não ser abonada.
- **Abonada** = `abonada = true` — não desconta (atestado aprovado, decisão do RH).

As flags conversam com `tipos_afastamento` ([04 §5](04-catalogos-configuraveis.md)): `conta_como_falta` e `remunerado` ditam o efeito na apuração (Fase 3 — §6).

### 3.3 Origem do dado (sem ponto eletrônico até a Fase 5)

Na Fase 1/2 **não há ponto eletrônico** ([09](09-roadmap-fases.md)) — a ocorrência é **lançada manualmente** por gestor/RH (a falta é percebida e registrada). A partir da **Fase 5** (espelho de ponto) e **Fase 6** (dispositivo), as ocorrências passam a ser **derivadas das marcações** confrontadas com a escala ([07](07-jornada-horas-extras-folha.md) / [09](09-roadmap-fases.md)) — a tabela e a semântica são as mesmas; muda só a **origem** do dado (manual → apurada). Modelar `ocorrencias` agora é o que evita retrabalho quando o ponto chegar.

---

## 4. Abono — como um atestado aprovado abona uma falta

Aprovar um atestado (§2.4) dispara `AprovarAtestadoAction`, que aplica **um** de três efeitos conforme os dados (e o limiar de 15 dias):

| Cenário                                                       | Efeito da aprovação                                                                                                                |
| ------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Abona horas de um dia** (`minutos_abonados`)                | cria/atualiza a `ocorrencia` de `atraso`/`saida_antecipada` do dia marcando `abonada=true`, `atestado_id` apontado                 |
| **Abona dias** (`dias_abonados`, dentro do limiar)            | marca as `ocorrencias` de `falta` dos dias como `justificada=true`/`abonada=true` (ou cria-as já abonadas), `atestado_id`          |
| **Tipo com `suspende_contrato`** (ex.: INSS, acima do limiar) | **gera afastamento** (§5): chama `RegistrarAfastamentoAction` ([06 §5.2](06-linha-do-tempo.md)) e grava `atestados.afastamento_id` |

- O **abono** liga `ocorrencias.atestado_id` → `atestados.id`, deixando a trilha "esta falta foi abonada por aquele atestado".
- A **classificação** da ocorrência abonada usa um `tipo_afastamento` adequado (ex.: "Atestado ≤15d" — [04 §5](04-catalogos-configuraveis.md)); a flag operativa do abono é **`conta_como_falta=false`** (a falta abonada não pesa na frequência) + `remunerado` — **não** `exige_atestado` (essa só obriga o anexo). A apuração de folha lê essas flags na Fase 3.
- **Estorno**: um atestado **aprovado** é desfeito por **`estornar`** (`rh.atestados.estornar` → status `estornado`), que **reverte** o abono (a ocorrência volta a injustificada / o afastamento gerado é encerrado), preservando a trilha (§2.4). `rejeitar` é só para `pendente`/`em_analise`.

---

## 5. Vínculo com afastamento (a fronteira com [06](06-linha-do-tempo.md))

Quando o atestado leva a um `tipo_afastamento` com **`suspende_contrato=true`** (tipicamente auxílio-doença INSS), ele **não** vira só abono — vira **afastamento**. O limiar legal de 15 dias é um **parâmetro configurável** (`config('rh.atestado.dias_limite_inss')`, default 15) que **sugere** o tipo INSS na análise; a decisão é a **escolha do `tipo_afastamento`** pelo operador (catálogo-dirigido — [04 §5](04-catalogos-configuraveis.md)), não um `if` fixo no código:

- A aprovação chama **`RegistrarAfastamentoAction`** ([06 §5.2](06-linha-do-tempo.md)) com o `tipo_afastamento` de INSS (flag `suspende_contrato=true` — [04 §5](04-catalogos-configuraveis.md)), passando `data_inicio`, `cid` e o `anexo` do atestado.
- Isso, **em [06](06-linha-do-tempo.md)**, cria a linha `funcionario_afastamentos`, grava o evento `inicio_afastamento` na linha do tempo e concilia o `status` do funcionário para `afastado`. O retorno usa `EncerrarAfastamentoAction` ([06 §5.2](06-linha-do-tempo.md)) → evento `fim_afastamento`.
- O atestado guarda `afastamento_id` (liga documento ↔ período). **A mecânica do afastamento não é reescrita aqui** — é a de [06](06-linha-do-tempo.md); este doc só **dispara** e **referencia**.

### 5.1 Afastamento INSS — caso especial (acompanhamento; eSocial S-2230 é Fase 4)

O afastamento por INSS (auxílio-doença > 15 dias) tem particularidades de **acompanhamento**: prazo previsto × efetivo, prorrogação, perícia, retorno. O **fluxo de abertura/acompanhamento** usa as estruturas de [06](06-linha-do-tempo.md) (período + flags + eventos). A **transmissão eSocial S-2230** (afastamento temporário), alimentada por `tipos_afastamento.codigo_esocial` e `funcionario_afastamentos`, é **Fase 4** — fronteira já documentada em [09 §5](09-roadmap-fases.md) e [00 §4.1/§5](00-prd.md). A Fase 1/2 **não transmite**; só registra no formato certo.

---

## 6. Efeito na frequência/DSR/folha — fronteira de fase

Faltas e abonos **afetam a folha** (desconto de DSR sobre falta injustificada, perda/manutenção de remuneração conforme `tipos_afastamento.remunerado`/`conta_como_falta`). Mas **a apuração é Fase 3** ([07](07-jornada-horas-extras-folha.md) / [09 §4](09-roadmap-fases.md)): na Fase 1/2 as `ocorrencias` e seus abonos são **registrados** (o dado existe, classificado), e a folha futura os **consome**. Modelar agora as flags certas (em `ocorrencias` e nos `tipos_afastamento`) é o **contrato** que a apuração lerá — sem retrabalho ([09 §9](09-roadmap-fases.md)).

---

## 7. Self-service (cruza com [05 §9](05-organograma-acl-hierarquica.md) e [03 §11](03-cadastro-pessoa-documentos.md))

O portal do colaborador ([05 §9](05-organograma-acl-hierarquica.md)) ganha, nas fases respectivas:

- **Enviar atestado** (`origem = portal_colaborador`) — anexa foto/PDF, informa datas e (opcional) CID; o atestado nasce `pendente` para o RH analisar. O colaborador **acompanha o status** (pendente → aprovado/rejeitado) mas **não** se autoanalisa.
- **Consultar faltas e afastamentos** (leitura) — vê as próprias `ocorrencias` e `funcionario_afastamentos`, sob a auto-visibilidade ([05 §8.6](05-organograma-acl-hierarquica.md)) e o modo `proprio` ([03 §11](03-cadastro-pessoa-documentos.md)); **não** lança nem abona (isso é do RH/gestor).
- **Defesa no servidor**: enviar é a única escrita do colaborador; analisar/aprovar/abonar são vedados ao modo `proprio` (allowlist da Action — [03 §11.1](03-cadastro-pessoa-documentos.md)).

---

## 8. Acompanhamento (telas e alertas)

Derivados das tabelas, para a operação do RH/gestor:

- **Atestados pendentes de análise** — `atestados WHERE status IN ('pendente','em_analise')`, escopados pela ACL ([05](05-organograma-acl-hierarquica.md)); fila de trabalho do RH/gestor (KPI no dashboard).
- **Afastamentos a retornar** — reusa o relatório de [06 §6.3](06-linha-do-tempo.md) (`data_fim_efetiva IS NULL`, prazo previsto × hoje).
- **Faltas no período** — `ocorrencias` por funcionário/equipe, com a classificação (§3.2); base para política de frequência.
- **Alertas** — atestado parado em `pendente` além de N dias; afastamento sem retorno após o previsto.

---

## 9. Permissões (resumo — canônicas em [01 §10](01-modelo-de-dominio.md))

| Recurso        | Ações                                                                                                                                             |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| `atestados`    | `listar` · `criar` · `editar` · `analisar` · `aprovar` · `rejeitar` · `estornar` · `deletar` · `restaurar` · `excluir_permanente` **+ `ver_cid`** |
| `ocorrencias`  | `listar` · `criar` · `editar` · `deletar` · `restaurar` · `excluir_permanente` _(+ `abonar` opcional)_                                            |
| `afastamentos` | (em [06 §7](06-linha-do-tempo.md) / [01 §10](01-modelo-de-dominio.md)) — `registrar`/`encerrar` + lixeira + `ver_cid`                             |

UI: colaborador "enviar" / gestor "registrar" = `criar` (discriminado por `origem`); "aprovar"/"rejeitar" são as transições (§2.4).

---

## 10. Faseamento (fundação Fase 1 × workflow completo Fase 2)

| Fase                  | O que entra                                                                                                                                                                                                                                                                      |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Fase 1 (fundação)** | `funcionario_afastamentos` + anexo + flags ([06 §5](06-linha-do-tempo.md)); as tabelas `atestados` (§C3) e `ocorrencias` (§C4) **existem** no schema ([01](01-modelo-de-dominio.md)) — fundação aditiva. Atestado como **anexo de afastamento** já funciona.                     |
| **Fase 2 (completo)** | **workflow do atestado** (estados, análise, aprovação, origem), **faltas/ocorrências** com lançamento e classificação, **abono** (atestado→ocorrência/dias), **afastamento INSS** com acompanhamento. É o tema de "Gestão de ausências e tempo" do [09 §3](09-roadmap-fases.md). |
| **Fase 3**            | apuração de frequência/DSR/folha que **consome** faltas e abonos ([07](07-jornada-horas-extras-folha.md)).                                                                                                                                                                       |
| **Fase 4**            | eSocial **S-2230** (afastamento) — [09 §5](09-roadmap-fases.md).                                                                                                                                                                                                                 |
| **Fase 5/6**          | `ocorrencias` passam a ser **apuradas do ponto** (não mais só manuais) — §3.3.                                                                                                                                                                                                   |

Decisão (entidade-com-workflow vs anexo simples; relação falta/atestado/afastamento; fronteira de fase) em [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md).

---

## 11. Checklist de implementação

**Fase 1 (fundação — aditiva):**

- [ ] Migrations `atestados` ([01 §C3](01-modelo-de-dominio.md): `cid` encrypted, índices, CHECKs de dias/minutos) e `ocorrencias` ([01 §C4](01-modelo-de-dominio.md)); enums `StatusAtestado`/`OrigemAtestado`/`TipoOcorrencia` ([01 §4.2](01-modelo-de-dominio.md)) com lógica; factories.
- [ ] Models `Atestado` (`Auditavel`, `SoftDeletes`, `cid` em `atributosNaoAuditados()` + `encrypted`, FK `afastamento_id`/`anexo_id`) e `Ocorrencia` (FKs `atestado_id`/`tipo_afastamento_id`).
- [ ] Atestado como **anexo de afastamento** já operante via [06 §5](06-linha-do-tempo.md) (sem o workflow ainda).

**Fase 2 (workflow completo):**

- [ ] Máquina de estados (`AnalisarAtestadoAction`/`AprovarAtestadoAction`/`RejeitarAtestadoAction`/`EstornarAtestadoAction`) com transições guardadas por permissão; `AprovarAtestadoAction` aplica o efeito (§4): abona horas/dias (`ocorrencias`) ou gera afastamento (chama `RegistrarAfastamentoAction` de [06](06-linha-do-tempo.md)); `EstornarAtestadoAction` reverte um `aprovado` (→ `estornado`).
- [ ] Lançamento/classificação de `ocorrencias` (manual); abono ligando `atestado_id`; leitura das flags de `tipos_afastamento` ([04 §5](04-catalogos-configuraveis.md)).
- [ ] Telas: `IndexAtestado`/`FormAtestado`/`AtestadoTable` (filtros por status/origem; `cid` mascarado sem `ver_cid`); `IndexOcorrencia`/`FormOcorrencia`/`OcorrenciaTable`; fila de **pendentes de análise** (§8).
- [ ] Self-service ([05 §9](05-organograma-acl-hierarquica.md) / [03 §11](03-cadastro-pessoa-documentos.md)): enviar atestado (portal), acompanhar status, consultar faltas/afastamentos (leitura); defesa no servidor (allowlist).
- [ ] Acompanhamento (§8): pendentes, a retornar ([06 §6.3](06-linha-do-tempo.md)), alertas; KPIs no dashboard.
- [ ] Permissões `rh.atestados.*` (+ `ver_cid`) e `rh.ocorrencias.*` ([01 §10](01-modelo-de-dominio.md)); Policies sob a ACL ([05](05-organograma-acl-hierarquica.md)).
- [ ] Testes Pest: transições válidas/inválidas do atestado; aprovação aplicando cada efeito (horas/dias/afastamento); estorno revertendo; `cid` mascarado sem `ver_cid`; classificação de ocorrência; ACL (colaborador só envia/vê o próprio); tenant scope.
- [ ] Pós-tarefa: `pint`, `prettier`, `phpstan`, `php artisan test`.
