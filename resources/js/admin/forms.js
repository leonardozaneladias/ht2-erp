import ChoicesModule from 'choices.js';
import DropzoneModule from 'dropzone';
import flatpickrModule from 'flatpickr';
import InputmaskModule from 'inputmask';
import Quill from 'quill';
import { Portuguese } from 'flatpickr/dist/l10n/pt.js';
import 'quill/dist/quill.snow.css';
import { notify } from './toast';

// Vite 8 (Rolldown) tornou o interop de módulos CJS/UMD consistente: o default
// pode resolver como o próprio valor ou como `{ default: valor }`. Normalizamos
// aqui para que os usos abaixo (new Choices/Dropzone/Inputmask, flatpickr())
// funcionem em qualquer um dos shapes — sem depender de compat deprecado.
// Quill é ESM nativo e dispensa normalização.
const Choices = ChoicesModule?.default ?? ChoicesModule;
const Dropzone = DropzoneModule?.default ?? DropzoneModule;
const flatpickr = flatpickrModule?.default ?? flatpickrModule;
const Inputmask = InputmaskModule?.default ?? InputmaskModule;

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

/**
 * Guards de idempotência.
 *
 * NÃO use `dataset` para marcar "já inicializado": o morph do Livewire reescreve os
 * atributos do elemento a partir do HTML do servidor, que não contém o `data-*` que
 * o JS acabou de setar — a marca some, o re-scan seguinte reinicializa o plugin, e:
 *
 *   • flatpickr: `destroy()` + recria → o input REAL fica vazio, e o usuário vê um
 *     campo obrigatório em branco (o valor ainda está no servidor, mas some da tela);
 *   • Inputmask: `remove()` reescreve o valor cru no input via setter nativo, SEM
 *     disparar evento → o wire:model dessincroniza;
 *   • password/CEP/Quill: `addEventListener` cru duplicado → o toggle de senha mostra
 *     e esconde no mesmo clique, o ViaCEP é chamado duas vezes por blur, e o Quill
 *     empilha toolbars.
 *
 * O WeakSet guarda por REFERÊNCIA do nó: sobrevive ao morph que só atualiza atributos,
 * e o nó descartado sai sozinho da coleção (não vaza). Quando o morph de fato substitui
 * o nó, o nó novo não está no set — e a inicialização acontece, que é o correto.
 *
 * É o mesmo raciocínio que `sortable.js` (`_afSortable`) e o Dropzone (`_afDropzone`)
 * já aplicavam com propriedade JS; aqui o WeakSet serve aos casos que não precisam
 * guardar o handle da instância.
 */
const inicializados = {
  passwordField: new WeakSet(),
  passwordMeter: new WeakSet(),
  datePicker: new WeakSet(),
  inputMask: new WeakSet(),
  cepField: new WeakSet(),
  richEditor: new WeakSet(),
};

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
    if (inicializados.passwordMeter.has(meter)) {
      return;
    }

    const targetId = meter.dataset.passwordMeterStandalone;
    const input = targetId ? document.getElementById(targetId) : null;
    const meterBar = meter.querySelector('[data-password-meter-bar]');
    const meterLabel = meter.querySelector('[data-password-meter-label]');

    if (!input || !meterBar || !meterLabel || input.closest('[data-password-field]')) {
      return;
    }

    inicializados.passwordMeter.add(meter);

    input.addEventListener('input', () => {
      updatePasswordMeter(input, meterBar, meterLabel);
    });

    updatePasswordMeter(input, meterBar, meterLabel);
  });
}

function initPasswordFields(root = document) {
  collectElements(root, '[data-password-field]').forEach((field) => {
    if (inicializados.passwordField.has(field)) {
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

    inicializados.passwordField.add(field);

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

/**
 * Date picker (flatpickr) como Alpine.data — registrado em `alpine:init`.
 *
 * Por que Alpine, e não o bootAdminForms como os demais plugins: o flatpickr MUTA o
 * input original (`type = 'hidden'`) e insere um altInput irmão para exibir d/m/Y. O
 * morph do Livewire desfaz essas mutações — ele reconcilia o DOM com o HTML do
 * servidor, onde o input é `type="text"` e não tem `value`. Resultado: a cada morph
 * (isto é, a cada edição de QUALQUER campo do formulário) o input original reaparecia
 * VAZIO na frente do altInput, e o usuário via o campo de data em branco, mesmo com o
 * valor intacto no servidor. Em campos obrigatórios (data de admissão) isso induzia o
 * operador a digitar tudo de novo.
 *
 * `wire:ignore` no container impede o morph de tocar na subárvore, e o `$wire.entangle`
 * sincroniza nos dois sentidos — o mesmo padrão que o x-shared.money-input já usava.
 */
function registrarAlpineDatePicker() {
  document.addEventListener('alpine:init', () => {
    window.Alpine?.data('afDatePicker', (valorInicial = null, config = {}) => ({
      valor: valorInicial,
      fp: null,

      init() {
        this.fp = flatpickr(this.$refs.campo, {
          disableMobile: true,
          locale: Portuguese,
          allowInput: true,
          // O seletor de mês vira <select> (e o ano é um campo digitável): chegar a 1985
          // deixa de exigir dezenas de cliques na seta de "mês anterior".
          monthSelectorType: 'dropdown',
          ...config,
          onChange: (_datas, texto) => this.aplicar(texto),
          onClose: (_datas, texto) => this.aplicar(texto),
        });

        // Máscara de digitação no campo VISÍVEL (altInput). Sem ela, `allowInput` aceita
        // qualquer coisa e só o flatpickr decide se entendeu — digitar a data de
        // nascimento (o caso em que ninguém quer abrir o calendário) era às cegas.
        this.mascararAltInput();

        // O input não traz `value` no HTML (quem hidrata é o Livewire): o estado
        // inicial vem do entangle.
        if (this.valor) {
          this.fp.setDate(this.valor, false);
        }

        // servidor → tela: reset do formulário, autofill, troca de registro.
        this.$watch('valor', (novo) => {
          if ((novo ?? '') !== (this.fp?.input?.value ?? '')) {
            this.fp?.setDate(novo || '', false);
          }
        });
      },

      /**
       * Máscara de digitação sobre o altInput. O formato vem do próprio flatpickr
       * (altFormat), então intervalo e data-hora ganham a máscara certa sem configuração
       * extra. `clearIncomplete` fica DESLIGADO de propósito: apagar o que o operador
       * digitou pela metade, num campo de data, é hostil — quem julga o valor final é o
       * flatpickr, no blur.
       */
      mascararAltInput() {
        const alt = this.fp?.altInput;
        const formato = this.fp?.config?.altFormat ?? '';

        // Sem altInput (altInput: false) ou em modo intervalo/múltiplo, a máscara não se
        // aplica — o texto ali não é uma data única ("01/01/2026 até 31/01/2026").
        if (!alt || this.fp?.config?.mode !== 'single') {
          return;
        }

        const mascara = formato
          .replace('d', '99')
          .replace('m', '99')
          .replace('Y', '9999')
          .replace('H', '99')
          .replace('i', '99');

        new Inputmask({ mask: mascara, clearIncomplete: false, placeholder: '_' }).mask(alt);

        // Digitou a data completa? O calendário aberto acompanha (sem isso ele fica no
        // mês corrente e o operador acha que a digitação "não pegou").
        alt.addEventListener('input', () => {
          const m = alt.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

          if (!m) {
            return;
          }

          const data = new Date(Number(m[3]), Number(m[2]) - 1, Number(m[1]));

          if (!Number.isNaN(data.getTime())) {
            this.fp?.jumpToDate(data);
          }
        });
      },

      /** tela → servidor (o entangle propaga; sem entangle, o input hidden basta ao POST). */
      aplicar(texto) {
        this.valor = texto;
      },

      destroy() {
        this.fp?.destroy();
      },
    }));
  });
}

function initInputMasks(root = document) {
  collectElements(root, '[data-af-inputmask]').forEach((element) => {
    if (inicializados.inputMask.has(element)) {
      return;
    }

    const config = parseJsonDataset(element, 'afInputmask');
    const inputmask = new Inputmask(config);

    inputmask.mask(element);
    inicializados.inputMask.add(element);
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

    // Assinatura em propriedade JS (não dataset): o morph apagaria o data-*, e o
    // re-scan destruiria/recriaria o Choices a cada morph — perdendo foco e busca
    // digitada. Recriar só quando as OPÇÕES realmente mudarem continua valendo.
    if (element._afChoices && element._afChoicesSignature === signature) {
      return;
    }

    if (element._afChoices?.destroy) {
      element._afChoices.destroy();
    }

    const instance = new Choices(element, config);

    if (config.disabled) {
      instance.disable();
    }

    element._afChoicesSignature = signature;
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
    if (inicializados.cepField.has(element)) {
      return;
    }

    const config = parseJsonDataset(element, 'afCep');
    const loader = element.closest('[data-af-cep-field]')?.querySelector('[data-cep-loading]');

    inicializados.cepField.add(element);

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
          notify('warning', 'CEP não encontrado.');
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
        notify('danger', 'Erro ao buscar CEP.');
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

  notify('danger', `O arquivo ${invalidFile.name} excede ${config.maxSize}MB.`);
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
    // Flag como propriedade JS (não dataset): o morph do Livewire reescreve os
    // atributos do wrapper e apagaria um data-*, fazendo o observer re-bindar.
    if (field._afFileBound) {
      return;
    }

    const trigger = field.querySelector('[data-af-file-trigger]');
    const input = field.querySelector('[data-af-file-input]');

    if (!trigger || !input) {
      return;
    }

    const config = parseJsonDataset(field, 'afFile');
    field._afFileBound = true;

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
    // Flag como propriedade JS (não dataset): o morph do Livewire reescreve os
    // atributos do wrapper e apagaria um data-*, fazendo o observer instanciar
    // um segundo Dropzone sobre o mesmo nó.
    if (field._afDropzone) {
      return;
    }

    const config = parseJsonDataset(field, 'afDropzone');
    const dropzoneElement = field.querySelector('[data-af-dropzone]');
    const previewsContainer = field.querySelector('[data-af-dropzone-previews]');
    const previewTemplate = field.querySelector('[data-af-dropzone-template]');

    if (!dropzoneElement || !previewsContainer || !previewTemplate) {
      return;
    }

    // O input fica fora do wire:ignore (o Livewire re-binda o wire:model no
    // morph); re-consulta a cada uso para nunca segurar um nó substituído.
    const getNativeInput = () => field.querySelector('[data-af-dropzone-input]');

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
      const nativeInput = getNativeInput();

      if (!nativeInput || config.uploadUrl) {
        return;
      }

      const acceptedFiles = instance.files.filter(
        (file) => file.status !== Dropzone.CANCELED && file.status !== Dropzone.ERROR,
      );
      syncNativeFileInput(nativeInput, acceptedFiles);
    };

    // O handler de upload do Livewire ignora change com FileList vazio — remover
    // o último chip precisa zerar a propriedade direto no componente.
    const zerarPropriedadeLivewire = () => {
      const nativeInput = getNativeInput();

      if (!nativeInput || config.uploadUrl || !window.Livewire) {
        return;
      }

      const atributo = nativeInput
        .getAttributeNames()
        .find((nome) => nome === 'wire:model' || nome.startsWith('wire:model.'));
      const model = atributo ? nativeInput.getAttribute(atributo) : null;
      const componentRoot = nativeInput.closest('[wire\\:id]');

      if (!model || !componentRoot) {
        return;
      }

      window.Livewire.find(componentRoot.getAttribute('wire:id'))?.set(model, config.multiple ? [] : null);
    };

    if (!config.multiple) {
      instance.on('maxfilesexceeded', (file) => {
        instance.removeAllFiles();
        instance.addFile(file);
      });
    }

    instance.on('addedfile', syncInputFromDropzone);

    instance.on('removedfile', () => {
      syncInputFromDropzone();

      // Microtask: na troca de arquivo (maxfilesexceeded) o addFile roda no
      // mesmo tick — só zera o modelo se a lista CONTINUAR vazia depois dele.
      queueMicrotask(() => {
        const restantes = instance.files.filter(
          (file) => file.status !== Dropzone.CANCELED && file.status !== Dropzone.ERROR,
        );

        if (restantes.length === 0) {
          zerarPropriedadeLivewire();
        }
      });
    });

    instance.on('error', (file, message) => {
      // Re-sincroniza: o addedfile roda antes de o accept() marcar ERROR — sem
      // isso um arquivo rejeitado (tipo/tamanho) ficaria na propriedade Livewire.
      syncInputFromDropzone();

      const feedback = typeof message === 'string' ? message : `Falha ao enviar ${file.name}.`;
      notify('danger', feedback);
    });

    // Não-imagens não têm thumbnail: o chip nasce com a <img> escondida e um
    // ícone genérico; quando o Dropzone gerar o thumbnail, troca.
    instance.on('thumbnail', (file, dataUrl) => {
      if (!dataUrl || !file.previewElement) {
        return;
      }

      file.previewElement.querySelector('[data-dz-thumbnail]')?.classList.remove('hidden');
      file.previewElement.querySelector('[data-af-dz-icon]')?.classList.add('hidden');
    });

    // Reset programático local (JS da página): limpa os chips sem tocar o modelo.
    field.addEventListener('af-file-upload:reset', () => {
      instance.removeAllFiles(true);
    });

    field._afDropzone = instance;
  });
}

function initRichEditors(root = document) {
  collectElements(root, '[data-af-quill]').forEach((wrapper) => {
    // O wire:ignore do Blade protege os FILHOS do morph, não os atributos do próprio
    // wrapper — um guard em dataset seria apagado assim mesmo, empilhando toolbars.
    if (inicializados.richEditor.has(wrapper)) {
      return;
    }

    const editorEl = wrapper.querySelector('[data-af-quill-editor]');
    const input = wrapper.querySelector('[data-af-quill-input]');

    if (!editorEl || !input) {
      return;
    }

    const config = parseJsonDataset(wrapper, 'afQuillConfig');
    inicializados.richEditor.add(wrapper);

    const quill = new Quill(editorEl, {
      theme: 'snow',
      placeholder: config.placeholder ?? '',
      modules: {
        // Toolbar mínima: formatação inline + listas + link (sem mídia/embeds).
        toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']],
      },
    });

    // Nome acessível: o controle real focável é o .ql-editor (quill.root), não o
    // <textarea> oculto que carrega o <label for>. Replica o label/erro/hint no
    // editor visível — aria-labelledby vem do wrapper (gancho do Blade) e
    // aria-describedby do próprio textarea (já reúne erro+hint).
    const labelledBy = wrapper.dataset.afQuillLabelledby;
    const describedBy = input.getAttribute('aria-describedby');

    if (labelledBy) {
      quill.root.setAttribute('aria-labelledby', labelledBy);
    }

    if (describedBy) {
      quill.root.setAttribute('aria-describedby', describedBy);
    }

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
          '[data-password-field], [data-password-meter-standalone], [data-af-inputmask], [data-af-choices], [data-af-cep], [data-af-file-upload]',
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

registrarAlpineDatePicker();

document.addEventListener('DOMContentLoaded', () => {
  bootAdminForms(document);
  startMutationObserver();
});

document.addEventListener('livewire:navigated', () => {
  bootAdminForms(document);
});

// A11y dos drawers/overlays: o Preline não move o foco ao abrir — sem foco dentro,
// Esc não fecha e o teclado continua atrás do backdrop. O painel já tem
// tabindex="-1"; focá-lo resolve os dois.
document.addEventListener('open.hs.overlay', (evento) => {
  if (evento.target instanceof HTMLElement) {
    evento.target.focus({ preventScroll: true });
  }
});

// Rede de segurança contra races do MutationObserver: ao FIM de cada morph o
// componente inteiro é re-escaneado. Os guards (WeakSet/propriedade JS) tornam o
// re-scan idempotente e barato; elementos que o observer perdeu no meio do morph
// (ex.: linha nova de repeater que nasceu sem máscara) são apanhados aqui.
document.addEventListener('livewire:init', () => {
  window.Livewire?.hook?.('morphed', ({ el, component }) => {
    bootAdminForms(component?.el ?? el ?? document);
  });
});

// Reset dos uploads a partir do servidor: `$this->dispatch('af-file-upload:reset',
// name: 'arquivo')` limpa os chips do campo com aquele name (sem `name`, limpa
// todos os campos da página). Só a UI do Dropzone — o modelo é responsabilidade
// de quem disparou.
window.addEventListener('af-file-upload:reset', (event) => {
  const alvo = event.detail?.name;

  document.querySelectorAll('[data-af-file-upload]').forEach((field) => {
    if (alvo && !field.querySelector(`[name="${alvo}"], [name="${alvo}[]"]`)) {
      return;
    }

    field._afDropzone?.removeAllFiles(true);
  });
});

// wire:navigate troca o conteúdo da página: destrói as instâncias do Dropzone
// para não vazar listeners/observers de telas anteriores.
document.addEventListener('livewire:navigating', () => {
  document.querySelectorAll('[data-af-file-upload]').forEach((field) => {
    field._afDropzone?.destroy?.();
    delete field._afDropzone;
    delete field._afFileBound;
  });
});

window.initAdminForms = bootAdminForms;
