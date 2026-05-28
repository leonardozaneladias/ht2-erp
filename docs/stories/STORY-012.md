# Enums de domínio base

**ID:** STORY-012
**Epic:** F1-E5 — Tipos de domínio (Enums, DTOs, Actions, Exceptions)
**Priority:** Must Have
**Story Points:** 1
**Status:** Not Started
**Skills:** `laravel-enums`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe**
Quero **ter todos os enums de domínio base criados com backed strings, labels e cores padronizados**
Para que **todo o código da aplicação use tipos seguros em vez de strings soltas, garantindo consistência e análise estática**

## Acceptance Criteria

- [ ] `app/Enums/Adesao/StatusAdesao.php` existe com cases `PENDENTE='pendente'`, `CONFIRMADA='confirmada'`, `CANCELADA='cancelada'` e métodos `label(): string` e `color(): string`
- [ ] `app/Enums/Shared/PerfilAtor.php` existe com cases `ADMIN='admin'`, `COMISSAO='comissao'`, `FORMANDO='formando'`, `CONVIDADO='convidado'`
- [ ] `app/Enums/Shared/OrigemAtor.php` existe com cases `ADMIN='admin'`, `PORTAL='portal'`, `WEBHOOK='webhook'`, `SISTEMA='sistema'`
- [ ] `app/Enums/Pagamentos/StatusParcela.php` existe com cases `PENDENTE='pendente'`, `PAGO='pago'`, `VENCIDO='vencido'`, `CANCELADO='cancelado'` e métodos `label(): string`, `color(): string`, `isPago(): bool`, `isVencido(): bool`
- [ ] Todos os enums são `backed string` (`:string` após o nome do enum)
- [ ] Todos os arquivos têm `declare(strict_types=1)` na linha 2
- [ ] Todos os arquivos têm namespace correto (`App\Enums\Adesao`, `App\Enums\Shared`, `App\Enums\Pagamentos`)
- [ ] `StatusAdesao::CONFIRMADA->label()` retorna `'Confirmada'` e `->color()` retorna uma string de classe CSS Tailwind (ex: `'text-green-600'`)
- [ ] `StatusParcela::PAGO->isPago()` retorna `true`; `StatusParcela::VENCIDO->isVencido()` retorna `true`; todos os outros cases retornam `false` nos respectivos métodos
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `app/Enums/Adesao/StatusAdesao.php` — enum backed string com `label()` e `color()`
- `app/Enums/Shared/PerfilAtor.php` — enum backed string, sem métodos adicionais
- `app/Enums/Shared/OrigemAtor.php` — enum backed string, sem métodos adicionais
- `app/Enums/Pagamentos/StatusParcela.php` — enum backed string com `label()`, `color()`, `isPago()`, `isVencido()`

### Observações técnicas

Usar PHP 8.1+ backed enums. Estrutura de referência para `StatusAdesao`:

```php
<?php

declare(strict_types=1);

namespace App\Enums\Adesao;

enum StatusAdesao: string
{
    case PENDENTE   = 'pendente';
    case CONFIRMADA = 'confirmada';
    case CANCELADA  = 'cancelada';

    public function label(): string
    {
        return match($this) {
            self::PENDENTE   => 'Pendente',
            self::CONFIRMADA => 'Confirmada',
            self::CANCELADA  => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDENTE   => 'text-yellow-600',
            self::CONFIRMADA => 'text-green-600',
            self::CANCELADA  => 'text-red-600',
        };
    }
}
```

`StatusParcela::isPago()` deve ser `return $this === self::PAGO;`. `StatusParcela::isVencido()` deve ser `return $this === self::VENCIDO;`.

Valores de `color()` devem usar classes Tailwind CSS 4 compatíveis com dark mode futuro (preferir `text-*` ou `bg-*` como string para ser aplicado via componente Blade).

Não usar `from()` nem `tryFrom()` dentro dos próprios enums — esses são auxiliares externos.

## Dependencies

- **Blocked by:** Nenhuma
- **Blocks:** STORY-013 (DTOs e Actions referenciam `StatusAdesao` e `StatusParcela`)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY012` verde
- [ ] Teste unitário `tests/Unit/Enums/StatusAdesaoTest.php` cobrindo todos os cases de `label()` e `color()`
- [ ] Teste unitário `tests/Unit/Enums/StatusParcelaTest.php` cobrindo `label()`, `color()`, `isPago()` e `isVencido()` para todos os cases
- [ ] Assertions de que `StatusAdesao::from('confirmada') === StatusAdesao::CONFIRMADA`
- [ ] Assertions de que `StatusParcela::PAGO->isPago()` é `true` e `StatusParcela::PENDENTE->isPago()` é `false`
