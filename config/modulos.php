<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Módulos-pacote (HT2 ERP)
|--------------------------------------------------------------------------
|
| Convenções de marca usadas pelo gerador `make:modulo-pacote` e pelo modo
| pacote do `make:modulo` (ver ADR-0015). HT2 ERP é o produto/base; cada
| módulo de negócio reutilizável é um pacote Composer próprio.
|
| Exemplo (vendor=ht2erp, namespace=HT2ERP, prefixo=modulo-):
|   módulo "Rh"  ->  pacote ht2erp/modulo-rh  ·  namespace HT2ERP\Rh\
|                ->  packages/modulo-rh  ·  views "rh::"
|                ->  repo GitHub do release: {org}/erp-module-rh
|
*/

return [
    // Vendor Composer dos pacotes de módulo.
    'vendor' => 'ht2erp',

    // Namespace PHP base dos pacotes (sem barra final).
    'namespace' => 'HT2ERP',

    // Conta/org no GitHub (usada nos runbooks de distribuição e pelo bin/release-module.sh).
    // Hoje é a conta pessoal; o repo de cada módulo é {org}/erp-module-{slug} (ex.:
    // leonardozaneladias/erp-module-rh). Migra para a org ht2-erp depois, via transfer.
    'org' => 'leonardozaneladias',

    // Diretório onde os pacotes em desenvolvimento vivem (path repository).
    'path' => 'packages',

    // Prefixo do nome do pacote Composer: {vendor}/{prefixo}{slug}.
    'prefixo_pacote' => 'modulo-',

    /*
    | Módulo "Exemplo (demo)" — referência viva do gerador make:modulo. Mantenha
    | true no boilerplate; num projeto-cliente, defina EXEMPLO_DEMO=false para
    | ocultar o item de menu e suas permissões (a tela segue em /admin/exemplos
    | como referência de código). Gateado direto por env() nos config/ de menu e
    | acesso para não depender da ordem de carregamento dos arquivos de config.
    */
    'exemplo_demo' => env('EXEMPLO_DEMO', true),
];
