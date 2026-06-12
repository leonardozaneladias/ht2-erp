<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auditoria;

use App\Models\Activity;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Wireable;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class AuditoriaTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'auditoria-table';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function noDataLabel(): \Illuminate\Contracts\View\View
    {
        return view('admin.partials.powergrid-empty', [
            'icon' => 'tabler--history-off',
            'titulo' => 'Nenhum registro de auditoria',
            'descricao' => 'As ações relevantes do painel aparecerão aqui automaticamente.',
        ]);
    }

    /**
     * @return array<int, Wireable>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('auditoria')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Activity>
     */
    public function datasource(): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject', 'empresa'])
            ->visiveisPara($this->usuario());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_formatted', fn (Activity $a): string => $a->created_at instanceof Carbon ? $a->created_at->format('d/m/Y H:i:s') : '—')
            ->add('quem', fn (Activity $a): string => Blade::render('<span class="text-body-color inline-flex items-center gap-1.5"><i class="iconify tabler--user text-default-400 shrink-0 text-sm"></i>{{ $q }}</span>', ['q' => $this->quem($a)]))
            ->add('empresa', fn (Activity $a): string => $a->empresa?->getAttribute('nome') ?? '—')
            ->add('log_name', fn (Activity $a): string => Blade::render('<x-shared.badge variant="default" size="sm">{{ $l }}</x-shared.badge>', ['l' => $a->log_name ?? '—']))
            ->add('event', fn (Activity $a): string => $this->renderEvento($a))
            ->add('sujeito', fn (Activity $a): string => $this->sujeito($a))
            ->add('description', fn (Activity $a): string => e((string) $a->description))
            ->add('acoes', fn (Activity $a): string => Blade::render(
                '<x-shared.button type="button" variant="default" appearance="ghost" size="sm" icon="tabler--eye" icon-only aria-label="Ver detalhes" wire:click="$dispatch(\'auditoria::detalhar\', { id: ' . $a->id . ' })" />',
            ));
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Quando', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('Quem', 'quem'),

            Column::make('Empresa', 'empresa'),

            Column::make('Log', 'log_name')
                ->sortable(),

            Column::make('Evento', 'event')
                ->sortable(),

            Column::make('Sujeito', 'sujeito'),

            Column::make('Descrição', 'description')
                ->searchable(),

            Column::make('', 'acoes')
                ->bodyAttribute('text-end'),
        ];
    }

    /**
     * @return array<int, FilterBase>
     */
    public function filters(): array
    {
        $filtros = [
            Filter::select('log_name')
                ->dataSource($this->opcoes('log_name'))
                ->optionValue('valor')
                ->optionLabel('valor'),

            Filter::select('event')
                ->dataSource($this->opcoes('event'))
                ->optionValue('valor')
                ->optionLabel('valor'),

            Filter::datepicker('created_at_formatted', 'created_at'),
        ];

        if ($this->podeVerTodasEmpresas()) {
            $filtros[] = Filter::select('empresa', 'empresa_id')
                ->dataSource(Empresa::query()->orderBy('nome')->get(['id', 'nome'])->all())
                ->optionValue('id')
                ->optionLabel('nome');
        }

        return $filtros;
    }

    protected function quem(Activity $a): string
    {
        $causer = $a->causer;

        return $causer?->getAttribute('nome')
            ?? $causer?->getAttribute('email')
            ?? 'sistema';
    }

    protected function sujeito(Activity $a): string
    {
        if ($a->subject_type === null) {
            return '<span class="text-default-400">—</span>';
        }

        return Blade::render(
            '<span class="inline-flex items-center gap-1.5"><x-shared.badge variant="default" size="sm">{{ $tipo }}</x-shared.badge><span class="text-default-500 text-xs">#{{ $id }}</span></span>',
            ['tipo' => class_basename($a->subject_type), 'id' => (string) $a->subject_id],
        );
    }

    /**
     * Badge do evento com cor e icone semanticos (mantem o valor cru como rotulo
     * para casar com as opcoes do filtro).
     */
    protected function renderEvento(Activity $a): string
    {
        $evento = (string) ($a->event ?? '');

        $mapa = [
            'created' => ['success', 'tabler--circle-plus'],
            'updated' => ['warning', 'tabler--edit'],
            'deleted' => ['danger', 'tabler--trash'],
            'restored' => ['info', 'tabler--restore'],
        ];

        [$variant, $icon] = $mapa[$evento] ?? ['default', 'tabler--point'];

        return Blade::render(
            '<x-shared.badge :variant="$v" :icon="$i" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $variant, 'i' => $icon, 't' => $evento !== '' ? $evento : '—'],
        );
    }

    /**
     * Valores distintos de uma coluna para popular o filtro select.
     *
     * @return list<array{valor: string}>
     */
    protected function opcoes(string $coluna): array
    {
        return Activity::query()
            ->visiveisPara($this->usuario())
            ->select($coluna)
            ->whereNotNull($coluna)
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna)
            ->map(static fn (string $valor): array => ['valor' => $valor])
            ->all();
    }

    /**
     * O isolamento por empresa vive no scope Activity::visiveisPara — único
     * ponto reusado pelo grid, pelas opções de filtro e pelo drawer de detalhe.
     */
    private function usuario(): \App\Models\AdminUser
    {
        $user = Auth::guard('admin')->user();

        abort_if($user === null, 403);

        return $user;
    }

    private function podeVerTodasEmpresas(): bool
    {
        return Auth::guard('admin')->user()?->can('auditoria.todas-empresas') ?? false;
    }
}
