# Migrations bloco A — identidade (admin_users, portal_users, comissao_users)

**ID:** STORY-009
**Epic:** F1-E4 — Modelos e banco de dados
**Priority:** Must Have
**Story Points:** 2
**Status:** Not Started
**Skills:** `laravel-best-practices`, `eloquent-best-practices`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe Portal ArtFinal**
Quero **ter as migrations das tabelas de identidade criadas e executáveis**
Para que **os três tipos de usuário do sistema (AdminUser, PortalUser, ComissaoUser) tenham suas estruturas de banco prontas para os models e guards**

## Acceptance Criteria

- [ ] Migration `create_admin_users_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `name` VARCHAR(255), `email` VARCHAR(255) unique not null, `password` VARCHAR(255), `mfa_secret` VARCHAR(255) nullable, `remember_token` VARCHAR(100) nullable, `active` BOOLEAN default true, `created_at`, `updated_at`, `deleted_at` (softDeletes)
- [ ] Migration `create_portal_users_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `turma_id` BIGINT FK para `turmas` nullable, `formando_id` BIGINT FK para `formandos` nullable, `name` VARCHAR(255), `email` VARCHAR(255) unique not null, `password` VARCHAR(255) nullable, `convite_token` VARCHAR(64) unique nullable, `remember_token` VARCHAR(100) nullable, `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Migration `create_comissao_users_table` existe com: `id` BIGINT PK, `ulid` CHAR(26) unique not null, `turma_id` BIGINT FK para `turmas` not null, `name` VARCHAR(255), `email` VARCHAR(255) unique not null, `password` VARCHAR(255), `active` BOOLEAN default true, `created_at`, `updated_at`
- [ ] Índices criados: `ulid` (unique), `email` (unique), `active` (index), `turma_id` (index) em `portal_users` e `comissao_users`
- [ ] `php artisan migrate:fresh` executa sem erros com as três migrations
- [ ] `php artisan migrate:rollback` executa `down()` sem erros (tabelas removidas na ordem correta)
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `database/migrations/{timestamp}_create_admin_users_table.php` — tabela de usuários do backoffice
- `database/migrations/{timestamp}_create_portal_users_table.php` — tabela de formandos/responsáveis
- `database/migrations/{timestamp}_create_comissao_users_table.php` — tabela de membros da comissão
- Ordem de criação: `admin_users` → `portal_users` → `comissao_users` (portal_users depende de turmas via FK nullable; se turmas não existir ainda, usar `->nullable()->constrained('turmas')` com restrição diferida ou omitir FK nesta migration e adicionar em migration separada na STORY-010)

### Observações técnicas

**Estratégia de FK com dependência circular:**
`portal_users.turma_id` aponta para `turmas`, que ainda não existe nesta story. Opções:

1. Definir a coluna como `unsignedBigInteger('turma_id')->nullable()` sem `constrained()` nesta migration, e adicionar a FK em `add_turma_fk_to_portal_users_table` após a STORY-010 criar `turmas`.
2. Esta é a abordagem recomendada para evitar dependência de ordem de migration.

**Exemplo de migration admin_users:**

```php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique()->notNull();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('mfa_secret')->nullable();
            $table->rememberToken();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
```

**Soft-delete:** apenas `admin_users` usa `softDeletes()` — `portal_users` e `comissao_users` usam apenas o campo `active` (conforme regra do projeto: soft-delete via inativação).

**Convenção de ULID:** CHAR(26) — armazenado como string, gerado no PHP pela Trait `HasUlid` (STORY-011). No banco, apenas garantir a constraint unique.

**PostgreSQL 16:** `boolean` mapeia para `BOOLEAN` nativo. `char(26)` mapeia para `CHAR(26)`. Não usar `tinyInteger` para booleanos.

**Ordem de timestamp nos arquivos:** garantir que `create_admin_users_table` tenha timestamp anterior a `create_portal_users_table` e `create_comissao_users_table`.

## Dependencies

- **Blocked by:** STORY-001 (ambiente Docker + PostgreSQL operacional)
- **Blocks:** STORY-010 (turmas precisa existir antes das FKs reais de portal_users), STORY-011 (models dependem das tabelas)

## Testing Requirements

- [ ] Teste: `php artisan migrate:fresh` executa todas as três migrations sem erro
- [ ] Teste: `php artisan migrate:rollback --step=3` remove as tabelas sem erro
- [ ] Teste unit/architecture: tabelas `admin_users`, `portal_users`, `comissao_users` existem após migrate
- [ ] Teste: coluna `ulid` em todas as três tabelas tem constraint unique
- [ ] Teste: `email` tem constraint unique nas três tabelas
- [ ] `php artisan test --compact --filter=STORY009` verde
