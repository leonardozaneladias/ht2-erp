// Toggle do menu lateral (Inspinia). O botao #button-toggle-menu existe no topbar
// e tem estilo, mas nao havia handler JS — entao o menu nunca abria/fechava.
// Este modulo liga o clique aos estados de `data-sidenav-size`:
//   - Desktop (> 1140px): alterna entre 'default' (completo) e 'condensed' (so icones).
//   - Mobile  (<= 1140px): o theme-bootstrap ja forca 'offcanvas'; a sidebar aparece
//     quando o <html> ganha a classe `sidenav-enable` (ver _sidenav.css).

const html = document.documentElement;
const MOBILE_BREAKPOINT = 1140;
const TOGGLE_ID = 'button-toggle-menu';
const MENU_ID = 'app-menu';

function isMobile() {
  return window.innerWidth <= MOBILE_BREAKPOINT;
}

function openOffcanvas() {
  html.setAttribute('data-sidenav-size', 'offcanvas');
  html.classList.add('sidenav-enable');
}

function closeOffcanvas() {
  html.classList.remove('sidenav-enable');
}

function toggleSidenav() {
  if (isMobile()) {
    if (html.classList.contains('sidenav-enable')) {
      closeOffcanvas();
    } else {
      openOffcanvas();
    }

    return;
  }

  const current = html.getAttribute('data-sidenav-size') || 'default';
  html.setAttribute('data-sidenav-size', current === 'condensed' ? 'default' : 'condensed');
  closeOffcanvas();
}

function bindToggleButton() {
  const toggleButton = document.getElementById(TOGGLE_ID);

  if (!toggleButton || toggleButton.dataset.afSidenavBound === 'true') {
    return;
  }

  toggleButton.addEventListener('click', (event) => {
    event.preventDefault();
    toggleSidenav();
  });

  toggleButton.dataset.afSidenavBound = 'true';
}

function bindGlobalListeners() {
  if (document.body.dataset.afSidenavGlobalBound === 'true') {
    return;
  }

  // Clique fora da sidebar (no overlay/conteudo) fecha o offcanvas no mobile.
  document.addEventListener('click', (event) => {
    if (!html.classList.contains('sidenav-enable')) {
      return;
    }

    const insideMenu = event.target.closest(`#${MENU_ID}`);
    const onToggle = event.target.closest(`#${TOGGLE_ID}`);

    if (!insideMenu && !onToggle) {
      closeOffcanvas();
    }
  });

  // Clicar num link do menu fecha o offcanvas (navegacao em mobile).
  document.addEventListener('click', (event) => {
    if (isMobile() && event.target.closest(`#${MENU_ID} a`)) {
      closeOffcanvas();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeOffcanvas();
    }
  });

  // O theme-bootstrap so ajusta o tamanho no load; ao cruzar o breakpoint depois
  // precisamos normalizar para a sidebar nao ficar presa visivel nem condensada.
  window.addEventListener('resize', () => {
    if (isMobile()) {
      if (html.getAttribute('data-sidenav-size') !== 'offcanvas') {
        html.setAttribute('data-sidenav-size', 'offcanvas');
        closeOffcanvas();
      }

      return;
    }

    closeOffcanvas();

    if (html.getAttribute('data-sidenav-size') === 'offcanvas') {
      html.setAttribute('data-sidenav-size', 'default');
    }
  });

  document.body.dataset.afSidenavGlobalBound = 'true';
}

function bootSidenav() {
  bindToggleButton();
  bindGlobalListeners();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootSidenav);
} else {
  bootSidenav();
}

document.addEventListener('livewire:navigated', () => {
  closeOffcanvas();
  bindToggleButton();
});
