/**
 * Avatar cropper — crop circular interativo (arrastar + zoom) antes do upload.
 *
 * Funciona por DELEGAÇÃO de eventos no document (imune a morphs do Livewire e
 * ao wire:navigate — sem re-init). O cropperjs e seu CSS são importados
 * dinamicamente no primeiro uso (zero custo no bundle principal).
 *
 * Markup esperado (ver x-shared.avatar-cropper):
 *   <div data-af-avatar-cropper data-af-avatar='{"model":"avatar","maxSize":2}'>
 *     <button data-af-avatar-trigger>…</button>
 *     <input type="file" data-af-avatar-input class="hidden" />
 *     <div data-af-avatar-cropper-modal class="hidden">
 *       <img data-af-avatar-stage />
 *       <button data-af-avatar-zoom-out /> <button data-af-avatar-zoom-in />
 *       <button data-af-avatar-cancel /> <button data-af-avatar-apply />
 *     </div>
 *   </div>
 *
 * Fluxo: seleção → validação client-side → modal com Cropper (aspect 1:1,
 * máscara redonda via CSS) → "Aplicar" gera canvas 512×512 → JPEG q0.9 →
 * $wire.$upload(model, file) → Livewire re-renderiza com temporaryUrl().
 */

const TIPOS_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];

let sessao = null; // { wrapper, modal, input, cropper, objectUrl }

function toast(variant, message) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { variant, message } }));
}

function configDe(wrapper) {
  try {
    return JSON.parse(wrapper.dataset.afAvatar || '{}');
  } catch {
    return {};
  }
}

function resolveWire(element) {
  const wireRoot = element.closest('[wire\\:id]');
  const wireId = wireRoot?.getAttribute('wire:id');

  if (!wireId || !window.Livewire?.find) {
    return null;
  }

  return window.Livewire.find(wireId);
}

function encerrarSessao() {
  if (!sessao) {
    return;
  }

  sessao.cropper?.destroy();

  if (sessao.objectUrl) {
    URL.revokeObjectURL(sessao.objectUrl);
  }

  if (sessao.input) {
    sessao.input.value = '';
  }

  if (sessao.modal) {
    sessao.modal.style.display = 'none';
  }

  document.documentElement.classList.remove('overflow-hidden');
  sessao = null;
}

async function abrirCropper(wrapper, file) {
  const config = configDe(wrapper);
  const maxSizeMb = Number(config.maxSize || 2);

  if (!TIPOS_ACEITOS.includes(file.type)) {
    toast('error', 'Formato inválido. Use PNG, JPG ou WebP.');
    return;
  }

  if (file.size > maxSizeMb * 1024 * 1024) {
    toast('error', `A imagem deve ter no máximo ${maxSizeMb} MB.`);
    return;
  }

  const modal = wrapper.querySelector('[data-af-avatar-cropper-modal]');
  const stage = modal?.querySelector('[data-af-avatar-stage]');

  if (!modal || !stage) {
    return;
  }

  const objectUrl = URL.createObjectURL(file);
  stage.src = objectUrl;
  modal.style.display = 'flex';
  document.documentElement.classList.add('overflow-hidden');

  const [{ default: Cropper }] = await Promise.all([import('cropperjs'), import('cropperjs/dist/cropper.css')]);

  sessao = {
    wrapper,
    modal,
    input: wrapper.querySelector('[data-af-avatar-input]'),
    objectUrl,
    cropper: new Cropper(stage, {
      aspectRatio: 1,
      viewMode: 1,
      dragMode: 'move',
      autoCropArea: 1,
      cropBoxMovable: false,
      cropBoxResizable: false,
      toggleDragModeOnDblclick: false,
      guides: false,
      center: false,
      background: false,
    }),
  };
}

function aplicarCrop() {
  if (!sessao?.cropper) {
    return;
  }

  const { wrapper, modal } = sessao;
  const config = configDe(wrapper);
  const wire = resolveWire(wrapper);
  const botao = modal.querySelector('[data-af-avatar-apply]');

  if (!wire || !config.model) {
    encerrarSessao();
    return;
  }

  const canvas = sessao.cropper.getCroppedCanvas({
    width: 512,
    height: 512,
    imageSmoothingQuality: 'high',
  });

  botao?.setAttribute('disabled', 'disabled');

  canvas.toBlob(
    (blob) => {
      if (!blob) {
        botao?.removeAttribute('disabled');
        toast('error', 'Não foi possível processar a imagem.');
        return;
      }

      // Nome + type coerentes: a validação `mimes` do temp upload depende disto.
      const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

      wire.$upload(
        config.model,
        file,
        () => {
          botao?.removeAttribute('disabled');
          encerrarSessao();
        },
        () => {
          botao?.removeAttribute('disabled');
          toast('error', 'Falha ao enviar a imagem. Tente novamente.');
        },
      );
    },
    'image/jpeg',
    0.9,
  );
}

document.addEventListener('change', (event) => {
  const input = event.target.closest?.('[data-af-avatar-input]');

  if (!input) {
    return;
  }

  const wrapper = input.closest('[data-af-avatar-cropper]');
  const file = input.files?.[0];

  if (wrapper && file) {
    abrirCropper(wrapper, file);
  }
});

document.addEventListener('click', (event) => {
  const alvo = event.target;

  if (alvo.closest?.('[data-af-avatar-trigger]')) {
    const wrapper = alvo.closest('[data-af-avatar-cropper]');
    wrapper?.querySelector('[data-af-avatar-input]')?.click();
    return;
  }

  if (!sessao) {
    return;
  }

  if (alvo.closest?.('[data-af-avatar-zoom-in]')) {
    sessao.cropper?.zoom(0.1);
  } else if (alvo.closest?.('[data-af-avatar-zoom-out]')) {
    sessao.cropper?.zoom(-0.1);
  } else if (alvo.closest?.('[data-af-avatar-apply]')) {
    aplicarCrop();
  } else if (alvo.closest?.('[data-af-avatar-cancel]') || alvo.matches?.('[data-af-avatar-cropper-modal]')) {
    // Cancelar explícito ou clique no backdrop (o próprio modal overlay).
    encerrarSessao();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && sessao) {
    encerrarSessao();
  }
});

// Navegação SPA: não deixar modal/cropper órfãos.
document.addEventListener('livewire:navigated', () => encerrarSessao());
