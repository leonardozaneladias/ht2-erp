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
                'label' => 'Empresas',
                'icon' => 'tabler--building-community',
                'route' => 'admin.empresas.index',
                'permission' => 'empresas.listar',
                'active' => ['admin.empresas.*'],
            ],
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
            [
                'label' => 'Comunicados',
                'icon' => 'tabler--bell',
                'route' => 'admin.comunicados',
                'permission' => 'notificacoes.enviar',
                'active' => ['admin.comunicados'],
            ],
            [
                'label' => 'Configurações',
                'icon' => 'tabler--settings',
                'route' => 'admin.configuracoes.index',
                'permission' => 'configuracoes.editar',
                'active' => ['admin.configuracoes.*'],
            ],
        ],
    ],
];
