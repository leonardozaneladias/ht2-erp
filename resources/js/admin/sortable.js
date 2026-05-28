import Sortable from 'sortablejs';

let observerStarted = false;

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

function parseConfig(element) {
  try {
    return JSON.parse(element.dataset.afSortable || '{}');
  } catch (error) {
    console.warn('Configuracao invalida de sortable.', error);
    return {};
  }
}

function bindSortable(element) {
  if (element.dataset.afSortableInitialized === 'true') {
    return;
  }

  const config = parseConfig(element);
  const handle = config.handle || '.drag-handle';

  const instance = new Sortable(element, {
    animation: Number(config.animation || 150),
    ghostClass: config.ghostClass || 'bg-primary/10',
    handle,
    onEnd: () => {
      const ids = Array.from(element.children)
        .map((child) => child.dataset.id)
        .filter(Boolean);

      element.dispatchEvent(
        new CustomEvent('sortable:changed', {
          bubbles: true,
          detail: { ids },
        }),
      );

      if (config.target && element.__livewire) {
        element.__livewire.call(config.target, ids);
        return;
      }

      const root = element.closest('[wire\\:id]');
      const livewireId = root?.getAttribute('wire:id');

      if (config.target && window.Livewire?.find && livewireId) {
        window.Livewire.find(livewireId)?.call(config.target, ids);
      }
    },
  });

  element.dataset.afSortableInitialized = 'true';
  element._afSortable = instance;
}

function bootSortables(root = document) {
  collectElements(root, '[data-af-sortable]').forEach(bindSortable);
}

function ensureObserver() {
  if (observerStarted) {
    return;
  }

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          bootSortables(node);
        }
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
  observerStarted = true;
}

bootSortables();
ensureObserver();

document.addEventListener('livewire:navigated', () => bootSortables());

window.initAdminSortables = bootSortables;
