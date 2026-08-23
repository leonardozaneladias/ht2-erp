# 04 — Catálogos Configuráveis

> Como o cliente configura o vocabulário do RH **sem tocar em código**. Cada catálogo nasce com um **padrão pronto** (semeado na criação da empresa) e é totalmente editável por CRUD — adicionar, editar, ligar/desligar e enviar para a lixeira. As colunas-**flag** dão comportamento sem virar enum.
>
> Os nomes de tabelas, colunas, enums, seeds e permissões aqui referenciados são a **fonte de verdade** do [01 — Modelo de Domínio](01-modelo-de-dominio.md); divergências corrigem-se lá primeiro. Pacote `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · views `rh::`.

Relacionados: [01 — Modelo de Domínio](01-modelo-de-dominio.md) · [03 — Cadastro de Pessoa e Documentos](03-cadastro-pessoa-documentos.md) · [07 — Jornada, Horas Extras e Folha](07-jornada-horas-extras-folha.md) · [adrs/ADR-RH-002 — Fronteira ENUM vs Catálogo](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)

---

## 1. Introdução: ENUM vs CATÁLOGO vs REFERÊNCIA

O requisito do cliente é direto: **tudo configurável**. Mas "tudo" tem três naturezas diferentes, e tratá-las da mesma forma seria um erro. A fronteira é decidida em [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md) e resumida assim:

|                            | **ENUM PHP**                                 | **CATÁLOGO tenant** (este documento)              | **REFERÊNCIA global**              |
| -------------------------- | -------------------------------------------- | ------------------------------------------------- | ---------------------------------- |
| Quem define                | Engenharia (código)                          | **O cliente** (UI), com seed padrão               | Dado oficial BR/ISO                |
| Muda em runtime?           | Não (só deploy)                              | **Sim, por empresa**                              | Raramente (sync)                   |
| Tem `if`/cálculo no valor? | Sim                                          | Não (rótulo + flags)                              | Não                                |
| Exemplo                    | `StatusFuncionario`, `Sexo`, `TipoHoraExtra` | `departamentos`, `funcoes`, `rubricas`, `tipos_*` | `cargos` (CBO), `bancos`, `paises` |

**Mnemônico:** _tem lógica/cálculo atrelada ao valor → ENUM · o cliente adiciona linhas → CATÁLOGO · é lista oficial BR/ISO → REFERÊNCIA._

O que é **ENUM** (mora no código, não editável pelo cliente): status do funcionário, sexo, estado civil, escolaridade, raça/cor, tipo de vínculo, regime de trabalho, grau de parentesco, tipo de hora extra (50/100/noturno/DSR), status de aprovação da HE — tudo que dirige `if`, badge ou cálculo. Mudar exige deploy, e isso é proposital: a regra de negócio acompanha o valor.

O que é **REFERÊNCIA global** (lista oficial, sem `empresa_id`, semeada nacionalmente pelo core): `cargos` (CBO), `bancos`, `paises`, `municipios`, `estados`, `tipos_logradouro`. O RH **reaproveita** — não recria. Ver §10 (Cargo).

O que é **CATÁLOGO tenant** (o tema deste documento): `departamentos`, `funcoes` (+ pivot `funcionario_funcao`), `tipos_documento`, `tipos_afastamento`, `escalas` (+ `escala_dias`, `escala_funcionario`) e `rubricas`. Cada um pertence a uma empresa, nasce com o padrão e é editado pelo cliente.

### O padrão híbrido (flags de comportamento)

Vários catálogos não são listas de rótulos puros: a **linha** é do cliente, mas algumas **colunas booleanas** ("flags") dão comportamento sem promover o valor a enum. Exemplos que aparecem adiante:

- `tipos_documento.exige_validade`, `exige_numero`, `exige_orgao_emissor`, `exige_arquivo` — controlam o formulário do documento do funcionário.
- `tipos_afastamento.remunerado`, `conta_como_falta`, `suspende_contrato`, `exige_atestado` — alimentam folha e linha do tempo.
- `rubricas.natureza` (enum embutido provento/desconto/informativa) + `incide_inss` / `incide_fgts` / `incide_irrf` — fundação do cálculo de folha.

Assim o cliente adiciona uma linha nova (ex.: um tipo de afastamento próprio) e o sistema já sabe se ela é remunerada, se conta como falta etc. — **sem alterar código**.

### Padrões comuns a TODOS os catálogos

Para não repetir em cada subseção, vale para todos (confirmado no core — ver [01 §0](01-modelo-de-dominio.md)):

- **Tenant-scoped** — trait `App\Models\Concerns\BelongsToEmpresa`: `empresa_id NOT NULL`, global scope `empresa` automático, auto-fill no `creating`. Toda unicidade é **por empresa** (índice único composto parcial `UNIQUE (empresa_id, <coluna>) WHERE deleted_at IS NULL`).
- **Lixeira** — `SoftDeletes` + contrato `UsaSoftDeletes` no model e trait `HT2ML\Core\Livewire\Concerns\ComLixeira` na Table. Três níveis: excluir → lixeira (`delete`), `restore`, excluir definitivamente (`forceDelete`). Catálogo **em uso** não é excluído fisicamente (`restrictOnDelete` nas FKs das filhas; ver §9). Detalhes em [`docs/lixeira.md`](../../../lixeira.md).
- **Auditoria** — trait `Auditavel` (spatie/activitylog), append-only.
- **CRUD via Livewire** — componentes `Index` (página + tabela embutida), `Form` (drawer/modal) e `Table` (PowerGrid com filtros, busca e lixeira). Validação em **FormRequest + Rules** (`Rule::unique()->where('empresa_id', ...)`). Transporte entre camadas por **DTO readonly**; gravação por **Actions**.
- **Permissões** — `rh.<catalogo>.{listar, criar, editar, deletar, restaurar, excluir_permanente}`, conferidas via Policy. As três últimas casam com o fluxo do `ComLixeira` (`delete`/`restore`/`forceDelete`) e ficam guardadas por `@can` nas ações da linha.
- **Componentes de UI** — formulários usam `x-shared.select-search` (nunca `<select>` cru), `x-shared.toggle` (flags booleanas) e `x-shared.color-picker` (cor de badge). Sem CSS customizado.

> **Provisionamento:** toda empresa nova nasce com o padrão preenchido pela Action idempotente `ProvisionarCatalogosRh` (§11). Os enums não têm seed (vivem no código); as referências globais (`cargos`, `bancos`…) já vêm semeadas pelo core.

---

## 2. `departamentos` — Departamento / Departamento (árvore)

**Propósito.** Estrutura organizacional da empresa, em **árvore** (um departamento pode ter sub-departamentos). É a lotação do funcionário (`funcionarios.departamento_id`, com histórico na linha do tempo) e base do organograma por área.

**Colunas-chave e comportamento** (tabela `departamentos`, [01 §A1](01-modelo-de-dominio.md)):

| Coluna                                  | Papel                                                                                                          |
| --------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `departamento_pai_id` (self, nullable)  | hierarquia de sub-departamentos; `restrictOnDelete` (não apaga pai em uso) + CHECK `departamento_pai_id <> id` |
| `codigo` (VARCHAR 30)                   | código interno do cliente (opcional, único por empresa)                                                        |
| `nome` (VARCHAR 120)                    | rótulo do departamento (único por empresa)                                                                     |
| `responsavel_funcionario_id` (nullable) | responsável pela área (`nullOnDelete`; nullable evita ciclo na criação)                                        |
| `ativo` (bool)                          | liga/desliga sem excluir — some das seleções, segue visível na gestão                                          |
| `ordem` (int)                           | ordenação na UI                                                                                                |

**Telas CRUD.**

- _Lista_ (PowerGrid): árvore/indentação por `departamento_pai_id`, colunas nome, código, departamento-pai, responsável, status (`ativo`); filtros por status e por departamento-pai; busca por nome/código; toggle "Ver lixeira".
- _Formulário_ (drawer): nome, código, descrição, **departamento-pai** via `x-shared.select-search` (lista os demais departamentos da empresa, exceto descendentes para evitar ciclo), **responsável** via `select-search` de funcionários, `x-shared.toggle` para `ativo`, campo de ordem.

**Permissões:** `rh.departamentos.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira`. Excluir um departamento com sub-departamentos ou funcionários lotados é bloqueado (`restrictOnDelete` / hook `bloqueioExclusao`); mova/realoque primeiro.

**Valores semente** ([01 §5](01-modelo-de-dominio.md)): Administrativo · Financeiro (→ **Contas a Pagar**, **Contas a Receber**) · RH · Comercial/Vendas · Operações · TI · Logística · Atendimento · Diretoria.

**Como o cliente adapta:** cria/renomeia departamentos, monta a hierarquia escolhendo o departamento-pai, define responsável e ordem, desativa o que não usa. Excluídos vão para a lixeira e podem voltar.

---

## 3. `funcoes` — Funções / Extras (atribuição N:N com vigência)

**Propósito.** Vocabulário de **papéis extras** que um funcionário acumula além do cargo: líder, preposto, supervisor, membro da CIPA etc. Diferente de `cargos` (que é único e oficial), uma função é **por empresa** e um funcionário pode ter **várias ao mesmo tempo**, cada uma com período de vigência.

**Colunas-chave e comportamento** (tabela `funcoes`, [01 §A2](01-modelo-de-dominio.md)):

| Coluna                 | Papel                                                         |
| ---------------------- | ------------------------------------------------------------- |
| `nome` (VARCHAR 80)    | rótulo da função (único por empresa)                          |
| `descricao` (TEXT)     | detalhe opcional                                              |
| `cor` (VARCHAR 7, hex) | **cor do badge** na UI (ex.: identificar "Líder" visualmente) |
| `ativo` (bool)         | liga/desliga                                                  |

**Atribuição N:N com vigência** — pivot `funcionario_funcao` ([01 §A3](01-modelo-de-dominio.md)): `funcionario_id`, `funcao_id` (`restrictOnDelete`), `inicio` (DATE), `fim` (DATE, `null` = vigente), `observacao`. Único por `(funcionario_id, funcao_id, inicio)`. **Sem soft-delete**: encerra-se preenchendo `fim` (preserva o histórico de quem foi líder e quando). A atribuição em si vive no cadastro do funcionário (ver [03](03-cadastro-pessoa-documentos.md)); aqui se mantém o **catálogo** das funções disponíveis.

**Telas CRUD.**

- _Lista_ (PowerGrid): nome, descrição, **amostra da cor** (badge), status; filtro por status; busca por nome.
- _Formulário_ (drawer): nome, descrição, **cor** via `x-shared.color-picker`, `x-shared.toggle` para `ativo`.

**Permissões:** `rh.funcoes.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira`. Função atribuída a alguém (pivot) é protegida por `restrictOnDelete`.

**Valores semente:** Líder · Preposto · Supervisor · Coordenador · Encarregado · Fiscal · Membro CIPA · Brigadista · Procurador · Responsável Técnico.

**Como o cliente adapta:** adiciona funções próprias, escolhe a cor do badge, desativa as que não usa. A vigência (início/fim) é definida na hora de atribuir a função a cada funcionário.

---

## 4. `tipos_documento` — Tipos de documento do funcionário (flags `exige_*`)

**Propósito.** Catálogo dos tipos de documento que o RH guarda por funcionário (RG, CTPS, CNH…). As **flags** ditam o que o formulário do documento exige, sem código por tipo.

> **Nome de classe:** o model é `TipoDocumentoRh` (tabela `tipos_documento`) para não colidir com o enum `App\Enums\TipoDocumento` do core, que trata de numeração fiscal — conceito distinto.

**Colunas-chave e comportamento** (tabela `tipos_documento`, [01 §A4](01-modelo-de-dominio.md)):

| Coluna                                   | Papel                                                             |
| ---------------------------------------- | ----------------------------------------------------------------- |
| `codigo` (VARCHAR 30)                    | chave estável (`rg`, `cpf`, `ctps`…), único por empresa           |
| `nome` (VARCHAR 80)                      | rótulo                                                            |
| `exige_numero` (bool, def. **true**)     | número do documento obrigatório                                   |
| `exige_validade` (bool, def. false)      | exige data de validade → habilita relatório "documentos a vencer" |
| `exige_orgao_emissor` (bool, def. false) | exige órgão/UF emissor                                            |
| `exige_arquivo` (bool, def. false)       | exige anexo (PDF/imagem)                                          |
| `sensivel_lgpd` (bool, def. true)        | marca PII para mascaramento/auditoria reduzida                    |
| `ativo` (bool)                           | liga/desliga                                                      |

As flags são lidas pelo formulário de `funcionario_documentos` (ver [03](03-cadastro-pessoa-documentos.md)): cada `exige_*` torna o campo correspondente obrigatório.

**Telas CRUD.**

- _Lista_ (PowerGrid): código, nome, badges das flags ligadas, status; filtros por status e por flag (ex.: "só os que exigem validade"); busca.
- _Formulário_ (drawer): código, nome, um `x-shared.toggle` por flag (`exige_*`, `sensivel_lgpd`, `ativo`).

**Permissões:** `rh.tipos_documento.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira`. Tipo já usado em `funcionario_documentos` é protegido (`restrictOnDelete`).

**Valores semente:** RG · CPF · CTPS · PIS/PASEP · Título de Eleitor · Reservista · CNH _(`exige_validade`)_ · Comprovante de Escolaridade · Comprovante de Residência _(`exige_arquivo`)_ · Carteira de Vacinação · ASO/Exame Admissional _(`exige_validade`)_ · Foto 3x4.

**Como o cliente adapta:** cria tipos próprios e marca as exigências por toggle (ex.: um certificado interno que exige validade e arquivo). O formulário do documento passa a respeitar essas regras na hora.

> **Tag de documento:** o `codigo` é também a **chave do mapeamento de tag** no envio em lote/ZIP — `config('rh.documentos.tags')` casa prefixos do nome do arquivo (`documento-cpf`, `comprovante-endereco`…) a este `codigo` para classificar automaticamente ([03 §8.6](03-cadastro-pessoa-documentos.md)).

---

## 5. `tipos_afastamento` — Tipos de afastamento (flags eSocial)

**Propósito.** Catálogo dos motivos de afastamento usados na **linha do tempo** do funcionário (férias, atestado, INSS…). As flags carregam o comportamento trabalhista/eSocial.

**Colunas-chave e comportamento** (tabela `tipos_afastamento`, [01 §A5](01-modelo-de-dominio.md)):

| Coluna                                 | Papel                                                            |
| -------------------------------------- | ---------------------------------------------------------------- |
| `codigo` (VARCHAR 30)                  | chave estável (`ferias`, `atestado`, `inss`…), único por empresa |
| `codigo_esocial` (VARCHAR 10)          | mapeia para a **tabela 18 do eSocial**                           |
| `nome` (VARCHAR 100)                   | rótulo                                                           |
| `remunerado` (bool, def. true)         | conta no pagamento?                                              |
| `conta_como_falta` (bool, def. false)  | impacta frequência/DSR                                           |
| `suspende_contrato` (bool, def. false) | suspende vínculo (INSS, licença não remunerada)                  |
| `exige_atestado` (bool, def. false)    | exige anexo de atestado                                          |
| `ativo` (bool)                         | liga/desliga                                                     |

Essas flags alimentam o registro de `funcionario_afastamentos` e os cálculos de folha (resumo em [07](07-jornada-horas-extras-folha.md)).

**Telas CRUD.**

- _Lista_ (PowerGrid): código, nome, código eSocial, badges das flags, status; filtros por status / remunerado / suspende-contrato; busca.
- _Formulário_ (drawer): código, nome, **código eSocial** (`select-search`/`input`), um `x-shared.toggle` por flag.

**Permissões:** `rh.tipos_afastamento.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira`. Tipo já usado em afastamentos é protegido (`restrictOnDelete`).

**Valores semente** (com flags marcadas): Férias · Atestado ≤15d _(`exige_atestado`)_ · Auxílio-doença INSS >15d _(`suspende_contrato`)_ · Acidente de trabalho · Licença-maternidade _(`remunerado`)_ · Licença-paternidade · Licença não remunerada _(`!remunerado`, `suspende_contrato`)_ · Falta justificada · Falta injustificada _(`conta_como_falta`)_ · Suspensão disciplinar · Serviço militar · Gala (núpcias) · Nojo (luto) · Doação de sangue.

**Como o cliente adapta:** ajusta nomes/códigos eSocial conforme a convenção da empresa, cria motivos próprios e marca as flags — a linha do tempo e a folha passam a tratar o novo tipo corretamente.

> **Faltas e atestados:** as flags (`conta_como_falta`, `remunerado`, `suspende_contrato`, `exige_atestado`) também classificam **faltas/ocorrências** e o **abono** por atestado, e decidem quando um atestado **vira afastamento** (>15 d → INSS, `suspende_contrato`) — ver [12](12-ausencias-faltas-atestados-afastamentos.md).

---

## 6. `escalas` + `escala_dias` + `escala_funcionario` — Jornadas reutilizáveis

**Propósito.** Definir **jornadas reutilizáveis** que se atribuem a vários funcionários, em vez de repetir horários pessoa a pessoa. É um catálogo de três peças; o detalhe de cálculo (carga semanal, valor-hora, intervalos, cruzar meia-noite) está em [07](07-jornada-horas-extras-folha.md) — aqui fica a visão de catálogo.

**Cabeçalho** — `escalas` ([01 §A6](01-modelo-de-dominio.md)):

| Coluna                                       | Papel                                                   |
| -------------------------------------------- | ------------------------------------------------------- |
| `nome` (VARCHAR 100)                         | rótulo (ex.: "44h Comercial"), único por empresa        |
| `tipo` (enum `TipoEscala`)                   | semanal · 12x36 · revezamento · parcial · personalizada |
| `carga_semanal_minutos` (int)                | cache conferido na escrita                              |
| `horas_mensais_divisor` (smallint, def. 220) | base do valor-hora                                      |
| `ativo` (bool)                               | liga/desliga                                            |

**Dias** — `escala_dias` ([01 §A7](01-modelo-de-dominio.md)): uma linha por dia×turno. `dia_semana` (enum `DiaSemana`, 1=segunda…7=domingo, ISO), `ordem_turno` (1=manhã, 2=tarde…), `eh_folga` (bool), `entrada`/`saida` (TIME; `saida < entrada` cruza a meia-noite). O **intervalo** (almoço) é a lacuna entre turnos do mesmo dia. CHECK: dia de folga ou com entrada **e** saída preenchidas.

**Atribuição** — `escala_funcionario` ([01 §A8](01-modelo-de-dominio.md)): vínculo com **vigência** (`vigencia_inicio`, `vigencia_fim` = `null` quando vigente) — é o histórico de escalas do funcionário. Índice único parcial garante **no máximo uma vigência aberta** por funcionário; não-sobreposição validada na Action.

**Telas CRUD.**

- _Lista_ (PowerGrid): nome, tipo, carga semanal (formatada h), divisor, status; filtros por tipo/status; busca.
- _Formulário_ (página/drawer com sub-grade): cabeçalho (nome, tipo via `select-search`, divisor, `toggle` ativo) **e** a grade de `escala_dias` (por dia da semana: folga ou turnos com entrada/saída). A atribuição a funcionários (`escala_funcionario`) acontece no cadastro da pessoa.

**Permissões:** `rh.escalas.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira` (cabeçalho). `escala_dias` cascateia no force-delete da escala; uma escala com atribuição vigente é protegida (`restrictOnDelete`).

**Valores semente** (cabeçalho + dias): "44h Seg–Sex+Sáb" · "40h Seg–Sex" · "12x36 Diurno" · "12x36 Noturno" · "Estágio 6h" · "30h Seg–Sex".

**Como o cliente adapta:** cria escalas próprias montando os turnos por dia, escolhe o divisor de horas mensais e atribui a escala aos funcionários com data de início.

---

## 7. `rubricas` — Proventos / descontos (fundação de folha)

**Propósito.** Catálogo de **verbas** de folha (proventos, descontos e informativas) com suas **incidências**. É a **fundação** do cálculo de folha: a Fase 1 não apura folha, mas já estrutura as rubricas e as conecta às horas extras (`horas_extras.rubrica_id`). Detalhe de cálculo em [07 §Folha](07-jornada-horas-extras-folha.md).

**Colunas-chave e comportamento** (tabela `rubricas`, [01 §A9](01-modelo-de-dominio.md)):

| Coluna                              | Papel                                                               |
| ----------------------------------- | ------------------------------------------------------------------- |
| `codigo` (VARCHAR 30)               | chave estável (`he_50`, `salario`, `desc_inss`…), único por empresa |
| `codigo_esocial` (VARCHAR 10)       | mapeia para a **tabela 03 (rubricas) do eSocial**                   |
| `nome` (VARCHAR 100)                | rótulo                                                              |
| `natureza` (enum `NaturezaRubrica`) | **provento · desconto · informativa** (enum embutido + CHECK)       |
| `incide_inss` (bool)                | base de INSS                                                        |
| `incide_fgts` (bool)                | base de FGTS                                                        |
| `incide_irrf` (bool)                | base de IRRF                                                        |
| `referencia_he_tipo` (VARCHAR 20)   | liga um `TipoHoraExtra` a esta rubrica (opcional)                   |
| `ativo` (bool)                      | liga/desliga                                                        |

**Telas CRUD.**

- _Lista_ (PowerGrid): código, nome, natureza (badge), trio de incidências (badges INSS/FGTS/IRRF), status; filtros por natureza/incidência/status; busca.
- _Formulário_ (drawer): código, nome, **natureza** via `x-shared.select-search`, `codigo_esocial`, três `x-shared.toggle` de incidência, `referencia_he_tipo` (`select-search` com os casos de `TipoHoraExtra`), `toggle` ativo.

**Permissões:** `rh.rubricas.{listar, criar, editar, deletar, restaurar, excluir_permanente}`.

**Lixeira:** `ComLixeira`. Rubrica referenciada por horas extras (ou, no futuro, por folha) é protegida (`restrictOnDelete`).

**Valores semente** (com incidências): Salário Base _(provento, incide tudo)_ · Hora Extra 50% _(provento, `referencia_he_tipo=he_50`)_ · Hora Extra 100% · Adicional Noturno · DSR sobre HE · INSS _(desconto)_ · IRRF _(desconto)_ · FGTS _(informativa)_ · Vale-Transporte _(desconto)_ · Salário-Família _(provento)_.

**Como o cliente adapta:** cria rubricas próprias, escolhe a natureza, liga as incidências (INSS/FGTS/IRRF) por toggle e, opcionalmente, amarra um tipo de hora extra. O cálculo futuro de folha lê essas flags — nenhuma regra fica hard-coded no código.

---

## 7.1 `centros_custo` — Centro de custo (catálogo tenant **opcional**)

**Propósito.** Agrupamento **gerencial/financeiro** do funcionário (ex.: "Administrativo", "Obra X", "Filial Centro") para relatórios, headcount e rateio de custo. Decisão **D1** desta revisão: é um **catálogo tenant novo e opcional** — **não existe no core** (verificado: nenhum model/migração `centro_custo`), então nasce aqui sem recriar nada. É **dimensão organizacional paralela** — agrupa a pessoa, mas **não governa a ACL** ([05 §3.2](05-organograma-acl-hierarquica.md)). É **CATÁLOGO** (não enum): o cliente cria linhas, sem lógica/cálculo atrelado ao valor ([ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)).

> **Aditivo, não bloqueante.** Por ser opcional, a tabela `centros_custo` (§A12 do [01](01-modelo-de-dominio.md)) e a FK `funcionarios.centro_custo_id` entram por **migration aditiva** quando o cliente adotar centro de custo — **não fazem parte do B1 mínimo** ([02 §1.1](02-fase-1-blueprint.md)). Quem não usa, ignora.

**Colunas-chave** (tabela `centros_custo`, [01 §A12](01-modelo-de-dominio.md)):

| Coluna                | Papel                                                       |
| --------------------- | ----------------------------------------------------------- |
| `codigo` (VARCHAR 30) | código contábil/gerencial (opcional, único por empresa)     |
| `nome` (VARCHAR 120)  | rótulo (ex.: "Administrativo", "Obra X"), único por empresa |
| `descricao` (TEXT)    | detalhe opcional                                            |
| `ativo` (bool)        | liga/desliga                                                |
| `ordem` (int)         | ordenação na UI                                             |

**Vínculo:** FK nullable `funcionarios.centro_custo_id` (`nullOnDelete`); selecionável no cadastro do funcionário ([03](03-cadastro-pessoa-documentos.md)) e usado como filtro no organograma ([05 §10.1.3](05-organograma-acl-hierarquica.md)). **Evolução opcional:** `departamentos.centro_custo_id` (herança de centro de custo por área).

**Telas CRUD.** _Lista_ (PowerGrid): código, nome, status; filtro por status; busca. _Formulário_ (drawer): código, nome, descrição, `x-shared.toggle` para `ativo`, ordem.

**Permissões:** `rh.centros_custo.{listar, criar, editar, deletar, restaurar, excluir_permanente}` ([01 §10](01-modelo-de-dominio.md)).

**Lixeira:** `ComLixeira`. Centro de custo referenciado por funcionários é protegido (`restrictOnDelete`).

**Seed padrão (opcional, idempotente).** Diferente dos seis catálogos sempre semeados, o centro de custo nasce **vazio** por padrão (é muito específico de cada empresa). Quando o cliente adota, o `ProvisionarCatalogosRh` (§10) pode semear um conjunto mínimo idempotente — ex.: **Administrativo**, **Operacional**, **Comercial** — via `firstOrCreate(['empresa_id','codigo'], …)`, ou o cliente cria os seus. Sem seed, sem prejuízo.

**Como o cliente adapta:** cria os centros de custo da empresa, vincula no cadastro de cada funcionário e usa como filtro/agrupamento em listagens e no organograma.

---

## 8. Cargo — reaproveitamento da referência global (CBO)

Cargo é **REFERÊNCIA global**, não catálogo tenant. O RH reaproveita o catálogo oficial `cargos` (`App\Models\Referencia\Cargo`, semeado pelo `CargoSeeder` com a CBO), sem recriá-lo:

- `funcionarios.cargo_id` aponta para `cargos` (selecionável no cadastro — satisfaz o `FuncionarioCargoTest`, que espera `cargosDisponiveis` populado).
- O **nível hierárquico** que o organograma precisa mora em `funcionarios.cargo_nivel` (SMALLINT desnormalizado, cache), ou numa coluna `nivel_hierarquico` adicionada a `cargos` caso o catálogo global passe a ser editável por Tabelas Auxiliares.

**Ponto de decisão (registrado no [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)):** se o cliente precisar de **cargos próprios** (não-CBO) com CRUD por empresa, a evolução é **aditiva** — promover para um catálogo tenant `cargos_empresa` (mesmos padrões: `BelongsToEmpresa` + `ComLixeira` + permissões `rh.cargos.*`), sem quebrar o que existe. Por ora, Fase 1 fica na referência global; cargo entra aqui apenas como nota de fronteira, não como CRUD do módulo RH.

---

## 9. Integridade: catálogo em uso não some

Catálogos são fundação de cadastros, então a exclusão é defensiva:

- FKs das tabelas que consomem o catálogo usam **`restrictOnDelete`** (`funcionario_documentos.tipo_documento_id`, `funcionario_afastamentos.tipo_afastamento_id`, `funcionario_funcao.funcao_id`, `escala_funcionario.escala_id`, `horas_extras.rubrica_id`). Tentar excluir definitivamente algo em uso falha; o `ComLixeira` (hook `bloqueioExclusao` / `textoExcluirDefinitivo`) traduz isso em toast amigável.
- **Soft-delete não cascateia** — mover um catálogo para a lixeira não derruba registros que o referenciam; ele apenas some das novas seleções e pode ser restaurado.
- Auto-FKs em árvore (`departamentos.departamento_pai_id`) usam `restrictOnDelete` + CHECK anti-auto-referência; ciclos profundos são validados na Action.
- Unicidade sempre **por empresa** e **parcial** (`WHERE deleted_at IS NULL`): um código/nome na lixeira não bloqueia recriar o mesmo no ativo.

---

## 10. Provisionamento por empresa — `ProvisionarCatalogosRh`

Toda empresa nova precisa nascer **já configurada** com o padrão — esse é o coração do requisito "padrão pronto, editável depois".

- **Action idempotente** `HT2ML\Rh\Actions\ProvisionarCatalogosRh` (análoga ao `App\Actions\Admin\Menu\AplicarMenuPadraoAction` do core, que usa exatamente este padrão), semeia os catálogos tenant via `firstOrCreate` por chave estável: `(empresa_id, codigo)` para `tipos_documento`/`tipos_afastamento`/`rubricas`; `(empresa_id, nome)` para `departamentos`/`funcoes`/`escalas`. Rodar duas vezes é **no-op** — nunca duplica nem sobrescreve o que o cliente já editou.
- **Gatilho:** chamada **na criação da empresa** (no fluxo de cadastro de empresa / listener do core), dentro de uma transação. Greenfield e reexecutável.
- **O que NÃO entra aqui:** enums (vivem no código) e referências globais (`cargos`, `bancos`, `paises`… já semeados pelo core). A Action cuida só dos seis catálogos tenant (+ as filhas `escala_dias` e o conjunto de `funcionario_funcao`/`escala_funcionario` permanecem vazios — são preenchidos por atribuição). O catálogo **opcional** `centros_custo` (§7.1) **não** faz parte dos seis: nasce **vazio** por padrão e só é semeado (mínimo idempotente) se o cliente adotar centro de custo.
- **Empacotamento (ADR-0015):** a Action e seus dados-semente vivem no pacote `packages/extensao-rh`; o core não é editado. O item de menu do RH já é contribuído pelo pacote (visto em `AplicarMenuPadraoAction`: grupo `grupo-tab-rh`).

Resultado: a empresa abre o módulo de RH e encontra departamentos, funções, tipos de documento, tipos de afastamento, escalas e rubricas **prontos** — e livres para editar.

---

## 11. Tabela-resumo

| Catálogo                             | Tabela(s)                                           | Permissões `rh.<x>.*`                                                      | Seeds padrão (qtd.)                    | Flags / colunas de comportamento                                                                                           |
| ------------------------------------ | --------------------------------------------------- | -------------------------------------------------------------------------- | -------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Departamentos                        | `departamentos` (árvore)                            | `departamentos.{listar,criar,editar,deletar,restaurar,excluir_permanente}` | 9 departamentos (Financeiro com 2 sub) | `departamento_pai_id`, `responsavel_funcionario_id`, `ativo`, `ordem`                                                      |
| Funções/Extras                       | `funcoes` + pivot `funcionario_funcao`              | `funcoes.*`                                                                | 10 funções                             | `cor` (badge), `ativo`; pivot com vigência `inicio`/`fim`                                                                  |
| Tipos de documento                   | `tipos_documento` (model `TipoDocumentoRh`)         | `tipos_documento.*`                                                        | 12 tipos                               | `exige_numero`, `exige_validade`, `exige_orgao_emissor`, `exige_arquivo`, `sensivel_lgpd`, `ativo`                         |
| Tipos de afastamento                 | `tipos_afastamento`                                 | `tipos_afastamento.*`                                                      | 14 tipos                               | `codigo_esocial`, `remunerado`, `conta_como_falta`, `suspende_contrato`, `exige_atestado`, `ativo`                         |
| Escalas/Jornadas                     | `escalas` + `escala_dias` + `escala_funcionario`    | `escalas.*`                                                                | 6 escalas (com dias)                   | `tipo` (`TipoEscala`), `horas_mensais_divisor`, `ativo`; dias com `eh_folga`/turnos; atribuição com vigência               |
| Rubricas                             | `rubricas`                                          | `rubricas.*`                                                               | 10 rubricas                            | `natureza` (`NaturezaRubrica`), `incide_inss`/`incide_fgts`/`incide_irrf`, `referencia_he_tipo`, `codigo_esocial`, `ativo` |
| Centro de custo _(opcional, D1)_     | `centros_custo`                                     | `centros_custo.*`                                                          | — (opcional; nasce vazio)              | `codigo`, `nome`, `ativo`, `ordem`; FK `funcionarios.centro_custo_id` ([01 §A12](01-modelo-de-dominio.md))                 |
| Cargo _(referência, não CRUD do RH)_ | `cargos` (global, CBO) + `funcionarios.cargo_nivel` | usa permissões de referência do core                                       | seed nacional (core)                   | — (evolução opcional: catálogo tenant `cargos_empresa`)                                                                    |

**Provisionamento:** os seis catálogos tenant são semeados por empresa pela Action idempotente `ProvisionarCatalogosRh` (`firstOrCreate`), invocada na criação da empresa. Padrão pronto, 100% editável por CRUD, sem tocar em código.
