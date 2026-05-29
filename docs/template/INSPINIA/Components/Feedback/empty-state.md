# Empty State

**Categoria:** Feedback
**Origem Inspinia:** N/A — padrão comum extraído das páginas do template (não há arquivo dedicado)
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Mensagem exibida quando uma listagem está vazia (sem dados, sem resultados de busca, sem acesso). Padrão amigável com **ícone grande**, **título**, **descrição** opcional e **CTA** (call-to-action) opcional. Melhor que uma tabela vazia ou uma mensagem "Nenhum registro encontrado" sem contexto.

> **Inspinia não fornece um empty state padronizado** — este componente é totalmente nosso, baseado em padrões comuns do ecosystem Tailwind/Preline.

---

## Código de referência (compilado do padrão de empty states modernos)

```html
<div class="flex flex-col items-center justify-center px-4 py-16 text-center">
    <div class="bg-default-100 mb-4 flex size-20 items-center justify-center rounded-full">
        <i class="iconify tabler--inbox text-default-400 text-4xl"></i>
    </div>
    <h3 class="mb-2 text-lg font-semibold">Nenhum pedido cadastrado</h3>
    <p class="text-default-400 mb-6 max-w-sm">
        Comece criando seu primeiro pedido para vincular clientes e iniciar o fluxo de vendas.
    </p>
    <a href="/admin/pedidos/create" class="btn bg-primary text-white">
        <i class="iconify tabler--plus"></i>
        Novo Pedido
    </a>
</div>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.empty-state>`
**Arquivo:** `resources/views/components/shared/empty-state.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop          | Tipo      | Obrigatório | Default           | Descrição                                      |
| ------------- | --------- | :---------: | ----------------- | ---------------------------------------------- |
| `icon`        | `string`  |     ❌      | `'tabler--inbox'` | Ícone Iconify                                  |
| `title`       | `string`  |     ✅      | —                 | Título principal                               |
| `description` | `?string` |     ❌      | `null`            | Texto descritivo                               |
| `size`        | `string`  |     ❌      | `'md'`            | sm, md, lg — define padding e tamanho do ícone |

### Slots

| Slot              | Descrição                                     |
| ----------------- | --------------------------------------------- |
| `$action`         | Botão CTA (ex: "Novo Pedido")                 |
| `$slot` (default) | Se usado, substitui `description` (HTML rico) |

### Código

```blade
{{-- resources/views/components/shared/empty-state.blade.php --}}
@props ([
    'icon' => 'tabler--inbox',
    'title',
    'description' => null,
    'size' => 'md',
])

@php
    $sizeClass = match($size) {
        'sm' => ['py-8 px-4', 'size-12', 'text-2xl', 'text-base'],
        'lg' => ['py-24 px-4', 'size-28', 'text-5xl', 'text-xl'],
        default => ['py-16 px-4', 'size-20', 'text-4xl', 'text-lg'],
    };
    [$containerPad, $iconCircleSize, $iconTextSize, $titleSize] = $sizeClass;
@endphp

<div class="flex flex-col items-center justify-center {{ $containerPad }} text-center">
    <div class="bg-default-100 {{ $iconCircleSize }} rounded-full flex items-center justify-center mb-4">
        <i class="iconify {{ $icon }} {{ $iconTextSize }} text-default-400"></i>
    </div>

    <h3 class="{{ $titleSize }} font-semibold mb-2">{{ $title }}</h3>

    @if (isset($slot) && trim($slot) !== '')
        <div class="text-default-400 max-w-sm mb-6">{{ $slot }}</div>
    @elseif ($description)
        <p class="text-default-400 max-w-sm mb-6">{{ $description }}</p>
    @endif

    @isset ($action)
        <div>{{ $action }}</div>
    @endisset
</div>
```

---

## Exemplos de Uso

### Lista sem dados

```blade
<x-shared.card :body-padding="false">
    @if ($pedidos->isEmpty())
        <x-shared.empty-state
            icon="tabler--file-text"
            title="Nenhum pedido cadastrado"
            description="Comece criando seu primeiro pedido para vincular clientes e iniciar o fluxo de vendas."
        >
            <x-slot:action>
                <x-shared.button variant="primary" icon="tabler--plus" :href="route('admin.pedidos.create')">
                    Novo Pedido
                </x-shared.button>
            </x-slot:action>
        </x-shared.empty-state>
    @else
        <livewire:admin.pedidos.tabela :pedidos="$pedidos" />
    @endif
</x-shared.card>
```

### Busca sem resultado

```blade
@if (empty($clientes) && !empty($busca))
    <x-shared.empty-state
        icon="tabler--search-off"
        title="Nenhum cliente encontrado"
        description="Tente ajustar os filtros ou revisar o termo de busca."
    >
        <x-slot:action>
            <x-shared.button variant="default" style="outline" wire:click="limparFiltros">
                Limpar Filtros
            </x-shared.button>
        </x-slot:action>
    </x-shared.empty-state>
@endif
```

### Sem permissão (403 inline)

```blade
@cannot ('relatorios.visualizar')
    <x-shared.empty-state
        icon="tabler--lock"
        title="Sem permissão"
        description="Você não tem acesso a esta seção. Contate um administrador."
        size="sm"
    />
@endcannot
```

### Aba vazia na ficha do cliente

```blade
<x-shared.tab-panel id="auditoria">
    @if ($cliente->auditLogs->isEmpty())
        <x-shared.empty-state
            icon="tabler--history"
            title="Sem histórico"
            description="Ações realizadas sobre este cliente aparecerão aqui."
            size="sm"
        />
    @else
        <livewire:admin.clientes.tabs.auditoria :cliente="$cliente" />
    @endif
</x-shared.tab-panel>
```

---

## Quando Usar ✅

- Listagens (DataTable, Livewire) quando retornarem 0 itens
- Busca/filtro sem resultado
- Tabs sem dados vinculados (documentos, auditoria, itens)
- Seções sem permissão

## Quando NÃO Usar ❌

- Mensagem de erro → usar `<x-shared.alert variant="danger">`
- Loading state → usar `<x-shared.spinner>` ou skeleton
- Página 404 → usar a view de erro dedicada
- Feedback efêmero → usar `<x-shared.toast>`

---

## Classificação

| Critério         | Valor               |
| ---------------- | ------------------- |
| **Vai usar**     | 🟢 Sim (recorrente) |
| **Complexidade** | Trivial             |
| **Status**       | 🟢 Concluído        |

---

## Notas de Adaptação

1. **Não existe no Inspinia original** — totalmente desenhado por nós. Ícone Iconify padrão (`tabler--inbox`) mas customizável por contexto
2. **Tamanhos `sm`/`md`/`lg`:** escolher baseado no container. Tabs usam `sm`, card principal usa `md`, página completa usa `lg`
3. **CTA via slot `$action`** — flexível, aceita `<x-shared.button>` ou múltiplos botões
4. **Slot default ou `description` prop:** usar slot quando precisar de HTML (link inline); usar prop quando é só texto
5. **Ícones sugeridos por contexto:**
    - Lista vazia: `tabler--inbox`
    - Busca: `tabler--search-off`
    - Sem permissão: `tabler--lock`
    - Auditoria vazia: `tabler--history`
    - Usuários: `tabler--users-off`
    - Arquivos: `tabler--file-off`
6. **`max-w-sm`** na descrição limita largura do texto para legibilidade
7. **`center` layout** — nunca usar empty-state alinhado à esquerda; sempre central

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/empty-state.blade.php`
- **Preview visual:** `/admin/dev/components/empty-state`

### API final

- props: `icon`, `title`, `description`, `size`
- slots: default slot para conteúdo rico e `action` para CTA

### Exemplo final

```blade
<x-shared.empty-state
    icon="tabler--search-off"
    title="Nenhum cliente encontrado"
    description="Tente ajustar os filtros ou revisar o termo informado."
>
    <x-slot:action>
        <x-shared.button variant="default" appearance="outline"> Limpar filtros </x-shared.button>
    </x-slot:action>
</x-shared.empty-state>
```
