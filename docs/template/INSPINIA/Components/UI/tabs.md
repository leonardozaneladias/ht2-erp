# Tabs

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/tabs.blade.php`
**Plugins JS:** Preline 4.0.1 (`data-hs-tab`, `hs-tab-active:*`)
**Plugins CSS:** Classes `hs-tab-active:*` do Preline

---

## Descrição

Navegação por abas horizontais. Crítico no Portal ArtFinal — usado em **14.4 Contratos (5 tabs)**, **14.6 Produtos (5 tabs)**, e **14.12 Ficha Formando (7 tabs)**. Preline cuida do toggle de visibilidade dos panels via classes dinâmicas.

---

## Código Original (Inspinia — essência)

```html
<nav aria-label="Tabs" class="flex flex-wrap" role="tablist">
    <button
        type="button"
        role="tab"
        id="tab-overview"
        data-hs-tab="#panel-overview"
        aria-controls="panel-overview"
        aria-selected="true"
        class="hs-tab-active:border-b-transparent hs-tab-active:bg-card hs-tab-active:border hs-tab-active:text-primary border-default-300 hover:text-primary active inline-flex items-center rounded-t border-b px-4 py-2 font-medium"
    >
        Overview
    </button>
    <button
        type="button"
        role="tab"
        id="tab-activity"
        data-hs-tab="#panel-activity"
        aria-controls="panel-activity"
        aria-selected="false"
        class="hs-tab-active:border-b-transparent hs-tab-active:bg-card hs-tab-active:border hs-tab-active:text-primary border-default-300 hover:text-primary inline-flex items-center rounded-t border-b px-4 py-2 font-medium"
    >
        Activity
    </button>
</nav>

<div class="mt-5">
    <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview">
        <p>Conteúdo Overview (visível por padrão — sem `hidden`)</p>
    </div>
    <div id="panel-activity" role="tabpanel" aria-labelledby="tab-activity" class="hidden">
        <p>Conteúdo Activity (oculto por padrão)</p>
    </div>
</div>
```

**Variações:** justified (`w-full md:w-auto`), vertical, com ícones, pills, disabled.

---

## Composição Blade Oficial

> **Decisão oficial do Batch 3:** `x-shared.tabs` continua sendo o nome guarda-chuva no catálogo e no mapa, mas a API Blade final não será um wrapper array-driven. A implementação real fica oficializada como composição `x-shared.tab-nav` + `x-shared.tab-trigger` + `x-shared.tab-panel`.

**Nome guarda-chuva:** `<x-shared.tabs>`

**Arquivos reais:**

- `resources/views/components/shared/tab-nav.blade.php`
- `resources/views/components/shared/tab-trigger.blade.php`
- `resources/views/components/shared/tab-panel.blade.php`

**Tipo:** Blade anônimo em composição

**Motivo da decisão:** Blade não lida bem com slots dinâmicos para múltiplos painéis; a composição explícita é mais previsível, mais fácil de documentar e mais simples de manter em telas grandes.

### Componentes da composição oficial

### `<x-shared.tab-nav>` (wrapper do nav)

```blade
{{-- resources/views/components/shared/tab-nav.blade.php --}}
@props (['justified' => false])

<nav aria-label="Tabs" role="tablist" @class (['flex flex-wrap', 'md:flex-nowrap' => $justified])> {{ $slot }}</nav>
```

### `<x-shared.tab-trigger>` (button individual)

```blade
{{-- resources/views/components/shared/tab-trigger.blade.php --}}
@props ([
    'id',
    'active' => false,
    'disabled' => false,
    'icon' => null,
    'justified' => false,
])

<button
    type="button"
    role="tab"
    id="tab-{{ $id }}"
    data-hs-tab="#panel-{{ $id }}"
    aria-controls="panel-{{ $id }}"
    aria-selected="{{ $active ? 'true' : 'false' }}"
    @disabled ($disabled)
    @class ([
            'hs-tab-active:border-b-transparent hs-tab-active:bg-card hs-tab-active:border hs-tab-active:text-primary',
            'border-default-300 hover:text-primary',
            'inline-flex items-center gap-2 rounded-t border-b px-4 py-2 font-medium',
            'focus:outline-hidden disabled:pointer-events-none disabled:opacity-50',
            'active' => $active,
            'w-auto md:w-full justify-center' => $justified,
        ])
>
    @if ($icon)
        <i class="iconify {{ $icon }} text-base"></i>
    @endif
    {{ $slot }}
</button>
```

### `<x-shared.tab-panel>` (painel de conteúdo)

```blade
{{-- resources/views/components/shared/tab-panel.blade.php --}}
@props (['id', 'active' => false])

<div id="panel-{{ $id }}" role="tabpanel" aria-labelledby="tab-{{ $id }}" @class (['hidden' => !$active])>
    {{ $slot }}
</div>
```

---

## Exemplos de Uso

### Real (Ficha Formando 14.12 — 7 tabs)

```blade
<x-shared.card>
    <x-shared.tab-nav>
        <x-shared.tab-trigger id="dados" active icon="tabler--user">Dados Pessoais</x-shared.tab-trigger>
        <x-shared.tab-trigger id="resp" icon="tabler--users">Responsáveis</x-shared.tab-trigger>
        <x-shared.tab-trigger id="portal" icon="tabler--device-laptop">Portal Users</x-shared.tab-trigger>
        <x-shared.tab-trigger id="pacotes" icon="tabler--package">Pacotes</x-shared.tab-trigger>
        <x-shared.tab-trigger id="extrato" icon="tabler--cash">Extrato</x-shared.tab-trigger>
        <x-shared.tab-trigger id="termos" icon="tabler--file-text">Termos</x-shared.tab-trigger>
        <x-shared.tab-trigger id="auditoria" icon="tabler--history">Histórico</x-shared.tab-trigger>
    </x-shared.tab-nav>

    <div class="mt-5">
        <x-shared.tab-panel id="dados" active>
            <livewire:admin.formandos.tabs.dados :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="resp">
            <livewire:admin.formandos.tabs.responsaveis :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="portal">
            <livewire:admin.formandos.tabs.portal-users :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="pacotes">
            <livewire:admin.formandos.tabs.pacotes :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="extrato">
            <livewire:admin.formandos.tabs.extrato :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="termos">
            <livewire:admin.formandos.tabs.termos :formando="$formando" />
        </x-shared.tab-panel>
        <x-shared.tab-panel id="auditoria">
            <livewire:admin.formandos.tabs.auditoria :formando="$formando" />
        </x-shared.tab-panel>
    </div>
</x-shared.card>
```

---

## Quando Usar ✅

- Ficha do formando (7 tabs — 14.12)
- Form de contrato (5 tabs — 14.4)
- Form de produto (5 tabs — 14.6)
- Configurações agrupadas

## Quando NÃO Usar ❌

- Formulários longos com sections — usar `<x-shared.accordion>` (14.20 cadastro manual)
- Navegação por rota — usar sidebar
- Apenas 2 estados — usar toggle/switch

---

## Mapeamento no PRD

| Tela           | Seção PRD | Tabs                                                              |
| -------------- | --------- | ----------------------------------------------------------------- |
| Contrato form  | 14.4      | 5 tabs (Dados, Responsáveis, Cursos, Períodos, Reajustes)         |
| Produto form   | 14.6      | 5 tabs (Dados, Programações, Condições, Descontos, Termos)        |
| Ficha Formando | 14.12     | 7 tabs (Dados, Resp, Portal, Pacotes, Extrato, Termos, Auditoria) |

---

## Classificação

| Critério         | Valor                                 |
| ---------------- | ------------------------------------- |
| **Vai usar**     | 🟢 Sim (crítico para forms complexos) |
| **Prioridade**   | P1 (Onda 2)                           |
| **Complexidade** | Média                                 |
| **Status**       | 🟢 Concluído                          |

---

## Notas de Adaptação

1. **Abordagem split em 3 componentes** (tab-nav, tab-trigger, tab-panel) — mais idiomática que wrapper único, Blade não suporta slots dinâmicos
2. **Preline classes** — `hs-tab-active:*` aplicadas automaticamente quando aba ativa
3. **Classe `active`** inicial precisa coincidir com o painel **sem** `hidden`
4. **`aria-selected`**: início `"true"` na aba ativa, `"false"` nas demais. O Preline atualiza no click
5. **`disabled`** tabs pulam navegação automática pelo Preline
6. **Scroll horizontal em mobile:** se tabs excedem largura, adicionar `overflow-x-auto` no `<nav>`
7. **Livewire `wire:ignore`:** para preservar estado dos painéis entre re-renders, envolver o nav com `wire:ignore` — cuidado com isso
8. **`x-shared.tabs` é o nome da família**, não uma tag Blade independente nesta fase. O uso real em view deve consumir os 3 subcomponentes acima

---

## Código Final Blade

- **Arquivos finais:**
    - `resources/views/components/shared/tab-nav.blade.php`
    - `resources/views/components/shared/tab-trigger.blade.php`
    - `resources/views/components/shared/tab-panel.blade.php`
- **Preview visual:** `/admin/dev/components/tabs`

### API final

- `x-shared.tab-nav`: `justified`
- `x-shared.tab-trigger`: `id`, `active`, `disabled`, `icon`, `justified`
- `x-shared.tab-panel`: `id`, `active`

### Exemplo final

```blade
<x-shared.tab-nav>
    <x-shared.tab-trigger id="dados" active icon="tabler--user">Dados</x-shared.tab-trigger>
    <x-shared.tab-trigger id="historico" icon="tabler--history">Histórico</x-shared.tab-trigger>
</x-shared.tab-nav>

<x-shared.tab-panel id="dados" active> ... </x-shared.tab-panel>

<x-shared.tab-panel id="historico"> ... </x-shared.tab-panel>
```
