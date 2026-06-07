// Botao de tela cheia (topbar). O markup tem `data-toggle="fullscreen"` e troca
// os icones (maximize/minimize) via a classe `.fullscreen-active` no proprio
// botao (group variant do Tailwind) — mas nunca houve handler JS, entao o botao
// nao fazia nada (Preline 4 tambem nao traz esse plugin).
//
// Usamos delegacao de evento no `document`: assim o handler sobrevive as
// navegacoes SPA do Livewire (wire:navigate re-renderiza a topbar) sem precisar
// re-registrar listener. O estado visual e sincronizado pelo evento nativo
// `fullscreenchange`, que tambem cobre a saida via tecla ESC.

const SELECTOR = '[data-toggle="fullscreen"]';
const ACTIVE_CLASS = 'fullscreen-active';

function fullscreenSuportado() {
  return Boolean(document.fullscreenEnabled || document.webkitFullscreenEnabled);
}

function elementoCheio() {
  return document.fullscreenElement || document.webkitFullscreenElement || null;
}

function entrar() {
  const alvo = document.documentElement;
  const pedir = alvo.requestFullscreen || alvo.webkitRequestFullscreen;

  if (typeof pedir === 'function') {
    // Pode rejeitar (ex.: fora de um gesto do usuario) — ignoramos com seguranca.
    Promise.resolve(pedir.call(alvo)).catch(() => {});
  }
}

function sair() {
  const fechar = document.exitFullscreen || document.webkitExitFullscreen;

  if (typeof fechar === 'function') {
    Promise.resolve(fechar.call(document)).catch(() => {});
  }
}

function alternar() {
  if (!fullscreenSuportado()) {
    return;
  }

  if (elementoCheio()) {
    sair();
  } else {
    entrar();
  }
}

// Sincroniza todos os botoes (icone + aria-pressed) com o estado real.
function sincronizar() {
  const ativo = Boolean(elementoCheio());

  document.querySelectorAll(SELECTOR).forEach((botao) => {
    botao.classList.toggle(ACTIVE_CLASS, ativo);
    botao.setAttribute('aria-pressed', ativo ? 'true' : 'false');
  });
}

// Delegacao: um unico listener no document cobre qualquer botao presente ou
// futuro (apos morphs do Livewire/PowerGrid e navegacoes SPA).
document.addEventListener('click', (evento) => {
  const botao = evento.target.closest?.(SELECTOR);

  if (botao) {
    evento.preventDefault();
    alternar();
  }
});

document.addEventListener('fullscreenchange', sincronizar);
document.addEventListener('webkitfullscreenchange', sincronizar);

// Apos navegar (wire:navigate) a topbar e recriada no estado default — reaplica
// o estado real do fullscreen no botao novo.
document.addEventListener('livewire:navigated', sincronizar);
