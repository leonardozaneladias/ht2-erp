# Wizard (Form Wizard)

**Categoria:** Form
**Origem Inspinia:** `resources/views/form/wizard.blade.php`
**Plugins JS:** Preline (stepper interno) + Livewire (controle de etapa)
**Plugins CSS:** Tailwind custom

---

## Descrição

Formulário multi-etapas com navegação progressiva. **Usado no portal do formando** (adesão com 7 etapas). No admin, o cadastro manual (14.20) usa **accordion** em vez de wizard. Este componente é primariamente **portal**, documentado aqui para referência e eventual reuso.

---

## Código Original (Inspinia — essência)

```html
<div class="wizard">
    <!-- Progress bar (stepper) -->
    <nav aria-label="Progress">
        <ol class="flex items-center justify-between">
            <li class="relative flex-1">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Contrato</div>
                </div>
            </li>
            <li class="relative flex-1">
                <div class="step current">
                    <div class="step-number">2</div>
                    <div class="step-label">Dados</div>
                </div>
            </li>
            <!-- ... -->
        </ol>
    </nav>

    <!-- Current step content -->
    <div class="wizard-body mt-6">{{-- conteúdo da etapa atual --}}</div>

    <!-- Navigation -->
    <div class="mt-6 flex justify-between">
        <button class="btn btn-secondary">Voltar</button>
        <button class="btn btn-primary">Próximo</button>
    </div>
</div>
```

---

## Componente Blade Proposto

**Nome:** `<x-portal.wizard>` + `<x-portal.wizard-step>`
**Arquivos:**

- `resources/views/components/portal/wizard.blade.php`
- `resources/views/components/portal/wizard-step.blade.php`

### Props — `wizard`

| Prop      | Tipo    | Default | Descrição                                                                        |
| --------- | ------- | ------- | -------------------------------------------------------------------------------- |
| `steps`   | `array` | —       | `[['id' => '1', 'label' => 'Contrato'], ['id' => '2', 'label' => 'Dados'], ...]` |
| `current` | `int`   | `1`     | Etapa atual (1-indexed)                                                          |

### Código (wizard container)

```blade
{{-- resources/views/components/portal/wizard.blade.php --}}
@props (['steps', 'current' => 1])

<div class="wizard">
    {{-- Progress bar --}}
    <nav aria-label="Progress das etapas" class="mb-8">
        <ol class="flex items-center">
            @foreach ($steps as $i => $step)
                @php
                    $stepNum = $i + 1;
                    $status = match(true) {
                        $stepNum < $current => 'completed',
                        $stepNum === $current => 'current',
                        default => 'upcoming',
                    };
                @endphp
                <li
                    class="relative flex-1 @if($i < count($steps) - 1) after:content-[''] after:absolute after:top-4 after:start-1/2 after:w-full after:h-0.5 {{ $status === 'completed' ? 'after:bg-primary' : 'after:bg-default-200' }} @endif"
                >
                    <div class="relative flex flex-col items-center">
                        <div
                            @class ([
                            'size-8 rounded-full flex items-center justify-center font-semibold z-10',
                            'bg-primary text-white' => $status === 'completed',
                            'bg-primary text-white ring-4 ring-primary/20' => $status === 'current',
                            'bg-default-200 text-default-600' => $status === 'upcoming',
                        ])
                        >
                            @if ($status === 'completed')
                                <i class="iconify tabler--check"></i>
                            @else
                                {{ $stepNum }}
                            @endif
                        </div>
                        <span
                            class="mt-2 text-xs {{ $status === 'current' ? 'font-semibold text-primary' : 'text-default-400' }}"
                        >
                            {{ $step['label'] }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>

    {{-- Progress % mobile --}}
    <div class="md:hidden mb-4">
        <x-shared.progress-bar :value="($current / count($steps)) * 100" />
        <p class="text-xs text-default-400 mt-1 text-center">Etapa {{ $current }} de {{ count($steps) }}</p>
    </div>

    {{-- Conteúdo --}}
    <div class="wizard-body">{{ $slot }}</div>
</div>
```

---

## Exemplos de Uso

### Real (Portal — Wizard de Adesão 7 etapas)

```php
// app/Livewire/Portal/Wizard/Index.php
class Index extends Component
{
    public int $etapa = 1;

    public array $steps = [
        ['id' => '1', 'label' => 'Contrato'],
        ['id' => '2', 'label' => 'Formando'],
        ['id' => '3', 'label' => 'Responsáveis'],
        ['id' => '4', 'label' => 'Portal'],
        ['id' => '5', 'label' => 'Pacotes'],
        ['id' => '6', 'label' => 'Pagamento'],
        ['id' => '7', 'label' => 'Confirmação'],
    ];

    public function proxima(): void
    {
        $this->validate($this->rulesEtapaAtual());
        if ($this->etapa < 7) $this->etapa++;
    }

    public function anterior(): void
    {
        if ($this->etapa > 1) $this->etapa--;
    }
}
```

```blade
<x-portal.layout title="Adesão">
    <x-portal.wizard :steps="$steps" :current="$etapa">
        @switch ($etapa)
            @case (1)
                <livewire:portal.wizard.etapas.contrato />
                @break
            @case (2)
                <livewire:portal.wizard.etapas.formando />
                @break
                {{-- ... --}}
        @endswitch

        <div class="flex justify-between mt-6">
            <x-shared.button variant="default" style="outline" wire:click="anterior" :disabled="$etapa === 1">
                Voltar
            </x-shared.button>
            <x-shared.loading-button variant="primary" wire:click="proxima">
                {{ $etapa === 7 ? 'Confirmar' : 'Próximo' }}
            </x-shared.loading-button>
        </div>
    </x-portal.wizard>
</x-portal.layout>
```

---

## Quando Usar ✅

- **Portal** — wizard de adesão (7 etapas)
- Processos longos onde usuário precisa saber onde está
- Fluxos com validação por etapa

## Quando NÃO Usar ❌

- **Admin** — usar `<x-shared.accordion>` ou `<x-shared.tab-nav>`
- Forms simples (1-3 campos) → página única

---

## Mapeamento no PRD

| Tela                  | Uso                    |
| --------------------- | ---------------------- |
| Portal adesão         | Wizard 7 etapas        |
| 14.20 Cadastro Manual | Accordion (NÃO wizard) |

---

## Classificação

| Critério         | Valor                  |
| ---------------- | ---------------------- |
| **Vai usar**     | 🟢 Sim (apenas portal) |
| **Prioridade**   | P1 (Sprint portal)     |
| **Complexidade** | Média                  |
| **Status**       | 🔴 Não iniciado        |

---

## Notas de Adaptação

1. **Namespace `portal`** — não é `shared` nem `admin`. Exclusivo do portal
2. **Stepper server-driven** — estado vem do Livewire, não JS
3. **Validação por etapa** — `rulesEtapaAtual()` retorna regras específicas
4. **Mobile fallback** — progress bar em vez de stepper horizontal (ocupa menos espaço)
5. **Não usar preline stepper** — queremos controle total do Livewire sobre a etapa atual
