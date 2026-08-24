<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Menu;

use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Persiste a nova ordem das seções do menu após um drag & drop.
 */
class ReordenarSecoesMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    /**
     * @param  list<string>  $secaoKeys  todas as seções, na nova ordem
     */
    public function execute(array $secaoKeys): void
    {
        $conhecidas = $this->menu->destinosDeSecao();

        foreach ($secaoKeys as $key) {
            if (! is_string($key) || ! in_array($key, $conhecidas, true)) {
                throw new InvalidArgumentException('Seção de menu desconhecida no payload de ordenação.');
            }
        }

        DB::transaction(function () use ($secaoKeys): void {
            foreach (array_values($secaoKeys) as $posicao => $key) {
                $personalizacao = MenuPersonalizacao::query()->firstOrNew([
                    'tipo' => TipoPersonalizacaoMenu::Secao,
                    'key' => $key,
                ]);

                $personalizacao->ordem = $posicao + 1;
                $personalizacao->save();
            }

            activity('menus')
                ->withProperties(['ordem' => array_values($secaoKeys)])
                ->event('menu_reordenado')
                ->log('Seções do menu reordenadas');
        });

        $this->menu->invalidarCache();
    }
}
