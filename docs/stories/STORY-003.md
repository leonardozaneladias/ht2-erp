# Trait HasUlid, Support\Ulid, CorrelationContext e estrutura de bounded contexts

**ID:** STORY-003  
**Epic:** F1-E2 — Infraestrutura de domínio  
**Priority:** Must Have  
**Story Points:** 3  
**Status:** Not Started  
**Skills:** `laravel-best-practices`, `php-best-practices`

## User Story

Como **desenvolvedor do Portal ArtFinal**
Quero **ter o Trait `HasUlid`, as classes `Ulid` e `CorrelationContext` implementados, e os namespaces de bounded contexts criados**
Para que **todos os Models usem ULIDs como identificadores públicos, o contexto de correlação seja propagado em logs e filas, e cada bounded context tenha sua estrutura de pastas pronta para receber Actions, Enums, Events e demais artefatos**

## Acceptance Criteria

- [ ] `app/Models/Concerns/HasUlid.php` existe com `declare(strict_types=1)`, trait que sobrescreve `boot()` gerando ULID no evento `creating` e um método `getRouteKeyName(): string` retornando `'ulid'`
- [ ] `app/Models/Concerns/HasUlid.php` define a propriedade `ulid` como chave pública (não é a PK — a PK continua sendo `id` inteiro autoincrement)
- [ ] `app/Support/Ulid.php` existe com `declare(strict_types=1)`, método estático `generate(): string` que retorna um ULID em formato string usando `Str::ulid()` do Laravel
- [ ] `app/Support/CorrelationContext.php` existe com `declare(strict_types=1)`, método estático `set(string $correlationId): void` e `get(): string` que lê/grava o ID em `app('request')->headers` ou em uma variável estática privada (para uso em jobs fora de HTTP)
- [ ] `app/Support/CorrelationContext.php` implementa fallback: se chamado fora de contexto HTTP (ex: job na fila), usa variável estática interna — nunca falha
- [ ] Estrutura de diretórios com `.gitkeep` criada para bounded contexts: `app/Domain/Adesao/`, `app/Domain/Contrato/`, `app/Domain/Formando/`, `app/Domain/Financeiro/`, `app/Domain/Organizacao/` — cada um com subdiretórios `Actions/`, `Data/`, `Enums/`, `Events/`, `Exceptions/`, `Listeners/`, `Observers/`, `Policies/`
- [ ] Os `.gitkeep` não estão em `app/Actions/`, `app/Enums/` etc. (que são os namespaces globais já existentes) — ficam **somente** nos bounded contexts em `app/Domain/`
- [ ] `HasUlid` pode ser aplicado em qualquer Model via `use HasUlid` sem configuração adicional
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros neste arquivo

## Technical Notes

### Arquivos a criar/modificar

- `app/Models/Concerns/HasUlid.php` — criar; trait com `bootHasUlid()` (convenção Laravel para boot de traits), não `boot()`
- `app/Support/Ulid.php` — criar; wrapper fino sobre `Str::ulid()->toString()`
- `app/Support/CorrelationContext.php` — criar; gerencia propagação do request ID
- `app/Domain/Adesao/Actions/.gitkeep` — criar (e demais subdiretórios/bounded contexts listados)
- `app/Domain/Contrato/Actions/.gitkeep` — criar
- `app/Domain/Formando/Actions/.gitkeep` — criar
- `app/Domain/Financeiro/Actions/.gitkeep` — criar
- `app/Domain/Organizacao/Actions/.gitkeep` — criar
- Repetir `.gitkeep` para: `Data/`, `Enums/`, `Events/`, `Exceptions/`, `Listeners/`, `Observers/`, `Policies/` em cada bounded context

### Observações técnicas

- Usar `bootHasUlid()` (não `boot()`) — Laravel chama automaticamente `boot{TraitName}()` de cada trait quando o Model é inicializado. Usar `boot()` na trait sobrescreve o método `boot()` do Model se o devenv não usar `parent::boot()`, causando bugs silenciosos.
- O campo `ulid` deve ser adicionado ao `$fillable` ou ter cast automatizado? **Não.** O ULID é gerado automaticamente no evento `creating` pelo próprio trait — nunca deve ser passado pelo usuário. Não adicionar ao `$fillable`.
- `CorrelationContext` deve ser thread-safe para o uso em jobs concorrentes. Como PHP é single-threaded por worker, a variável estática privada é suficiente. Não usar `Cache` ou `Redis` para o correlation ID — o overhead não vale para algo de escopo de request/job.
- A estrutura `app/Domain/` segue DDD lite — os bounded contexts são organizadores, não namespaces rígidos com regras de camada. Actions globais ainda vão em `app/Actions/`, DTOs globais em `app/DTOs/`. Os bounded contexts em `app/Domain/` são para artefatos que pertencem exclusivamente a um domínio.
- Não criar autoloads PSR-4 extras no `composer.json` — o namespace `App\Domain\` já é coberto pelo mapeamento `App\\` → `app/`.

### Exemplo de implementação esperada para HasUlid

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->ulid)) {
                $model->ulid = Str::ulid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
```

## Dependencies

- **Blocked by:** STORY-001 (pacotes base instalados)
- **Blocks:** STORY-004 (providers referenciam namespaces de domínio), F1-E4 (Models herdam HasUlid)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY003` verde
- [ ] Teste unitário: instanciar um Model de teste com `HasUlid`, salvar no banco (usando `RefreshDatabase`) e verificar que `$model->ulid` não é null e tem 26 caracteres
- [ ] Teste unitário: `App\Support\Ulid::generate()` retorna string de 26 caracteres
- [ ] Teste unitário: `App\Support\CorrelationContext::set('test-id')` seguido de `::get()` retorna `'test-id'`
- [ ] Teste unitário: `App\Support\CorrelationContext::get()` sem `set()` prévio não lança exceção (retorna string vazia ou UUID gerado automaticamente)
- [ ] Diretórios `app/Domain/Adesao/Actions/`, `app/Domain/Contrato/Data/`, `app/Domain/Financeiro/Enums/` existem no filesystem
