<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\PowerGrid\Exports;

use PowerComponents\LivewirePowerGrid\Components\Exports\OpenSpout\v5\ExportToXLS as BaseExportToXLS;

/**
 * Exporter XLS do PowerGrid com limpeza de HTML/whitespace (ver CleansExportData).
 */
class ExportToXLS extends BaseExportToXLS
{
    use CleansExportData;
}
