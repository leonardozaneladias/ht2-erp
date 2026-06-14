import Choices from 'choices.js';
import Dropzone from 'dropzone';
import flatpickr from 'flatpickr';
import Inputmask from 'inputmask';
import Quill from 'quill';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import 'quill/dist/quill.snow.css';

const passwordMeterStates = [
  {
    max: 25,
    label: 'Muito fraca',
    barClass: 'bg-danger',
    labelClass: 'text-danger',
  },
  {
    max: 50,
    label: 'Fraca',
    barClass: 'bg-warning',
    labelClass: 'text-warning',
  },
  {
    max: 75,
    label: 'Média',
    barClass: 'bg-info',
    labelClass: 'text-info',
  },
  {
    max: 100,
    label: 'Forte',
    barClass: 'bg-success',
    labelClass: 'text-success',
  },
];

let mutationObserverStarted = false;
let dropzoneConfigured = false;

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

function dispatchToast(variant, message) {
  window.dispatchEvent(
    new CustomEvent('toast', {
      detail: { variant, message },
    }),
  );
}

function calculatePasswordScore(password) {
  let score = 0;

  if (password.length >= 8) score += 25;
  if (password.length >= 12) score += 15;
  if (/[a-z]/.test(password)) score += 10;
  if (/[A-Z]/.test(password)) score += 15;
  if (/[0-9]/.test(password)) score += 15;
  if (/[^a-zA-Z0-9]/.test(password)) score += 20;

  return Math.min(100, score);
}

function resolvePasswordMeterState(score) {
  return passwordMeterStates.find((state) => score <= state.max) ?? passwordMeterStates.at(-1);
}

function updatePasswordMeter(input, meterBar, meterLabel) {
  if (!meterBar || !meterLabel) {
    return;
  }

  const score = calculatePasswordScore(input.value ?? '');

  meterBar.classList.remove('bg-danger', 'bg-warning', 'bg-info', 'bg-success');
  meterLabel.classList.remove('text-danger', 'text-warning', 'text-info', 'text-success', 'text-default-400');

  if (score === 0) {
    meterBar.style.width = '0%';
    meterLabel.textContent = 'Digite uma senha';
    meterLabel.classList.add('text-default-400');
    return;
  }

  const state = resolvePasswordMeterState(score);

  meterBar.style.width = `${score}%`;
  meterBar.classList.add(state.barClass);
  meterLabel.textContent = state.label;
  meterLabel.classList.add(state.labelClass);
}

function initStandalonePasswordMeters(root = document) {
  collectElements(root, '[data-password-meter-standalone]').forEach((meter) => {
    if (meter.dataset.afFormsStandaloneMeterBound === 'true') {
      return;
    }

    const targetId = meter.dataset.passwordMeterStandalone;
    const input = targetId ? document.getElementById(targetId) : null;
    const meterBar = meter.querySelector('[data-password-meter-bar]');
    const meterLabel = meter.querySelector('[data-password-meter-label]');

    if (!input || !meterBar || !meterLabel || input.closest('[data-password-field]')) {
      return;
    }

    meter.dataset.afFormsStandaloneMeterBound = 'true';

    input.addEventListener('input', () => {
      updatePasswordMeter(input, meterBar, meterLabel);
    });

    updatePasswordMeter(input, meterBar, meterLabel);
  });
}

function initPasswordFields(root = document) {
  collectElements(root, '[data-password-field]').forEach((field) => {
    if (field.dataset.afFormsPasswordBound === 'true') {
      return;
    }

    const input = field.querySelector('[data-password-input]');
    const toggle = field.querySelector('[data-password-toggle]');
    const toggleIcon = field.querySelector('[data-password-toggle-icon]');
    const meterBar = field.querySelector('[data-password-meter-bar]');
    const meterLabel = field.querySelector('[data-password-meter-label]');

    if (!input || !toggle) {
      return;
    }

    field.dataset.afFormsPasswordBound = 'true';

    const syncVisibility = () => {
      const isVisible = input.type === 'text';

      if (toggleIcon) {
        toggleIcon.classList.remove('tabler--eye', 'tabler--eye-off');
        toggleIcon.classList.add(isVisible ? 'tabler--eye-off' : 'tabler--eye');
      }

      toggle.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
      toggle.setAttribute('aria-label', isVisible ? 'Ocultar senha' : 'Mostrar senha');
    };

    toggle.addEventListener('click', () => {
      input.type = input.type === 'password' ? 'text' : 'password';
      syncVisibility();
    });

    input.addEventListener('input', () => {
      updatePasswordMeter(input, meterBar, meterLabel);
    });

    syncVisibility();
    updatePasswordMeter(input, meterBar, meterLabel);
  });
}

function initDatePickers(root = document) {
  collectElements(root, '[data-af-flatpickr]').forEach((element) => {
    if (element.dataset.afFormsDatepickerBound === 'true') {
      return;
    }

    const config = parseJsonDataset(element, 'afFlatpickr');

    const syncLivewire = () => {
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));
    };

    flatpickr(element, {
      disableMobile: true,
      locale: Portuguese,
      allowInput: true,
      ...config,
      onChange: [...(config.onChange ?? []), syncLivewire],
      onClose: [...(config.onClose ?? []), syncLivewire],
    });

    element.dataset.afFormsDatepickerBound = 'true';
  });
}

function initInputMasks(root = document) {
  collectElements(root, '[data-af-inputmask]').forEach((element) => {
    if (element.dataset.afFormsInputmaskBound === 'true') {
      return;
    }

    const config = parseJsonDataset(element, 'afInputmask');
    const inputmask = new Inputmask(config);

    inputmask.mask(element);
    element.dataset.afFormsInputmaskBound = 'true';
  });
}

function buildChoicesConfig(element) {
  const rawConfig = parseJsonDataset(element, 'afChoices');

  return {
    allowHTML: false,
    shouldSort: rawConfig.shouldSort ?? false,
    placeholder: true,
    placeholderValue: rawConfig.placeholder ?? undefined,
    searchEnabled: rawConfig.searchable ?? true,
    removeItemButton: rawConfig.removeItem ?? Boolean(rawConfig.multiple),
    duplicateItemsAllowed: rawConfig.allowDuplicates ?? false,
    maxItemCount: rawConfig.maxItems ?? -1,
    addItems: rawConfig.allowCreate ?? false,
    addChoices: rawConfig.allowCreate ?? false,
    editItems: rawConfig.allowCreate ?? false,
    delimiter: rawConfig.delimiter ?? ',',
    noResultsText: 'Nenhum resultado encontrado',
    noChoicesText: 'Nenhuma opção disponível',
    itemSelectText: 'Pressione para selecionar',
    ...rawConfig,
  };
}

function buildChoicesSignature(element, config) {
  return JSON.stringify({
    config,
    options: Array.from(element.options).map((option) => ({
      value: option.value,
      label: option.text,
      selected: option.selected,
      disabled: option.disabled,
      group: option.parentElement?.tagName === 'OPTGROUP' ? option.parentElement.label : null,
    })),
  });
}

function initChoiceFields(root = document) {
  collectElements(root, '[data-af-choices]').forEach((element) => {
    const config = buildChoicesConfig(element);
    const signature = buildChoicesSignature(element, config);

    if (element.dataset.afFormsChoicesBound === 'true' && element.dataset.afFormsChoicesSignature === signature) {
      return;
    }

    if (element._afChoices?.destroy) {
      element._afChoices.destroy();
    }

    const instance = new Choices(element, config);

    if (config.disabled) {
      instance.disable();
    }

    element.dataset.afFormsChoicesBound = 'true';
    element.dataset.afFormsChoicesSignature = signature;
    element._afChoices = instance;
  });
}

function resolveLivewireComponent(element) {
  const wireRoot = element.closest('[wire\\:id]');

  if (!wireRoot) {
    return null;
  }

  const wireId = wireRoot.getAttribute('wire:id');

  if (!wireId || !window.Livewire?.find) {
    return null;
  }

  return window.Livewire.find(wireId);
}

function initCepFields(root = document) {
  collectElements(root, '[data-af-cep]').forEach((element) => {
    if (element.dataset.afFormsCepBound === 'true') {
      return;
    }

    const config = parseJsonDataset(element, 'afCep');
    const loader = element.closest('[data-af-cep-field]')?.querySelector('[data-cep-loading]');

    element.dataset.afFormsCepBound = 'true';

    element.addEventListener('blur', async () => {
      const cleanCep = (element.value || '').replace(/\D/g, '');

      if (cleanCep.length !== 8) {
        return;
      }

      if (loader) {
        loader.classList.remove('hidden');
      }

      try {
        const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`);
        const data = await response.json();

        if (data.erro) {
          dispatchToast('warning', 'CEP não encontrado.');
          return;
        }

        const detail = {
          cep: cleanCep,
          logradouro: data.logradouro || '',
          bairro: data.bairro || '',
          cidade: data.localidade || '',
          uf: data.uf || '',
          raw: data,
        };

        element.dispatchEvent(
          new CustomEvent(config.eventName || 'cep-filled', {
            bubbles: true,
            detail,
          }),
        );

        if (config.target) {
          const component = resolveLivewireComponent(element);

          if (component?.call) {
            component.call(config.target, detail);
          }
        }
      } catch (error) {
        console.error('Erro ao buscar CEP', error);
        dispatchToast('danger', 'Erro ao buscar CEP.');
      } finally {
        if (loader) {
          loader.classList.add('hidden');
        }
      }
    });
  });
}

function formatFileSize(bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) {
    return '0 KB';
  }

  if (bytes >= 1024 * 1024) {
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  }

  return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function buildNativeFileList(files) {
  const transfer = new DataTransfer();

  files.forEach((file) => {
    if (file instanceof File) {
      transfer.items.add(file);
    }
  });

  return transfer.files;
}

function syncNativeFileInput(input, files) {
  if (!input) {
    return;
  }

  input.files = buildNativeFileList(files);
  input.dispatchEvent(new Event('change', { bubbles: true }));
}

function validateSelectedFiles(files, config) {
  const maxBytes = Number(config.maxSize ?? 0) * 1024 * 1024;

  if (!maxBytes) {
    return true;
  }

  const invalidFile = files.find((file) => file.size > maxBytes);

  if (!invalidFile) {
    return true;
  }

  dispatchToast('danger', `O arquivo ${invalidFile.name} excede ${config.maxSize}MB.`);
  return false;
}

function updateSimpleFilePreview(field, files = []) {
  const preview = field.querySelector('[data-af-file-preview]');
  const previewImage = field.querySelector('[data-af-file-preview-image]');
  const previewIcon = field.querySelector('[data-af-file-preview-icon]');
  const fileName = field.querySelector('[data-af-file-name]');
  const emptyState = field.querySelector('[data-af-file-empty]');
  const fileList = field.querySelector('[data-af-file-list]');
  const normalizedFiles = Array.from(files);

  if (normalizedFiles.length === 0) {
    if (preview) preview.classList.add('hidden');
    if (emptyState) emptyState.classList.remove('hidden');
    if (fileName) fileName.textContent = '';
    if (fileList) {
      fileList.classList.add('hidden');
      fileList.innerHTML = '';
    }
    return;
  }

  const [firstFile] = normalizedFiles;

  if (preview) preview.classList.remove('hidden');
  if (emptyState) emptyState.classList.add('hidden');
  if (fileName) {
    fileName.textContent =
      normalizedFiles.length === 1 ? firstFile.name : `${normalizedFiles.length} arquivos selecionados`;
  }

  if (previewImage && firstFile.type.startsWith('image/')) {
    previewImage.src = URL.createObjectURL(firstFile);
    previewImage.classList.remove('hidden');

    if (previewIcon) {
      previewIcon.classList.add('hidden');
    }
  } else if (previewImage) {
    previewImage.classList.add('hidden');

    if (previewIcon) {
      previewIcon.classList.remove('hidden');
    }
  }

  if (fileList) {
    fileList.innerHTML = normalizedFiles
      .map(
        (file) =>
          `<li class="rounded-xl border border-default-300 bg-card px-3 py-2">${file.name} <span class="text-xs text-default-400">(${formatFileSize(file.size)})</span></li>`,
      )
      .join('');

    fileList.classList.toggle('hidden', normalizedFiles.length <= 1);
  }
}

function initSimpleFileUploads(root = document) {
  collectElements(root, "[data-af-file-upload][data-af-file-mode='livewire']").forEach((field) => {
    if (field.dataset.afFormsFileBound === 'true') {
      return;
    }

    const trigger = field.querySelector('[data-af-file-trigger]');
    const input = field.querySelector('[data-af-file-input]');

    if (!trigger || !input) {
      return;
    }

    const config = parseJsonDataset(field, 'afFile');
    field.dataset.afFormsFileBound = 'true';

    trigger.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
      const files = Array.from(input.files ?? []);

      if (files.length === 0) {
        updateSimpleFilePreview(field, []);
        return;
      }

      if (!validateSelectedFiles(files, config)) {
        input.value = '';
        return;
      }

      updateSimpleFilePreview(field, files);
    });
  });
}

function initDropzones(root = document) {
  if (!dropzoneConfigured) {
    Dropzone.autoDiscover = false;
    dropzoneConfigured = true;
  }

  collectElements(root, "[data-af-file-upload][data-af-file-mode='dropzone']").forEach((field) => {
    if (field.dataset.afFormsDropzoneBound === 'true') {
      return;
    }

    const config = parseJsonDataset(field, 'afDropzone');
    const dropzoneElement = field.querySelector('[data-af-dropzone]');
    const previewsContainer = field.querySelector('[data-af-dropzone-previews]');
    const previewTemplate = field.querySelector('[data-af-dropzone-template]');
    const nativeInput = field.querySelector('[data-af-dropzone-input]');

    if (!dropzoneElement || !previewsContainer || !previewTemplate) {
      return;
    }

    const instance = new Dropzone(dropzoneElement, {
      url: config.uploadUrl || '/',
      autoProcessQueue: Boolean(config.uploadUrl),
      acceptedFiles: config.accept || null,
      maxFilesize: config.maxSize || null,
      uploadMultiple: config.multiple ?? false,
      maxFiles: config.multiple ? null : 1,
      previewsContainer,
      previewTemplate: previewTemplate.innerHTML,
      clickable: dropzoneElement,
      addRemoveLinks: false,
      headers: config.uploadUrl
        ? {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          }
        : undefined,
    });

    const syncInputFromDropzone = () => {
      if (!nativeInput || config.uploadUrl) {
        return;
      }

      const acceptedFiles = instance.files.filter(
        (file) => file.status !== Dropzone.CANCELED && file.status !== Dropzone.ERROR,
      );
      syncNativeFileInput(nativeInput, acceptedFiles);
    };

    if (!config.multiple) {
      instance.on('maxfilesexceeded', (file) => {
        instance.removeAllFiles();
        instance.addFile(file);
      });
    }

    instance.on('addedfile', syncInputFromDropzone);
    instance.on('removedfile', syncInputFromDropzone);
    instance.on('error', (file, message) => {
      const feedback = typeof message === 'string' ? message : `Falha ao enviar ${file.name}.`;
      dispatchToast('danger', feedback);
    });

    field.dataset.afFormsDropzoneBound = 'true';
    field._afDropzone = instance;
  });
}

function initRichEditors(root = document) {
  collectElements(root, '[data-af-quill]').forEach((wrapper) => {
    if (wrapper.dataset.afFormsQuillBound === 'true') {
      return;
    }

    const editorEl = wrapper.querySelector('[data-af-quill-editor]');
    const input = wrapper.querySelector('[data-af-quill-input]');

    if (!editorEl || !input) {
      return;
    }

    const config = parseJsonDataset(wrapper, 'afQuillConfig');
    wrapper.dataset.afFormsQuillBound = 'true';

    const quill = new Quill(editorEl, {
      theme: 'snow',
      placeholder: config.placeholder ?? '',
      modules: {
        // Toolbar mínima: formatação inline + listas + link (sem mídia/embeds).
        toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']],
      },
    });

    // Conteúdo inicial vem do textarea (POST/wire:model/old()).
    if (input.value.trim() !== '') {
      quill.clipboard.dangerouslyPasteHTML(input.value.trim());
    }

    quill.on('text-change', () => {
      // Editor vazio gera "<p><br></p>"; normaliza para string vazia.
      input.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    wrapper._afQuill = quill;
  });
}

function bootAdminForms(root = document) {
  initPasswordFields(root);
  initStandalonePasswordMeters(root);
  initDatePickers(root);
  initInputMasks(root);
  initChoiceFields(root);
  initCepFields(root);
  initSimpleFileUploads(root);
  initDropzones(root);
  initRichEditors(root);
}

function startMutationObserver() {
  if (mutationObserverStarted || !document.body) {
    return;
  }

  mutationObserverStarted = true;

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }

        bootAdminForms(node);
      });

      if (mutation.target instanceof HTMLElement) {
        const host = mutation.target.closest?.(
          '[data-password-field], [data-password-meter-standalone], [data-af-flatpickr], [data-af-inputmask], [data-af-choices], [data-af-cep], [data-af-file-upload]',
        );

        if (host) {
          bootAdminForms(host);
        }
      }
    });
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bootAdminForms(document);
  startMutationObserver();
});

document.addEventListener('livewire:navigated', () => {
  bootAdminForms(document);
});

window.initAdminForms = bootAdminForms;
