// Combobox custom (busca + checkbox) — substitui o TomSelect nos filtros do
// PowerGrid e e a base dos selects de formulario com busca.
//
// Um unico factory Alpine com dois "drivers" de saida, escolhidos por `mode`:
//   - 'form'      -> controla um <select> nativo oculto (POST tradicional +
//                    wire:model do Livewire), disparando input/change.
//   - 'pg-filter' -> despacha `pg:multiSelect-{tableName}` (contrato oficial do
//                    PowerGrid) e ouve os eventos de limpeza do pacote.
//
// Posicionamento: o painel usa `position:fixed` calculado a partir do gatilho
// (mesmo padrao do menu de acoes de linha em row-actions.js), escapando do
// `overflow-x-auto` do wrapper da tabela sem depender de teleport. Como e
// registrado via Alpine.data no `alpine:init`, reinicializa sozinho apos cada
// morph do Livewire (busca/ordenacao/paginacao) e em `livewire:navigated`.

// Normaliza para busca acento- e caixa-insensivel ("São" casa com "sao").
const normalizar = (texto) =>
  String(texto ?? '')
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .toLowerCase();

document.addEventListener('alpine:init', () => {
  window.Alpine.data('comboBox', (config = {}) => ({
    // ---- configuracao (vinda do Blade) ------------------------------------
    mode: config.mode ?? 'form',
    multiple: config.multiple ?? false,
    searchable: config.searchable ?? true,
    placeholder: config.placeholder ?? 'Selecione...',
    // exclusivo do modo pg-filter (dispatch para o PowerGrid)
    field: config.field ?? null,
    tableName: config.tableName ?? null,
    label: config.label ?? null,
    // modo form: valor sentinela que representa "sem selecao" (ex.: 'all'/'' nos
    // filtros boolean/select do PowerGrid). clearable controla o botao limpar.
    emptyValue: String(config.emptyValue ?? ''),
    clearable: config.clearable ?? true,

    // ---- estado -----------------------------------------------------------
    open: false,
    search: '',
    selected: [], // sempre array de strings (single = no maximo 1)
    options: [], // { value, label, disabled, group }
    activeIndex: -1, // indice destacado em opcoesFiltradas (navegacao por teclado)
    coords: { top: 0, left: 0, width: 0 },

    init() {
      this.carregarOpcoes();
      this.carregarSelecionados();

      // wire:model hidrata o <select> nativo DEPOIS deste init (o resumo ficava no
      // placeholder mesmo com valor salvo — laudo 18, F9-02): re-sincroniza num tick
      // pós-boot e sempre que o valor nativo mudar programaticamente.
      const select = this.selectNativo();
      if (select) {
        select.addEventListener('change', () => this.sincronizarDoNativo());
        window.requestAnimationFrame(() => this.sincronizarDoNativo());
      }

      if (this.mode === 'pg-filter') {
        this.ouvirLimpezaPowerGrid();
      }

      // Painel fixo precisa acompanhar scroll (capture pega qualquer ancestral)
      // e resize enquanto aberto.
      this._reposicionar = () => {
        if (this.open) {
          this.posicionar();
        }
      };
      window.addEventListener('resize', this._reposicionar);
      window.addEventListener('scroll', this._reposicionar, true);

      // Cada digitacao na busca zera o destaque do teclado.
      this.$watch('search', () => {
        this.activeIndex = -1;
      });
    },

    destroy() {
      window.removeEventListener('resize', this._reposicionar);
      window.removeEventListener('scroll', this._reposicionar, true);
    },

    // ---- fontes de dados --------------------------------------------------

    selectNativo() {
      return this.$refs.native ?? null;
    },

    // Modo pg-filter recebe as opcoes prontas via JSON; modo form le do <select>.
    carregarOpcoes() {
      const brutas = Array.isArray(config.options) ? config.options : null;

      if (brutas) {
        this.options = brutas
          .map((o) => ({
            value: String(o.value ?? ''),
            label: String(o.label ?? o.value ?? ''),
            disabled: Boolean(o.disabled),
            group: o.group ?? null,
          }))
          .filter((o) => o.value !== '');
        return;
      }

      const select = this.selectNativo();
      if (!select) {
        return;
      }

      this.options = Array.from(select.options)
        .filter((o) => o.value !== this.emptyValue)
        .map((o) => ({
          value: String(o.value),
          label: o.textContent.trim(),
          disabled: o.disabled,
          group: o.parentElement?.tagName === 'OPTGROUP' ? o.parentElement.label : null,
        }));
    },

    carregarSelecionados() {
      if (Array.isArray(config.initial) && config.initial.length) {
        this.selected = config.initial.map(String);
        return;
      }

      this.sincronizarDoNativo();
    },

    // Fonte de verdade do modo form é o <select> nativo: espelha o estado atual dele
    // no resumo (usado no boot e quando o valor muda por fora — Livewire, outros JS).
    sincronizarDoNativo() {
      const select = this.selectNativo();
      if (select) {
        this.selected = Array.from(select.selectedOptions)
          .map((o) => String(o.value))
          .filter((v) => v !== this.emptyValue);
      }
    },

    // ---- getters ----------------------------------------------------------

    get temGrupos() {
      return this.options.some((o) => o.group);
    },

    get opcoesFiltradas() {
      const termo = normalizar(this.search);
      if (!termo) {
        return this.options;
      }
      return this.options.filter((o) => normalizar(o.label).includes(termo));
    },

    get resumo() {
      const n = this.selected.length;

      if (n === 0) {
        return this.mode === 'pg-filter' ? 'Todos' : this.placeholder;
      }

      if (n === 1) {
        const op = this.options.find((o) => o.value === this.selected[0]);
        return op ? op.label : '1 selecionado';
      }

      return `${n} selecionados`;
    },

    get vazio() {
      return this.selected.length === 0;
    },

    estaSelecionado(value) {
      return this.selected.includes(String(value));
    },

    // Cabecalho de grupo aparece na primeira opcao de cada grupo (lista flat).
    primeiroDoGrupo(indice) {
      const atual = this.opcoesFiltradas[indice];
      if (!atual?.group) {
        return false;
      }
      const anterior = this.opcoesFiltradas[indice - 1];
      return !anterior || anterior.group !== atual.group;
    },

    // ---- abertura / posicionamento ----------------------------------------

    alternar() {
      if (this.open) {
        this.fechar();
      } else {
        this.abrir();
      }
    },

    abrir() {
      if (this.open) {
        return;
      }
      this.open = true;
      this.search = '';
      this.$nextTick(() => {
        this.posicionar();
        if (this.searchable) {
          this.$refs.busca?.focus();
        }
      });
    },

    fechar(retornarFoco = false) {
      if (!this.open) {
        return;
      }
      this.open = false;
      this.activeIndex = -1;
      if (retornarFoco) {
        this.$refs.gatilho?.focus();
      }
    },

    posicionar() {
      const painel = this.$refs.painel;
      const gatilho = this.$refs.gatilho;
      if (!painel || !gatilho) {
        return;
      }

      const g = gatilho.getBoundingClientRect();
      const margem = 8;
      const altura = painel.offsetHeight;
      let top = g.bottom + 4;

      // Sem espaco abaixo? Abre para cima.
      if (top + altura > window.innerHeight - margem && g.top - altura - 4 > margem) {
        top = g.top - altura - 4;
      }

      this.coords = { top, left: g.left, width: g.width };
    },

    get estiloPainel() {
      return `top:${this.coords.top}px;left:${this.coords.left}px;min-width:${this.coords.width}px`;
    },

    // ---- selecao ----------------------------------------------------------

    selecionar(value) {
      const v = String(value);
      const op = this.options.find((o) => o.value === v);
      if (op?.disabled) {
        return;
      }

      if (this.multiple) {
        if (this.selected.includes(v)) {
          this.selected = this.selected.filter((x) => x !== v);
        } else {
          this.selected = [...this.selected, v];
        }
        this.gravar();
      } else {
        this.selected = [v];
        this.gravar();
        this.fechar(true);
      }
    },

    limpar() {
      if (this.selected.length === 0) {
        return;
      }
      this.selected = [];
      this.gravar();
    },

    // ---- drivers de saida -------------------------------------------------

    gravar() {
      if (this.mode === 'pg-filter') {
        this.gravarPowerGrid();
      } else {
        this.gravarSelectNativo();
      }
    },

    // Form: espelha a selecao no <select> oculto e avisa Livewire/submit.
    // Sem selecao, marca a opcao sentinela (emptyValue) — ex.: 'all'/'' dos
    // filtros do PowerGrid voltam a "Todos".
    gravarSelectNativo() {
      const select = this.selectNativo();
      if (!select) {
        return;
      }

      const semSelecao = this.selected.length === 0;

      Array.from(select.options).forEach((o) => {
        o.selected = semSelecao ? String(o.value) === this.emptyValue : this.selected.includes(String(o.value));
      });

      select.dispatchEvent(new Event('input', { bubbles: true }));
      select.dispatchEvent(new Event('change', { bubbles: true }));
    },

    // Filtro: mesmo contrato do TomSelect (storeMultiSelect do PowerGrid).
    gravarPowerGrid() {
      window.Livewire?.dispatch(`pg:multiSelect-${this.tableName}`, {
        label: this.label,
        field: this.field,
        values: this.selected,
      });
    },

    ouvirLimpezaPowerGrid() {
      const limpar = () => {
        this.selected = [];
      };
      window.addEventListener(`pg:clear_multi_select::${this.tableName}:${this.field}`, limpar);
      window.addEventListener(`pg:clear_all_multi_select::${this.tableName}`, limpar);
    },

    // ---- navegacao por teclado --------------------------------------------

    mover(delta) {
      const ops = this.opcoesFiltradas;
      if (!ops.length) {
        return;
      }

      let i = this.activeIndex;
      for (let passo = 0; passo < ops.length; passo++) {
        i = (i + delta + ops.length) % ops.length;
        if (!ops[i].disabled) {
          this.activeIndex = i;
          return;
        }
      }
    },

    aoTeclar(evento) {
      switch (evento.key) {
        case 'ArrowDown':
          evento.preventDefault();
          if (!this.open) {
            this.abrir();
          } else {
            this.mover(1);
          }
          break;
        case 'ArrowUp':
          evento.preventDefault();
          this.mover(-1);
          break;
        case 'Enter': {
          evento.preventDefault();
          const alvo = this.opcoesFiltradas[this.activeIndex];
          if (alvo) {
            this.selecionar(alvo.value);
          }
          break;
        }
        case 'Home':
          evento.preventDefault();
          this.activeIndex = -1;
          this.mover(1);
          break;
        case 'End':
          evento.preventDefault();
          this.activeIndex = 0;
          this.mover(-1);
          break;
        case 'Escape':
          evento.preventDefault();
          this.fechar(true);
          break;
        case 'Tab':
          this.fechar();
          break;
        default:
          break;
      }
    },
  }));
});
