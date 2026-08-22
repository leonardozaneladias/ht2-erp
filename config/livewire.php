<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Livewire — overrides do app (Onda 8 G1)
|--------------------------------------------------------------------------
|
| O merge do pacote é raso (mergeConfigFrom): definir uma chave de primeiro
| nível substitui o bloco inteiro — por isso o bloco abaixo replica os
| defaults do vendor e altera apenas `rules`.
|
| `rules`: sem o override, o teto EFETIVO do upload temporário é o default de
| 12MB do Livewire — o `max:20480` validado nos componentes (documentos de
| funcionário, importação) nunca seria alcançável acima de 12MB.
|
*/

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', 'max:20480'], // 20MB, alinhado às validações das telas
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
