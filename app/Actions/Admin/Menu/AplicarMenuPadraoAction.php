<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;

/**
 * Aplica a disposição padrão do menu do starter kit: grupos "Cadastros"
 * (Empresas, Usuários admin) e "Segurança" (Controle de acesso, Menus,
 * Configurações) na seção Administração, com Auditoria e Comunicados diretos.
 *
 * Idempotente e não-destrutiva nos dois eixos: vira no-op se JÁ existe algum
 * grupo (instalação organizada/cliente mexeu) e cada linha usa firstOrCreate
 * (nunca sobrescreve personalização de item pré-existente).
 *
 * Chamada pelo MenuPadraoSeeder (dev) e pelo ConcluirSetupAction (produção —
 * o deploy roda só `migrate`; o DatabaseSeeder nunca executa lá).
 */
class AplicarMenuPadraoAction
{
    public function __construct(private readonly MenuService $menu) {}

    /**
     * Retorna true quando a disposição foi aplicada (false = no-op).
     */
    public function execute(): bool
    {
        if (MenuPersonalizacao::query()->where('tipo', TipoPersonalizacaoMenu::Grupo)->exists()) {
            return false;
        }

        DB::transaction(function (): void {
            $grupos = [
                'grupo-cadastros' => ['label' => 'Cadastros', 'icone' => 'tabler--folder', 'ordem' => 1],
                'grupo-seguranca' => ['label' => 'Segurança', 'icone' => 'tabler--shield-lock', 'ordem' => 2],
            ];

            foreach ($grupos as $key => $dados) {
                MenuPersonalizacao::query()->firstOrCreate(
                    ['tipo' => TipoPersonalizacaoMenu::Grupo, 'key' => $key],
                    [...$dados, 'secao_key' => 'administracao', 'e_custom' => true],
                );
            }

            $itens = [
                'empresas' => ['grupo_key' => 'grupo-cadastros', 'ordem' => 1],
                'usuarios' => ['grupo_key' => 'grupo-cadastros', 'ordem' => 2],
                'acesso' => ['grupo_key' => 'grupo-seguranca', 'ordem' => 1],
                'menus' => ['grupo_key' => 'grupo-seguranca', 'ordem' => 2],
                'configuracoes' => ['grupo_key' => 'grupo-seguranca', 'ordem' => 3],
                'auditoria' => ['ordem' => 3],
                'comunicados' => ['ordem' => 4],
            ];

            foreach ($itens as $key => $dados) {
                MenuPersonalizacao::query()->firstOrCreate(
                    ['tipo' => TipoPersonalizacaoMenu::Item, 'key' => $key],
                    $dados,
                );
            }
        });

        $this->menu->invalidarCache();

        return true;
    }
}
