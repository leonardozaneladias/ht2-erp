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

// Livewire troca o DOM da topbar ao navegar; religa o handler.
document.addEventListener('livewire:navigated', bindToggle);
