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
        'title' => 'Configurações',
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
                'label' => 'Logs de auditoria',
                'icon' => 'tabler--history',
                'route' => 'admin.auditoria.index',
                'permission' => 'auditoria.visualizar',
                'active' => ['admin.auditoria.*'],
            ],
        ],
    ],
];
