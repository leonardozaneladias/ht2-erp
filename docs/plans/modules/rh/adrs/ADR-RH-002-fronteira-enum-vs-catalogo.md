---
title: 'ADR-RH-002: Fronteira ENUM PHP vs CATÁLOGO configurável vs REFERÊNCIA global'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-002: Fronteira ENUM PHP vs CATÁLOGO configurável vs REFERÊNCIA global

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** modelagem, configurabilidade, rh

> Pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Schema canônico em [01 — Modelo de Domínio](../01-modelo-de-dominio.md); detalhe de catálogos em [04 — Catálogos Configuráveis](../04-catalogos-configuraveis.md).

## Contexto e problema

O requisito do cliente é "tudo configurável". Mas o domínio de RH tem dezenas de campos com valores finitos e naturezas **diferentes**: alguns são regra de negócio que dirige `if`/cálculo (status do funcionário, tipo de hora extra), outros são vocabulário que cada empresa define (departamentos, funções, tipos de afastamento), outros ainda são listas **oficiais nacionais** (cargos/CBO, bancos, países, municípios).

Tratar os três do mesmo jeito é um erro caro nas duas pontas:

- **Tudo enum** (no código): engessa o cliente — adicionar um departamento ou um tipo de afastamento viraria deploy.
- **Tudo catálogo** (CRUD no banco): perde a type-safety dos backed enums ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)), o `match` exaustivo, o CHECK constraint e a possibilidade de pendurar **lógica** no valor (`TipoHoraExtra::fatorPadraoBps()`, `RegimeTrabalho::baseCalculoHoraExtra()`). Recria, mal, listas oficiais que o core já mantém.

É preciso uma **fronteira clara e repetível**, decidida campo a campo, que sirva de regra para a Fase 1 e para evoluções.

## Drivers da decisão

- Type-safety e lógica no valor onde a **regra de negócio acompanha o domínio** ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)): `match` exaustivo, CHECK no Postgres, `label()`/`variant()`, métodos de comportamento.
- Configurabilidade real (sem deploy) onde o **cliente** precisa adicionar linhas, **por empresa** (`BelongsToEmpresa`).
- Não recriar listas oficiais BR/ISO que o core já semeia em `app/Models/Referencia/` (`cargos`, `bancos`, `paises`, `municipios`, `estados`, `tipos_logradouro`).
- Uma regra **memorizável** para decidir a categoria de qualquer campo novo.

## Alternativas consideradas

### Alt 1: Tudo ENUM PHP (código)

- Prós: máxima type-safety; CHECK em tudo; lógica centralizada; testes determinísticos.
- Contras: engessa o cliente — todo departamento/função/tipo de afastamento/escala/rubrica novo exige deploy. Contraria o requisito central "tudo configurável". Inviável para os domínios que a empresa customiza.

### Alt 2: Tudo CATÁLOGO tenant (CRUD no banco)

- Prós: configurabilidade total; o cliente muda qualquer valor.
- Contras: perde a type-safety dos backed enums, o `match` exaustivo e o CHECK; força "lógica em dado" (a regra de FGTS de um vínculo, o fator de uma HE viram linha editável que código precisa interpretar por string mágica). Recria listas oficiais (CBO, bancos) que o core já mantém. Frágil e propenso a typo silencioso — exatamente o que [ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md) combate.

### Alt 3: Fronteira em três categorias (escolhida)

- Prós: cada campo na categoria que respeita sua natureza; type-safety/lógica onde importa, configurabilidade onde o cliente manda, reaproveitamento das referências do core. Regra mnemônica reduz a indecisão.
- Contras: exige uma **decisão consciente** por campo (não há default automático); o padrão híbrido (catálogo com colunas-flag) precisa ser entendido pela equipe.

## Decisão

Três categorias, com uma regra de fronteira explícita.

|                          | **ENUM PHP** ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)) | **CATÁLOGO tenant** (CRUD)       | **REFERÊNCIA global**      |
| ------------------------ | ------------------------------------------------------------------------------------- | -------------------------------- | -------------------------- |
| Quem define              | Engenharia (código)                                                                   | O cliente (UI), com seeds padrão | Dado oficial BR/ISO (core) |
| Muda em runtime?         | Não (deploy)                                                                          | Sim, por empresa                 | Raramente (sync)           |
| Lógica/cálculo no valor? | Sim                                                                                   | Não (rótulo + flags)             | Não                        |
| Exemplo                  | `StatusFuncionario`, `TipoHoraExtra`                                                  | `departamentos`, `rubricas`      | `cargos` (CBO), `bancos`   |

**Regra mnemônica:** _tem `if`/cálculo no valor → ENUM · o cliente adiciona linhas → CATÁLOGO · é lista oficial BR/ISO → REFERÊNCIA._

**ENUM backed** ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)) — coluna `VARCHAR` + CHECK + cast no model, em `packages/modulo-rh/src/Enums/`: `StatusFuncionario`, `Sexo`, `EstadoCivil`, `Escolaridade`, `RacaCor`, `TipoVinculo` (`geraFgts()`, `temCarteira()`), `RegimeTrabalho` (`baseCalculoHoraExtra()`), `GrauParentesco`, `TipoContaBancaria`, `Titularidade`, `TipoChavePix`, `TipoContato`, `TipoTelefone`, `TipoEndereco`, `TipoEscala`, `DiaSemana` (int, ISO), `TipoEventoFuncional`, `TipoHoraExtra` (`fatorPadraoBps()`, `adicionalNoturno()`), `StatusHoraExtra` (`isFinal()`), `NaturezaRubrica`. São domínios fixos do eSocial ou valores com lógica/badge/cálculo.

**CATÁLOGO tenant** — CRUD por empresa (`BelongsToEmpresa` + `SoftDeletes` + `ComLixeira`), semeado por `ProvisionarCatalogosRh` (idempotente), com **colunas-flag de comportamento** quando preciso: `departamentos` (árvore), `funcoes` (+ pivot `funcionario_funcao`), `tipos_documento` (flags `exige_numero/validade/orgao_emissor/arquivo`, `sensivel_lgpd`), `tipos_afastamento` (flags eSocial `remunerado/conta_como_falta/suspende_contrato/exige_atestado` + `codigo_esocial`), `escalas` (+ `escala_dias`, `escala_funcionario`), `rubricas` (`natureza` embutida + `incide_inss/fgts/irrf`). A **linha** é do cliente; as **colunas-flag** dão comportamento sem promover o valor a enum.

**REFERÊNCIA global** — listas oficiais sem `empresa_id`, semeadas pelo core, **reaproveitadas** (não recriadas): `cargos` (CBO), `bancos`, `paises`, `municipios`, `estados`, `tipos_logradouro`. O RH referencia via FK. Parâmetros nacionais por vigência (INSS/IRRF/salário-família) entram como referência versionada `tabelas_legais` (seed do pacote, atualizável por competência — ver [07](../07-jornada-horas-extras-folha.md)).

**Casos de fronteira registrados:**

- **Cargo** fica em REFERÊNCIA (`cargos`/CBO), satisfazendo o teste de intenção que espera `cargosDisponiveis`. O **nível hierárquico** que o organograma precisa mora em `funcionarios.cargo_nivel` (cache desnormalizado). Se o cliente exigir cargos próprios (não-CBO) com CRUD por empresa, a evolução é **aditiva**: promover para catálogo tenant `cargos_empresa` (`rh.cargos.*`), sem quebrar o que existe.
- **`NaturezaRubrica`** (provento/desconto/informativa) é ENUM **embutido** numa linha de catálogo — exemplo de coexistência: a rubrica é catálogo do cliente, mas sua natureza dirige cálculo, logo é enum.
- **Centro de custo** (`centros_custo`, [01 §A12](../01-modelo-de-dominio.md)) é **CATÁLOGO tenant opcional** (decisão D1 desta revisão): o cliente cria linhas, **sem** lógica/cálculo atrelado ao valor (só rótulo + agrupamento gerencial/financeiro) — aplica-se a regra mnemônica "o cliente adiciona linhas → CATÁLOGO". **Não é REFERÊNCIA** (não há lista oficial BR/ISO; não existe no core — verificado) **nem ENUM** (não dirige `if`/cálculo; é dimensão de agrupamento). Entra de forma **aditiva** (FK nullable `funcionarios.centro_custo_id`), sem bloquear o B1 mínimo — CRUD em [04 §7.1](../04-catalogos-configuraveis.md).

## Consequências

**Positivas:**

- Cada campo na categoria certa: type-safety e CHECK onde há regra, CRUD por empresa onde o cliente customiza, zero reinvenção das listas oficiais do core.
- A regra mnemônica torna a decisão de modelagem repetível e revisável em PR.
- O padrão híbrido (catálogo + flags) entrega "tudo configurável" sem espalhar lógica em strings mágicas: o sistema sabe, por flag, se um afastamento é remunerado ou se uma rubrica incide INSS.

**Negativas / a gerenciar:**

- Exige decisão consciente por campo — não há default; revisar a categoria de todo campo novo no PR contra a regra mnemônica.
- Mudar a categoria depois é migration (enum→catálogo move dados de código para o banco): preferível acertar na modelagem. Evoluções previstas (ex.: `cargos_empresa`) já são desenhadas como aditivas.
- A equipe precisa entender quando uma flag basta e quando o valor "merece" virar enum (regra: tem `if`/cálculo → enum).

## Referências

- [ADR-0010: Enums PHP backed em todo campo enumerado](../../../../architecture/adrs/ADR-0010-enums-php-backed.md) — base da categoria ENUM (CHECK, `match`, lógica no valor).
- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — catálogos/enums do pacote, core intocado.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§1 a fronteira, §3 Bloco A catálogos, §4 enums) · [04 — Catálogos Configuráveis](../04-catalogos-configuraveis.md) · [07 — Jornada, Horas Extras e Folha](../07-jornada-horas-extras-folha.md) (rubricas e tabelas legais).
