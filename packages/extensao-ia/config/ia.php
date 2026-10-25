<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ht2ml/extensao-ia
|--------------------------------------------------------------------------
|
| `vendor:publish --tag=ia-config`. Os provedores reais entram trocando o
| binding das interfaces no AppServiceProvider do produto; nada aqui escolhe
| provedor.
|
*/

return [

    /*
    | Teto do texto ENVIADO a um provedor, em caracteres. Corta o que vai para
    | o terceiro, não o que é gravado: uma única chamada podia mandar
    | megabytes a um provedor cobrado por token.
    */
    'limite_caracteres' => 20000,

    /*
    | Limites por minuto, por usuário (ou IP). Dois, e não um, porque o custo
    | é diferente: a tradução tem cache por hash, então repetir é grátis; o
    | resumo e o assistente geram de novo a cada pedido.
    */
    'limites' => [
        'ia' => 10,
        'traducao' => 20,
    ],

];
