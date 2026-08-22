// =============================================================================
// Guarda de alterações não salvas (opt-in por container)
//
// Formulários longos (ex.: cadastro de funcionário) perdiam tudo num clique em
// "Cancelar"/breadcrumb/back sem qualquer aviso. Este módulo observa containers
// marcados com `data-af-unsaved-guard` (normalmente o root do componente
// Livewire do formulário):
//
//   - marca o container como SUJO em `input`/`change` originados de campos do
//     PRÓPRIO componente (inputs de componentes Livewire aninhados — abas
//     auto-persistentes — não sujam o pai);
//   - LIMPA quando um request Livewire com action `salvar` do componente dono
//     termina sem campos inválidos (ou com redirect — criar→editar);
//   - intercepta a saída: cliques em links `wire:navigate`, o evento
//     `livewire:navigate` (back/forward) e `beforeunload` (fechar/refresh)
//     pedem confirmação enquanto houver guarda suja.
//
// Opt-in e sem estado no servidor: nenhum componente PHP precisa mudar. Para
// ignorar um campo (filtros locais etc.), envolva-o com `data-af-unsaved-ignore`.
// =============================================================================

const METODOS_SUBMIT = new Set(['salvar']);

const MENSAGEM = 'Há alterações não salvas. Sair mesmo assim?';

// Containers sujos (elementos vivos; entradas desconectadas são descartadas).
const sujos = new Set();

function guardaDe(el) {
  return el?.closest?.('[data-af-unsaved-guard]') ?? null;
}

function componenteDe(el) {
  return el?.closest?.('[wire\\:id]') ?? null;
}

function algumaGuardaSuja() {
  for (const el of sujos) {
    if (el.isConnected) {
      return true;
    }

    sujos.delete(el);
  }

  return false;
}

function limparTodas() {
  sujos.clear();
}

// --- Marcação de sujo -------------------------------------------------------

function aoEditarCampo(evento) {
  const alvo = evento.target;
  const guarda = guardaDe(alvo);

  if (!guarda || alvo.closest('[data-af-unsaved-ignore]')) {
    return;
  }

  // Campo de componente aninhado (aba auto-persistente) não suja o form pai.
  if (componenteDe(alvo) !== componenteDe(guarda)) {
    return;
  }

  sujos.add(guarda);
}

document.addEventListener('input', aoEditarCampo, true);
document.addEventListener('change', aoEditarCampo, true);

// --- Limpeza no salvar ------------------------------------------------------

document.addEventListener('livewire:init', () => {
  if (!window.Livewire?.interceptMessage) {
    return;
  }

  window.Livewire.interceptMessage(({ message, onSuccess }) => {
    const chamouSubmit = [...(message.actions ?? [])].some((acao) => METODOS_SUBMIT.has(acao.name));

    if (!chamouSubmit) {
      return;
    }

    const raiz = message.component.el;
    const guarda = raiz.matches?.('[data-af-unsaved-guard]') ? raiz : raiz.querySelector?.('[data-af-unsaved-guard]');

    if (!guarda) {
      return;
    }

    onSuccess(({ payload, onRender }) => {
      // Salvou e vai navegar (ex.: criar→editar): limpa antes do redirect.
      if (payload?.effects?.redirect) {
        sujos.delete(guarda);

        return;
      }

      onRender(() => {
        // Com campo inválido o dado continua NÃO salvo — permanece sujo.
        if (!raiz.querySelector('[aria-invalid="true"]')) {
          sujos.delete(guarda);
        }
      });
    });
  });
});

// --- Interceptação de saída --------------------------------------------------

// Cliques em links wire:navigate (Cancelar, breadcrumb, sidebar) — fase de
// captura para vencer o handler do próprio Livewire.
document.addEventListener(
  'click',
  (evento) => {
    if (!algumaGuardaSuja()) {
      return;
    }

    const link = evento.target?.closest?.('a[wire\\:navigate], a[wire\\:navigate\\.hover]');

    if (!link) {
      return;
    }

    if (window.confirm(MENSAGEM)) {
      limparTodas(); // o usuário escolheu sair — sem segundo aviso
    } else {
      evento.preventDefault();
      evento.stopImmediatePropagation();
    }
  },
  true,
);

// Navegações SPA que não passam por clique (back/forward do browser).
document.addEventListener('livewire:navigate', (evento) => {
  if (!algumaGuardaSuja()) {
    return;
  }

  if (window.confirm(MENSAGEM)) {
    limparTodas();
  } else if (evento.cancelable) {
    evento.preventDefault();
  }
});

// Fechar a aba / refresh / navegação full-page.
window.addEventListener('beforeunload', (evento) => {
  if (!algumaGuardaSuja()) {
    return;
  }

  evento.preventDefault();
  evento.returnValue = '';
});
