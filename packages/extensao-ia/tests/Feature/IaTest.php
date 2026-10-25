<?php

declare(strict_types=1);

use HT2ML\Ia\Contracts\AssistenteInterface;
use HT2ML\Ia\Contracts\ResumidorInterface;
use HT2ML\Ia\Contracts\TradutorInterface;
use HT2ML\Ia\Fakes\FakeTradutor;
use HT2ML\Ia\Models\TraducaoEmCache;
use HT2ML\Ia\Support\IdiomaDaRequisicao;
use HT2ML\Ia\Support\TextoParaIa;
use HT2ML\Ia\TraducaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/** Tradutor que conta chamadas e declara um nome próprio, para provar cache e proveniência. */
function tradutorEspiao(): TradutorInterface
{
    return new class implements TradutorInterface
    {
        public int $chamadas = 0;

        /** @var list<string> */
        public array $recebidos = [];

        public function traduzir(string $texto, string $idiomaOrigem, string $idiomaDestino): array
        {
            $this->chamadas++;
            $this->recebidos[] = $texto;

            return ['texto' => "traduzido:{$idiomaDestino}"];
        }

        public function identificador(): string
        {
            return 'espiao-v1';
        }
    };
}

it('liga os fakes por padrão, e o produto troca o binding sem tocar no resto', function (): void {
    expect(app(TradutorInterface::class))->toBeInstanceOf(FakeTradutor::class)
        ->and(app(ResumidorInterface::class)->resumir('Título', 'Corpo', 'es')['texto'])->toContain('[resumo:es] Título')
        ->and(app(AssistenteInterface::class)->responder('Oi?', ['pedido' => 7], 'en')['resposta'])->toBe('[assistente/en sobre pedido] Oi?');

    app()->instance(TradutorInterface::class, $espiao = tradutorEspiao());

    expect(app(TraducaoService::class)->traduzir('Olá', 'en')['texto'])->toBe('traduzido:en')
        ->and($espiao->chamadas)->toBe(1);
});

it('o mesmo texto não é traduzido duas vezes para o mesmo idioma, nem entre empresas', function (): void {
    $servico = new TraducaoService($espiao = tradutorEspiao());

    expect($servico->traduzir('Reunião amanhã', 'en', empresaId: 1))->toBe(['texto' => 'traduzido:en', 'cache' => false])
        ->and($servico->traduzir('Reunião amanhã', 'en', empresaId: 2))->toBe(['texto' => 'traduzido:en', 'cache' => true])
        ->and($espiao->chamadas)->toBe(1);

    // Outro idioma é outra tradução.
    $servico->traduzir('Reunião amanhã', 'es');
    expect($espiao->chamadas)->toBe(2);
});

it('grava quem traduziu, declarado pelo provedor', function (): void {
    (new TraducaoService(tradutorEspiao()))->traduzir('Texto', 'en');

    expect(TraducaoEmCache::query()->sole()->provedor)->toBe('espiao-v1');
});

it('manda ao provedor no máximo o teto da config', function (): void {
    config(['ia.limite_caracteres' => 50]);

    (new TraducaoService($espiao = tradutorEspiao()))->traduzir(str_repeat('a', 500), 'en');

    expect(Illuminate\Support\Str::length($espiao->recebidos[0]))->toBe(51)   // 50 + reticências
        ->and(TextoParaIa::limitar('curto'))->toBe('curto');
});

it('o código de idioma é o locale efetivo, em duas letras', function (): void {
    app()->setLocale('pt_BR');
    expect(IdiomaDaRequisicao::codigo())->toBe('pt');

    app()->setLocale('es');
    expect(IdiomaDaRequisicao::codigo())->toBe('es');
});

it('throttle:ia e throttle:traducao existem e limitam com os valores da config', function (): void {
    config(['ia.limites.ia' => 2]);

    Route::get('/teste-ia', fn (): string => 'ok')->middleware('throttle:ia');
    Route::get('/teste-traducao', fn (): string => 'ok')->middleware('throttle:traducao');

    $this->get('/teste-ia')->assertOk();
    $this->get('/teste-ia')->assertOk();
    $this->get('/teste-ia')->assertTooManyRequests();

    $this->get('/teste-traducao')->assertOk()->assertHeader('X-RateLimit-Limit', '20');
});
