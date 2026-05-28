---
title: Test Plan — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-18
status: draft
---

# Test Plan — Portal ArtFinal v2 (Backend API v1)

Plano operacional de testes: estrutura de pastas, factories, seeders, fixtures, mocks, execução paralela, cobertura mínima e exemplos Pest executáveis.

---

## 1. Estrutura de pastas

```
tests/
├── Pest.php                          # setup global Pest 4
├── TestCase.php                      # base class com helpers custom
├── CreatesApplication.php
├── Arch/
│   ├── ActionsTest.php
│   ├── ControllersTest.php
│   ├── ModelsTest.php
│   ├── PoliciesTest.php
│   └── StrictTypesTest.php
├── Unit/
│   ├── Identidade/
│   │   ├── LoginRequestTest.php
│   │   └── TokenDevicePolicyTest.php
│   ├── Cadastro/
│   │   ├── CnpjValidatorTest.php
│   │   └── OrganizacaoFactoryTest.php
│   ├── Comercial/
│   │   ├── CotaCalculatorTest.php
│   │   ├── ParcelamentoCalculatorServiceTest.php
│   │   └── SnapshotComercialServiceTest.php
│   ├── Convites/
│   │   ├── GerarTokenConviteServiceTest.php
│   │   └── TransferirConviteActionTest.php
│   ├── Rsvp/
│   │   └── RegistrarRsvpActionTest.php
│   ├── Seating/
│   │   ├── ReservarAssentoActionTest.php
│   │   ├── ConfirmarAssentoActionTest.php
│   │   ├── TrocarAssentoActionTest.php
│   │   ├── ExpirarHoldAssentoActionTest.php
│   │   ├── HoldServiceTest.php
│   │   └── DisponibilidadeServiceTest.php
│   ├── Extras/
│   │   ├── CriarPedidoExtraActionTest.php
│   │   └── AprovarPedidoExtraActionTest.php
│   ├── Pagamentos/
│   │   ├── IniciarPagamentoActionTest.php
│   │   ├── ProcessarWebhookPagamentoActionTest.php
│   │   └── ReconciliarPagamentosJobTest.php
│   ├── Enquetes/
│   │   ├── RegistrarVotoActionTest.php
│   │   └── AvaliarElegibilidadeServiceTest.php
│   └── Comunicacao/
│       └── EnviarConviteEmailJobTest.php
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── Auth/
│   │       │   ├── LoginControllerTest.php
│   │       │   ├── LogoutControllerTest.php
│   │       │   └── MeControllerTest.php
│   │       ├── Cadastro/
│   │       │   ├── OrganizacoesControllerTest.php
│   │       │   ├── InstituicoesControllerTest.php
│   │       │   ├── TurmasControllerTest.php
│   │       │   └── EventosControllerTest.php
│   │       ├── Adesoes/
│   │       │   ├── AdesoesControllerTest.php
│   │       │   └── ParcelasControllerTest.php
│   │       ├── Convites/
│   │       │   ├── ConvitesControllerTest.php
│   │       │   ├── ConviteLoteControllerTest.php
│   │       │   └── ConvitePublicoControllerTest.php
│   │       ├── Rsvp/
│   │       │   └── RsvpControllerTest.php
│   │       ├── Seating/
│   │       │   ├── ReservasControllerTest.php
│   │       │   ├── ConfirmarReservaControllerTest.php
│   │       │   ├── TrocarReservaControllerTest.php
│   │       │   └── MapaControllerTest.php
│   │       ├── Extras/
│   │       │   ├── PedidosExtrasControllerTest.php
│   │       │   └── AprovacaoControllerTest.php
│   │       ├── Pagamentos/
│   │       │   └── PagamentosControllerTest.php
│   │       ├── Enquetes/
│   │       │   ├── EnquetesControllerTest.php
│   │       │   └── VotosControllerTest.php
│   │       └── Me/
│   │           └── MeExclusaoControllerTest.php
│   ├── Webhook/
│   │   ├── PagamentoWebhookItauTest.php
│   │   ├── PagamentoWebhookMockTest.php
│   │   └── WebhookIdempotenciaTest.php
│   └── Contract/
│       ├── OpenApiConsistenciaTest.php
│       └── ResponseSchemaTest.php
├── Concurrency/
│   ├── ReservarAssentoParalelosTest.php
│   ├── TrocarAssentoBilateralTest.php
│   └── WebhookDuplicadoTest.php
└── Browser/
    └── Admin/
        ├── LoginAdminTest.php
        ├── EventoCrudTest.php
        ├── ConviteLoteTest.php
        └── MapaMesasTest.php
```

### 1.1 Naming conventions

- Feature test: nome do controller + `Test.php`; cada test file descreve um único endpoint ou fluxo coeso.
- Unit test: nome da classe testada + `Test.php`.
- Arch: nome da invariante + `Test.php`.
- Concurrency: nome do cenário + `Test.php`.
- Test closures: `it('descreve o comportamento em PT-BR', function (): void { ... })`.
- Datasets: nome descritivo PT-BR com `dataset('nome_dataset', [...])`.

## 2. Factories

Uma factory por model; states nomeados para setup legível.

### 2.1 Organização de pastas

```
database/factories/
├── Acesso/
│   ├── AdminUserFactory.php
│   ├── PortalUserFactory.php
│   └── ComissaoUserFactory.php
├── Cadastro/
│   ├── OrganizacaoFactory.php
│   ├── InstituicaoFactory.php
│   ├── CursoFactory.php
│   ├── TurmaFactory.php
│   ├── EventoFactory.php
│   └── FormandoFactory.php
├── Comercial/
│   ├── AdesaoFactory.php
│   ├── PacoteFactory.php
│   ├── ProdutoFactory.php
│   └── ParcelaFactory.php
├── Convites/
│   └── ConviteFactory.php
├── Rsvp/
│   └── RsvpHistoricoFactory.php
├── Seating/
│   ├── MapaMesasFactory.php
│   ├── SetorFactory.php
│   ├── MesaFactory.php
│   ├── AssentoFactory.php
│   └── ReservaAssentoFactory.php
├── Extras/
│   ├── PedidoExtraFactory.php
│   └── PedidoExtraItemFactory.php
├── Pagamentos/
│   ├── PagamentoFactory.php
│   └── WebhookEventoFactory.php
├── Enquetes/
│   ├── EnqueteFactory.php
│   └── VotoFactory.php
└── Comunicacao/
    └── NotificacaoFactory.php
```

### 2.2 States nomeados obrigatórios

| Factory                 | States obrigatórios                                                             |
| ----------------------- | ------------------------------------------------------------------------------- |
| `OrganizacaoFactory`    | `ativo`, `inativo`                                                              |
| `EventoFactory`         | `rascunho`, `publicado`, `encerrado`, `exigeRsvpParaSeating`                    |
| `AdesaoFactory`         | `rascunho`, `pendentePagamento`, `ativa`, `cancelada`, `inadimplente`           |
| `ConviteFactory`        | `rascunho`, `emitido`, `cancelado`, `transferido`, `comRsvpConfirmado`, `extra` |
| `ReservaAssentoFactory` | `hold`, `holdExpirado`, `confirmada`, `cancelada`, `bloqueada`                  |
| `PedidoExtraFactory`    | `rascunho`, `pendenteAprovacao`, `aguardandoPagamento`, `pago`, `estornado`     |
| `PagamentoFactory`      | `pendente`, `autorizado`, `pago`, `falhou`, `estornado`                         |
| `WebhookEventoFactory`  | `recebido`, `processado`, `falhou`                                              |
| `EnqueteFactory`        | `aberta`, `fechada`, `comPermissaoEdicao`, `secreta`                            |

### 2.3 Exemplo de factory

```php
<?php

declare(strict_types=1);

namespace Database\Factories\Seating;

use App\Enums\Seating\StatusReserva;
use App\Models\Seating\Assento;
use App\Models\Seating\ReservaAssento;
use App\Support\Ulid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservaAssento>
 */
final class ReservaAssentoFactory extends Factory
{
    protected $model = ReservaAssento::class;

    public function definition(): array
    {
        return [
            'ulid'             => Ulid::generate(),
            'assento_id'       => Assento::factory(),
            'formando_id'      => null,
            'convite_id'       => null,
            'idempotency_key'  => fake()->uuid(),
            'origem'           => 'formando',
            'status'           => StatusReserva::Hold,
            'hold_expires_at'  => now()->addMinutes(5),
            'confirmado_at'    => null,
            'correlation_id'   => fake()->uuid(),
        ];
    }

    public function hold(): static
    {
        return $this->state(fn () => [
            'status'          => StatusReserva::Hold,
            'hold_expires_at' => now()->addMinutes(5),
        ]);
    }

    public function holdExpirado(): static
    {
        return $this->state(fn () => [
            'status'          => StatusReserva::Hold,
            'hold_expires_at' => now()->subMinute(),
        ]);
    }

    public function confirmada(): static
    {
        return $this->state(fn () => [
            'status'          => StatusReserva::Confirmada,
            'hold_expires_at' => null,
            'confirmado_at'   => now(),
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => [
            'status' => StatusReserva::Cancelada,
        ]);
    }
}
```

## 3. Fixtures e seeders

### 3.1 `DevelopmentSeeder`

- Cria dados aleatórios com `faker('pt_BR')`.
- Estado reproduzível apenas por `--seed=fixo`; por default cada `make fresh` gera dados novos.
- Composição: 1 org, 1 instituição, 2 cursos, 3 turmas, 1 evento com mapa 10 mesas × 8 assentos, 20 formandos, cotas, 50 convites, reservas em estados variados, 5 pedidos extras pagos, 2 enquetes.
- Uso: `make fresh` no dev; **nunca** em teste automatizado.

### 3.2 `TestSeeder`

- Determinístico; usado por feature tests que precisam de setup maior que uma factory isolada.
- Usa `--seed=TestSeeder` (nome explícito).
- Fixa seed faker: `fake()->seed(1234)`.

### 3.3 Fixtures raros

- Evitar ao máximo. Preferir factory.
- Quando usar: payload de webhook real de sandbox Itaú em `tests/Fixtures/Webhook/itau-pagamento-confirmado.json` — justificativa de aderir à forma literal do integrador.

## 4. Mocks vs Fakes

| Cenário                      | Preferir               | Por quê                                                                  |
| ---------------------------- | ---------------------- | ------------------------------------------------------------------------ |
| HTTP outgoing (gateway Itaú) | `Http::fake()`         | Fake controlável, contrato explícito na assertion `Http::assertSent()`.  |
| Queue dispatch               | `Queue::fake()`        | `assertPushed` verifica side-effect sem executar job.                    |
| Email                        | `Mail::fake()`         | `assertQueued` verifica gatilho sem enviar.                              |
| Event dispatch               | `Event::fake()`        | Verifica que domínio disparou evento certo.                              |
| Storage                      | `Storage::fake()`      | Valida upload sem escrever em disco real.                                |
| Policy/Gate                  | **não mockar**         | Usar factory de usuário com role real; mockar policy esconde bug de ACL. |
| `DB::transaction`            | **não mockar**         | Rodar com banco real; mockar mascara deadlock e locks.                   |
| Time (`now()`)               | `Carbon::setTestNow()` | Controle determinístico de janelas (RSVP, hold, enquete).                |
| External dependency concreta | `Mockery`              | Quando a dependência tem comportamento complexo e não é facade.          |

### 4.1 Exemplo — Http::fake

```php
it('chama gateway com payload correto ao iniciar pagamento', function (): void {
    Http::fake([
        'itau.com.br/v2/cobrancas' => Http::response(['id' => 'gw-1', 'status' => 'criado'], 201),
    ]);

    $pedido = PedidoExtra::factory()->aguardandoPagamento()->create();
    app(IniciarPagamentoAction::class)->execute($pedido);

    Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
        return str_contains($request->url(), 'cobrancas')
            && $request['valor_centavos'] > 0;
    });
});
```

## 5. Execução paralela

Pest `--parallel` divide a suíte em workers independentes.

### 5.1 Configuração

`phpunit.xml`:

```xml
<phpunit ...>
  <source>
    <include>
      <directory>./app</directory>
    </include>
  </source>
  <php>
    <env name="DB_CONNECTION" value="pgsql_test"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
  </php>
</phpunit>
```

`config/database.php`:

```php
'pgsql_test' => [
    'driver'   => 'pgsql',
    'host'     => env('DB_HOST', 'postgres'),
    'database' => env('DB_DATABASE_TEST', 'artfinal_test_'.env('TEST_TOKEN', '')),
    'prefix'   => '',
    'schema'   => 'public',
],
```

Cada worker tem seu próprio DB via `TEST_TOKEN` (feature nativa do Paratest/Pest parallel).

### 5.2 Testes que **não** rodam em paralelo

- `tests/Concurrency/*` — marcados com `group('serial')`, rodados em worker único.
- Testes que usam `Carbon::setTestNow()` com escopo global — isolar com `beforeEach`/`afterEach`.

## 6. Concorrência real

Para provar que `lockForUpdate` + unique parcial funcionam sob corrida real, usar **pcntl_fork** (Unix).

### 6.1 Template

```php
<?php

declare(strict_types=1);

use App\Actions\Seating\ReservarAssentoAction;
use App\Data\Seating\ReservaRequestData;
use App\Enums\Seating\OrigemReserva;
use App\Enums\Seating\StatusReserva;
use App\Models\Seating\Assento;
use App\Models\Seating\ReservaAssento;

it('somente uma entre N requisições concorrentes vence (fork real)', function (): void {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl não disponível.');
    }

    $assento = Assento::factory()->create();
    $totalProcessos = 10;

    $pids = [];
    for ($i = 0; $i < $totalProcessos; $i++) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            // filho: reconecta DB e tenta reservar
            DB::reconnect();
            try {
                app(ReservarAssentoAction::class)->execute(new ReservaRequestData(
                    assentoUlid: $assento->ulid,
                    conviteUlid: null,
                    origem: OrigemReserva::Formando,
                    idempotencyKey: "k-$i",
                    atorId: $i + 1,
                    atorTipo: 'formando',
                ));
                exit(0); // sucesso
            } catch (\Throwable) {
                exit(1); // conflito esperado
            }
        }
        $pids[] = $pid;
    }

    $sucessos = 0;
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        if (pcntl_wexitstatus($status) === 0) {
            $sucessos++;
        }
    }

    expect($sucessos)->toBe(1);
    expect(ReservaAssento::query()
        ->where('assento_id', $assento->id)
        ->whereIn('status', [StatusReserva::Hold, StatusReserva::Confirmada])
        ->count())->toBe(1);
})->group('serial', 'concurrency');
```

### 6.2 Alternativa — dispatchSync em loop

Mais portável (Windows + Mac), menos fiel:

```php
it('apenas uma de N dispatches síncronos vence', function (): void {
    $assento = Assento::factory()->create();
    $action  = app(ReservarAssentoAction::class);

    $sucesso = 0;
    foreach (range(1, 100) as $i) {
        try {
            $action->execute(new ReservaRequestData(
                assentoUlid: $assento->ulid,
                conviteUlid: null,
                origem: OrigemReserva::Formando,
                idempotencyKey: "k-$i",
                atorId: $i,
                atorTipo: 'formando',
            ));
            $sucesso++;
        } catch (\App\Exceptions\Seating\AssentoIndisponivelException) {
            // conflito esperado
        }
    }

    expect($sucesso)->toBe(1);
});
```

## 7. Cobertura mínima

| Classe/categoria    | Cobertura mínima | Ferramenta de medição                        |
| ------------------- | ---------------- | -------------------------------------------- |
| Actions             | 100%             | `pest --coverage --filter=Actions`           |
| Controllers API v1  | 80%              | `pest --coverage --filter=Api/V1`            |
| Jobs                | 90%              | `pest --coverage --filter=Jobs`              |
| Policies            | 100%             | `pest --coverage --filter=Policies`          |
| Services domínio    | 90%              | `pest --coverage --filter=Services`          |
| DTOs                | 100%             | `pest --coverage --filter=Data`              |
| Enums               | 100%             | `pest --coverage --filter=Enums`             |
| Observers/Listeners | 85%              | `pest --coverage --filter=Events\|Listeners` |
| Global              | ≥ 80%            | `pest --coverage`                            |

## 8. Exemplos Pest por contexto

### 8.1 Unit — CotaCalculator

```php
<?php

declare(strict_types=1);

use App\Services\Comercial\CotaCalculator;
use App\Models\Cadastro\Formando;

it('calcula cota base sem extras', function (): void {
    $formando = Formando::factory()->comAdesaoAtiva()->create();
    $formando->adesaoAtiva->snapshot_comercial = ['cota' => ['base' => 4]];
    $formando->adesaoAtiva->save();

    $resultado = app(CotaCalculator::class)->calcular($formando);

    expect($resultado->total)->toBe(4)
        ->and($resultado->emitidos)->toBe(0)
        ->and($resultado->disponivel)->toBe(4);
});

it('soma extras pagos ao total', function (): void {
    $formando = Formando::factory()
        ->comAdesaoAtiva(['snapshot_comercial' => ['cota' => ['base' => 4, 'extra_paga_max' => 2]]])
        ->comPedidoExtraPago(quantidade: 1)
        ->create();

    $resultado = app(CotaCalculator::class)->calcular($formando);

    expect($resultado->total)->toBe(5);
});

it('retorna zero disponível quando emitidos = total', function (): void {
    $formando = Formando::factory()
        ->comAdesaoAtiva(['snapshot_comercial' => ['cota' => ['base' => 4]]])
        ->comConvitesEmitidos(4)
        ->create();

    $resultado = app(CotaCalculator::class)->calcular($formando);

    expect($resultado->disponivel)->toBe(0);
});
```

### 8.2 Feature — Login SPA

```php
<?php

declare(strict_types=1);

use App\Models\Acesso\PortalUser;

it('autentica formando via SPA e seta cookie de sessão', function (): void {
    PortalUser::factory()->create([
        'email'    => 'maria@exemplo.com',
        'password' => bcrypt('segredo123'),
    ]);

    $this->getJson('/sanctum/csrf-cookie')->assertNoContent();

    $response = $this->postJson('/api/v1/auth/login', [
        'email'    => 'maria@exemplo.com',
        'password' => 'segredo123',
        'mode'     => 'spa',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['status', 'user' => ['id', 'email']])
        ->assertJson(['status' => 'ok']);

    expect($response->headers->getCookies())->not->toBeEmpty();
});

it('retorna 401 em credenciais inválidas com envelope §2.11', function (): void {
    PortalUser::factory()->create(['email' => 'x@y.z', 'password' => bcrypt('ok')]);

    $this->postJson('/api/v1/auth/login', [
        'email'    => 'x@y.z',
        'password' => 'errado',
        'mode'     => 'spa',
    ])->assertUnauthorized()
      ->assertJsonPath('error.code', 'unauthenticated');
});
```

### 8.3 Feature — Webhook idempotente

```php
<?php

declare(strict_types=1);

use App\Models\Extras\PedidoExtra;
use App\Models\Webhook\WebhookEvento;

it('não aplica pagamento duas vezes para o mesmo gateway_reference', function (): void {
    $pedido  = PedidoExtra::factory()->aguardandoPagamento()->create();
    $payload = [
        'tipo'        => 'pagamento.confirmado',
        'evento'      => ['id' => 'gw-123'],
        'pedido_ulid' => $pedido->ulid,
    ];
    $sig = hash_hmac('sha256', json_encode($payload), config('gateway.itau.webhook_secret'));

    $this->withHeader('X-Signature', $sig)
         ->postJson('/webhooks/pagamentos/itau', $payload)
         ->assertAccepted();

    $this->withHeader('X-Signature', $sig)
         ->postJson('/webhooks/pagamentos/itau', $payload)
         ->assertOk()
         ->assertJson(['status' => 'already_processed']);

    expect(WebhookEvento::count())->toBe(1);
    expect($pedido->fresh()->status->value)->toBe('pago');
});
```

### 8.4 Arch — invariantes

```php
<?php

declare(strict_types=1);

arch('actions não tocam HTTP')
    ->expect('App\Actions')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Http\JsonResponse',
        'Illuminate\Http\RedirectResponse',
    ]);

arch('models não tocam actions')
    ->expect('App\Models')->not->toUse('App\Actions');

arch('controllers API v1 sem facade DB')
    ->expect('App\Http\Api\V1\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('DTOs readonly')
    ->expect('App\Data')->toBeReadonly();

arch('enums no namespace correto')
    ->expect('App\Enums')->toBeEnums();

arch('strict_types em todo o app')
    ->expect(['App', 'tests'])->toUseStrictTypes();
```

### 8.5 Browser — admin CRUD evento

```php
<?php

declare(strict_types=1);

it('admin cria evento via Livewire', function (): void {
    $admin = \App\Models\Acesso\AdminUser::factory()->admin()->create();

    $this->actingAs($admin, 'admin');

    visit('/admin/eventos/criar')
        ->fill('nome', 'Formatura 2026')
        ->fill('data_evento', '2026-12-15')
        ->click('Criar')
        ->assertPathIs('/admin/eventos')
        ->assertSee('Evento criado com sucesso.');

    expect(\App\Models\Cadastro\Evento::where('nome', 'Formatura 2026')->exists())->toBeTrue();
});
```

## 9. Helpers e macros

`tests/TestCase.php`:

```php
<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function authAs(string $role, array $attrs = []): \App\Models\Acesso\PortalUser
    {
        $user = \App\Models\Acesso\PortalUser::factory()->create($attrs);
        $user->assignRole($role);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    protected function comHmacItau(array $payload): self
    {
        $sig = hash_hmac('sha256', json_encode($payload), config('gateway.itau.webhook_secret'));
        return $this->withHeader('X-Signature', $sig);
    }
}
```

## 10. Comandos e Makefile

Em `Makefile`:

```make
.PHONY: test test-unit test-feature test-arch test-concurrent test-coverage quality

test:
	php artisan test --compact --parallel

test-unit:
	php artisan test --compact --testsuite=Unit

test-feature:
	php artisan test --compact --testsuite=Feature

test-arch:
	php artisan test --compact tests/Arch

test-concurrent:
	php artisan test --group=concurrency --stop-on-failure

test-coverage:
	php artisan test --coverage --min=80

quality:
	./vendor/bin/pint --test
	./vendor/bin/phpstan analyse --no-progress --memory-limit=2G
	npx prettier --check resources/
	npx eslint resources/js/
	php artisan test --compact --parallel

lint:
	./vendor/bin/pint
	npx prettier --write resources/
```

## 11. Dataset pattern

Para cobrir matriz de casos:

```php
it('rejeita transição de estado inválida', function (string $from, string $to): void {
    $adesao = \App\Models\Comercial\Adesao::factory()->state(['status' => $from])->create();

    $response = $this->authAs('admin-system')
        ->putJson("/api/v1/adesoes/{$adesao->ulid}", ['status' => $to]);

    $response->assertStatus(409)->assertJsonPath('error.code', 'transicao_invalida');
})->with([
    'cancelada→ativa'  => ['cancelada', 'ativa'],
    'cancelada→rascunho' => ['cancelada', 'rascunho'],
    'ativa→rascunho'   => ['ativa', 'rascunho'],
]);
```

## 12. CI — GitHub Actions

`.github/workflows/ci.yml` (resumo):

```yaml
name: CI
on: [push, pull_request]

jobs:
    quality:
        runs-on: ubuntu-latest
        services:
            postgres:
                image: postgres:16
                env: { POSTGRES_DB: artfinal_test, POSTGRES_PASSWORD: secret }
                options: --health-cmd="pg_isready" --health-interval=10s
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with: { php-version: '8.4', coverage: xdebug }
            - run: composer install --no-interaction --prefer-dist
            - run: cp .env.example .env && php artisan key:generate
            - run: ./vendor/bin/pint --test --format agent
            - run: ./vendor/bin/phpstan analyse --no-progress --memory-limit=2G
            - run: php artisan test --compact --parallel
            - run: php artisan test --group=concurrency --stop-on-failure
```

## 13. Anti-patterns a evitar

- **Test helper que faz tudo** — esconde setup. Factories explícitas ou métodos pequenos na TestCase.
- **Assertar só status** — sempre verificar corpo + side-effect em DB/queue.
- **`sleep()`** em teste de concorrência — use sync ou fork com `waitpid`.
- **Seeder global no `beforeEach`** — isola teste via factory.
- **Mockar domínio para "acelerar"** — perde o valor da suíte.
- **Teste que imprime `dump()`** — CI fica ruidoso; use assertion clara.
- **Usar banco sqlite para feature tests de JSONB/EXCLUDE** — precisa PG real.
