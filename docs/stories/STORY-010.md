# Migrations bloco B — cadastro (organizacoes, instituicoes, cursos, turmas, eventos)

**ID:** STORY-010
**Epic:** F1-E4 — Modelos e banco de dados
**Priority:** Must Have
**Story Points:** 3
**Status:** Not Started
**Skills:** `laravel-best-practices`, `eloquent-best-practices`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe Portal ArtFinal**
Quero **ter as migrations da hierarquia de cadastro criadas e executáveis na ordem correta**
Para que **a cadeia Organizacao → Instituicao → Curso → Turma → Evento esteja estruturada no banco e pronta para receber dados via seeders e API**

## Acceptance Criteria

- [ ] Migration `create_organizacoes_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `name` VARCHAR(255) not null, `cnpj` VARCHAR(18) unique nullable, `logo_path` VARCHAR(500) nullable, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration `create_instituicoes_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `organizacao_id` BIGINT FK → `organizacoes.id` ON DELETE RESTRICT, `name` VARCHAR(255) not null, `sigla` VARCHAR(20) nullable, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration `create_cursos_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `instituicao_id` BIGINT FK → `instituicoes.id` ON DELETE RESTRICT, `name` VARCHAR(255) not null, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration `create_turmas_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `curso_id` BIGINT FK → `cursos.id` ON DELETE RESTRICT, `name` VARCHAR(255) not null, `codigo` VARCHAR(50) unique not null, `ano_formatura` SMALLINT not null, `meta_formandos` INTEGER not null default 0, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration `create_eventos_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `turma_id` BIGINT FK → `turmas.id` ON DELETE RESTRICT, `name` VARCHAR(255) not null, `data_evento` DATE nullable, `local` TEXT nullable, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration adicional `add_turma_fk_to_portal_users_table` adiciona FK `turma_id` → `turmas.id` em `portal_users` (fechando dependência da STORY-009)
- [ ] Índices criados em todas as tabelas: `ulid` (unique), `active` (index), FK columns (index automático via `constrained()`)
- [ ] `turmas.codigo` tem índice unique
- [ ] `php artisan migrate:fresh` executa todas as migrations sem erros
- [ ] `php artisan migrate:rollback` em ordem inversa sem erros
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `database/migrations/{timestamp}_create_organizacoes_table.php`
- `database/migrations/{timestamp}_create_instituicoes_table.php`
- `database/migrations/{timestamp}_create_cursos_table.php`
- `database/migrations/{timestamp}_create_turmas_table.php`
- `database/migrations/{timestamp}_create_eventos_table.php`
- `database/migrations/{timestamp}_add_turma_fk_to_portal_users_table.php` — fecha FK pendente de STORY-009

### Observações técnicas

**Ordem de execução das migrations (timestamps devem refletir):**

1. `create_organizacoes_table`
2. `create_instituicoes_table` (depende de organizacoes)
3. `create_cursos_table` (depende de instituicoes)
4. `create_turmas_table` (depende de cursos)
5. `create_eventos_table` (depende de turmas)
6. `add_turma_fk_to_portal_users_table` (depende de turmas + portal_users)

**Exemplo de migration turmas:**

```php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique()->notNull();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->string('name');
            $table->string('codigo', 50)->unique();
            $table->smallInteger('ano_formatura');
            $table->integer('meta_formandos')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
```

**Migration add_turma_fk_to_portal_users_table:**

```php
public function up(): void
{
    Schema::table('portal_users', function (Blueprint $table): void {
        $table->foreign('turma_id')
            ->references('id')
            ->on('turmas')
            ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('portal_users', function (Blueprint $table): void {
        $table->dropForeign(['turma_id']);
    });
}
```

**ON DELETE RESTRICT vs RESTRICT:** usar `->restrictOnDelete()` para todas as FKs da hierarquia — nunca CASCADE em entidades de cadastro principal.

**`ano_formatura`:** SMALLINT no banco; PHP cast para `int`. Não usar DATE para este campo — é apenas o ano (ex: 2025).

**`cnpj`:** armazenar sem formatação (apenas dígitos, 14 chars) ou com formatação (18 chars `XX.XXX.XXX/XXXX-XX`)? Convenção do projeto: armazenar sem formatação, VARCHAR(14). Ajustar o size para 14.

**`logo_path`:** path relativo ao storage (ex: `organizacoes/logos/uuid.jpg`). Não URL absoluta.

**PostgreSQL:** `smallInteger` mapeia para `SMALLINT`. `integer` mapeia para `INTEGER`. Correto para `meta_formandos` e `ano_formatura`.

## Dependencies

- **Blocked by:** STORY-009 (portal_users deve existir antes de add_turma_fk)
- **Blocks:** STORY-011 (models precisam das tabelas para factories e testes)

## Testing Requirements

- [ ] Teste: `php artisan migrate:fresh` executa as 6 migrations desta story sem erro
- [ ] Teste: hierarquia de FKs é respeitada — inserir `instituicao` sem `organizacao` válida falha com DB exception
- [ ] Teste: `turmas.codigo` tem constraint unique — inserir dois registros com mesmo código falha
- [ ] Teste: `add_turma_fk_to_portal_users_table` adiciona FK corretamente após `turmas` existir
- [ ] Teste: `php artisan migrate:rollback` remove as tabelas sem erro de FK (ordem inversa correta)
- [ ] `php artisan test --compact --filter=STORY010` verde
