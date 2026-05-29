# Confirm Dialog (SweetAlert2 Helper)

**Categoria:** Feedback
**Origem Inspinia:** `resources/views/plugins/sweet-alerts.blade.php`
**Plugins JS:** SweetAlert2 11.22.5
**Plugins CSS:** CSS próprio do SweetAlert2 (importado via `import 'sweetalert2/dist/sweetalert2.min.css'`)

---

## Descrição

Modal de confirmação estilizado para ações destrutivas ou importantes. Diferente de `<x-shared.modal>` (flexível, custom), o confirm-dialog usa **SweetAlert2** para um prompt padronizado Yes/No com ícone, título, mensagem. Usado em confirmações antes de excluir, cancelar ou inativar registros.

> **Decisão oficial do Batch 3:** `x-shared.confirm-dialog` permanece no lote, mas como helper JS/bridge Livewire. Não existe arquivo Blade próprio para ele e não existe `x-admin.confirm-modal` na fonte oficial.

---

## Código Original (SweetAlert2)

```js
import Swal from 'sweetalert2';

Swal.fire({
    title: 'Excluir pedido?',
    text: 'Esta ação não pode ser desfeita.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sim, excluir',
    cancelButtonText: 'Cancelar',
}).then((result) => {
    if (result.isConfirmed) {
        // Executar ação
    }
});
```

Icons disponíveis: `success`, `error`, `warning`, `info`, `question`.

---

## Abordagem do projeto: helper JS + dispatch Livewire

Em vez de chamar `Swal.fire()` diretamente em cada lugar, criar um **helper JS global** + um **bridge Livewire** para disparar confirmações via eventos.

### Helper JS (`resources/js/admin/confirm.js`)

```js
import Swal from 'sweetalert2';

// Cores alinhadas com o skin default do Inspinia
const baseStyle = {
    buttonsStyling: false,
    customClass: {
        confirmButton: 'btn bg-primary hover:bg-primary-hover text-white mx-1',
        cancelButton: 'btn bg-light hover:bg-light-hover text-dark mx-1',
        actions: 'flex gap-2',
    },
    reverseButtons: true,
};

export function confirmDestructive({ title, text, confirmText = 'Sim, confirmar' }) {
    return Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        customClass: {
            ...baseStyle.customClass,
            confirmButton: 'btn bg-danger hover:bg-danger-hover text-white mx-1',
            cancelButton: 'btn bg-light text-dark mx-1',
        },
        reverseButtons: true,
        buttonsStyling: false,
    });
}

export function confirmInfo({ title, text, confirmText = 'Confirmar' }) {
    return Swal.fire({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        ...baseStyle,
    });
}

export function alertSuccess({ title, text }) {
    return Swal.fire({ title, text, icon: 'success', ...baseStyle });
}

export function alertError({ title, text }) {
    return Swal.fire({ title, text, icon: 'error', ...baseStyle });
}

// Expor no window para uso via Alpine/Livewire
window.confirmDestructive = confirmDestructive;
window.confirmInfo = confirmInfo;
window.alertSuccess = alertSuccess;
window.alertError = alertError;
```

### Bridge Livewire (em `resources/js/admin.js`)

```js
document.addEventListener('livewire:init', () => {
    Livewire.on('confirm', async (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        const fn = data.destructive ? window.confirmDestructive : window.confirmInfo;
        const result = await fn(data);
        if (result.isConfirmed && data.onConfirm) {
            Livewire.dispatch(data.onConfirm, data.params || {});
        }
    });
});
```

---

## Exemplos de Uso

### Via Livewire dispatch (padrão recomendado)

```php
// app/Livewire/Admin/Pedidos/Tabela.php
class Tabela extends Component
{
    public function confirmarInativacao(int $pedidoId): void
    {
        $pedido = Pedido::findOrFail($pedidoId);
        $itens = $pedido->itens()->ativos()->count();

        $this->dispatch('confirm',
            destructive: true,
            title: 'Inativar pedido?',
            text: $itens > 0
                ? "Este pedido possui {$itens} itens vinculados. Tem certeza?"
                : 'Esta ação pode ser revertida depois.',
            confirmText: 'Sim, inativar',
            onConfirm: 'inativar',
            params: ['id' => $pedidoId],
        );
    }

    public function inativar(int $id): void
    {
        Pedido::findOrFail($id)->update(['ativo' => false]);
        $this->dispatch('toast', variant: 'success', message: 'Pedido inativado.');
    }
}
```

### Direto via Alpine

```blade
<button
    type="button"
    @click="
            const result = await window.confirmDestructive({
                title: 'Excluir cliente?',
                text: 'Esta ação removerá todo o histórico.',
                confirmText: 'Sim, excluir'
            })
            if (result.isConfirmed) $wire.excluir({{ $cliente->id }})
        "
    class="btn btn-danger"
>
    Excluir
</button>
```

### Success alert após ação

```php
$this->dispatch('alert',
    type: 'success',
    title: 'Operação concluída',
    text: 'Os valores em aberto foram recalculados.',
);
```

---

## Quando Usar ✅

- **Confirmação antes de ação destrutiva** (excluir, cancelar, inativar)
- **Confirmação antes de ação irreversível** (operações que recalculam ou migram dados)
- **Sucesso crítico** que merece mais destaque que toast (operações bancárias, migrações)
- **Erros críticos** que precisam de atenção do usuário

## Quando NÃO Usar ❌

- Feedback efêmero → usar `<x-shared.toast>`
- Mensagem persistente na página → usar `<x-shared.alert>`
- Form contextual → usar `<x-shared.modal>` (permite mais markup)
- Toda ação menor → sobrecarrega o UX

---

## Classificação

| Critério         | Valor                      |
| ---------------- | -------------------------- |
| **Vai usar**     | 🟢 Sim                     |
| **Complexidade** | Média (bridge JS + helper) |
| **Status**       | 🟢 Concluído               |

---

## Notas de Adaptação

1. **SweetAlert2 via npm** — `sweetalert2@11.22.5` já no package.json do Inspinia. Importar em `resources/js/admin.js`
2. **`buttonsStyling: false` + `customClass`** para usar nossos botões Tailwind em vez dos estilos padrão do SweetAlert
3. **`reverseButtons: true`** — cancelar à esquerda, confirmar à direita (padrão ocidental)
4. **Textos em PT-BR** hardcoded no helper
5. **Livewire bridge via `$this->dispatch('confirm', ...)`** — centralize confirmações no servidor
6. **NÃO misturar com toast** — toast é efêmero, confirm-dialog é modal blocking
7. **Dark mode:** SweetAlert2 tem tema dark via CSS class — adicionar `document.documentElement.classList.contains('dark')` no customClass se necessário
8. **Não componentizar em Blade** — não faz sentido, é 100% JS disparado por evento
9. **Sem `x-admin.confirm-modal`** — quando houver conteúdo rico, formulário ou explicação longa, usar `<x-shared.modal>`; para prompt simples, manter este helper

---

## Código Final JS

- **Arquivo final:** `resources/js/admin/confirm.js`
- **Bundle:** importado por `resources/js/admin.js`
- **Preview visual:** `/admin/dev/components/confirm-dialog`

### API final

- `window.confirmDestructive({ title, text, confirmText })`
- `window.confirmInfo({ title, text, confirmText })`
- `window.alertSuccess({ title, text })`
- `window.alertError({ title, text })`
- bridge Livewire: `Livewire.on('confirm', ...)` e `Livewire.on('alert', ...)`

### Exemplo final

```js
const result = await window.confirmDestructive({
    title: 'Excluir pedido?',
    text: 'Esta ação não pode ser desfeita.',
    confirmText: 'Sim, excluir',
});
```
