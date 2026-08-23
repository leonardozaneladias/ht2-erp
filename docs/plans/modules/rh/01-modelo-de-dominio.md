# 01 — Modelo de Domínio e Dicionário de Dados

> **Fonte de verdade de schema** do módulo de RH. Todos os demais documentos da suíte referenciam os nomes de tabelas, colunas, enums e permissões definidos aqui. Ao implementar, divergências devem ser corrigidas **neste** documento primeiro.
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · `packages/extensao-rh/` · views `rh::` · banco **PostgreSQL 16** · multi-tenant lógico por `empresa_id`.

Relacionados: [00-prd.md](00-prd.md) · [05-organograma-acl-hierarquica.md](05-organograma-acl-hierarquica.md) · [07-jornada-horas-extras-folha.md](07-jornada-horas-extras-folha.md) · [adrs/ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)

---

## 0. Convenções herdadas do core (aplicadas a TODAS as tabelas do módulo)

Verificadas no código real (`app/Models/Exemplo.php`, `app/Models/Concerns/*`, `app/Models/Anexo.php`, migrations e ADRs):

- **Tenancy** — trait `App\Models\Concerns\BelongsToEmpresa`: toda tabela de negócio tem `empresa_id BIGINT NOT NULL`, global scope `empresa` automático e auto-fill no `creating`. `filial_id BIGINT NULL` onde a lotação física importa.
- **Soft delete** — contrato `HT2ML\Core\Models\Contracts\UsaSoftDeletes` + trait `SoftDeletes`: `deleted_at` em toda tabela de negócio e nos catálogos tenant. Lixeira via `HT2ML\Core\Livewire\Concerns\ComLixeira` (3 permissões: `deletar`→lixeira, `restaurar`, `excluir_permanente`→force-delete).
- **Auditoria** — trait `App\Models\Concerns\Auditavel` (spatie/activitylog). PII vai para `atributosNaoAuditados()` por model (ver §8 LGPD).
- **Dinheiro** — INTEGER em centavos (ADR-0014). Coluna `*_centavos`. Nunca `float`/`decimal` para dinheiro. Operações via helper de Money do core.
- **Tempo/duração** — minutos inteiros (`*_minutos`) para evitar float de horas. Horários do dia em `TIME`.
- **Enums** — PHP backed (ADR-0010): coluna `VARCHAR` + **CHECK constraint** Postgres com a lista de valores + cast `EnumClass::class` no model.
- **Chave pública** — o core hoje faz route binding por `id` (ex.: rota `exemplos/{exemplo}`); **não há `HasUlid` implementado**. O ADR-0004 (ULID público) é **aspiracional**: este módulo segue o padrão real (`id` interno) e adota ULID apenas se/quando o core introduzir a trait compartilhada. Onde uma URL amigável é desejável, usa-se `slug`/`matricula`, não ULID.
- **`unique` por tenant** — `Rule::unique()->where('empresa_id', ...)` na validação **+** índice único composto **parcial** no banco: `UNIQUE (empresa_id, <coluna>) WHERE deleted_at IS NULL` (libera o valor após soft-delete; padrão real visto em `add_deleted_at_*`).
- **Migrations incrementais** — `create_<tabela>_table` + migrations aditivas separadas (`add_<coluna>_to_<tabela>_table`), como em `exemplos`. No pacote, vivem em `packages/extensao-rh/database/migrations` (carregadas por `loadMigrationsFrom`).
- **Tipos Postgres** — `BIGINT`/`BIGSERIAL` (FK/PK), `VARCHAR(n)`, `TEXT`, `DATE`, `TIMESTAMP`, `TIME`, `BOOLEAN`, `SMALLINT`, `INTEGER`, `JSONB`. `NUMERIC(p,s)` apenas para percentuais/quantidades — **nunca** dinheiro.

### Catálogos de **referência global** já existentes — REAPROVEITADOS (não recriar)

Confirmados em `app/Models/Referencia/` (+ seeders em `database/seeders/Referencia/`): `Pais`, `Estado`, `Municipio`, `TipoLogradouro`, `Banco`, `Moeda`, `Cargo`, `Cnae`, `Cfop`, `Ncm`. São globais (sem `empresa_id`), com seed nacional. O RH referencia:

| Conceito no RH                    | Tabela de referência          | Uso                                                                               |
| --------------------------------- | ----------------------------- | --------------------------------------------------------------------------------- |
| Cargo / CBO                       | `cargos` (`Referencia\Cargo`) | `funcionarios.cargo_id` (catálogo selecionável — satisfaz `FuncionarioCargoTest`) |
| Banco                             | `bancos` (`Referencia\Banco`) | `funcionario_dados_bancarios.banco_id`                                            |
| País (nacionalidade)              | `paises`                      | `funcionarios.nacionalidade_pais_id`, `funcionario_enderecos.pais_id`             |
| Município (naturalidade/endereço) | `municipios`                  | `funcionarios.naturalidade_municipio_id`, `funcionario_enderecos.municipio_id`    |
| UF                                | `estados`                     | desnormalização de UF em endereço                                                 |
| Tipo de logradouro                | `tipos_logradouro`            | `funcionario_enderecos.tipo_logradouro_id`                                        |

> **Decisão sobre Cargo** (ver [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)): a Fase 1 reaproveita o catálogo global `cargos` (CBO) como fonte do `cargo_id`, pois há teste de intenção que espera `cargosDisponiveis` populado pelo `CargoSeeder`. O **nível hierárquico** do cargo, exigido pelo organograma, mora em `funcionarios.cargo_nivel` (SMALLINT desnormalizado) **ou** numa coluna `nivel_hierarquico` adicionada a `cargos` se o catálogo global for editável por Tabelas Auxiliares. Caso o cliente precise de cargos próprios (não-CBO) com CRUD por empresa, promover para catálogo tenant `cargos_empresa` é uma evolução aditiva — registrada no ADR.

---

## 1. Fronteira de modelagem: ENUM vs CATÁLOGO vs REFERÊNCIA GLOBAL

Regra (detalhada em [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)):

|                              | **ENUM PHP** (ADR-0010) | **CATÁLOGO tenant** (CRUD)       | **REFERÊNCIA global**      |
| ---------------------------- | ----------------------- | -------------------------------- | -------------------------- |
| Quem define                  | Engenharia (código)     | O cliente (UI), com seeds padrão | Dado oficial BR/ISO (sync) |
| Muda em runtime?             | Não (deploy)            | Sim, por empresa                 | Raramente                  |
| Tem lógica/cálculo atrelado? | Sim                     | Não (rótulo + flags)             | Não                        |
| Exemplo                      | `StatusFuncionario`     | `departamentos`, `rubricas`      | `cargos`, `bancos`         |

**Mnemônico:** _tem `if`/cálculo no valor → ENUM · o cliente adiciona linhas → CATÁLOGO · é lista oficial BR/ISO → REFERÊNCIA._

Catálogos com **flags de comportamento** (ex.: `tipos_afastamento.remunerado`, `rubricas.incide_inss`) são o padrão híbrido: a _linha_ é do cliente, mas as _colunas-flag_ dão comportamento sem virar enum.

### Decisão item a item (requisitos do cliente)

| Conceito                                                     | Decisão                                                | Por quê                                                                                     |
| ------------------------------------------------------------ | ------------------------------------------------------ | ------------------------------------------------------------------------------------------- |
| Status do funcionário                                        | ENUM `StatusFuncionario`                               | conjunto fixo com lógica/badge                                                              |
| Sexo, Estado civil, Escolaridade, Raça/cor                   | ENUM                                                   | domínios fixos do eSocial                                                                   |
| Tipo de vínculo (CLT/PJ/estágio/…)                           | ENUM `TipoVinculo`                                     | dirige cálculo (FGTS, regras)                                                               |
| Regime (mensalista/horista/…)                                | ENUM `RegimeTrabalho`                                  | dirige base de cálculo da HE                                                                |
| Tipo de conta / chave PIX / telefone / endereço / parentesco | ENUM                                                   | fixos (alguns com validação)                                                                |
| **Departamento**                                             | CATÁLOGO `departamentos`                               | cliente cria, com hierarquia                                                                |
| **Cargo**                                                    | REFERÊNCIA `cargos` (CBO) + nível                      | catálogo oficial reaproveitado                                                              |
| **Funções/Extras (líder, preposto…)**                        | CATÁLOGO `funcoes` (N:N)                               | vocabulário por empresa                                                                     |
| **Tipos de documento**                                       | CATÁLOGO `tipos_documento`                             | cliente adiciona, com flags                                                                 |
| **Tipos de afastamento**                                     | CATÁLOGO `tipos_afastamento`                           | cliente customiza + flags eSocial                                                           |
| **Escalas/Jornadas**                                         | CATÁLOGO `escalas` + `escala_dias`                     | reutilizáveis, criadas pelo cliente                                                         |
| **Rubricas (proventos/descontos)**                           | CATÁLOGO `rubricas`                                    | cliente define, com incidências                                                             |
| Tipo de evento de histórico                                  | ENUM `TipoEventoFuncional`                             | dirige snapshot/colunas                                                                     |
| Tipo de hora extra (50/100/noturno/DSR)                      | ENUM `TipoHoraExtra` (+ override de fator por empresa) | percentual com lógica                                                                       |
| Status de aprovação da HE                                    | ENUM `StatusHoraExtra`                                 | máquina de estados                                                                          |
| Tabelas legais (INSS/IRRF/salário-família)                   | REFERÊNCIA por vigência `tabelas_legais`               | parâmetros nacionais por competência                                                        |
| **Campos personalizados (definições)**                       | CATÁLOGO **meta** `campos_personalizados`              | o cliente cria campos por entidade, sem código ([10](10-campos-personalizados.md))          |
| Tipo de campo personalizado                                  | ENUM `TipoCampoPersonalizado`                          | dirige componente de UI + regra de validação                                                |
| Status do atestado                                           | ENUM `StatusAtestado`                                  | máquina de estados (`pendente`→`em_analise`→`aprovado`/`rejeitado`; `aprovado`→`estornado`) |
| Origem do atestado                                           | ENUM `OrigemAtestado`                                  | dirige fluxo/permissão de quem lançou                                                       |
| Tipo de ocorrência (falta/atraso)                            | ENUM `TipoOcorrencia`                                  | domínio fixo (falta, atraso, saída antecipada)                                              |

> Os conceitos novos (atestado, ocorrência, campos personalizados) entram nesta revisão como **fundação aditiva** (§6); o detalhe vive em [10](10-campos-personalizados.md), [11](11-importacao-exportacao.md) e [12](12-ausencias-faltas-atestados-afastamentos.md).

---

## 2. ERD textual

```
empresas (core) 1 ─< funcionarios >─ 0..1 admin_users (core)   [vínculo 1:1 opcional → ACL hierárquica]
empresas 1 ─< departamentos, funcoes, tipos_documento, tipos_afastamento, escalas, rubricas   [catálogos tenant]
empresas 1 ─< campos_personalizados             [definições de campos por entidade — doc 10; valores em funcionarios.dados_personalizados JSONB]
empresas 1 ─< importacoes                        [log de importação de planilha — apoio, doc 11]

funcionarios ─< funcionarios            (self: gestor_id)      [organograma de pessoas → ACL]
departamentos ─< departamentos                      (self: departamento_pai_id)   [sub-departamentos]
cargos (ref. global, CBO) 1 ─< funcionarios (cargo_id)

funcionarios 1 ─< funcionario_contatos          (emails/telefones)
funcionarios 1 ─< funcionario_enderecos         (>─ municipios/paises/tipos_logradouro)
funcionarios 1 ─< funcionario_dados_bancarios   (>─ bancos)
funcionarios 1 ─< funcionario_dependentes
funcionarios 1 ─< funcionario_documentos        (>─ 0..1 anexos [core polimórfico] + tipos_documento)
funcionarios 1 ─< funcionario_eventos           [append-only, snapshot JSONB]
funcionarios 1 ─< funcionario_afastamentos      (>─ tipos_afastamento)
funcionarios 1 ─< atestados                     (>─ 0..1 anexos; >─ 0..1 funcionario_afastamentos)   [workflow de ausência — doc 12]
funcionarios 1 ─< ocorrencias                   (>─ 0..1 atestados; >─ 0..1 tipos_afastamento)        [faltas/atrasos — doc 12]
funcionarios 1 ─< horas_extras                  (>─ admin_users: lançou/aprovou; >─ rubricas)

funcionarios M ─< funcionario_funcao >─ M funcoes      [pivot c/ vigência início/fim]
funcionarios M ─< funcionario_escala >─ M escalas      [pivot c/ vigência]
escalas 1 ─< escala_dias                                [1 linha por dia×turno]

rubricas (catálogo) ── referenciada por horas_extras e (futuro) apuração de folha
tabelas_legais (ref. por vigência) ── INSS/IRRF/salário-família p/ a fundação de folha

anexos (core, MorphTo) ──> funcionarios | funcionario_documentos | funcionario_afastamentos | funcionario_dependentes
```

Cardinalidades-chave: `funcionario↔admin_user` = 1:1 (0..1 dos dois lados) · `funcionario↔gestor` = N:1 (árvore) · `departamento↔departamento_pai` = N:1 (árvore) · `funcionario↔funcao` e `funcionario↔escala` = N:N com vigência.

---

## 3. Dicionário de dados

Legenda de traits: **[E]** BelongsToEmpresa · **[S]** SoftDeletes · **[A]** Auditavel · **[Anx]** alvo de Anexo (polimórfico).

### Bloco A — Catálogos tenant-scoped

#### A1. `departamentos` — [E][S][A] (Departamento, com sub-departamentos)

| Coluna                           | Tipo         | Null | Notas                                                          |
| -------------------------------- | ------------ | ---- | -------------------------------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                                                |
| empresa_id                       | BIGINT       | N    | FK→empresas cascade                                            |
| filial_id                        | BIGINT       | S    | FK→filiais nullOnDelete                                        |
| departamento_pai_id              | BIGINT       | S    | FK→departamentos (self) restrictOnDelete                       |
| codigo                           | VARCHAR(30)  | S    | código interno do cliente                                      |
| nome                             | VARCHAR(120) | N    |                                                                |
| descricao                        | TEXT         | S    |                                                                |
| responsavel_funcionario_id       | BIGINT       | S    | FK→funcionarios nullOnDelete (nullable evita ciclo de criação) |
| ativo                            | BOOLEAN      | N    | default true                                                   |
| ordem                            | INTEGER      | S    | ordenação UI                                                   |
| created_at/updated_at/deleted_at |              |      |                                                                |

Unique: `(empresa_id, nome)` parcial; `(empresa_id, codigo)` parcial. Índices: `(empresa_id, ativo)`, `departamento_pai_id`, `filial_id`. CHECK `departamento_pai_id <> id`. Casts: `ativo`, `ordem`.

#### A2. `funcoes` — [E][S][A] (funções/extras: líder, preposto, supervisor…)

| Coluna                           | Tipo         | Null | Notas        |
| -------------------------------- | ------------ | ---- | ------------ |
| id                               | BIGSERIAL PK | N    |              |
| empresa_id                       | BIGINT       | N    | FK cascade   |
| nome                             | VARCHAR(80)  | N    |              |
| descricao                        | TEXT         | S    |              |
| cor                              | VARCHAR(7)   | S    | hex p/ badge |
| ativo                            | BOOLEAN      | N    | default true |
| created_at/updated_at/deleted_at |              |      |              |

Unique: `(empresa_id, nome)` parcial. Índice: `(empresa_id, ativo)`.

#### A3. `funcionario_funcao` — [E][A] (pivot N:N com vigência)

| Coluna                | Tipo         | Null | Notas                              |
| --------------------- | ------------ | ---- | ---------------------------------- |
| id                    | BIGSERIAL PK | N    |                                    |
| empresa_id            | BIGINT       | N    | FK cascade                         |
| funcionario_id        | BIGINT       | N    | FK cascade                         |
| funcao_id             | BIGINT       | N    | FK→funcoes restrict                |
| inicio                | DATE         | N    |                                    |
| fim                   | DATE         | S    | null = vigente                     |
| observacao            | TEXT         | S    |                                    |
| created_at/updated_at |              |      | sem soft-delete: encerra via `fim` |

Unique: `(funcionario_id, funcao_id, inicio)`. Índices: `funcionario_id`, `funcao_id`, `(funcionario_id, fim)`.

#### A4. `tipos_documento` — [E][S][A] (catálogo de tipos de documento do funcionário)

| Coluna                           | Tipo         | Null | Notas                    |
| -------------------------------- | ------------ | ---- | ------------------------ |
| id                               | BIGSERIAL PK | N    |                          |
| empresa_id                       | BIGINT       | N    | FK cascade               |
| codigo                           | VARCHAR(30)  | N    | ex.: `rg`, `cpf`, `ctps` |
| nome                             | VARCHAR(80)  | N    |                          |
| exige_numero                     | BOOLEAN      | N    | default true             |
| exige_validade                   | BOOLEAN      | N    | default false            |
| exige_orgao_emissor              | BOOLEAN      | N    | default false            |
| exige_arquivo                    | BOOLEAN      | N    | default false            |
| sensivel_lgpd                    | BOOLEAN      | N    | default true             |
| ativo                            | BOOLEAN      | N    | default true             |
| created_at/updated_at/deleted_at |              |      |                          |

Unique: `(empresa_id, codigo)` parcial. Índice: `(empresa_id, ativo)`.

> Nome de classe do model: `TipoDocumentoRh` (a tabela é `tipos_documento`) para não confundir com o enum `App\Enums\TipoDocumento` do core, que trata de numeração fiscal — conceito distinto.

#### A5. `tipos_afastamento` — [E][S][A] (híbrido com flags eSocial)

| Coluna                           | Tipo         | Null | Notas                             |
| -------------------------------- | ------------ | ---- | --------------------------------- |
| id                               | BIGSERIAL PK | N    |                                   |
| empresa_id                       | BIGINT       | N    | FK cascade                        |
| codigo                           | VARCHAR(30)  | N    | ex.: `ferias`, `atestado`, `inss` |
| codigo_esocial                   | VARCHAR(10)  | S    | tabela 18 eSocial                 |
| nome                             | VARCHAR(100) | N    |                                   |
| remunerado                       | BOOLEAN      | N    | default true                      |
| conta_como_falta                 | BOOLEAN      | N    | default false                     |
| suspende_contrato                | BOOLEAN      | N    | default false                     |
| exige_atestado                   | BOOLEAN      | N    | default false                     |
| ativo                            | BOOLEAN      | N    | default true                      |
| created_at/updated_at/deleted_at |              |      |                                   |

Unique: `(empresa_id, codigo)` parcial. Índice: `(empresa_id, ativo)`.

#### A6. `escalas` — [E][S][A] (jornada reutilizável, cabeçalho)

| Coluna                           | Tipo         | Null | Notas                            |
| -------------------------------- | ------------ | ---- | -------------------------------- |
| id                               | BIGSERIAL PK | N    |                                  |
| empresa_id                       | BIGINT       | N    | FK cascade                       |
| nome                             | VARCHAR(100) | N    | ex.: "44h Comercial"             |
| descricao                        | TEXT         | S    |                                  |
| tipo                             | VARCHAR(20)  | N    | ENUM `TipoEscala` + CHECK        |
| carga_semanal_minutos            | INTEGER      | S    | conferido na escrita (cache)     |
| horas_mensais_divisor            | SMALLINT     | N    | default 220 (base do valor-hora) |
| ativo                            | BOOLEAN      | N    | default true                     |
| created_at/updated_at/deleted_at |              |      |                                  |

Unique: `(empresa_id, nome)` parcial. Detalhes de cálculo em [07](07-jornada-horas-extras-folha.md).

#### A7. `escala_dias` — [E][A] (1 linha por dia×turno)

| Coluna                | Tipo         | Null | Notas                                                     |
| --------------------- | ------------ | ---- | --------------------------------------------------------- |
| id                    | BIGSERIAL PK | N    |                                                           |
| empresa_id            | BIGINT       | N    | FK cascade                                                |
| escala_id             | BIGINT       | N    | FK→escalas cascade                                        |
| dia_semana            | SMALLINT     | N    | ENUM `DiaSemana` (1=segunda..7=domingo, ISO) + CHECK 1..7 |
| ordem_turno           | SMALLINT     | N    | default 1 (1=manhã, 2=tarde…)                             |
| eh_folga              | BOOLEAN      | N    | default false                                             |
| entrada               | TIME         | S    | null se folga                                             |
| saida                 | TIME         | S    | (saida<entrada ⇒ cruza meia-noite)                        |
| created_at/updated_at |              |      |                                                           |

Unique: `(escala_id, dia_semana, ordem_turno)`. CHECK `eh_folga OR (entrada IS NOT NULL AND saida IS NOT NULL)`. O **intervalo** (almoço) é a lacuna entre turnos do mesmo dia.

#### A8. `escala_funcionario` — [E][A] (atribuição com vigência = histórico)

| Coluna                | Tipo         | Null | Notas               |
| --------------------- | ------------ | ---- | ------------------- |
| id                    | BIGSERIAL PK | N    |                     |
| empresa_id            | BIGINT       | N    | FK cascade          |
| funcionario_id        | BIGINT       | N    | FK cascade          |
| escala_id             | BIGINT       | N    | FK→escalas restrict |
| vigencia_inicio       | DATE         | N    |                     |
| vigencia_fim          | DATE         | S    | null = vigente      |
| created_at/updated_at |              |      |                     |

Índices: `funcionario_id`, `(funcionario_id, vigencia_fim)`, `escala_id`. Índice parcial Postgres `UNIQUE (funcionario_id) WHERE vigencia_fim IS NULL` (no máx. uma vigência aberta). Regra de não-sobreposição validada na Action.

#### A9. `rubricas` — [E][S][A] (catálogo de proventos/descontos — fundação de folha)

| Coluna                           | Tipo         | Null | Notas                                                          |
| -------------------------------- | ------------ | ---- | -------------------------------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                                                |
| empresa_id                       | BIGINT       | N    | FK cascade                                                     |
| codigo                           | VARCHAR(30)  | N    | ex.: `he_50`, `salario`, `desc_inss`                           |
| codigo_esocial                   | VARCHAR(10)  | S    | tabela 03 (rubricas) eSocial                                   |
| nome                             | VARCHAR(100) | N    |                                                                |
| natureza                         | VARCHAR(12)  | N    | ENUM `NaturezaRubrica` (provento/desconto/informativa) + CHECK |
| incide_inss                      | BOOLEAN      | N    | default false                                                  |
| incide_fgts                      | BOOLEAN      | N    | default false                                                  |
| incide_irrf                      | BOOLEAN      | N    | default false                                                  |
| referencia_he_tipo               | VARCHAR(20)  | S    | mapeia `TipoHoraExtra` → rubrica (opcional)                    |
| ativo                            | BOOLEAN      | N    | default true                                                   |
| created_at/updated_at/deleted_at |              |      |                                                                |

Unique: `(empresa_id, codigo)` parcial. **Fundação** apenas: ver fronteira em [07 §Folha](07-jornada-horas-extras-folha.md).

#### A10. `fator_horas_extras` — [E][S][A] (override fino do fator de HE por empresa)

Catálogo tenant **opcional e fino**: sobrepõe, por empresa, o fator-padrão do enum `TipoHoraExtra::fatorPadraoBps()` (convenção coletiva — ex.: HE a 60%, domingo a 110%). Mantém o **enum como fonte da semântica** (`adicionalNoturno()`, mapeamento p/ rubrica) e move só o **número do fator** para o banco — padrão híbrido de [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md) aplicado a um único atributo numérico. Decisão e resolução de precedência (`override ativo ?? fatorPadraoBps()`) em [07 §3.3](07-jornada-horas-extras-folha.md) e [ADR-RH-004](adrs/ADR-RH-004-jornada-horas-extras-folha.md).

| Coluna                           | Tipo         | Null | Notas                                            |
| -------------------------------- | ------------ | ---- | ------------------------------------------------ |
| id                               | BIGSERIAL PK | N    |                                                  |
| empresa_id                       | BIGINT       | N    | FK cascade                                       |
| tipo                             | VARCHAR(24)  | N    | valor de `TipoHoraExtra` + CHECK (lista do enum) |
| fator_bps                        | INTEGER      | N    | sobrepõe `fatorPadraoBps()` (basis points)       |
| ativo                            | BOOLEAN      | N    | default true                                     |
| created_at/updated_at/deleted_at |              |      |                                                  |

Unique: `(empresa_id, tipo)` parcial `WHERE deleted_at IS NULL` (no máx. um override ativo por tipo/empresa). Índice: `(empresa_id, ativo)`. CHECK `fator_bps >= 0`. **Vigência opcional (evolução):** se o cliente exigir histórico de fatores por período, adicionar `vigencia_inicio`/`vigencia_fim` (DATE) de forma aditiva, com unique parcial de vigência aberta análogo a `escala_funcionario` (§A8); na Fase 1 vale o override corrente (sem vigência). O **CRUD/tela** deste catálogo entra como incremento em **B6/B7** ([02](02-fase-1-blueprint.md)); permissões `rh.fator_horas_extras.*` em §10.

#### A11. `campos_personalizados` — [E][S][A] (meta-catálogo: definições de campos do cliente)

Catálogo tenant **meta**: o cliente define, **sem código**, campos extras por **entidade** (`funcionario` na Fase 1). Os **valores** moram numa coluna JSONB na entidade hospedeira (`funcionarios.dados_personalizados`, §B1) — modelo **JSONB-híbrido**, não EAV nem schemaless ([ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)). Trait reutilizável `TemCamposPersonalizados`, enum `TipoCampoPersonalizado` (§4) e mecânica completa em [10](10-campos-personalizados.md). É **fundação reutilizável** (candidata a promoção ao core — analogia ao [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md)).

| Coluna                           | Tipo         | Null | Notas                                                                          |
| -------------------------------- | ------------ | ---- | ------------------------------------------------------------------------------ |
| id                               | BIGSERIAL PK | N    |                                                                                |
| empresa_id                       | BIGINT       | N    | FK→empresas cascade                                                            |
| entidade                         | VARCHAR(40)  | N    | entidade hospedeira (ex.: `funcionario`); na Fase 1 só `funcionario`           |
| chave                            | VARCHAR(40)  | N    | slug `snake_case` — chave no JSONB `dados_personalizados`                      |
| rotulo                           | VARCHAR(120) | N    | label exibido na UI                                                            |
| tipo                             | VARCHAR(20)  | N    | ENUM `TipoCampoPersonalizado` + CHECK                                          |
| opcoes                           | JSONB        | S    | lista de opções (apenas `select`/`multi_select`)                               |
| obrigatorio                      | BOOLEAN      | N    | default false                                                                  |
| sensivel                         | BOOLEAN      | N    | default false · **LGPD**: liga mascaramento + exclusão de auditoria (dinâmico) |
| grupo                            | VARCHAR(60)  | S    | agrupamento/aba na UI                                                          |
| ordem                            | INTEGER      | S    | ordenação                                                                      |
| ajuda                            | VARCHAR(255) | S    | texto de ajuda (tooltip)                                                       |
| regras                           | JSONB        | S    | validação extra (min/max/regex) resolvida por tipo                             |
| ativo                            | BOOLEAN      | N    | default true                                                                   |
| created_at/updated_at/deleted_at |              |      |                                                                                |

Unique: `(empresa_id, entidade, chave)` parcial `WHERE deleted_at IS NULL`. Índices: `(empresa_id, entidade, ativo)`. CHECK `tipo IN (...)` (lista do enum). **Sem seed** (catálogo nasce vazio — o cliente cria). Permissões `rh.campos_personalizados.*` em §10.

#### A12. `centros_custo` — [E][S][A] (centro de custo: catálogo tenant **opcional**)

Catálogo tenant **opcional e aditivo** (decisão **D1** desta revisão): agrupamento **gerencial/financeiro** do funcionário (e, opcionalmente, do departamento), para relatórios e rateio. É **dimensão organizacional paralela**, não governa a ACL ([05 §3.2](05-organograma-acl-hierarquica.md)). **Não existe no core** (verificado: não há model/migração `centro_custo`), logo nasce aqui — sem recriar nada. É **CATÁLOGO** (não enum): o cliente cria linhas, sem lógica/cálculo no valor ([ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md) §casos de fronteira). Por ser **aditivo**, **não bloqueia o B1 mínimo** — entra como migration aditiva quando o cliente precisar. CRUD/seed em [04 §7.1](04-catalogos-configuraveis.md).

| Coluna                           | Tipo         | Null | Notas                           |
| -------------------------------- | ------------ | ---- | ------------------------------- |
| id                               | BIGSERIAL PK | N    |                                 |
| empresa_id                       | BIGINT       | N    | FK→empresas cascade             |
| codigo                           | VARCHAR(30)  | S    | código contábil/gerencial       |
| nome                             | VARCHAR(120) | N    | ex.: "Administrativo", "Obra X" |
| descricao                        | TEXT         | S    |                                 |
| ativo                            | BOOLEAN      | N    | default true                    |
| ordem                            | INTEGER      | S    | ordenação UI                    |
| created_at/updated_at/deleted_at |              |      |                                 |

Unique: `(empresa_id, codigo)` parcial; `(empresa_id, nome)` parcial. Índice: `(empresa_id, ativo)`. Vínculo: **FK nullable `funcionarios.centro_custo_id`** (`nullOnDelete`, §B1) e — **evolução opcional** — `departamentos.centro_custo_id`. Permissões `rh.centros_custo.*` em §10.

### Bloco B — Pessoa / Funcionário (agregado-raiz)

#### B1. `funcionarios` — [E][S][A][Anx] (núcleo: dados pessoais + contratação, eSocial-ready)

| Coluna                                             | Tipo         | Null | Notas                                                                                                                      |
| -------------------------------------------------- | ------------ | ---- | -------------------------------------------------------------------------------------------------------------------------- |
| id                                                 | BIGSERIAL PK | N    | route binding por id                                                                                                       |
| empresa_id                                         | BIGINT       | N    | FK→empresas cascade                                                                                                        |
| filial_id                                          | BIGINT       | S    | FK→filiais nullOnDelete (lotação atual)                                                                                    |
| admin_user_id                                      | BIGINT       | S    | FK→admin_users nullOnDelete (vínculo 1:1 — ver B-ACL)                                                                      |
| gestor_id                                          | BIGINT       | S    | FK→funcionarios (self) nullOnDelete (organograma)                                                                          |
| departamento_id                                    | BIGINT       | S    | FK→departamentos nullOnDelete (atual; histórico em C1)                                                                     |
| cargo_id                                           | BIGINT       | S    | FK→cargos (referência CBO) nullOnDelete (atual; histórico em C1)                                                           |
| cargo_nivel                                        | SMALLINT     | S    | nível hierárquico do cargo (cache p/ organograma)                                                                          |
| centro_custo_id                                    | BIGINT       | S    | FK→centros_custo nullOnDelete (catálogo tenant **opcional** — §A12; **aditivo**, não bloqueia o B1 mínimo)                 |
| _Dados pessoais_                                   |              |      |                                                                                                                            |
| nome                                               | VARCHAR(150) | N    |                                                                                                                            |
| nome_social                                        | VARCHAR(150) | S    |                                                                                                                            |
| cpf                                                | VARCHAR(11)  | N    | só dígitos · PII                                                                                                           |
| rg                                                 | VARCHAR(20)  | S    | PII                                                                                                                        |
| rg_orgao_emissor                                   | VARCHAR(20)  | S    |                                                                                                                            |
| rg_uf                                              | CHAR(2)      | S    |                                                                                                                            |
| data_nascimento                                    | DATE         | S    |                                                                                                                            |
| sexo                                               | VARCHAR(20)  | S    | ENUM `Sexo` + CHECK                                                                                                        |
| estado_civil                                       | VARCHAR(20)  | S    | ENUM `EstadoCivil` + CHECK                                                                                                 |
| escolaridade                                       | VARCHAR(30)  | S    | ENUM `Escolaridade` + CHECK                                                                                                |
| raca_cor                                           | VARCHAR(20)  | S    | ENUM `RacaCor` (eSocial) + CHECK                                                                                           |
| nacionalidade_pais_id                              | BIGINT       | S    | FK→paises                                                                                                                  |
| naturalidade_municipio_id                          | BIGINT       | S    | FK→municipios                                                                                                              |
| nome_mae                                           | VARCHAR(150) | S    | PII                                                                                                                        |
| nome_pai                                           | VARCHAR(150) | S    | PII                                                                                                                        |
| foto_caminho                                       | VARCHAR(255) | S    | disco privado                                                                                                              |
| _Contratação_                                      |              |      |                                                                                                                            |
| matricula                                          | VARCHAR(30)  | N    | gerada/manual                                                                                                              |
| pis_pasep                                          | VARCHAR(11)  | S    | PII eSocial · nullable (cadastro progressivo); **obrigatório na validação eSocial — Fase 4**                               |
| data_admissao                                      | DATE         | N    |                                                                                                                            |
| data_demissao                                      | DATE         | S    | null = ativo                                                                                                               |
| tipo_vinculo                                       | VARCHAR(20)  | N    | ENUM `TipoVinculo` + CHECK                                                                                                 |
| regime_trabalho                                    | VARCHAR(20)  | N    | ENUM `RegimeTrabalho` + CHECK                                                                                              |
| salario_base_centavos                              | INTEGER      | S    | atual; histórico em C1                                                                                                     |
| salario_tipo                                       | VARCHAR(10)  | N    | ENUM (mensal/horista) + CHECK, default mensal                                                                              |
| status                                             | VARCHAR(20)  | N    | ENUM `StatusFuncionario` + CHECK, default `ativo`                                                                          |
| _PCD / Deficiência (dado de saúde — LGPD art. 11)_ |              |      | grupo eSocial `infoDeficiencia`; mesmo rigor do `cid` (§8) — ver [03 §2.1]                                                 |
| def_fisica                                         | BOOLEAN      | S    | deficiência física · **dado sensível de saúde**                                                                            |
| def_visual                                         | BOOLEAN      | S    | deficiência visual · sensível                                                                                              |
| def_auditiva                                       | BOOLEAN      | S    | deficiência auditiva · sensível                                                                                            |
| def_mental                                         | BOOLEAN      | S    | deficiência mental · sensível                                                                                              |
| def_intelectual                                    | BOOLEAN      | S    | deficiência intelectual · sensível                                                                                         |
| reabilitado_readaptado                             | BOOLEAN      | S    | reabilitado/readaptado pelo INSS                                                                                           |
| beneficiario_cota                                  | BOOLEAN      | S    | preenche cota de PCD (Lei 8.213/91 art. 93)                                                                                |
| observacao_pcd                                     | TEXT         | S    | laudo/observações · **PII sensível**                                                                                       |
| _Campos personalizados (doc 10)_                   |              |      |                                                                                                                            |
| dados_personalizados                               | JSONB        | S    | valores dos campos definidos em `campos_personalizados` (§A11); mapa `chave→valor` — ver [10](10-campos-personalizados.md) |
| created_at/updated_at/deleted_at                   |              |      |                                                                                                                            |

Unique (todos parciais `WHERE deleted_at IS NULL`): `(empresa_id, cpf)`, `(empresa_id, matricula)`, `(empresa_id, admin_user_id)`.
Índices: `(empresa_id, status)`, `(empresa_id, departamento_id)`, `(empresa_id, cargo_id)`, `(empresa_id, gestor_id)` (recursão do organograma), `filial_id`, `(empresa_id, nome)`, `(empresa_id, data_admissao)`.
CHECK: `gestor_id <> id`; `data_demissao IS NULL OR data_demissao >= data_admissao`.
`atributosNaoAuditados()`: `['cpf','rg','pis_pasep','nome_mae','nome_pai','def_fisica','def_visual','def_auditiva','def_mental','def_intelectual','reabilitado_readaptado','beneficiario_cota','observacao_pcd']`.

> **Campos personalizados:** a coluna `dados_personalizados` (JSONB) guarda os valores dos campos definidos em `campos_personalizados` (§A11) para a entidade `funcionario`; chaves marcadas `sensivel=true` entram em mascaramento e ficam **fora de auditoria dinamicamente** (resolvido pelo trait `TemCamposPersonalizados`, não por lista estática) — ver [10](10-campos-personalizados.md).

> **PCD = categoria especial de dado pessoal (LGPD art. 11)** — o grupo de deficiência recebe **o mesmo rigor do `cid`** (§8): fora do diff de auditoria (acima) **+** permissão dedicada `rh.funcionarios.ver_dados_sensiveis` (§10) para exibir/editar (sem ela, a UI oculta a seção PCD); a tela ([03 §2.1](03-cadastro-pessoa-documentos.md)) marca a seção com selo "eSocial" e visibilidade restrita. **Alternativa de isolamento físico:** extrair o grupo para uma tabela-filha 1:1 `funcionario_pcd` (mesmas colunas, FK `funcionario_id` única, `[E][S][A]`) — preferível se o cliente exigir segregação de armazenamento/coluna; na Fase 1 o grupo nasce **embutido** em `funcionarios` (colunas nullable, cadastro progressivo). Cobertura eSocial em [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md).

#### B2. `funcionario_contatos` — [E][S][A] (emails + telefones, discriminados)

| Coluna                           | Tipo         | Null | Notas                                       |
| -------------------------------- | ------------ | ---- | ------------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                             |
| empresa_id                       | BIGINT       | N    | FK cascade                                  |
| funcionario_id                   | BIGINT       | N    | FK cascade                                  |
| tipo_contato                     | VARCHAR(10)  | N    | ENUM `TipoContato` (email/telefone) + CHECK |
| subtipo                          | VARCHAR(20)  | S    | ENUM `TipoTelefone` quando telefone         |
| valor                            | VARCHAR(120) | N    | email ou número (dígitos)                   |
| principal                        | BOOLEAN      | N    | default false                               |
| whatsapp                         | BOOLEAN      | N    | default false                               |
| observacao                       | VARCHAR(120) | S    |                                             |
| created_at/updated_at/deleted_at |              |      |                                             |

Índices: `funcionario_id`, `(funcionario_id, tipo_contato, principal)`. Unique parcial: `(funcionario_id, tipo_contato) WHERE principal = true AND deleted_at IS NULL`.

#### B3. `funcionario_enderecos` — [E][S][A]

| Coluna                           | Tipo         | Null | Notas                       |
| -------------------------------- | ------------ | ---- | --------------------------- |
| id                               | BIGSERIAL PK | N    |                             |
| empresa_id                       | BIGINT       | N    | FK cascade                  |
| funcionario_id                   | BIGINT       | N    | FK cascade                  |
| tipo_endereco                    | VARCHAR(20)  | N    | ENUM `TipoEndereco` + CHECK |
| cep                              | VARCHAR(8)   | S    | dígitos                     |
| tipo_logradouro_id               | BIGINT       | S    | FK→tipos_logradouro         |
| logradouro                       | VARCHAR(150) | N    |                             |
| numero                           | VARCHAR(20)  | S    |                             |
| complemento                      | VARCHAR(80)  | S    |                             |
| bairro                           | VARCHAR(80)  | S    |                             |
| municipio_id                     | BIGINT       | S    | FK→municipios               |
| uf                               | CHAR(2)      | S    | desnormalizado              |
| pais_id                          | BIGINT       | S    | FK→paises (default Brasil)  |
| principal                        | BOOLEAN      | N    | default false               |
| created_at/updated_at/deleted_at |              |      |                             |

Índices: `funcionario_id`, `municipio_id`, `cep`. Unique parcial: `(funcionario_id) WHERE principal=true AND deleted_at IS NULL`.

#### B4. `funcionario_dados_bancarios` — [E][S][A] (PII financeira)

| Coluna                           | Tipo         | Null | Notas                            |
| -------------------------------- | ------------ | ---- | -------------------------------- |
| id                               | BIGSERIAL PK | N    |                                  |
| empresa_id                       | BIGINT       | N    | FK cascade                       |
| funcionario_id                   | BIGINT       | N    | FK cascade                       |
| banco_id                         | BIGINT       | S    | FK→bancos                        |
| agencia                          | VARCHAR(10)  | S    |                                  |
| agencia_digito                   | VARCHAR(2)   | S    |                                  |
| conta                            | VARCHAR(20)  | S    | PII                              |
| conta_digito                     | VARCHAR(2)   | S    |                                  |
| tipo_conta                       | VARCHAR(20)  | N    | ENUM `TipoContaBancaria` + CHECK |
| titularidade                     | VARCHAR(20)  | S    | ENUM `Titularidade` + CHECK      |
| pix_tipo                         | VARCHAR(20)  | S    | ENUM `TipoChavePix` + CHECK      |
| pix_chave                        | VARCHAR(120) | S    | PII (candidato a `encrypted`)    |
| principal                        | BOOLEAN      | N    | default true                     |
| created_at/updated_at/deleted_at |              |      |                                  |

Índices: `funcionario_id`, `banco_id`. Unique parcial: `(funcionario_id) WHERE principal=true AND deleted_at IS NULL`. `atributosNaoAuditados()`: `['conta','conta_digito','pix_chave','agencia']`.

#### B5. `funcionario_dependentes` — [E][S][A][Anx]

| Coluna                           | Tipo         | Null | Notas                         |
| -------------------------------- | ------------ | ---- | ----------------------------- |
| id                               | BIGSERIAL PK | N    |                               |
| empresa_id                       | BIGINT       | N    | FK cascade                    |
| funcionario_id                   | BIGINT       | N    | FK cascade                    |
| nome                             | VARCHAR(150) | N    |                               |
| grau_parentesco                  | VARCHAR(30)  | N    | ENUM `GrauParentesco` + CHECK |
| cpf                              | VARCHAR(11)  | S    | PII                           |
| data_nascimento                  | DATE         | S    |                               |
| sexo                             | VARCHAR(20)  | S    | ENUM `Sexo` + CHECK           |
| dependente_ir                    | BOOLEAN      | N    | default false                 |
| dependente_salario_familia       | BOOLEAN      | N    | default false                 |
| dependente_plano_saude           | BOOLEAN      | N    | default false                 |
| created_at/updated_at/deleted_at |              |      |                               |

Índice: `funcionario_id`. `atributosNaoAuditados()`: `['cpf']`.

#### B6. `funcionario_documentos` — [E][S][A] (metadados; binário reusa `anexos`)

| Coluna                           | Tipo         | Null | Notas                                     |
| -------------------------------- | ------------ | ---- | ----------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                           |
| empresa_id                       | BIGINT       | N    | FK cascade                                |
| funcionario_id                   | BIGINT       | N    | FK cascade                                |
| tipo_documento_id                | BIGINT       | N    | FK→tipos_documento restrict               |
| anexo_id                         | BIGINT       | S    | FK→anexos nullOnDelete (arquivo opcional) |
| numero                           | VARCHAR(60)  | S    | PII                                       |
| orgao_emissor                    | VARCHAR(60)  | S    |                                           |
| uf_emissor                       | CHAR(2)      | S    |                                           |
| data_emissao                     | DATE         | S    |                                           |
| data_validade                    | DATE         | S    |                                           |
| observacao                       | TEXT         | S    |                                           |
| created_at/updated_at/deleted_at |              |      |                                           |

Índices: `funcionario_id`, `(empresa_id, tipo_documento_id)`, `anexo_id`, `data_validade` (relatório "a vencer"). `atributosNaoAuditados()`: `['numero']`. O binário vai em `App\Models\Anexo` (`anexavel_type=Funcionario`); ver [03](03-cadastro-pessoa-documentos.md).

### Bloco C — Linha do tempo / histórico

#### C1. `funcionario_eventos` — [E][A] (append-only, snapshot JSONB — ADR-0009)

| Coluna                       | Tipo         | Null | Notas                                                           |
| ---------------------------- | ------------ | ---- | --------------------------------------------------------------- |
| id                           | BIGSERIAL PK | N    |                                                                 |
| empresa_id                   | BIGINT       | N    | FK cascade                                                      |
| funcionario_id               | BIGINT       | N    | FK cascade                                                      |
| tipo_evento                  | VARCHAR(30)  | N    | ENUM `TipoEventoFuncional` + CHECK                              |
| data_evento                  | DATE         | N    | data de efeito                                                  |
| motivo                       | TEXT         | S    |                                                                 |
| cargo_id                     | BIGINT       | S    | FK→cargos nullOnDelete (estado novo)                            |
| departamento_id              | BIGINT       | S    | FK→departamentos nullOnDelete                                   |
| filial_id                    | BIGINT       | S    | FK→filiais nullOnDelete                                         |
| salario_centavos             | INTEGER      | S    | novo salário (eventos salariais)                                |
| salario_anterior_centavos    | INTEGER      | S    |                                                                 |
| snapshot_anterior            | JSONB        | S    | estado relevante antes                                          |
| snapshot_novo                | JSONB        | S    | estado relevante depois                                         |
| registrado_por_admin_user_id | BIGINT       | S    | FK→admin_users nullOnDelete                                     |
| created_at/updated_at        |              |      | **sem `deleted_at`**: append-only; correção = evento de estorno |

Índices: `(funcionario_id, data_evento)`, `(empresa_id, tipo_evento)`, `(empresa_id, data_evento)`. A Action grava o evento **e** atualiza as colunas "atuais" em `funcionarios` na mesma transação. Detalhes em [06](06-linha-do-tempo.md).

#### C2. `funcionario_afastamentos` — [E][S][A][Anx]

| Coluna                           | Tipo         | Null | Notas                                                               |
| -------------------------------- | ------------ | ---- | ------------------------------------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                                                     |
| empresa_id                       | BIGINT       | N    | FK cascade                                                          |
| funcionario_id                   | BIGINT       | N    | FK cascade                                                          |
| tipo_afastamento_id              | BIGINT       | N    | FK→tipos_afastamento restrict                                       |
| data_inicio                      | DATE         | N    |                                                                     |
| data_fim_prevista                | DATE         | S    |                                                                     |
| data_fim_efetiva                 | DATE         | S    | null = em curso                                                     |
| dias                             | INTEGER      | S    | cache derivável                                                     |
| cid                              | VARCHAR(10)  | S    | **dado de saúde — LGPD art. 11** (`encrypted` + permissão dedicada) |
| observacao                       | TEXT         | S    |                                                                     |
| registrado_por_admin_user_id     | BIGINT       | S    | FK→admin_users nullOnDelete                                         |
| created_at/updated_at/deleted_at |              |      |                                                                     |

Índices: `funcionario_id`, `(funcionario_id, data_fim_efetiva)`, `(empresa_id, tipo_afastamento_id)`, `(empresa_id, data_inicio)`. CHECK `data_fim_efetiva IS NULL OR data_fim_efetiva >= data_inicio`. `atributosNaoAuditados()`: `['cid']`.

#### C3. `atestados` — [E][S][A][Anx] (atestado como entidade com workflow)

Atestado deixa de ser "só um anexo de afastamento" e vira **entidade com máquina de estados** (`pendente → em_analise → aprovado | rejeitado`): recebido por vários canais (`origem`), analisado pelo RH, e capaz de **abonar** horas/dias (`ocorrencias`, §C4) ou **virar afastamento** (>15 d → INSS, gera `funcionario_afastamentos` §C2). Modelo, papéis, workflow e fronteira Fase 1 × Fase 2 em [12](12-ausencias-faltas-atestados-afastamentos.md) e [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md).

| Coluna                           | Tipo         | Null | Notas                                                                                                  |
| -------------------------------- | ------------ | ---- | ------------------------------------------------------------------------------------------------------ |
| id                               | BIGSERIAL PK | N    |                                                                                                        |
| empresa_id                       | BIGINT       | N    | FK→empresas cascade                                                                                    |
| funcionario_id                   | BIGINT       | N    | FK→funcionarios cascade                                                                                |
| tipo                             | VARCHAR(20)  | S    | natureza (médico/odontológico/acompanhante) — domínio leve, candidato a enum `TipoAtestado` (evolução) |
| data_emissao                     | DATE         | N    | data do atestado                                                                                       |
| data_inicio                      | DATE         | S    | início do período coberto (default = `data_emissao`)                                                   |
| dias_abonados                    | INTEGER      | S    | dias inteiros abonados                                                                                 |
| minutos_abonados                 | INTEGER      | S    | minutos abonados (parte do dia — convenção §0, nunca horas-float)                                      |
| cid                              | VARCHAR(10)  | S    | **dado de saúde — LGPD art. 11** (`encrypted` + permissão `rh.atestados.ver_cid`)                      |
| anexo_id                         | BIGINT       | S    | FK→anexos nullOnDelete (imagem/PDF do atestado)                                                        |
| status                           | VARCHAR(16)  | N    | ENUM `StatusAtestado` + CHECK, default `pendente`                                                      |
| origem                           | VARCHAR(24)  | N    | ENUM `OrigemAtestado` + CHECK (canal de entrada)                                                       |
| afastamento_id                   | BIGINT       | S    | FK→funcionario_afastamentos nullOnDelete (quando o atestado gera afastamento)                          |
| observacao                       | TEXT         | S    |                                                                                                        |
| registrado_por_admin_user_id     | BIGINT       | S    | FK→admin_users nullOnDelete (quem lançou)                                                              |
| analisado_por_admin_user_id      | BIGINT       | S    | FK→admin_users nullOnDelete (quem analisou)                                                            |
| analisado_em                     | TIMESTAMP    | S    |                                                                                                        |
| motivo_rejeicao                  | TEXT         | S    |                                                                                                        |
| created_at/updated_at/deleted_at |              |      |                                                                                                        |

Índices: `(empresa_id, funcionario_id, data_emissao)`, `(empresa_id, status)`, `afastamento_id`, `anexo_id`. CHECK `dias_abonados IS NULL OR dias_abonados >= 0`; `minutos_abonados IS NULL OR minutos_abonados >= 0`. `atributosNaoAuditados()`: `['cid']` (mesmo rigor do `cid` de afastamento — §8 / [06 §5.3](06-linha-do-tempo.md)). **Fundação na Fase 1** (entidade + anexo + estados); workflow/análise/abono completos são Fase 2 — [12](12-ausencias-faltas-atestados-afastamentos.md).

#### C4. `ocorrencias` — [E][S][A] (faltas, atrasos e saídas antecipadas)

Ausência **pontual** (um dia / algumas horas), distinta do afastamento (período, §C2) e do atestado (documento, §C3). Classificação **justificada / injustificada / abonada** (derivada das flags); **origem manual** por gestor/RH na Fase 1 (sem ponto eletrônico até a Fase 5 — [09](09-roadmap-fases.md)). Detalhe em [12](12-ausencias-faltas-atestados-afastamentos.md).

| Coluna                           | Tipo         | Null | Notas                                                                     |
| -------------------------------- | ------------ | ---- | ------------------------------------------------------------------------- |
| id                               | BIGSERIAL PK | N    |                                                                           |
| empresa_id                       | BIGINT       | N    | FK→empresas cascade                                                       |
| funcionario_id                   | BIGINT       | N    | FK→funcionarios cascade                                                   |
| data                             | DATE         | N    | dia da ocorrência                                                         |
| tipo                             | VARCHAR(20)  | N    | ENUM `TipoOcorrencia` + CHECK (falta, atraso, saida_antecipada)           |
| minutos                          | INTEGER      | S    | duração (atraso/saída; falta de dia inteiro pode ser null) — convenção §0 |
| justificada                      | BOOLEAN      | N    | default false                                                             |
| abonada                          | BOOLEAN      | N    | default false (abono por atestado aprovado ou decisão do RH)              |
| atestado_id                      | BIGINT       | S    | FK→atestados nullOnDelete (quando abonada por atestado)                   |
| tipo_afastamento_id              | BIGINT       | S    | FK→tipos_afastamento nullOnDelete (classificação trabalhista)             |
| observacao                       | TEXT         | S    |                                                                           |
| registrado_por_admin_user_id     | BIGINT       | S    | FK→admin_users nullOnDelete                                               |
| created_at/updated_at/deleted_at |              |      |                                                                           |

Índices: `(empresa_id, funcionario_id, data)`, `(empresa_id, tipo)`, `atestado_id`. CHECK `minutos IS NULL OR minutos >= 0`. **Injustificada** = `NOT justificada AND NOT abonada` (derivada, sem coluna). As flags conversam com `tipos_afastamento` (`conta_como_falta`, `remunerado`) e com a apuração de frequência/DSR (Fase 3 — [07](07-jornada-horas-extras-folha.md)). **Fundação na Fase 1**; abono automático via atestado aprovado é Fase 2.

### Bloco D — Horas extras

#### D1. `horas_extras` — [E][A] (lançamento + cálculo + aprovação)

| Coluna                     | Tipo         | Null | Notas                                             |
| -------------------------- | ------------ | ---- | ------------------------------------------------- |
| id                         | BIGSERIAL PK | N    |                                                   |
| empresa_id                 | BIGINT       | N    | FK cascade                                        |
| funcionario_id             | BIGINT       | N    | FK cascade                                        |
| data                       | DATE         | N    | dia da HE                                         |
| minutos                    | INTEGER      | N    | duração (inteiro)                                 |
| tipo                       | VARCHAR(24)  | N    | ENUM `TipoHoraExtra` + CHECK                      |
| rubrica_id                 | BIGINT       | S    | FK→rubricas nullOnDelete (ponte p/ folha)         |
| justificativa              | TEXT         | S    |                                                   |
| status                     | VARCHAR(16)  | N    | ENUM `StatusHoraExtra` + CHECK, default `lancada` |
| percentual_aplicado_bps    | INTEGER      | S    | snapshot do fator (basis points)                  |
| valor_hora_base_centavos   | INTEGER      | S    | snapshot da base/hora                             |
| valor_calculado_centavos   | INTEGER      | S    | congelado na aprovação                            |
| snapshot_calculo           | JSONB        | S    | memória de cálculo imutável (ADR-0009)            |
| lancado_por_admin_user_id  | BIGINT       | N    | FK→admin_users (o gestor)                         |
| aprovado_por_admin_user_id | BIGINT       | S    | FK→admin_users                                    |
| aprovado_em                | TIMESTAMP    | S    |                                                   |
| motivo_rejeicao            | TEXT         | S    |                                                   |
| created_at/updated_at      |              |      | cancelamento = status                             |

Índices: `(empresa_id, funcionario_id, data)`, `(empresa_id, status)`, `lancado_por_admin_user_id`. CHECK `minutos > 0`. Fórmula, workflow e fronteira de folha em [07](07-jornada-horas-extras-folha.md).

### Bloco E — Vínculo ACL (organograma)

Resolvido por `funcionarios.admin_user_id` (FK nullable, `UNIQUE(empresa_id, admin_user_id)`) em B1 — FK mora no pacote, core intocado. `AdminUser` ganha relação inversa `funcionario(): HasOne` via método no model do pacote (sem migration no core). Mecânica completa (resolução "qual funcionário sou eu", subárvore recursiva, self-service) em [05](05-organograma-acl-hierarquica.md) e [ADR-RH-001](adrs/ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md).

### Referência de apoio à folha

#### `tabelas_legais` — REFERÊNCIA global por vigência (INSS/IRRF/salário-família)

Parâmetros nacionais que mudam por competência (faixas/alíquotas). Modelados como referência versionada por `vigencia_inicio`/`vigencia_fim` + `tipo` (inss/irrf/salario_familia) + payload JSONB com as faixas. **Fundação** de folha: alimenta cálculos futuros; não há apuração na Fase 1. Pode nascer como seed do pacote e ser atualizada por competência. Ver fronteira em [07](07-jornada-horas-extras-folha.md).

### Bloco F — Apoio à importação (opcional)

#### `importacoes` — [E][A] (log de importação de planilha — apoio)

Registro **opcional** de cada importação de planilha ([11](11-importacao-exportacao.md)): status, contadores e relatório de erros por linha, para a tela de resultado e a auditoria do que foi criado/atualizado. **Não** é pré-requisito da importação — é apoio operacional; pode entrar no incremento que entrega a importação (pós-Fase 1). Sem `deleted_at` (log).

| Coluna                     | Tipo         | Null | Notas                                                                                             |
| -------------------------- | ------------ | ---- | ------------------------------------------------------------------------------------------------- |
| id                         | BIGSERIAL PK | N    |                                                                                                   |
| empresa_id                 | BIGINT       | N    | FK→empresas cascade                                                                               |
| entidade                   | VARCHAR(40)  | N    | alvo (ex.: `funcionarios`)                                                                        |
| arquivo_nome               | VARCHAR(255) | S    | nome original do arquivo                                                                          |
| status                     | VARCHAR(16)  | N    | domínio leve + CHECK: `pendente`/`processando`/`concluida`/`falhou` (sem enum dedicado na Fase 1) |
| total_linhas               | INTEGER      | S    |                                                                                                   |
| criados                    | INTEGER      | S    |                                                                                                   |
| atualizados                | INTEGER      | S    |                                                                                                   |
| com_erro                   | INTEGER      | S    |                                                                                                   |
| relatorio_erros            | JSONB        | S    | erros por linha (linha → motivos)                                                                 |
| iniciado_por_admin_user_id | BIGINT       | S    | FK→admin_users nullOnDelete                                                                       |
| created_at/updated_at      |              |      | sem `deleted_at` (log)                                                                            |

Índices: `(empresa_id, entidade)`, `(empresa_id, status)`. Fluxo completo (assíncrono, fila, relatório de erros, round-trip) em [11](11-importacao-exportacao.md).

---

## 4. Enums a criar (`packages/extensao-rh/src/Enums/`)

Todos backed `string` (exceto `DiaSemana` = `int`), com `label()`, `options()`, `variant()/color()` quando viram badge, e métodos de lógica indicados. Cada um acompanha **CHECK constraint** na migration.

| Enum                  | Cases (valor)                                                                                                                                                        | Lógica                                                   |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| `StatusFuncionario`   | ativo, experiencia, afastado, ferias, desligado                                                                                                                      | `isAtivo()`, variant                                     |
| `Sexo`                | masculino, feminino, outro, nao_informado                                                                                                                            | label                                                    |
| `EstadoCivil`         | solteiro, casado, divorciado, viuvo, uniao_estavel, separado                                                                                                         | label                                                    |
| `Escolaridade`        | analfabeto, fundamental_incompleto, fundamental, medio_incompleto, medio, superior_incompleto, superior, pos, mestrado, doutorado                                    | `nivelOrdinal()`                                         |
| `RacaCor`             | branca, preta, parda, amarela, indigena, nao_informado                                                                                                               | label (eSocial)                                          |
| `TipoVinculo`         | clt, pj, estagio, temporario, autonomo, aprendiz, terceirizado                                                                                                       | `geraFgts()`, `temCarteira()`, `codCategEsocial(): ?int` |
| `RegimeTrabalho`      | mensalista, horista, comissionado, diarista                                                                                                                          | `baseCalculoHoraExtra()`                                 |
| `TipoContaBancaria`   | corrente, poupanca, salario, pagamento                                                                                                                               | label                                                    |
| `Titularidade`        | propria, terceiro                                                                                                                                                    | label                                                    |
| `TipoChavePix`        | cpf, cnpj, email, celular, aleatoria                                                                                                                                 | `validaFormato()`                                        |
| `TipoContato`         | email, telefone                                                                                                                                                      | label                                                    |
| `TipoTelefone`        | celular, fixo, comercial, recado                                                                                                                                     | label                                                    |
| `TipoEndereco`        | residencial, comercial, correspondencia                                                                                                                              | label                                                    |
| `GrauParentesco`      | conjuge, filho, enteado, pai, mae, companheiro, outro                                                                                                                | `eDependenteIrPadrao()`                                  |
| `TipoEscala`          | semanal, doze_trinta_seis, revezamento, parcial, personalizada                                                                                                       | label                                                    |
| `DiaSemana` (int)     | 1..7 (ISO, 1=segunda)                                                                                                                                                | `abreviacao()`, `nome()`                                 |
| `TipoEventoFuncional` | admissao, promocao, alteracao_salarial, reajuste, transferencia_departamento, transferencia_filial, mudanca_cargo, inicio_afastamento, fim_afastamento, desligamento | `afetaSalario()`, `afetaLotacao()`                       |
| `TipoHoraExtra`       | he_50, he_100, noturna, dsr                                                                                                                                          | `fatorPadraoBps(): int`, `adicionalNoturno(): bool`      |
| `StatusHoraExtra`     | rascunho, lancada, aprovada, rejeitada, paga, cancelada                                                                                                              | `isFinal()`, variant                                     |
| `NaturezaRubrica`     | provento, desconto, informativa                                                                                                                                      | label                                                    |

### 4.1 `codCateg` eSocial derivado do vínculo (sem coluna nova)

A **categoria do trabalhador** do eSocial (campo `codCateg` do grupo `infoContrato` do **S-2200**, Tabela 01) é **derivada do enum** `TipoVinculo` via `codCategEsocial(): ?int` — **não há coluna nova** (o código sai do vínculo já cadastrado). Mapeamento:

| `TipoVinculo`  | `codCategEsocial()` | Categoria eSocial (Tabela 01)                                            |
| -------------- | ------------------- | ------------------------------------------------------------------------ |
| `clt`          | `101`               | Empregado — Geral (CLT)                                                  |
| `aprendiz`     | `103`               | Empregado — Aprendiz                                                     |
| `temporario`   | `106`               | Trabalhador temporário (Lei 6.019/74)                                    |
| `autonomo`     | `701`               | Contribuinte individual — autônomo em geral                              |
| `estagio`      | `901`               | Estagiário                                                               |
| `pj`           | `null`              | Pessoa jurídica — não é categoria de trabalhador no S-2200 (contrato/NF) |
| `terceirizado` | `null`              | Vínculo é com a prestadora — sem `codCateg` no S-2200 deste empregador   |

> Os códigos seguem a **Tabela 01 do eSocial** e devem ser **reconfirmados contra o leiaute vigente** (S-1.x) na implementação da Fase 4. `null` significa "não se aplica a um S-2200 deste empregador". Cobertura eSocial completa em [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md) e na matriz S-2200 do [00 §4.1](00-prd.md).

### 4.2 Enums desta revisão (campos personalizados e ausências)

Backed `string`, com `label()`/`options()` e **CHECK** na coluna, como os demais. Uso detalhado em [10](10-campos-personalizados.md) (campos personalizados) e [12](12-ausencias-faltas-atestados-afastamentos.md) (atestados/ocorrências).

| Enum                     | Cases (valor)                                                             | Lógica                                                                                                                                                         |
| ------------------------ | ------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `TipoCampoPersonalizado` | texto, texto_longo, numero, decimal, data, booleano, select, multi_select | `componente(): string` (mapeia p/ `x-shared.*`), `regraValidacao(array $regras, ?array $opcoes): array`, `aceitaOpcoes(): bool`, `castValor(mixed): mixed`     |
| `StatusAtestado`         | pendente, em_analise, aprovado, rejeitado, estornado                      | `isFinal()`, `podeTransicionarPara()`, `variant()` (`estornar` reverte um `aprovado` → `estornado` — [12 §2.4](12-ausencias-faltas-atestados-afastamentos.md)) |
| `OrigemAtestado`         | portal_colaborador, gestor, rh, importacao                                | `label()` (canal de entrada — [12](12-ausencias-faltas-atestados-afastamentos.md))                                                                             |
| `TipoOcorrencia`         | falta, atraso, saida_antecipada                                           | `label()`, `usaMinutos(): bool` (atraso/saída usam `minutos`; falta de dia inteiro não)                                                                        |

---

## 5. Seeds padrão (catálogos) — por empresa, idempotentes

Catálogos tenant são semeados **na criação da empresa** por uma Action `ProvisionarCatalogosRh` (análoga a `AplicarMenuPadraoAction`), com `firstOrCreate` por `(empresa_id, codigo|nome)`. Enums não têm seed (vivem no código). Referências globais (`cargos`, `bancos`, `paises`…) já têm seed no core.

- **`tipos_documento`**: RG, CPF, CTPS, PIS/PASEP, Título de Eleitor, Reservista, CNH (`exige_validade`), Comprovante de Escolaridade, Comprovante de Residência (`exige_arquivo`), Carteira de Vacinação, ASO/Exame Admissional (`exige_validade`), Foto 3x4.
- **`tipos_afastamento`** (códigos eSocial tab. 18): Férias, Atestado ≤15d (`exige_atestado`), Auxílio-doença INSS >15d (`suspende_contrato`), Acidente de trabalho, Licença-maternidade (`remunerado`), Licença-paternidade, Licença não remunerada (`!remunerado`,`suspende_contrato`), Falta justificada, Falta injustificada (`conta_como_falta`), Suspensão disciplinar, Serviço militar, Gala (núpcias), Nojo (luto), Doação de sangue.
- **`funcoes`**: Líder, Preposto, Supervisor, Coordenador, Encarregado, Fiscal, Membro CIPA, Brigadista, Procurador, Responsável Técnico.
- **`departamentos`** (hierárquico): Administrativo, Financeiro (→ Contas a Pagar, Contas a Receber), RH, Comercial/Vendas, Operações, TI, Logística, Atendimento, Diretoria.
- **`escalas`** (+ `escala_dias`): "44h Seg–Sex+Sáb", "40h Seg–Sex", "12x36 Diurno", "12x36 Noturno", "Estágio 6h", "30h Seg–Sex".
- **`rubricas`**: Salário Base (provento, incide tudo), Hora Extra 50% (provento, `referencia_he_tipo=he_50`), Hora Extra 100%, Adicional Noturno, DSR sobre HE, INSS (desconto), IRRF (desconto), FGTS (informativa), Vale-Transporte (desconto), Salário-Família (provento).
- **`tabelas_legais`** (referência): faixas INSS e IRRF da competência vigente + salário-família (seed inicial; atualizável por competência).

---

## 6. Evolução sem quebrar (migrations aditivas)

Greenfield: a Fase 1 cria as tabelas já completas. O padrão para evoluções futuras (mesmo das tabelas do RH) segue o core (`add_<coluna>_to_<tabela>`):

1. Colunas novas sempre `NULL`/com `default` → migration aditiva pura.
2. Enum novo = coluna `VARCHAR` + CHECK adicionado **após** eventual backfill.
3. FK `admin_user_id`/`gestor_id` entram `nullable` + índice (único parcial onde aplicável). Não tocam o core.
4. Catálogos novos e tabelas-filhas são puramente aditivos.
5. Empacotamento (ADR-0015): migrations em `packages/extensao-rh/database/migrations` (via `loadMigrationsFrom`); permissões/menu mesclados em runtime no `boot()`; **aditivo, nunca edita o core**.

> **Itens desta revisão (todos aditivos).** As tabelas novas — `campos_personalizados` (§A11) + coluna `funcionarios.dados_personalizados`, `atestados` (§C3), `ocorrencias` (§C4) e `importacoes` (§F, opcional) — e os enums de §4.2 entram pelas regras acima (colunas/tabelas novas `NULL`/com default; FKs nullable; CHECK após backfill). **Faseamento:** campos personalizados é **fundação reutilizável** aplicada ao funcionário já na Fase 1 (candidata a promoção ao core — [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)); atestado/ocorrência entram como **fundação na Fase 1** (entidade + estados + anexo), com workflow/abono completos nas fases seguintes ([12](12-ausencias-faltas-atestados-afastamentos.md), [09](09-roadmap-fases.md)); a importação é **pós-Fase 1** ([11](11-importacao-exportacao.md)).

---

## 7. Performance e integridade

- Toda FK indexada. Compostos quentes já previstos: `(empresa_id, gestor_id)` (recursão do organograma), `(funcionario_id, data)` em HE/afastamentos/eventos, `(empresa_id, status)` em funcionários, `(funcionario_id, vigencia_fim)`/`(funcionario_id, fim)` para vínculos vigentes via `IS NULL`, `data_validade` (documentos a vencer).
- `funcionario_eventos` é append-only e cresce: índice `(funcionario_id, data_evento)` cobre a timeline; particionamento só se o volume exigir (não na Fase 1).
- Colunas "atuais" desnormalizadas em `funcionarios` (cargo/departamento/filial/salário/cargo_nivel) evitam varrer o histórico nas listagens; o evento mantém a verdade temporal.
- Integridade: catálogos com `restrictOnDelete` (não apagar em uso); filhas do funcionário com `cascadeOnDelete` físico (só em force-delete; soft-delete não cascateia). Auto-FKs (`gestor_id`, `departamento_pai_id`) com `nullOnDelete` + CHECK anti-auto-referência; ciclos profundos validados na Action (ver [05](05-organograma-acl-hierarquica.md)).
- `empresa_id` redundante em filhas/pivôs é intencional: uniformiza o global scope `BelongsToEmpresa` e o unique-por-tenant (auto-fill garante consistência).
- Unique parciais `WHERE deleted_at IS NULL` em toda tabela soft-deletada (CPF/matrícula na lixeira não bloqueia novo cadastro).

---

## 8. LGPD (dados sensíveis)

### 8.1 Matriz de dados sensíveis (fonte única)

Esta é a **lista canônica** de tudo que é sensível no módulo: o que é, onde mora, qual a categoria LGPD, qual permissão dedicada o protege e como é tratado. Os demais documentos **referenciam esta matriz** em vez de redefini-la: [03 §2.1/§8.3](03-cadastro-pessoa-documentos.md), [06 §5.3](06-linha-do-tempo.md), [10 §6](10-campos-personalizados.md) e [12 §2.6](12-ausencias-faltas-atestados-afastamentos.md). Categorias: **PII** (art. 5º — dado pessoal comum) · **Saúde** (art. 11 — categoria especial de dado pessoal).

| Dado sensível                                                                                        | Entidade · coluna(s)                                                 | Categoria                          | Permissão dedicada                                                            | Tratamento                                                                                                                                                                 |
| ---------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- | ---------------------------------- | ----------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CPF, RG, PIS/PASEP, nome do pai/mãe                                                                  | `funcionarios.{cpf,rg,pis_pasep,nome_mae,nome_pai}`                  | PII                                | — (visível sob `rh.funcionarios.*`)                                           | `atributosNaoAuditados()` (fora do diff de auditoria)                                                                                                                      |
| CPF de dependente                                                                                    | `funcionario_dependentes.cpf`                                        | PII                                | —                                                                             | `atributosNaoAuditados()`                                                                                                                                                  |
| Número de documento                                                                                  | `funcionario_documentos.numero`                                      | PII                                | —                                                                             | `atributosNaoAuditados()`                                                                                                                                                  |
| Dados bancários (conta, agência, dígito, chave PIX)                                                  | `funcionario_dados_bancarios.{conta,conta_digito,agencia,pix_chave}` | PII financeira                     | —                                                                             | `atributosNaoAuditados()` + `encrypted` recomendado em `pix_chave`/`conta`                                                                                                 |
| **Grupo PCD/Deficiência** (`def_*`, `reabilitado_readaptado`, `beneficiario_cota`, `observacao_pcd`) | `funcionarios` (colunas PCD — §B1)                                   | **Saúde (art. 11)**                | **`rh.funcionarios.ver_dados_sensiveis`**                                     | `atributosNaoAuditados()` + UI **oculta a seção** sem a permissão; selo "eSocial" — [03 §2.1](03-cadastro-pessoa-documentos.md)                                            |
| **CID do afastamento**                                                                               | `funcionario_afastamentos.cid`                                       | **Saúde (art. 11)**                | **`rh.afastamentos.ver_cid`**                                                 | `encrypted` + `atributosNaoAuditados()` + **mascarado** sem a permissão — [06 §5.3](06-linha-do-tempo.md)                                                                  |
| **CID do atestado**                                                                                  | `atestados.cid`                                                      | **Saúde (art. 11)**                | **`rh.atestados.ver_cid`**                                                    | `encrypted` + `atributosNaoAuditados()` + **mascarado** sem a permissão — [12 §2.6](12-ausencias-faltas-atestados-afastamentos.md)                                         |
| Campos personalizados marcados `sensivel=true`                                                       | `funcionarios.dados_personalizados` (chaves marcadas — §A11)         | conforme o dado (pode ser saúde)   | herda da entidade (`rh.funcionarios.editar`); flag `sensivel` liga a proteção | máscara + fora de auditoria **dinâmico** (trait `TemCamposPersonalizados`, não lista estática) — [10 §6](10-campos-personalizados.md)                                      |
| Foto                                                                                                 | `funcionarios.foto_caminho`                                          | PII (imagem)                       | —                                                                             | disco **privado** `rh_privado` + URL assinada (nunca `public`)                                                                                                             |
| Arquivos de documento (binário)                                                                      | `funcionario_documentos.anexo_id` → `anexos`                         | PII (pode conter saúde — ex.: ASO) | download por Policy                                                           | disco **privado** + URL assinada + layout não-adivinhável — [03 §8.3](03-cadastro-pessoa-documentos.md) / [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md) |
| Biometria (digital/facial)                                                                           | _(futuro — ponto, Fase 6)_                                           | **Saúde/biométrico (art. 11)**     | _(a definir)_                                                                 | mesmo rigor do `cid`; registrado em [09 §7](09-roadmap-fases.md) — fora da Fase 1                                                                                          |

> **Regra única:** "ver o registro" ≠ "ver o dado de saúde". As permissões sensíveis (`ver_cid`, `ver_dados_sensiveis`) são **separadas do CRUD** e do escopo (tenant + organograma); super-admin faz bypass. Dado de saúde nunca em logs (reforça a regra do core).

### 8.2 Princípios gerais

- **PII fora de auditoria** — `atributosNaoAuditados()` por model (acima). Reforça a regra do core "dados sensíveis nunca em logs".
- **Retenção trabalhista** — guarda legal longa (eSocial/FGTS): soft-delete + append-only de eventos atende; **não expurgar** `funcionario_eventos`.
- **Anonimização** — alinhar ao fluxo LGPD do core (`anonimizado_em` no `AdminUser`; plano `docs/superpowers/plans/2026-06-05-lgpd.md`). Funcionário desligado pode ser anonimizado mascarando PII e mantendo o esqueleto para obrigações legais; chamar `disableLogging()` antes do save.

---

## 9. Resumo (25 tabelas novas — 23 obrigatórias + 2 opcionais — além das reaproveitadas)

**Catálogos tenant (12):** `departamentos`, `funcoes`, `funcionario_funcao`, `tipos_documento`, `tipos_afastamento`, `escalas`, `escala_dias`, `escala_funcionario`, `rubricas`, `fator_horas_extras`, `campos_personalizados` (§A11, meta), `centros_custo` (§A12, **opcional/aditivo**).
**Funcionário e filhas (6):** `funcionarios` (inclui o grupo **PCD** — colunas, não tabela —, o vínculo ACL `admin_user_id` e a coluna `dados_personalizados` JSONB), `funcionario_contatos`, `funcionario_enderecos`, `funcionario_dados_bancarios`, `funcionario_dependentes`, `funcionario_documentos`.
**Histórico e ausências (4):** `funcionario_eventos`, `funcionario_afastamentos`, `atestados` (§C3), `ocorrencias` (§C4).
**Operacional (1):** `horas_extras`.
**Apoio à importação (1, opcional):** `importacoes` (§F).
**Referência de folha (1):** `tabelas_legais`.
**Reaproveitadas do core (sem nova tabela):** `anexos`, `cargos`, `bancos`, `paises`, `estados`, `municipios`, `tipos_logradouro`, `empresas`, `filiais`, `admin_users`.

> O grupo **PCD/Deficiência** e a derivação **`codCateg`** (§4.1) seguem **cadastrais e baratos** — colunas nullable em `funcionarios` + método no enum, **sem tabela nova**. Esta revisão acrescenta **4 tabelas** (`campos_personalizados`, `atestados`, `ocorrencias` e a opcional `importacoes`) + a coluna `dados_personalizados` + o catálogo **opcional** `centros_custo` (§A12, com a FK aditiva `funcionarios.centro_custo_id`), todas **aditivas** (§6) e detalhadas em [10](10-campos-personalizados.md)/[11](11-importacao-exportacao.md)/[12](12-ausencias-faltas-atestados-afastamentos.md) (centro de custo em [04 §7.1](04-catalogos-configuraveis.md)). As **permissões canônicas** estão em §10.

---

## 10. Permissões canônicas (`rh.*`) — fonte de verdade

Esta é a lista **canônica** das permissões do módulo. README, [02](02-fase-1-blueprint.md), specs [03](03-cadastro-pessoa-documentos.md)–[09](09-roadmap-fases.md) e os ADRs referenciam **estes** slugs; **divergência de vocabulário corrige-se aqui primeiro**. O que `php artisan access:sync` publica na tabela `permissions` é exatamente esta lista (mesclada em `config('access.modules')['negocio']` pelo `RhServiceProvider` — [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).

**Convenções de slug** (confirmadas no gerador real `App\Support\Generator\EspecificacaoModulo::permissaoBase()` = `"{slug}.{snakePlural}"`):

- Slug = **`rh.<recurso_snake_plural>.<acao>`** — prefixo `rh.` obrigatório (anti-colisão); recurso em **`snake_case` plural** (= nome da tabela); ação em `snake_case`. (Atenção: a chave de **menu** usa `rh-<recurso>` e a **URL** usa kebab-case — só a **permissão** usa `snake_case`; não confundir com o slug kebab de [04](04-catalogos-configuraveis.md), que se alinha a esta tabela.)
- **CRUD padrão** (gerado por `make:modulo` para recurso tenant): `listar`, `criar`, `editar`, `deletar`, `restaurar`, `excluir_permanente` (as três últimas = fluxo `ComLixeira`).
- **Especiais** (escritas à mão em `config/rh.php`, fora das âncoras CRUD): coluna "Especiais" abaixo.
- **Verbo semântico de UI** (`registrar`/`encerrar` em afastamentos; `ver` individual de funcionário) é **rótulo** mapeado a um slug CRUD registrado — o slug que `access:sync` publica é o desta tabela.

| Recurso                 | Tipo                             | Ações registradas (`rh.<recurso>.…`)                                                                                                                                                                                                                                        |
| ----------------------- | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `departamentos`         | catálogo tenant                  | `listar` · `criar` · `editar` · `deletar` · `restaurar` · `excluir_permanente`                                                                                                                                                                                              |
| `funcoes`               | catálogo tenant                  | CRUD + lixeira (idem)                                                                                                                                                                                                                                                       |
| `tipos_documento`       | catálogo tenant                  | CRUD + lixeira                                                                                                                                                                                                                                                              |
| `tipos_afastamento`     | catálogo tenant                  | CRUD + lixeira                                                                                                                                                                                                                                                              |
| `escalas`               | catálogo tenant                  | CRUD + lixeira                                                                                                                                                                                                                                                              |
| `rubricas`              | catálogo tenant                  | CRUD + lixeira                                                                                                                                                                                                                                                              |
| `fator_horas_extras`    | catálogo tenant (fino, §A10)     | CRUD + lixeira                                                                                                                                                                                                                                                              |
| `centros_custo`         | catálogo tenant (opcional, §A12) | CRUD + lixeira _(criado quando o cliente adotar centro de custo — D1, aditivo)_                                                                                                                                                                                             |
| `funcionarios`          | agregado-raiz                    | CRUD + lixeira **+ `ver_todos`** (desliga o eixo organograma — [05](05-organograma-acl-hierarquica.md)) **+ `ver_dados_sensiveis`** (grupo PCD — dado de saúde, LGPD art. 11, §8) **+ `importar`** · **`exportar`** (planilha — [11](11-importacao-exportacao.md))          |
| `funcoes_funcionario`   | pivot (vigência)                 | `atribuir` · `encerrar`                                                                                                                                                                                                                                                     |
| `escala_funcionario`    | pivot (vigência)                 | `atribuir` · `encerrar`                                                                                                                                                                                                                                                     |
| `organograma`           | tela                             | `ver`                                                                                                                                                                                                                                                                       |
| `self`                  | portal do colaborador            | `ver`                                                                                                                                                                                                                                                                       |
| `eventos`               | append-only (§C1)                | `listar` · `registrar` _(sem editar/excluir — append-only; correção = evento de estorno)_                                                                                                                                                                                   |
| `afastamentos`          | soft-deletable                   | `listar` · `criar` · `editar` · `deletar` · `restaurar` · `excluir_permanente` **+ `ver_cid`** (dado de saúde) _(UI: `criar`="registrar", `editar`="encerrar")_                                                                                                             |
| `horas_extras`          | máquina de estados (sem lixeira) | `listar` · `lancar` · `aprovar` · `estornar` · `marcar_paga` · `ver_valores`                                                                                                                                                                                                |
| `tabelas_legais`        | referência (leitura)             | `listar` · `ver`                                                                                                                                                                                                                                                            |
| `documentos`            | opcional                         | `listar` · `criar` · `editar` · `deletar` _(default: gerido dentro do form do funcionário, sob `rh.funcionarios.*`)_                                                                                                                                                        |
| `campos_personalizados` | meta-catálogo (§A11)             | `listar` · `criar` · `editar` · `deletar` · `restaurar` · `excluir_permanente` _(gestão das **definições**; editar os **valores** segue a permissão da entidade, ex. `rh.funcionarios.editar`)_                                                                             |
| `atestados`             | workflow (soft-deletable, §C3)   | `listar` · `criar` · `editar` · `analisar` · `aprovar` · `rejeitar` · `estornar` · `deletar` · `restaurar` · `excluir_permanente` **+ `ver_cid`** (dado de saúde) _(UI: colaborador "enviar"/gestor "registrar" = `criar`; `estornar` reverte um `aprovado` → `estornado`)_ |
| `ocorrencias`           | soft-deletable (§C4)             | `listar` · `criar` · `editar` · `deletar` · `restaurar` · `excluir_permanente` _(+ `abonar` opcional — abono separado do `editar`)_                                                                                                                                         |

**Notas de reconciliação (resolvem divergências entre docs):**

- **`horas_extras`** não tem lixeira (tabela **sem `deleted_at`**, §D1) — o ciclo é por `status`. `lancar` cobre criar/editar enquanto `rascunho`/`lancada` **e** cancelar nesse estágio; `aprovar` cobre **aprovar e rejeitar**; `ver_valores` separa "ver que houve HE" de "ver quanto custou". Máquina de estados completa em [07 §5](07-jornada-horas-extras-folha.md). _(Substitui o vocabulário CRUD `criar/editar/rejeitar/cancelar` que aparecia em rascunhos do [02](02-fase-1-blueprint.md).)_
- **`eventos`** é append-only: só `listar`/`registrar` (`registrar` ≡ "criar" semanticamente; **sem** editar/deletar/restaurar). Granularidade por ação (`registrar_promocao`, …) é evolução opcional ([06 §7](06-linha-do-tempo.md)).
- **`afastamentos`** é soft-deletable: tem o conjunto CRUD + lixeira **+** `ver_cid`. Os verbos `registrar`/`encerrar` da UI ([06 §7](06-linha-do-tempo.md)) **mapeiam** para `criar`/`editar` — não são slugs registrados à parte.
- **`funcionarios.ver`** (abrir um cadastro) citado em [05 §11](05-organograma-acl-hierarquica.md) é **subsumido por `listar`** no conjunto gerado; um split `ver` é opcional (entra como especial, se adotado). O eixo RBAC é o mesmo: **o verbo é RBAC; quais linhas é tenant + organograma**.
- Permissões sensíveis (`ver_cid`, `ver_dados_sensiveis`) são **separadas do CRUD** — ver o registro ≠ ver o dado de saúde (LGPD art. 11, §8). Convivem com o RBAC de dois níveis e a ACL hierárquica ([05](05-organograma-acl-hierarquica.md)); super-admin faz bypass.
- **`rh.cargos.*` não é permissão da Fase 1.** Cargo é **referência global** (CBO), não catálogo do RH (§0/[ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)). O slug `rh.cargos.*` aparece em [04](04-catalogos-configuraveis.md)/[ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md) **apenas** como **evolução futura** (promover a catálogo tenant `cargos_empresa`); se/quando adotado, entra aqui como CRUD + lixeira.
- **`atestados` é workflow + lixeira:** além do CRUD + lixeira, tem os verbos de análise (`analisar`/`aprovar`/`rejeitar`/`estornar` — este reverte um `aprovado` → `estornado`) e o sensível **`ver_cid`** (dado de saúde, §8) — mesmo rigor do `cid` de afastamento ([06 §5.3](06-linha-do-tempo.md)). Os rótulos de UI "enviar" (colaborador, portal) e "registrar" (gestor/RH) mapeiam para `criar`, discriminados pela coluna `origem` (`OrigemAtestado`). Máquina de estados em [12](12-ausencias-faltas-atestados-afastamentos.md) / [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md).
- **`ocorrencias`** (faltas/atrasos) tem CRUD + lixeira; o **abono** (marcar `abonada`/`justificada`, que afeta frequência/folha) pode ser o `editar` ou um verbo especial `rh.ocorrencias.abonar` se o cliente exigir separar quem lança de quem abona. O slug alternativo `rh.faltas.*` é equivalente — adota-se **`rh.ocorrencias.*`** (= nome da tabela).
- **`campos_personalizados`** governa as **definições** (CRUD + lixeira). Editar os **valores** (`funcionarios.dados_personalizados`) **não** tem permissão própria — segue a permissão da entidade hospedeira (`rh.funcionarios.editar`); campos com `sensivel=true` herdam o mascaramento/ocultação do grupo PCD ([10](10-campos-personalizados.md)).
- **`rh.funcionarios.{importar,exportar}`** são **especiais** (escritas à mão em `config/rh.php`, fora das âncoras CRUD) — [11](11-importacao-exportacao.md). A exportação carrega PII (auditar a operação + mascaramento); o log opcional `importacoes` (§F) é consultado dentro do fluxo de `importar` (sem permissão própria na Fase 1).

### 10.1 Matriz de atribuição por perfil

A tabela acima é a lista de permissões **por recurso**; esta é a contrapartida **por perfil** — _quem recebe o quê_. As permissões são atribuídas a **papéis** (RBAC de dois níveis: papéis globais spatie + papéis por empresa `admin_user_empresa_role`); o que segue é a **atribuição-base recomendada**, e o admin pode ajustá-la por empresa. Reflete as personas de [00 §3](00-prd.md) e as decisões **D-GESTOR**/**D-COLAB** confirmadas ([00 §8](00-prd.md) · relatório em [14](14-auditoria-e-validacao.md)). Legenda: ✅ recebe · 🟡 escopado/condicional · — não recebe · 🔓 bypass.

| Recurso / ação                                                                                                                                                | RH/DP (P1)           | Gestor (P2)                                                   | Colaborador (P3)                         | Super-admin (P4) |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- | ------------------------------------------------------------- | ---------------------------------------- | ---------------- |
| Catálogos (`departamentos`, `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`, `rubricas`, `fator_horas_extras`, `centros_custo`) — CRUD + lixeira | ✅                   | — (só consome nos selects)                                    | —                                        | 🔓               |
| `funcionarios` — CRUD + lixeira                                                                                                                               | ✅                   | 🟡 só `listar`/`ver` da **subárvore** (não edita)             | — (acessa via `self`)                    | 🔓               |
| `funcionarios.ver_todos` (desliga o eixo organograma)                                                                                                         | ✅                   | —                                                             | —                                        | 🔓               |
| `funcionarios.ver_dados_sensiveis` (grupo PCD — saúde)                                                                                                        | 🟡 conforme política | —                                                             | —                                        | 🔓               |
| `funcionarios.{importar,exportar}`                                                                                                                            | ✅                   | —                                                             | —                                        | 🔓               |
| `documentos` do funcionário                                                                                                                                   | ✅                   | 🟡 leitura da equipe                                          | 🟡 os próprios (`self`)                  | 🔓               |
| `eventos` — `listar`/`registrar`                                                                                                                              | ✅                   | 🟡 `listar` da subárvore                                      | 🟡 `listar` os próprios                  | 🔓               |
| `afastamentos` — CRUD + lixeira                                                                                                                               | ✅                   | 🟡 `listar` da equipe                                         | 🟡 os próprios                           | 🔓               |
| `afastamentos.ver_cid` / `atestados.ver_cid` (saúde)                                                                                                          | 🟡 conforme política | —                                                             | —                                        | 🔓               |
| `horas_extras` — `listar`/`lancar`/`aprovar`/`estornar`/`marcar_paga`/`ver_valores`                                                                           | ✅ todos             | 🟡 `listar`+`lancar`+**`aprovar`**+`ver_valores` da subárvore | 🟡 ver as próprias                       | 🔓               |
| `organograma.ver`                                                                                                                                             | ✅ (empresa)         | ✅ (subárvore)                                                | 🟡 própria posição                       | 🔓               |
| `self.ver` + auto-edição de **contato/endereço/bancário**                                                                                                     | — (opera como RH)    | (no próprio registro, se for funcionário)                     | ✅ edita; nunca cargo/salário/status     | 🔓               |
| `tabelas_legais` — `listar`/`ver`                                                                                                                             | ✅                   | —                                                             | —                                        | 🔓               |
| `campos_personalizados` (definições) — CRUD + lixeira                                                                                                         | ✅                   | —                                                             | —                                        | 🔓               |
| `atestados` (Fase 2) — workflow + `ver_cid`                                                                                                                   | ✅ todos             | 🟡 `criar`/`listar`/análise conforme política                 | 🟡 `criar` (enviar)/`listar` os próprios | 🔓               |
| `ocorrencias` (Fase 2) — CRUD + lixeira (+`abonar`)                                                                                                           | ✅                   | 🟡 `listar` da equipe                                         | 🟡 ver as próprias                       | 🔓               |

- **Escopo do gestor (🟡):** tudo o que o gestor vê é confinado pela **ACL hierárquica** à sua subárvore ([05](05-organograma-acl-hierarquica.md)); ele **não** recebe `ver_todos` nem, por padrão, as permissões de saúde. **Aprova HE** conforme a política/segregação de funções ([07 §5.3/§5.4](07-jornada-horas-extras-folha.md)) — decisão **D-GESTOR**.
- **Colaborador (🟡):** acessa **apenas o próprio** registro (`self.ver`, resolvido por `admin_user_id`); a **edição parcial** (contato, endereço, **dados bancários**) é a mecânica do modo `proprio` ([03 §11.1](03-cadastro-pessoa-documentos.md)), não um verbo separado — decisão **D-COLAB**. A maioria dos funcionários nasce **sem** `admin_user_id` (sem login); o acesso é provisionado pelo RH ([03 §11.2](03-cadastro-pessoa-documentos.md) · [05 §9.1](05-organograma-acl-hierarquica.md)).
- **Super-admin (🔓):** bypass global pela precedência do `AccessResolver` (super-admin > deny > grant > role). "Admin do módulo" **não** é super-admin: recebe o conjunto `rh.*` **no escopo da empresa ativa**.
- **Dados de saúde** (`ver_cid`, `ver_dados_sensiveis`): concedidos **pontualmente** (não pertencem ao pacote padrão de nenhum perfil sem decisão explícita) — LGPD art. 11 (§8.1).
- Esta é a **base recomendada**; a atribuição efetiva é configurável por empresa (RBAC nível 2). Mudanças de política aqui devem refletir-se em [00 §3](00-prd.md).
