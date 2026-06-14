// Preview ao vivo do Centro de Aparência.
//
// Aplica os atributos data-* no <html> e injeta a paleta num <style> no <head>,
// usando EXATAMENTE a mesma fórmula do BrandingService (PHP) — assim o preview
// é idêntico ao estado que será salvo. Exposto em window.AppearancePreview para
// o wrapper Alpine da aba consumir; descarta restaura o estado do servidor.

const STYLE_ID = 'af-appearance-preview';
const HEX = /^#[0-9a-f]{6}$/i;

// chave do componente Livewire -> atributo data-* no <html>
const ATRIBUTOS = {
  tema: 'data-theme',
  skin: 'data-skin',
  topbar_color: 'data-topbar-color',
  menu_color: 'data-menu-color',
  layout_width: 'data-layout-width',
  sidenav_size: 'data-sidenav-size',
};

function canalLinear(valor) {
  const c = valor / 255;

  return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

function luminancia(hex) {
  const h = hex.replace('#', '');

  return (
    0.2126 * canalLinear(parseInt(h.slice(0, 2), 16)) +
    0.7152 * canalLinear(parseInt(h.slice(2, 4), 16)) +
    0.0722 * canalLinear(parseInt(h.slice(4, 6), 16))
  );
}

function corDeContraste(hex) {
  const lum = luminancia(hex);
  const contrasteBranco = 1.05 / (lum + 0.05);
  const contrasteEscuro = (lum + 0.05) / (luminancia('#1c2531') + 0.05);

  return contrasteBranco >= contrasteEscuro ? '#ffffff' : '#1c2531';
}

function escalaTonal(hex) {
  return {
    50: `color-mix(in srgb, ${hex} 8%, #fff)`,
    100: `color-mix(in srgb, ${hex} 16%, #fff)`,
    200: `color-mix(in srgb, ${hex} 30%, #fff)`,
    300: `color-mix(in srgb, ${hex} 50%, #fff)`,
    400: `color-mix(in srgb, ${hex} 72%, #fff)`,
    500: hex,
    600: `color-mix(in srgb, ${hex} 85%, #000)`,
    700: `color-mix(in srgb, ${hex} 70%, #000)`,
    800: `color-mix(in srgb, ${hex} 55%, #000)`,
    900: `color-mix(in srgb, ${hex} 42%, #000)`,
    950: `color-mix(in srgb, ${hex} 30%, #000)`,
  };
}

function cssVariables(cores) {
  const linhas = [];

  for (const [nome, hex] of Object.entries(cores)) {
    if (!HEX.test(hex)) {
      continue;
    }

    linhas.push(`--color-${nome}: ${hex};`);
    linhas.push(`--color-${nome}-hover: color-mix(in srgb, ${hex} 88%, #000);`);
    linhas.push(`--color-${nome}-foreground: ${corDeContraste(hex)};`);
  }

  if (cores.primary && HEX.test(cores.primary)) {
    for (const [tom, valor] of Object.entries(escalaTonal(cores.primary))) {
      linhas.push(`--color-primary-${tom}: ${valor};`);
    }
  }

  return linhas.join('\n');
}

function aplicarAtributos(atributos = {}) {
  const html = document.documentElement;

  for (const [chave, attr] of Object.entries(ATRIBUTOS)) {
    let valor = atributos[chave];

    if (valor == null || valor === '') {
      continue;
    }

    if (chave === 'tema' && valor === 'system') {
      valor = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    if (chave === 'sidenav_size' && window.innerWidth <= 1140) {
      valor = 'offcanvas';
    }

    html.setAttribute(attr, valor);
  }
}

function aplicarPaleta(cores = {}) {
  const vars = cssVariables(cores);
  let style = document.getElementById(STYLE_ID);

  if (!style) {
    style = document.createElement('style');
    style.id = STYLE_ID;
    document.head.appendChild(style);
  }

  // Mesmo seletor do branding-css (vence os skins por especificidade + ordem).
  style.textContent = vars ? `:root,\nhtml[data-skin] {\n${vars}\n}` : '';
}

function descartar() {
  const style = document.getElementById(STYLE_ID);

  if (style) {
    style.remove();
  }

  // Restaura os atributos a partir do estado renderizado pelo servidor.
  const base = window.defaultConfig || {};
  const html = document.documentElement;
  const mapa = {
    'data-theme': base.theme,
    'data-skin': base.skin,
    'data-topbar-color': base['topbar-color'],
    'data-menu-color': base['sidenav-color'],
    'data-layout-width': base.width,
    'data-sidenav-size': base['sidenav-size'],
  };

  for (const [attr, valor] of Object.entries(mapa)) {
    if (valor) {
      html.setAttribute(attr, valor);
    }
  }
}

const AppearancePreview = {
  aplicar(payload = {}) {
    if (payload.atributos) {
      aplicarAtributos(payload.atributos);
    }

    if (payload.cores) {
      aplicarPaleta(payload.cores);
    }
  },
  aplicarAtributos,
  aplicarPaleta,
  descartar,
};

window.AppearancePreview = AppearancePreview;

// Remove o <style> de preview ao navegar entre páginas (SPA) para não vazar.
document.addEventListener('livewire:navigated', () => {
  const style = document.getElementById(STYLE_ID);

  if (style) {
    style.remove();
  }
});

export default AppearancePreview;
