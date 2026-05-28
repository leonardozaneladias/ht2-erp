---
title: SPEC-F-001 — Contrato e Turma
version: 0.3.0
date: 2026-04-23
status: draft
feature_id: SPEC-F-001
fase: foundation
story_points: 5
depends_on: []
unlocks: [SPEC-F-002, SPEC-F-004, SPEC-F-007, SPEC-010, SPEC-011]
---

# SPEC-F-001 — Contrato e Turma

> **Fundacional.** Modela o Contrato como entidade comercial central, alinhada ao PRD v3.1.0 §4 (recuperação de conceito perdido na transição v3→v4). Contrato é o "acordo entre organizadora e instituição" e agora é o **agregado raiz** do domínio de adesão: contém pacotes, programações, descontos, regras de responsáveis **e uma ou mais turmas (combinações curso+ano+semestre)** dentro do seu escopo. O `codigo_acesso` público (usado em SPEC-010) vive **no contrato**; a turma é escolhida pelo formando no wizard após validar o código.

---

## 1. Escopo

### 1.1 Entidades cobertas

- `contratos` (nova; recebe `codigo_acesso` + `adesao_publica_ativa` — antes viviam em `turmas`)
- `turmas` (existe; **inversão de hierarquia**: passa a pertencer a um contrato via `contrato_id` NOT NULL)
- `pacotes` (referência; ganha coluna `categoria` ∈ `formatura|extra` — detalhe completo em SPEC-F-004)
- `instituicoes`, `cursos` (referências; já planejadas em PLANEJAMENTO_BACKEND §4.2 bloco B)

### 1.2 Fora do escopo

- Pacotes e produtos completos (SPEC-F-004 / SPEC-012)
- Responsáveis (SPEC-F-002)
- Termos (SPEC-F-007)
- Admin CRUD (SPEC-011)

---

## 2. Modelo de dados (preview)

### 2.1 `contratos` — nova tabela

Campos mínimos (detalhes na expansão):

| Campo                              | Tipo            | Observação                                                           |
| ---------------------------------- | --------------- | -------------------------------------------------------------------- |
| `id`                               | BIGINT PK       |                                                                      |
| `ulid`                             | CHAR(26) UNIQUE | público                                                              |
| `categoria`                        | VARCHAR(30)     | `formatura` no MVP (enum extensível)                                 |
| `evento_id`                        | FK eventos      | **nullable** (vinculação tardia)                                     |
| `nome`                             | VARCHAR(150)    | "Formatura Medicina USP 2026"                                        |
| `status`                           | ENUM            | `rascunho`, `ativo`, `encerrado`, `cancelado`                        |
| `codigo_acesso`                    | VARCHAR(32)     | nullable, UNIQUE global, CITEXT, regex `^[A-Z0-9-]{4,32}$` (público) |
| `adesao_publica_ativa`             | BOOLEAN         | default `true`                                                       |
| `meta_formandos`                   | INTEGER         | nullable                                                             |
| `data_inicio`                      | DATE            | quando adesão abre                                                   |
| `data_fim_adesao`                  | DATE            | nullable                                                             |
| `exige_responsavel_cadastro`       | BOOLEAN         |                                                                      |
| `exige_responsavel_financeiro`     | BOOLEAN         |                                                                      |
| `permite_formando_resp_financeiro` | BOOLEAN         | (se ≥18)                                                             |
| `permite_formando_resp_cadastro`   | BOOLEAN         | (se ≥18)                                                             |
| `observacoes`                      | TEXT            | nullable                                                             |
| `timestamps` + `softDeletes`       |                 |                                                                      |

> **Nota — inversão da hierarquia (2026-04-23).** A coluna `turma_id` foi **removida** do contrato.
> Agora a relação é `Contrato hasMany Turmas` (ver §2.2 e §3). O código humano-legível de acesso público
> mudou de `turmas.codigo_acesso` para `contratos.codigo_acesso`.

### 2.2 `turmas` — alterações

Turma = combinação concreta **curso + ano + semestre** dentro de um contrato. Um contrato pode
ter múltiplas turmas (ex.: Medicina USP 2026-1 e Medicina USP 2026-2 sob o mesmo contrato).

| Campo                | Tipo         | Observação                                                 |
| -------------------- | ------------ | ---------------------------------------------------------- |
| `contrato_id`        | FK contratos | NOT NULL (**novo** — inversão: turma pertence ao contrato) |
| `curso_id`           | FK cursos    | NOT NULL                                                   |
| `ano_formatura`      | SMALLINT     | NOT NULL (ex.: `2026`)                                     |
| `semestre_formatura` | SMALLINT     | nullable (`1` ou `2`)                                      |

**Removido desta tabela** (migrado para `contratos`): `codigo_acesso`, `adesao_publica_ativa`.

### 2.3 `pacotes` — alteração (referência cruzada)

| Campo       | Tipo             | Observação                                    |
| ----------- | ---------------- | --------------------------------------------- |
| `categoria` | ENUM/VARCHAR(30) | `['formatura','extra']` — default `formatura` |

> Wizard público de adesão (SPEC-010) mostra **apenas** pacotes com `categoria='formatura'`.
> Pacotes `extra` (convites adicionais, mesas premium, combos) só aparecem no portal autenticado
> **após** a adesão ser concretizada. Detalhamento completo em SPEC-F-004.

---

## 3. Relacionamentos

> **Inversão 2026-04-23.** Contrato é o agregado raiz. As relações antigas
> (`Turma hasOne ContratoAtivo`, `Turma hasMany Contratos`, `Contrato belongsTo Turma`) **foram removidas**.

- `Contrato hasMany Turmas` (uma formatura pode agrupar várias turmas — ex.: Medicina 2026-1 + 2026-2)
- `Turma belongsTo Contrato` (NOT NULL — turma só existe dentro de um contrato)
- `Turma belongsTo Curso` (um curso por turma; combinação curso+ano+semestre é a identidade operacional)
- `Contrato hasMany Pacotes` (pacotes são ofertas comerciais do contrato)
- `Contrato hasMany Adesoes` (continua — cada adesão ocorre dentro de um contrato)
- `Contrato hasMany CondicoesPagamento` (ver SPEC-F-005)
- `Contrato belongsTo Evento` (nullable, vinculação tardia — evento concreto só existe após data definida)
- `Adesao belongsTo Contrato` (NOT NULL)
- `Adesao belongsTo Turma` (NOT NULL — escolha do formando no wizard)
- `Adesao belongsTo PortalUser` (nullable — fluxo público de SPEC-010 pode criar adesão sem auth)

---

## 4. Impacto em SPECs existentes

| SPEC       | Mudança requerida                                                                                                                                                                                                                  |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| SPEC-010   | `codigo_acesso` agora vive em `contratos` (não mais em `turmas`). Rotas públicas mudam para `/api/v1/adesao/publico/{codigo-contrato}`. Wizard ganha **nova etapa** ("escolher curso + período/turma") antes da seleção de pacote. |
| SPEC-002   | Wizard ganha step de turma (curso + ano + semestre). `turma_ulid` torna-se obrigatório no payload antes do commit da adesão. DTOs e schemas passam a exigir `contrato_ulid` + `turma_ulid`.                                        |
| SPEC-F-002 | Responsáveis continuam ligados à adesão; sem mudança estrutural nesta inversão.                                                                                                                                                    |
| SPEC-003   | `adesao.contrato_id` continua; adicionar também `adesao.turma_id` (NOT NULL) nas queries e índices de seleção.                                                                                                                     |
| SPEC-004   | Convites continuam ligados a Evento; acesso indireto via `adesao.contrato.evento_id` (quando o contrato já tiver evento).                                                                                                          |

---

## 5. Pontos a expandir na versão `draft`

- [x] Inversão da hierarquia Contrato ↔ Turma (decisão tomada em 2026-04-23 — ver §3)
- [x] `pacotes.categoria` enum (`formatura` / `extra`) e filtro no wizard público
- [x] Campos completos da migration (refletindo o novo domínio)
- [x] `codigo_acesso` movido para `contratos` (UNIQUE global, CITEXT, regex `^[A-Z0-9-]{4,32}$`)
- [x] Regras de estado (transições de `status`) — ver §5.1
- [ ] Validações do `codigo_acesso` (unicidade global vs por organização)
- [x] Model `Contrato` com casts, relacionamentos, scopes
- [ ] Policy `ContratoPolicy` (admin only para criar/editar/regenerar código)
- [ ] Factory + seeder de desenvolvimento (contrato + N turmas + pacotes formatura/extra)
- [ ] Testes: estado inicial, transições, uniqueness do código, trigger de `adesao_publica_ativa = false` revogar adesão em curso
- [ ] Decisão: Contrato histórico retém adesões antigas? (soft delete vs status)
- [ ] Seed: contrato exemplo com 2 turmas (mesmo curso, semestres diferentes) para validar fluxo do wizard

---

### 5.1 Máquinas de estado

Tabelas de transição para os quatro enums de status do domínio core. **Nota:** hoje o enforcement é apenas documental + CHECK constraint no DB (valores válidos). Validação de transição em runtime (ex: `Contrato::canTransitionTo($novo)`) só será introduzida se surgir regra de negócio crítica que exija bloquear transições inválidas — ver ADR-0008.

Convenções das tabelas:

- **✅** = transição permitida; descrição curta indica o gatilho
- **❌** = transição proibida
- **(terminal)** = estado de onde não se sai

#### 5.1.1 `StatusContrato` (`App\Enums\Contrato\StatusContrato`)

| De → Para     | Rascunho | Ativo            | Encerrado                  | Cancelado        |
| ------------- | -------- | ---------------- | -------------------------- | ---------------- |
| **Rascunho**  | —        | ✅ admin publica | ❌                         | ✅ admin cancela |
| **Ativo**     | ❌       | —                | ✅ data_fim_adesao vencida | ✅ admin cancela |
| **Encerrado** | ❌       | ❌               | —                          | ❌ (terminal)    |
| **Cancelado** | ❌       | ❌               | ❌                         | — (terminal)     |

Gatilhos automáticos esperados:

- `Ativo → Encerrado`: job periódico que compara `now() >= contratos.data_fim_adesao` (quando esta coluna não é nula).

Gatilhos manuais (admin):

- `Rascunho → Ativo`: publicação pelo admin após conferência final do contrato e pacotes associados.
- `* → Cancelado`: admin pode cancelar contrato em qualquer estado não-terminal (registra motivo em `audit_logs`).

#### 5.1.2 `StatusTurma` (`App\Enums\Turma\StatusTurma`)

| De → Para     | Ativa            | Arquivada        | Concluida              |
| ------------- | ---------------- | ---------------- | ---------------------- |
| **Ativa**     | —                | ✅ admin arquiva | ✅ formatura realizada |
| **Arquivada** | ✅ admin reativa | —                | ❌                     |
| **Concluida** | ❌               | ❌               | — (terminal)           |

`Arquivada` indica turma temporariamente oculta mas reversível; `Concluida` é terminal (após a formatura concreta).

#### 5.1.3 `StatusPacote` (`App\Enums\Pacotes\StatusPacote`)

| De → Para     | Ativo            | Inativo           | Arquivado        |
| ------------- | ---------------- | ----------------- | ---------------- |
| **Ativo**     | —                | ✅ admin desativa | ✅ admin arquiva |
| **Inativo**   | ✅ admin reativa | —                 | ✅ admin arquiva |
| **Arquivado** | ❌               | ❌                | — (terminal)     |

`Inativo` = pacote oculto no wizard mas restaurável; `Arquivado` = terminal para efeitos de histórico.

#### 5.1.4 `StatusAdesao` (`App\Enums\Adesao\StatusAdesao`)

| De → Para             | Rascunho | PendentePagamento | Ativa                    | Cancelada         | Inadimplente           | Concluida              |
| --------------------- | -------- | ----------------- | ------------------------ | ----------------- | ---------------------- | ---------------------- |
| **Rascunho**          | —        | ✅ commit wizard  | ❌                       | ✅ abandono/admin | ❌                     | ❌                     |
| **PendentePagamento** | ❌       | —                 | ✅ 1ª parcela confirmada | ✅ admin cancela  | ❌                     | ❌                     |
| **Ativa**             | ❌       | ❌                | —                        | ✅ admin cancela  | ✅ 2 parcelas vencidas | ✅ última parcela paga |
| **Inadimplente**      | ❌       | ❌                | ✅ regularizou pagamento | ✅ admin cancela  | —                      | ❌                     |
| **Cancelada**         | ❌       | ❌                | ❌                       | — (terminal)      | ❌                     | ❌                     |
| **Concluida**         | ❌       | ❌                | ❌                       | ❌                | ❌                     | — (terminal)           |

Gatilhos automáticos (jobs/eventos):

- `Rascunho → PendentePagamento`: `POST /api/v1/adesao/publico/{codigo}/commit` (SPEC-010 Gate 3+)
- `PendentePagamento → Ativa`: webhook de pagamento Itaú confirma a 1ª parcela (SPEC-F-005/F-006)
- `Ativa → Inadimplente`: job `DetectarInadimplenciaAction` identifica 2+ parcelas vencidas (SPEC-F-006)
- `Inadimplente → Ativa`: pagamento das parcelas vencidas detectado (webhook)
- `Ativa → Concluida`: pagamento da última parcela

Gatilhos manuais (admin):

- `* → Cancelada` (exceto a partir de Concluida/Cancelada): cancelamento com motivo obrigatório registrado em `audit_logs` e `adesoes.motivo_cancelamento`.

#### 5.1.5 Timestamps de transição

Convenção a consolidar em SPEC-F-003 quando a tabela `adesoes` for expandida:

- `aceito_em` → entrada em `PendentePagamento` (commit do wizard)
- `confirmada_at` → entrada em `Ativa` (1ª parcela paga)
- `cancelada_at` → entrada em `Cancelada`
- Outros estados podem ou não ter timestamp dedicado; `audit_logs` é a fonte de verdade histórica.

#### 5.1.6 Estados terminais

- **`StatusContrato::Encerrado`**, **`StatusContrato::Cancelado`** — contrato imutável; adesões associadas mantêm seu estado próprio.
- **`StatusTurma::Concluida`** — turma cujo evento de formatura já aconteceu.
- **`StatusPacote::Arquivado`** — pacote fora do catálogo definitivamente; adesões existentes continuam referenciando via `pacote_snapshot`.
- **`StatusAdesao::Cancelada`** e **`StatusAdesao::Concluida`** — adesões imutáveis.

Toda entidade em estado terminal rejeita UPDATE salvo para campos audit-only (correlation_id, observações internas).

---

## 6. Migration SQL (referência)

> Extraído do plano de implementação `2026-04-19-adesao-publica-codigo-contrato-plan.md` — Gate 1, Tasks 1.1 e 1.2. Código de referência para o desenvolvedor; a migration real será gerada via `php artisan make:migration`.

### 6.1 `create_contratos_table` (Task 1.1)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('turma_id')->constrained('turmas');
            $table->string('categoria', 30)->default('formatura');
            $table->foreignId('evento_id')->nullable()->constrained('eventos');
            $table->string('nome', 150);
            $table->string('status', 20)->default('ativo');
            $table->boolean('adesao_publica_ativa')->default(true);
            $table->integer('meta_formandos')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim_adesao')->nullable();
            $table->boolean('exige_responsavel_cadastro')->default(false);
            $table->boolean('exige_responsavel_financeiro')->default(true);
            $table->boolean('permite_formando_resp_cadastro')->default(true);
            $table->boolean('permite_formando_resp_financeiro')->default(true);
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['turma_id', 'status']);
            $table->index('status');
            $table->index('ulid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
```

### 6.2 `alter_turmas_add_codigo_acesso` (Task 1.2)

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->string('codigo_acesso', 32)->nullable()->unique();
            $table->boolean('adesao_publica_ativa')->default(true);
        });

        // Índice funcional para lookup case-insensitive (PostgreSQL)
        DB::statement('CREATE INDEX turmas_codigo_acesso_upper_idx ON turmas (UPPER(codigo_acesso))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS turmas_codigo_acesso_upper_idx');
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn(['codigo_acesso', 'adesao_publica_ativa']);
        });
    }
};
```

> **Nota sobre `codigo_acesso`:** campo `VARCHAR(32)`, único global, regex validada na camada de aplicação: `^[A-Z0-9-]{4,32}$`. O índice `UPPER(codigo_acesso)` garante lookup case-insensitive eficiente no PostgreSQL.

---

## 7. Model Contrato (referência)

> Extraído do plano de implementação — Gate 1, Task 1.3. Caminho definitivo: `app/Models/Cadastro/Contrato.php`.

### 7.1 Model

```php
<?php

declare(strict_types=1);

namespace App\Models\Cadastro;

use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class Contrato extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'contratos';

    protected $fillable = [
        'turma_id',
        'categoria',
        'evento_id',
        'nome',
        'status',
        'adesao_publica_ativa',
        'meta_formandos',
        'data_inicio',
        'data_fim_adesao',
        'exige_responsavel_cadastro',
        'exige_responsavel_financeiro',
        'permite_formando_resp_cadastro',
        'permite_formando_resp_financeiro',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'adesao_publica_ativa' => 'boolean',
            'exige_responsavel_cadastro' => 'boolean',
            'exige_responsavel_financeiro' => 'boolean',
            'permite_formando_resp_cadastro' => 'boolean',
            'permite_formando_resp_financeiro' => 'boolean',
            'data_inicio' => 'date',
            'data_fim_adesao' => 'date',
            'meta_formandos' => 'integer',
        ];
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function adesoes(): HasMany
    {
        return $this->hasMany(\App\Models\Comercial\Adesao::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('contrato');
    }
}
```

### 7.2 Factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cadastro\Contrato;
use App\Models\Cadastro\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ContratoFactory extends Factory
{
    protected $model = Contrato::class;

    public function definition(): array
    {
        return [
            'turma_id' => Turma::factory(),
            'categoria' => 'formatura',
            'nome' => 'Contrato Formatura ' . fake()->year(),
            'status' => 'ativo',
            'adesao_publica_ativa' => true,
            'exige_responsavel_financeiro' => true,
            'permite_formando_resp_financeiro' => true,
            'data_inicio' => now()->subMonth(),
            'data_fim_adesao' => now()->addMonths(6),
        ];
    }
}
```

### 7.3 Testes mínimos (Pest)

```php
<?php

declare(strict_types=1);

use App\Models\Cadastro\Contrato;

it('gera ULID ao criar', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->ulid)->not->toBeNull()
        ->and(strlen($contrato->ulid))->toBe(26);
});

it('tem status ativo por padrão', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->status)->toBe('ativo');
});

it('tem adesao_publica_ativa true por padrão', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->adesao_publica_ativa)->toBeTrue();
});

it('relaciona com turma', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->turma)->not->toBeNull();
});
```

---

## 8. Referências

- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §4 — conceito original
- [`docs/prd/PRD_v4.md`](../../prd/PRD_v4.md) §2.2, §3.2, §6.1 — menção superficial
- [`docs/SPEC-RESTRUCTURE-PLAN.md`](../../SPEC-RESTRUCTURE-PLAN.md) — contexto da Foundation
- [`docs/superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md`](../../superpowers/specs/2026-04-19-reorganizacao-specs-adesao-publica-design.md) §2.6
- [`docs/superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md`](../../superpowers/plans/2026-04-19-adesao-publica-codigo-contrato-plan.md) Gate 1, Tasks 1.1–1.3

---

_**Estado:** `draft` (v0.2.0). Migration schema e Model/relationships expandidos. Pontos pendentes: transições de estado, ContratoPolicy, seeder de desenvolvimento._
