<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\DTOs\Admin\MenuPersonalizacaoDTO;
use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use App\Support\Menu\IconesMenu;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Grava a personalização de aparência (label, ícone, ativo) de um item ou
 * seção do menu. Valores iguais ao padrão do config viram null (herdam o
 * config); se a linha inteira ficar igual ao padrão, ela é removida — o
 * badge "Personalizado" continua honesto.
 */
class SalvarPersonalizacaoMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    public function execute(MenuPersonalizacaoDTO $dto): ?MenuPersonalizacao
    {
        $padrao = $this->menu->padraoDe($dto->tipo, $dto->key);

        if ($padrao === null) {
            throw new InvalidArgumentException('Item ou seção de menu desconhecidos.');
        }

        $label = $dto->label === $padrao['label'] ? null : $dto->label;
        $icone = $dto->tipo === TipoPersonalizacaoMenu::Item && $dto->icone !== $padrao['icone']
            ? $dto->icone
            : null;

        // Ícones são build-time (iconify/tailwind): fora da lista curada não
        // haveria CSS. A view também valida; aqui é a defesa final.
        if ($icone !== null && ! IconesMenu::valido($icone)) {
            throw new InvalidArgumentException('Ícone fora da lista suportada.');
        }

        $personalizacao = DB::transaction(function () use ($dto, $label, $icone): ?MenuPersonalizacao {
            $registro = MenuPersonalizacao::query()->firstOrNew([
                'tipo' => $dto->tipo,
                'key' => $dto->key,
            ]);

            $registro->label = $label;
            $registro->icone = $icone;
            $registro->ativo = $dto->tipo === TipoPersonalizacaoMenu::Item ? $dto->ativo : true;

            $igualAoPadrao = $registro->label === null
                && $registro->icone === null
                && $registro->ativo === true
                && $registro->ordem === null
                && $registro->secao_key === null;

            if ($igualAoPadrao) {
                if ($registro->exists) {
                    $registro->delete();
                }

                return null;
            }

            $registro->save();

            return $registro;
        });

        $this->menu->invalidarCache();

        return $personalizacao;
    }
}
