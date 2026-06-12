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

function idsDe(container) {
  return Array.from(container.children)
    .map((child) => child.dataset.id)
    .filter(Boolean);
}

function callLivewire(element, target, args) {
  if (!target) {
    return;
  }

  if (element.__livewire) {
    element.__livewire.call(target, ...args);
    return;
  }

  const root = element.closest('[wire\\:id]');
  const livewireId = root?.getAttribute('wire:id');

  if (window.Livewire?.find && livewireId) {
    window.Livewire.find(livewireId)?.call(target, ...args);
  }
}

function bindSortable(element) {
  // Guard por propriedade JS (não data-attribute): o morph do Livewire
  // remove atributos que não existem no HTML re-renderizado, mas preserva
  // propriedades do elemento reaproveitado.
  if (element._afSortable) {
    return;
  }

  const config = parseConfig(element);
  const handle = config.handle || '.drag-handle';

  const instance = new Sortable(element, {
    animation: Number(config.animation || 150),
    ghostClass: config.ghostClass || 'bg-primary/10',
    handle,
    group: config.group || undefined,
    onEnd: (evt) => {
      // Com group (drag entre listas), o onEnd dispara na lista de ORIGEM:
      // o payload precisa ler evt.from/evt.to — nunca element.children.
      if (config.group) {
        const item = evt.item?.dataset?.id;
        const para = evt.to?.dataset?.afContainer;

        if (!item || !para) {
          return;
        }

        const ordens = { [para]: idsDe(evt.to) };

        if (evt.from !== evt.to && evt.from?.dataset?.afContainer) {
          ordens[evt.from.dataset.afContainer] = idsDe(evt.from);
        }

        element.dispatchEvent(
          new CustomEvent('sortable:moved', {
            bubbles: true,
            detail: { item, para, ordens },
          }),
        );

        callLivewire(element, config.target, [item, para, ordens]);
        return;
      }

      const ids = idsDe(element);

      element.dispatchEvent(
        new CustomEvent('sortable:changed', {
          bubbles: true,
          detail: { ids },
        }),
      );

      callLivewire(element, config.target, [ids]);
    },
  });

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
