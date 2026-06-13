<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;

/**
 * Remove personalizações do menu, devolvendo-o ao padrão do config.
 * Cada remoção é auditada pelo trait Auditavel (deleted) + resumo de domínio.
 */
class RestaurarMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    /**
     * Restaura tudo (sem argumentos), um tipo inteiro ou uma key específica.
     * Retorna quantas personalizações foram removidas.
     */
    public function execute(?TipoPersonalizacaoMenu $tipo = null, ?string $key = null): int
    {
        $removidas = DB::transaction(function () use ($tipo, $key): int {
            $registros = MenuPersonalizacao::query()
                ->when($tipo !== null, fn ($query) => $query->where('tipo', $tipo))
                ->when($key !== null, fn ($query) => $query->where('key', $key))
                ->get();

            $registros->each(fn (MenuPersonalizacao $personalizacao) => $personalizacao->delete());

            if ($registros->isNotEmpty()) {
                activity('menus')
                    ->withProperties([
                        'tipo' => $tipo?->value,
                        'key' => $key,
                        'removidas' => $registros->count(),
                    ])
                    ->event('menu_restaurado')
                    ->log('Menu restaurado para o padrão');
            }

            return $registros->count();
        });

        $this->menu->invalidarCache();

        return $removidas;
    }

    /**
     * Remove personalizações órfãs (key que não existe mais no config).
     */
    public function removerOrfas(): int
    {
        $chavesItens = $this->menu->chavesDeItens();
        $chavesSecoes = $this->menu->chavesDeSecoes();

        $removidas = DB::transaction(function () use ($chavesItens, $chavesSecoes): int {
            $orfas = MenuPersonalizacao::query()
                ->get()
                ->filter(function (MenuPersonalizacao $personalizacao) use ($chavesItens, $chavesSecoes): bool {
                    // Customs (seções/grupos criados pela tela) nunca são órfãs.
                    if ($personalizacao->e_custom || $personalizacao->tipo === TipoPersonalizacaoMenu::Grupo) {
                        return false;
                    }

                    return ! in_array(
                        $personalizacao->key,
                        $personalizacao->tipo === TipoPersonalizacaoMenu::Item ? $chavesItens : $chavesSecoes,
                        true,
                    );
                });

            $orfas->each(fn (MenuPersonalizacao $personalizacao) => $personalizacao->delete());

            if ($orfas->isNotEmpty()) {
                activity('menus')
                    ->withProperties(['keys' => $orfas->pluck('key')->values()->all()])
                    ->event('menu_orfas_removidas')
                    ->log('Personalizações órfãs de menu removidas');
            }

            return $orfas->count();
        });

        $this->menu->invalidarCache();

        return $removidas;
    }
}
