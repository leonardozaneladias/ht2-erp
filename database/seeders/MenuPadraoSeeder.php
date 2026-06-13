<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Admin\Menu\AplicarMenuPadraoAction;
use Illuminate\Database\Seeder;

/**
 * Disposição padrão do menu (grupos Cadastros e Segurança) para ambientes de
 * desenvolvimento. Em produção o mesmo arranjo é aplicado pelo Setup Wizard
 * (ConcluirSetupAction) — o deploy nunca roda seeders.
 */
class MenuPadraoSeeder extends Seeder
{
    public function run(): void
    {
        app(AplicarMenuPadraoAction::class)->execute();
    }
}
