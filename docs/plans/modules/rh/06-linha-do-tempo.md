# 06 — Linha do Tempo (Histórico Funcional)

> A **história funcional** do colaborador — admissão, promoções, reajustes, transferências, mudanças de cargo, afastamentos e desligamento — modelada como **eventos imutáveis append-only** (`funcionario_eventos`, sem `deleted_at`, com snapshot JSONB), mais a gestão de **afastamentos** (`funcionario_afastamentos`). O **schema é definido em [01](01-modelo-de-dominio.md)** (fonte de verdade); aqui só consumimos os nomes de tabelas/colunas/enums/permissões de lá e descrevemos o comportamento, as Actions, a UI e as regras de integridade.
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · views `rh::` · banco **PostgreSQL 16** · multi-tenant lógico por `empresa_id`.

Relacionados: [01](01-modelo-de-dominio.md) · [03](03-cadastro-pessoa-documentos.md) · [05](05-organograma-acl-hierarquica.md) · [adrs/ADR-RH-005](adrs/ADR-RH-005-historico-eventos-imutaveis.md)

---

## 1. Conceito: o histórico funcional é uma linha do tempo

Um funcionário não é um registro estático: ao longo do vínculo ele é **admitido**, **promovido**, recebe **reajustes/alterações salariais**, é **transferido** de departamento ou de filial, tem o **cargo alterado**, **afasta-se** e retorna, e um dia é **desligado**. Cada um desses fatos é um **evento datado** com um **antes** e um **depois**.

Modelamos essa história como uma **linha do tempo append-only**: a tabela `funcionario_eventos` ([01 §3 C1](01-modelo-de-dominio.md)) recebe **um registro por fato**, na ordem em que os fatos acontecem, e **nunca é editada nem apagada**. Cada evento carrega um **snapshot JSONB** do estado relevante antes e depois da mudança, aplicando o **[ADR-0009 — Snapshots JSONB imutáveis](../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)**: o estado do funcionário **no momento daquele fato** fica "fotografado" e não muda retroativamente quando os mestres (cargo, departamento, tabela salarial) mudarem no futuro.

Por isso `funcionario_eventos` é uma das poucas tabelas do módulo **sem `deleted_at`** — não há lixeira para um fato histórico (ver exceções append-only em [02](02-fase-1-blueprint.md)). A correção de um lançamento errado **não** é um `UPDATE`/`DELETE`: é um **evento compensatório** (estorno) lançado por cima, preservando a trilha completa para fins trabalhistas e de auditoria (guarda legal longa — [01 §8](01-modelo-de-dominio.md)).

**Duas verdades, sincronizadas:**

- A **verdade temporal** mora na linha do tempo (`funcionario_eventos`): _o que aconteceu, quando, e qual era o estado_.
- A **verdade do "agora"** mora em colunas desnormalizadas de `funcionarios` (`cargo_id`, `departamento_id`, `filial_id`, `salario_base_centavos`, `cargo_nivel`, `status`): um **cache** do estado corrente para que listagens, organograma e folha não precisem varrer o histórico a cada consulta.

Quem mantém as duas em sincronia é a **Action** que registra o evento — ela grava o evento **e** atualiza o cache **na mesma transação** (§4). Nunca se altera a coluna "atual" do funcionário "à revelia": toda mudança em salário/cargo/departamento/filial/status após a criação passa por um evento ([03 §7](03-cadastro-pessoa-documentos.md)).

---

## 2. Tipos de evento — `TipoEventoFuncional`

O enum `TipoEventoFuncional` ([01 §4](01-modelo-de-dominio.md), backed `string` + **CHECK constraint** Postgres) é o que **dirige** o registro: ele decide quais colunas dimensionais do evento são preenchidas e como se monta o snapshot. Métodos de lógica do enum: `afetaSalario()` e `afetaLotacao()` (usados pela Action e pela UI para saber o que comparar/exibir); `label()`, `variant()`/`color()` para o badge.

| `tipo_evento`                | O que registra                                                                | Colunas dimensionais preenchidas                                                                | Snapshots (`snapshot_anterior` / `snapshot_novo`)                                                         |
| ---------------------------- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `admissao`                   | Entrada do colaborador (marco zero da linha)                                  | `cargo_id`, `departamento_id`, `filial_id`, `salario_centavos`                                  | `anterior = null`; `novo` = foto da contratação (cargo/departamento/filial/salário/vínculo/regime/status) |
| `promocao`                   | Promoção (normalmente cargo↑ + salário↑)                                      | `cargo_id`, `salario_centavos`, `salario_anterior_centavos` (e `departamento_id` se acompanhar) | foto cargo+nível+salário antes/depois                                                                     |
| `alteracao_salarial`         | Mudança de salário fora de reajuste de tabela (mérito, correção, equiparação) | `salario_centavos`, `salario_anterior_centavos`                                                 | foto salário antes/depois                                                                                 |
| `reajuste`                   | Reajuste coletivo/dissídio (data-base)                                        | `salario_centavos`, `salario_anterior_centavos`                                                 | foto salário antes/depois; `motivo` registra a convenção/percentual                                       |
| `transferencia_departamento` | Troca de lotação organizacional                                               | `departamento_id` (novo)                                                                        | foto departamento (e nome) antes/depois                                                                   |
| `transferencia_filial`       | Troca de filial/estabelecimento                                               | `filial_id` (novo) (e `departamento_id` se realocar)                                            | foto filial antes/depois                                                                                  |
| `mudanca_cargo`              | Mudança de cargo **sem** caráter de promoção (lateral, reenquadramento)       | `cargo_id`, `cargo_nivel` (via snapshot)                                                        | foto cargo+nível antes/depois                                                                             |
| `inicio_afastamento`         | Marca o início de um afastamento na timeline                                  | (referencia o afastamento em §5)                                                                | `novo` = `{ tipo_afastamento, data_inicio, data_fim_prevista, afastamento_id }`                           |
| `fim_afastamento`            | Marca o retorno do afastamento                                                | (referencia o afastamento em §5)                                                                | `novo` = `{ data_fim_efetiva, dias }`                                                                     |
| `desligamento`               | Saída do colaborador                                                          | (encerramento)                                                                                  | `novo` = `{ data_demissao, motivo, status: desligado }`                                                   |

Colunas comuns a todos (de [01 §3 C1](01-modelo-de-dominio.md)): `data_evento` (DATE, **data de efeito** — não a data de digitação), `motivo` (TEXT), `registrado_por_admin_user_id` (FK→`admin_users` — **quem lançou**), `created_at/updated_at`.

> **Regra de preenchimento:** as colunas dimensionais (`cargo_id`/`departamento_id`/`filial_id`/`salario_centavos`/`salario_anterior_centavos`) refletem o **estado novo** resultante do evento (o "anterior" só existe para salário, por ser numérico e útil em relatório). O **estado completo** antes/depois mora nos snapshots JSONB, que são serializados do estado **resolvido** no momento da Action (valores, não apenas IDs) — exatamente o que o ADR-0009 prescreve.

---

## 3. Regra de ouro: imutabilidade (ADR-0009)

A linha do tempo é **append-only**. Disso decorrem invariantes inegociáveis, herdadas do [ADR-0009](../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md):

1. **Nunca `UPDATE` nem `DELETE`** em `funcionario_eventos`. Não há `deleted_at`, não há lixeira, não há edição de evento. O model é `Auditavel`, mas o próprio registro já é o log.
2. **Correção = evento compensatório (estorno).** Lançou um reajuste errado? Não se apaga: lança-se um evento de correção (`alteracao_salarial`) que retorna ao valor correto, com `motivo` explicando o estorno. A história mostra _o erro e a correção_ — é isso que torna a trilha auditável e defensável trabalhisticamente.
3. **Snapshot é "escreve uma vez, lê muitas".** Depois de gravado, o conteúdo do `snapshot_anterior`/`snapshot_novo` **não muda** — mesmo que o cargo, o departamento ou a tabela salarial mudem amanhã. É o que garante que "o salário que constava na minha promoção de 2024" continue sendo aquele.
4. **Snapshot nunca entra em `WHERE` de query operacional.** JSONB de snapshot serve **só para exibição e auditoria**. Filtro, ordenação e junção operacional usam as **colunas tipadas** (`tipo_evento`, `data_evento`, `funcionario_id`, FKs) e as **colunas "atuais"** do funcionário — nunca `snapshot_novo->>'salario'`. (O ADR-0009 é explícito: JSONB não normalizado, sem índice secundário, consulta pontual.)

### 3.1 Reconstrução temporal — "qual era o estado em uma data X?"

Como a linha do tempo é a verdade, dá para **reconstruir o estado do funcionário em qualquer data** sem depender do cache. Por **dimensão** (salário, cargo, departamento, filial), o estado em `X` é o do **último evento com `data_evento <= X` que afeta aquela dimensão**:

```sql
-- Salário vigente em :data para um funcionário (verdade temporal, não o cache):
SELECT salario_centavos
FROM   funcionario_eventos
WHERE  funcionario_id = :id
  AND  data_evento <= :data
  AND  tipo_evento IN ('admissao','promocao','alteracao_salarial','reajuste')
ORDER BY data_evento DESC, id DESC   -- desempate por id (ordem de lançamento)
LIMIT 1;
```

O mesmo padrão (trocando o conjunto de `tipo_evento` por `afetaLotacao()` / cargo) reconstrói **cargo**, **departamento** e **filial** numa data. Em `data_evento = hoje`, o resultado **coincide** com o cache em `funcionarios` — e essa coincidência é uma boa **asserção de teste** (o cache nunca pode divergir da projeção da timeline). O desempate por `id` cobre dois eventos no mesmo dia (a ordem de lançamento vence).

> **Performance:** a reconstrução usa o índice `(funcionario_id, data_evento)` ([01 §7](01-modelo-de-dominio.md)). Para o "agora", **não** se reconstrói — lê-se direto a coluna em `funcionarios` (é o propósito do cache).

---

## 4. Sincronização transacional (evento + cache, atômico)

Toda mudança funcional é uma **Action** (`execute()`, padrão do core — CLAUDE §6) que faz **duas escritas numa única transação** `DB::transaction`: (a) **insere o evento** em `funcionario_eventos` com seus snapshots; (b) **atualiza a(s) coluna(s) "atual(is)"** correspondente(s) em `funcionarios`. Ou as duas acontecem, ou nenhuma — o cache **nunca** diverge da timeline.

Actions previstas (em `packages/extensao-rh/src/Actions/Eventos/`), todas recebendo um **DTO readonly** (nunca `Request` — CLAUDE §5.6) e retornando o evento criado / um DTO:

| Action                         | Evento gravado                     | Coluna(s) de `funcionarios` atualizada(s) na mesma transação                                              |
| ------------------------------ | ---------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `RegistrarAdmissaoAction`      | `admissao`                         | (já definidas na criação — chamada por `CreateFuncionarioAction`, [03](03-cadastro-pessoa-documentos.md)) |
| `RegistrarPromocaoAction`      | `promocao`                         | `cargo_id`, `cargo_nivel`, `salario_base_centavos` (e `departamento_id` se houver)                        |
| `AlterarSalarioAction`         | `alteracao_salarial` \| `reajuste` | `salario_base_centavos`                                                                                   |
| `TransferirDepartamentoAction` | `transferencia_departamento`       | `departamento_id`                                                                                         |
| `TransferirFilialAction`       | `transferencia_filial`             | `filial_id` (e `departamento_id` se realocar)                                                             |
| `MudarCargoAction`             | `mudanca_cargo`                    | `cargo_id`, `cargo_nivel`                                                                                 |
| `RegistrarDesligamentoAction`  | `desligamento`                     | `data_demissao`, `status = desligado` (chamada na demissão — [03](03-cadastro-pessoa-documentos.md))      |

Anatomia de uma Action de evento (ex.: `RegistrarPromocaoAction`):

```text
1. Lê o estado ATUAL do funcionário (cargo/nível/salário) → vira snapshot_anterior + salario_anterior_centavos.
2. Resolve o estado NOVO a partir do DTO (novo cargo_id → novo cargo_nivel; novo salário) → snapshot_novo.
3. DB::transaction:
     a. funcionario_eventos::create({ tipo_evento, data_evento, motivo,
            cargo_id, salario_centavos, salario_anterior_centavos,
            snapshot_anterior, snapshot_novo, registrado_por_admin_user_id });
     b. $funcionario->update({ cargo_id, cargo_nivel, salario_base_centavos });
4. (fora da transação) dispara evento de domínio se aplicável (§4.1).
```

Pontos de projeto:

- **`registrado_por_admin_user_id`** = `auth('admin')->id()` no momento do lançamento (quem operou), **distinto** de `funcionario.admin_user_id` (de quem é a história). Para auditoria de quem mexeu.
- **`cargo_nivel`** é re-resolvido a partir do `cargo_id` novo (mesma regra do cadastro — [01 §0/§3 B1](01-modelo-de-dominio.md)), mantendo o cache do organograma coerente após promoção/mudança de cargo.
- **Validação** em FormRequest + Rules (datas coerentes, `salario_centavos > 0`, cargo/departamento existentes e da empresa via `Rule::exists()->where('empresa_id', ...)`). A Action assume o DTO já válido.
- **Idempotência/concorrência:** a transação serializa a dupla escrita; o desempate por `id` na reconstrução (§3.1) cobre eventos no mesmo `data_evento`.

### 4.1 Eventos de domínio (Laravel) disparados pela timeline

Não confundir **evento funcional** (linha `funcionario_eventos`) com **evento de domínio** (objeto Laravel `Event`+`Listener`). Alguns eventos funcionais **disparam** um evento de domínio para reações desacopladas (CLAUDE §6), **após** o commit:

- `admissao` → provisionamento de acesso / e-mail de boas-vindas (quando há `admin_user_id` vinculado — ver organograma/ACL em [05](05-organograma-acl-hierarquica.md)).
- `desligamento` → revogação de acesso / desligamento de papéis por empresa.
- `inicio_afastamento` / `fim_afastamento` → conciliação de `status` (§5).

Esses listeners são **idempotentes** e tolerantes a reprocessamento; jobs pesados vão para fila (Horizon).

---

## 5. Afastamentos — `funcionario_afastamentos`

Afastamento é um **período** (não um instante), por isso vive em tabela própria, `funcionario_afastamentos` ([01 §3 C2](01-modelo-de-dominio.md)) — soft-deletável e auditável — e **gera eventos** na linha do tempo nas bordas (início e fim).

### 5.1 Estrutura e período

| Coluna                                       | Papel                                                                                                                                                                                    |
| -------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `tipo_afastamento_id`                        | FK→`tipos_afastamento` (`restrictOnDelete`) — catálogo com **flags** `remunerado` / `conta_como_falta` / `suspende_contrato` / `exige_atestado` ([04 §5](04-catalogos-configuraveis.md)) |
| `data_inicio`                                | início do afastamento (DATE)                                                                                                                                                             |
| `data_fim_prevista`                          | previsão (DATE, nullable)                                                                                                                                                                |
| `data_fim_efetiva`                           | retorno real; **`null` = afastamento em curso**                                                                                                                                          |
| `dias`                                       | INTEGER **cache** derivável de início/fim (conferido na escrita)                                                                                                                         |
| `cid`                                        | **dado de saúde — LGPD art. 11** (ver §5.3)                                                                                                                                              |
| `observacao`, `registrado_por_admin_user_id` | quem lançou; nota livre                                                                                                                                                                  |

CHECK `data_fim_efetiva IS NULL OR data_fim_efetiva >= data_inicio` ([01 §3 C2](01-modelo-de-dominio.md)). "Em curso" = `data_fim_efetiva IS NULL`, lido pelo índice `(funcionario_id, data_fim_efetiva)` — base do relatório "afastamentos a retornar" (§6).

### 5.2 Ciclo de vida e reflexo no status do funcionário

O afastamento tem **duas Actions** (em `packages/extensao-rh/src/Actions/Afastamentos/`), cada uma fechando o ciclo evento↔status numa transação:

- **`RegistrarAfastamentoAction`** — cria a linha de `funcionario_afastamentos` (`data_fim_efetiva = null`), grava o evento `inicio_afastamento` na timeline e **concilia o `status`** do funcionário para `afastado` (ou `ferias` quando o `tipo_afastamento` for férias). Se `tipos_afastamento.exige_atestado`, exige o anexo (binário via `anexos`, polimórfico — [01 §3 C2](01-modelo-de-dominio.md)); o `cid` é capturado quando informado (§5.3).
- **`EncerrarAfastamentoAction`** — preenche `data_fim_efetiva`, recalcula `dias` (cache), grava o evento `fim_afastamento` e **devolve o `status`** do funcionário a `ativo` (ou ao estado anterior coerente). Encerrar é a operação **"encerrar/retornar"** — não é editar nem excluir o período.

`StatusFuncionario` ([01 §4](01-modelo-de-dominio.md)) tem os casos `afastado` e `ferias`; o status **derivado do afastamento** é **conciliado pela Action**, nunca digitado solto na tela de cadastro ([03 §9](03-cadastro-pessoa-documentos.md)). As **flags** do tipo (`remunerado`, `conta_como_falta`, `suspende_contrato`) são lidas na exibição e ficam reservadas para a apuração de folha (fronteira em [07](07-jornada-horas-extras-folha.md)); na Fase 1 não há cálculo de folha.

> **Correção de afastamento:** por ser soft-deletável (com lixeira/`ComLixeira`, 3 permissões), um afastamento **lançado por engano** pode ir para a lixeira. Os **eventos** `inicio_afastamento`/`fim_afastamento` que ele gerou na timeline, porém, **permanecem** (append-only) — um estorno se faz com nota/observação, mantendo a trilha. A regra de ouro (§3) vale para a timeline, não para a tabela de período.

### 5.3 CID — dado de saúde protegido (LGPD art. 11)

O `cid` (código da doença) é **categoria especial de dado pessoal** (LGPD art. 11). Tratamento obrigatório, conforme a **matriz única de dados sensíveis** ([01 §8.1](01-modelo-de-dominio.md)):

- **`encrypted`** — cast de criptografia no model (mesmo padrão do `two_factor_secret` do `AdminUser`); nunca em claro no banco.
- **Fora de auditoria** — `cid` em `atributosNaoAuditados()`: não vaza para o diff do activitylog (reforça "dados sensíveis nunca em logs" — CLAUDE §19).
- **Permissão dedicada `rh.afastamentos.ver_cid`** — exibir/editar o CID exige **essa** permissão, **além** de poder ver o afastamento. Sem ela, a UI mostra o afastamento mas **mascara** o CID; a tela de cadastro do funcionário ([03 §1](03-cadastro-pessoa-documentos.md)) **não** exibe o CID. Defesa no servidor (Policy/escopo), não só na UI.

---

## 6. UI — linha do tempo no perfil do funcionário

No perfil do funcionário ([03](03-cadastro-pessoa-documentos.md)) há uma **aba "Linha do tempo"** que lista os eventos em ordem **`data_evento` desc** (mais recente no topo), com **filtros por tipo** e **badges por tipo de evento**.

### 6.1 Componente

A timeline é renderizada por um componente Blade de timeline vertical no namespace do admin. **Atenção ao componente existente:** `resources/views/components/admin/timeline-table.blade.php` (`x-admin.timeline-table`) **já existe**, mas hoje é especializado para **"programações"** (linhas de período com início/fim/valor/status `ativa|futura|expirada`) — **não** é uma timeline genérica de eventos funcionais, e **não há** um `timeline-item` genérico no catálogo Inspinia/shared.

**Decisão (registrada): criar um componente dedicado `x-admin.event-timeline`** (não generalizar o `timeline-table`).

| Opção                                                 | Veredito      | Por quê                                                                                                                                                                                                                                                      |
| ----------------------------------------------------- | ------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Criar `x-admin.event-timeline`** (eventos pontuais) | **Escolhida** | A semântica diverge: histórico funcional é **evento pontual** (`data` + `tipo` + antes→depois), enquanto `timeline-table` é **período** (início/fim/status). Misturar os dois acopla conceitos distintos e arrisca regressão visual na tela de programações. |
| Generalizar `x-admin.timeline-table`                  | Alternativa   | Evitaria um componente novo, mas exigiria um modo "evento" retrocompatível dentro de um componente pensado para períodos — mais frágil.                                                                                                                      |

O `x-admin.event-timeline` recebe uma coleção de eventos (`{ data, tipo, titulo, descricao, badge_variant, meta }`). **Necessidade a anotar no catálogo de componentes** (CLAUDE §9): registrar `event-timeline` como item **🔴 a componentizar** no [`CATALOGO-COMPONENTES.md`](../../../template/INSPINIA/CATALOGO-COMPONENTES.md) (documentar → registrar → criar `.blade.php`), sem `<select>` nativo e sem CSS custom (Tailwind).

O componente recebe os eventos já **projetados pela camada Livewire** (não monta SQL na view): cada item traz rótulo (`TipoEventoFuncional::label()`), **badge** com `variant()` por tipo, `data_evento` formatada, `motivo`, e o **resumo do antes→depois** derivado dos snapshots (ex.: "Salário R$ 3.000,00 → R$ 3.450,00"; "Departamento Comercial → Operações"). Sem CSS customizado; só classes Tailwind (CLAUDE §9). O CID, quando o item é de afastamento, respeita §5.3.

### 6.2 Comportamento

- **Ordenação:** `data_evento` desc, desempate por `id` desc (mesma regra da reconstrução, §3.1).
- **Filtros:** por `tipo_evento` (multi-select via `x-shared.select-search :multiple=true` — nunca `<select>` cru, CLAUDE §19) e por intervalo de datas. Filtro **sempre** nas colunas tipadas, nunca no JSONB (§3).
- **ACL hierárquica ([05](05-organograma-acl-hierarquica.md)):** **só quem pode ver o funcionário vê a linha do tempo dele.** A visibilidade segue o organograma/escopo (gestor vê a subárvore; colaborador vê a própria — modo `proprio` vs `rh` de [03](03-cadastro-pessoa-documentos.md)). A timeline **não** é uma porta lateral: a Policy do funcionário governa o acesso à sua história.
- **Lançar evento pela UI:** ações "Registrar promoção", "Alterar salário", "Transferir departamento/filial", "Registrar afastamento" abrem um **drawer/modal** (Livewire → DTO → Action), guardadas por `@can` das permissões de §7. O colaborador, no autoatendimento, **vê** sua linha do tempo mas **não lança** eventos sujeitos a evento funcional ([03 §11](03-cadastro-pessoa-documentos.md)).

### 6.3 Relatórios úteis (derivados da timeline)

- **Histórico salarial** — todos os eventos com `afetaSalario()` de um funcionário (admissão, promoção, alteração, reajuste), com variação e percentual; alimenta gráfico de evolução. Lê `salario_centavos`/`salario_anterior_centavos` (colunas tipadas).
- **Tempo no cargo / no departamento** — diferença entre o `data_evento` do último `mudanca_cargo`/`promocao` (ou `transferencia_departamento`) e hoje; útil para política de carreira.
- **Afastamentos a retornar** — `funcionario_afastamentos WHERE data_fim_efetiva IS NULL` (em curso), comparando `data_fim_prevista` com hoje (atrasados em destaque). Usa o índice `(funcionario_id, data_fim_efetiva)`.

---

## 7. Permissões

A linha do tempo expõe **operações** (lançar fatos), então as permissões são **orientadas à ação** — distintas do CRUD genérico de catálogo. Os **slugs canônicos são os de [01 §10](01-modelo-de-dominio.md)** (fonte de verdade); esta seção descreve o **uso**, e os rótulos semânticos `registrar`/`encerrar` da UI **mapeiam** para os slugs CRUD registrados lá. Verbos (Policy via `@can`):

- **Eventos** — `rh.eventos.listar` (ver a linha do tempo de um funcionário visível) e `rh.eventos.registrar` (lançar qualquer evento funcional). Opcionalmente, granularidade **por ação** (`rh.eventos.registrar_promocao`, `rh.eventos.alterar_salario`, `rh.eventos.transferir`, …) quando o cliente precisar separar quem promove de quem reajusta. O **blueprint** ([02 §B4](02-fase-1-blueprint.md)) cita o par CRUD-style `rh.eventos.{listar,criar}`; `criar` ≡ `registrar` (semântica de "lançar evento"). **Não há** `editar`/`deletar`/`restaurar`/`excluir_permanente` para eventos — a tabela é append-only (§3).
- **Afastamentos** — `rh.afastamentos.listar`, `rh.afastamentos.registrar` (abrir afastamento), `rh.afastamentos.encerrar` (registrar retorno) e a permissão sensível **`rh.afastamentos.ver_cid`** (§5.3). Como a tabela é soft-deletável, valem também `rh.afastamentos.{deletar, restaurar, excluir_permanente}` do fluxo `ComLixeira` ([02 §B4](02-fase-1-blueprint.md) lista `{listar,criar,editar,deletar,restaurar,excluir_permanente}` + `ver_cid`); `criar`≡`registrar`, `editar` cobre o `encerrar`. Os verbos `registrar`/`encerrar` são os **nomes semânticos** preferidos na UI; mantêm-se mapeados aos da lixeira para reaproveitar o core.

> Todas as permissões são conferidas no servidor (Policy + escopo da [ACL hierárquica](05-organograma-acl-hierarquica.md)); a UI apenas reflete o que a Policy autoriza.

---

## 8. Performance e integridade

- **Índice `(funcionario_id, data_evento)`** em `funcionario_eventos` ([01 §3 C1/§7](01-modelo-de-dominio.md)) cobre a timeline do perfil **e** a reconstrução temporal (§3.1). Índices auxiliares `(empresa_id, tipo_evento)` e `(empresa_id, data_evento)` servem relatórios por tipo/competência.
- **Append-only cresce** — `funcionario_eventos` só ganha linhas. O índice cobre a Fase 1; **particionamento** (ex.: por ano de `data_evento`) é **evolução futura**, só se o volume exigir — **fora da Fase 1** ([01 §7](01-modelo-de-dominio.md)).
- **Cache vs. histórico** — as colunas "atuais" de `funcionarios` (cargo/departamento/filial/salário/`cargo_nivel`) evitam varrer o histórico nas listagens e no organograma; a Action mantém ambos coerentes na mesma transação (§4). Teste de invariante: a reconstrução em `hoje` (§3.1) **deve** bater com o cache.
- **Integridade referencial** — FKs do evento (`cargo_id`/`departamento_id`/`filial_id`/`registrado_por_admin_user_id`) com `nullOnDelete` (o evento sobrevive mesmo que o mestre seja removido — o **snapshot** preserva o que importa, por isso o ID pode esvaziar sem perder a história). `tipos_afastamento` em afastamento é `restrictOnDelete` (catálogo em uso não some — [04 §9](04-catalogos-configuraveis.md)).
- **JSONB** — `snapshot_anterior`/`snapshot_novo` sem índice secundário (ADR-0009); TOAST do Postgres compacta transparentemente. **Nunca** filtrar/ordenar por dentro do JSONB.

---

## 9. Checklist de implementação (Fase 1)

- [ ] Migration `funcionario_eventos` (**sem `deleted_at`**, JSONB `snapshot_anterior`/`snapshot_novo`, índices `(funcionario_id, data_evento)`, `(empresa_id, tipo_evento)`, `(empresa_id, data_evento)`); CHECK do enum `tipo_evento`; factory.
- [ ] Migration `funcionario_afastamentos` (com `deleted_at`, CHECK de datas, `cid` `encrypted`, índice `(funcionario_id, data_fim_efetiva)`); factory.
- [ ] Enum `TipoEventoFuncional` com `afetaSalario()`, `afetaLotacao()`, `label()`, `variant()`.
- [ ] Models `FuncionarioEvento` (`Auditavel`, **sem** `SoftDeletes`/lixeira) e `FuncionarioAfastamento` (`Auditavel`, `SoftDeletes`/`UsaSoftDeletes`, `cid` em `atributosNaoAuditados()` + cast `encrypted`).
- [ ] DTOs readonly + Actions `Eventos\{RegistrarAdmissao,RegistrarPromocao,AlterarSalario,TransferirDepartamento,TransferirFilial,MudarCargo,RegistrarDesligamento}` e `Afastamentos\{RegistrarAfastamento,EncerrarAfastamento}` — cada uma grava evento **e** atualiza o cache de `funcionarios` em `DB::transaction`; concilia `status` nos afastamentos.
- [ ] FormRequests + Rules (datas, `salario_centavos > 0`, FKs `->where('empresa_id', ...)`).
- [ ] Eventos de domínio + listeners idempotentes (admissão/desligamento/início-fim de afastamento).
- [ ] Componente **`x-admin.event-timeline`** (decisão §6.1 — dedicado a eventos pontuais, não generalizar o `timeline-table`); registrar como item 🔴 no `CATALOGO-COMPONENTES.md`; aba "Linha do tempo" no perfil (filtros por tipo, badges, antes→depois).
- [ ] Policies/permissões `rh.eventos.{listar,registrar}` e `rh.afastamentos.{listar,registrar,encerrar,ver_cid}` (+ aliases de lixeira); ACL hierárquica de visibilidade ([05](05-organograma-acl-hierarquica.md)).
- [ ] Relatórios: histórico salarial, tempo no cargo/departamento, afastamentos a retornar.
- [ ] Testes: imutabilidade (sem `UPDATE`/`DELETE` de evento), invariante cache = reconstrução(`hoje`), conciliação de status no afastamento, mascaramento de CID sem `ver_cid`, atomicidade da transação.
