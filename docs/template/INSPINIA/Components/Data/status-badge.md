# Status Badge (Enum-driven)

**Categoria:** Data
**Origem Inspinia:** N/A — composição nossa baseada em `<x-shared.badge>`
**Plugins JS:** Nenhum
**Plugins CSS:** Herda de `badge.md`

---

## Descrição

Especialização de `<x-shared.badge>` que recebe um **PHP BackedEnum** e deriva automaticamente o **label** (texto em PT-BR) e a **cor** (variant). Remove boilerplate em tabelas — em vez de escrever `match` em cada view, o Enum define uma vez `label()` e `color()`, e este componente apenas renderiza.

> **Pré-requisito:** todos os enums do projeto devem implementar o trait `App\Enums\Concerns\HasLabelAndColor` (ou equivalente).

---

## Padrão do Enum

```php
// app/Enums/StatusPedido.php
declare(strict_types=1);

namespace App\Enums;

enum StatusPedido: string
{
    case PENDENTE = 'pendente';
    case PAGO = 'pago';
    case VENCIDO = 'vencido';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match($this) {
            self::PENDENTE => 'Pendente',
            self::PAGO => 'Pago',
            self::VENCIDO => 'Vencido',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDENTE => 'warning',
            self::PAGO => 'success',
            self::VENCIDO => 'danger',
            self::CANCELADO => 'default',
        };
    }

    public function icon(): ?string
    {
        return match($this) {
            self::PENDENTE => 'tabler--clock',
            self::PAGO => 'tabler--circle-check',
            self::VENCIDO => 'tabler--alert-triangle',
            self::CANCELADO => 'tabler--ban',
        };
    }
}
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.status-badge>`
**Arquivo:** `resources/views/components/shared/status-badge.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop       | Tipo         | Obrigatório | Default | Descrição                                          |
| ---------- | ------------ | :---------: | ------- | -------------------------------------------------- |
| `enum`     | `BackedEnum` |     ✅      | —       | Instância do Enum (deve ter `label()` e `color()`) |
| `withIcon` | `bool`       |     ❌      | `false` | Incluir ícone (requer `icon()` no Enum)            |
| `pill`     | `bool`       |     ❌      | `true`  | Formato pill                                       |
| `solid`    | `bool`       |     ❌      | `false` | Fundo sólido                                       |
| `size`     | `string`     |     ❌      | `'md'`  | sm, md, lg                                         |

### Código

```blade
{{-- resources/views/components/shared/status-badge.blade.php --}}
@props ([
    'enum',
    'withIcon' => false,
    'pill' => true,
    'solid' => false,
    'size' => 'md',
])

@php
    $variant = $enum->color();
    $label = $enum->label();
    $icon = $withIcon && method_exists($enum, 'icon') ? $enum->icon() : null;
@endphp

<x-shared.badge :variant="$variant" :pill="$pill" :solid="$solid" :size="$size" :icon="$icon">
    {{ $label }}
</x-shared.badge>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.status-badge :enum="$pedido->status" />
{{-- Renderiza: <span class="badge bg-warning/15 text-warning rounded-full ...">Pendente</span> --}}

<x-shared.status-badge :enum="$pedido->status" with-icon solid />
```

### Real (Tabela de Pagamentos)

```blade
<table class="table">
    <thead>
        <tr>
            <th>Pagamento</th>
            <th>Vencimento</th>
            <th>Valor</th>
            <th>Modalidade</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($pagamentos as $pagamento)
            <tr>
                <td>{{ $pagamento->numero }}/{{ $pagamento->total }}</td>
                <td>{{ $pagamento->vencimento->format('d/m/Y') }}</td>
                <td class="text-end">{{ MoneyHelper::format($pagamento->valor_cobrado_centavos) }}</td>
                <td>
                    <x-shared.status-badge :enum="$pagamento->modalidade" pill />
                </td>
                <td>
                    <x-shared.status-badge :enum="$pagamento->status" with-icon />
                </td>
                <td>
                    <x-shared.dropdown placement="bottom-end">
                        {{-- ... --}}
                    </x-shared.dropdown>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### Real (Dashboard — Último pedido)

```blade
<td>
    <x-shared.status-badge :enum="$pedido->status" />
</td>
```

### Real (Ficha do cliente — situação)

```blade
<div class="sidebar">
    <h5>{{ $cliente->nome }}</h5>
    <x-shared.status-badge :enum="$cliente->statusSituacao()" size="lg" with-icon />
</div>
```

---

## Quando Usar ✅

- Toda vez que a view exibe o valor de um Enum
- Tabelas com coluna de status
- Sidebars com indicadores
- Filtros (ex: render do valor selecionado)

## Quando NÃO Usar ❌

- Valores não-enum (texto livre) → usar `<x-shared.badge>` diretamente
- Múltiplos enums na mesma cell → renderizar cada um separadamente
- Badge com cor fora do padrão do Enum → usar `<x-shared.badge>` custom

---

## Exemplos de Enums compatíveis

Qualquer enum com `label()` + `color()` (e opcionalmente `icon()`) funciona com este componente. Exemplos típicos:

| Enum                                              | Onde costuma aparecer       |
| ------------------------------------------------- | --------------------------- |
| `StatusPedido` (Pendente/Pago/Vencido/Cancelado)  | Tabelas e dashboard         |
| `StatusProduto` (disponibilidade)                 | Listagem de produtos        |
| `ModalidadePagamento` (Boleto/Cartão/PIX/Híbrida) | Tabelas financeiras         |
| `StatusCliente` (ativo/inativo)                   | Ficha e listagem de clientes |
| `StatusAdmin` (ativo/inativo)                     | Gestão de usuários admin    |

---

## Classificação

| Critério         | Valor              |
| ---------------- | ------------------ |
| **Vai usar**     | 🟢 Sim (universal) |
| **Complexidade** | Trivial (wrapper)  |
| **Status**       | 🟢 Concluído       |

---

## Notas de Adaptação

1. **Dependência do Enum ter `label()` + `color()`** — estabelecer o padrão no `CLAUDE.md` ou criar interface `LabeledColoredEnum`
2. **Trait opcional:** criar `App\Enums\Concerns\HasLabelAndColor` com defaults que os enums podem sobrescrever
3. **Casting Eloquent:** usar `protected $casts = ['status' => StatusPedido::class]` para que `$pedido->status` já retorne a instância do Enum, não string
4. **Safelist Tailwind:** as cores derivadas (`variant` vindo do Enum) precisam estar no safelist como toda cor dinâmica
5. **i18n futuro:** se o projeto ganhar inglês, mover `label()` do Enum para `__('enums.StatusPedido.pendente')`. Deixar pronto para essa evolução
6. **Icons opcional:** nem todo Enum terá `icon()`. O componente faz check `method_exists`
7. **Formato pill padrão** — diferente do `<x-shared.badge>` onde default é retângulo. Status badges pill ficam melhores visualmente

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/status-badge.blade.php`
- **Preview visual:** `/admin/dev/components/status-badge`
- **Base reutilizada:** `x-shared.badge`

### API final

- props: `enum`, `withIcon`, `pill`, `solid`, `size`
- aceita `BackedEnum` ou objeto compatível com `label()`, `color()` e `icon()`

### Exemplo final

```blade
<x-shared.status-badge :enum="$pedido->status" with-icon />
```
