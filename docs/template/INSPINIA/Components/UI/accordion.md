# Accordion

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/accordions.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-accordion-group`, `hs-accordion`, `hs-accordion-toggle`)
**Plugins CSS:** Classes `hs-accordion-active:*` do Preline

---

## Descrição

Grupo de seções colapsáveis onde normalmente apenas **uma seção está aberta por vez**. Diferente de `<x-shared.collapse>` (item único on/off), accordion coordena múltiplos items. Útil em cadastros longos com várias seções de campos.

---

## Código Original (Inspinia — essência)

```html
<div class="hs-accordion-group divide-default-300 border-default-300 divide-y overflow-hidden rounded border">
    <div class="hs-accordion active" id="item-1">
        <button
            aria-controls="collapse-1"
            aria-expanded="true"
            class="hs-accordion-toggle hs-accordion-active:bg-default-100 flex w-full items-center justify-between px-5 py-4 font-semibold transition"
        >
            Seção 1
            <i class="iconify tabler--chevron-down hs-accordion-active:rotate-180 transition-transform"></i>
        </button>
        <div
            id="collapse-1"
            aria-labelledby="item-1"
            class="hs-accordion-content border-default-300 w-full overflow-hidden border-t transition-[height] duration-300"
            role="region"
        >
            <div class="px-5 py-4">Conteúdo da seção 1 (visível por padrão)</div>
        </div>
    </div>

    <div class="hs-accordion" id="item-2">
        <button aria-controls="collapse-2" aria-expanded="false" class="hs-accordion-toggle ...">
            Seção 2
            <i class="iconify tabler--chevron-down hs-accordion-active:rotate-180 transition-transform"></i>
        </button>
        <div
            id="collapse-2"
            class="hs-accordion-content hidden w-full overflow-hidden border-t transition-[height] duration-300"
        >
            <div class="px-5 py-4">Conteúdo oculto</div>
        </div>
    </div>
</div>
```

### Notas do markup

- Container: `hs-accordion-group` (Preline) + classes visuais (`border`, `divide-y`, `rounded`)
- Item aberto por padrão: classe `active` + body sem `hidden`
- Item fechado: sem `active` + body com `hidden`
- Chevron rotaciona via `hs-accordion-active:rotate-180`

---

## Componente Blade Proposto

**Nome:** `<x-shared.accordion>` + `<x-shared.accordion-item>`
**Arquivos:**

- `resources/views/components/shared/accordion.blade.php` (container)
- `resources/views/components/shared/accordion-item.blade.php` (item)
  **Tipo:** Blade anônimo

### Props — `accordion`

| Prop    | Tipo   | Obrigatório | Default | Descrição                          |
| ------- | ------ | :---------: | ------- | ---------------------------------- |
| `flush` | `bool` |     ❌      | `false` | Sem borda/rounded (inline no card) |

### Props — `accordion-item`

| Prop    | Tipo      | Obrigatório | Default | Descrição                          |
| ------- | --------- | :---------: | ------- | ---------------------------------- |
| `id`    | `string`  |     ✅      | —       | ID único do item                   |
| `title` | `string`  |     ✅      | —       | Título exibido no botão            |
| `open`  | `bool`    |     ❌      | `false` | Se este item inicia aberto         |
| `icon`  | `?string` |     ❌      | `null`  | Ícone Iconify à esquerda do título |

### Código — container

```blade
{{-- resources/views/components/shared/accordion.blade.php --}}
@props (['flush' => false])

<div
    @class ([
    'hs-accordion-group',
    'divide-default-300 border-default-300 divide-y rounded border overflow-hidden' => !$flush,
])
>
    {{ $slot }}
</div>
```

### Código — item

```blade
{{-- resources/views/components/shared/accordion-item.blade.php --}}
@props ([
    'id',
    'title',
    'open' => false,
    'icon' => null,
])

<div @class (['hs-accordion', 'active' => $open]) id="{{ $id }}">
    <button
        type="button"
        class="hs-accordion-toggle hs-accordion-active:bg-default-100 flex w-full items-center justify-between px-5 py-4 font-semibold transition text-start"
        aria-controls="collapse-{{ $id }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
    >
        <span class="flex items-center gap-2">
            @if ($icon)
                <i class="iconify {{ $icon }} text-lg"></i>
            @endif
            {{ $title }}
        </span>
        <i
            class="iconify tabler--chevron-down hs-accordion-active:rotate-180 transition-transform text-base shrink-0"
        ></i>
    </button>

    <div
        id="collapse-{{ $id }}"
        aria-labelledby="{{ $id }}"
        @class ([
             'hs-accordion-content border-default-300 w-full overflow-hidden border-t transition-[height] duration-300',
             'hidden' => !$open,
         ])
        role="region"
    >
        <div class="px-5 py-4">{{ $slot }}</div>
    </div>
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.accordion>
    <x-shared.accordion-item id="sec1" title="Dados do Cliente" open>
        <p>Conteúdo da seção 1</p>
    </x-shared.accordion-item>

    <x-shared.accordion-item id="sec2" title="Endereço">
        <p>Conteúdo da seção 2</p>
    </x-shared.accordion-item>

    <x-shared.accordion-item id="sec3" title="Itens">
        <p>Conteúdo da seção 3</p>
    </x-shared.accordion-item>
</x-shared.accordion>
```

### Formulário longo com seções agrupadas

```blade
<x-admin.layout title="Novo Pedido">
    <form wire:submit="salvar">
        <x-shared.accordion>
            <x-shared.accordion-item id="categoria" title="1. Categoria e Produto" icon="tabler--file-text" open>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-shared.select-search label="Categoria" wire:model.live="categoria_id" :options="$categorias" />
                    <x-shared.select-search label="Produto" wire:model.live="produto_id" :options="$produtos" />
                    <x-shared.select-search label="Variação" wire:model.live="variacao_id" :options="$variacoes" />
                </div>
            </x-shared.accordion-item>

            <x-shared.accordion-item id="dados" title="2. Dados do Cliente" icon="tabler--user">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-shared.input label="Nome Completo" wire:model="cliente.nome" />
                    <x-shared.cpf-input label="CPF" wire:model="cliente.cpf" />
                    {{-- ... --}}
                </div>
            </x-shared.accordion-item>

            @if ($pedido?->exige_endereco_cobranca)
                <x-shared.accordion-item id="end-cob" title="3. Endereço de Cobrança" icon="tabler--user-plus">
                    {{-- ... --}}
                </x-shared.accordion-item>
            @endif

            @if ($pedido?->exige_endereco_entrega)
                <x-shared.accordion-item id="end-ent" title="4. Endereço de Entrega" icon="tabler--cash">
                    {{-- ... --}}
                </x-shared.accordion-item>
            @endif

            <x-shared.accordion-item id="conta" title="5. Conta de Acesso" icon="tabler--device-laptop">
                {{-- ... --}}
            </x-shared.accordion-item>

            <x-shared.accordion-item id="itens" title="6. Seleção de Itens" icon="tabler--package">
                {{-- ... --}}
            </x-shared.accordion-item>

            <x-shared.accordion-item id="pagamento" title="7. Forma de Pagamento" icon="tabler--credit-card">
                {{-- cálculo dinâmico --}}
            </x-shared.accordion-item>

            <x-shared.accordion-item id="resumo" title="8. Resumo e Confirmação" icon="tabler--circle-check">
                {{-- resumo do pedido --}}
            </x-shared.accordion-item>
        </x-shared.accordion>

        <div class="flex justify-end gap-2 mt-6">
            <x-shared.button variant="default" style="outline" :href="route('admin.pedidos.index')">
                Cancelar
            </x-shared.button>
            <x-shared.loading-button variant="primary" type="submit" wire:target="salvar">
                Confirmar Pedido
            </x-shared.loading-button>
        </div>
    </form>
</x-admin.layout>
```

---

## Quando Usar ✅

- Formulários longos com seções agrupadas (alternativa a tabs quando vertical layout é melhor)
- FAQ/documentação do usuário

## Quando NÃO Usar ❌

- Formulários com < 4 seções → usar tabs ou card único
- Navegação por rota → usar tabs ou sidebar
- Conteúdo independente (não precisa coordenar abertura) → usar `<x-shared.collapse>` múltiplos

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Código Final Blade

**Arquivos:**

- `resources/views/components/shared/accordion.blade.php`
- `resources/views/components/shared/accordion-item.blade.php`
  **Preview:** `resources/views/admin/dev/components/accordion.blade.php`

### API final consolidada

#### `x-shared.accordion`

| Prop    | Tipo   | Default | Uso                                                          |
| ------- | ------ | ------- | ------------------------------------------------------------ |
| `flush` | `bool` | `false` | Remove borda externa quando o card pai já fornece o contorno |

#### `x-shared.accordion-item`

| Prop    | Tipo      | Default | Uso                              |
| ------- | --------- | ------- | -------------------------------- |
| `id`    | `string`  | —       | ID único do item                 |
| `title` | `string`  | —       | Título visível do gatilho        |
| `open`  | `bool`    | `false` | Estado inicial                   |
| `icon`  | `?string` | `null`  | Ícone opcional ao lado do título |

### Código

```blade
<x-shared.accordion>
    <x-shared.accordion-item id="dados" title="Dados do Cliente" icon="tabler--user" open>
        <x-shared.input name="nome" label="Nome completo" />
    </x-shared.accordion-item>

    <x-shared.accordion-item id="pagamento" title="Forma de Pagamento" icon="tabler--credit-card">
        <x-shared.select-search name="modalidade" label="Modalidade" :options="$modalidades" />
    </x-shared.accordion-item>
</x-shared.accordion>
```

## Notas de Adaptação

1. **Preline obrigatório** — usa `hs-accordion-group` + classes `hs-accordion-*`
2. **`open` inicial** — por padrão apenas 1 item (ou nenhum). NÃO tentar abrir múltiplos simultaneamente — accordion espera 1-aberto
3. **IDs únicos obrigatórios** — `collapse-{id}` precisa ser único na página
4. **Validação Livewire reabrindo seção com erro** continua sendo integração da tela, não responsabilidade do componente base
5. **`flush` mode** — sem borda, usado quando o accordion está dentro de card que já tem borda
6. **Icon opcional** nos titles — melhora escaneabilidade
7. **Preline aria updates** — ele atualiza `aria-expanded` automaticamente no toggle; começar em "false" ou "true" coerente com `open`
