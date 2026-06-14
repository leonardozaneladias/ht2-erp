<?php

declare(strict_types=1);

use App\Enums\ModuloAcesso;

return [

    /*
    |--------------------------------------------------------------------------
    | Role de super-admin
    |--------------------------------------------------------------------------
    | Role com acesso irrestrito (bypass no Gate). É imune a deny direto e à
    | restrição de perfil ativo, e está sempre no topo da hierarquia.
    */

    'super_admin_role' => 'super-admin',

    /*
    |--------------------------------------------------------------------------
    | Roles protegidas
    |--------------------------------------------------------------------------
    | Não podem ser renomeadas, excluídas, nem ter as permissões alteradas
    | pela interface de gestão.
    */

    'protected_roles' => ['super-admin'],

    /*
    |--------------------------------------------------------------------------
    | Permissões de auto-gestão (anti-lockout)
    |--------------------------------------------------------------------------
    | Um usuário não pode remover de si mesmo estas permissões, para não se
    | trancar para fora do módulo de controle de acesso.
    */

    'self_management_permissions' => [
        'acessos.gerenciar',
        'permissoes.gerenciar',
    ],

    /*
    |--------------------------------------------------------------------------
    | Níveis hierárquicos das roles (semente)
    |--------------------------------------------------------------------------
    | Maior número = mais poder. Um usuário só gere usuários/perfis/acessos de
    | nível estritamente abaixo do seu. Estes valores são aplicados pelo seeder;
    | a interface permite ajustar o nível de cada perfil posteriormente.
    */

    'role_levels' => [
        'super-admin' => 100,
        'gestor' => 50,
    ],

    'default_role_level' => 10,

    /*
    |--------------------------------------------------------------------------
    | Cache da resolução de acesso
    |--------------------------------------------------------------------------
    | TTL curto (segundos) para refletir expirações rapidamente. O store nulo
    | usa o driver padrão de cache (config/cache.php).
    */

    'cache_ttl' => 300,
    'cache_store' => env('ACCESS_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Catálogo de permissões (fonte de verdade)
    |--------------------------------------------------------------------------
    | Estrutura: módulo (valor de ModuloAcesso) => [ permissão => [label, descricao] ].
    | Sincronizado para a tabela `permissions` via `php artisan access:sync`.
    | A interface (matriz, simulador) consome os metadados já gravados na tabela.
    */

    'modules' => [

        ModuloAcesso::Dashboard->value => [
            'dashboard.view' => [
                'label' => 'Visualizar dashboard',
                'descricao' => 'Acessar o painel inicial e seus indicadores.',
            ],
        ],

        ModuloAcesso::Empresas->value => [
            'empresas.listar' => [
                'label' => 'Listar empresas',
                'descricao' => 'Ver a listagem de empresas e filiais.',
            ],
            'empresas.criar' => [
                'label' => 'Criar empresas',
                'descricao' => 'Cadastrar novas empresas e filiais.',
            ],
            'empresas.editar' => [
                'label' => 'Editar empresas',
                'descricao' => 'Alterar dados, branding, filiais e status de empresas.',
            ],
            'empresas.deletar' => [
                'label' => 'Excluir empresas',
                'descricao' => 'Remover empresas e suas filiais.',
            ],
            'empresas.acessos' => [
                'label' => 'Gerenciar acesso a empresas',
                'descricao' => 'Definir quais empresas e filiais cada usuário acessa.',
            ],
        ],

        ModuloAcesso::Usuarios->value => [
            'usuarios.listar' => [
                'label' => 'Listar usuários',
                'descricao' => 'Ver a listagem de usuários administrativos.',
            ],
            'usuarios.criar' => [
                'label' => 'Criar usuários',
                'descricao' => 'Cadastrar novos usuários administrativos.',
            ],
            'usuarios.editar' => [
                'label' => 'Editar usuários',
                'descricao' => 'Alterar dados e status de usuários.',
            ],
            'usuarios.deletar' => [
                'label' => 'Excluir usuários',
                'descricao' => 'Remover usuários administrativos.',
            ],
            'usuarios.impersonar' => [
                'label' => 'Personificar usuários',
                'descricao' => 'Entrar como outro usuário (act-as) para suporte e diagnóstico.',
            ],
            'usuarios.exportar-dados' => [
                'label' => 'Exportar dados (LGPD)',
                'descricao' => 'Exportar os dados pessoais de um usuário (direito de acesso/portabilidade).',
            ],
            'usuarios.anonimizar' => [
                'label' => 'Anonimizar usuário (LGPD)',
                'descricao' => 'Anonimizar irreversivelmente a PII de um usuário (direito ao esquecimento).',
            ],
        ],

        ModuloAcesso::Perfis->value => [
            'perfis.listar' => [
                'label' => 'Listar perfis',
                'descricao' => 'Ver a listagem de perfis (papéis).',
            ],
            'perfis.gerenciar' => [
                'label' => 'Gerenciar perfis',
                'descricao' => 'Criar, editar e definir as permissões de perfis.',
            ],
        ],

        ModuloAcesso::Acessos->value => [
            'acessos.gerenciar' => [
                'label' => 'Acessar controle de acesso',
                'descricao' => 'Entrar no módulo de governança de acesso.',
            ],
            'acessos.conceder' => [
                'label' => 'Conceder acessos diretos',
                'descricao' => 'Conceder permissões diretas (temporárias) a usuários.',
            ],
            'acessos.revogar' => [
                'label' => 'Revogar ou negar acessos diretos',
                'descricao' => 'Revogar ou negar permissões diretas de usuários.',
            ],
            'acessos.matriz' => [
                'label' => 'Editar matriz de permissões',
                'descricao' => 'Configurar a grade de perfis × permissões.',
            ],
            'acessos.simular' => [
                'label' => 'Usar simulador de acesso',
                'descricao' => 'Consultar e explicar o acesso efetivo de um usuário.',
            ],
            'acessos.historico' => [
                'label' => 'Ver histórico de acesso',
                'descricao' => 'Consultar a trilha de mudanças de acesso.',
            ],
            'permissoes.gerenciar' => [
                'label' => 'Administrar catálogo de permissões',
                'descricao' => 'Gerenciar o catálogo de permissões do sistema.',
            ],
        ],

        ModuloAcesso::Auditoria->value => [
            'auditoria.visualizar' => [
                'label' => 'Visualizar auditoria',
                'descricao' => 'Consultar os logs de auditoria do sistema.',
            ],
            'auditoria.todas-empresas' => [
                'label' => 'Ver auditoria de todas as empresas',
                'descricao' => 'Consultar a auditoria sem isolamento por empresa (visão cross-empresa).',
            ],
        ],

        ModuloAcesso::Configuracoes->value => [
            'configuracoes.editar' => [
                'label' => 'Editar configurações',
                'descricao' => 'Alterar as configurações gerais do sistema.',
            ],
            'configuracoes.menus' => [
                'label' => 'Gerenciar menus',
                'descricao' => 'Personalizar ordem, rótulos, ícones e visibilidade do menu lateral.',
            ],
        ],

        ModuloAcesso::Notificacoes->value => [
            'notificacoes.enviar' => [
                'label' => 'Enviar comunicados',
                'descricao' => 'Compor e enviar comunicados in-app aos usuários.',
            ],
        ],

        /*
        | Módulos de negócio gerados via `php artisan make:modulo`. Cada módulo
        | declara aqui suas permissões (listar/criar/editar/deletar). Para dar a
        | um módulo a própria seção na matriz, crie um case em ModuloAcesso e
        | mova suas permissões para a chave correspondente.
        */
        ModuloAcesso::Negocio->value => [
            'produtos.listar' => [
                'label' => 'Listar produtos',
                'descricao' => 'Ver a listagem de produtos.',
            ],
            'produtos.criar' => [
                'label' => 'Criar produtos',
                'descricao' => 'Cadastrar novos registros de produto.',
            ],
            'produtos.editar' => [
                'label' => 'Editar produtos',
                'descricao' => 'Alterar dados e status de produtos.',
            ],
            'produtos.deletar' => [
                'label' => 'Excluir produtos',
                'descricao' => 'Remover produtos.',
            ],
            'exemplos.listar' => [
                'label' => 'Listar exemplos',
                'descricao' => 'Ver a listagem de exemplos.',
            ],
            'exemplos.criar' => [
                'label' => 'Criar exemplos',
                'descricao' => 'Cadastrar novos registros de exemplo.',
            ],
            'exemplos.editar' => [
                'label' => 'Editar exemplos',
                'descricao' => 'Alterar dados e status de exemplos.',
            ],
            'exemplos.deletar' => [
                'label' => 'Excluir exemplos',
                'descricao' => 'Remover exemplos.',
            ],
            // make:modulo insere permissões de negócio acima desta linha
        ],

        ModuloAcesso::Sistema->value => [
            'sistema.horizon' => [
                'label' => 'Acessar Horizon',
                'descricao' => 'Ver o dashboard de filas (Laravel Horizon).',
            ],
            'sistema.pulse' => [
                'label' => 'Acessar Pulse',
                'descricao' => 'Ver o monitoramento de performance (Laravel Pulse).',
            ],
        ],

    ],
];
