<?php

declare(strict_types=1);

return [
    [
        'title' => 'Principal',
        'items' => [
            [
                'label' => 'Dashboard',
                'icon' => 'tabler--dashboard',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],
        ],
    ],
    [
        'title' => 'Controle de Acesso',
        'items' => [
            [
                'label' => 'Usuários admin',
                'icon' => 'tabler--users',
                'route' => 'admin.usuarios.index',
                'permission' => 'usuarios.listar',
                'active' => ['admin.usuarios.*'],
            ],
            [
                'label' => 'Perfis & permissões',
                'icon' => 'tabler--shield-lock',
                'route' => 'admin.perfis.index',
                'permission' => 'perfis.listar',
                'active' => ['admin.perfis.*'],
            ],
            [
                'label' => 'Matriz de permissões',
                'icon' => 'tabler--table',
                'route' => 'admin.acesso.matriz',
                'permission' => 'acessos.matriz',
                'active' => ['admin.acesso.matriz'],
            ],
            [
                'label' => 'Simulador de acesso',
                'icon' => 'tabler--user-search',
                'route' => 'admin.acesso.simulador',
                'permission' => 'acessos.simular',
                'active' => ['admin.acesso.simulador'],
            ],
            [
                'label' => 'Histórico de acesso',
                'icon' => 'tabler--clock-shield',
                'route' => 'admin.acesso.historico',
                'permission' => 'acessos.historico',
                'active' => ['admin.acesso.historico'],
            ],
            [
                'label' => 'Logs de auditoria',
                'icon' => 'tabler--history',
                'route' => 'admin.auditoria.index',
                'permission' => 'auditoria.visualizar',
                'active' => ['admin.auditoria.*'],
            ],
        ],
    ],
];
