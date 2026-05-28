# Progress Bar

**Categoria:** UI
**Origem Inspinia:** `resources/views/ui/progress.blade.php`
**Plugins JS:** Nenhum
**Plugins CSS:** Apenas Tailwind

---

## Descrição

Barra horizontal de progresso. Usa `role="progressbar"` + `aria-valuenow`/`aria-valuemin`/`aria-valuemax`. Suporta variantes de cor, espessura (sm, md, lg), label interno (`25%`), animação striped e uso em stacks. Usado no dashboard para meta de formandos por contrato (14.2) e no simulador de parcelamento.

---

## Código Original (Inspinia — essência)

```html
<!-- Básico -->
<div
    class="bg-light/50 flex h-2 w-full overflow-hidden rounded-full"
    role="progressbar"
    aria-valuenow="25"
    aria-valuemin="0"
    aria-valuemax="100"
>
    <div
        class="bg-primary flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap text-white transition duration-500"
        style="width: 25%"
    ></div>
</div>

<!-- Com label -->
<div class="bg-light/50 flex h-4 w-full overflow-hidden rounded-full">
    <div
        class="bg-success flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap text-white transition duration-500"
        style="width: 75%"
    >
        75%
    </div>
</div>

<!-- Striped animated -->
<div class="bg-light/50 flex h-4 w-full overflow-hidden rounded-full">
    <div
        class="bg-warning bg-stripes animate-stripes flex flex-col justify-center overflow-hidden rounded-full text-center text-xs whitespace-nowrap text-white transition duration-500"
        style="width: 50%"
    ></div>
</div>
```

Variantes no template: thin/thick, multi-color stack, label acima da barra, com porcentagem ao lado.

---

## Componente Blade Proposto

**Nome:** `<x-shared.progress-bar>`
**Arquivo:** `resources/views/components/shared/progress-bar.blade.php`
**Tipo:** Blade anônimo

### Props

| Prop        | Tipo         | Obrigatório | Default     | Descrição                                                           |
| ----------- | ------------ | :---------: | ----------- | ------------------------------------------------------------------- |
| `value`     | `int\|float` |     ✅      | —           | 0 a 100                                                             |
| `variant`   | `string`     |     ❌      | `'primary'` | primary, success, warning, danger, info                             |
| `size`      | `string`     |     ❌      | `'md'`      | sm (h-1), md (h-2), lg (h-4)                                        |
| `label`     | `?string`    |     ❌      | `null`      | Texto dentro da barra (ex: "75%"). Usa `value . '%'` se `true`      |
| `striped`   | `bool`       |     ❌      | `false`     | Barra listrada                                                      |
| `animated`  | `bool`       |     ❌      | `false`     | Animação das listras (só faz sentido com striped)                   |
| `autoColor` | `bool`       |     ❌      | `false`     | Cor automática por faixa: verde ≥80%, amarelo 50-79%, vermelho <50% |

### Código

```blade
{{-- resources/views/components/shared/progress-bar.blade.php --}}
@props ([
    'value' => 0,
    'variant' => 'primary',
    'size' => 'md',
    'label' => null,
    'striped' => false,
    'animated' => false,
    'autoColor' => false,
])

@php
    $pct = max(0, min(100, (float) $value));

    if ($autoColor) {
        $variant = match(true) {
            $pct >= 80 => 'success',
            $pct >= 50 => 'warning',
            default => 'danger',
        };
    }

    $heightClass = match($size) {
        'sm' => 'h-1',
        'lg' => 'h-4',
        default => 'h-2',
    };

    $innerClasses = collect([
        "bg-{$variant}",
        'flex flex-col justify-center rounded-full overflow-hidden',
        'text-xs text-white text-center whitespace-nowrap transition duration-500',
        $striped ? 'bg-stripes' : null,
        $animated ? 'animate-stripes' : null,
    ])->filter()->join(' ');
@endphp

<div
    {{ $attributes->class(["flex w-full bg-light/50 rounded-full overflow-hidden", $heightClass]) }}
    role="progressbar"
    aria-valuenow="{{ $pct }}"
    aria-valuemin="0"
    aria-valuemax="100"
>
    <div class="{{ $innerClasses }}" style="width: {{ $pct }}%">
        @if ($label === true)
            {{ round($pct) }}%
        @elseif (is_string($label))
            {{ $label }}
        @endif
    </div>
</div>
```

---

## Exemplos de Uso

### Básico

```blade
<x-shared.progress-bar :value="65" label />
<x-shared.progress-bar :value="30" variant="danger" size="lg" />
<x-shared.progress-bar :value="85" striped animated />
```

### Real (Dashboard 14.2 — Meta de Formandos por Contrato)

```blade
<table class="table">
    <thead>
        <tr>
            <th>Contrato</th>
            <th>Meta</th>
            <th>Aderidos</th>
            <th>% Atingido</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($contratos as $contrato)
            @php $pct = $contrato->meta_formandos > 0
                ? ($contrato->adesoes_ativas / $contrato->meta_formandos) * 100
                : 0; @endphp
            <tr>
                <td>{{ $contrato->codigo_turma }} — {{ $contrato->instituicao->nome_fantasia }}</td>
                <td>{{ $contrato->meta_formandos }}</td>
                <td>{{ $contrato->adesoes_ativas }}</td>
                <td>
                    <x-shared.progress-bar :value="$pct" auto-color label size="lg" />
                </td>
                <td>
                    <x-shared.status-badge :enum="$contrato->statusMeta()" />
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### Real (Wizard do Portal — barra 7 etapas)

```blade
@php $pct = ($etapaAtual / 7) * 100; @endphp
<x-shared.progress-bar :value="$pct" variant="primary" size="lg"> Etapa {{ $etapaAtual }} de 7 </x-shared.progress-bar>
```

---

## Quando Usar ✅

- Meta de formandos por contrato (14.2)
- Wizard progress do portal (7 etapas)
- Loading determinístico (upload em progresso)
- % de uso de quota (ex: espaço em uploads)

## Quando NÃO Usar ❌

- Loading indeterminado → usar `<x-shared.spinner>`
- Muitos passos discretos → usar stepper custom
- Indicador pequeno dentro de badge → usar apenas `<x-shared.badge>` com texto

---

## Mapeamento no PRD

| Tela          | Seção PRD         | Uso                                           |
| ------------- | ----------------- | --------------------------------------------- |
| Dashboard     | 14.2              | Meta de formandos por contrato — `auto-color` |
| Portal Wizard | Portal            | Progresso 7 etapas                            |
| Uploads       | 14.3, 14.6, 14.12 | Progresso de upload de imagem                 |

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

**Arquivo:** `resources/views/components/shared/progress-bar.blade.php`
**Preview:** `resources/views/admin/dev/components/progress-bar.blade.php`

### API final consolidada

| Prop        | Tipo                 | Default   | Observação                                                   |
| ----------- | -------------------- | --------- | ------------------------------------------------------------ |
| `value`     | `int\|float`         | `0`       | Percentual de 0 a 100                                        |
| `variant`   | `string`             | `primary` | `primary`, `success`, `warning`, `danger`, `info`, `default` |
| `size`      | `string`             | `md`      | `sm`, `md`, `lg`                                             |
| `label`     | `bool\|string\|null` | `null`    | `true` usa percentual automático; string força texto         |
| `striped`   | `bool`               | `false`   | Fundo listrado                                               |
| `animated`  | `bool`               | `false`   | Animação das listras                                         |
| `autoColor` | `bool`               | `false`   | Verde/amarelo/vermelho por faixa de valor                    |

### Slots finais

| Slot    | Uso                                |
| ------- | ---------------------------------- |
| default | Texto interno customizado da barra |

### Código

```blade
<x-shared.progress-bar :value="$pct" auto-color size="lg" label />

<x-shared.progress-bar :value="43" variant="info" size="lg"> Etapa 3 de 7 </x-shared.progress-bar>
```

---

## Notas de Adaptação

1. **`auto-color`** continua sendo a regra oficial para metas do dashboard e não exige componente admin separado
2. **Mapa explícito de variantes e tamanhos** no Blade final, sem depender de classes utilitárias geradas dinamicamente
3. **`width: N%` inline** permanece necessária para representar qualquer percentual com precisão
4. **Slot default** cobre o caso do wizard/etapas sem abrir outro wrapper só para progresso textual
5. **`role="progressbar"` + `aria-valuenow`** seguem obrigatórios por acessibilidade
6. **`striped` e `animated`** continuam válidos, reaproveitando as classes visuais já presentes no projeto
