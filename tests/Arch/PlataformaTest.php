<?php

declare(strict_types=1);

/*
 * Regras de arquitetura da plataforma.
 *
 * Este arquivo era tests/Arch.php e NUNCA RODOU: nenhum testsuite do
 * phpunit.xml cobria tests/ na raiz, nenhum ->in() do tests/Pest.php o
 * alcançava, e o CI invocava --testsuite=Unit,Feature,Extensoes. Três omissões
 * independentes, todas silenciosas.
 *
 * As regras também miravam App\Services, App\Actions e App\Models, que a
 * extração da plataforma esvaziou: app/ tem 4 arquivos, e o único model que
 * sobrou estava na própria lista de ignoring(). Cinco regras avaliando nada.
 *
 * ATENÇÃO ao escrever regra nova: `expect('HT2ML')` NÃO funciona. O
 * Pest\Arch\Support\Composer resolve o alvo contra os prefixos PSR-4
 * registrados — que são 'HT2ML\Core', 'HT2ML\Rh', etc., nunca 'HT2ML'. Um
 * prefixo que não casa devolve conjunto vazio e a regra passa sem testar nada.
 * Por isso a lista abaixo é derivada dos composer.json, e por isso existe o
 * tests/Unit/Arquitetura/RegrasDeArquiteturaTest.php, que prova que as regras
 * mordem.
 */

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\LoginHistory;
use HT2ML\Documentos\Models\DocumentSequence;
use Illuminate\Database\Eloquent\Model;

/** @var list<string> Namespaces raiz de todo pacote da plataforma, sem allowlist: extensão nova entra sozinha. */
$plataforma = namespacesDosPacotes();

arch('todo o código da plataforma tem declare(strict_types=1)')
    ->expect($plataforma)
    ->toUseStrictTypes();

arch('o app do monorepo tem declare(strict_types=1)')
    ->expect('App')
    ->toUseStrictTypes();

// Services e Actions são a camada de domínio: não conhecem HTTP. Quem traduz
// resultado em resposta é o componente Livewire ou o controller.
arch('services não retornam respostas HTTP')
    ->expect('HT2ML\Core\Services')
    ->not->toUse([
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);

arch('actions não retornam respostas HTTP')
    ->expect('HT2ML\Core\Actions')
    ->not->toUse([
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);

// Auditoria automática obrigatória: todo model novo usa o trait Auditavel
// (captura created/updated/deleted no activity_log) — ou entra na whitelist
// abaixo COM justificativa. Ver docs/devops/conventions.md §7.2.
arch('models usam Auditavel ou estão na whitelist consciente')
    ->expect($plataforma)
    ->classes()
    ->extending(Model::class)
    ->toUseTrait(Auditavel::class)
    ->ignoring([
        Activity::class,          // o log em si (append-only)
        LoginHistory::class,      // histórico append-only (já é trilha)
        DocumentSequence::class,  // contador técnico de infra, não entidade de negócio
    ]);

// Models de Referência são catálogos globais, não dados de cliente: aplicar
// tenancy neles esconderia linhas que todo mundo deve enxergar.
// (Estava em tests/Feature/Referencia/ReferenciaInfraTest.php mirando
// App\Models\Referencia, namespace que deixou de existir na extração.)
arch('models de Referência não são tenant-scoped')
    ->expect('HT2ML\Core\Models\Referencia')
    ->not->toUse('HT2ML\Core\Models\Concerns\BelongsToEmpresa');
