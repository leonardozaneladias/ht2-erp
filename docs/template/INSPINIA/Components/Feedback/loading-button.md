# Loading Button

**Categoria:** Feedback
**Origem Inspinia:** `resources/views/plugins/loading-buttons.blade.php` (usa Ladda)
**Plugins JS:** nenhum plugin extra — usa diretivas Livewire (`wire:loading`) + spinner Iconify
**Plugins CSS:** classes do próprio `x-shared.button` + utilitários Tailwind

---

## Descrição

Botão que mostra indicador de loading durante submissão/processamento. Inspinia usa **Ladda** (jQuery plugin com animações expand/zoom/slide). Para o Portal ArtFinal, **vamos usar abordagem pure Livewire + Tailwind** em vez de Ladda — porque Livewire já oferece `wire:loading` e `wire:target` nativamente sem dependência de jQuery.

> **Decisão arquitetural:** Ladda vai para o parking lot. Nosso `<x-shared.loading-button>` é um `<x-shared.button>` com integração `wire:loading` e spinner inline.
>
> **Decisão oficial do Batch 3:** permanece como componente Blade anônimo em `x-shared.*`; não existe variante `x-admin.loading-button`.

---

## Código Original (Inspinia — usando Ladda)

```html
<button class="ladda-button btn bg-primary text-white" data-style="expand-left">Submit</button>
```

```js
// Init Ladda
Ladda.bind('.ladda-button');

// Ou manual
const l = Ladda.create(document.querySelector('button'));
btn.addEventListener('click', () => {
    l.start();
    fetch('/api/...').then(() => l.stop());
});
```

### Estilos Ladda disponíveis

`expand-left`, `expand-right`, `expand-up`, `expand-down`, `zoom-in`, `zoom-out`, `slide-left`, `slide-right`, `slide-up`, `slide-down`, `contract`

---

## Componente Blade Proposto (Livewire-native, sem Ladda)

**Nome:** `<x-shared.loading-button>`
**Arquivo:** `resources/views/components/shared/loading-button.blade.php`
**Tipo:** Blade anônimo

### Props

Herda todas as props de `<x-shared.button>` + :

| Prop          | Tipo      | Obrigatório | Default | Descrição                                                        |
| ------------- | --------- | :---------: | ------- | ---------------------------------------------------------------- |
| `target`      | `?string` |     ❌      | `null`  | `wire:target` específico (método Livewire)                       |
| `loadingText` | `?string` |     ❌      | `null`  | Texto exibido durante loading (default: mantém o texto original) |

### Código

```blade
{{-- resources/views/components/shared/loading-button.blade.php --}}
@props ([
    'variant' => 'primary',
    'style' => 'solid',
    'size' => 'md',
    'pill' => false,
    'icon' => null,
    'type' => 'submit',
    'target' => null,
    'loadingText' => null,
    'disabled' => false,
])

@php
    $variantClass = match($style) {
        'outline' => "border-{$variant} text-{$variant} hover:bg-{$variant} hover:text-white",
        'ghost' => "text-{$variant} hover:bg-{$variant}/10",
        default => "bg-{$variant} hover:bg-{$variant}-hover text-white",
    };

    $sizeClass = match($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $loadingAttr = $target
        ? ['wire:loading.attr' => 'disabled', 'wire:target' => $target]
        : ['wire:loading.attr' => 'disabled'];
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->class([
            'btn inline-flex items-center gap-2 justify-center',
            $variantClass,
            $sizeClass,
            $pill ? 'rounded-full' : null,
        ])->merge($loadingAttr) }}
    @disabled ($disabled)
>
    {{-- Estado idle --}}
    <span
        class="inline-flex items-center gap-2"
        @if ($target) wire:loading.remove wire:target="{{ $target }}" @else wire:loading.remove @endif
    >
        @if ($icon)
            <i class="iconify {{ $icon }}"></i>
        @endif
        {{ $slot }}
    </span>

    {{-- Estado loading --}}
    <span
        class="inline-flex items-center gap-2"
        @if ($target) wire:loading wire:target="{{ $target }}" @else wire:loading @endif
    >
        <i class="iconify tabler--loader-2 animate-spin"></i>
        {{ $loadingText ?? $slot }}
    </span>
</button>
```

---

## Exemplos de Uso

### Submit básico

```blade
<form wire:submit="salvar">
    {{-- campos --}}
    <x-shared.loading-button variant="primary" type="submit" target="salvar"> Salvar Contrato </x-shared.loading-button>
</form>
```

### Com loading text customizado

```blade
<x-shared.loading-button variant="primary" target="processar" loading-text="Processando...">
    Processar Pagamento
</x-shared.loading-button>
```

### Click action (não submit)

```blade
<x-shared.loading-button variant="danger" type="button" target="excluir" wire:click="excluir">
    Excluir Contrato
</x-shared.loading-button>
```

### Sem Livewire (fallback para JS custom)

```blade
<x-shared.loading-button
    variant="primary"
    type="submit"
    x-data="{ loading: false }"
    @click="loading = true; $el.closest('form').submit()"
>
    <span x-show="!loading">Enviar</span>
    <span x-show="loading" class="inline-flex items-center gap-2">
        <i class="iconify tabler--loader-2 animate-spin"></i>
        Enviando...
    </span>
</x-shared.loading-button>
```

---

## Quando Usar ✅

- **Todo botão de submit** em forms Livewire
- **Ações que levam > 500ms** (cálculos, API calls, uploads)
- **Botões destrutivos** onde o usuário precisa ver feedback

## Quando NÃO Usar ❌

- Botões instantâneos (toggle, abrir modal) → usar `<x-shared.button>`
- Links de navegação → usar `<x-shared.button>` com `href`
- Ações dentro de dropdowns → usar `<x-shared.dropdown-item>` (loading inline raramente necessário)

---

## Mapeamento no PRD

| Tela              | Seção PRD   | Uso                                                       |
| ----------------- | ----------- | --------------------------------------------------------- |
| Login             | 14.1        | "Entrar" com loading durante auth                         |
| Instituições form | 14.3        | "Salvar"                                                  |
| Contratos form    | 14.4        | "Salvar" em cada tab                                      |
| Produtos form     | 14.6        | "Salvar"                                                  |
| Termos form       | 14.11       | "Salvar" + "Preview"                                      |
| Formandos ficha   | 14.12 Tab 5 | "Dar Baixa Manual", "Reemitir Boleto", "Cancelar Parcela" |
| Parcelas          | 14.13       | "Dar Baixa em Lote"                                       |
| Configurações     | 14.15       | "Salvar Configurações"                                    |
| Reajustes         | 14.4 Tab 5  | "Aplicar Reajuste"                                        |

---

## Classificação

| Critério         | Valor                       |
| ---------------- | --------------------------- |
| **Vai usar**     | 🟢 Sim                      |
| **Prioridade**   | P1 (Onda 2)                 |
| **Complexidade** | Média (integração Livewire) |
| **Status**       | 🟢 Concluído                |

---

## Notas de Adaptação

1. **Ladda descartado** — jQuery dependency desnecessária quando temos Livewire nativo
2. **`wire:loading.attr="disabled"`** — Livewire desabilita o botão automaticamente
3. **`wire:target`** específico — evita "loading state" em cliques em outros botões da mesma página
4. **Spinner via Iconify** (`tabler--loader-2 animate-spin`) — sem dependência extra
5. **`wire:loading.remove`** esconde o estado idle durante loading
6. **Fallback Alpine** — para casos sem Livewire, `x-show` com estado local
7. **Não usar `disabled` prop diretamente** — o Livewire aplica via `wire:loading.attr`
8. **Rollback de Ladda no parking lot:** se aparecer algum form sem Livewire que precise de loading, Ladda ainda está disponível

---

## Código Final Blade

- **Arquivo final:** `resources/views/components/shared/loading-button.blade.php`
- **Preview visual:** `/admin/dev/components/loading-button`
- **Base reutilizada:** `x-shared.button`

### API final

- herda `variant`, `appearance`, `size`, `pill`, `block`, `icon`, `type`
- adiciona `target` e `loadingText`
- mantém o comportamento real via `wire:loading` e `wire:target`

### Exemplo final

```blade
<x-shared.loading-button variant="primary" type="submit" target="salvar" loading-text="Salvando...">
    Salvar contrato
</x-shared.loading-button>
```
