<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Persiste a nova ordem dos itens do menu após um drag & drop, incluindo a
 * movimentação entre seções. O payload vem do cliente e é hostil: toda key
 * é validada contra o registro do config antes de gravar.
 */
class ReordenarItensMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    /**
     * @param  array<string, list<string>>  $ordens  ordem completa dos itens por seção afetada
     */
    public function execute(string $itemKey, string $secaoDestino, array $ordens): void
    {
        $chavesItens = $this->menu->chavesDeItens();
        $chavesSecoes = $this->menu->chavesDeSecoes();

        if (! in_array($itemKey, $chavesItens, true) || ! in_array($secaoDestino, $chavesSecoes, true)) {
            throw new InvalidArgumentException('Item ou seção de menu desconhecidos.');
        }

        foreach ($ordens as $secaoKey => $itens) {
            if (! in_array($secaoKey, $chavesSecoes, true)) {
                throw new InvalidArgumentException("Seção de menu desconhecida: {$secaoKey}");
            }

            foreach ($itens as $key) {
                if (! is_string($key) || ! in_array($key, $chavesItens, true)) {
                    throw new InvalidArgumentException('Item de menu desconhecido no payload de ordenação.');
                }
            }
        }

        DB::transaction(function () use ($itemKey, $secaoDestino, $ordens): void {
            foreach ($ordens as $secaoKey => $itens) {
                foreach (array_values($itens) as $posicao => $key) {
                    $personalizacao = MenuPersonalizacao::query()->firstOrNew([
                        'tipo' => TipoPersonalizacaoMenu::Item,
                        'key' => $key,
                    ]);

                    $personalizacao->ordem = $posicao + 1;

                    // Seção destino só é gravada quando difere da natural —
                    // mantém o badge "Personalizado" significativo.
                    $personalizacao->secao_key = $secaoKey === $this->menu->secaoNaturalDoItem($key)
                        ? null
                        : $secaoKey;

                    $personalizacao->save();
                }
            }

            // Resumo de domínio: a operação em massa que os diffs por linha
            // (trait Auditavel) não expressam de uma vez.
            activity('menus')
                ->withProperties(['item' => $itemKey, 'secao_destino' => $secaoDestino, 'ordens' => $ordens])
                ->event('menu_reordenado')
                ->log('Itens do menu reordenados');
        });

        $this->menu->invalidarCache();
    }
}
