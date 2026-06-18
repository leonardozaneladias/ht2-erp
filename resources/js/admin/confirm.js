import SwalModule from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

// Vite 8 (Rolldown): normaliza o interop CJS/UMD do default export.
const Swal = SwalModule?.default ?? SwalModule;

function resolvePopupClass() {
  const html = document.documentElement;
  const isDark = html.classList.contains('dark') || html.getAttribute('data-theme') === 'dark';

  return isDark
    ? 'bg-default-950 text-default-100 border border-default-700'
    : 'bg-card text-body-color border border-default-300';
}

// Posição do diálogo definida nas Configurações → Notificações (NotificacaoSettings).
function resolveConfirmPosition() {
  return window.__notificacaoConfig?.confirm?.position === 'top' ? 'top' : 'center';
}

function getBaseOptions() {
  return {
    buttonsStyling: false,
    reverseButtons: true,
    position: resolveConfirmPosition(),
    customClass: {
      popup: resolvePopupClass(),
      title: 'text-lg font-semibold text-body-color dark:text-default-100',
      htmlContainer: 'text-sm leading-6 text-default-500 dark:text-default-400',
      actions: 'flex flex-wrap justify-center gap-2 pt-4',
      confirmButton: 'btn bg-primary text-white hover:bg-primary-hover',
      cancelButton:
        'btn bg-light text-dark hover:bg-light-hover dark:bg-default-900 dark:text-default-100 dark:hover:bg-default-800',
    },
  };
}

function normalizePayload(payload) {
  if (Array.isArray(payload)) {
    return normalizePayload(payload[0] ?? {});
  }

  if (!payload || typeof payload !== 'object') {
    return {};
  }

  return payload;
}

async function runConfirm(payload = {}) {
  const data = normalizePayload(payload);
  const destructive = Boolean(data.destructive);
  const baseOptions = getBaseOptions();

  return Swal.fire({
    // baseOptions PRIMEIRO: o customClass (com o botão destrutivo) precisa vir
    // DEPOIS, senão o spread sobrescreveria o vermelho da ação destrutiva.
    ...baseOptions,
    // data.position permite o preview ao vivo (aba Configurações) sobrepor o padrão.
    position: data.position ?? baseOptions.position,
    title: data.title ?? 'Confirmar ação',
    text: data.html ? undefined : data.text,
    html: data.html,
    icon: data.icon ?? (destructive ? 'warning' : 'question'),
    showCancelButton: data.showCancelButton ?? true,
    focusCancel: data.focusCancel ?? true,
    confirmButtonText: data.confirmText ?? (destructive ? 'Sim, confirmar' : 'Confirmar'),
    cancelButtonText: data.cancelText ?? 'Cancelar',
    allowOutsideClick: data.allowOutsideClick ?? !destructive,
    allowEscapeKey: data.allowEscapeKey ?? true,
    customClass: {
      ...baseOptions.customClass,
      confirmButton: destructive
        ? 'btn bg-danger text-white hover:bg-danger-hover'
        : baseOptions.customClass.confirmButton,
    },
  });
}

function confirmDestructive(options = {}) {
  return runConfirm({
    destructive: true,
    ...options,
  });
}

function confirmInfo(options = {}) {
  return runConfirm({
    destructive: false,
    ...options,
  });
}

function showAlert({ type = 'success', title, text, html } = {}) {
  return Swal.fire({
    title: title ?? (type === 'error' ? 'Algo deu errado' : 'Operação concluída'),
    text,
    html,
    icon: type,
    confirmButtonText: 'Fechar',
    ...getBaseOptions(),
    customClass: {
      ...getBaseOptions().customClass,
      confirmButton:
        type === 'error'
          ? 'btn bg-danger text-white hover:bg-danger-hover'
          : 'btn bg-success text-white hover:bg-success-hover',
    },
  });
}

function alertSuccess(options = {}) {
  return showAlert({ type: 'success', ...options });
}

function alertError(options = {}) {
  return showAlert({ type: 'error', ...options });
}

function registerLivewireBridge() {
  document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.on) {
      return;
    }

    window.Livewire.on('confirm', async (...payload) => {
      const data = normalizePayload(payload.length === 1 ? payload[0] : payload);
      const result = await runConfirm(data);

      if (result.isConfirmed && data.onConfirm && window.Livewire?.dispatch) {
        window.Livewire.dispatch(data.onConfirm, data.params ?? {});
      }
    });

    window.Livewire.on('alert', async (...payload) => {
      const data = normalizePayload(payload.length === 1 ? payload[0] : payload);
      await showAlert({
        type: data.type ?? 'success',
        title: data.title,
        text: data.text,
        html: data.html,
      });
    });
  });
}

window.addEventListener('confirm', async (event) => {
  await runConfirm(event.detail);
});

window.confirmDestructive = confirmDestructive;
window.confirmInfo = confirmInfo;
window.alertSuccess = alertSuccess;
window.alertError = alertError;

registerLivewireBridge();

export { alertError, alertSuccess, confirmDestructive, confirmInfo, runConfirm };
