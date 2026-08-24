<?php

declare(strict_types=1);

use HT2ML\Core\Enums\ModuloAcesso;

/*
| O que esta extensão contribui ao core. Antes vivia em config/access.php e
| config/admin-menu.php do NÚCLEO, atrás de um env('EXEMPLO_DEMO') — ou seja: o
| pacote da plataforma descrevia um módulo que não era dele. Mesma inversão que
| as rotas do demo tinham, e que a instalação num Laravel limpo denunciou.
|
| Agora quem não quer o demo não o instala. É o caminho inverso que o plano
| pedia deste pacote provar: produto REMOVE a extensão.
*/
return [
    'modulo_acesso' => ModuloAcesso::Negocio->value,
    'secao_menu' => 'negocio',

    'permissoes' => [
        'exemplos.listar' => ['label' => 'Listar exemplos', 'descricao' => 'Ver a listagem de exemplos.'],
        'exemplos.criar' => ['label' => 'Criar exemplos', 'descricao' => 'Cadastrar novos registros de exemplo.'],
        'exemplos.editar' => ['label' => 'Editar exemplos', 'descricao' => 'Alterar dados e status de exemplos.'],
        'exemplos.deletar' => ['label' => 'Excluir exemplos', 'descricao' => 'Mover exemplos para a lixeira.'],
        'exemplos.restaurar' => ['label' => 'Restaurar exemplos', 'descricao' => 'Restaurar exemplos da lixeira.'],
        'exemplos.excluir_permanente' => [
            'label' => 'Excluir exemplos permanentemente',
            'descricao' => 'Remover exemplos definitivamente do banco (irreversível).',
        ],
    ],

    'menu' => [
        [
            'key' => 'exemplos',
            'label' => 'Exemplo (demo)',
            'icon' => 'tabler--components',
            'route' => 'admin.exemplos.index',
            'permission' => 'exemplos.listar',
            'active' => ['admin.exemplos.*'],
        ],
    ],
];
