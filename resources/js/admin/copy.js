import { notify } from './toast';

function collectElements(root, selector) {
  const elements = [];

  if (root?.matches?.(selector)) {
    elements.push(root);
  }

  if (root?.querySelectorAll) {
    elements.push(...root.querySelectorAll(selector));
  }

  return elements;
}

function parseJsonDataset(element, key) {
  try {
    return JSON.parse(element.dataset[key] || '{}');
  } catch (error) {
    console.warn(`Configuracao invalida em ${key}`, error);
    return {};
  }
}

function updateButtonState(button, copied, config) {
  const icon = button.querySelector('[data-copy-icon]');
  const label = button.querySelector('[data-copy-label]');

  if (icon) {
    icon.classList.remove(config.icon || 'tabler--copy', config.successIcon || 'tabler--check');
    icon.classList.add(copied ? config.successIcon || 'tabler--check' : config.icon || 'tabler--copy');
  }

  if (label) {
    label.textContent = copied ? config.copiedLabel || 'Copiado!' : config.label || '';
  }
}

function initCopyButtons(root = document) {
  collectElements(root, '[data-af-copy]').forEach((button) => {
    if (button.dataset.afCopyBound === 'true') {
      return;
    }

    const config = parseJsonDataset(button, 'afCopy');
    button.dataset.afCopyBound = 'true';

    button.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(config.value || '');
        updateButtonState(button, true, config);

        if (config.successMessage) {
          notify('success', config.successMessage);
        }

        window.setTimeout(() => {
          updateButtonState(button, false, config);
        }, 1500);
      } catch (error) {
        console.error('Erro ao copiar', error);
        notify('danger', config.errorMessage || 'Erro ao copiar.');
      }
    });
  });
}

function bootCopyButtons() {
  initCopyButtons(document);

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          initCopyButtons(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootCopyButtons, { once: true });
} else {
  bootCopyButtons();
}
