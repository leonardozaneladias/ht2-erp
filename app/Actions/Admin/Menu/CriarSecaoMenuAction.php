<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use HT2ML\Core\Enums\TipoPersonalizacaoMenu;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cria uma seção custom do menu (sem âncora no config). A criação em si é
 * auditada pelo trait Auditavel (created) — sem log de domínio duplicado.
 */
class CriarSecaoMenuAction
{
    public function __construct(private readonly MenuService $menu) {}

    public function execute(string $label): MenuPersonalizacao
    {
        $label = trim($label);

        if ($label === '') {
            throw new InvalidArgumentException('Informe o nome da seção.');
        }

        $key = $this->keyDisponivel('secao-' . Str::slug($label));

        $personalizacao = DB::transaction(fn (): MenuPersonalizacao => MenuPersonalizacao::create([
            'tipo' => TipoPersonalizacaoMenu::Secao,
            'key' => $key,
            'label' => $label,
            'e_custom' => true,
        ]));

        $this->menu->invalidarCache();

        return $personalizacao;
    }

    private function keyDisponivel(string $base): string
    {
        if ($base === 'secao-') {
            $base = 'secao-' . Str::lower(Str::random(6));
        }

        $ocupadas = array_merge(
            $this->menu->chavesDeSecoes(),
            MenuPersonalizacao::query()
                ->where('tipo', TipoPersonalizacaoMenu::Secao)
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
