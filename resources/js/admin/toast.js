const DEFAULT_DURATION = 4000;

const variantMap = {
  default: {
    wrapper:
      'border-default-300 bg-card/95 text-default-700 dark:border-default-700 dark:bg-default-950/95 dark:text-default-100',
    iconColor: 'text-info',
    icon: 'tabler--info-circle',
  },
  success: {
    wrapper: 'border-success/30 bg-success/10 text-success dark:border-success/40 dark:bg-success/15',
    iconColor: 'text-success',
    icon: 'tabler--circle-check',
  },
  danger: {
    wrapper: 'border-danger/30 bg-danger/10 text-danger dark:border-danger/40 dark:bg-danger/15',
    iconColor: 'text-danger',
    icon: 'tabler--alert-octagon',
  },
  warning: {
    wrapper: 'border-warning/30 bg-warning/10 text-warning dark:border-warning/40 dark:bg-warning/15',
    iconColor: 'text-warning',
    icon: 'tabler--alert-triangle',
  },
  info: {
    wrapper: 'border-info/30 bg-info/10 text-info dark:border-info/40 dark:bg-info/15',
    iconColor: 'text-info',
    icon: 'tabler--info-circle',
  },
};

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

function removeToast(toast) {
  if (!toast || !toast.isConnected) {
    return;
  }

  toast.classList.add('translate-x-4', 'opacity-0');
  toast.classList.remove('translate-x-0', 'opacity-100');

  window.setTimeout(() => {
    toast.remove();
  }, 250);
}

function createIcon(icon, className) {
  const element = document.createElement('i');
  element.className = `iconify ${icon} ${className}`.trim();

  return element;
}

function buildToast(payload) {
  const data = normalizePayload(payload);
  const variant = variantMap[data.variant] ?? variantMap.default;
  const toast = document.createElement('div');

  toast.className = [
    'pointer-events-auto',
    'w-full',
    'max-w-sm',
    'overflow-hidden',
    'rounded-xl',
    'border',
    'shadow-lg',
    'backdrop-blur',
    'transition',
    'duration-300',
    'ease-out',
    'translate-x-4',
    'opacity-0',
    variant.wrapper,
  ].join(' ');

  toast.setAttribute('role', 'status');
  toast.setAttribute('aria-live', 'polite');
  toast.dataset.toastItem = 'true';

  const body = document.createElement('div');
  body.className = 'flex items-start gap-3 p-4';

  const iconCircle = document.createElement('span');
  iconCircle.className =
    'mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white/70 dark:bg-default-900/70';
  iconCircle.appendChild(createIcon(data.icon ?? variant.icon, `${variant.iconColor} text-xl`));

  const content = document.createElement('div');
  content.className = 'min-w-0 flex-1';

  if (data.title) {
    const title = document.createElement('p');
    title.className = 'font-semibold text-body-color dark:text-default-100';
    title.textContent = data.title;
    content.appendChild(title);
  }

  if (data.message) {
    const message = document.createElement('div');
    message.className = data.title
      ? 'mt-1 text-sm leading-6 text-default-500 dark:text-default-400'
      : 'text-sm leading-6 text-default-500 dark:text-default-400';
    message.textContent = data.message;
    content.appendChild(message);
  }

  const closeButton = document.createElement('button');
  closeButton.type = 'button';
  closeButton.className =
    'inline-flex size-8 shrink-0 items-center justify-center rounded-full text-default-400 transition hover:bg-black/5 hover:text-body-color dark:text-default-500 dark:hover:bg-white/5 dark:hover:text-default-100';
  closeButton.setAttribute('aria-label', 'Fechar toast');
  closeButton.appendChild(createIcon('tabler--x', 'text-lg'));
  closeButton.addEventListener('click', () => removeToast(toast));

  body.appendChild(iconCircle);
  body.appendChild(content);

  if (data.dismissible !== false) {
    body.appendChild(closeButton);
  }

  toast.appendChild(body);

  const duration = data.duration === false ? false : Number(data.duration || DEFAULT_DURATION);
  let timeoutId = null;

  const startTimer = () => {
    if (duration === false) {
      return;
    }

    timeoutId = window.setTimeout(() => removeToast(toast), duration);
  };

  const stopTimer = () => {
    if (timeoutId) {
      window.clearTimeout(timeoutId);
      timeoutId = null;
    }
  };

  toast.addEventListener('mouseenter', stopTimer);
  toast.addEventListener('mouseleave', startTimer);
  startTimer();

  return toast;
}

function showToast(payload) {
  const container = getContainer();

  if (!container) {
    return null;
  }

  const toast = buildToast(payload);
  container.appendChild(toast);

  window.requestAnimationFrame(() => {
    toast.classList.remove('translate-x-4', 'opacity-0');
    toast.classList.add('translate-x-0', 'opacity-100');
  });

  return toast;
}

function registerToastEventBridge() {
  window.addEventListener('toast', (event) => {
    showToast(event.detail);
  });

  document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.on) {
      return;
    }

    window.Livewire.on('toast', (...payload) => {
      showToast(payload.length === 1 ? payload[0] : payload);
    });
  });
}

registerToastEventBridge();

window.showToast = (detail) => {
  window.dispatchEvent(new CustomEvent('toast', { detail }));
};

export { removeToast, showToast };
