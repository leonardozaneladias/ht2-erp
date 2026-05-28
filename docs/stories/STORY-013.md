# DTOs base + Actions skeleton + Exceções de domínio

**ID:** STORY-013
**Epic:** F1-E5 — Tipos de domínio (Enums, DTOs, Actions, Exceptions)
**Priority:** Must Have
**Story Points:** 2
**Status:** Not Started
**Skills:** `laravel-dtos`, `laravel-actions`, `laravel-exceptions`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe**
Quero **ter DTOs tipados com `spatie/laravel-data`, skeletons de Actions e exceções de domínio customizadas**
Para que **o transporte de dados entre camadas seja seguro, as Actions tenham contrato definido desde F1 e erros de negócio sejam distinguíveis de erros de infraestrutura**

## Acceptance Criteria

- [ ] `app/Data/Adesao/NovaAdesaoData.php` existe como classe `spatie/laravel-data` com propriedades `turma_id: int`, `modalidade_pagamento: string`, `parcelas: int`; todos os campos têm type hints; inclui método `toArray(): array`
- [ ] `app/Data/Api/PaginatedResponseData.php` existe com propriedades `data: array` e `meta: array` (com keys `total`, `per_page`, `current_page`, `last_page`); inclui método `toArray(): array`
- [ ] `app/Actions/Adesao/CriarAdesaoAction.php` existe com método público `execute(NovaAdesaoData $data): AdesaoResultData` que lança `\RuntimeException('Não implementado em F1')` por ora
- [ ] `app/Actions/Adesao/GerarParcelasAction.php` existe com método público `execute(NovaAdesaoData $data): array` que lança `\RuntimeException('Não implementado em F1')` por ora
- [ ] `app/Exceptions/Domain/DomainException.php` existe e estende `\RuntimeException`
- [ ] `app/Exceptions/Domain/InvariantViolationException.php` existe e estende `DomainException`
- [ ] `app/Exceptions/Cota/CotaEsgotadaException.php` existe e estende `DomainException`
- [ ] Todos os arquivos têm `declare(strict_types=1)` e namespaces corretos
- [ ] Nenhuma classe usa `Request`, `redirect()`, `view()` ou `response()` diretamente
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `app/Data/Adesao/NovaAdesaoData.php` — DTO usando `Spatie\LaravelData\Data`; propriedades públicas readonly
- `app/Data/Api/PaginatedResponseData.php` — DTO usando `Spatie\LaravelData\Data`; representa envelope de paginação
- `app/Actions/Adesao/CriarAdesaoAction.php` — Action com método `execute()`; assinatura final definida, corpo pendente para F2
- `app/Actions/Adesao/GerarParcelasAction.php` — Action com método `execute()`; assinatura final definida, corpo pendente para F2
- `app/Exceptions/Domain/DomainException.php` — classe base para erros de domínio
- `app/Exceptions/Domain/InvariantViolationException.php` — lançada quando invariante de negócio é violada
- `app/Exceptions/Cota/CotaEsgotadaException.php` — lançada quando turma não tem vagas disponíveis

### Observações técnicas

**DTOs com spatie/laravel-data:**

```php
<?php

declare(strict_types=1);

namespace App\Data\Adesao;

use Spatie\LaravelData\Data;

class NovaAdesaoData extends Data
{
    public function __construct(
        public readonly int $turma_id,
        public readonly string $modalidade_pagamento,
        public readonly int $parcelas,
    ) {}

    public function toArray(): array
    {
        return [
            'turma_id'             => $this->turma_id,
            'modalidade_pagamento' => $this->modalidade_pagamento,
            'parcelas'             => $this->parcelas,
        ];
    }
}
```

**`PaginatedResponseData`** deve ter `toArray()` que retorna `['data' => $this->data, 'meta' => $this->meta]`. O campo `meta` é do tipo `array` com shape `['total' => int, 'per_page' => int, 'current_page' => int, 'last_page' => int]` — documentar via PHPDoc.

**Actions:** A assinatura de `CriarAdesaoAction::execute()` retorna `AdesaoResultData`. Esse DTO ainda não existe em F1 — declarar o return type como `mixed` com comentário `// TODO F2: trocar por AdesaoResultData` ou criar um stub `AdesaoResultData` vazio em `app/Data/Adesao/AdesaoResultData.php` para satisfazer o PHPStan. Preferir o stub.

**Actions NÃO devem:**

- Receber `Illuminate\Http\Request`
- Retornar `redirect()`, `view()`, `response()`
- Acessar `session()` diretamente

**Exceções:** `DomainException` pode ter construtor com `message` e opcional `$context: array = []` para carregar dados extras de debug.

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

class DomainException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }
}
```

`CotaEsgotadaException` pode ter factory method estático `static::paraVaga(int $turmaId): static` para criar a exceção com mensagem padronizada (opcional mas recomendado).

## Dependencies

- **Blocked by:** STORY-012 (os enums são referenciados como valores válidos para `modalidade_pagamento` em contexto de negócio; PHPStan depende dos types corretos)
- **Blocks:** STORY-014 (arch test de DTOs readonly verifica estas classes)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY013` verde
- [ ] Teste unitário `tests/Unit/Data/NovaAdesaoDataTest.php`: instanciar `NovaAdesaoData` e verificar que `toArray()` retorna o array correto com todas as keys
- [ ] Teste unitário `tests/Unit/Data/PaginatedResponseDataTest.php`: verificar estrutura de `toArray()` com `data` e `meta`
- [ ] Teste unitário `tests/Unit/Exceptions/CotaEsgotadaExceptionTest.php`: verificar que é instância de `DomainException` e de `\RuntimeException`
- [ ] Teste unitário `tests/Unit/Actions/CriarAdesaoActionTest.php`: verificar que `execute()` lança `\RuntimeException` com mensagem `'Não implementado em F1'`
