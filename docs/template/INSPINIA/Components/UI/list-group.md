# List Group

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/list-group.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classes `.list-group`, `.list-group-item` do Inspinia

---

## Descrição

Lista vertical de itens agrupados num container com bordas. Usado para exibir **informações estruturadas sem tabela** — ex: dados pessoais de um formando, detalhes de um contrato, properties de um produto. Itens podem ser simples, com badges, clicáveis, ou como grupos de navegação.

---

## Código Original (Inspinia — essência)

```html
<!-- Básico -->
<ul class="border-default-300 divide-default-300 divide-y rounded border">
    <li class="px-4 py-3">An item</li>
    <li class="px-4 py-3">A second item</li>
    <li class="px-4 py-3">A third item</li>
</ul>

<!-- Com badges -->
<ul class="border-default-300 divide-default-300 divide-y rounded border">
    <li class="flex items-center justify-between px-4 py-3">
        <span>Pending</span>
        <span class="bg-warning/15 text-warning rounded px-2 py-0.5 text-xs font-semibold">14</span>
    </li>
    <li class="flex items-center justify-between px-4 py-3">
        <span>Completed</span>
        <span class="bg-success/15 text-success rounded px-2 py-0.5 text-xs font-semibold">50</span>
    </li>
</ul>

<!-- Clicável (nav) -->
<ul class="border-default-300 divide-default-300 divide-y rounded border">
    <li>
        <a class="hover:bg-default-100 block px-4 py-3 transition" href="#">Item 1</a>
    </li>
</ul>
```

---

## Componente Blade Proposto

**Nome:** `<x-shared.list-group>` + `<x-shared.list-group-item>`
**Arquivos:**

- `resources/views/components/shared/list-group.blade.php`
- `resources/views/components/shared/list-group-item.blade.php`
  **Tipo:** Blade anônimo

### Props — `list-group`

Nenhuma. Apenas wrapper.

### Props — `list-group-item`

| Prop     | Tipo      | Obrigatório | Default | Descrição                              |
| -------- | --------- | :---------: | ------- | -------------------------------------- |
| `href`   | `?string` |     ❌      | `null`  | Se fornecido, renderiza `<a>` clicável |
| `active` | `bool`    |     ❌      | `false` | Destaque de item atual                 |

### Código

```blade
{{-- resources/views/components/shared/list-group.blade.php --}}
<ul {{ $attributes->class(['border-default-300 rounded border divide-y divide-default-300']) }}>
    {{ $slot }}
</ul>
```

```blade
{{-- resources/views/components/shared/list-group-item.blade.php --}}
@props ([
    'href' => null,
    'active' => false,
])

@php
    $baseClasses = 'px-4 py-3';
    $interactiveClasses = 'block hover:bg-default-100 transition';
    $activeClasses = 'bg-primary/10 text-primary font-semibold';
@endphp

<li>
    @if ($href)
        <a href="{{ $href }}" @class ([$baseClasses, $interactiveClasses, $activeClasses => $active])> {{ $slot }} </a>
    @else
        <div @class ([$baseClasses, $activeClasses => $active])> {{ $slot }}</div>
    @endif
</li>
```

---

## Exemplos de Uso

### Dados estruturados (ex: sidebar da ficha formando 14.12)

```blade
<x-shared.card title="Dados Pessoais">
    <x-shared.list-group>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span class="text-default-400">CPF</span>
                <span class="font-mono">{{ $formando->cpf_formatado }}</span>
            </div>
        </x-shared.list-group-item>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span class="text-default-400">Data Nasc.</span>
                <span>{{ $formando->data_nascimento->format('d/m/Y') }}</span>
            </div>
        </x-shared.list-group-item>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span class="text-default-400">Telefone</span>
                <span>{{ $formando->telefone_formatado }}</span>
            </div>
        </x-shared.list-group-item>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span class="text-default-400">E-mail</span>
                <a href="mailto:{{ $formando->email }}" class="text-primary">{{ $formando->email }}</a>
            </div>
        </x-shared.list-group-item>
    </x-shared.list-group>
</x-shared.card>
```

### Contadores por status (dashboard)

```blade
<x-shared.card title="Parcelas por Status">
    <x-shared.list-group>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span>Pendentes</span>
                <x-shared.badge variant="warning">{{ $contadores->pendentes }}</x-shared.badge>
            </div>
        </x-shared.list-group-item>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span>Pagas</span>
                <x-shared.badge variant="success">{{ $contadores->pagas }}</x-shared.badge>
            </div>
        </x-shared.list-group-item>
        <x-shared.list-group-item>
            <div class="flex items-center justify-between">
                <span>Vencidas</span>
                <x-shared.badge variant="danger">{{ $contadores->vencidas }}</x-shared.badge>
            </div>
        </x-shared.list-group-item>
    </x-shared.list-group>
</x-shared.card>
```

### Navegação lateral (alternativa a tabs)

```blade
<x-shared.list-group>
    <x-shared.list-group-item :href="route('admin.conta.perfil')" :active="request()->routeIs('admin.conta.perfil')">
        <i class="iconify tabler--user me-2"></i> Perfil
    </x-shared.list-group-item>
    <x-shared.list-group-item :href="route('admin.conta.senha')" :active="request()->routeIs('admin.conta.senha')">
        <i class="iconify tabler--lock me-2"></i> Senha
    </x-shared.list-group-item>
    <x-shared.list-group-item
        :href="route('admin.conta.notificacoes')"
        :active="request()->routeIs('admin.conta.notificacoes')"
    >
        <i class="iconify tabler--bell me-2"></i> Notificações
    </x-shared.list-group-item>
</x-shared.list-group>
```

---

## Quando Usar ✅

- **Dados key-value** de um registro (ficha do formando 14.12, detalhes do contrato)
- **Contadores por categoria** (dashboard)
- **Nav lateral** em páginas de configurações de conta

## Quando NÃO Usar ❌

- Tabela de dados com múltiplas colunas → usar `<x-shared.static-table>` ou DataTable
- Lista com imagens grandes → usar grid de cards
- Listas muito longas → usar DataTable com paginação

---

## Mapeamento no PRD

| Tela                        | Seção PRD | Uso                          |
| --------------------------- | --------- | ---------------------------- |
| Ficha do Formando (sidebar) | 14.12     | Dados pessoais, responsáveis |
| Dashboard (contadores)      | 14.2      | "Parcelas por status"        |
| Configurações conta admin   | —         | Nav lateral de settings      |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P1 (Onda 2)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/shared/list-group.blade.php`
- `resources/views/components/shared/list-group-item.blade.php`
  **Preview:** `resources/views/admin/dev/components/list-group.blade.php`

### API final consolidada

#### `x-shared.list-group`

Wrapper sem props obrigatórias. Recebe apenas `class`/atributos extras e organiza os itens com borda, `divide-y` e fundo consistente para light/dark.

#### `x-shared.list-group-item`

| Prop     | Tipo      | Default | Uso                                   |
| -------- | --------- | ------- | ------------------------------------- |
| `href`   | `?string` | `null`  | Se presente, renderiza `<a>` clicável |
| `active` | `bool`    | `false` | Destaca o item atual                  |

### Código

```blade
<x-shared.list-group>
    <x-shared.list-group-item>
        <div class="flex items-center justify-between gap-3">
            <span class="text-default-400">CPF</span>
            <span class="font-mono text-body-color">{{ $formando->cpf_formatado }}</span>
        </div>
    </x-shared.list-group-item>

    <x-shared.list-group-item :href="route('admin.conta.perfil')" :active="request()->routeIs('admin.conta.perfil')">
        <div class="flex items-center gap-2">
            <i class="iconify tabler--user text-base"></i>
            <span>Perfil</span>
        </div>
    </x-shared.list-group-item>
</x-shared.list-group>
```

---

## Notas de Adaptação

1. **Composição mínima**: pai + item resolvem os três usos principais do projeto sem abrir subfamília maior
2. **`divide-y` + fundo do card** entregam a leitura visual do Inspinia sem depender de classes utilitárias repetidas em cada item
3. **`active` state** foi fechado com `bg-primary/8`, `text-primary` e `font-semibold`, alinhado ao restante do sistema
4. **Renderização condicional `<a>` vs `<div>`** via prop `href`, mantendo a API previsível
5. **Para key-value:** continuar usando `flex justify-between` dentro do item, com label cinza e valor destacado
6. **Não confundir com `<x-shared.tabs>`**: `list-group` é vertical e serve bem como navegação auxiliar, não como navegação principal horizontal
