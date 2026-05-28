# Card

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/cards.blade.php`
**Plugins JS:** Nenhum (exceto `data-action="card-toggle"` que precisa handler custom — opcional)
**Plugins CSS:** Classes `.card`, `.card-header`, `.card-title`, `.card-body`, `.card-footer` do Inspinia

---

## Descrição

Container básico do Inspinia — **o envelope mais usado** em todas as views. Tem header opcional (com título + ações), body e footer. Variantes: cor de fundo, borda dashed, header colorido, header com sub-header, com action tools (toggle, dropdown).

---

## Código Original (Inspinia — essência)

```html
<!-- Simples -->
<div class="card">
    <div class="card-body">
        <p>Conteúdo...</p>
    </div>
</div>

<!-- Com header + footer -->
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Título</h4>
    </div>
    <div class="card-body">
        <p>Conteúdo do card</p>
    </div>
    <div class="card-footer text-default-400">2 days ago</div>
</div>

<!-- Com ações no header -->
<div class="card">
    <div class="card-header">
        <h4 class="card-title">Título</h4>
        <div class="flex gap-1">
            <button
                class="btn bg-light text-default-700 hover:text-primary size-6 rounded-full"
                data-action="card-toggle"
            >
                <i class="iconify tabler--chevron-up text-base"></i>
            </button>
        </div>
    </div>
    <div class="card-body">...</div>
</div>
```

---

## Componente Blade Final

**Nome:** `<x-shared.card>`
**Arquivo:** `resources/views/components/shared/card.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/card.blade.php`

### Props

| Prop          | Tipo      | Obrigatório | Default | Descrição                                                                             |
| ------------- | --------- | :---------: | ------- | ------------------------------------------------------------------------------------- |
| `title`       | `?string` |     ❌      | `null`  | Título no header. Se `null`, header é omitido (a menos que `$header` slot seja usado) |
| `subtitle`    | `?string` |     ❌      | `null`  | Sub-header abaixo do título                                                           |
| `bodyPadding` | `bool`    |     ❌      | `true`  | Se `false`, `card-body` sem padding (para tabelas edge-to-edge)                       |

### Slots

| Slot              | Descrição                                                       |
| ----------------- | --------------------------------------------------------------- |
| `$slot` (default) | Conteúdo do body                                                |
| `$header`         | Override completo do header (em vez de usar `title`/`subtitle`) |
| `$headerActions`  | Botões/ações no canto direito do header                         |
| `$footer`         | Conteúdo do footer (se ausente, footer não é renderizado)       |

### Código

```blade
{{-- resources/views/components/shared/card.blade.php --}}
@props ([
    'title' => null,
    'subtitle' => null,
    'bodyPadding' => true,
])

<div {{ $attributes->class(['card']) }}>
    @if ($title || $subtitle || isset($header) || isset($headerActions))
        <div class="card-header">
            @if (isset($header))
                {{ $header }}
            @elseif ($title || $subtitle)
                <div class="min-w-0">
                    @if ($title)
                        <h4 class="card-title">{{ $title }}</h4>
                    @endif

                    @if ($subtitle)
                        <p class="mt-1 text-2xs text-default-400">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            @isset ($headerActions)
                <div class="ms-auto flex flex-wrap items-center gap-2">{{ $headerActions }}</div>
            @endisset
        </div>
    @endif

    <div @class (['card-body', '!p-0' => !$bodyPadding])> {{ $slot }}</div>

    @isset ($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.card title="Meta de Formandos">
    <p>Contagem atualizada em tempo real.</p>
</x-shared.card>
```

### Com ações no header

```blade
<x-shared.card title="Contratos Recentes" subtitle="Últimos 10 dias">
    <x-slot:headerActions>
        <x-shared.button variant="primary" size="sm" icon="tabler--plus" :href="route('admin.contratos.create')">
            Novo
        </x-shared.button>
    </x-slot:headerActions>

    <livewire:admin.contratos.tabela :recentes="true" />
</x-shared.card>
```

### Sem padding (para DataTable full-width)

```blade
<x-shared.card title="Todos os Formandos" :body-padding="false">
    <livewire:admin.formandos.tabela />
</x-shared.card>
```

### Com footer

```blade
<x-shared.card title="Total Geral">
    <div class="grid grid-cols-4 gap-4">
        <div>Valor: R$ 500.000,00</div>
        <div>Pago: R$ 350.000,00</div>
        <div>Pendente: R$ 100.000,00</div>
        <div>Vencido: R$ 50.000,00</div>
    </div>

    <x-slot:footer>
        <span class="text-default-400 text-sm">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
    </x-slot:footer>
</x-shared.card>
```

---

## Quando Usar ✅

- Envelope de qualquer section dentro de uma view (dashboard widgets, formulários, tabelas)
- Grouping de campos em formulários longos (14.3 Instituições, 14.4 Contratos, 14.15 Configs)
- Container de sub-views dentro de tabs

## Quando NÃO Usar ❌

- Conteúdo full-width sem boxing visual — usar `<div>` direto
- Envelope de ações curtas → usar `<x-shared.button>` em grupo
- Substituto de modal → usar `<x-shared.modal>`

---

## Mapeamento no PRD

Usado em **todas as 20 telas**. Citações explícitas no PRD:

| Tela                 | Uso específico                         |
| -------------------- | -------------------------------------- |
| 14.2 Dashboard       | KPI cards, gráfico cards, tabela cards |
| 14.3–14.20           | Envelope de todos os formulários       |
| 14.12 Ficha Formando | 7 tabs cada uma dentro de card         |
| 14.15 Configurações  | Cards agrupados por seção              |

---

## Classificação

| Critério         | Valor                        |
| ---------------- | ---------------------------- |
| **Vai usar**     | 🟢 Sim (primitivo universal) |
| **Prioridade**   | P1 (Onda 2)                  |
| **Complexidade** | Simples                      |
| **Status**       | 🟢 Concluído                 |

---

## Notas de Adaptação

1. **Classe `.card`** e derivados já estão ativos no projeto via `resources/css/custom/_card.css`
2. **Header condicional mais robusto** — a implementação final também considera `subtitle` sozinho e mantém `headerActions` alinhado à direita
3. **Slot `$header` override** continua sendo a escape hatch para casos mais complexos (tabs, badges no título, layouts compostos)
4. **`bodyPadding` toggle** segue crítico para DataTables e blocos edge-to-edge
5. **Attributes forwarding** (`$attributes->class(['card'])`) permite `wire:ignore`, `id`, `data-*` e classes extras no container
6. **Preview pronto:** acessar `/admin/dev/components/card` para validar header, ações, footer e body sem padding
