// Preline (componentes data-hs-*): abas, dropdowns, accordion, tooltips, overlays.
//
// O Preline só executa autoInit no evento `load` nativo do navegador. Em navegação SPA
// do Livewire (wire:navigate) o `load` não dispara, e os novos data-hs-* ficam sem
// inicialização — por isso as abas e dropdowns só voltavam a funcionar após um F5.
//
// O MORPH tem o mesmo problema, e é o caso mais comum: um componente Livewire que
// re-renderiza e traz um data-hs-* novo (um modal, um dropdown numa linha de tabela)
// não recebia autoInit. O sintoma são as chamadas manuais de
// `window.HSStaticMethods?.autoInit?.()` espalhadas por 8 views do RH, cada uma
// corrigindo localmente o mesmo furo. O hook `morph.added` fecha o furo na origem,
// inclusive para os casos que ninguém corrigiu à mão.
//
// As chamadas manuais seguem no lugar de propósito: elas rodam num `$nextTick` do
// Alpine, imediatamente antes de `HSOverlay.open()`, enquanto este hook agrupa num
// requestAnimationFrame — que pode cair DEPOIS daquele open. Removê-las exige um teste
// por modal; como autoInit() é idempotente, mantê-las não custa nada.
//
// autoInit() é idempotente: descarta da coleção os elementos que saíram do DOM e só
// instancia os novos — pode ser chamado a cada morph sem duplicar listeners nem perder
// estado. Ainda assim agrupamos as chamadas num rAF, porque um único morph pode emitir
// `morph.added` dezenas de vezes (uma por nó adicionado).
let agendado = false;

function bootPreline() {
  window.HSStaticMethods?.autoInit();
}

function agendarBootPreline() {
  if (agendado) {
    return;
  }

  agendado = true;

  requestAnimationFrame(() => {
    agendado = false;
    bootPreline();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootPreline);
} else {
  bootPreline();
}

// Após navegar (wire:navigate): a página é re-renderizada com novos data-hs-*.
document.addEventListener('livewire:navigated', bootPreline);

// Após um morph: nós novos (modais, dropdowns, abas) precisam do mesmo tratamento.
document.addEventListener('livewire:init', () => {
  window.Livewire?.hook('morph.added', agendarBootPreline);
});
