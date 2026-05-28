# Pagination

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/pagination.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Navegação de páginas com botões Previous/Next e números. No Laravel, a paginação nativa (`$items->links()`) renderiza uma view padrão — vamos sobrescrever para usar o estilo Inspinia. O componente Blade é um **tema customizado de pagination**, registrado via `Paginator::defaultView()`.

---

## Código Original (Inspinia — essência)

```html
<nav aria-label="Pagination" class="flex items-center -space-x-px">
    <button
        aria-label="Previous"
        class="border-default-300 hover:bg-default-100 hover:text-primary inline-flex items-center gap-x-1.5 border px-3 py-1.5 transition-all first:rounded-s-md last:rounded-e-md"
        type="button"
    >
        Previous
    </button>
    <button
        aria-current="page"
        class="border-default-300 hover:bg-default-100 hover:text-primary bg-primary flex items-center justify-center border px-3 py-1.5 text-white"
        type="button"
    >
        1
    </button>
    <button
        class="border-default-300 hover:bg-default-100 hover:text-primary flex items-center justify-center border px-3 py-1.5"
        type="button"
    >
        2
    </button>
    <button
        class="border-default-300 hover:bg-default-100 hover:text-primary flex items-center justify-center border px-3 py-1.5"
        type="button"
    >
        3
    </button>
    <button
        aria-label="Next"
        class="border-default-300 hover:bg-default-100 hover:text-primary inline-flex items-center gap-x-1.5 border px-3 py-1.5 first:rounded-s-md last:rounded-e-md"
        type="button"
    >
        Next
    </button>
</nav>
```

---

## Componente Blade Proposto

**Nome:** view de paginação customizada (não é componente Blade clássico)
**Arquivo:** `resources/views/vendor/pagination/inspinia.blade.php`
**Tipo:** View especial consumida por `LengthAwarePaginator::links('vendor.pagination.inspinia')`

### Código

```blade
{{-- resources/views/vendor/pagination/inspinia.blade.php --}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination" class="flex items-center justify-center -space-x-px">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span
                aria-disabled="true"
                class="text-default-400 border-default-300 inline-flex items-center gap-x-1.5 border px-3 py-1.5 first:rounded-s-md cursor-not-allowed"
            >
                <i class="iconify tabler--chevron-left rtl:rotate-180"></i>
                <span class="hidden sm:inline">Anterior</span>
            </span>
        @else
            <button
                type="button"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                rel="prev"
                aria-label="Página anterior"
                class="border-default-300 hover:bg-default-100 hover:text-primary inline-flex items-center gap-x-1.5 border px-3 py-1.5 transition-all first:rounded-s-md"
            >
                <i class="iconify tabler--chevron-left rtl:rotate-180"></i>
                <span class="hidden sm:inline">Anterior</span>
            </button>
        @endif

        {{-- Elements --}}
        @foreach ($elements as $element)
            {{-- "Three dots" separator --}}
            @if (is_string($element))
                <span
                    class="border-default-300 flex items-center justify-center border px-3 py-1.5 text-default-400"
                    >{{ $element }}</span
                >
            @endif
            {{-- Array of links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span
                            aria-current="page"
                            class="border-primary bg-primary text-white flex items-center justify-center border px-3 py-1.5 font-semibold"
                        >
                            {{ $page }}
                        </span>
                    @else
                        <button
                            type="button"
                            wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                            class="border-default-300 hover:bg-default-100 hover:text-primary flex items-center justify-center border px-3 py-1.5 transition-all"
                        >
                            {{ $page }}
                        </button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <button
                type="button"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                rel="next"
                aria-label="Próxima página"
                class="border-default-300 hover:bg-default-100 hover:text-primary inline-flex items-center gap-x-1.5 border px-3 py-1.5 transition-all last:rounded-e-md"
            >
                <span class="hidden sm:inline">Próxima</span>
                <i class="iconify tabler--chevron-right rtl:rotate-180"></i>
            </button>
        @else
            <span
                aria-disabled="true"
                class="text-default-400 border-default-300 inline-flex items-center gap-x-1.5 border px-3 py-1.5 last:rounded-e-md cursor-not-allowed"
            >
                <span class="hidden sm:inline">Próxima</span>
                <i class="iconify tabler--chevron-right rtl:rotate-180"></i>
            </span>
        @endif
    </nav>
@endif
```

### Registrar globalmente

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Pagination\Paginator;

public function boot(): void
{
    Paginator::defaultView('vendor.pagination.inspinia');
    Paginator::defaultSimpleView('vendor.pagination.simple-inspinia');
}
```

---

## Exemplos de Uso

### Livewire Component

```php
// app/Livewire/Admin/Contratos/Tabela.php
class Tabela extends Component
{
    use WithPagination;

    public function render()
    {
        $contratos = Contrato::query()->paginate(15);
        return view('livewire.admin.contratos.tabela', ['contratos' => $contratos]);
    }
}
```

```blade
{{-- resources/views/livewire/admin/contratos/tabela.blade.php --}}
<div>
    <table class="table">
        @foreach ($contratos as $contrato)
            {{-- linha --}}
        @endforeach
    </table>

    <div class="mt-4">
        {{ $contratos->links() }}
        {{-- Usa vendor.pagination.inspinia automaticamente --}}
    </div>
</div>
```

### Controller tradicional

```blade
<div class="mt-4">{{ $formandos->links('vendor.pagination.inspinia') }}</div>
```

---

## Quando Usar ✅

- Toda listagem com `->paginate()` (Livewire ou controller)
- DataTables **não** usam este — têm paginação própria do jQuery plugin

## Quando NÃO Usar ❌

- DataTables (14.3, 14.4, 14.6, 14.12, 14.13, 14.17, 14.18) — usam paginação do jQuery DataTables
- Listas curtas (< 20 items) → `->get()` sem paginação

---

## Mapeamento no PRD

| Tela               | Seção PRD  |         Usa?          |
| ------------------ | ---------- | :-------------------: |
| DataTables (todas) | 14.3–14.13 |          ❌           |
| Livewire tables    | —          |          ✅           |
| Relatórios         | 14.17      | ✅ (se usar Livewire) |

---

## Classificação

| Critério         | Valor                            |
| ---------------- | -------------------------------- |
| **Vai usar**     | 🟢 Sim                           |
| **Prioridade**   | P1 (Onda 2)                      |
| **Complexidade** | Média (integração com Paginator) |
| **Status**       | 🟢 Concluído                     |

---

## Notas de Adaptação

1. **Não é componente Blade tradicional** — é view do Laravel Paginator. Registrar no `AppServiceProvider`
2. **Compatibilidade dupla** — a implementação final detecta contexto Livewire e usa `wire:click` quando possível; fora disso, usa `href` normal para controllers e páginas Blade tradicionais
3. **Responsive:** texto "Anterior"/"Próxima" esconde no mobile (`hidden sm:inline`), só ícone
4. **`rtl:rotate-180`** nos chevrons para suporte RTL
5. **`aria-current="page"`** no item ativo
6. **Disabled states** como `<span>` em vez de `<button disabled>` — visual mais correto
7. **Simple view:** criar também `vendor.pagination.simple-inspinia` para `simplePaginate()` (só prev/next)

---

## Código Final Blade

**Arquivos:**

- `resources/views/vendor/pagination/inspinia.blade.php`
- `resources/views/vendor/pagination/simple-inspinia.blade.php`
- `app/Providers/AppServiceProvider.php`
  **Preview:** `resources/views/admin/dev/components/pagination.blade.php`

### API final consolidada

- `x-shared.pagination` permanece como nome de catálogo, mas a entrega real é o tema `vendor.pagination.*`
- `Paginator::defaultView('vendor.pagination.inspinia')` e `Paginator::defaultSimpleView('vendor.pagination.simple-inspinia')` foram registrados globalmente
- a view final suporta tanto controllers tradicionais quanto componentes Livewire

### Observações de implementação

- a paginação completa renderiza anterior, números e próxima com destaque do item ativo
- a versão simple mantém apenas anterior/próxima e um label com a página atual
- o preview cobre os dois modos usando `LengthAwarePaginator` e `Paginator` diretamente em Blade
