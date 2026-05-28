# Convenções e Padrões do Projeto

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 09/04/2026

---

## 1. Padrão de Commits (Conventional Commits)

### 1.1 Formato

```
tipo(escopo): descrição curta em português

Corpo opcional com detalhes.

Refs: #issue-number
```

### 1.2 Tipos

| Tipo       | Quando Usar                                  |
| ---------- | -------------------------------------------- |
| `feat`     | Nova funcionalidade                          |
| `fix`      | Correção de bug                              |
| `refactor` | Refatoração sem mudar comportamento          |
| `docs`     | Apenas documentação                          |
| `style`    | Formatação, espaços, ponto-e-vírgula         |
| `test`     | Adição ou correção de testes                 |
| `chore`    | Tarefas de manutenção, dependências, configs |
| `perf`     | Melhoria de performance                      |
| `ci`       | Mudanças em CI/CD                            |
| `revert`   | Reverter commit anterior                     |

### 1.3 Escopos

| Escopo       | Área                                    |
| ------------ | --------------------------------------- |
| `admin`      | Backoffice administrativo               |
| `portal`     | Portal do formando                      |
| `gateway`    | Integração com gateway de pagamentos    |
| `financeiro` | Parcelas, cálculos, extrato             |
| `adesao`     | Wizard de adesão                        |
| `auth`       | Autenticação (admin ou portal)          |
| `infra`      | Docker, Laradock, CI, deploy            |
| `models`     | Models, migrations, seeders             |
| `docs`       | Documentação                            |
| `ui`         | Interface, componentes visuais          |
| `skills`     | Skills e automações `.claude`/`.agents` |
| `squad`      | Documentação de squad planejamento      |

### 1.4 Regras

- Descrição em **português**, **imperativo**, **minúscula** após os dois-pontos
- Máximo 72 caracteres na primeira linha
- Sem ponto final na primeira linha
- Corpo do commit separado por linha em branco
- Referências a issues quando aplicável

### 1.5 Exemplos

```
feat(portal): implementar etapa de seleção de pacotes no wizard
fix(financeiro): corrigir cálculo de parcela mínima para boleto
refactor(gateway): extrair lógica de retry para service dedicado
docs(docs): documentar módulo de programações de valor
test(adesao): adicionar cenários de teste para checkout
chore(infra): atualizar PHP para 8.4 no Laradock
perf(admin): otimizar query de listagem de formandos com eager loading
```

### 1.6 Validação local (hooks + Composer)

Os hooks **Husky** já rodam **`commitlint`** em todo `git commit` (`.husky/commit-msg`). Para validar manualmente **a última mensagem** já registrada:

```bash
composer run lint:last-commit-msg
```

**Commit assistido no terminal** (tipo e escopo vêm do `commitlint.config.cjs`; prompts em pt-BR):

```bash
git add ...
npm run commit
# mesmo fluxo, via Composer na raíz do projeto:
composer run commit
```

O hook **`prepare-commit-msg`** (Husky):

- Reescreve na **primeira linha** escopos inválidos **`(database|db|sql)` → `(models)`** para alinhar ao `commitlint`;
- Remove **`Co-authored-by`** que mencione **Cursor** (IDE).

A sugestão _Generate Commit Message_ do Cursor (ou de extensões Git) **não valida** contra o `commitlint` — use **`npm run commit`** quando quiser mensagem guiada e compatível, ou edite a sugestão antes de confirmar.

---

## 2. Branching Strategy (Git Flow Simplificado)

```
main                    ← produção, sempre estável
  └── develop           ← desenvolvimento, base para features
       ├── feature/sprint-XX-descricao   ← feature por sprint ou funcionalidade
       ├── fix/descricao-do-bug          ← correção de bugs
       └── hotfix/descricao-critica      ← correção urgente em produção
```

### Fluxo

1. Criar branch a partir de `develop`: `git checkout -b feature/sprint-04-layout-portal`
2. Desenvolver e commitar seguindo as convenções
3. Abrir PR para `develop` com descrição do que foi feito
4. Após review, merge com squash (se muitos commits) ou merge regular
5. No final da sprint, merge `develop` → `main` com tag de versão

### Tags de Versão

```
v0.1.0 — Sprint 1-3 (Fundação)
v0.2.0 — Sprint 4-9 (Portal Adesão)
v0.3.0 — Sprint 10-11 (Portal Área)
v0.4.0 — Sprint 12-13 (Gateway)
v0.5.0 — Sprint 14 (E-mails)
v0.6.0 — Sprint 15-19 (Admin Core)
v0.7.0 — Sprint 20-23 (Admin Financeiro)
v0.8.0 — Sprint 24 (Admin Final)
v1.0.0 — Sprint 25-26 (Homologação + Go-Live)
```

---

## 3. Padrões de Código PHP

### 3.1 PSR-12 + Laravel Pint

O projeto usa **Laravel Pint** como formatter com preset `laravel`.

```bash
# Formatar código
./vendor/bin/pint

# Verificar sem alterar
./vendor/bin/pint --test
```

Configuração `pint.json`:

```json
{
    "preset": "laravel",
    "rules": {
        "concat_space": {
            "spacing": "one"
        },
        "ordered_imports": {
            "sort_algorithm": "alpha"
        }
    }
}
```

### 3.2 Análise Estática — PHPStan Level 6

```bash
./vendor/bin/phpstan analyse
```

Configuração `phpstan.neon`:

```neon
parameters:
    level: 6
    paths:
        - app/
    ignoreErrors: []
```

### 3.3 Naming Conventions

| Contexto    | Padrão                  | Exemplo                                         |
| ----------- | ----------------------- | ----------------------------------------------- |
| Model       | PascalCase, singular    | `Contrato`, `FormandoResponsavel`               |
| Controller  | PascalCase + Controller | `ContratoController`                            |
| Service     | PascalCase + Service    | `ParcelamentoCalculatorService`                 |
| Action      | PascalCase + Action     | `CreateAdesaoFromWizardAction`                  |
| Job         | PascalCase + Job        | `SendPaymentReminderJob`                        |
| Event       | PascalCase (passado)    | `AdesaoConcluida`, `PagamentoConfirmado`        |
| Listener    | PascalCase (ação)       | `SendAdesaoConfirmationEmail`                   |
| Observer    | PascalCase + Observer   | `ParcelaObserver`                               |
| Middleware  | PascalCase              | `AdminActive`, `CheckPermission`                |
| FormRequest | PascalCase + Request    | `StoreContratoRequest`                          |
| Enum        | PascalCase              | `StatusParcela`, `ModalidadePagamento`          |
| DTO         | PascalCase + DTO        | `ParcelamentoCalculoDTO`                        |
| Trait       | PascalCase (has/is)     | `HasAuditLog`, `Filterable`                     |
| Migration   | snake_case              | `create_contratos_table`                        |
| Tabela BD   | snake_case, plural      | `contratos`, `contrato_produtos`                |
| Coluna BD   | snake_case              | `codigo_turma`, `meta_formandos`                |
| Rota (name) | dot notation            | `admin.contratos.store`                         |
| Rota (URI)  | kebab-case              | `/admin/indices-reajuste`                       |
| Blade view  | kebab-case              | `create.blade.php`, `wizard-progress.blade.php` |
| Config key  | snake_case              | `formatura.valor_minimo_parcela`                |
| JS/CSS file | kebab-case              | `apex-charts-init.js`                           |
| Component   | kebab-case (blade)      | `<x-admin.kpi-card>`                            |

### 3.4 Models — Padrão Interno

```php
class Contrato extends Model
{
    // 1. Traits
    use HasFactory, HasAuditLog, SoftDeletes;

    // 2. Constantes e propriedades
    protected $table = 'contratos';

    protected $fillable = [
        'codigo_turma',
        'instituicao_id',
        // ...
    ];

    protected $casts = [
        'status' => ContratoStatus::class,
        'data_inicio_contrato' => 'date',
        'data_evento' => 'date',
        'exige_responsavel_cadastro' => 'boolean',
    ];

    // 3. Relationships (sempre retornar tipo)
    public function instituicao(): BelongsTo
    {
        return $this->belongsTo(Instituicao::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(ContratoProduto::class);
    }

    // 4. Scopes
    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('status', ContratoStatus::ATIVO);
    }

    // 5. Accessors e Mutators
    protected function codigoTurma(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }

    // 6. Business methods (simples, delegam para Services se complexo)
    public function isAtivo(): bool
    {
        return $this->status === ContratoStatus::ATIVO;
    }
}
```

### 3.5 Services — Padrão Interno

```php
// Services são stateless e recebem dependências via injeção
class ParcelamentoCalculatorService
{
    public function __construct(
        private readonly ProgramacaoAtivaService $programacaoService,
        private readonly DescontoAplicavelService $descontoService,
        private readonly PrimeiroVencimentoService $vencimentoService,
    ) {}

    public function calcular(
        ContratoProduto $produto,
        ModalidadePagamento $modalidade,
        int $parcelas,
        ?int $diaVencimento = null,
        ?Carbon $dataAdesao = null,
    ): ParcelamentoCalculoDTO {
        $dataAdesao ??= now();

        // 1. Buscar programação vigente
        $programacao = $this->programacaoService->buscarVigente($produto, $dataAdesao);

        // 2. Calcular desconto
        $desconto = $this->descontoService->resolver($produto, $modalidade, $parcelas, $dataAdesao);

        // 3. Montar resultado
        return new ParcelamentoCalculoDTO(
            totalParcelas: $parcelas,
            valorTotalCentavos: $valorFinal,
            // ...
        );
    }
}
```

### 3.6 Valores Monetários — SEMPRE em Centavos

```php
// ❌ NUNCA usar float para dinheiro
$valor = 1500.99; // ERRADO

// ✅ SEMPRE usar int em centavos
$valor = 150099; // R$ 1.500,99

// Exibição: MoneyHelper::format(150099) → "R$ 1.500,99"
// Input: MoneyHelper::toCents("1.500,99") → 150099
// Banco: coluna INTEGER, nome _centavos ou _cents
```

---

## 4. Padrões de Frontend

### 4.1 Tailwind CSS

- Usar classes utilitárias do Tailwind como padrão
- Customizações no `tailwind.config.js` para cores e fontes do projeto
- Nunca usar `@apply` excessivamente — se precisa de muitos `@apply`, faça um component Blade
- Cores do tema Inspinia definidas como variáveis CSS custom

### 4.2 Componentes Blade

```html
<!-- Uso de componentes -->
<x-admin.kpi-card title="Contratos Ativos" :value="$totalContratos" icon="document" color="blue" />

<x-shared.money-input name="valor" label="Valor (R$)" :value="$produto->valor_centavos" required />

<x-shared.status-badge :enum="$parcela->status" />
```

### 4.3 Livewire

- Componentes Livewire para interações dinâmicas (tabelas, formulários, wizard)
- Blade puro para páginas estáticas ou de visualização
- Nunca misturar Alpine.js complexo com Livewire na mesma interação — deixar o Livewire gerenciar o estado
- Alpine.js apenas para interações visuais locais (toggle menu, collapse, tooltips)

---

## 5. Banco de Dados

### 5.1 Migrations

- Nome descritivo: `create_contratos_table`, `add_origem_to_adesoes_table`
- Sempre definir `down()` funcional
- Índices em colunas de FK, status e campos filtráveis
- Constraints de FK com `onDelete('cascade')` quando faz sentido, `restrict` para entidades principais

### 5.2 Seeders

- `DevelopmentSeeder` — seeder mestre para ambiente de dev (rodar com `--seed`)
- Seeders individuais para cada domínio, chamados pelo `DevelopmentSeeder`
- Dados realistas (nomes BR, CPFs válidos, valores reais de mercado)
- Cenários de teste incluídos (formando adimplente, inadimplente, com extras, etc.)

---

## 6. Testes

### 6.1 Organização

```
tests/
├── Feature/          ← Testes de integração (HTTP, banco real)
│   ├── Admin/        ← Testes do backoffice
│   ├── Portal/       ← Testes do portal
│   └── Webhook/      ← Testes de webhooks
└── Unit/             ← Testes unitários (sem banco, sem HTTP)
    ├── Services/     ← Testes de Services puros
    └── Models/       ← Testes de comportamento de Models
```

### 6.2 Naming de Testes

```php
// Padrão: test_[contexto]_[ação]_[resultado_esperado]
public function test_calculo_parcela_reduz_por_meses_transcorridos(): void
public function test_adesao_rejeita_programacao_expirada(): void
public function test_admin_inativo_nao_consegue_logar(): void
```

### 6.3 Prioridade de Testes

1. **Cálculo dinâmico de parcelas** — 15+ cenários (crítico)
2. **Fluxo de adesão end-to-end** — wizard completo
3. **Webhooks de pagamento** — validação de assinatura, idempotência
4. **ACL e permissões** — acesso autorizado e negado
5. **Regras de responsáveis** — combinações de idade e flags

---

## 7. Logs e Monitoramento

### 7.1 Logs da Aplicação (Laravel Log)

Usar `Log::channel()` com canais separados:

```php
// config/logging.php — canais customizados
'channels' => [
    'gateway' => [
        'driver' => 'daily',
        'path' => storage_path('logs/gateway.log'),
        'days' => 30,
    ],
    'webhook' => [
        'driver' => 'daily',
        'path' => storage_path('logs/webhook.log'),
        'days' => 30,
    ],
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'days' => 90,
    ],
],
```

### 7.2 Auditoria (audit_logs no banco)

- Toda ação crítica grava em `audit_logs` com before/after JSON
- Implementado via Trait `HasAuditLog` + Observers
- Tabela append-only (nunca deletar registros)
- Acessível via tela dedicada no admin e na ficha do formando

### 7.3 Monitoramento

- **Horizon** — Dashboard de filas (`/horizon`)
- **Pulse** — Métricas da aplicação (`/pulse`)
- **Mailpit** — E-mails em desenvolvimento

---

## 8. Segurança

### 8.1 Regras Gerais

- CSRF ativo em todas as rotas web (exceto webhooks)
- Rate limiting em login (5 tentativas / 10 min)
- Sanitização de inputs via FormRequest
- Passwords com `Hash::make()` (bcrypt padrão)
- Tokens de webhook validados com HMAC-SHA256
- Dados sensíveis nunca em logs (CPF completo, senhas, tokens)

### 8.2 Webhooks

```php
// Validação de assinatura HMAC no middleware
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, config('gateway.webhook_secret'));

        if (! hash_equals($expected, $signature)) {
            Log::channel('webhook')->warning('Assinatura inválida', [
                'ip' => $request->ip(),
            ]);
            abort(401);
        }

        return $next($request);
    }
}
```

---

## 9. Cache (Redis)

### 9.1 Quando Cachear

| Dado                            | TTL   | Invalidação                         |
| ------------------------------- | ----- | ----------------------------------- |
| Configurações globais           | 24h   | Ao salvar em ConfiguracaoController |
| Permissões ACL por admin        | 1h    | Ao editar perfil/permissões         |
| Programação ativa de produto    | 1h    | Ao criar/editar programação         |
| Contagem formandos por contrato | 15min | Auto-expira                         |
| Dashboard KPIs                  | 5min  | Auto-expira                         |

### 9.2 Quando NÃO Cachear

- Dados de formando, parcelas (mudam via webhooks), drafts de adesão, dados monetários em checkout

### 9.3 Padrão

```php
// Cache::remember com TTL explícito e prefixo descritivo
$config = Cache::remember('config:global', 86400, fn () =>
    ConfiguracaoGlobal::all()->pluck('valor', 'chave')
);

// Invalidar ao alterar
Cache::forget('config:global');

// NUNCA usar Cache::forever() — sempre com TTL
// Prefixos: config:, acl:, programacao:, dashboard:, contrato:
```

---

## 10. Filas (Horizon)

### 10.1 Filas

| Fila       | Prioridade | Uso                  |
| ---------- | :--------: | -------------------- |
| `gateway`  |    Alta    | Pagamentos           |
| `webhooks` |    Alta    | Webhooks recebidos   |
| `default`  |   Normal   | Jobs gerais          |
| `emails`   |   Normal   | Envio de e-mails     |
| `exports`  |   Baixa    | Relatórios CSV/Excel |
| `pdf`      |   Baixa    | PDFs de termos       |

### 10.2 Retry Policy

- Gateway: 3 tentativas, backoff `[10, 60, 300]`
- E-mails: 3 tentativas, backoff `[30, 60, 120]`
- Exports/PDF: 1 tentativa (reprocessar manualmente)
- **Todo job deve ser idempotente**

---

## 11. Tratamento de Erros

### 11.1 Exceções Customizadas

```php
// ❌ throw new \Exception('Programação não encontrada');
// ✅ throw new ProgramacaoNaoEncontradaException($produto, $dataAdesao);
```

### 11.2 Hierarquia

```
app/Exceptions/
├── BusinessRuleException.php
├── AdesaoException.php → ProgramacaoNaoEncontradaException, DraftExpiradoException
├── PagamentoException.php → GatewayIndisponivelException, WebhookInvalidoException
└── FinanceiroException.php → ParcelaMinimaException, DescontoInvalidoException
```

### 11.3 Handler (bootstrap/app.php)

- `BusinessRuleException` → feedback amigável (422 ou back with error)
- `PagamentoException` → log detalhado no canal gateway + mensagem genérica ao usuário

---

## 12. Performance

### 12.1 Prevenir N+1

```php
// AppServiceProvider::boot()
Model::preventLazyLoading(! app()->isProduction());
```

### 12.2 Eager Loading obrigatório

```php
// ❌ $contratos = Contrato::all(); → $c->instituicao->nome (N+1)
// ✅ $contratos = Contrato::with('instituicao')->get();
```

### 12.3 Índices

Índice em toda coluna usada em WHERE, ORDER BY, JOIN/FK, e campos de status/tipo/data filtráveis.

---

## 13. Localização pt_BR

```env
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
```

```bash
composer require lucascudo/laravel-pt-br-localization --dev
php artisan vendor:publish --tag=laravel-pt-br-localization
```

Datas: `Carbon::setLocale('pt_BR')` + `$data->format('d/m/Y')`.
Moeda: sempre via `MoneyHelper::format()`, nunca `number_format()` direto.

---

## 14. Padrão de E-mails

- **Mailables** → e-mails transacionais (adesão, boleto, lembrete)
- **Notifications** → notificações in-app do admin (sino no header)
- **Sempre assíncrono:** `Mail::to()->queue()` ou Job dedicado na fila `emails`
- Todo e-mail registra em `email_logs` (destinatário, assunto, tipo, status, timestamp)

---

## 15. REGRA OBRIGATÓRIA — Componentização Inspinia

> **Princípio:** nenhum HTML reutilizável é escrito direto em view. Tudo passa por componente Blade catalogado. O Inspinia é apenas **matéria-prima** — o produto final é o nosso catálogo `<x-admin.*>` / `<x-portal.*>` / `<x-shared.*>`.

### 15.1 Fluxo obrigatório antes de escrever HTML

1. **Consultar o mapa** → [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md). Para a tela que você vai codar (ex: §14.4 Contratos), localize os componentes principais e auxiliares.
2. **Consultar o catálogo** → [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md). Para cada componente do mapa, valide o status:
    - **🟢 Vai usar — pronto:** use direto, sem confirmação
    - **🟡 A validar:** **pare**. Registre a decisão na [§16 Decisões de UI](#16-decisões-de-ui) **antes** de continuar
    - **🔴 Não iniciado:** **componentize primeiro** (fluxo 15.2), depois consuma
3. **Consultar a doc técnica** → `docs/template/INSPINIA/<Categoria>/<nome>.md`. Cada componente tem doc com props, dependências JS, exemplos e notas de adaptação.
4. **Consumir o componente** na view/Livewire. Nunca inlinar HTML reutilizável direto.

### 15.2 Componente não existe no catálogo?

Ordem obrigatória:

1. Buscar no parking lot em [`template/INSPINIA/TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §2 — pode estar catalogado mas deferido.
2. Se for promover um item do parking lot ou criar um novo:
    1. Criar doc técnica em `docs/template/INSPINIA/<Categoria>/<nome>.md` (props, código Blade, plugin, exemplo de uso, mapeamento no PRD, classificação, notas)
    2. Inserir na tabela correta de [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md) (§4–12 conforme a categoria)
    3. Criar o `.blade.php` em `resources/views/components/<namespace>/<nome>.blade.php`
    4. Se for referenciado pela tela do mapa, atualizar [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md)
    5. Só agora consumir

### 15.3 Ordem de preferência ao estender a UI

| Preferência | Decisão                                   | Exemplo                                                                  |
| :---------: | ----------------------------------------- | ------------------------------------------------------------------------ |
|     1ª      | **♻️ Reuso** de componente existente      | Usar `x-shared.modal` em 14.13 (já criado na Onda 2)                     |
|     2ª      | **🧩 Composição** de componentes menores  | `x-admin.page-header` que compõe `x-shared.breadcrumb` + slot `$actions` |
|     3ª      | **➕ Variação por prop** no mesmo arquivo | `x-admin.data-table :selectable :exportable :column-search`              |
|     4ª      | **✅ Componente novo** — último recurso   | Só se nenhum dos três primeiros couber                                   |

### 15.4 Regras não negociáveis

- **Páginas completas não são componentes.** Pages viram `resources/views/admin/<modulo>/<tela>.blade.php`, nunca `components/`. Se está tentado a criar `<x-admin.dashboard-14-2-page>`, **pare** — decomponha em componentes menores.
- **Nunca duplicar HTML reutilizável.** Se o mesmo bloco aparece 2× em views diferentes, já é candidato a componente.
- **Dark mode obrigatório.** Usar classes Tailwind `dark:*`. Componente que não renderiza bem em dark é bug.
- **Responsividade obrigatória.** Breakpoints Tailwind `sm:`, `md:`, `lg:`, `xl:`. Admin é desktop-first (1366×768 mín), portal é mobile-first.
- **Consistência de API.** Namespaces `x-admin.*` / `x-portal.*` / `x-shared.*` — nada de `<x-kpi-card>` solto na raiz.
- **Plugin JS** → sempre documentar dependência, inicialização, necessidade de `wire:ignore`, ID único por instância.
- **Forms de campo** → propagar `$errors->has($name)` e `@error` automaticamente. Nunca renderizar erro manualmente em view.
- **CSS customizado proibido** — usar Tailwind (CLAUDE.md §19 "Anti-patterns").

### 15.5 O que NÃO fazer

```
❌ Copiar página inteira do Inspinia para uma view — decompor em componentes
❌ Escrever <div class="card ..."> direto em view — usar <x-shared.card>
❌ Criar <x-admin.contratos-listagem> — é uma view, não componente
❌ Mudar comportamento de um componente existente com JS inline na view — evoluir o componente
❌ Criar componentes com namespace `<x-kpi-card>` solto — usar namespace correto
❌ Ignorar o catálogo "porque é rápido" — abrir catálogo é parte do processo
❌ Inlinar dependência de plugin JS na view — o wrapper do plugin fica no componente
```

### 15.6 Referências obrigatórias

| Documento                                                                  | Quando abrir                                                 |
| -------------------------------------------------------------------------- | ------------------------------------------------------------ |
| [`04-TEMPLATE-MAP-AND-COMPONENTS.md`](04-TEMPLATE-MAP-AND-COMPONENTS.md)   | Ponto de entrada (índice)                                    |
| [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md)     | Saber se um componente existe e seu status                   |
| [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md) | Ver quais componentes uma tela precisa                       |
| [`template/INSPINIA/TRIAGEM.md`](template/INSPINIA/TRIAGEM.md)             | Parking lot + descartados (antes de instalar plugin externo) |
| `template/INSPINIA/<Categoria>/<nome>.md`                                  | Doc técnica do componente (props, deps, código)              |

---

## 16. Decisões de UI

> **Registrar aqui** toda decisão arquitetural de UI que afeta a escolha de componente, plugin ou abordagem. Cada linha deve estar assinada com data. Decisões pendentes viram **bloqueios de sprint** se a tela que consome for iniciada.

### 16.1 Decisões pendentes (🟡 a validar)

|  #  | Item                   | Onde afeta                         | Opções                                                                                                   | Prazo                      | Status |
| :-: | ---------------------- | ---------------------------------- | -------------------------------------------------------------------------------------------------------- | -------------------------- | :----: |
|  1  | **Editor rich text**   | 14.11 Gestão de Termos             | (a) Quill — grátis, parking lot; (b) TinyMCE self-host — grátis, mais features; (c) TinyMCE cloud — pago | Antes de iniciar M06/14.11 |   🟡   |
|  3  | **Slider de parcelas** | 14.14 Simulador de Parcelamento    | (a) `<input type="number">` simples — atende; (b) noUiSlider — parking lot, mais visual                  | Antes de iniciar 14.14     |   🟡   |
|  5  | **Template do Portal** | Todo o portal (wizard, área, etc.) | (a) Preline UI; (b) Tailwind puro; (c) Híbrido Preline + custom                                          | Antes da Sprint 4          |   🟡   |

### 16.2 Como registrar uma decisão tomada

Ao tomar a decisão, mover a linha para §16.3 abaixo seguindo o template:

```markdown
| 1 | Editor rich text | **Quill** | Gratuito, já está no parking lot com doc, integra como Alpine, cobre 100% dos casos do 14.11 (rich text simples com variáveis) | 2026-04-XX | leonardozaneladias |
```

Depois:

1. Atualizar o status no catálogo (`INSPINIA-CATALOGO-COMPONENTES.md`) de 🟡 → 🟢
2. Atualizar o mapa (`INSPINIA-MAPA-TELAS-COMPONENTES.md`) removendo a marcação 🟡
3. Se for um item promovido do parking lot, atualizar `TRIAGEM.md` §2 movendo para §1

### 16.3 Decisões tomadas

|  #  | Item       | Decisão                                                                                               | Motivo                                                                                                                                                                                               | Data       | Por   |
| :-: | ---------- | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------- | ----- |
|  1  | Tags input | **`x-shared.tags-input`** como wrapper semântico sobre `x-shared.select-search`, ambos com Choices.js | Preserva a experiência de chips/tags sem introduzir Tagify ou outra dependência; `select-search` continua para relacionamento/search e `tags-input` fica para listas múltiplas com semântica própria | 2026-04-12 | Codex |

### 16.4 Decisões fora do escopo Inspinia (já tomadas)

Decisões tomadas antes desta seção existir, registradas aqui para auditoria:

|  #  | Item                           | Decisão                                                                                                                                                                                                                                                                         | Motivo                                                                                                                                                                                                   | Registrado em                                                                                                                                                                                                                                                                                                                                                |
| :-: | ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
|  A  | Template admin                 | **Inspinia v5.0 Tailwind** (era "Metronic" no PRD v3.0)                                                                                                                                                                                                                         | Inspinia foi comprado, Metronic descartado                                                                                                                                                               | `CLAUDE.md` §4; `docs/04-TEMPLATE-MAP-AND-COMPONENTS.md` §1                                                                                                                                                                                                                                                                                                  |
|  B  | Skin Inspinia                  | **`default`** + `sidenav-color: dark` / `topbar-color: light`                                                                                                                                                                                                                   | Admin é backoffice corporativo; skin `default` é limpa e neutra                                                                                                                                          | `docs/template/INSPINIA/README.md` §5                                                                                                                                                                                                                                                                                                                        |
|  C  | Loading button                 | **Livewire nativo** (`wire:loading.attr="disabled"` + `wire:target`) — Ladda abandonado                                                                                                                                                                                         | Evita dependência JS extra; Livewire já trata estado de loading                                                                                                                                          | [`loading-button.md`](template/INSPINIA/Components/Feedback/loading-button.md)                                                                                                                                                                                                                                                                               |
|  D  | File upload                    | **Livewire `WithFileUploads`** primário; Dropzone opcional                                                                                                                                                                                                                      | Livewire cobre 90% dos casos sem dependência extra; Dropzone fica disponível para casos com drag-and-drop rico                                                                                           | [`file-upload.md`](template/INSPINIA/Forms/file-upload.md)                                                                                                                                                                                                                                                                                                   |
|  E  | Clipboard                      | **`navigator.clipboard` nativo** (não clipboard.js)                                                                                                                                                                                                                             | API padrão moderna, sem dependência; clipboard.js fica como fallback parking lot                                                                                                                         | [`clipboard.md`](template/INSPINIA/Plugins/clipboard.md)                                                                                                                                                                                                                                                                                                     |
|  F  | DataTables                     | **Um único `x-admin.data-table`** com props para alternar features (`:searchable`, `:exportable`, `:selectable`, `:column-search`, `:date-range`) — em vez de N componentes por variante                                                                                        | Reduz proliferação; variantes parking lot podem ser promovidas se precisar                                                                                                                               | [`data-table.md`](template/INSPINIA/Tables/data-table.md); [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §2.4                                                                                                                                                                                                                                                 |
|  G  | Charts                         | **ApexCharts 5.3.5** para dashboards/relatórios; ECharts fica no parking lot para geo-maps                                                                                                                                                                                      | ApexCharts cobre bar/line/column/pie — os 4 tipos usados. ECharts reservado para geo-maps Brasil (futuro)                                                                                                | [`chart-card.md`](template/INSPINIA/Charts/chart-card.md); [`TRIAGEM.md`](template/INSPINIA/TRIAGEM.md) §2.5                                                                                                                                                                                                                                                 |
|  H  | Status badge                   | **Enum-driven wrapper** — `x-shared.status-badge` recebe um `BackedEnum` com métodos `label()`, `color()` e (opcional) `icon()`                                                                                                                                                 | Garante consistência entre todos os status do sistema (StatusParcela, StatusContrato, etc.) — mudança central em um lugar                                                                                | [`status-badge.md`](template/INSPINIA/Components/Data/status-badge.md)                                                                                                                                                                                                                                                                                       |
|  I  | Money                          | **Inputmask currency alias** + `MoneyHelper::toCents()` (integer centavos)                                                                                                                                                                                                      | PRD §7.3 proíbe float para dinheiro; Inputmask é leve e integra bem com Livewire                                                                                                                         | [`money-input.md`](template/INSPINIA/Forms/money-input.md); `CLAUDE.md` §7.3                                                                                                                                                                                                                                                                                 |
|  J  | Wizard                         | **`x-portal.wizard`** exclusivo do portal (stepper server-driven) — admin usa `accordion` em 14.20                                                                                                                                                                              | Wizard é padrão de fluxo linear (adesão do formando); admin precisa liberdade de navegação (accordion)                                                                                                   | [`wizard.md`](template/INSPINIA/Forms/wizard.md); [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md) §14.20, P1                                                                                                                                                                                                                      |
|  K  | Shell do admin                 | **`<x-admin.layout>`** é a fonte da verdade do shell; `admin/layouts/app.blade.php` e `admin/partials/theme-bootstrap.blade.php` ficam como adapters de compatibilidade                                                                                                         | Resolve a divergência entre catálogo e docs técnicas sem perder suporte a `@extends` / `#[Layout(...)]`                                                                                                  | `CLAUDE.md` §5.1; [`vertical.md`](template/INSPINIA/Layouts/vertical.md)                                                                                                                                                                                                                                                                                     |
|  L  | Escopo do topbar               | **Mega menu removido** do ArtFinal; **notification-bell não é componente autônomo** e permanece composição interna do `x-admin.topbar`                                                                                                                                          | Evita criar componentes sem lastro no catálogo e mantém a navegação principal concentrada no sidebar                                                                                                     | [`topbar.md`](template/INSPINIA/Components/Navigation/topbar.md); [`sidebar.md`](template/INSPINIA/Components/Navigation/sidebar.md)                                                                                                                                                                                                                         |
|  M  | Toast                          | **Família `x-shared.toast` + `x-shared.toast-container`**, com fila/disparo por browser events e helper JS leve; sem Alpine complexo                                                                                                                                            | Mantém o padrão de feedback efêmero reutilizável sem adicionar outra camada reativa fora do necessário                                                                                                   | [`toast.md`](template/INSPINIA/Components/Feedback/toast.md); `CLAUDE.md` §11                                                                                                                                                                                                                                                                                |
|  N  | Tabs                           | **`x-shared.tabs` permanece nome guarda-chuva**, mas a API Blade final é a composição `x-shared.tab-nav` + `x-shared.tab-trigger` + `x-shared.tab-panel`                                                                                                                        | Blade não lida bem com slots dinâmicos; a composição explícita é mais previsível e documentável                                                                                                          | [`tabs.md`](template/INSPINIA/Components/UI/tabs.md); [`INSPINIA-CATALOGO-COMPONENTES.md`](INSPINIA-CATALOGO-COMPONENTES.md) §5                                                                                                                                                                                                                              |
|  O  | Confirmações simples           | **Não existe `x-admin.confirm-modal`**. Confirmar ações destrutivas/irreversíveis via `x-shared.confirm-dialog`; usar `x-shared.modal` apenas para conteúdo contextual/rico                                                                                                     | Evita APIs concorrentes para o mesmo problema e separa "prompt simples" de "modal completo"                                                                                                              | [`confirm-dialog.md`](template/INSPINIA/Components/Feedback/confirm-dialog.md); [`modal.md`](template/INSPINIA/Components/UI/modal.md)                                                                                                                                                                                                                       |
|  P  | Timeline reutilizável          | **Não existe `x-admin.timeline-item`**. O único artefato reutilizável de timeline no escopo oficial é `x-admin.timeline-table`; a aba de histórico usa referência visual de página, não componente genérico                                                                     | Evita abstração prematura sem lastro em catálogo/doc                                                                                                                                                     | [`custom-table.md`](template/INSPINIA/Tables/custom-table.md); [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md) §14.7 e §14.12                                                                                                                                                                                                     |
|  Q  | Família de pickers             | **`x-shared.date-picker` e `x-shared.date-range-picker` coexistem** como componentes irmãos, ambos apoiados pelo mesmo helper JS de Flatpickr                                                                                                                                   | Mantém APIs explícitas para data simples e intervalo, sem obrigar prop ambígua ou componente único inchado                                                                                               | [`date-picker.md`](template/INSPINIA/Forms/date-picker.md); [`date-range-picker.md`](template/INSPINIA/Forms/date-range-picker.md)                                                                                                                                                                                                                           |
|  R  | File upload                    | **Um único `x-shared.file-upload`** com modos `livewire` e `dropzone`                                                                                                                                                                                                           | Garante upload simples como padrão e preserva drag-and-drop rico quando o contexto realmente pedir                                                                                                       | [`file-upload.md`](template/INSPINIA/Forms/file-upload.md)                                                                                                                                                                                                                                                                                                   |
|  S  | Escopo do Batch 6              | **`x-admin.data-table` é o hub de listagens e actions; `x-shared.list-group` permanece a composição vertical auxiliar. Não existem `x-admin.filter-panel`, `x-admin.action-dropdown`, `x-admin.bulk-actions` nem `x-admin.export-buttons` como componentes autônomos oficiais** | Evita duplicar responsabilidades já cobertas por `x-admin.drawer`, `x-shared.dropdown` e pelas props/capacidades do `x-admin.data-table` (`:exportable`, `:selectable`, `:column-search`, `:date-range`) | [`data-table.md`](template/INSPINIA/Tables/data-table.md); [`dropdown.md`](template/INSPINIA/Components/UI/dropdown.md); [`drawer.md`](template/INSPINIA/Components/UI/drawer.md); [`list-group.md`](template/INSPINIA/Components/UI/list-group.md)                                                                                                          |
|  T  | Escopo do Batch 7              | **`x-admin.kpi-card`, `x-admin.chart-card` e `x-shared.progress-bar` compõem o lote oficial de wrappers de dashboard. Não existem `metric`, `widget`, `formando-card` nem `parcela-row` como componentes oficiais neste ciclo**                                                 | Só `kpi-card`, `chart-card` e `progress-bar` têm lastro suficiente nas fontes oficiais; os demais seguem como composição/view específica até ganharem doc e catálogo próprios                            | [`kpi-card.md`](template/INSPINIA/Components/Data/kpi-card.md); [`chart-card.md`](template/INSPINIA/Charts/chart-card.md); [`progress.md`](template/INSPINIA/Components/UI/progress.md)                                                                                                                                                                      |
|  U  | Tooltip                        | **`x-shared.tooltip`** é o padrão oficial para tooltips textuais simples; conteúdo rico continua fora do escopo e não vira popover nesta fase                                                                                                                                   | O mapa já usa tooltip em telas reais e a doc própria é suficiente para fechar a decisão; Alpine inline deixa de ser a fonte oficial                                                                      | [`tooltip.md`](template/INSPINIA/Components/UI/tooltip.md); [`INSPINIA-MAPA-TELAS-COMPONENTES.md`](INSPINIA-MAPA-TELAS-COMPONENTES.md)                                                                                                                                                                                                                       |
|  V  | Escopo do Batch 8              | **Entram `x-shared.accordion`, `x-shared.modal`, `x-shared.tooltip` e `x-admin.sortable-list`. Não entram `x-shared.offcanvas`, `x-shared.popover` nem `x-admin.programacao-timeline` como componentes autônomos oficiais**                                                     | `drawer` já cobre offcanvas; `popover` não tem doc/catálogo oficial; a programação continua em `timeline-table` + `modal`                                                                                | [`accordion.md`](template/INSPINIA/Components/UI/accordion.md); [`modal.md`](template/INSPINIA/Components/UI/modal.md); [`tooltip.md`](template/INSPINIA/Components/UI/tooltip.md); [`sortable.md`](template/INSPINIA/Plugins/sortable.md)                                                                                                                   |
|  W  | Remanescentes base             | **`x-shared.pagination` é um tema do paginator do Laravel (`vendor.pagination.*`), enquanto `x-shared.spinner`, `x-shared.static-table` e `x-admin.timeline-table` seguem como componentes Blade anônimos oficiais**                                                            | Fecha a ambiguidade final dos remanescentes sem forçar a paginação para uma API Blade artificial e preserva os demais como peças reutilizáveis normais                                                   | [`pagination.md`](template/INSPINIA/Components/UI/pagination.md); [`spinner.md`](template/INSPINIA/Components/UI/spinner.md); [`static-table.md`](template/INSPINIA/Tables/static-table.md); [`custom-table.md`](template/INSPINIA/Tables/custom-table.md)                                                                                                   |
|  X  | Rodada final de charts/plugins | **`chart-bar`, `chart-line`, `chart-column` e `chart-pie` são wrappers Blade finos sobre `chart-card` + `charts.js`; `copy-button` usa helper nativo sem Alpine; `password-strength-meter` vira subcomponente oficial do `password-input` e também funciona standalone**        | Fecha os remanescentes do catálogo sem abrir segunda arquitetura de gráficos ou plugins, reaproveitando `chart-card`, `forms.js` e o sistema de toast já existente                                       | [`bar.md`](template/INSPINIA/Charts/ApexCharts/bar.md); [`line.md`](template/INSPINIA/Charts/ApexCharts/line.md); [`column.md`](template/INSPINIA/Charts/ApexCharts/column.md); [`pie.md`](template/INSPINIA/Charts/ApexCharts/pie.md); [`clipboard.md`](template/INSPINIA/Plugins/clipboard.md); [`pass-meter.md`](template/INSPINIA/Plugins/pass-meter.md) |

### 16.5 Changelog

| Data       | Descrição                                                                                                                                                                                                                                                  |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 2026-04-11 | Seções §15 e §16 criadas — regra obrigatória de componentização + registro de decisões de UI (5 pendentes + 10 já tomadas)                                                                                                                                 |
| 2026-04-11 | Escopo oficial do Batch 3 consolidado: `toast`, `tabs`, `confirm-dialog`, `loading-button`, `empty-state` e `status-badge` fechados; `x-admin.confirm-modal` e `x-admin.timeline-item` removidos do escopo oficial                                         |
| 2026-04-12 | Escopo oficial do Batch 5 consolidado: `tags-input` promovido como wrapper semântico de `select-search`; `date-picker` e `date-range-picker` fechados como componentes irmãos; `file-upload` oficializado com modos `livewire` e `dropzone`                |
| 2026-04-12 | Escopo oficial do Batch 6 consolidado: `x-admin.data-table` + `x-shared.list-group`/`list-group-item` aprovados; `filter-panel`, `action-dropdown`, `bulk-actions` e `export-buttons` rebaixados para composição/capacidade, sem componente autônomo       |
| 2026-04-12 | Escopo oficial do Batch 7 consolidado: `x-admin.kpi-card`, `x-admin.chart-card` e `x-shared.progress-bar` aprovados; `metric`, `widget`, `formando-card` e `parcela-row` saíram do lote por falta de doc/catálogo oficial próprio                          |
| 2026-04-12 | Escopo oficial do Batch 8 consolidado: `x-shared.accordion`, `x-shared.modal`, `x-shared.tooltip` e `x-admin.sortable-list` aprovados; `offcanvas`, `popover` e `programacao-timeline` ficaram fora do escopo oficial                                      |
| 2026-04-12 | Rodada de fechamento dos remanescentes base concluída: `x-shared.pagination`, `x-shared.spinner`, `x-shared.static-table` e `x-admin.timeline-table` implementados; paginação registrada como tema global do Laravel Paginator                             |
| 2026-04-12 | Rodada final concluída: `chart-bar`, `chart-line`, `chart-column`, `chart-pie`, `copy-button` e `password-strength-meter` implementados; charts unificados em `resources/js/admin/charts.js` e o meter oficializado como subcomponente do `password-input` |
