<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Extensão Fiscal BR
|--------------------------------------------------------------------------
|
| Catálogos de classificação fiscal brasileira: CNAE, CFOP e NCM. Saíram do
| core porque só sistemas fiscais precisam deles — uma escola ou um CRM nunca
| vão querer CFOP (ver ADR-0019).
|
*/

return [
    // Módulo do catálogo de acesso e seção da sidebar onde as contribuições entram.
    'modulo_acesso' => 'tabelas_auxiliares',
    'secao_menu' => 'tabelas-auxiliares',

    'permissoes' => [
        'cnaes.listar' => ['label' => 'Listar CNAEs', 'descricao' => 'Ver o catálogo de CNAEs.'],
        'cnaes.criar' => ['label' => 'Criar CNAEs', 'descricao' => 'Cadastrar novos CNAEs.'],
        'cnaes.editar' => ['label' => 'Editar CNAEs', 'descricao' => 'Alterar dados de CNAEs.'],
        'cnaes.deletar' => ['label' => 'Excluir CNAEs', 'descricao' => 'Mover CNAEs para a lixeira.'],
        'cnaes.restaurar' => ['label' => 'Restaurar CNAEs', 'descricao' => 'Restaurar CNAEs da lixeira.'],
        'cnaes.excluir_permanente' => ['label' => 'Excluir CNAEs permanentemente', 'descricao' => 'Remover CNAEs definitivamente (irreversível).'],
        'cfops.listar' => ['label' => 'Listar CFOPs', 'descricao' => 'Ver o catálogo de CFOPs.'],
        'cfops.criar' => ['label' => 'Criar CFOPs', 'descricao' => 'Cadastrar novos CFOPs.'],
        'cfops.editar' => ['label' => 'Editar CFOPs', 'descricao' => 'Alterar dados de CFOPs.'],
        'cfops.deletar' => ['label' => 'Excluir CFOPs', 'descricao' => 'Mover CFOPs para a lixeira.'],
        'cfops.restaurar' => ['label' => 'Restaurar CFOPs', 'descricao' => 'Restaurar CFOPs da lixeira.'],
        'cfops.excluir_permanente' => ['label' => 'Excluir CFOPs permanentemente', 'descricao' => 'Remover CFOPs definitivamente (irreversível).'],
        'ncms.listar' => ['label' => 'Listar NCMs', 'descricao' => 'Ver o catálogo de NCMs.'],
        'ncms.criar' => ['label' => 'Criar NCMs', 'descricao' => 'Cadastrar novos NCMs.'],
        'ncms.editar' => ['label' => 'Editar NCMs', 'descricao' => 'Alterar dados de NCMs.'],
        'ncms.deletar' => ['label' => 'Excluir NCMs', 'descricao' => 'Mover NCMs para a lixeira.'],
        'ncms.restaurar' => ['label' => 'Restaurar NCMs', 'descricao' => 'Restaurar NCMs da lixeira.'],
        'ncms.excluir_permanente' => ['label' => 'Excluir NCMs permanentemente', 'descricao' => 'Remover NCMs definitivamente (irreversível).'],
    ],

    'menu' => [
        [
            'key' => 'ref-cnaes',
            'label' => 'CNAE',
            'icon' => 'tabler--list-numbers',
            'route' => 'admin.referencia.cnaes.index',
            'permission' => 'cnaes.listar',
            'active' => ['admin.referencia.cnaes.*'],
        ],
        [
            'key' => 'ref-cfops',
            'label' => 'CFOP',
            'icon' => 'tabler--file-invoice',
            'route' => 'admin.referencia.cfops.index',
            'permission' => 'cfops.listar',
            'active' => ['admin.referencia.cfops.*'],
        ],
        [
            'key' => 'ref-ncms',
            'label' => 'NCM',
            'icon' => 'tabler--barcode',
            'route' => 'admin.referencia.ncms.index',
            'permission' => 'ncms.listar',
            'active' => ['admin.referencia.ncms.*'],
        ],
    ],
];
