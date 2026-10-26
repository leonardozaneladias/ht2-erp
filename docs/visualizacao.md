# Visualização ("Ver") — ficha read-only dos cadastros

Todo CRUD pode ganhar a opção **Ver**: uma ficha de consulta em drawer largo (quase a
tela toda, com blur no fundo) aberta direto da listagem, sem rota nova. É o
caminho do **ACL de consulta**: um perfil com apenas a permissão `.listar` do recurso
enxerga a listagem, abre a ficha e não vê nenhum botão de edição/exclusão.

## Permissão — nenhuma nova

A ability `view` das Policies já mapeia para a permissão de listagem do recurso
(quem pode ver a listagem já vê os dados dela): `{modulo}.{recurso}.listar` num
recurso de módulo (ex.: `rh.departamentos.listar`), ou `{recurso}.listar` nos
recursos do core e nos legados (ex.: `empresas.listar`, `exemplos.listar`). O item
"Ver" do kebab é gated por `can('view', $row)`; perfis "só consulta" = conceder apenas
`.listar` na tela `/admin/acesso`.

## Arquitetura

- **Trait `HT2ML\Core\Livewire\Concerns\ComFicha`** (no **Index**, não na Table): estado
  `#[Locked] $fichaId`, `abrirFicha()` (localiza → toast se sumiu → `authorize('view')`
  → dispara o evento browser `ficha-abrir`), computeds `ficha`/`fichaTitulo`/`fichaUrlEditar`
  (re-autorizam a cada render). Com `SoftDeletes`, a ficha abre também para
  registros na lixeira (ajuda a decidir uma restauração) com badge "Na lixeira".
  A base declarativa `HT2ML\Core\Livewire\Grid\RecursoIndex` já usa o trait.
- **`x-admin.ficha-drawer`**: composição sobre `x-admin.drawer` `wide`+`blur` —
  título, corpo (slot), rodapé com meta + Fechar + Editar (gated `can('update')`).
- **`x-shared.field-display`**: rótulo + valor read-only, o tijolo do corpo.
- **`_ficha.blade.php`** por recurso: seções `<section>` com grid de field-display.
  Formatação por tipo: dinheiro `Money::fromCentavos(...)->formatado()`, enum via badge,
  boolean Sim/Não, datas `d/m/Y`, vazio `—`.
- **Item "Ver" do kebab:** numa tabela que estende `HT2ML\Core\Livewire\Grid\RecursoTable`,
  ele vem da base — o kebab Ver/Editar/lixeira é derivado, e o evento é
  `eventoVer()` = `{recurso}::ver`. Só as telas antigas, que não estendem a base, ainda
  têm um `_acoes.blade.php` com o item escrito à mão.

O drawer vive no **Index** (fora do morph do PowerGrid — filtros/paginação não
o tocam) e a Table não muda: o kebab só dispara um evento global namespaced.

## Em recurso novo: já vem pronto

O `make:recurso --modulo=…` gera tudo: a Table estende `RecursoTable` (kebab com Ver),
o Index estende `RecursoIndex` (trait `ComFicha`), a view do Index inclui o drawer e o
`_ficha.blade.php` sai com os campos formatados por tipo. Nada a fazer. Ver
[`criar-recurso.md`](criar-recurso.md).

## Adotar numa tela antiga (custo unitário)

Para uma tela que **não** estende `RecursoTable`/`RecursoIndex` — o caso do RH e do
Exemplo hoje:

1. **`_acoes.blade.php`** — item "Ver" como primeiro do kebab, nos DOIS ramos
   (ativos e lixeira):

    ```blade
    @if ($ator?->can('view', $row))
        <x-shared.dropdown-item icon="tabler--eye" wire:click="$dispatch('exemplos::ver', { id: {{ $row->id }} })">
            Ver
        </x-shared.dropdown-item>
    @endif
    ```

2. **Index** — trait + bridge concreto (o `#[On]` não interpola hooks) + hooks:

    ```php
    use ComFicha;

    #[On('exemplos::ver')]
    public function verRegistro(int $id): void { $this->abrirFicha($id); }

    protected function modelClassFicha(): string { return Exemplo::class; }
    protected function relacoesFicha(): array { return ['filial']; }
    protected function urlEditarFicha(Model $registro): ?string
    { return route('admin.exemplos.edit', ['exemplo' => $registro->getKey()]); }
    ```

    Convenção do evento: `{snake_plural}::ver` (`exemplos::ver`,
    `departamentos::ver`).

3. **View do Index** — depois da `<livewire:...-table />`, incluindo a ficha pelo
   namespace de view do pacote:

    ```blade
    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('exemplo-demo::exemplos._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
    ```

4. **`_ficha.blade.php`** — o conteúdo. Gabarito:
   `packages/extensao-exemplo-demo/resources/views/exemplos/_ficha.blade.php`
   (view `exemplo-demo::exemplos._ficha`).

Migrar a tela para `RecursoTable`/`RecursoIndex` costuma sair mais barato que os quatro
passos, e elimina o `_acoes.blade.php`.

## Referência viva e cobertura

Medido em 2026-10-25 (Index com `ComFicha`, direto ou via `RecursoIndex`, e um
`_ficha.blade.php` correspondente):

- **Exemplo** (`ht2ml/extensao-exemplo-demo`): `/admin/exemplos` → kebab → Ver. Infra
  testada em `tests/Feature/Admin/Exemplos/ExemploFichaTest.php`. Não há teste de
  browser específico da ficha.
- **Core:** Empresas, Usuários e os 7 catálogos de referência (Banco, Cargo, Estado,
  Moeda, Município, País, Tipo de logradouro).
- **Fiscal** (`ht2ml/extensao-fiscal-br`): CNAE, CFOP e NCM.
- **Fora:** o RH (`ht2ml/extensao-rh`) não tem ficha — Departamentos e Funcionários só
  têm listagem, criação e edição, sem `ComFicha` nem `_ficha.blade.php`. Também ficam
  fora as telas com padrão próprio de listagem/detalhe: Acesso/Perfis e Auditoria
  (drawer próprio).
