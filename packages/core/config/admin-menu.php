<?php

declare(strict_types=1);

// Registro do menu lateral do admin. A `key` de cada seção/item é a âncora
// ESTÁVEL das personalizações (ordem, rótulo, ícone, ativo) feitas na tela
// de Gestão de Menus — nunca renomeie uma key sem migrar a personalização.
//
// `ordem` e `grupo` são DECLARAÇÃO: a disposição que este pacote sugere. Quem
// instalou decide, arrastando na tela — e a decisão dele, gravada em
// menu_personalizacoes, vence sempre. Antes disto a disposição padrão era uma
// Action no core que hardcodava 'grupo-tab-rh', 'ref-cnaes' e 'rh-departamentos'
// (o core conhecendo extensões pelo nome), e toda instalação nova nascia com 23
// linhas de personalização que nenhum humano tinha escolhido.
//
// Faixas de ordem: core 100–499, extensões e módulos do produto 500+.
return [
    [
        'key' => 'principal',
        'title' => 'Principal',
        'ordem' => 100,
        'items' => [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'tabler--dashboard',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
                'ordem' => 100,
            ],
        ],
    ],
    [
        'key' => 'administracao',
        'title' => 'Administração',
        'ordem' => 200,
        'grupos' => [
            'grupo-cadastros' => ['label' => 'Organização', 'icone' => 'tabler--folder', 'ordem' => 100],
            'grupo-seguranca' => ['label' => 'Segurança', 'icone' => 'tabler--shield-lock', 'ordem' => 200],
        ],
        'items' => [
            [
                'key' => 'empresas',
                'label' => 'Empresas',
                'icon' => 'tabler--building-community',
                'route' => 'admin.empresas.index',
                'permission' => 'empresas.listar',
                'active' => ['admin.empresas.*'],
                'grupo' => 'grupo-cadastros',
                'ordem' => 100,
            ],
            [
                'key' => 'usuarios',
                'label' => 'Usuários admin',
                'icon' => 'tabler--users',
                'route' => 'admin.usuarios.index',
                'permission' => 'usuarios.listar',
                'active' => ['admin.usuarios.*'],
                'grupo' => 'grupo-cadastros',
                'ordem' => 200,
            ],
            [
                'key' => 'acesso',
                'label' => 'Controle de acesso',
                'icon' => 'tabler--shield-lock',
                'route' => 'admin.acesso.index',
                'permission' => 'perfis.listar',
                'active' => ['admin.acesso.*', 'admin.perfis.*'],
                'grupo' => 'grupo-seguranca',
                'ordem' => 100,
            ],
            [
                'key' => 'menus',
                'label' => 'Menus',
                'icon' => 'tabler--layout-sidebar',
                'route' => 'admin.menus.index',
                'permission' => 'configuracoes.menus',
                'active' => ['admin.menus.*'],
                'grupo' => 'grupo-seguranca',
                'ordem' => 200,
            ],
            [
                'key' => 'configuracoes',
                'label' => 'Configurações',
                'icon' => 'tabler--settings',
                'route' => 'admin.configuracoes.index',
                'permission' => 'configuracoes.editar',
                'active' => ['admin.configuracoes.*'],
                'grupo' => 'grupo-seguranca',
                'ordem' => 300,
            ],
            [
                'key' => 'auditoria',
                'label' => 'Logs de auditoria',
                'icon' => 'tabler--history',
                'route' => 'admin.auditoria.index',
                'permission' => 'auditoria.visualizar',
                'active' => ['admin.auditoria.*'],
                'ordem' => 300,
            ],
            [
                'key' => 'comunicados',
                'label' => 'Comunicados',
                'icon' => 'tabler--bell',
                'route' => 'admin.comunicados',
                'permission' => 'notificacoes.enviar',
                'active' => ['admin.comunicados'],
                'ordem' => 400,
            ],
        ],
    ],
    [
        // Módulos de negócio gerados pelo make:modulo. Cada item nasce com
        // `permission => '{modulo}.listar'`: visível só para super-admin (bypass)
        // até a permissão ser atribuída a outros perfis.
        'key' => 'negocio',
        'title' => 'Negócio',
        'ordem' => 300,
        'items' => [
            // make:modulo insere itens de menu acima desta linha
        ],
    ],
    [
        // Tabelas Auxiliares — catálogos de dados de referência (IBGE/BrasilAPI).
        'key' => 'tabelas-auxiliares',
        'title' => 'Tabelas Auxiliares',
        'ordem' => 400,
        'grupos' => [
            'grupo-tab-cadastros' => ['label' => 'Cadastros', 'icone' => 'tabler--folder', 'ordem' => 100],
        ],
        'items' => [
            [
                'key' => 'ref-estados',
                'label' => 'Estados',
                'icon' => 'tabler--map-pin',
                'route' => 'admin.referencia.estados.index',
                'permission' => 'estados.listar',
                'active' => ['admin.referencia.estados.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 100,
            ],
            [
                'key' => 'ref-paises',
                'label' => 'Países',
                'icon' => 'tabler--world',
                'route' => 'admin.referencia.paises.index',
                'permission' => 'paises.listar',
                'active' => ['admin.referencia.paises.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 200,
            ],
            [
                'key' => 'ref-municipios',
                'label' => 'Municípios',
                'icon' => 'tabler--building-estate',
                'route' => 'admin.referencia.municipios.index',
                'permission' => 'municipios.listar',
                'active' => ['admin.referencia.municipios.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 300,
            ],
            [
                'key' => 'ref-moedas',
                'label' => 'Moedas',
                'icon' => 'tabler--coin',
                'route' => 'admin.referencia.moedas.index',
                'permission' => 'moedas.listar',
                'active' => ['admin.referencia.moedas.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 400,
            ],
            [
                'key' => 'ref-bancos',
                'label' => 'Bancos',
                'icon' => 'tabler--building-bank',
                'route' => 'admin.referencia.bancos.index',
                'permission' => 'bancos.listar',
                'active' => ['admin.referencia.bancos.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 500,
            ],
            [
                'key' => 'ref-cargos',
                'label' => 'Cargos',
                'icon' => 'tabler--briefcase',
                'route' => 'admin.referencia.cargos.index',
                'permission' => 'cargos.listar',
                'active' => ['admin.referencia.cargos.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 600,
            ],
            [
                'key' => 'ref-tipos-logradouro',
                'label' => 'Tipos de Logradouro',
                'icon' => 'tabler--road',
                'route' => 'admin.referencia.tipos_logradouro.index',
                'permission' => 'tipos_logradouro.listar',
                'active' => ['admin.referencia.tipos_logradouro.*'],
                'grupo' => 'grupo-tab-cadastros',
                'ordem' => 700,
            ],
        ],
    ],
];
