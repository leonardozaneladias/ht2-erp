# Enums — Roadmap e Índice

> **Fonte de verdade** para todos os enums PHP backed do projeto. Mantido conforme novas tabelas são migradas e novas colunas com valores finitos são introduzidas.
>
> Padrão obrigatório por [ADR-0010](adrs/ADR-0010-enums-php-backed.md): cada campo com valores finitos tem enum PHP + cast no model + CHECK constraint no DB.

## Como usar este documento

- **Ao criar migration** que introduza coluna com valores finitos: confirme que existe enum correspondente nesta lista. Se "aguardando-migration", promova o enum declarando cast no novo model e adicionando CHECK na migration.
- **Ao criar enum novo**: registre aqui com caminho, tabela.coluna, estado e SPEC de origem.
- **Ao revisar PR**: rode `rg "where\('(status|categoria|tipo|papel|origem|metodo|vinculo)', ?'[a-z]" app/` e confirme que o resultado está vazio — strings literais são proibidas.

## Estados

- **✅ Implementado** — enum existe, cast declarado no model, CHECK constraint no DB.
- **⚠️ Parcial** — enum existe; cast ou CHECK ainda pendente.
- **⏳ Aguardando migration** — enum criado preventivamente; tabela correspondente ainda não migrada. Ao criar a migration, promover para ✅.

## Índice

### Adesão (`app/Enums/Adesao/`)

| Enum                 | Tabela.Coluna                                   | Estado                                                            | SPEC de origem       |
| -------------------- | ----------------------------------------------- | ----------------------------------------------------------------- | -------------------- |
| `OrigemAdesao`       | `adesoes.origem_adesao`                         | ✅ Implementado                                                   | SPEC-010, SPEC-F-003 |
| `StatusAdesao`       | `adesoes.status`                                | ✅ Implementado                                                   | SPEC-F-003           |
| `TipoSolicitante`    | `adesoes.tipo_solicitante` (futuro) / claim JWT | ⚠️ Parcial (usado em DraftTokenClaims, sem coluna dedicada ainda) | SPEC-F-002, SPEC-010 |
| `VinculoResponsavel` | `adesoes.responsavel_cadastro_vinculo`          | ✅ Implementado                                                   | SPEC-F-002           |

### Contrato (`app/Enums/Contrato/`)

| Enum                | Tabela.Coluna         | Estado          | SPEC de origem |
| ------------------- | --------------------- | --------------- | -------------- |
| `StatusContrato`    | `contratos.status`    | ✅ Implementado | SPEC-F-001     |
| `CategoriaContrato` | `contratos.categoria` | ✅ Implementado | SPEC-F-001     |

### Turma (`app/Enums/Turma/`)

| Enum          | Tabela.Coluna   | Estado          | SPEC de origem |
| ------------- | --------------- | --------------- | -------------- |
| `StatusTurma` | `turmas.status` | ✅ Implementado | SPEC-F-001     |

### Instituição (`app/Enums/Instituicao/`)

| Enum              | Tabela.Coluna       | Estado          | SPEC de origem                 |
| ----------------- | ------------------- | --------------- | ------------------------------ |
| `ModalidadeCurso` | `cursos.modalidade` | ✅ Implementado | `docs/data/data-model.md` §B.3 |

### Pacote (`app/Enums/Pacotes/`)

> Namespace plural `Pacotes` por decisão histórica (primeiro enum do contexto). Novos enums desta família seguem o mesmo namespace para consistência.

| Enum              | Tabela.Coluna              | Estado          | SPEC de origem       |
| ----------------- | -------------------------- | --------------- | -------------------- |
| `StatusPacote`    | `pacotes.status`           | ✅ Implementado | SPEC-F-001           |
| `CategoriaPacote` | `pacotes.categoria`        | ✅ Implementado | SPEC-010, SPEC-F-001 |
| `TipoProgramacao` | `pacote_programacoes.tipo` | ✅ Implementado | SPEC-F-004           |

### Pagamento (`app/Enums/Pagamento/`)

| Enum                    | Tabela.Coluna                                                                                                           | Estado          | SPEC de origem                              |
| ----------------------- | ----------------------------------------------------------------------------------------------------------------------- | --------------- | ------------------------------------------- |
| `MetodoPagamento`       | `condicoes_pagamento.metodos_permitidos_json` (JSONB array); valida via método `CondicaoPagamento::metodosPermitidos()` | ✅ Implementado | SPEC-F-005                                  |
| `StatusParcela`         | `parcelas.status`                                                                                                       | ✅ Implementado | SPEC-F-006, CLAUDE.md §7.4, data-model §C.5 |
| `TipoCondicaoPagamento` | `condicoes_pagamento.tipo`                                                                                              | ✅ Implementado | SPEC-011                                    |

### Termo (`app/Enums/Termo/`)

| Enum        | Tabela.Coluna | Estado                  | SPEC de origem                                                        |
| ----------- | ------------- | ----------------------- | --------------------------------------------------------------------- |
| `TipoTermo` | `termos.tipo` | ⏳ Aguardando migration | SPEC-F-007 (campo ainda não formalizado — cases iniciais podem mudar) |

### Evento (`app/Enums/Evento/`)

| Enum           | Tabela.Coluna    | Estado                  | SPEC de origem                                                             |
| -------------- | ---------------- | ----------------------- | -------------------------------------------------------------------------- |
| `StatusEvento` | `eventos.status` | ⏳ Aguardando migration | SPEC-F-001 (ref. Evento), `docs/data/data-model.md` §B.5 (CHECK normativo) |

## Convenções

1. **Namespace**: `App\Enums\{Contexto}\{Nome}`. Contexto em singular (`Contrato`, `Turma`) exceto `Pacotes` e `Pagamento` por razões históricas.
2. **Case names**: TitleCase (`PendentePagamento`, não `PENDENTE_PAGAMENTO`).
3. **Case values**: snake_case (`pendente_pagamento`).
4. **Método obrigatório**: `label(): string` retornando PT-BR.
5. **Métodos comportamentais** (`isAtivo()`, `permiteTransicao(...)`): adicionar só quando houver uso concreto. **YAGNI**.
6. **Docblock**: obrigatório para enums "aguardando-migration" ou cujo case-set vem de SPEC específica; opcional para enums triviais.
7. **Teste unitário**: obrigatório. Padrão em `tests/Unit/Enums/Adesao/OrigemAdesaoTest.php` — cobrir count, values, labels não vazios, `from()`.

## Quando promover "⏳ Aguardando migration" para "✅ Implementado"

Ao criar a migration da tabela correspondente:

1. Adicione CHECK constraint com os valores exatos do enum:
    ```php
    DB::statement("ALTER TABLE {tabela} ADD CONSTRAINT {tabela}_{coluna}_check CHECK ({coluna} IN ('val1','val2'))");
    ```
2. No model, declare cast:
    ```php
    protected function casts(): array {
        return ['{coluna}' => App\Enums\{Contexto}\{Nome}::class, ...];
    }
    ```
3. Atualize o estado deste arquivo de ⏳ para ✅.
4. Se o enum ainda era fallback (caso de `TipoTermo`), revisite os cases — a SPEC agora deve estar consolidada.

## Links

- [ADR-0010 — Enums PHP backed](adrs/ADR-0010-enums-php-backed.md) — decisão arquitetural raiz
- [ADR-0008 — Verbos e state machine](adrs/ADR-0008-verbos-state-machine.md) — complementar para transições
- [`docs/features/foundation/SPEC-F-001-contrato-e-turma.md`](../features/foundation/SPEC-F-001-contrato-e-turma.md) §5 — máquinas de estado dos principais enums de status
- [`docs/data/data-model.md`](../data/data-model.md) — tabelas normativas e CHECK constraints
