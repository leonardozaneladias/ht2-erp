<?php

declare(strict_types=1);

namespace App\Actions\Admin\Menu;

use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Illuminate\Support\Facades\DB;

/**
 * Aplica a disposição padrão do menu do starter kit:
 *
 * - Seção Administração: grupos "Cadastros" (Empresas, Usuários admin) e
 *   "Segurança" (Controle de acesso, Menus, Configurações); Auditoria e
 *   Comunicados diretos.
 * - Seção Tabelas Auxiliares: grupos "Cadastros" (catálogos de referência) e
 *   "RH" (Departamentos — item contribuído pelo pacote modulo-rh).
 *
 * Idempotente e não-destrutiva nos dois eixos: vira no-op se JÁ existe algum
 * grupo (instalação organizada/cliente mexeu) e cada linha usa firstOrCreate
 * (nunca sobrescreve personalização de item pré-existente). Itens cujo registro
 * ainda não exista no config viram personalização dormente — passam a renderizar
 * assim que o item correspondente entrar em config/admin-menu.php.
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
                'grupo-cadastros' => ['label' => 'Cadastros', 'icone' => 'tabler--folder', 'secao_key' => 'administracao', 'ordem' => 1],
                'grupo-seguranca' => ['label' => 'Segurança', 'icone' => 'tabler--shield-lock', 'secao_key' => 'administracao', 'ordem' => 2],
                'grupo-tab-cadastros' => ['label' => 'Cadastros', 'icone' => 'tabler--folder', 'secao_key' => 'tabelas-auxiliares', 'ordem' => 1],
                'grupo-tab-rh' => ['label' => 'RH', 'icone' => 'tabler--users-group', 'secao_key' => 'tabelas-auxiliares', 'ordem' => 2],
            ];

            foreach ($grupos as $key => $dados) {
                MenuPersonalizacao::query()->firstOrCreate(
                    ['tipo' => TipoPersonalizacaoMenu::Grupo, 'key' => $key],
                    [...$dados, 'e_custom' => true],
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

                // Tabelas Auxiliares → Cadastros (catálogos de referência).
                'ref-estados' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 1],
                'ref-paises' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 2],
                'ref-municipios' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 3],
                'ref-moedas' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 4],
                'ref-bancos' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 5],
                'ref-cargos' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 6],
                'ref-tipos-logradouro' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 7],
                'ref-cnaes' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 8],
                'ref-cfops' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 9],
                'ref-ncms' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 10],

                // Tabelas Auxiliares → RH (item do pacote modulo-rh, movido da seção Negócio).
                'rh-departamentos' => ['grupo_key' => 'grupo-tab-rh', 'ordem' => 1],
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
