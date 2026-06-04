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
        'title' => 'Administração',
        'items' => [
            [
                'label' => 'Controle de acesso',
                'icon' => 'tabler--shield-lock',
                'route' => 'admin.acesso.index',
                'permission' => 'perfis.listar',
                'active' => ['admin.acesso.*', 'admin.perfis.*'],
            ],
            [
                'label' => 'Usuários admin',
                'icon' => 'tabler--users',
                'route' => 'admin.usuarios.index',
                'permission' => 'usuarios.listar',
                'active' => ['admin.usuarios.*'],
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
