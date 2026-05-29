# Alert

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/alerts.blade.php`
**Plugins JS:** Preline 4.0.1 (apenas se dismissible)
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Banner colorido para mensagens informativas/de status dentro da página. Diferente de toast (ver `toast.md`), alert é **persistente** e fica no fluxo da página. Tem 8 variantes de cor (primary, secondary, success, danger, warning, info, light, dark), duas intensidades (`bg-{color}/15 text-{color}` soft, ou `bg-{color} text-white` solid), e opção de dismissível (X para fechar).

---

## Código Original (Inspinia — essência)

```html
<!-- Soft (variante padrão) -->
<div class="bg-primary/15 text-primary flex items-center rounded px-4 py-3" role="alert">
    This is a primary alert—something important you should know!
</div>

<!-- Solid -->
<div class="bg-success flex items-center rounded px-4 py-3 text-white" role="alert">
    Success! Your action was completed successfully.
</div>

<!-- Com link -->
<div class="bg-info/15 text-info flex items-center gap-1 rounded px-4 py-3" role="alert">
    Need more info? Check out
    <a class="font-bold" href="#">this info link</a>
    for important details.
</div>

<!-- Com ícone -->
<div class="bg-warning/15 text-warning flex items-center gap-2 rounded px-4 py-3" role="alert">
    <i class="iconify tabler--alert-triangle text-lg"></i>
    Warning! Please double-check your inputs.
</div>

<!-- Dismissible -->
<div class="bg-primary/15 text-primary flex items-center rounded px-4 py-3" id="dismiss-alert-1" role="alert">
    <span class="grow">Mensagem...</span>
    <button class="btn-close" data-hs-remove-element="#dismiss-alert-1">
        <i class="iconify tabler--x"></i>
    </button>
</div>
```

---

## Componente Blade Final

**Nome:** `<x-shared.alert>`
**Arquivo:** `resources/views/components/shared/alert.blade.php`
**Tipo:** Blade anônimo
**Preview visual:** `resources/views/admin/dev/components/alert.blade.php`

### Props

| Prop          | Tipo                 | Obrigatório | Default  | Descrição                                                                          |
| ------------- | -------------------- | :---------: | -------- | ---------------------------------------------------------------------------------- |
| `variant`     | `string`             |     ❌      | `'info'` | primary, secondary, success, danger, warning, info, light, dark                    |
| `solid`       | `bool`               |     ❌      | `false`  | Fundo sólido em vez de soft                                                        |
| `icon`        | `string\|bool\|null` |     ❌      | auto     | Ícone Iconify. Se `false`, oculta o ícone. Se `null`, usa ícone padrão por variant |
| `dismissible` | `bool`               |     ❌      | `false`  | Mostra botão X para fechar                                                         |
| `title`       | `?string`            |     ❌      | `null`   | Título opcional em negrito na primeira linha                                       |

### Slots

- `$slot`: Conteúdo da mensagem (texto + links aceitos)

### Código

```blade
{{-- resources/views/components/shared/alert.blade.php --}}
@props ([
    'variant' => 'info',
    'solid' => false,
    'icon' => null,
    'dismissible' => false,
    'title' => null,
])

@php
    $variants = [
        'primary' => [
            'soft' => 'bg-primary/15 text-primary',
            'solid' => 'bg-primary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'secondary' => [
            'soft' => 'bg-secondary/15 text-secondary',
            'solid' => 'bg-secondary text-white',
            'icon' => 'tabler--info-circle',
        ],
        'success' => [
            'soft' => 'bg-success/15 text-success',
            'solid' => 'bg-success text-white',
            'icon' => 'tabler--circle-check',
        ],
        'danger' => [
            'soft' => 'bg-danger/15 text-danger',
            'solid' => 'bg-danger text-white',
            'icon' => 'tabler--alert-octagon',
        ],
        'warning' => [
            'soft' => 'bg-warning/15 text-warning',
            'solid' => 'bg-warning text-white',
            'icon' => 'tabler--alert-triangle',
        ],
        'info' => [
            'soft' => 'bg-info/15 text-info',
            'solid' => 'bg-info text-white',
            'icon' => 'tabler--info-circle',
        ],
        'light' => [
            'soft' => 'border border-default-300 bg-light/60 text-default-700',
            'solid' => 'bg-light text-dark',
            'icon' => 'tabler--info-circle',
        ],
        'dark' => [
            'soft' => 'bg-dark/15 text-dark',
            'solid' => 'bg-dark text-white',
            'icon' => 'tabler--info-circle',
        ],
    ];

    $variant = array_key_exists($variant, $variants) ? $variant : 'info';
    $tone = $solid ? 'solid' : 'soft';
    $resolvedIcon = $icon === false ? null : ($icon ?: $variants[$variant]['icon']);
    $wrapperId = $attributes->get('id') ?: 'alert-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $wrapperAttributes = $attributes
        ->class([
            'hs-removing:translate-x-5 hs-removing:opacity-0',
            'rounded px-4 py-3 transition duration-300',
            'flex items-start gap-3',
            $variants[$variant][$tone],
            '[&_a]:font-semibold [&_a]:underline [&_a]:underline-offset-2',
            '[&_a:hover]:opacity-80',
        ])
        ->merge([
            'id' => $wrapperId,
            'role' => 'alert',
        ]);
@endphp

<div {{ $wrapperAttributes }}>
    @if ($resolvedIcon)
        <span class="mt-0.5 inline-flex shrink-0 items-center justify-center">
            <i class="iconify {{ $resolvedIcon }} text-xl"></i>
        </span>
    @endif

    <div class="min-w-0 grow text-sm leading-5">
        @if ($title)
            <p class="mb-1 font-semibold">{{ $title }}</p>
        @endif

        <div class="space-y-2">{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button
            type="button"
            class="ms-auto inline-flex size-8 shrink-0 items-center justify-center rounded-full opacity-70 transition hover:opacity-100"
            aria-label="Fechar"
            data-hs-remove-element="#{{ $wrapperId }}"
        >
            <i class="iconify tabler--x text-lg"></i>
        </button>
    @endif
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.alert variant="success"> Pedido criado com sucesso. </x-shared.alert>

<x-shared.alert variant="warning" title="Atenção"> Este cliente possui 5 pedidos vinculados. </x-shared.alert>

<x-shared.alert variant="danger" dismissible> Erro ao processar pagamento. Tente novamente. </x-shared.alert>
```

### Real (Dashboard — Seção Alertas do Sistema)

```blade
@if ($alertas->pedidosSemProcessamento > 0)
    <x-shared.alert variant="warning" title="Pedidos sem processamento">
        Existem <strong>{{ $alertas->pedidosSemProcessamento }}</strong> pedidos sem processamento.
        <a href="{{ route('admin.pedidos.index', ['filter' => 'sem-processamento']) }}" class="font-bold">Revisar</a>
    </x-shared.alert>
@endif

@if ($alertas->prazosVencendo15Dias > 0)
    <x-shared.alert variant="info" title="Prazos vencendo">
        <strong>{{ $alertas->prazosVencendo15Dias }}</strong> prazos vencem nos próximos 15 dias.
    </x-shared.alert>
@endif

@if ($alertas->pagamentosVencidos > 0)
    <x-shared.alert variant="danger" title="Pagamentos vencidos sem tratamento">
        <strong>{{ $alertas->pagamentosVencidos }}</strong> pagamentos vencidos aguardando ação.
        <a href="{{ route('admin.financeiro.pagamentos.index', ['status' => 'vencido']) }}" class="font-bold"
            >Ver todas</a
        >
    </x-shared.alert>
@endif
```

---

## Quando Usar ✅

- Feedback persistente dentro de uma página (após uma ação)
- Alertas do sistema no dashboard
- Avisos contextuais em formulários ("Esta ação é irreversível")
- Sessão flash messages (`session()->flash('success', '...')` renderizado como alert no topo)

## Quando NÃO Usar ❌

- Feedback efêmero de ação → usar `<x-shared.toast>` (aparece e some)
- Confirmação de ação destrutiva → usar `<x-shared.confirm-dialog>` (SweetAlert)
- Validação de campo → usar o erro do próprio `<x-shared.input>` via `@error`

---

## Classificação

| Critério         | Valor        |
| ---------------- | ------------ |
| **Vai usar**     | 🟢 Sim       |
| **Complexidade** | Simples      |
| **Status**       | 🟢 Concluído |

---

## Notas de Adaptação

1. **Sem safelist manual:** a implementação final usa mapas explícitos de classes por variant/tone, então não depende de concatenação dinâmica de utility classes
2. **Ícone automático por variant** — `success` vira check, `warning` vira triangle, `danger` vira octagon. `:icon="false"` desliga o ícone quando necessário
3. **ID aleatório para dismissible** — usa o `id` passado via atributo quando existir; caso contrário gera um identificador único para o `data-hs-remove-element`
4. **`data-hs-remove-element`** é do Preline — remove o elemento do DOM ao clicar no X e já está disponível via `resources/js/vendor.js`
5. **Links internos** já recebem destaque via seletor `[&_a]`, evitando repetição de classes no conteúdo do slot
6. **Preview pronto:** acessar `/admin/dev/components/alert` para validar as variantes implementadas
