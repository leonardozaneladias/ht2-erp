/**
 * Avatar cropper — crop circular interativo (arrastar + zoom + girar) antes do upload.
 *
 * Funciona por DELEGAÇÃO de eventos no document (imune a morphs do Livewire e
 * ao wire:navigate — sem re-init). O cropperjs e seu CSS são importados
 * dinamicamente no primeiro uso (zero custo no bundle principal).
 *
 * Markup esperado (ver x-shared.avatar-cropper):
 *   <div data-af-avatar-cropper data-af-avatar='{"model":"avatar","maxSize":2}'>
 *     <button data-af-avatar-trigger>…</button>
 *     <input type="file" data-af-avatar-input class="hidden" />
 *     <div data-af-avatar-cropper-modal style="display:none">
 *       <img data-af-avatar-stage />
 *       <button data-af-avatar-zoom-out /> <input data-af-avatar-zoom-slider />
 *       <button data-af-avatar-zoom-in /> <button data-af-avatar-rotate />
 *       <button data-af-avatar-cancel /> <button data-af-avatar-apply />
 *     </div>
 *   </div>
 *
 * Fluxo: seleção → validação client-side → modal com Cropper (aspect 1:1,
 * máscara redonda via CSS) → "Aplicar" gera canvas 512×512 → JPEG q0.9 →
 * $wire.$upload(model, file) → Livewire re-renderiza com temporaryUrl().
 *
 * Zoom: o slider (0–100) mapeia linearmente minRatio→maxRatio, calculados no
 * `ready` do Cropper (min = enquadramento inicial; max = 4×). Scroll/pinch e
 * os botões −/+ movem o mesmo slider — sincronização bidirecional.
 */

import { notify } from './toast';

const TIPOS_ACEITOS = ['image/jpeg', 'image/png', 'image/webp'];

let sessao = null; // { wrapper, modal, input, slider, cropper, objectUrl, minRatio, maxRatio, zoomViaSlider }
let aberturaAtual = 0; // token de corrida: só a abertura mais recente instancia o Cropper

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
    sessao.modal.classList.remove('is-open');
    sessao.modal.style.display = 'none';
  }

  document.documentElement.classList.remove('overflow-hidden');
  sessao = null;
}

/* --- Zoom: slider 0–100 ↔ ratio do cropper ------------------------------- */

function prepararZoom() {
  if (!sessao?.cropper) {
    return;
  }

  const dados = sessao.cropper.getImageData();

  if (!dados.naturalWidth) {
    return;
  }

  sessao.minRatio = dados.width / dados.naturalWidth;
  sessao.maxRatio = sessao.minRatio * 4;
  posicionarSlider(0);
}

function posicionarSlider(valor) {
  if (!sessao?.slider) {
    return;
  }

  const v = Math.round(Math.min(100, Math.max(0, valor)));

  sessao.slider.value = String(v);
  // Preenchimento da trilha à esquerda do thumb (ver CSS do slider).
  sessao.slider.style.setProperty('--af-zoom', String(v));
}

function ratioDoSlider(valor) {
  return sessao.minRatio + (sessao.maxRatio - sessao.minRatio) * (valor / 100);
}

function aplicarZoomDoSlider(valor) {
  if (!sessao?.cropper || sessao.minRatio == null) {
    return;
  }

  posicionarSlider(valor);
  sessao.zoomViaSlider = true;
  sessao.cropper.zoomTo(ratioDoSlider(Number(sessao.slider.value)));
  sessao.zoomViaSlider = false;
}

function sincronizarZoom(event) {
  if (!sessao || sessao.minRatio == null) {
    return;
  }

  const ratio = event.detail.ratio;

  if (ratio > sessao.maxRatio) {
    event.preventDefault();
    posicionarSlider(100);
    return;
  }

  if (sessao.zoomViaSlider) {
    return; // o zoom veio do próprio slider — já está posicionado.
  }

  // Scroll/pinch: reflete a posição no slider.
  posicionarSlider(((ratio - sessao.minRatio) / (sessao.maxRatio - sessao.minRatio)) * 100);
}

/* --- Sessão -------------------------------------------------------------- */

async function abrirCropper(wrapper, file) {
  // Sessão anterior pendente (ex.: change disparado duas vezes): destruir antes
  // de recriar — um segundo Cropper sobre o mesmo <img> é ignorado pela lib.
  encerrarSessao();

  const config = configDe(wrapper);
  const maxSizeMb = Number(config.maxSize || 2);

  if (!TIPOS_ACEITOS.includes(file.type)) {
    notify('error', 'Formato inválido. Use PNG, JPG ou WebP.');
    return;
  }

  if (file.size > maxSizeMb * 1024 * 1024) {
    notify('error', `A imagem deve ter no máximo ${maxSizeMb} MB.`);
    return;
  }

  const modal = wrapper.querySelector('[data-af-avatar-cropper-modal]');
  const stage = modal?.querySelector('[data-af-avatar-stage]');

  if (!modal || !stage) {
    return;
  }

  const token = ++aberturaAtual;
  const objectUrl = URL.createObjectURL(file);
  stage.src = objectUrl;
  modal.style.display = 'flex';
  requestAnimationFrame(() => modal.classList.add('is-open'));
  document.documentElement.classList.add('overflow-hidden');

  const [{ default: Cropper }] = await Promise.all([import('cropperjs'), import('cropperjs/dist/cropper.css')]);

  if (token !== aberturaAtual) {
    URL.revokeObjectURL(objectUrl);
    return; // outra abertura começou enquanto a lib carregava.
  }

  sessao = {
    wrapper,
    modal,
    input: wrapper.querySelector('[data-af-avatar-input]'),
    slider: modal.querySelector('[data-af-avatar-zoom-slider]'),
    objectUrl,
    minRatio: null,
    maxRatio: null,
    zoomViaSlider: false,
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
      ready: prepararZoom,
      zoom: sincronizarZoom,
    }),
  };
}

function alternarLoadingAplicar(botao, ativo) {
  if (!botao) {
    return;
  }

  botao.toggleAttribute('disabled', ativo);

  const icone = botao.querySelector('.iconify');
  icone?.classList.toggle('tabler--check', !ativo);
  icone?.classList.toggle('tabler--loader-2', ativo);
  icone?.classList.toggle('animate-spin', ativo);
}

/**
 * Progresso do upload. O $wire.$upload aceita um 5º callback com o percentual — ele
 * simplesmente não era usado, e um recorte de 512×512 numa conexão ruim ficava sem
 * nenhum sinal de vida entre o "Aplicar" e o preview.
 */
function atualizarProgresso(modal, percentual) {
  const barra = modal?.querySelector('[data-af-avatar-progress-bar]');
  const caixa = modal?.querySelector('[data-af-avatar-progress]');

  if (!barra || !caixa) {
    return;
  }

  const concluido = percentual >= 100;

  caixa.classList.toggle('hidden', percentual <= 0 || concluido);
  caixa.setAttribute('aria-valuenow', String(percentual));
  barra.style.width = `${percentual}%`;
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

  alternarLoadingAplicar(botao, true);

  canvas.toBlob(
    (blob) => {
      if (!blob) {
        alternarLoadingAplicar(botao, false);
        notify('error', 'Não foi possível processar a imagem.');
        return;
      }

      // Nome + type coerentes: a validação `mimes` do temp upload depende disto.
      const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });

      wire.$upload(
        config.model,
        file,
        () => {
          alternarLoadingAplicar(botao, false);
          atualizarProgresso(modal, 100);
          encerrarSessao();
        },
        () => {
          alternarLoadingAplicar(botao, false);
          atualizarProgresso(modal, 0);
          notify('error', 'Falha ao enviar a imagem. Tente novamente.');
        },
        (evento) => atualizarProgresso(modal, evento.detail.progress),
      );
    },
    'image/jpeg',
    0.9,
  );
}

/* --- Entradas: arquivo, arrastar-soltar, colar ---------------------------- */

/**
 * O cropper aceita a imagem por três caminhos, e todos caem aqui: escolher o arquivo,
 * arrastar sobre o avatar, ou colar (Ctrl+V) — colar é o mais rápido quando a foto veio
 * de um print ou do WhatsApp, e era o que faltava.
 */
function primeiraImagemDe(lista) {
  return [...(lista ?? [])].find((item) => item.type?.startsWith('image/')) ?? null;
}

/** Onde o Ctrl+V deve cair: o cropper com foco dentro, ou o único visível na tela. */
function wrapperParaColar() {
  const visiveis = [...document.querySelectorAll('[data-af-avatar-cropper]')].filter(
    (w) => w.checkVisibility?.() ?? w.offsetParent !== null,
  );

  if (visiveis.length === 0) {
    return null;
  }

  const comFoco = visiveis.find((w) => w.contains(document.activeElement));

  return comFoco ?? (visiveis.length === 1 ? visiveis[0] : null);
}

/* --- Delegação ------------------------------------------------------------ */

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

// Arrastar e soltar sobre o avatar.
document.addEventListener('dragover', (event) => {
  const wrapper = event.target.closest?.('[data-af-avatar-cropper]');

  if (!wrapper) {
    return;
  }

  event.preventDefault(); // sem isso o browser abre a imagem numa aba
  wrapper.classList.add('is-dragging');
});

document.addEventListener('dragleave', (event) => {
  const wrapper = event.target.closest?.('[data-af-avatar-cropper]');

  // relatedTarget fora do wrapper = o ponteiro saiu de verdade (não só trocou de filho).
  if (wrapper && !wrapper.contains(event.relatedTarget)) {
    wrapper.classList.remove('is-dragging');
  }
});

document.addEventListener('drop', (event) => {
  const wrapper = event.target.closest?.('[data-af-avatar-cropper]');

  if (!wrapper) {
    return;
  }

  event.preventDefault();
  wrapper.classList.remove('is-dragging');

  const file = primeiraImagemDe(event.dataTransfer?.files);

  if (file) {
    abrirCropper(wrapper, file);
  } else {
    notify('warning', 'Solte um arquivo de imagem (PNG, JPG ou WebP).');
  }
});

// Colar (Ctrl+V) — só quando não estamos digitando num campo.
document.addEventListener('paste', (event) => {
  const digitando = event.target.closest?.('input, textarea, [contenteditable]');

  if (digitando || sessao) {
    return;
  }

  const file = primeiraImagemDe(event.clipboardData?.files);
  const wrapper = file ? wrapperParaColar() : null;

  if (wrapper) {
    event.preventDefault();
    abrirCropper(wrapper, file);
  }
});

document.addEventListener('input', (event) => {
  const slider = event.target.closest?.('[data-af-avatar-zoom-slider]');

  if (slider && sessao) {
    aplicarZoomDoSlider(Number(slider.value));
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
    aplicarZoomDoSlider(Number(sessao.slider?.value || 0) + 10);
  } else if (alvo.closest?.('[data-af-avatar-zoom-out]')) {
    aplicarZoomDoSlider(Number(sessao.slider?.value || 0) - 10);
  } else if (alvo.closest?.('[data-af-avatar-rotate]')) {
    sessao.cropper?.rotate(90);
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
