// =============================================================================
// Sistema de notificações (toasts) — MOTOR ÚNICO DE COMPORTAMENTO
//
// As preferências (posição, duração, estilo, máximo) vêm das Configurações do
// sistema, injetadas em window.__notificacaoConfig pelo partial
// x-admin.partials.notification-config (origem: HT2ML\Core\Settings\NotificacaoSettings).
//
//   • APARÊNCIA (markup/classes) ........ resources/views/components/shared/toast-container.blade.php
//   • OPÇÕES p/ o admin ................. /admin/configuracoes?aba=notificacoes
//   • DISPARO no backend ............... HT2ML\Core\Livewire\Concerns\EmiteNotificacoes
//
// Disparo no frontend:  notify('success', 'Mensagem')  — ou  window.notify(...)
// Preview ao vivo (aba Configurações):  window.notifyConfigure({ position, durationKey, style, max })
// =============================================================================

// Defaults usados quando não há config injetada (páginas de erro/instalação).
const DEFAULTS = {
  position: 'top-center',
  duration: 4500,
  style: 'pilula',
  max: 3,
};

// Acréscimo (ms) na duração de erros, para dar mais tempo de leitura.
const DANGER_EXTRA = 1500;
const ENTER_DURATION = 300;
const LEAVE_DURATION = 200;

// Espelha HT2ML\Core\Enums\Admin\Notificacao\ToastDuracao::ms() — usado só no preview,
// onde o JS recebe a chave (curta|media|longa) e não os milissegundos.
const DURATION_MS = { curta: 3000, media: 4500, longa: 7000 };

const VARIANTS = {
  default: { icon: 'tabler--info-circle', color: 'text-info' },
  success: { icon: 'tabler--circle-check', color: 'text-success' },
  danger: { icon: 'tabler--alert-octagon', color: 'text-danger' },
  warning: { icon: 'tabler--alert-triangle', color: 'text-warning' },
  info: { icon: 'tabler--info-circle', color: 'text-info' },
};

// Severidade -> urgência da live region (WAI-ARIA APG). Espelha o critério do
// x-shared.alert: erros e avisos interrompem o leitor de tela
// (role="alert"/aria-live="assertive"); as demais variantes mantêm o
// role="status"/aria-live="polite" declarado no <template> do toast-container.
const ASSERTIVE_VARIANTS = new Set(['danger', 'warning']);

// Posições aplicadas via inline-style (classes dinâmicas não são escaneadas pelo
// Tailwind). `edge` define a direção da animação de entrada/saída.
const POSITIONS = {
  'top-center': { top: '1rem', bottom: 'auto', left: '0px', right: '0px', alignItems: 'center', edge: 'top' },
  'top-right': { top: '1rem', bottom: 'auto', left: 'auto', right: '1rem', alignItems: 'flex-end', edge: 'top' },
  'top-left': { top: '1rem', bottom: 'auto', left: '1rem', right: 'auto', alignItems: 'flex-start', edge: 'top' },
  'bottom-center': { top: 'auto', bottom: '1rem', left: '0px', right: '0px', alignItems: 'center', edge: 'bottom' },
  'bottom-right': { top: 'auto', bottom: '1rem', left: 'auto', right: '1rem', alignItems: 'flex-end', edge: 'bottom' },
  'bottom-left': { top: 'auto', bottom: '1rem', left: '1rem', right: 'auto', alignItems: 'flex-start', edge: 'bottom' },
};

const config = { ...DEFAULTS };

function prefersReducedMotion() {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false;
}

// 'error' é um alias histórico de 'danger'.
function resolveVariantName(name) {
  if (name === 'error') {
    return 'danger';
  }

  return VARIANTS[name] ? name : 'default';
}

function normalizePayload(payload) {
  if (Array.isArray(payload)) {
    return normalizePayload(payload[0] ?? {});
  }

  if (typeof payload === 'string') {
    return { message: payload };
  }

  if (!payload || typeof payload !== 'object') {
    return { message: '' };
  }

  return payload;
}

function getContainer() {
  return document.querySelector('[data-toast-container]');
}

function positionFor(key) {
  return POSITIONS[key] ?? POSITIONS[DEFAULTS.position];
}

function enterTransform() {
  return positionFor(config.position).edge === 'bottom' ? 'translateY(8px)' : 'translateY(-8px)';
}

function applyContainerPosition() {
  const container = getContainer();

  if (!container) {
    return;
  }

  const pos = positionFor(config.position);
  container.style.top = pos.top;
  container.style.bottom = pos.bottom;
  container.style.left = pos.left;
  container.style.right = pos.right;
  container.style.alignItems = pos.alignItems;
}

function loadConfig() {
  const injected = window.__notificacaoConfig?.toast;

  if (!injected || typeof injected !== 'object') {
    return;
  }

  if (injected.position) {
    config.position = injected.position;
  }
  if (injected.duration) {
    config.duration = Number(injected.duration) || config.duration;
  }
  if (injected.style) {
    config.style = injected.style;
  }
  if (injected.max) {
    config.max = Number(injected.max) || config.max;
  }
}

function templateFor(style) {
  const container = getContainer();

  return (
    container?.querySelector(`[data-toast-template="${style}"]`) ??
    container?.querySelector('[data-toast-template="pilula"]') ??
    container?.querySelector('[data-toast-template]')
  );
}

function removeToast(toast) {
  if (!toast || !toast.isConnected) {
    return;
  }

  toast.style.transitionDuration = `${LEAVE_DURATION}ms`;
  toast.style.opacity = '0';
  toast.style.transform = enterTransform();

  window.setTimeout(() => toast.remove(), LEAVE_DURATION);
}

// Mantém no máximo config.max toasts: remove os mais antigos antes de inserir um novo.
function enforceMax(container) {
  const items = [...container.querySelectorAll('[data-toast-item]')];
  const excess = items.length - config.max + 1;

  for (let i = 0; i < excess; i += 1) {
    removeToast(items[i]);
  }
}

function durationFor(variantName, data) {
  if (data.duration === false) {
    return false;
  }

  if (data.duration != null) {
    return Number(data.duration);
  }

  return variantName === 'danger' ? config.duration + DANGER_EXTRA : config.duration;
}

function startAutoDismiss(toast, data, variantName) {
  const duration = durationFor(variantName, data);

  if (!duration) {
    return;
  }

  let timeoutId = null;

  const start = () => {
    timeoutId = window.setTimeout(() => removeToast(toast), duration);
  };

  const stop = () => {
    if (timeoutId) {
      window.clearTimeout(timeoutId);
      timeoutId = null;
    }
  };

  toast.addEventListener('mouseenter', stop);
  toast.addEventListener('mouseleave', start);
  start();
}

function buildToast(payload) {
  const template = templateFor(config.style);

  if (!template?.content?.firstElementChild) {
    return null;
  }

  const data = normalizePayload(payload);
  const variantName = resolveVariantName(data.variant);
  const variant = VARIANTS[variantName];

  const toast = template.content.firstElementChild.cloneNode(true);

  // Erros e avisos elevam a live region para assertiva, interrompendo o leitor de
  // tela; sucesso/info seguem com o padrão polite do <template>. Só sobrescreve os
  // atributos ARIA — não toca em classes nem na aparência.
  if (ASSERTIVE_VARIANTS.has(variantName)) {
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
  }

  const iconEl = toast.querySelector('[data-toast-icon]');
  if (iconEl) {
    const iconClasses = (data.icon || variant.icon).split(' ').filter(Boolean);
    iconEl.classList.add(...iconClasses, variant.color);
  }

  const titleEl = toast.querySelector('[data-toast-title]');
  if (titleEl && data.title) {
    titleEl.textContent = data.title;
    titleEl.classList.remove('hidden');
  }

  const messageEl = toast.querySelector('[data-toast-message]');
  if (messageEl) {
    messageEl.textContent = data.message ?? '';
  }

  const closeEl = toast.querySelector('[data-toast-close]');
  if (data.dismissible === false) {
    closeEl?.remove();
  } else {
    closeEl?.addEventListener('click', () => removeToast(toast));
  }

  // Estado inicial (transparente e deslocado) para a animação de entrada.
  toast.style.opacity = '0';
  toast.style.transform = enterTransform();

  return { toast, data, variantName };
}

function showToast(payload) {
  // O container é recriado a cada navegação SPA e o reposicionamento via
  // `livewire:navigated` só roda DEPOIS dos scripts do body — um toast disparado
  // pelo flash do layout nasceria na posição default. Reaplicar aqui (idempotente)
  // torna o toast independente da ordem de execução.
  applyContainerPosition();

  const container = getContainer();

  if (!container) {
    return null;
  }

  const built = buildToast(payload);

  if (!built) {
    return null;
  }

  const { toast, data, variantName } = built;

  enforceMax(container);
  container.appendChild(toast);

  const reveal = () => {
    toast.style.transitionDuration = `${ENTER_DURATION}ms`;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
  };

  if (prefersReducedMotion()) {
    toast.style.opacity = '1';
    toast.style.transform = 'none';
  } else {
    window.requestAnimationFrame(reveal);
  }

  startAutoDismiss(toast, data, variantName);

  return toast;
}

function registerToastEventBridge() {
  // O Livewire entrega `$this->dispatch('toast', ...)` como um CustomEvent no
  // window (detail = { variant, message, title }). Portanto UM único listener
  // cobre os três caminhos: backend (dispatch via trait), frontend (notify) e o
  // flash do layout. Registrar TAMBÉM via Livewire.on('toast') faria o mesmo
  // dispatch ser processado duas vezes — dois toasts idênticos.
  window.addEventListener('toast', (event) => {
    showToast(event.detail);
  });
}

// API única de disparo no frontend (substitui os antigos `dispatchToast` locais).
function notify(variant, message, options = {}) {
  window.dispatchEvent(
    new CustomEvent('toast', {
      detail: { variant, message, ...options },
    }),
  );
}

// Aplica preferências em runtime (preview ao vivo na aba Configurações).
function notifyConfigure(partial = {}) {
  if (partial.position) {
    config.position = partial.position;
  }
  if (partial.durationKey && DURATION_MS[partial.durationKey]) {
    config.duration = DURATION_MS[partial.durationKey];
  }
  if (partial.duration != null) {
    config.duration = Number(partial.duration) || config.duration;
  }
  if (partial.style) {
    config.style = partial.style;
  }
  if (partial.max != null) {
    config.max = Number(partial.max) || config.max;
  }

  applyContainerPosition();
}

function init() {
  loadConfig();
  applyContainerPosition();
}

registerToastEventBridge();
init();

// O container é recriado a cada navegação SPA (wire:navigate) — reaplica a posição.
document.addEventListener('livewire:navigated', applyContainerPosition);

window.notify = notify;
window.notifyConfigure = notifyConfigure;
// Alias histórico — dispara o evento `toast` a partir de um payload completo.
window.showToast = (detail) => window.dispatchEvent(new CustomEvent('toast', { detail }));

export { notify, notifyConfigure, removeToast, showToast };
