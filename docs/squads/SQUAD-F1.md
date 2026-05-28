# Squad — F1: Fundação de domínio e API-ready

> **Fase:** F1
> **Objetivo:** `api/v1` publicada; guards operacionais; migrations A+B; policies base
> **Story Points:** 34 SP
> **Dependências:** nenhuma (fase inicial)
> **Status:** 🟢 Em andamento
> **Atualizado em:** 2026-04-25
> **Progresso:** 16/16 stories concluídas · F1 FECHADA ✅
> **Verificação de skills:** ✅ 13/13 obrigatórias instaladas · ✅ 5/5 opcionais instaladas · ✅ 3/3 BMAD agents instalados

---

## F1 CONCLUÍDA — 2026-04-25

> Todas as 16 stories entregues e mescladas em `develop`. Próxima fase: F2 (Portal Adesão — wizard público).

**Critérios de aceite confirmados:**

- ✅ `GET /api/v1/me` → 401 sem auth, 200 com Sanctum
- ✅ `X-Request-Id` em toda resposta (AttachRequestId global)
- ✅ `HasUlid` trait sem IDs sequenciais em URLs
- ✅ 6 arch assertions passando (strict_types, DTOs readonly, exceções)
- ✅ CI/GitHub Actions configurado (`.github/workflows/ci.yml`)

---

## Agents BMAD

| Agent               | Papel na fase                                                    |
| ------------------- | ---------------------------------------------------------------- |
| `bmad-orchestrator` | Inicialização do projeto BMAD, status e roteamento entre stories |
| `scrum-master`      | Quebrar F1 em stories; estimativas Fibonacci; planejar sprint    |
| `developer`         | Implementar cada story; commitar; invocar skills especializadas  |

---

## Skills por domínio

### Obrigatórias

| Skill                    | Domínio                                                                 | Como invocar                             |
| ------------------------ | ----------------------------------------------------------------------- | ---------------------------------------- |
| `laravel-best-practices` | Padrões gerais (strict_types, type hints, PSR-12)                       | `/laravel-best-practices`                |
| `laravel-specialist`     | Setup Laravel 13, guards, Sanctum, providers                            | `Skill({ skill: "laravel-specialist" })` |
| `laravel-models`         | Models base: Organizacao, Instituicao, Curso, Turma, Evento, Formando   | `/laravel-models`                        |
| `laravel-services`       | Services de domínio; RateLimiterServiceProvider, GatewayServiceProvider | `/laravel-services`                      |
| `laravel-routing`        | routes/api/v1.php e routes/webhook.php skeleton                         | `/laravel-routing`                       |
| `laravel-validation`     | FormRequests base e middleware de validação                             | `/laravel-validation`                    |
| `laravel-security`       | Sanctum, CORS, CSRF, middleware de autenticação                         | `/laravel-security`                      |
| `laravel-enums`          | Enums base: StatusAdesao, StatusParcela, PerfilAtor, OrigemAtor         | `/laravel-enums`                         |
| `laravel-dtos`           | Data classes: NovaAdesaoData, AdesaoResultData, PaginatedResponseData   | `/laravel-dtos`                          |
| `laravel-actions`        | Actions skeleton: CriarAdesaoAction, GerarParcelasAction                | `/laravel-actions`                       |
| `laravel-exceptions`     | DomainException, InvariantViolationException                            | `/laravel-exceptions`                    |
| `pest-testing`           | Pest arch tests, testes de middleware e guards                          | `/pest-testing`                          |
| `php-best-practices`     | PHP 8.4: readonly, fibers, type system completo                         | `/php-best-practices`                    |

### Opcionais/situacionais

| Skill                                          | Domínio       | Quando usar                                                |
| ---------------------------------------------- | ------------- | ---------------------------------------------------------- |
| `laravel-owasp-security`                       | Segurança     | Ao configurar rate limiting e headers de segurança         |
| `superpowers-laravel:migrations-and-factories` | Migrations    | Ao escrever migrations A+B e factories base                |
| `adr-skill`                                    | Arquitetura   | Para registrar decisões: ULID vs UUID, guards, auth driver |
| `laravel-multi-tenancy`                        | Multi-tenancy | Se decidir por tenant isolation no modelo de dados         |
| `laravel-quality`                              | Qualidade     | Antes de cada commit (Pint + PHPStan + Pest)               |

---

## Atribuição por tarefa (stories F1)

Legenda: ✅ Concluída · 🔄 Em andamento · ⬜ Pendente

| Tarefa / Story                                                                                                | Status | Domínio    | Skill primária           | Skill secundária                               | BMAD agent  |
| ------------------------------------------------------------------------------------------------------------- | ------ | ---------- | ------------------------ | ---------------------------------------------- | ----------- |
| Instalar pacotes base (sanctum, saloon, firebase/php-jwt, spatie/permission, pt-br-validator)                 | ✅     | Pacotes    | `laravel-best-practices` | `laravel-packages`                             | `developer` |
| Configurar config/auth.php (guards admin, portal, sanctum) e config/sanctum.php                               | ✅     | Auth       | `laravel-security`       | `laravel-specialist`                           | `developer` |
| Criar routes/api/v1.php e routes/webhook.php skeleton                                                         | ✅     | Roteamento | `laravel-routing`        | `laravel-api`                                  | `developer` |
| Service Providers: RateLimiterServiceProvider, PaymentServiceProvider                                         | ✅     | Providers  | `laravel-services`       | `laravel-providers`                            | `developer` |
| Migrations bloco A (identidade: admin_users, portal_users)                                                    | ✅     | Banco      | `laravel-models`         | `superpowers-laravel:migrations-and-factories` | `developer` |
| Migrations bloco B (cadastro: organizacoes, instituicoes, cursos, turmas, contratos, adesoes, parcelas)       | ✅     | Banco      | `laravel-models`         | `superpowers-laravel:migrations-and-factories` | `developer` |
| Models base: Organizacao, Instituicao, Curso, Turma, Contrato, Pacote, Adesao, Parcela, PortalUser, AdminUser | ✅     | Models     | `laravel-models`         | `eloquent-best-practices`                      | `developer` |
| Enums base (8 namespaces: Adesao, Contrato, Evento, Instituicao, Pacotes, Pagamento, Termo, Turma)            | ✅     | Enums      | `laravel-enums`          | `laravel-best-practices`                       | `developer` |
| DTOs: Pagamento (4 DTOs completos) + Adesao (IniciarAdesaoResult, DraftTokenClaims)                           | ✅     | DTOs       | `laravel-dtos`           | `laravel-value-objects`                        | `developer` |
| Actions: IniciarAdesaoPublicaAction, ResolveContratoPorCodigoAction (+ contracts)                             | ✅     | Actions    | `laravel-actions`        | `laravel-dtos`                                 | `developer` |
| Exceções de domínio: Adesao (7 exc.) + Pagamento (5 exc.)                                                     | ✅     | Exceções   | `laravel-exceptions`     | `php-best-practices`                           | `developer` |
| SPEC-F-009 Gateway: PaymentGatewayContract, FakeGateway, ItauGateway stub, webhook pipeline                   | ✅     | Gateway    | `laravel-services`       | `laravel-security`                             | `developer` |
| Middlewares: AttachRequestId global middleware (SPEC-F-010)                                                   | ✅     | Middleware | `laravel-security`       | `laravel-best-practices`                       | `developer` |
| Trait HasUlid em app/Support/ com bootHasUlid e getRouteKeyName                                               | ✅     | Suporte    | `php-best-practices`     | `laravel-best-practices`                       | `developer` |
| Pest arch tests (strict_types, DTOs readonly, exceções, controllers)                                          | ✅     | Testes     | `pest-testing`           | `laravel-testing`                              | `developer` |
| CI: GitHub Actions (pint + phpstan + pest + prettier) + Validar GET /api/v1/me → 401/200                      | ✅     | DevOps/API | `laravel-quality`        | `laravel-security`                             | `developer` |

---

## Critérios de aceite da fase

- [ ] `php artisan test --compact` passa 100%
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros
- [ ] `./vendor/bin/pint --dirty` sem alterações
- [ ] `GET /api/v1/me` retorna 401 sem token e 200 com Sanctum válido
- [ ] `POST /api/v1/convite/{token}/rsvp` resolve token via middleware custom
- [ ] Todos os arquivos PHP têm `declare(strict_types=1)`
- [ ] Nenhum ID sequencial exposto em URL ou resposta da API
- [ ] CI verde no GitHub Actions

---

## Progresso detalhado (2026-04-25) — F1 FECHADA

### Entregue

- **14 Models** com factories e seeders (DevelopmentSeeder orquestrado)
- **20+ Enums backed** em 8 namespaces (ex.: StatusAdesao, StatusIntent, CategoriaPacote)
- **6 DTOs readonly** (Pagamento completo + Adesao parcial)
- **2 Actions** com contracts (IniciarAdesaoPublica, ResolveContratoPorCodigo)
- **12 Exceções de domínio** (7 Adesao + 5 Pagamento)
- **SPEC-F-009** — FakeGateway completo: PaymentManager, drivers, webhook pipeline, `ProcessarWebhookJob`, `SimularWebhookJob`, contract test com dataset
- **SPEC-F-010** — MeController, AttachRequestId (global), HasUlid, Arch tests (6 asserções)
- **DraftTokenService** + `DraftTokenClaims` DTO
- **Routes**: `routes/api/v1.php` e `routes/webhook.php` operacionais
- **Providers**: RateLimiterServiceProvider + PaymentServiceProvider
- **CI/CD**: `.github/workflows/ci.yml` — quality (Pint + PHPStan + Prettier) + tests (Pest SQLite)
- **390+ testes** passando (Unit + Feature)

---

## Notas e decisões

- ULID público + BIGINT interno (ver PRD §ULID) — registrar ADR se houver questionamento
- Guards separados: `admin` (AdminUser) e `portal` (PortalUser) — nunca compartilhar session
- Webhooks em `routes/webhook.php` sem CSRF mas com validação HMAC
- Valores monetários: colunas INTEGER (centavos), nunca float
- `FakeGateway` usa cache (não DB) para estado de intent — volátil por design
- `DB::transaction()` no controller do webhook (não no job) para SAVEPOINT correto
- CI/GitHub Actions: `.github/workflows/ci.yml` com jobs `quality` + `tests` separados

---

_Gerado por skill `squad-configurator`. Atualizado em 2026-04-24._
