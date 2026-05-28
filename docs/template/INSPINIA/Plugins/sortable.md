# Sortable (SortableJS)

**Categoria:** Plugin
**Origem Inspinia:** `resources/views/plugins/sortable.blade.php`
**Plugins JS:** SortableJS 1.15.6
**Uso no ArtFinal:** 14.10 — Drag-and-drop de termos do produto

---

## Descrição

Biblioteca para reordenar listas por drag-and-drop. Usado no **14.10 Termos do Produto** para permitir que o admin arraste e solte os termos, atualizando a ordem de exibição (`produto_termos.ordem`).

---

## Código Original (Inspinia — essência)

```html
<ul id="sortable-list">
    <li data-id="1">Termo Geral</li>
    <li data-id="2">Termo de Pagamento</li>
    <li data-id="3">Termo de Privacidade</li>
</ul>

<script>
    import Sortable from 'sortablejs';

    new Sortable(document.getElementById('sortable-list'), {
        animation: 150,
        ghostClass: 'bg-primary/10',
        onEnd(evt) {
            // ordem mudou — persistir
        },
    });
</script>
```

---

## Componente Blade Proposto

**Nome:** `<x-admin.sortable-list>`
**Arquivo:** `resources/views/components/admin/sortable-list.blade.php`

### Props

| Prop     | Tipo     | Default | Descrição                                                            |
| -------- | -------- | ------- | -------------------------------------------------------------------- |
| `id`     | `string` | auto    | ID do container                                                      |
| `target` | `string` | —       | Método Livewire chamado com nova ordem: `atualizarOrdem(array $ids)` |

### Código

```blade
{{-- resources/views/components/admin/sortable-list.blade.php --}}
@props ([
    'id' => 'sortable-' . \Illuminate\Support\Str::random(6),
    'target',
])

<div
    id="{{ $id }}"
    wire:ignore
    x-data
    x-init="
         const sortable = new Sortable($el, {
             animation: 150,
             ghostClass: 'bg-primary/10',
             handle: '.drag-handle',
             onEnd: (evt) => {
                 const ids = Array.from($el.children).map(el => el.dataset.id)
                 $wire.call('{{ $target }}', ids)
             }
         })
     "
>
    {{ $slot }}
</div>
```

---

## Exemplo de Uso

### Real (14.10 Reordenar termos do produto)

```blade
<x-shared.card title="Termos Vinculados">
    <x-admin.sortable-list target="atualizarOrdemTermos">
        @foreach ($produto->termos()->orderBy('ordem')->get() as $termo)
            <div
                data-id="{{ $termo->id }}"
                class="flex items-center gap-3 p-3 border-b last:border-b-0 bg-card hover:bg-default-50"
            >
                <i class="iconify tabler--grip-vertical drag-handle cursor-move text-default-400"></i>
                <span class="grow">
                    <strong>{{ $termo->nome }}</strong>
                    <span class="text-xs text-default-400 ms-2">v{{ $termo->versao }}</span>
                </span>
                <button wire:click="desvincular({{ $termo->id }})" class="btn btn-icon btn-sm text-danger">
                    <i class="iconify tabler--x"></i>
                </button>
            </div>
        @endforeach
    </x-admin.sortable-list>
</x-shared.card>
```

```php
// app/Livewire/Admin/Produtos/Termos.php
class Termos extends Component
{
    public Produto $produto;

    public function atualizarOrdemTermos(array $ids): void
    {
        foreach ($ids as $ordem => $termoId) {
            ProdutoTermo::where('produto_id', $this->produto->id)
                ->where('termo_id', $termoId)
                ->update(['ordem' => $ordem + 1]);
        }

        $this->dispatch('toast',
            variant: 'success',
            message: 'Ordem dos termos atualizada.'
        );
    }
}
```

---

## Mapeamento no PRD

| Tela                    | Seção PRD | Uso                                                |
| ----------------------- | --------- | -------------------------------------------------- |
| 14.10 Termos do Produto | 14.10     | Reordenar via drag-and-drop                        |
| Parking lot             | —         | Reordenar qualquer lista (menus, categorias, etc.) |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P5 (Onda 6)  |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Código Final Blade

**Arquivo:** `resources/views/components/admin/sortable-list.blade.php`
**Helper JS:** `resources/js/admin/sortable.js`
**Preview:** `resources/views/admin/dev/components/sortable-list.blade.php`

### API final consolidada

| Prop         | Tipo      | Default         | Observação                               |
| ------------ | --------- | --------------- | ---------------------------------------- |
| `id`         | `?string` | auto            | Gera `sortable-*` quando omitido         |
| `target`     | `?string` | `null`          | Método Livewire chamado com a nova ordem |
| `handle`     | `string`  | `.drag-handle`  | Área efetiva de arraste                  |
| `animation`  | `int`     | `150`           | Animação do SortableJS                   |
| `ghostClass` | `string`  | `bg-primary/10` | Classe aplicada ao item fantasma         |

### Código

```blade
<x-admin.sortable-list target="atualizarOrdemTermos">
    @foreach ($produto->termos as $termo)
        <div data-id="{{ $termo->id }}" class="flex items-center gap-3 border-b px-4 py-3 last:border-b-0">
            <i class="iconify tabler--grip-vertical drag-handle cursor-grab text-xl text-default-400"></i>
            <div class="grow">
                <p class="font-semibold">{{ $termo->nome }}</p>
                <p class="text-xs text-default-400">v{{ $termo->versao }}</p>
            </div>
        </div>
    @endforeach
</x-admin.sortable-list>
```

## Notas de Adaptação

1. **`wire:ignore`** obrigatório — SortableJS manipula o DOM, Livewire não deve re-renderizar
2. **Handle `.drag-handle`** — só o ícone é área de arraste (evita arrastar ao clicar no botão delete)
3. **`data-id`** em cada item — usado para identificar após reordenar
4. **Implementação final usa helper JS do admin** em vez de Alpine inline
5. **Evento local `sortable:changed`** foi adicionado para previews e usos sem Livewire
6. **Persistência real** continua responsabilidade da tela/método Livewire
