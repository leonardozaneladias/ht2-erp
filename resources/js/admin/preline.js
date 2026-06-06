// Preline (componentes data-hs-*): abas, dropdowns, accordion, tooltips, etc.
// O Preline so executa autoInit no evento `load` nativo do navegador. Em
// navegacao SPA do Livewire (wire:navigate) o `load` nao dispara, entao os
// novos data-hs-* ficam sem inicializacao — por isso as abas e dropdowns so
// voltavam a funcionar apos um refresh (F5).
//
// autoInit() e idempotente: descarta da colecao os elementos que sairam do DOM
// e so instancia os novos, logo pode ser chamado a cada navegacao sem duplicar
// listeners nem perder estado.
function bootPreline() {
  window.HSStaticMethods?.autoInit();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootPreline);
} else {
  bootPreline();
}

// Apos navegar (wire:navigate): a pagina e re-renderizada com novos data-hs-*.
document.addEventListener('livewire:navigated', bootPreline);
