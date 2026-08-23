<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuração do módulo Rh (HT2 ERP)
|--------------------------------------------------------------------------
|
| Publicável: php artisan vendor:publish --tag=rh-config
| É aqui que mora a customização por cliente (config-driven, ADR-0015): o
| catálogo de permissões e os itens de menu do módulo. O make:modulo preenche
| estas listas (entre as âncoras) ao gerar cada recurso CRUD.
|
*/

return [
    // Onde as contribuições desta extensão entram no core.
    // 'modulo_acesso' precisa ser um caso de App\Enums\ModuloAcesso; 'secao_menu',
    // a key de uma seção existente em config/admin-menu.php.
    'modulo_acesso' => 'negocio',
    'secao_menu' => 'negocio',

    // Permissões do módulo, no formato do catálogo do core (config/access.php).
    'permissoes' => [
        'rh.funcionarios.listar' => ['label' => 'Listar funcionarios', 'descricao' => 'Ver a listagem de funcionarios.'],
        'rh.funcionarios.criar' => ['label' => 'Criar funcionarios', 'descricao' => 'Cadastrar novos registros de funcionario.'],
        'rh.funcionarios.editar' => ['label' => 'Editar funcionarios', 'descricao' => 'Alterar dados e status de funcionarios.'],
        'rh.funcionarios.deletar' => ['label' => 'Excluir funcionarios', 'descricao' => 'Mover funcionarios para a lixeira.'],
        'rh.funcionarios.restaurar' => ['label' => 'Restaurar funcionarios', 'descricao' => 'Restaurar funcionarios da lixeira.'],
        'rh.funcionarios.excluir_permanente' => ['label' => 'Excluir funcionarios permanentemente', 'descricao' => 'Remover funcionarios definitivamente (irreversível).'],
        'rh.departamentos.listar' => ['label' => 'Listar departamentos', 'descricao' => 'Ver a listagem de departamentos.'],
        'rh.departamentos.criar' => ['label' => 'Criar departamentos', 'descricao' => 'Cadastrar novos registros de departamento.'],
        'rh.departamentos.editar' => ['label' => 'Editar departamentos', 'descricao' => 'Alterar dados e status de departamentos.'],
        'rh.departamentos.deletar' => ['label' => 'Excluir departamentos', 'descricao' => 'Mover departamentos para a lixeira.'],
        'rh.departamentos.restaurar' => ['label' => 'Restaurar departamentos', 'descricao' => 'Restaurar departamentos da lixeira.'],
        'rh.departamentos.excluir_permanente' => ['label' => 'Excluir departamentos permanentemente', 'descricao' => 'Remover departamentos definitivamente (irreversível).'],
        // make:modulo insere as permissões do módulo acima desta linha
    ],

    // Itens de menu (seção "Negócio" da sidebar).
    'menu' => [
        [
            'key' => 'rh-funcionarios',
            'label' => 'Funcionarios',
            'icon' => 'tabler--folder',
            'route' => 'admin.rh.funcionarios.index',
            'permission' => 'rh.funcionarios.listar',
            'active' => ['admin.rh.funcionarios.*'],
        ],
        [
            'key' => 'rh-departamentos',
            'label' => 'Departamentos',
            'icon' => 'tabler--folder',
            'route' => 'admin.rh.departamentos.index',
            'permission' => 'rh.departamentos.listar',
            'active' => ['admin.rh.departamentos.*'],
        ],
        // make:modulo insere os itens de menu do módulo acima desta linha
    ],
];
