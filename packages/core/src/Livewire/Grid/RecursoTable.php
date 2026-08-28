<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

use BackedEnum;
use HT2ML\Core\DTOs\Admin\Export\ExportavelDTO;
use HT2ML\Core\Livewire\Concerns\ComAcoesCrud;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Livewire\Concerns\ExportaPdf;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

/**
 * Base declarativa de uma tabela de recurso: quatro métodos, e o resto sai daí.
 *
 * As dezesseis tabelas do repositório somavam 2.472 linhas, 47% delas repetidas
 * literalmente entre arquivos — e as diferenças reais eram o model, o prefixo de
 * permissão, a rota e a lista de campos. Tudo o mais era o mesmo `setUp()`, o
 * mesmo `datasource()`, os mesmos `eventoVer()`/`rotaEditar()`/
 * `modelClassLixeira()`, e quatro listas paralelas sobre os mesmos campos.
 *
 * Aqui isso vira:
 *
 *   protected function model(): string    { return Cargo::class; }
 *   protected function recurso(): string  { return 'cargos'; }
 *   protected function rotaBase(): string { return 'admin.referencia.cargos'; }
 *   protected function campos(): array    { return [Campo::texto(...), ...]; }
 *
 * E a tela entrega busca global, filtro com o widget certo por tipo, ordenação,
 * seletor de colunas, lixeira com Policy, kebab Ver/Editar, estado vazio com
 * CTA, exportação respeitando os filtros e eager load automático.
 *
 * A base é OPT-IN, nunca obrigatória. Quando o desenho não cabe, há três fugas
 * graduadas — por campo (`->comColuna()`, `->comFiltro()`, `Campo::personalizado()`),
 * por método (toda derivação chama `parent::`), e não estender. `AuditoriaTable`,
 * que é log somente-leitura sem model CRUD, sem lixeira e sem Policy, fica de
 * fora para sempre e é a prova viva de que a terceira existe.
 *
 * O erro a não repetir é o do `ComAcoesCrud`: a saída era binária — usar o trait
 * ou escrever a view inteira — e o resultado foram seis `_acoes.blade.php` que
 * sobreviveram, cada um agora com manutenção própria. Uma fuga sem degraus vira
 * fuga em massa.
 */
abstract class RecursoTable extends PowerGridComponent
{
    use ComAcoesCrud;
    use ComLixeira;
    use ExportaPdf;
    use WithExport;

    /** @var list<Campo>|null memo por request: campos() é chamado por cinco derivações */
    private ?array $camposResolvidos = null;

    /**
     * `boot()` roda em toda requisição do Livewire, antes de mount/hydrate — é
     * onde o nome da tabela pode ser derivado sem obrigar cada subclasse a
     * repetir uma propriedade que já está em `recurso()`.
     */
    public function boot(): void
    {
        if ($this->tableName === '' || $this->tableName === 'default') {
            $this->tableName = $this->recurso() . '-table';
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        $setUp = [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable($this->recurso())
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];

        if ($this->temLixeira()) {
            $setUp[0]->includeViewOnTop('livewire.admin.partials.lixeira-toolbar');
        }

        return $setUp;
    }

    /**
     * @return Builder<Model>
     */
    public function datasource(): Builder
    {
        /** @var Builder<Model> $query */
        $query = $this->model()::query();

        $relacoes = array_values(array_filter(array_map(
            static fn (Campo $campo): ?string => $campo->eagerLoad(),
            $this->camposDoRecurso(),
        )));

        if ($relacoes !== []) {
            // O N+1 some por construção. Antes, docs/criar-recurso.md pedia o
            // ->with() à mão — e passo manual documentado é fonte de bug
            // documentada.
            $query->with(array_unique($relacoes));
        }

        // Escopo da tela por FORA da lixeira, como manda o docblock de
        // ComLixeira: os global scopes `empresa` e SoftDeletingScope são
        // independentes, então onlyTrashed continua respeitando o tenant.
        $query = $this->escopoDaTela($query);

        /** @var Builder<Model> $query */
        $query = $this->temLixeira() ? $this->aplicarLixeira($query) : $query;

        return $this->aplicarEscopos($query);
    }

    public function fields(): PowerGridFields
    {
        $fields = PowerGrid::fields()->add('id');

        foreach ($this->camposDoRecurso() as $campo) {
            $formatador = $campo->formatador();

            if ($formatador === null) {
                $fields->add($campo->nome);

                continue;
            }

            $fields->add($campo->campoDeExibicao(), static fn (Model $linha): string => $formatador($linha));
        }

        return $this->camposDaTela($fields);
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        $colunas = array_map(
            static fn (Campo $campo): Column => $campo->coluna(),
            $this->camposDoRecurso(),
        );

        $colunas = [...$this->colunasIniciais(), ...$colunas];
        $colunas[] = Column::action('Ações');

        return $colunas;
    }

    /**
     * @return array<int, FilterBase>
     */
    public function filters(): array
    {
        $filtros = $this->filtrosIniciais();

        foreach ($this->camposDoRecurso() as $campo) {
            $filtro = $campo->filtro($this->opcoesDeFiltro($campo));

            if ($filtro !== null) {
                $filtros[] = $filtro;
            }
        }

        return $filtros;
    }

    public function noDataLabel(): View
    {
        $podeCriar = auth('admin')->user()?->can($this->permissaoBase() . '.criar') ?? false;

        return view('core::admin.partials.powergrid-empty', [
            'titulo' => $this->verLixeira
                ? 'A lixeira está vazia'
                : 'Nenhum registro encontrado',
            'descricao' => $this->verLixeira
                ? 'Nada foi excluído por aqui ainda.'
                : 'Ajuste a busca e os filtros, ou cadastre o primeiro registro.',
            'ctaUrl' => $podeCriar && ! $this->verLixeira ? $this->rotaCriar() : null,
            'ctaLabel' => $podeCriar && ! $this->verLixeira ? 'Novo registro' : null,
        ]);
    }

    // ------------------------------------------------------------- contrato

    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Chave plural do recurso: 'cargos', 'alunos'. */
    abstract protected function recurso(): string;

    /** Prefixo das rotas nomeadas: 'admin.referencia.cargos'. */
    abstract protected function rotaBase(): string;

    /** @return list<Campo> */
    abstract protected function campos(): array;

    // ----------------------------------------------------------- derivações

    /**
     * Chave do módulo, quando as permissões são prefixadas por ele
     * (`rh.departamentos.listar`). Null para os recursos do core, cujas
     * permissões são só `cargos.listar`.
     */
    protected function modulo(): ?string
    {
        return null;
    }

    protected function permissaoBase(): string
    {
        $modulo = $this->modulo();

        return $modulo === null ? $this->recurso() : "{$modulo}.{$this->recurso()}";
    }

    protected function eventoVer(): string
    {
        return $this->recurso() . '::ver';
    }

    protected function rotaEditar(Model $row): string
    {
        // Parâmetro posicional: o nome do binding varia com a inflexão do
        // model ('cargo', 'pais', 'ncm') e derivá-lo de 'cargos' com o
        // inflector inglês do Laravel erra em português.
        return route($this->rotaBase() . '.edit', [$row->getKey()]);
    }

    protected function rotaCriar(): string
    {
        return route($this->rotaBase() . '.create');
    }

    /** @return class-string<Model&UsaSoftDeletes> */
    protected function modelClassLixeira(): string
    {
        $model = $this->model();

        if (! $this->temLixeira()) {
            // Chegar aqui significa que alguém disparou uma ação de lixeira num
            // recurso que não tem lixeira — evento forjado, ou uma tela cuja
            // toolbar ficou visível por engano. Erro claro em vez de
            // "Call to undefined method onlyTrashed()".
            throw new LogicException(sprintf(
                '%s acionou a lixeira, mas %s não usa SoftDeletes.',
                static::class,
                $model,
            ));
        }

        /** @var class-string<Model&UsaSoftDeletes> */
        return $model;
    }

    /**
     * Lixeira é fato do MODEL, não opção da tabela.
     *
     * Antes era uma flag do gerador (`--sem-soft-delete`), então uma tabela
     * podia dizer que tinha lixeira sobre um model sem SoftDeletes — e a tela
     * quebrava só quando alguém clicasse em "Ver lixeira".
     *
     * Exige as DUAS coisas: o trait, que traz `onlyTrashed()`, e a interface,
     * que é o que o contrato de ComLixeira declara. Um model com o trait e sem
     * a interface passaria no teste e quebraria o tipo.
     */
    protected function temLixeira(): bool
    {
        $model = $this->model();

        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && is_a($model, UsaSoftDeletes::class, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Ganchos de composição
    |--------------------------------------------------------------------------
    |
    | Seis pontos onde uma dimensão transversal — hoje só a multiempresa — entra
    | em TODAS as derivações de uma vez. Vazios aqui; quem os implementa é o
    | trait RecursoMultiEmpresa.
    |
    | Existem porque a alternativa era a base chamar métodos de um trait que ela
    | não usa, protegida por um `if` em runtime. Isso funciona e é indefensável:
    | a análise estática não vê, e a próxima dimensão transversal (filial de
    | terceiro nível, arquivamento) copiaria o mesmo `if` em seis lugares.
    |
    | E resolve, por construção, o defeito que o gerador produzia: uma tabela com
    | `use FiltraPorMultiEmpresa` e SEM o `aplicarEscopoMultiEmpresa()` no
    | datasource passa em todos os testes de um tenant só, e vaza linhas de
    | outras empresas no dia em que a segunda for cadastrada. Aqui o trait traz
    | as seis composições juntas ou nenhuma.
    */

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function escopoDaTela(Builder $query): Builder
    {
        return $query;
    }

    protected function camposDaTela(PowerGridFields $fields): PowerGridFields
    {
        return $fields;
    }

    /**
     * @return list<Column>
     */
    protected function colunasIniciais(): array
    {
        return [];
    }

    /**
     * @return list<FilterBase>
     */
    protected function filtrosIniciais(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function cabecalhosIniciais(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function celulasIniciais(Model $linha): array
    {
        return [];
    }

    /**
     * Permissão que o seletor de empresas consulta (FiltraPorMultiEmpresa).
     *
     * Derivada, e não redigitada: era exatamente aqui que o gerador emitia uma
     * segunda fórmula discordante da primeira — `departamentos.listar` em vez
     * de `rh.departamentos.listar` —, e o efeito era o seletor de empresas
     * nascer vazio para todo mundo que não fosse super-admin.
     */
    protected function permissaoListagem(): string
    {
        return $this->permissaoBase() . '.listar';
    }

    /**
     * Escopos extras da tela (fuga de nível 2). Toda derivação chama parent::.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function aplicarEscopos(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Opções de um filtro de múltipla seleção (enum ou relação).
     *
     * O padrão cobre enum; relação precisa de consulta, e quem sabe qual é a
     * tela — sobrescrever aqui é a fuga.
     *
     * @return array<int|string, string>
     */
    protected function opcoesDeFiltro(Campo $campo): array
    {
        if ($campo->tipo !== TipoCampo::Enum || ! is_string($campo->referencia)) {
            return [];
        }

        $enum = $campo->referencia;

        // is_a, não enum_exists: um enum puro não tem ->value, e o filtro de
        // múltipla seleção precisa de um valor para mandar ao banco.
        if (! is_a($enum, BackedEnum::class, true)) {
            return [];
        }

        $opcoes = [];

        foreach ($enum::cases() as $caso) {
            $opcoes[$caso->value] = method_exists($caso, 'label')
                ? (string) $caso->label()
                : Str::headline((string) $caso->value);
        }

        return $opcoes;
    }

    protected function tituloDaExportacao(): string
    {
        return Str::headline($this->recurso());
    }

    protected function dadosParaExportacao(): ExportavelDTO
    {
        $campos = array_values(array_filter(
            $this->camposDoRecurso(),
            static fn (Campo $campo): bool => $campo->exportavel(),
        ));

        $linhas = $this->linhasParaExportacao()
            ->map(fn (Model $linha): array => [
                ...$this->celulasIniciais($linha),
                ...array_map(
                    static fn (Campo $campo): string => $campo->textoDeExportacao($linha),
                    $campos,
                ),
            ])
            ->values()
            ->all();

        return new ExportavelDTO(
            titulo: $this->tituloDaExportacao(),
            colunas: [
                ...$this->cabecalhosIniciais(),
                ...array_map(static fn (Campo $campo): string => $campo->rotulo, $campos),
            ],
            linhas: $linhas,
        );
    }

    /**
     * @return list<Campo>
     */
    private function camposDoRecurso(): array
    {
        return $this->camposResolvidos ??= $this->campos();
    }
}
