<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

/*
|--------------------------------------------------------------------------
| Cada pacote declara o que usa
|--------------------------------------------------------------------------
|
| A convenção não dava para escrever honestamente em Pest: "esta classe vem de
| um pacote que este composer.json declara?" exige o mapa classe -> pacote que
| o Composer monta, e reimplementá-lo em teste seria reimplementar o Composer.
|
| O que a ferramenta pegou na primeira execução, e nenhum teste pegaria:
|
|   ezyang/htmlpurifier   ninguém declarava. Chegava só porque maatwebsite/excel
|                         arrasta phpoffice/phpspreadsheet, que o exige. E quem
|                         o usa é o HtmlSanitizer do core — defesa contra XSS.
|                         Uma troca de dependência do Excel derrubaria a
|                         sanitização, e o sintoma seria um fatal em produção.
|   ht2ml/extensao-rh     declarava só php e ht2ml/core, usando Livewire e
|                         PowerGrid direto. Funcionava porque o core os arrasta;
|                         no dia em que o core largar o PowerGrid, o RH quebra.
|   ext-intl              NumberFormatter no Money.php. Num host sem intl, fatal
|                         na primeira formatação de dinheiro.
|
| RODAR: por pacote, com o vendor da raiz visível.
|
|   ln -sfn ../../vendor packages/core/vendor
|   vendor/bin/composer-dependency-analyser --composer-json=packages/core/composer.json
|   rm -f packages/core/vendor
|
| O symlink existe porque a ferramenta procura o autoload ao lado do
| composer.json que analisa, e num monorepo com path repos os pacotes não têm
| vendor próprio. Instalar cinco vendors só para analisar custaria minutos de CI
| para responder a mesma pergunta.
|
*/

$config = new Configuration;

// Um config serve os cinco pacotes, então a maioria dos ignores não se aplica em
// cada execução — e a ferramenta reporta cada ignore não usado. Sem isto, a
// saída de um pacote limpo tem vinte linhas de ruído e o achado real se perde
// no meio.
$config->disableReportingUnmatchedIgnores();

// --- Artefato 1: o pacote referencia as próprias classes. -------------------
// Óbvio para um humano, invisível para a ferramenta: ela vê HT2ML\Core\... e
// pergunta por que ht2ml/core não está no require de ht2ml/core.
//
// Por pacote **E CAMINHO**, nunca por pacote só. A primeira versão deste arquivo
// ignorava shadow-dependency dos cinco globalmente — e com isso apagava a
// checagem entre pacotes: uma extensão usando classe de OUTRA extensão sem
// declarar passaria batido. Medido no repositório do EduConecta, que copiou este
// arranjo: um `use` de outro módulo saiu com EXIT=0 e nenhuma linha de saída.
// Um guard vazio é pior que nenhum, porque parece cobertura.
//
// O guard A1 cobre core -> extensão; este cobre extensão -> extensão, que
// nenhum outro cobria.
foreach ([
    'ht2ml/core' => 'packages/core',
    'ht2ml/extensao-rh' => 'packages/extensao-rh',
    'ht2ml/extensao-fiscal-br' => 'packages/extensao-fiscal-br',
    'ht2ml/extensao-exemplo-demo' => 'packages/extensao-exemplo-demo',
    'ht2ml/extensao-documentos' => 'packages/extensao-documentos',
] as $pacote => $caminho) {
    $config->ignoreErrorsOnPackageAndPath(
        $pacote,
        __DIR__ . '/' . $caminho,
        [ErrorType::SHADOW_DEPENDENCY, ErrorType::DEV_DEPENDENCY_IN_PROD],
    );
}

// --- Artefato 2: monólito instalado, splits declarados. ---------------------
// O vendor da raiz tem laravel/framework, não os illuminate/* separados. Então
// toda classe Illuminate\ resolve para laravel/framework (shadow) e os splits
// que os pacotes declaram parecem não usados.
//
// Quem está certo são os pacotes: uma biblioteca declara os componentes que usa,
// não o framework inteiro — é o que permite instalá-la sem arrastar o mundo.
// Ignorar aqui é reconhecer que a pergunta não pode ser respondida com um vendor
// que tem só o monólito, não é abrir exceção para o hábito.
$config->ignoreErrorsOnPackage('laravel/framework', [
    ErrorType::SHADOW_DEPENDENCY,
    ErrorType::DEV_DEPENDENCY_IN_PROD,
]);

$config->ignoreErrorsOnPackages([
    'illuminate/auth',
    'illuminate/bus',
    'illuminate/console',
    'illuminate/contracts',
    'illuminate/database',
    'illuminate/http',
    'illuminate/notifications',
    'illuminate/queue',
    'illuminate/support',
    'illuminate/validation',
    'illuminate/view',
], [ErrorType::UNUSED_DEPENDENCY]);

// --- Artefato 3: os testes dos pacotes rodam pela suíte da raiz. ------------
// packages/*/tests é coletado pelo Pest do monorepo (tests/Pest.php), então os
// pacotes não declaram pest em require-dev. Declarar exigiria instalar o Pest
// cinco vezes para rodar a mesma suíte uma.
$config->ignoreErrorsOnPackages([
    'pestphp/pest',
    'pestphp/pest-plugin-arch',
], [ErrorType::SHADOW_DEPENDENCY, ErrorType::DEV_DEPENDENCY_IN_PROD]);

// --- Real, mas fora do alcance de um pacote: -------------------------------
// pragmarx/google2fa-laravel entra pelo ServiceProvider e pela config, não por
// referência de classe. É uso por integração, que nenhuma análise estática vê.
$config->ignoreErrorsOnPackage('pragmarx/google2fa-laravel', [ErrorType::UNUSED_DEPENDENCY]);

return $config;
