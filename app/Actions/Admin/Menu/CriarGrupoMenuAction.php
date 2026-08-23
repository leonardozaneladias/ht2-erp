<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Support\Menu\IconesMenu;
use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria um grupo (submenu) do menu — apresentação pura, sem rota/permissão.
 * A key não pode colidir com itens do config: o resolver da reordenação
 * distingue grupo × item pela key. Criação auditada pelo trait Auditavel.
 */
class CriarGrupoMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    public function execute(string $label, string $icone, string $secaoKey): MenuPersonalizacao
    {
        $label = trim($label);

        if ($label === '') {
            throw new InvalidArgumentException('Informe o nome do grupo.');
        }

        if (! IconesMenu::valido($icone)) {
            throw new InvalidArgumentException('Ícone fora da lista suportada.');
        }

        if (! in_array($secaoKey, $this->menu->destinosDeSecao(), true)) {
            throw new InvalidArgumentException('Seção de menu desconhecida.');
        }

        $key = $this->keyDisponivel('grupo-' . Str::slug($label));

        $personalizacao = DB::transaction(fn (): MenuPersonalizacao => MenuPersonalizacao::create([
            'tipo' => TipoPersonalizacaoMenu::Grupo,
            'key' => $key,
            'label' => $label,
            'icone' => $icone,
            'secao_key' => $secaoKey,
            'e_custom' => true,
        ]));

        $this->menu->invalidarCache();

        return $personalizacao;
    }

    private function keyDisponivel(string $base): string
    {
        if ($base === 'grupo-') {
            $base = 'grupo-' . Str::lower(Str::random(6));
        }

        $ocupadas = array_merge(
            $this->menu->chavesDeItens(),
            MenuPersonalizacao::query()
                ->where('tipo', TipoPersonalizacaoMenu::Grupo)
                ->pluck('key')
                ->all(),
        );

        $key = $base;
        $sufixo = 2;

        while (in_array($key, $ocupadas, true)) {
            $key = "{$base}-{$sufixo}";
            $sufixo++;
        }

        return $key;
    }
}
