<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Extensão RH
|--------------------------------------------------------------------------
|
| Onde o módulo entra no core e como ele se apresenta. As PERMISSÕES e os ITENS
| DE MENU não estão aqui: são derivados da chave de cada recurso pelo
| ModuloBuilder (ADR-0021), porque escrevê-los à mão é a chance de divergir —
| e já divergiram uma vez, com o gerador emitindo 'departamentos.listar' onde o
| catálogo dizia 'rh.departamentos.listar'.
|
| O que sobra aqui é decisão de quem instala: onde as contribuições entram,
| como cada recurso é chamado e em que ordem aparece.
|
*/

return [
    // Área do catálogo de acesso e seção da sidebar onde as contribuições entram.
    // 'modulo_acesso' é uma chave de config('access.areas'); 'secao_menu', a key
    // de uma seção de config('admin-menu').
    'modulo_acesso' => 'negocio',
    'secao_menu' => 'tabelas-auxiliares',

    // Grupos (submenus) que esta extensão declara. Antes, este arranjo vivia
    // hardcoded na AplicarMenuPadraoAction do core — o core conhecendo a
    // extensão pelo nome. Faixa de ordem das extensões: 500+.
    'grupos' => [
        'grupo-tab-rh' => [
            'secao' => 'tabelas-auxiliares',
            'label' => 'RH',
            'icone' => 'tabler--users-group',
            'ordem' => 500,
        ],
    ],

    // Um recurso por linha. Dele saem seis permissões, o item de menu, o nome
    // da rota, a permissão que guarda o item e o padrão de `active`.
    'recursos' => [
        'departamentos' => [
            'label' => 'Departamentos',
            'singular' => 'departamento',
            'icone' => 'tabler--folder',
            'grupo' => 'grupo-tab-rh',
            'ordem' => 500,
        ],
        'funcionarios' => [
            'label' => 'Funcionários',
            'singular' => 'funcionário',
            'icone' => 'tabler--folder',
            'grupo' => 'grupo-tab-rh',
            'ordem' => 600,
        ],
        // make:recurso insere os recursos do módulo acima desta linha
    ],
];
