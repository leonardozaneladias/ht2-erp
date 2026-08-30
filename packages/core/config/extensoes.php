<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Extensões
|--------------------------------------------------------------------------
|
| Convenções de marca usadas pelo `make:modulo` (que cria a casca) e pelo
| `make:recurso --modulo=` (que gera o CRUD dentro dela).
|
| Vocabulário atual (ADR-0021 e CONTEXT-MAP.md): **módulo** é a área de negócio,
| identificada por uma chave kebab; **extensão** é o envelope — o pacote que
| carrega um módulo (extensão-módulo) ou só código sem UI (extensão-biblioteca);
| **pacote** é a forma de distribuição. O prefixo do nome do pacote continua
| `extensao-` porque os cinco já publicados usam esse nome: o envelope não
| mudou, o vocabulário mudou.
|
| Exemplo (vendor=ht2ml, namespace=HT2ML, prefixo=extensao-):
|   módulo "rh"  ->  pacote ht2ml/extensao-rh  ·  namespace HT2ML\Rh\
|                ->  packages/extensao-rh  ·  views "rh::"
|
*/

return [
    // Vendor Composer das extensões.
    'vendor' => 'ht2ml',

    // Namespace PHP base das extensões (sem barra final).
    'namespace' => 'HT2ML',

    // Conta/org no GitHub (usada nos runbooks de distribuição e pelo bin/release-module.sh).
    // O nome do repo de cada pacote é DERIVADO do composer.json dele — ht2ml/core vira
    // {org}/ht2ml-core — e não de convenção escrita aqui: a anterior (erp-module-{slug})
    // ficou obsoleta sem ninguém notar. Migra para a org ht2-erp depois, via transfer.
    'org' => 'leonardozaneladias',

    // Diretório onde as extensões em desenvolvimento vivem (path repository).
    'path' => 'packages',

    /*
    | Idioma da inflexão usada PELOS GERADORES para derivar singular e plural do
    | nome de um recurso (classe, tabela, rota, namespace de view).
    |
    | O default do Laravel é inglês, e num domínio em português ele erra feio:
    | `Materia` vira a classe `Materium` e a tabela `materia`; `Nota` vira
    | `Notum`; `Frequencia` vira `Frequencium`; `Responsavel` pluraliza como
    | `Responsavels` e `Avaliacao` como `Avaliacaos`. Medido em doze nomes de um
    | domínio escolar: CINCO saíam errados. Renomear depois é migração de dados.
    |
    | Vale só durante a geração — o Eloquent continua resolvendo tabela como
    | sempre resolveu, porque os models gerados declaram `$table` explicitamente
    | e um produto já instalado não pode mudar de nome de tabela por causa disto.
    |
    | null mantém o inglês. Valores aceitos pelo Illuminate\Support\Pluralizer:
    | english, french, norwegian-bokmal, portuguese, spanish, turkish.
    */
    'idioma_inflexao' => null,

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
