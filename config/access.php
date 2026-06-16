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
                'descricao' => 'Mover empresas para a lixeira.',
            ],
            'empresas.restaurar' => [
                'label' => 'Restaurar empresas',
                'descricao' => 'Restaurar empresas (e filiais) da lixeira.',
            ],
            'empresas.excluir_permanente' => [
                'label' => 'Excluir empresas permanentemente',
                'descricao' => 'Remover empresas definitivamente (cascateia para filiais e vínculos).',
            ],
            'empresas.acessos' => [
                'label' => 'Gerenciar acesso a empresas',
                'descricao' => 'Definir quais empresas e filiais cada usuário acessa.',
            ],
            'listagens.multi_empresa' => [
                'label' => 'Filtro multi-empresa nas listagens',
                'descricao' => 'Incluir registros de outras empresas/filiais acessíveis nas listagens (além da empresa ativa).',
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
                'descricao' => 'Mover usuários para a lixeira.',
            ],
            'usuarios.restaurar' => [
                'label' => 'Restaurar usuários',
                'descricao' => 'Restaurar usuários da lixeira.',
            ],
            'usuarios.excluir_permanente' => [
                'label' => 'Excluir usuários permanentemente',
                'descricao' => 'Remover usuários definitivamente do banco (irreversível).',
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
            // Permissões do Exemplo (demo) — opcionais (ver EXEMPLO_DEMO em config/modulos.php).
            ...(env('EXEMPLO_DEMO', true) ? [
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
                    'descricao' => 'Mover exemplos para a lixeira.',
                ],
                'exemplos.restaurar' => [
                    'label' => 'Restaurar exemplos',
                    'descricao' => 'Restaurar exemplos da lixeira.',
                ],
                'exemplos.excluir_permanente' => [
                    'label' => 'Excluir exemplos permanentemente',
                    'descricao' => 'Remover exemplos definitivamente do banco (irreversível).',
                ],
            ] : []),
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

        /*
        | Tabelas Auxiliares — catálogos de dados de referência (localização,
        | financeiro, fiscal, pessoas). Majoritariamente oficiais (IBGE/BrasilAPI),
        | mantidos via `referencia:sync`; o CRUD permite ajustes pontuais.
        */
        ModuloAcesso::TabelasAuxiliares->value => [
            'estados.listar' => [
                'label' => 'Listar estados',
                'descricao' => 'Ver o catálogo de estados (UF).',
            ],
            'estados.criar' => [
                'label' => 'Criar estados',
                'descricao' => 'Cadastrar novos estados (UF).',
            ],
            'estados.editar' => [
                'label' => 'Editar estados',
                'descricao' => 'Alterar dados de estados (UF).',
            ],
            'estados.deletar' => [
                'label' => 'Excluir estados',
                'descricao' => 'Mover estados para a lixeira.',
            ],
            'estados.restaurar' => [
                'label' => 'Restaurar estados',
                'descricao' => 'Restaurar estados da lixeira.',
            ],
            'estados.excluir_permanente' => [
                'label' => 'Excluir estados permanentemente',
                'descricao' => 'Remover estados definitivamente do banco (irreversível).',
            ],

            'paises.listar' => ['label' => 'Listar países', 'descricao' => 'Ver o catálogo de países.'],
            'paises.criar' => ['label' => 'Criar países', 'descricao' => 'Cadastrar novos países.'],
            'paises.editar' => ['label' => 'Editar países', 'descricao' => 'Alterar dados de países.'],
            'paises.deletar' => ['label' => 'Excluir países', 'descricao' => 'Mover países para a lixeira.'],
            'paises.restaurar' => ['label' => 'Restaurar países', 'descricao' => 'Restaurar países da lixeira.'],
            'paises.excluir_permanente' => ['label' => 'Excluir países permanentemente', 'descricao' => 'Remover países definitivamente (irreversível).'],

            'municipios.listar' => ['label' => 'Listar municípios', 'descricao' => 'Ver o catálogo de municípios.'],
            'municipios.criar' => ['label' => 'Criar municípios', 'descricao' => 'Cadastrar novos municípios.'],
            'municipios.editar' => ['label' => 'Editar municípios', 'descricao' => 'Alterar dados de municípios.'],
            'municipios.deletar' => ['label' => 'Excluir municípios', 'descricao' => 'Mover municípios para a lixeira.'],
            'municipios.restaurar' => ['label' => 'Restaurar municípios', 'descricao' => 'Restaurar municípios da lixeira.'],
            'municipios.excluir_permanente' => ['label' => 'Excluir municípios permanentemente', 'descricao' => 'Remover municípios definitivamente (irreversível).'],

            'moedas.listar' => ['label' => 'Listar moedas', 'descricao' => 'Ver o catálogo de moedas.'],
            'moedas.criar' => ['label' => 'Criar moedas', 'descricao' => 'Cadastrar novas moedas.'],
            'moedas.editar' => ['label' => 'Editar moedas', 'descricao' => 'Alterar dados de moedas.'],
            'moedas.deletar' => ['label' => 'Excluir moedas', 'descricao' => 'Mover moedas para a lixeira.'],
            'moedas.restaurar' => ['label' => 'Restaurar moedas', 'descricao' => 'Restaurar moedas da lixeira.'],
            'moedas.excluir_permanente' => ['label' => 'Excluir moedas permanentemente', 'descricao' => 'Remover moedas definitivamente (irreversível).'],

            'bancos.listar' => ['label' => 'Listar bancos', 'descricao' => 'Ver o catálogo de bancos.'],
            'bancos.criar' => ['label' => 'Criar bancos', 'descricao' => 'Cadastrar novos bancos.'],
            'bancos.editar' => ['label' => 'Editar bancos', 'descricao' => 'Alterar dados de bancos.'],
            'bancos.deletar' => ['label' => 'Excluir bancos', 'descricao' => 'Mover bancos para a lixeira.'],
            'bancos.restaurar' => ['label' => 'Restaurar bancos', 'descricao' => 'Restaurar bancos da lixeira.'],
            'bancos.excluir_permanente' => ['label' => 'Excluir bancos permanentemente', 'descricao' => 'Remover bancos definitivamente (irreversível).'],

            'cargos.listar' => ['label' => 'Listar cargos', 'descricao' => 'Ver o catálogo de cargos (CBO).'],
            'cargos.criar' => ['label' => 'Criar cargos', 'descricao' => 'Cadastrar novos cargos.'],
            'cargos.editar' => ['label' => 'Editar cargos', 'descricao' => 'Alterar dados de cargos.'],
            'cargos.deletar' => ['label' => 'Excluir cargos', 'descricao' => 'Mover cargos para a lixeira.'],
            'cargos.restaurar' => ['label' => 'Restaurar cargos', 'descricao' => 'Restaurar cargos da lixeira.'],
            'cargos.excluir_permanente' => ['label' => 'Excluir cargos permanentemente', 'descricao' => 'Remover cargos definitivamente (irreversível).'],

            'tipos_logradouro.listar' => ['label' => 'Listar tipos de logradouro', 'descricao' => 'Ver o catálogo de tipos de logradouro.'],
            'tipos_logradouro.criar' => ['label' => 'Criar tipos de logradouro', 'descricao' => 'Cadastrar novos tipos de logradouro.'],
            'tipos_logradouro.editar' => ['label' => 'Editar tipos de logradouro', 'descricao' => 'Alterar dados de tipos de logradouro.'],
            'tipos_logradouro.deletar' => ['label' => 'Excluir tipos de logradouro', 'descricao' => 'Mover tipos de logradouro para a lixeira.'],
            'tipos_logradouro.restaurar' => ['label' => 'Restaurar tipos de logradouro', 'descricao' => 'Restaurar tipos de logradouro da lixeira.'],
            'tipos_logradouro.excluir_permanente' => ['label' => 'Excluir tipos de logradouro permanentemente', 'descricao' => 'Remover tipos de logradouro definitivamente (irreversível).'],

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
            // catálogos de referência adicionais entram acima desta linha
        ],

    ],
];
