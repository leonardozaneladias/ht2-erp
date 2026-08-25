<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Menu;

/**
 * Lista curada de ícones para itens de menu.
 *
 * Os ícones do iconify/tabler são gerados em BUILD TIME (o plugin Tailwind
 * escaneia classes literais nos arquivos): um ícone digitado livre não teria
 * CSS. Por isso a tela de gestão só aceita valores desta lista — e o grid de
 * sugestões da view renderiza cada classe literalmente, garantindo presença
 * no bundle. Ao adicionar um ícone aqui, ele entra no build pelo grid.
 */
final class IconesMenu
{
    /**
     * @return list<string>
     */
    public static function disponiveis(): array
    {
        return [
            // Em uso no registro do menu (config/admin-menu.php e configs de
            // extensão). Estes sete faltavam: a tela recusava justamente os
            // ícones que o menu já usava, então trocar o ícone de "Bancos" era
            // um caminho sem volta — `php artisan ht2ml:doutor` os apontou.
            'tabler--dashboard',
            'tabler--building-community',
            'tabler--shield-lock',
            'tabler--users',
            'tabler--history',
            'tabler--bell',
            'tabler--settings',
            'tabler--layout-sidebar',
            'tabler--building-estate',
            'tabler--coin',
            'tabler--building-bank',
            'tabler--road',
            'tabler--list-numbers',
            'tabler--barcode',
            'tabler--components',
            // Navegação / geral
            'tabler--home',
            'tabler--layout-grid',
            'tabler--list-details',
            'tabler--folder',
            'tabler--file-text',
            'tabler--clipboard-list',
            'tabler--checklist',
            'tabler--star',
            'tabler--puzzle',
            'tabler--tools',
            'tabler--adjustments',
            'tabler--help-circle',
            // Pessoas / acesso
            'tabler--user',
            'tabler--users-group',
            'tabler--id-badge',
            'tabler--key',
            'tabler--lock',
            'tabler--world',
            // Comercial / operação
            'tabler--briefcase',
            'tabler--building-store',
            'tabler--box',
            'tabler--package',
            'tabler--truck',
            'tabler--shopping-cart',
            'tabler--tag',
            'tabler--tags',
            // Financeiro
            'tabler--cash',
            'tabler--credit-card',
            'tabler--wallet',
            'tabler--file-invoice',
            'tabler--receipt',
            // Análise / comunicação
            'tabler--chart-bar',
            'tabler--chart-pie',
            'tabler--report-analytics',
            'tabler--calendar',
            'tabler--clock',
            'tabler--mail',
            'tabler--message',
            'tabler--phone',
            'tabler--map-pin',
            'tabler--link',
            'tabler--database',
        ];
    }

    public static function valido(string $icone): bool
    {
        return in_array($icone, self::disponiveis(), true);
    }
}
