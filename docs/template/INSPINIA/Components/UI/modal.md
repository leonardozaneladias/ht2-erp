# Modal

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/modals.blade.php`
**Plugins JS:** Preline 4.0.1 (`hs-overlay`, `data-hs-overlay`)
**Plugins CSS:** Classes `hs-overlay-*` do Preline

---

## Descrição

Modal dialog centralizado com backdrop. Usado para formulários rápidos, previews e confirmações **contextualizadas** com conteúdo mais rico. Diferente de `<x-admin.drawer>` (lateral), modal é **centralizado** e interrompe o flow. Tem 4 tamanhos (sm, md, lg, xl), scroll interno para conteúdo longo, e sistema de footer com botões.

> **Decisão oficial do Batch 3:** não existe `x-admin.confirm-modal` separado. Confirmações simples/yes-no ficam em `x-shared.confirm-dialog`; `<x-shared.modal>` cobre apenas casos em que o prompt precisa de markup contextual, formulário ou preview.

---

## Código Original (Inspinia — essência)

```html
<!-- Trigger -->
<button
    type="button"
    class="btn bg-primary text-white"
    data-hs-overlay="#modal-confirmacao"
    aria-controls="modal-confirmacao"
    aria-expanded="false"
    aria-haspopup="dialog"
>
    Abrir Modal
</button>

<!-- Modal -->
<div
    id="modal-confirmacao"
    class="hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 pointer-events-none fixed start-0 top-0 z-80 hidden size-full overflow-x-hidden overflow-y-auto opacity-0 transition-all"
    role="dialog"
    tabindex="-1"
    aria-labelledby="modal-confirmacao-label"
>
    <div class="hs-overlay-animation-target m-3 sm:mx-auto sm:w-full sm:max-w-lg">
        <div class="border-default-300 card pointer-events-auto flex flex-col rounded-md border">
            <!-- Header -->
            <div class="border-default-300 flex items-center justify-between border-b p-6">
                <h3 class="text-base font-semibold" id="modal-confirmacao-label">Confirmar ação</h3>
                <button type="button" data-hs-overlay="#modal-confirmacao" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <i class="iconify tabler--x text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="card-body overflow-y-auto">
                <p>Tem certeza que deseja continuar?</p>
            </div>

            <!-- Footer -->
            <div class="border-default-300 flex items-center justify-end border-t p-4">
                <button class="btn bg-light" data-hs-overlay="#modal-confirmacao" type="button">Cancelar</button>
                <button class="btn bg-primary text-white" type="button">Confirmar</button>
            </div>
        </div>
    </div>
</div>
```

### Tamanhos

- `sm`: `lg:max-w-xs`
- `md`: `sm:max-w-lg` (padrão)
- `lg`: `lg:max-w-3xl`
- `xl`: `xl:max-w-5xl`
- `full`: `w-full max-w-none`

---

## Componente Blade Proposto

**Nome:** `<x-shared.modal>`
**Arquivo:** `resources/views/components/shared/modal.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop         | Tipo      | Obrigatório | Default | Descrição                                                   |
| ------------ | --------- | :---------: | ------- | ----------------------------------------------------------- |
| `id`         | `string`  |     ✅      | —       | ID único (usado pelo `data-hs-overlay="#id"`)               |
| `title`      | `?string` |     ❌      | `null`  | Título no header                                            |
| `size`       | `string`  |     ❌      | `'md'`  | sm, md, lg, xl, full                                        |
| `scrollable` | `bool`    |     ❌      | `true`  | Body com scroll interno (útil para forms longos)            |
| `static`     | `bool`    |     ❌      | `false` | Não fecha ao clicar no backdrop (ex: confirmações críticas) |

### Slots

- `$slot` (default) — conteúdo do body
- `$footer` — botões de ação no rodapé

### Código

```blade
{{-- resources/views/components/shared/modal.blade.php --}}
@props ([
    'id',
    'title' => null,
    'size' => 'md',
    'scrollable' => true,
    'static' => false,
])

@php
    $sizeClass = match($size) {
        'sm' => 'sm:max-w-xs',
        'lg' => 'lg:max-w-3xl',
        'xl' => 'xl:max-w-5xl',
        'full' => 'max-w-none w-[95vw]',
        default => 'sm:max-w-lg',
    };

    $staticAttr = $static ? '[--overlay-backdrop:static]' : '';
@endphp

<div
    id="{{ $id }}"
    class="hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 pointer-events-none fixed start-0 top-0 z-80 hidden size-full overflow-x-hidden overflow-y-auto opacity-0 transition-all {{ $staticAttr }}"
    role="dialog"
    tabindex="-1"
    aria-labelledby="{{ $id }}-label"
>
    <div class="hs-overlay-animation-target m-3 sm:mx-auto sm:w-full {{ $sizeClass }}">
        <div class="border-default-300 pointer-events-auto flex flex-col rounded-md border card">
            @if ($title)
                <div class="border-default-300 flex items-center justify-between border-b p-6">
                    <h3 class="text-base font-semibold" id="{{ $id }}-label">{{ $title }}</h3>
                    <button
                        type="button"
                        data-hs-overlay="#{{ $id }}"
                        aria-label="Fechar"
                        class="opacity-60 hover:opacity-100"
                    >
                        <span class="sr-only">Fechar</span>
                        <i class="iconify tabler--x text-xl"></i>
                    </button>
                </div>
            @endif

            <div
                @class ([
                'card-body',
                'overflow-y-auto max-h-[70vh]' => $scrollable,
            ])
            >
                {{ $slot }}
            </div>

            @isset ($footer)
                <div class="border-default-300 flex items-center justify-end gap-2 border-t p-4">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
```

---

## Exemplos de Uso

### Confirmação simples

```blade
<x-shared.button variant="danger" data-hs-overlay="#modal-excluir-contrato"> Excluir </x-shared.button>

<x-shared.modal id="modal-excluir-contrato" title="Excluir contrato" size="sm" static>
    <p>Tem certeza que deseja excluir o contrato <strong>{{ $contrato->codigo_turma }}</strong>?</p>
    <p class="text-sm text-default-400 mt-2">Esta ação não pode ser desfeita.</p>

    <x-slot:footer>
        <x-shared.button variant="default" style="outline" data-hs-overlay="#modal-excluir-contrato">
            Cancelar
        </x-shared.button>
        <x-shared.loading-button variant="danger" wire:click="excluir"> Confirmar exclusão </x-shared.loading-button>
    </x-slot:footer>
</x-shared.modal>
```

### Form dentro de modal (Programação 14.7)

```blade
<x-shared.button variant="primary" icon="tabler--plus" data-hs-overlay="#modal-programacao">
    Nova Programação
</x-shared.button>

<x-shared.modal id="modal-programacao" title="Nova Programação de Valor" size="lg">
    <form wire:submit="salvar">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-shared.date-picker label="Data Início" wire:model="form.inicio" />
            <x-shared.date-picker label="Data Fim" wire:model="form.fim" />
            <x-shared.money-input label="Valor (R$)" wire:model="form.valor" />
            <x-shared.input type="number" label="Parcelas Máximas" wire:model="form.parcelas_max" />
        </div>
        <x-shared.input label="Descrição" wire:model="form.descricao" class="mt-4" />

        @if ($conflito)
            <x-shared.alert variant="warning" class="mt-4">
                Conflito com programação de {{ $conflito->inicio->format('d/m/Y') }} a {{ $conflito->fim->format('d/m/Y') }}
            </x-shared.alert>
        @endif
    </form>

    <x-slot:footer>
        <x-shared.button variant="default" style="outline" data-hs-overlay="#modal-programacao">
            Cancelar
        </x-shared.button>
        <x-shared.loading-button variant="primary" wire:click="salvar"> Salvar </x-shared.loading-button>
    </x-slot:footer>
</x-shared.modal>
```

### Preview (termo PDF 14.11)

```blade
<x-shared.button variant="secondary" data-hs-overlay="#modal-preview-termo"> Preview </x-shared.button>

<x-shared.modal id="modal-preview-termo" title="Preview do Termo — {{ $termo->nome }}" size="xl">
    <div class="prose max-w-none">{!! $termo->renderComDadosExemplo() !!}</div>

    <x-slot:footer>
        <x-shared.button variant="default" data-hs-overlay="#modal-preview-termo"> Fechar </x-shared.button>
    </x-slot:footer>
</x-shared.modal>
```

---

## Quando Usar ✅

- Confirmações com contexto rico (ex: explicar impacto antes de excluir ou cancelar)
- Forms rápidos contextuais (nova programação, nova condição)
- Preview de conteúdo gerado (PDF, snapshot de termo)
- Ações que não precisam de página dedicada

## Quando NÃO Usar ❌

- Formulários longos — usar drawer ou página dedicada
- Confirmações simples (Sim/Não sem contexto) — usar `<x-shared.confirm-dialog>` (SweetAlert)
- Navegação — usar rotas
- Feedback transitório — usar `<x-shared.toast>`

---

## Mapeamento no PRD

| Tela                     | Seção PRD   | Uso                                                                                         |
| ------------------------ | ----------- | ------------------------------------------------------------------------------------------- |
| Reajustes                | 14.4 Tab 5  | Modal "Novo Reajuste"                                                                       |
| Programações             | 14.7        | Modal para criar/editar                                                                     |
| Condições                | 14.8        | Modal                                                                                       |
| Descontos                | 14.9        | Modal                                                                                       |
| Termos                   | 14.11       | Modal preview                                                                               |
| Categorias               | 14.5        | Modal (alternativa: drawer)                                                                 |
| Formandos ficha ações    | 14.12 Tab 5 | Modal para baixa manual, reemitir boleto, cancelar parcela, alterar valor                   |
| Formandos ficha edição   | 14.12 Tab 1 | Modal edit dados pessoais                                                                   |
| Confirmações destrutivas | todas       | Preferir `<x-shared.confirm-dialog>`; usar modal só quando houver contexto/markup adicional |

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Prioridade**   | P1 (Onda 2)  |
| **Complexidade** | Média        |
| **Status**       | 🟢 Concluído |

---

## Código Final Blade

**Arquivo:** `resources/views/components/shared/modal.blade.php`
**Preview:** `resources/views/admin/dev/components/modal.blade.php`

### API final consolidada

| Prop         | Tipo      | Default | Observação                      |
| ------------ | --------- | ------- | ------------------------------- |
| `id`         | `string`  | —       | ID único do overlay             |
| `title`      | `?string` | `null`  | Título do header                |
| `size`       | `string`  | `md`    | `sm`, `md`, `lg`, `xl`, `full`  |
| `scrollable` | `bool`    | `true`  | Scroll interno no body          |
| `static`     | `bool`    | `false` | Não fecha ao clicar no backdrop |

### Slots finais

| Slot     | Uso             |
| -------- | --------------- |
| default  | Corpo do modal  |
| `footer` | Ações do rodapé |

### Código

```blade
<x-shared.modal id="modal-programacao" title="Nova Programação" size="lg">
    <div class="grid gap-4 md:grid-cols-2">
        <x-shared.date-picker name="inicio" label="Data início" />
        <x-shared.money-input name="valor" label="Valor" />
    </div>

    <x-slot:footer>
        <x-shared.button variant="default" appearance="outline" data-hs-overlay="#modal-programacao">
            Cancelar
        </x-shared.button>
        <x-shared.loading-button variant="primary">Salvar</x-shared.loading-button>
    </x-slot:footer>
</x-shared.modal>
```

## Notas de Adaptação

1. **Preline `data-hs-overlay`** — tanto trigger quanto botão "Fechar"
2. **Z-80** padrão — acima de topbar mas abaixo de toasts (z-100)
3. **Scrollable body** via `max-h-[70vh] overflow-y-auto` — evita modal maior que viewport
4. **Static modal** via `[--overlay-backdrop:static]` — não fecha ao clicar fora (críticas)
5. **`m-3 sm:mx-auto`** garante margem mínima em mobile
6. **Livewire + modal:** fechar modal após salvar via `$this->dispatch('close-modal', id: 'modal-xxx')` + listener JS. OR usar `wire:ignore` no modal e manipular visibilidade via Alpine
7. **ESC fecha** por padrão (Preline). Boa acessibilidade
8. **Focus trap:** Preline gerencia automaticamente
9. **Nested modals:** evitar — UX ruim. Se necessário, considerar drawer ou página dedicada
10. **`offcanvas` não foi promovido** — o papel lateral continua exclusivo do `x-admin.drawer`
