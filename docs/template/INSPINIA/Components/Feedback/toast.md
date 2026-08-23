# Toast (Notificações)

**Categoria:** Feedback
**Arquivos:**

- Backend (disparo): `app/Livewire/Concerns/EmiteNotificacoes.php`
- Aparência: `resources/views/components/shared/toast-container.blade.php`
- Comportamento: `resources/js/admin/toast.js`
- Configuração (admin): `app/Settings/NotificacaoSettings.php` + aba `/admin/configuracoes?aba=notificacoes`

---

## Descrição

Notificação efêmera e flutuante ("Salvo com sucesso", erros, avisos), com auto-dismiss.
Diferente de `<x-shared.alert>` (que fica no fluxo da página), o toast é temporário e some
sozinho. A **posição, duração, estilo e quantidade máxima** são definidos pelo admin nas
Configurações → Notificações; a posição das **confirmações** (SweetAlert2) também.

---

## Onde mexer (3 pontos únicos)

| Para mudar…                                                              | Edite…                                                     |
| ------------------------------------------------------------------------ | ---------------------------------------------------------- |
| **Quando/como o sistema notifica** (nos módulos)                         | trait `EmiteNotificacoes` → `$this->notificarSucesso(...)` |
| **Aparência** (forma, sombra, cores da pílula/card)                      | `toast-container.blade.php` (`<template>`)                 |
| **Comportamento padrão / opções** (durações, defaults, mapa de posições) | bloco `CONFIG`/`DEFAULTS` no topo de `toast.js`            |

O admin ajusta posição/duração/estilo/máximo (e a posição das confirmações) pela tela de
Configurações — sem tocar em código.

---

## Disparo no backend (forma canônica)

Em qualquer componente Livewire, use o trait:

```php
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;

class FormPedido extends Component
{
    use EmiteNotificacoes;

    public function salvar(): void
    {
        // ... persiste ...
        $this->notificarSucesso('Pedido salvo com sucesso.');     // permanece na página
        // $this->notificarErro($e->getMessage());
        // $this->notificarAviso('...'); / $this->notificarInfo('...');
    }

    public function salvarEVoltar(): void
    {
        // Quando o método REDIRECIONA logo em seguida (a mensagem sobrevive ao redirect):
        $this->notificarAposRedirect('success', 'Pedido criado.');
        $this->redirect(route('admin.pedidos.index'), navigate: true);
    }
}
```

Fora do Livewire (controllers/middleware), use a chave de sessão canônica:

```php
session()->flash('notify', ['variant' => 'success', 'message' => 'Pronto.', 'title' => null]);
```

## Disparo no frontend (JS)

```js
notify('success', 'Mensagem'); // ou window.notify(...)
```

`copy.js`, `forms.js` e `avatar-cropper.js` usam o helper único `notify()` exportado por
`toast.js` (sem `dispatchToast` duplicado).

---

## Variantes

`success` · `danger` (alias `error`) · `warning` · `info` · `default`. Ícone Tabler + cor
por variante são definidos no `CONFIG.variants` do `toast.js`. Erros ganham um acréscimo de
duração automático.

## Estilos

- `pilula` — compacta, ícone + mensagem em uma linha (padrão).
- `card` — maior, com título em negrito + mensagem secundária.

Cada estilo é um `<template>` em `toast-container.blade.php`; o motor clona o do estilo ativo.

---

## Quando Usar ✅

- Feedback imediato após ação (salvou, excluiu, processou).
- Mensagem que sobrevive a um `redirect()` (`notificarAposRedirect`).

## Quando NÃO Usar ❌

- Texto que o usuário precisa ler com calma → `<x-shared.alert>` na página.
- Confirmação antes de ação destrutiva → `confirm.js` (SweetAlert2, via `dispatch('confirm', ...)`).
- Validação de campo → `@error` no `<x-shared.input>`.

---

## Notas

1. **z-index `z-[1100]`** — acima dos menus de ação/combobox (`z-[1080]`), corrigindo o caso
   em que o toast sumia atrás deles.
2. **Posição via inline-style** aplicada pelo `toast.js` a partir da config (mapa `POSITIONS`),
   evitando depender do scan de classes dinâmicas do Tailwind. `window.notifyConfigure({...})`
   aplica em runtime (usado no preview da aba).
3. **`prefers-reduced-motion`** respeitado (sem slide, só fade).
4. **Pausa no hover** + clique para fechar. `duration: false` desabilita o auto-dismiss.
5. **Quantidade máxima** configurável: ao exceder, a notificação mais antiga é removida.
6. **Tolerância a falhas:** `NotificacaoService` cai nos padrões sem banco (páginas de erro/instalação).

---

## Preview e teste

- Showcase de variantes: `/admin/dev/components/toast`.
- Preview ao vivo das opções: Configurações → Notificações (botões "Testar notificação" e
  "Testar confirmação").
