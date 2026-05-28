# Custom Table (Timeline Programações)

**Categoria:** Table
**Origem Inspinia:** `resources/views/tables/custom.blade.php` (layouts exclusivos do template)
**Plugins JS:** Nenhum
**Plugins CSS:** Tailwind custom

---

## Descrição

Tabela com **visual customizado** — não é DataTable nem static padrão. Usada para casos específicos onde a estrutura tabular tradicional não serve, como:

- **Timeline de programações** (14.7) — barras visuais mostrando período ativo/futuro/expirado
- **Ficha do formando sidebar** (14.12) — layout key-value em vez de rows
- **Simulador cronograma** (14.14) — cronograma visual com destaque

Este arquivo documenta o padrão geral e dá exemplo do **timeline de programações** como caso canônico.

---

## Caso canônico: Timeline de Programações (14.7)

```blade
{{-- resources/views/components/admin/timeline-table.blade.php --}}
@props (['programacoes'])

<div class="space-y-2">
    <div class="grid grid-cols-12 text-xs font-semibold text-default-400 uppercase px-4 py-2">
        <div class="col-span-4">Período</div>
        <div class="col-span-2 text-end">Valor</div>
        <div class="col-span-1 text-center">Parcelas</div>
        <div class="col-span-3">Descrição</div>
        <div class="col-span-2 text-end">Ações</div>
    </div>

    @foreach ($programacoes as $prog)
        @php
            $status = match(true) {
                $prog->inicio->isFuture() => 'futura',
                $prog->fim->isPast() => 'expirada',
                default => 'ativa',
            };
            $badgeVariant = match($status) {
                'ativa' => 'success',
                'futura' => 'info',
                'expirada' => 'default',
            };
            $badgeLabel = strtoupper($status);
        @endphp
        <div
            @class ([
            'grid grid-cols-12 items-center px-4 py-4 rounded-lg border',
            'bg-success/5 border-success' => $status === 'ativa',
            'bg-info/5 border-info' => $status === 'futura',
            'bg-default-100 border-default-300 opacity-70' => $status === 'expirada',
        ])
        >
            <div class="col-span-4">
                <div class="flex items-center gap-2">
                    <x-shared.badge :variant="$badgeVariant" pill size="sm"> {{ $badgeLabel }} </x-shared.badge>
                    <span class="font-medium">
                        {{ $prog->inicio->format('d/m/Y') }} → {{ $prog->fim->format('d/m/Y') }}
                    </span>
                </div>
                <div class="text-xs text-default-400 mt-1">{{ $prog->inicio->diffInDays($prog->fim) }} dias</div>
            </div>

            <div class="col-span-2 text-end font-mono font-semibold">
                {{ MoneyHelper::format($prog->valor_centavos) }}
            </div>

            <div class="col-span-1 text-center">{{ $prog->parcelas_maximas }}x</div>

            <div class="col-span-3 text-sm">{{ $prog->descricao ?: '—' }}</div>

            <div class="col-span-2 text-end">
                <x-shared.button
                    variant="default"
                    size="sm"
                    icon-only
                    icon="tabler--edit"
                    wire:click="editar({{ $prog->id }})"
                />
                @unless ($status === 'expirada')
                    <x-shared.button
                        variant="default"
                        size="sm"
                        icon-only
                        icon="tabler--trash"
                        wire:click="confirmarRemocao({{ $prog->id }})"
                    />
                @endunless
            </div>
        </div>
    @endforeach

    {{-- Alerta de gap --}}
    @if ($temGap)
        <x-shared.alert variant="warning" title="Gap detectado">
            Existe um período sem programação ativa entre duas programações existentes.
        </x-shared.alert>
    @endif
</div>
```

---

## Outros casos customizados

### Sidebar de ficha (não é tabela, é key-value card)

Usar `<x-shared.list-group>` (ver `list-group.md`) para dados pessoais da ficha 14.12.

### Cronograma de parcelas (simulador 14.14)

```blade
<x-shared.static-table :headers="['#', 'Vencimento', 'Valor', 'Modalidade']" bordered>
    @foreach ($simulacao->parcelas as $i => $parcela)
        <tr @class (['bg-primary/5' => $parcela['em_destaque'] ?? false])>
            <td class="text-center font-semibold">{{ $i + 1 }}</td>
            <td>{{ $parcela['vencimento']->format('d/m/Y') }}</td>
            <td class="text-end font-mono">{{ MoneyHelper::format($parcela['valor']) }}</td>
            <td><x-shared.status-badge :enum="$parcela['modalidade']" /></td>
        </tr>
    @endforeach
</x-shared.static-table>
```

Usa `<x-shared.static-table>` — não precisa componente novo.

---

## Mapeamento no PRD

| Tela                       | Componente                 | Razão                                        |
| -------------------------- | -------------------------- | -------------------------------------------- |
| 14.7 Programações          | `<x-admin.timeline-table>` | Visual de período ativo com barras coloridas |
| 14.14 Simulador cronograma | `<x-shared.static-table>`  | Tabela simples — reutilizar static           |
| 14.12 Ficha sidebar        | `<x-shared.list-group>`    | Key-value, não tabular                       |

---

## Classificação

| Critério         | Valor                         |
| ---------------- | ----------------------------- |
| **Vai usar**     | 🟡 Sim (só timeline — 1 tela) |
| **Prioridade**   | P2 (Onda 3)                   |
| **Complexidade** | Média (visual custom)         |
| **Status**       | 🟢 Concluído                  |

---

## Notas de Adaptação

1. **Não é tabela HTML real** — usamos `<div class="grid grid-cols-12">` para ter mais controle visual
2. **Cores por status** — verde ativa, azul futura, cinza opacas para expiradas
3. **Alerta de gap** — detectar no server-side (Livewire computed) e renderizar condicionalmente
4. **Responsive:** em mobile, considerar empilhar em cards em vez de grid horizontal (overflow em 12 colunas é ruim)
5. **Este doc é precedente** para outros custom tables — cada caso é único

---

## Código Final Blade

**Arquivo:** `resources/views/components/admin/timeline-table.blade.php`
**Preview:** `resources/views/admin/dev/components/timeline-table.blade.php`

### API final consolidada

| Prop           | Tipo      | Default                             | Descrição                                       |
| -------------- | --------- | ----------------------------------- | ----------------------------------------------- | --------------------------- |
| `programacoes` | `array    | \Illuminate\Support\Collection`     | `[]`                                            | Lista de linhas da timeline |
| `gapMessage`   | `?string` | `null`                              | Mensagem opcional para alertar gaps de vigência |
| `emptyMessage` | `string`  | `'Nenhuma programação cadastrada.'` | Fallback quando não houver linhas               |

Cada item de `programacoes` pode trazer:

- `inicio`, `fim`
- `valor_formatado` ou `valor`
- `parcelas`
- `descricao`
- `status` opcional (`ativa`, `futura`, `expirada`)
- `actions` opcional, como lista de botões (`label`, `icon`, `href`, `variant`, `appearance`, `attributes`)

### Observações de implementação

- a implementação final mantém `grid` desktop para leitura rápida e empilha naturalmente no mobile
- o status é inferido por datas quando não vem explícito
- as ações ficam declarativas por item, evitando um `timeline-item` separado ou abstração prematura
- o preview cobre timeline populada e estado vazio, preservando o alerta de gap como parte da API oficial
