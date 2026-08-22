// =============================================================================
// Feedback global de validação ao salvar formulários Livewire
//
// Quando um `salvar()` termina com erros de validação, o único sinal visual são
// as mensagens sob os campos — que podem estar fora da viewport (formulários
// longos) ou dentro de painel de aba. Este módulo observa TODOS os requests
// Livewire que chamam `salvar` e, se o re-render deixou campos inválidos
// ([aria-invalid="true"], padrão dos componentes x-shared.*), dispara UM toast
// de erro e rola/foca até o primeiro campo visível.
//
// Cobre os 31 Forms de CRUD, o stub do gerador e sub-forms (ex.: campos
// personalizados) sem tocar em nenhum componente PHP — inclusive validações
// manuais via addError() que não lançam ValidationException.
//
// Validação em tempo real (wire:model.blur/.live) NÃO dispara nada: esses
// updates não carregam uma action `salvar`.
// =============================================================================

import { notify } from './toast';
import { documentosInvalidos } from './validators';

// Métodos que caracterizam um submit de formulário (convenção do core: é o que o
// gerador emite). A mensagem acompanha a ação — num wizard, "antes de salvar" seria
// mentira: o operador está tentando AVANÇAR, e ainda vai preencher outros passos.
//
// `proximo` é o avanço de passo do wizard: valida o passo atual e, se reprovar, precisa do
// MESMO tratamento do salvar (um toast + scroll até o campo). Sem ele aqui, o operador
// clica em "Continuar", nada acontece visivelmente, e o erro fica sob um campo que pode
// estar fora da viewport.
//
// Deliberadamente NÃO entra no unsaved-guard.js: `proximo` não salva nada, e limpar o
// estado "sujo" ali faria o guard parar de avisar sobre alterações não salvas.
const MENSAGENS = {
  salvar: 'Corrija os campos destacados antes de salvar.',
  proximo: 'Corrija os campos destacados antes de continuar.',
};

// Gancho de extensão: um módulo-pacote que tenha um submit fora da convenção registra o
// método e a mensagem dele, sem que o core precise conhecer o nome. Chamar antes do
// `livewire:init` (no bundle do próprio módulo).
window.registrarMetodoSubmit = (nome, mensagem) => {
  MENSAGENS[nome] = mensagem ?? MENSAGENS.salvar;
};

function ehSubmit(nome) {
  return Object.hasOwn(MENSAGENS, nome);
}

// Roots dos componentes que salvaram com erro neste ciclo; flush único por frame
// garante UM toast mesmo com vários componentes no mesmo request.
const raizesPendentes = new Set();
let flushAgendado = false;
let mensagemDoCiclo = MENSAGENS.salvar;

function prefersReducedMotion() {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
}

function estaVisivel(el) {
  return el.checkVisibility?.() ?? el.offsetParent !== null;
}

// Campos inválidos PRÓPRIOS do root (exclui componentes Livewire aninhados —
// erros antigos de um sub-form não podem virar falso positivo do pai).
function camposInvalidosDe(root) {
  return [...root.querySelectorAll('[aria-invalid="true"]')].filter((el) => el.closest('[wire\\:id]') === root);
}

// Toggles/checkboxes carregam o aria-invalid num <input> sr-only: o alvo de
// scroll sobe para o wrapper visível; o foco continua no input (peer-focus
// exibe o anel no controle estilizado).
function alvoDeScroll(campo) {
  if (estaVisivel(campo)) {
    return campo;
  }

  const wrapper = campo.closest('label, div');

  return wrapper && estaVisivel(wrapper) ? wrapper : null;
}

function ordemNoDocumento(a, b) {
  return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
}

function flush() {
  flushAgendado = false;

  const roots = [...raizesPendentes].filter((root) => root.isConnected).sort(ordemNoDocumento);
  raizesPendentes.clear();

  const candidatos = roots.flatMap(camposInvalidosDe);

  if (candidatos.length === 0) {
    return; // salvar teve sucesso — nenhum feedback de erro
  }

  notify('danger', mensagemDoCiclo);

  const campo = candidatos.find((c) => alvoDeScroll(c) !== null);

  if (!campo) {
    return; // erro sem campo visível (ex.: aba não renderizada): só o toast
  }

  alvoDeScroll(campo).scrollIntoView({
    block: 'center',
    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
  });
  campo.focus({ preventScroll: true });
}

/**
 * Pré-flight de dígito verificador (CPF/CNPJ/PIS).
 *
 * O DV é um checksum: dá para saber que o documento é impossível SEM perguntar ao
 * servidor. Barrando aqui, um "salvar" com CPF errado deixa de custar um round-trip —
 * e o operador vê o erro instantaneamente, não depois da latência da rede.
 *
 * Como o request é cancelado, não há morph — então marcar `aria-invalid` via JS é seguro
 * aqui (é justamente o que o morph apagaria em qualquer outro contexto).
 *
 * @returns {boolean} true se barrou o envio.
 */
function barrouPorDocumentoInvalido(root) {
  // Limpa o veredito anterior antes de julgar de novo (o usuário pode ter corrigido).
  root.querySelectorAll('[data-af-validate]').forEach((campo) => {
    campo.setAttribute('aria-invalid', 'false');
  });

  const invalidos = documentosInvalidos(root);

  if (invalidos.length === 0) {
    return false;
  }

  invalidos.forEach(({ elemento }) => elemento.setAttribute('aria-invalid', 'true'));

  notify('danger', invalidos[0].mensagem);

  const primeiro = invalidos[0].elemento;

  if (estaVisivel(primeiro)) {
    primeiro.scrollIntoView({
      block: 'center',
      behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    });
    primeiro.focus({ preventScroll: true });
  }

  return true;
}

document.addEventListener('livewire:init', () => {
  if (!window.Livewire?.interceptMessage) {
    return;
  }

  window.Livewire.interceptMessage(({ message, onSuccess, cancel }) => {
    const acaoDeSubmit = [...(message.actions ?? [])].find((acao) => ehSubmit(acao.name));

    if (!acaoDeSubmit) {
      return;
    }

    mensagemDoCiclo = MENSAGENS[acaoDeSubmit.name];

    // Pré-flight no salvar E no "próximo" do wizard: campo VAZIO nunca é inválido aqui
    // (documentosInvalidos ignora vazios), então avançar de passo com documentos ainda
    // não preenchidos segue livre — só um DV impossível JÁ digitado barra o round-trip.
    if (barrouPorDocumentoInvalido(message.component.el)) {
      cancel();

      return;
    }

    onSuccess(({ payload, onRender }) => {
      // Salvou e vai navegar (redirect): sem toast fantasma durante a transição.
      if (payload?.effects?.redirect) {
        return;
      }

      // onRender roda após o morph — o DOM já reflete o ViewErrorBag.
      onRender(() => {
        raizesPendentes.add(message.component.el);

        if (!flushAgendado) {
          flushAgendado = true;
          window.requestAnimationFrame(flush);
        }
      });
    });
  });
});
