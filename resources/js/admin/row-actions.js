// Menu de acoes de linha (kebab "⋮") para tabelas — PowerGrid e nativas.
//
// Por que Alpine (e nao Preline):
//  1. O container da tabela usa `overflow-x-auto`; pela spec do CSS isso forca
//     `overflow-y:auto`, entao um dropdown comum seria CORTADO verticalmente.
//  2. O PowerGrid re-monta as linhas via morph do Livewire (busca/ordenacao/
//     paginacao) e o autoInit do Preline so roda em `livewire:navigated` — logo
//     dropdowns Preline quebrariam apos cada morph.
//
// Solucao: Alpine (reinicializa sozinho apos morph, como o sino de notificacoes)
// + posicao `fixed` calculada a partir do botao, escapando do clipping do
// overflow e de stacking contexts.

document.addEventListener('alpine:init', () => {
  window.Alpine.data('afRowActions', () => ({
    open: false,
    coords: { top: 0, left: 0 },

    toggle() {
      if (this.open) {
        this.fechar();
      } else {
        this.abrir();
      }
    },

    abrir() {
      this.open = true;
      // Posiciona apos o menu existir no DOM (para medir altura/largura reais).
      this.$nextTick(() => this.posicionar());
    },

    fechar() {
      this.open = false;
    },

    posicionar() {
      const menu = this.$refs.menu;

      if (!menu) {
        return;
      }

      const gatilho = this.$refs.trigger.getBoundingClientRect();
      const m = menu.getBoundingClientRect();
      const margem = 8;

      // Alinha a borda direita do menu com a do botao (estilo bottom-end).
      let left = gatilho.right - m.width;
      let top = gatilho.bottom + 4;

      // Mantem dentro da viewport no eixo horizontal.
      left = Math.min(Math.max(left, margem), window.innerWidth - m.width - margem);

      // Sem espaco abaixo? Abre para cima.
      if (top + m.height > window.innerHeight - margem && gatilho.top - m.height - 4 > margem) {
        top = gatilho.top - m.height - 4;
      }

      this.coords = { top, left };
    },

    get menuStyle() {
      return `top:${this.coords.top}px;left:${this.coords.left}px`;
    },
  }));
});
