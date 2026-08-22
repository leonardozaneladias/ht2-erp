<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Extensões
|--------------------------------------------------------------------------
|
| Convenções de marca usadas pelo gerador `make:extensao` e pelo modo pacote
| do `make:modulo` (ver ADR-0015, que usa o vocabulário anterior).
|
| Vocabulário atual (ver CONTEXT-MAP.md): **módulo** vive dentro do produto;
| **extensão** é a unidade de negócio distribuída como pacote, instalável em
| qualquer produto; **pacote** é a forma de distribuição.
|
| Exemplo (vendor=ht2ml, namespace=HT2ML, prefixo=extensao-):
|   extensão "Rh"  ->  pacote ht2ml/extensao-rh  ·  namespace HT2ML\Rh\
|                  ->  packages/extensao-rh  ·  views "rh::"
|                  ->  repo GitHub do release: {org}/erp-module-rh
|
*/

return [
    // Vendor Composer das extensões.
    'vendor' => 'ht2ml',

    // Namespace PHP base das extensões (sem barra final).
    'namespace' => 'HT2ML',

    // Conta/org no GitHub (usada nos runbooks de distribuição e pelo bin/release-module.sh).
    // Hoje é a conta pessoal; o repo de cada módulo é {org}/erp-module-{slug} (ex.:
    // leonardozaneladias/erp-module-rh). Migra para a org ht2-erp depois, via transfer.
    'org' => 'leonardozaneladias',

    // Diretório onde as extensões em desenvolvimento vivem (path repository).
    'path' => 'packages',

    // Prefixo do nome do pacote Composer: {vendor}/{prefixo}{slug}.
    'prefixo_pacote' => 'extensao-',

    /*
    | Módulo "Exemplo (demo)" — referência viva do gerador make:modulo. Mantenha
    | true no boilerplate; num projeto-cliente, defina EXEMPLO_DEMO=false para
    | ocultar o item de menu e suas permissões (a tela segue em /admin/exemplos
    | como referência de código). Gateado direto por env() nos config/ de menu e
    | acesso para não depender da ordem de carregamento dos arquivos de config.
    */
    'exemplo_demo' => env('EXEMPLO_DEMO', true),
];
