<?php

declare(strict_types=1);

// Registro do menu lateral do admin. A `key` de cada seção/item é a âncora
// ESTÁVEL das personalizações (ordem, rótulo, ícone, ativo) feitas na tela
// de Gestão de Menus — nunca renomeie uma key sem migrar a personalização.
return [
    [
        'key' => 'principal',
        'title' => 'Principal',
        'items' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'tabler--dashboard',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],
        ],
    ],
    [
        'key' => 'administracao',
        'title' => 'Administração',
        'items' => [
            [
                'key' => 'empresas',
                'label' => 'Empresas',
                'icon' => 'tabler--building-community',
                'route' => 'admin.empresas.index',
                'permission' => 'empresas.listar',
                'active' => ['admin.empresas.*'],
            ],
            [
                'key' => 'acesso',
                'label' => 'Controle de acesso',
                'icon' => 'tabler--shield-lock',
                'route' => 'admin.acesso.index',
                'permission' => 'perfis.listar',
                'active' => ['admin.acesso.*', 'admin.perfis.*'],
            ],
            [
                'key' => 'usuarios',
                'label' => 'Usuários admin',
                'icon' => 'tabler--users',
                'route' => 'admin.usuarios.index',
                'permission' => 'usuarios.listar',
                'active' => ['admin.usuarios.*'],
            ],
            [
                'key' => 'auditoria',
                'label' => 'Logs de auditoria',
                'icon' => 'tabler--history',
                'route' => 'admin.auditoria.index',
                'permission' => 'auditoria.visualizar',
                'active' => ['admin.auditoria.*'],
            ],
            [
                'key' => 'comunicados',
                'label' => 'Comunicados',
                'icon' => 'tabler--bell',
                'route' => 'admin.comunicados',
                'permission' => 'notificacoes.enviar',
                'active' => ['admin.comunicados'],
            ],
            [
                'key' => 'configuracoes',
                'label' => 'Configurações',
                'icon' => 'tabler--settings',
                'route' => 'admin.configuracoes.index',
                'permission' => 'configuracoes.editar',
                'active' => ['admin.configuracoes.*'],
            ],
        ],
    ],
];
