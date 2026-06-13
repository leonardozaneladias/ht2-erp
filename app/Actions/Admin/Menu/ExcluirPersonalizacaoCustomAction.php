<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Exclui uma seção ou grupo CUSTOM do menu. Os registros que apontavam para
 * ele caem no fallback do merge (grupo → seção natural; seção → idem), sem
 * quebrar nada. A exclusão é auditada pelo trait Auditavel (deleted).
 */
class ExcluirPersonalizacaoCustomAction
{
    public function __construct(private readonly MenuService $menu) {}

    public function execute(TipoPersonalizacaoMenu $tipo, string $key): void
    {
        $registro = MenuPersonalizacao::query()
            ->where('tipo', $tipo)
            ->where('key', $key)
            ->first();

        if ($registro === null || ! $registro->e_custom) {
            throw new InvalidArgumentException('Somente seções e grupos criados pela tela podem ser excluídos.');
        }

        DB::transaction(fn () => $registro->delete());

        $this->menu->invalidarCache();
    }
}
