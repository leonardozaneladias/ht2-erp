// Alternância de tema claro/escuro (Inspinia). O botao #light-dark-mode existe
// no topbar e tem os icones (lua/sol) com transicoes via `data-theme`, mas nao
// havia handler JS — entao o tema nunca trocava ao clicar.
//
// O partial theme-bootstrap.blade.php APLICA o tema no load (de sessionStorage
// ou dos atributos do <html>). Este modulo fecha o ciclo: ao clicar, alterna
// data-theme, atualiza window.config e PERSISTE em sessionStorage (mesma chave),
// para o bootstrap reaplicar no proximo carregamento/navegacao.

const html = document.documentElement;
const STORAGE_KEY = '__THEME_CONFIG__';
const TOGGLE_ID = 'light-dark-mode';

function currentTheme() {
  const theme = html.getAttribute('data-theme');

  if (theme === 'system') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  return theme === 'dark' ? 'dark' : 'light';
}

function persist(theme) {
  // Reaproveita a config do bootstrap; cria uma base se ainda nao existir.
  const base = window.config && typeof window.config === 'object' ? window.config : {};
  const next = { ...base, theme };

  window.config = next;
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(next));
}

function applyTheme(theme) {
  html.setAttribute('data-theme', theme);
  persist(theme);
}

// Reaplica o tema salvo no <html>. Necessario apos wire:navigate: o Livewire
// substitui o <html> pela renderizacao do servidor (data-theme="light"
// hardcoded) e NAO re-executa o script inline theme-bootstrap — entao o tema
// escolhido se perdia ao trocar de tela. O sessionStorage persiste na navegacao
// SPA, entao reaplicamos a partir dele.
function syncThemeFromStorage() {
  const raw = sessionStorage.getItem(STORAGE_KEY);

  if (!raw) {
    return;
  }

  try {
    const config = JSON.parse(raw);

    if (config && typeof config === 'object' && config.theme) {
      window.config = config;

      const theme =
        config.theme === 'system'
          ? window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light'
          : config.theme;

      if (html.getAttribute('data-theme') !== theme) {
        html.setAttribute('data-theme', theme);
      }
    }
  } catch (error) {
    // Config malformada no storage: ignora e mantem o tema atual do <html>.
  }
}

function toggleTheme() {
  applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
}

function bindToggle() {
  const button = document.getElementById(TOGGLE_ID);

  if (!button || button.dataset.afThemeBound === 'true') {
    return;
  }

  button.addEventListener('click', toggleTheme);
  button.dataset.afThemeBound = 'true';
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bindToggle);
} else {
  bindToggle();
}

// Apos navegar (wire:navigate): reaplica o tema salvo (o servidor reseta para
// light) e religa o handler do botao (a topbar e re-renderizada).
document.addEventListener('livewire:navigated', () => {
  syncThemeFromStorage();
  bindToggle();
});
