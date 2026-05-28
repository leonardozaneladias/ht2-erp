# Static Table

**Categoria:** Table
**Origem Inspinia:** `resources/views/tables/static.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Classe `.table` do Inspinia

---

## Descrição

Tabela HTML pura — **sem JavaScript**, sem paginação, sem busca. Usada para listas curtas (< 30 linhas), sub-tabelas dentro de tabs (cursos, períodos, condições), e tabelas inline em modais/cards. Muito mais leve que `<x-admin.data-table>`.

---

## Código Original (Inspinia — essência)

```html
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>João Silva</td>
            <td>joao@example.com</td>
            <td><span class="badge bg-success">Ativo</span></td>
        </tr>
    </tbody>
</table>
```

Variantes do Inspinia: `.table-bordered`, `.table-striped`, `.table-hover`, `.table-sm` (compacta), `.table-borderless`.

---

## Componente Blade Proposto

**Nome:** `<x-shared.static-table>`
**Arquivo:** `resources/views/components/shared/static-table.blade.php`

### Props

| Prop           | Tipo     | Default                         | Descrição                                      |
| -------------- | -------- | ------------------------------- | ---------------------------------------------- |
| `headers`      | `?array` | `null`                          | Se fornecido, gera `<thead>` automaticamente   |
| `bordered`     | `bool`   | `false`                         | Bordas em todas as células                     |
| `striped`      | `bool`   | `false`                         | Linhas alternadas                              |
| `hover`        | `bool`   | `true`                          | Destaque na linha ao passar mouse              |
| `compact`      | `bool`   | `false`                         | Padding reduzido                               |
| `emptyMessage` | `string` | `'Nenhum registro encontrado.'` | Fallback quando não houver linhas renderizadas |

### Código

```blade
{{-- resources/views/components/shared/static-table.blade.php --}}
@props ([
    'headers' => null,
    'bordered' => false,
    'striped' => false,
    'hover' => true,
    'compact' => false,
])

<div class="overflow-x-auto">
    <table
        {{ $attributes->class([
        'table',
        'table-bordered' => $bordered,
        'table-striped' => $striped,
        'table-hover' => $hover,
        'table-sm' => $compact,
        'w-full',
    ]) }}
    >
        @if ($headers)
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @elseif (isset($head))
            <thead>
                {{ $head }}
            </thead>
        @endif

        <tbody>
            {{ $slot }}
        </tbody>

        @isset ($foot)
            <tfoot>
                {{ $foot }}
            </tfoot>
        @endisset
    </table>
</div>
```

---

## Exemplos de Uso

### Headers via prop array

```blade
<x-shared.static-table :headers="['Curso', 'Ativo', 'Ações']" bordered hover>
    @foreach ($contrato->cursos as $curso)
        <tr>
            <td>{{ $curso->nome }}</td>
            <td>
                <x-shared.badge :variant="$curso->ativo ? 'success' : 'default'">
                    {{ $curso->ativo ? 'Sim' : 'Não' }}
                </x-shared.badge>
            </td>
            <td>
                <button class="btn btn-icon btn-sm" wire:click="editarCurso({{ $curso->id }})">
                    <i class="iconify tabler--edit"></i>
                </button>
            </td>
        </tr>
    @endforeach
</x-shared.static-table>
```

### Header via slot

```blade
<x-shared.static-table striped>
    <x-slot:head>
        <tr>
            <th colspan="2">Dados do Formando</th>
        </tr>
        <tr>
            <th>Campo</th>
            <th>Valor</th>
        </tr>
    </x-slot:head>

    <tr>
        <td>Nome</td>
        <td>{{ $formando->nome_completo }}</td>
    </tr>
    <tr>
        <td>CPF</td>
        <td>{{ $formando->cpf_formatado }}</td>
    </tr>
</x-shared.static-table>
```

### Com footer (totalizadores)

```blade
<x-shared.static-table :headers="['Produto', 'Valor', 'Qtd', 'Subtotal']" bordered>
    @foreach ($itens as $item)
        <tr>
            <td>{{ $item->produto->nome }}</td>
            <td class="text-end">{{ MoneyHelper::format($item->valor) }}</td>
            <td class="text-center">{{ $item->quantidade }}</td>
            <td class="text-end">{{ MoneyHelper::format($item->subtotal) }}</td>
        </tr>
    @endforeach

    <x-slot:foot>
        <tr class="font-bold">
            <td colspan="3" class="text-end">Total</td>
            <td class="text-end">{{ MoneyHelper::format($itens->sum('subtotal')) }}</td>
        </tr>
    </x-slot:foot>
</x-shared.static-table>
```

---

## Mapeamento no PRD

| Tela                    | Uso                               |
| ----------------------- | --------------------------------- |
| 14.4 Contratos Tab 3    | Tabela de Cursos                  |
| 14.4 Contratos Tab 4    | Tabela de Períodos                |
| 14.8 Condições          | Listagem em card                  |
| 14.9 Descontos          | Listagem em card                  |
| 14.10 Termos do Produto | Lista de vínculos com drag handle |
| 14.12 Formando Tab 3    | Portal Users vinculados           |
| 14.12 Formando Tab 6    | Termos aceitos                    |
| 14.14 Simulador         | Cronograma de parcelas            |
| 14.20 Cadastro Manual   | Resumo da adesão                  |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P2 (Onda 3)  |
| **Complexidade** | Trivial      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **`overflow-x-auto`** wrapper — tabelas em mobile rolam horizontalmente
2. **Sem DataTables** — para simplicidade e performance em listas curtas
3. **Para > 30 linhas** — considerar `<x-admin.data-table>` ou pagination
4. **Classe `.table`** vem do CSS do Inspinia — traz padding, border-collapse, etc.
5. **Responsive:** para mobile real, considerar `.table-responsive-stack` (CSS custom que empilha cada row como card)

---

## Código Final Blade

**Arquivo:** `resources/views/components/shared/static-table.blade.php`
**Preview:** `resources/views/admin/dev/components/static-table.blade.php`

### API final consolidada

- `headers` continua gerando `<thead>` automaticamente quando fornecido
- `head` e `foot` permanecem disponíveis como named slots para estruturas mais complexas
- `emptyMessage` foi adicionado na implementação final para cobrir tabelas vazias sem markup extra na tela consumidora

### Observações de implementação

- a tabela final mantém wrapper `overflow-x-auto` e respeita classes `.table-*` do tema
- o preview cobre três cenários: headers via prop, `head/foot` customizados e estado vazio
- o componente foi mantido propositalmente leve, sem bridge JS nem comportamento de DataTables
