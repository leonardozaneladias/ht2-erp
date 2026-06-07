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
// overflow e de stacking contexts. Inclui navegacao por teclado (WAI-ARIA menu).

document.addEventListener('alpine:init', () => {
  window.Alpine.data('afRowActions', () => ({
    open: false,
    coords: { top: 0, left: 0 },

    toggle() {
      if (this.open) {
        this.fechar(true);
      } else {
        this.abrir();
      }
    },

    abrir() {
      this.open = true;
      // Posiciona e foca o primeiro item apos o menu existir no DOM.
      this.$nextTick(() => {
        this.posicionar();
        this.focarPrimeiro();
      });
    },

    fechar(retornarFoco = false) {
      if (!this.open) {
        return;
      }

      this.open = false;

      // Devolve o foco ao gatilho (ex.: fechou via ESC) para nao "perder" o
      // ponto de navegacao por teclado.
      if (retornarFoco) {
        this.$refs.trigger?.focus();
      }
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

    // ---- Navegacao por teclado (WAI-ARIA menu) -----------------------------

    itensMenu() {
      if (!this.$refs.menu) {
        return [];
      }

      return Array.from(this.$refs.menu.querySelectorAll('[role="menuitem"]')).filter(
        (el) => !el.hasAttribute('disabled') && el.getAttribute('aria-disabled') !== 'true',
      );
    },

    focarPrimeiro() {
      this.itensMenu()[0]?.focus();
    },

    mover(delta) {
      const itens = this.itensMenu();

      if (itens.length === 0) {
        return;
      }

      const atual = itens.indexOf(document.activeElement);
      const proximo = (atual + delta + itens.length) % itens.length;
      itens[proximo].focus();
    },

    extremo(qual) {
      const itens = this.itensMenu();

      if (itens.length === 0) {
        return;
      }

      (qual === 'fim' ? itens[itens.length - 1] : itens[0]).focus();
    },

    aoTeclar(evento) {
      switch (evento.key) {
        case 'ArrowDown':
          evento.preventDefault();
          this.mover(1);
          break;
        case 'ArrowUp':
          evento.preventDefault();
          this.mover(-1);
          break;
        case 'Home':
          evento.preventDefault();
          this.extremo('inicio');
          break;
        case 'End':
          evento.preventDefault();
          this.extremo('fim');
          break;
        case 'Escape':
          evento.preventDefault();
          this.fechar(true); // fecha e devolve o foco ao gatilho
          break;
        case 'Tab':
          // Deixa o Tab seguir naturalmente, apenas fechando o menu.
          this.fechar();
          break;
        default:
          break;
      }
    },
  }));
});
