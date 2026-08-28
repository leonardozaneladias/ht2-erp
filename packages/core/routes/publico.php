<?php

declare(strict_types=1);

use HT2ML\Core\Support\Modules\EscopoDeRota;
use HT2ML\Core\Support\Modules\ModuleRegistry;

/*
|--------------------------------------------------------------------------
| Rotas públicas contribuídas por módulos
|--------------------------------------------------------------------------
|
| Stack `web` completa — sessão, cookies, CSRF — sem autenticação e sem prefixo.
| É onde entra a página que alguém abre sem estar logado: matrícula, consulta,
| portal do responsável.
|
| Sem este canal, um módulo que precise de uma página pública teria de editar o
| routes/web.php do produto — a dependência de mão dupla que o ADR-0022 proíbe.
|
| O core não declara nada aqui: o arquivo existe para hospedar contribuições.
|
*/

foreach (ModuleRegistry::routeCallbacks(EscopoDeRota::Publico) as $registrarRotasDoModulo) {
    $registrarRotasDoModulo();
}
