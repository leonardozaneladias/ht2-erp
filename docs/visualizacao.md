# Visualização ("Ver") — ficha read-only dos cadastros

Todo CRUD ganha a opção **Ver**: uma ficha de consulta em drawer largo (quase a
tela toda, com blur no fundo) aberta direto da listagem, sem rota nova. É o
caminho do **ACL de consulta**: um perfil com apenas `{modulo}.listar` enxerga a
listagem, abre a ficha e não vê nenhum botão de edição/exclusão.

> Exceção: **Funcionários** tem uma ficha em página própria
> (`/admin/rh/funcionarios/{id}`, formato prontuário com seções e KPIs) — o
> padrão deste doc cobre os demais cadastros.

## Permissão — nenhuma nova

A ability `view` das Policies já mapeia para `{modulo}.listar` (quem pode ver a
listagem já vê os dados dela). O item "Ver" do kebab é gated por
`can('view', $row)`; perfis "só consulta" = conceder apenas `.listar` na tela
`/admin/acesso`.

## Arquitetura

- **Trait `HT2ML\Core\Livewire\Concerns\ComFicha`** (no **Index**, não na Table): estado
  `#[Locked] $fichaId`, `abrirFicha()` (localiza → toast se sumiu → `authorize('view')`
  → dispara o evento browser `ficha-abrir`), computeds `ficha`/`fichaTitulo`/`fichaUrlEditar`
  (re-autorizam a cada render). Com `SoftDeletes`, a ficha abre também para
  registros na lixeira (ajuda a decidir uma restauração) com badge "Na lixeira".
- **`x-admin.ficha-drawer`**: composição sobre `x-admin.drawer` `wide`+`blur` —
  título, corpo (slot), rodapé com meta + Fechar + Editar (gated `can('update')`).
- **`x-shared.field-display`**: rótulo + valor read-only, o tijolo do corpo.
- **`_ficha.blade.php`** por módulo (ao lado do `_acoes.blade.php`): seções
  `<section>` com grid de field-display. Formatação por tipo: dinheiro
  `Money::fromCentavos(...)->formatado()`, enum via badge, boolean Sim/Não,
  datas `d/m/Y`, vazio `—`.

O drawer vive no **Index** (fora do morph do PowerGrid — filtros/paginação não
o tocam) e a Table não muda: o kebab só dispara um evento global namespaced.

## Adotar em um módulo (custo unitário)

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
    `departamentos::ver`, `registros_ponto::ver`).

3. **View do Index** — depois da `<livewire:...-table />`:

    ```blade
    <x-admin.ficha-drawer :registro="$this->ficha" :titulo="$this->fichaTitulo" :editar-url="$this->fichaUrlEditar">
        @if ($this->ficha)
            @include ('livewire.admin.exemplos._ficha', ['registro' => $this->ficha])
        @endif
    </x-admin.ficha-drawer>
    ```

4. **`_ficha.blade.php`** — o conteúdo (ver o gabarito do Exemplo em
   `resources/views/livewire/admin/exemplos/_ficha.blade.php`).

O `make:modulo` gera tudo isso automaticamente para módulos novos.

## Referência viva e cobertura

- Módulo **Exemplo**: `/admin/exemplos` → kebab → Ver (infra testada em
  `tests/Feature/Admin/Exemplos/ExemploFichaTest.php` + E2E
  `tests/Browser/Admin/ExemploFichaSmokeTest.php`).
- Cobertos: cadastros do RH (departamentos, centros de custo, funções, níveis de
  cargo, convenções, tipos de documento/afastamento, escalas, rubricas, fatores
  de HE, feriados, campos personalizados), movimentos (ocorrências, ponto),
  Empresas, Usuários e as 10 tabelas de referência.
- Fora (padrão próprio de listagem/detalhe): Atestados, Horas extras, Férias,
  Estabelecimentos eSocial, Acesso/Perfis, Auditoria (drawer próprio) — adotar
  quando/se as telas migrarem para o CRUD padrão.
