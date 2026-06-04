<?php

declare(strict_types=1);

namespace App\PowerGridThemes;

use PowerComponents\LivewirePowerGrid\Themes\Tailwind;

/**
 * Tema PowerGrid alinhado ao design system Inspinia do projeto.
 *
 * Em vez de publicar e manter dezenas de views do vendor, mapeamos cada "slot"
 * do PowerGrid para as classes/componentes CSS que o admin ja usa
 * (.table, .form-input, .form-select, .form-checkbox, .badge, .pagination).
 * Resultado: as tabelas PowerGrid ficam coerentes com as tabelas nativas
 * (x-admin.table.*), herdam o dark mode via [data-theme="dark"] e ficam
 * compactas (densidade fixa) para economizar scroll.
 *
 * Mantemos $name = 'tailwind' de proposito: assim reaproveitamos os templates
 * Blade do framework "tailwind" do pacote (header, filtros, footer, checkbox),
 * sem precisar publicar/duplicar views — apenas trocamos as classes.
 */
final class InspiniaTheme extends Tailwind
{
    public string $name = 'tailwind';

    /**
     * @return array<string, array<string, string>>
     */
    public function table(): array
    {
        return [
            'layout' => [
                // Card Inspinia que envolve header + tabela + footer.
                'base' => 'bg-card border border-light rounded-[10px] shadow-sm p-4',
                // Sem bordas/margens negativas do tema padrao (origem do desalinhamento).
                'div' => 'overflow-x-auto',
                // Puxa todo o CSS nativo .table (borda, hover, sticky, densidade compacta).
                'table' => 'table table-hover table-sticky table-sm w-full align-middle',
                'container' => 'w-full',
                'actions' => 'flex items-center gap-1.5',
            ],

            'header' => [
                'thead' => '',
                'tr' => '',
                'th' => 'whitespace-nowrap',
                'thAction' => '!font-bold',
            ],

            'body' => [
                'tbody' => '',
                'tbodyEmpty' => '',
                'tr' => '',
                'td' => 'align-middle',
                'tdEmpty' => 'text-default-400 p-4 text-center',
                'tdSummarize' => 'text-default-600 space-y-2 p-2 text-right text-sm',
                'trSummarize' => '',
                'tdFilters' => 'px-2 py-1.5 align-top',
                'trFilters' => 'border-light border-b',
                'tdActionsContainer' => 'flex items-center gap-1.5',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function footer(): array
    {
        return [
            'view' => $this->root() . '.footer',
            'select' => 'form-select form-select-sm w-auto !bg-none !pe-9',
            'footer' => 'border-light mt-3 flex items-center border-t pt-3 text-sm',
            'footer_with_pagination' => 'md:flex md:flex-row w-full items-center',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function editable(): array
    {
        return [
            'view' => $this->root() . '.editable',
            'input' => 'form-input form-input-sm w-full',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function checkbox(): array
    {
        return [
            'th' => 'w-px align-middle',
            'base' => '',
            'label' => 'inline-flex items-center',
            'input' => 'form-checkbox',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function radio(): array
    {
        return [
            'th' => 'w-px align-middle',
            'base' => '',
            'label' => 'inline-flex items-center',
            'input' => 'form-radio',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterBoolean(): array
    {
        return [
            'view' => $this->root() . '.filters.boolean',
            'base' => 'min-w-[7rem]',
            'select' => 'form-select form-select-sm w-full !bg-none !pe-8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterDatePicker(): array
    {
        return [
            'base' => '',
            'view' => $this->root() . '.filters.date-picker',
            'input' => 'flatpickr flatpickr-input form-input form-input-sm w-full',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterMultiSelect(): array
    {
        return [
            'view' => $this->root() . '.filters.multi-select',
            'base' => 'relative inline-block w-full',
            'select' => 'mt-1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterNumber(): array
    {
        return [
            'view' => $this->root() . '.filters.number',
            'input' => 'form-input form-input-sm w-full min-w-[5rem]',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterSelect(): array
    {
        return [
            'view' => $this->root() . '.filters.select',
            'base' => '',
            'select' => 'form-select form-select-sm w-full !bg-none !pe-8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function filterInputText(): array
    {
        return [
            'view' => $this->root() . '.filters.input-text',
            'base' => 'min-w-[8rem]',
            'select' => 'form-select form-select-sm w-full !bg-none !pe-8',
            'input' => 'form-input form-input-sm w-full',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function searchBox(): array
    {
        return [
            'input' => 'form-input form-input-sm w-full !ps-9',
            'iconClose' => 'text-default-400',
            'iconSearch' => 'text-default-400 mr-2 h-4 w-4',
        ];
    }
}
