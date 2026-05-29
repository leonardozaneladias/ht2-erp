# Toast

**Categoria:** Feedback
**Origem Inspinia:** `resources/views/ui/notifications.blade.php` (Inspinia usa "notifications" como nome, mas é toast)
**Plugins JS:** helper JS leve + browser events (`window.dispatchEvent(new CustomEvent('toast', ...))`)
**Plugins CSS:** Tailwind + transições utilitárias

---

## Descrição

Notificação efêmera exibida em canto fixo da tela, com auto-dismiss opcional. Diferente de `<x-shared.alert>` (que fica no fluxo da página), o toast é **flutuante** e temporário. Usado para feedback de ação (salvou, excluiu, erro ao processar). É disparado via Livewire events ou session flash.

> **Decisão oficial do Batch 3:** `x-shared.toast` entra como família/composição. A implementação final prevista é `x-shared.toast` + `x-shared.toast-container`, com fila gerida por helper JS leve. A proposta antiga baseada em Alpine foi descartada.

---

## Código Original (Inspinia — essência)

```html
<div
    id="toast-1"
    role="alert"
    tabindex="-1"
    class="hs-removing:translate-x-5 hs-removing:opacity-0 bg-default-100 border-default-300 max-w-xs rounded-md border shadow transition duration-300"
>
    <div class="border-default-300 flex items-center border-b px-3 py-2">
        <p class="text-default-600 flex items-center gap-1.5 text-sm">
            <img class="size-4" src="/images/logo-sm.png" />
            <strong class="me-auto font-semibold">BRAND</strong>
        </p>
        <div class="ms-auto flex items-center gap-2">
            <span class="text-default-400 text-xs">agora</span>
            <button
                type="button"
                class="flex items-center justify-center opacity-50 hover:opacity-100"
                aria-label="Close"
                data-hs-remove-element="#toast-1"
            >
                <i class="iconify tabler--x text-default-800 size-6"></i>
            </button>
        </div>
    </div>
    <div class="p-3 text-sm">Pedido criado com sucesso!</div>
</div>
```

> O Inspinia **não fornece container de posicionamento nem auto-dismiss** — apenas o markup de toast individual. Precisamos agregar isso no componente Blade.

---

## Composição Blade + Helper JS Oficial

**Nome guarda-chuva:** `<x-shared.toast>`
**Implementação real:** `<x-shared.toast-container>` (container fixo, inclui no layout) + `<x-shared.toast>` (toast individual)
**Arquivos:**

- `resources/views/components/shared/toast-container.blade.php`
- `resources/views/components/shared/toast.blade.php`
- `resources/js/admin/toast.js` (helper/event bridge)

### `<x-shared.toast-container>` — Props

Nenhuma. É placeholder do container fixo no layout.

### Estrutura base do container

```blade
{{-- resources/views/components/shared/toast-container.blade.php --}}
<div data-toast-container class="fixed top-4 end-4 z-[100] flex max-w-sm flex-col gap-2"></div>
```

O helper JS escuta `window.dispatchEvent(new CustomEvent('toast', { detail: {...} }))`, injeta instâncias de `<x-shared.toast>` dentro do container e cuida de auto-dismiss/remoção. O comportamento fica centralizado em JS leve, sem exigir Alpine.

---

## Exemplos de Uso

### Incluir o container no layout (uma vez)

```blade
{{-- resources/views/components/admin/layout.blade.php --}}
<body>
    <div class="wrapper">
        {{-- sidebar, topbar, content --}}
    </div>
    <x-shared.toast-container />
    @livewireScripts
</body>
```

### Disparar a partir de Livewire

```php
// app/Livewire/Admin/Pedidos/Form.php
class Form extends Component
{
    public function save(): void
    {
        app(UpdatePedidoAction::class)->execute(...);

        $this->dispatch('toast',
            variant: 'success',
            title: 'Pedido atualizado',
            message: 'As alterações foram salvas com sucesso.',
            duration: 4000,
        );
    }
}
```

### Disparar a partir de JS puro

```js
window.dispatchEvent(
    new CustomEvent('toast', {
        detail: {
            variant: 'danger',
            message: 'Erro ao sincronizar com o gateway',
        },
    }),
);
```

### Session flash → toast

```blade
{{-- dentro de admin/layout.blade.php, antes do toast-container --}}
@if (session('success'))
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: { variant: 'success', message: @json (session('success')) },
                }),
            );
        });
    </script>
@endif

@if (session('error'))
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.dispatchEvent(
                new CustomEvent('toast', {
                    detail: { variant: 'danger', message: @json (session('error')) },
                }),
            );
        });
    </script>
@endif
```

---

## Quando Usar ✅

- Feedback imediato após ação Livewire (salvou, excluiu, processou)
- Notificações push do servidor via Laravel Echo + Livewire
- Session flash após `redirect()->route(...)->with('success', '...')`

## Quando NÃO Usar ❌

- Mensagens que o usuário precisa ler com calma → usar `<x-shared.alert>` dentro da página
- Confirmação antes de ação destrutiva → usar `<x-shared.confirm-dialog>` (SweetAlert)
- Validação de campo → usar `@error` no `<x-shared.input>`

---

## Classificação

| Critério         | Valor                                 |
| ---------------- | ------------------------------------- |
| **Vai usar**     | 🟢 Sim (padrão de feedback universal) |
| **Complexidade** | Média (composição Blade + helper JS)  |
| **Status**       | 🟢 Concluído                          |

---

## Notas de Adaptação

1. **Container fixed** posicionado top-right por padrão — pode ser configurado via prop futura (top-left, bottom-right)
2. **Sem Alpine complexo** — a fila de toasts fica em helper JS leve, alinhada com `CLAUDE.md` e com o bundle atual do admin
3. **Auto-dismiss com `setTimeout`** — configurável via `duration` (ms). Passar `duration: false` desabilita auto-dismiss
4. **Event name padrão:** `toast` (sem prefixo). Padronizar para Livewire e JS puro
5. **Z-index `z-[100]`** — acima de modais normais. Se modais precisarem subir, ajustar proporcionalmente
6. **Transição slide/fade** — aplicada pelo helper JS ao inserir/remover o nó; não depende de `x-transition`
7. **Variantes default + success/danger/warning/info** — sem 8 variantes como button/badge. Toasts precisam ser claros
8. **Session flash auto-conversão:** o script inline no layout dispara o toast uma vez ao carregar. Manter esse bridge para MVP rápido

---

## Código Final Blade + JS

- **Blade final:** `resources/views/components/shared/toast.blade.php`
- **Container final:** `resources/views/components/shared/toast-container.blade.php`
- **Helper final:** `resources/js/admin/toast.js`
- **Preview visual:** `/admin/dev/components/toast`

### API final

- `x-shared.toast`: `variant`, `title`, `message`, `icon`, `dismissible`
- `x-shared.toast-container`: sem props obrigatórias; apenas ponto global de montagem
- helper JS expõe `window.showToast({ variant, title, message, duration })`

### Exemplo final

```blade
<x-shared.toast-container />

<x-shared.toast variant="success" title="Pedido atualizado" message="As alterações foram salvas com sucesso." />
```
