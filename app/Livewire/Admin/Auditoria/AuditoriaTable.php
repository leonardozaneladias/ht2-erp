<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auditoria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
use Spatie\Activitylog\Models\Activity;

final class AuditoriaTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'auditoria-table';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

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
        return Activity::query()->with(['causer', 'subject']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_formatted', fn (Activity $a): string => $a->created_at instanceof Carbon ? $a->created_at->format('d/m/Y H:i:s') : '—')
            ->add('quem', fn (Activity $a): string => e($this->quem($a)))
            ->add('log_name', fn (Activity $a): string => Blade::render('<x-shared.badge>{{ $l }}</x-shared.badge>', ['l' => $a->log_name ?? '—']))
            ->add('event', fn (Activity $a): string => e($a->event ?? '—'))
            ->add('sujeito', fn (Activity $a): string => $this->sujeito($a))
            ->add('description', fn (Activity $a): string => e((string) $a->description));
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

            Column::make('Log', 'log_name')
                ->sortable(),

            Column::make('Evento', 'event')
                ->sortable(),

            Column::make('Sujeito', 'sujeito'),

            Column::make('Descrição', 'description')
                ->searchable(),
        ];
    }

    /**
     * @return array<int, FilterBase>
     */
    public function filters(): array
    {
        return [
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
            '<span class="text-default-600 text-sm">{{ $tipo }} #{{ $id }}</span>',
            ['tipo' => class_basename($a->subject_type), 'id' => (string) $a->subject_id],
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
            ->select($coluna)
            ->whereNotNull($coluna)
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna)
            ->map(static fn (string $valor): array => ['valor' => $valor])
            ->all();
    }
}
