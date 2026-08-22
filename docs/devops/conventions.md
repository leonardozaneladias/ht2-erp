# Convenções e Padrões

**Versão:** 1.0.0

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

| Escopo   | Área                                    |
| -------- | --------------------------------------- |
| `admin`  | Backoffice administrativo               |
| `auth`   | Autenticação (guard admin)              |
| `infra`  | DDEV, Docker, CI, deploy                |
| `models` | Models, migrations, seeders             |
| `rh`     | Módulo de RH (`ht2ml/extensao-rh`)       |
| `docs`   | Documentação                            |
| `ui`     | Interface, componentes visuais          |
| `skills` | Skills e automações `.claude`/`.agents` |

### 1.4 Regras

- Descrição em **português**, **imperativo**, **minúscula** após os dois-pontos
- Máximo 72 caracteres na primeira linha
- Sem ponto final na primeira linha
- Corpo do commit separado por linha em branco
- Referências a issues quando aplicável

### 1.5 Exemplos

```
feat(admin): implementar listagem de clientes
fix(admin): corrigir filtro de status na tabela de usuários
refactor(admin): extrair lógica de exportação para service dedicado
docs(docs): documentar convenções de código
test(admin): adicionar cenários de teste para cadastro de cliente
chore(infra): atualizar PHP para 8.4 no DDEV
perf(admin): otimizar query de listagem de clientes com eager loading
```

### 1.6 Validação local (hooks + Composer)

Os hooks **Husky** já rodam **`commitlint`** em todo `git commit` (`.husky/commit-msg`). Para validar manualmente **a última mensagem** já registrada:

```bash
composer run lint:last-commit-msg
```

**A mensagem é validada no commit:** o hook `commit-msg` (Husky) roda o `commitlint`
contra o `commitlint.config.cjs` (tipos, escopos e limites permitidos). Escreva a mensagem
no formato `tipo(escopo): descrição` — commits fora do padrão são barrados na hora.

O hook **`prepare-commit-msg`** (Husky):

- Reescreve na **primeira linha** escopos inválidos **`(database|db|sql)` → `(models)`** para alinhar ao `commitlint`;
- Remove **`Co-authored-by`** que mencione **Cursor** (IDE).

A sugestão _Generate Commit Message_ do Cursor (ou de extensões Git) **não valida** contra o `commitlint` — confira o formato antes de confirmar (o hook `commit-msg` rejeita o que estiver fora do padrão).

---

## 2. Branching Strategy (Git Flow Simplificado)

```
main                    ← produção, sempre estável
  └── develop           ← desenvolvimento, base para features
       ├── feature/descricao   ← feature ou funcionalidade
       ├── fix/descricao-do-bug          ← correção de bugs
       └── hotfix/descricao-critica      ← correção urgente em produção
```

### Fluxo

1. Criar branch a partir de `develop`: `git checkout -b feature/listagem-clientes`
2. Desenvolver e commitar seguindo as convenções
3. Abrir PR para `develop` com descrição do que foi feito
4. Após review, merge com squash (se muitos commits) ou merge regular
5. Ao concluir uma entrega, merge `develop` → `main` com tag de versão (SemVer)

### Tags de Versão

Seguir [SemVer](https://semver.org/lang/pt-BR/): `MAJOR.MINOR.PATCH`.

- `MAJOR` — mudanças incompatíveis.
- `MINOR` — novas funcionalidades retrocompatíveis.
- `PATCH` — correções retrocompatíveis.

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

| Contexto    | Padrão                  | Exemplo                                    |
| ----------- | ----------------------- | ------------------------------------------ |
| Model       | PascalCase, singular    | `Cliente`, `UsuarioPerfil`                 |
| Controller  | PascalCase + Controller | `ClienteController`                        |
| Service     | PascalCase + Service    | `ClienteImportService`                     |
| Action      | PascalCase + Action     | `CreateClienteAction`                      |
| Job         | PascalCase + Job        | `SendWelcomeEmailJob`                      |
| Event       | PascalCase (passado)    | `ClienteCriado`, `UsuarioAtivado`          |
| Listener    | PascalCase (ação)       | `SendClienteWelcomeEmail`                  |
| Observer    | PascalCase + Observer   | `ClienteObserver`                          |
| Middleware  | PascalCase              | `AdminActive`, `CheckPermission`           |
| FormRequest | PascalCase + Request    | `StoreClienteRequest`                      |
| Enum        | PascalCase              | `StatusCliente`, `TipoUsuario`             |
| DTO         | PascalCase + DTO        | `ClienteDTO`                               |
| Trait       | PascalCase (has/is)     | `HasAuditLog`, `Filterable`                |
| Migration   | snake_case              | `create_clientes_table`                    |
| Tabela BD   | snake_case, plural      | `clientes`, `cliente_enderecos`            |
| Coluna BD   | snake_case              | `nome_completo`, `data_cadastro`           |
| Rota (name) | dot notation            | `admin.clientes.store`                     |
| Rota (URI)  | kebab-case              | `/admin/clientes`                          |
| Blade view  | kebab-case              | `create.blade.php`, `data-table.blade.php` |
| Config key  | snake_case              | `app.itens_por_pagina`                     |
| JS/CSS file | kebab-case              | `apex-charts-init.js`                      |
| Component   | kebab-case (blade)      | `<x-admin.kpi-card>`                       |

### 3.4 Models — Padrão Interno

```php
class Cliente extends Model
{
    // 1. Traits
    use HasFactory, HasAuditLog, SoftDeletes;

    // 2. Constantes e propriedades
    protected $table = 'clientes';

    protected $fillable = [
        'nome_completo',
        'email',
        // ...
    ];

    protected $casts = [
        'status' => StatusCliente::class,
        'data_cadastro' => 'date',
        'ativo' => 'boolean',
    ];

    // 3. Relationships (sempre retornar tipo)
    public function enderecos(): HasMany
    {
        return $this->hasMany(ClienteEndereco::class);
    }

    // 4. Scopes
    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('status', StatusCliente::ATIVO);
    }

    // 5. Accessors e Mutators
    protected function nomeCompleto(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucwords(mb_strtolower($value)),
        );
    }

    // 6. Business methods (simples, delegam para Services se complexo)
    public function isAtivo(): bool
    {
        return $this->status === StatusCliente::ATIVO;
    }
}
```

### 3.5 Services — Padrão Interno

```php
// Services são stateless e recebem dependências via injeção
class ClienteImportService
{
    public function __construct(
        private readonly ClienteValidator $validator,
        private readonly EnderecoResolver $enderecoResolver,
    ) {}

    public function importar(ClienteImportDTO $dados): Cliente
    {
        // 1. Validar dados de entrada
        $this->validator->validar($dados);

        // 2. Resolver endereço a partir do CEP
        $endereco = $this->enderecoResolver->resolver($dados->cep);

        // 3. Persistir e retornar
        return Cliente::create([
            'nome_completo' => $dados->nomeCompleto,
            'email' => $dados->email,
            // ...
        ]);
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
<x-admin.kpi-card title="Clientes Ativos" :value="$totalClientes" icon="users" color="blue" />

<x-shared.money-input name="valor" label="Valor (R$)" :value="$item->valor_centavos" required />

<x-shared.status-badge :enum="$cliente->status" />
```

### 4.3 Livewire

- Componentes Livewire para interações dinâmicas (tabelas, formulários)
- Blade puro para páginas estáticas ou de visualização
- Nunca misturar Alpine.js complexo com Livewire na mesma interação — deixar o Livewire gerenciar o estado
- Alpine.js apenas para interações visuais locais (toggle menu, collapse, tooltips)

### 4.4 Padrões de UX (obrigatórios em todo módulo novo)

Decididos no fechamento da fase base. Todo CRUD/tela nova segue estas regras — divergência exige decisão registrada.

**Botões — `x-shared.button` é a única API**

- Proibido `class="btn btn-*"` direto em views. O componente cobre âncoras via prop `href` (com `wire:navigate`).
- Botão com loading: `x-shared.loading-button` (ou o slot com `wire:loading` dentro de `x-shared.button`).

**Confirmação destrutiva — sempre pelo bridge SweetAlert2**

- Proibido `wire:confirm` (usa `window.confirm` nativo: sem dark mode, sem PT-BR, visual inconsistente).
- Padrão: o componente expõe `solicitarXxx()` que faz `$this->dispatch('confirm', title: ..., text: ..., destructive: true, onConfirm: 'modulo::evento', params: [...])`; o método real leva `#[On('modulo::evento')]`. Exemplos: `UsuariosTable::solicitarToggleStatus()`, `IndexAuditoria::solicitarExpurgo()`.
- O texto sempre nomeia a consequência ("perderá o acesso", "não pode ser desfeita").

**Formulários longos — cards vs abas**

- **Cards empilhados** = padrão default (fluxo "preencher tudo e salvar"). Exemplo: Form de Empresa.
- **Abas** = somente quando pelo menos um grupo tem ação de salvar própria ou só existe após persistência (modo edição). Exemplo: Form de Usuário (Empresas/Acessos salvam separado).
- Com abas, todo `tab-trigger` recebe `:has-error="$errors->hasAny([...campos da aba...])"` — erro em aba inativa precisa de feedback visível.
- Rodapé de form sempre via `<x-admin.form-footer :cancel-href="..." :label="..." />`.
- Para salvar com **Enter**, envolva o corpo em `<form wire:submit="salvar" class="space-y-6">` e passe `submit` ao rodapé (`<x-admin.form-footer ... submit />`): isso torna o botão primário `type="submit"`, disparado pelo `<form>` (Enter ou clique). Sem `submit`, o rodapé mantém o disparo por `wire:click` (retrocompatível). O gerador `make:modulo` e o módulo Exemplo já nascem nesse padrão.

**Breadcrumbs**

- Toda página de nível ≥ 2 (forms, detalhes) passa `:breadcrumbs` explícito no `x-admin.page-header` (`Admin > Módulo > {nome do registro}`). O fallback automático só é aceitável em páginas de nível 1 (listas/hubs).

**Senhas**

- Todo campo de **definição** de senha (criar, trocar, resetar, aceitar convite, wizard) usa `x-shared.password-input` com `with-meter`. Campos de senha atual/login não usam meter.

**Empty states e skeletons**

- Toda coleção visível tem `x-shared.empty-state` do catálogo, com CTA quando o usuário tem permissão de criar.
- Componentes pesados não-imediatos usam `#[Lazy]` + `placeholder()` com `x-shared.skeleton` (ver presets no catálogo).

### 4.5 Menu lateral — registry no código + personalizações no banco

O menu do admin tem duas camadas, mescladas pelo `App\Services\Admin\Menu\MenuService` (cache de 10 min, invalidado nas Actions de menu):

- **Registro** (`config/admin-menu.php`) — fonte de verdade dos módulos, sempre **FLAT**. Toda seção e item exigem uma **`key` estável** (o serviço lança `LogicException` sem ela); o item declara `label`, `icon` (tabler), `route`, `permission` e `active`. Módulo novo = item novo aqui — ele aparece automaticamente na sidebar e na tela de gestão.
- **Personalizações** (`menu_personalizacoes`, tela `/admin/menus`, permissão `configuracoes.menus`) — ordem, label, ícone, container e `ativo` por cima do registro, além de **seções custom** e **grupos (submenus)** criados pela tela (`e_custom = true`, keys `secao-*`/`grupo-*` geradas por slug). Valores iguais ao padrão viram `null` (linha 100% padrão é removida); key que sumiu do config vira personalização **órfã** (ignorada na sidebar, listada na gestão para limpeza) — customs nunca são órfãs.
- **Grupos** são apresentação pura: sem rota/permissão, só aparecem na sidebar quando têm filho visível ao usuário; inativo esconde o grupo e os filhos. Excluir grupo/seção custom devolve os registros ao fallback natural. Um item pertence a `grupo_key` (prioridade) > `secao_key` > seção natural do config.
- **Disposição padrão do starter kit**: `AplicarMenuPadraoAction` — grupos Organização/Segurança (Administração), Cadastros (Tabelas Auxiliares) e a seção **Recursos Humanos** (itens de uso diário soltos + grupos temáticos, constantes `ITENS_SOLTOS_RH`/`GRUPOS_RH`). Idempotente e não-destrutiva (no-op se já existe grupo; `firstOrCreate` por linha). Chamada pelo `MenuPadraoSeeder` (dev) e pelo `ConcluirSetupAction` (produção — deploy não roda seeders); instalações já organizadas recebem só o arranjo de RH via `aplicarSecaoRh()` (data migration `2026_07_07_100000`, no-op quando o grupo `grupo-rh-*` já existe).

Regras:

- **Visibilidade por usuário é o ACL**, nunca uma segunda flag: o item é exibido se o usuário `can(permission)`. Os toggles "Visível/Oculto" por perfil na tela de gestão concedem/revogam a permissão no perfil (via `AlternarPermissaoPerfilAction`, mesmos guards do hub) — afetam menu **e** páginas.
- `ativo = false` é decisão global (some para todos, inclusive no preview dev/components); o acesso às rotas continua regido pelo ACL.
- Ícones de menu são **build-time** (iconify/tailwind escaneia literais): a tela só aceita a lista curada `App\Support\Menu\IconesMenu` — para oferecer um ícone novo, adicione-o lá (o grid de sugestões o renderiza literalmente, garantindo o CSS no bundle).
- Nunca renomeie uma `key` do config sem migrar a personalização correspondente.

---

## 5. Banco de Dados

### 5.1 Migrations

- Nome descritivo: `create_clientes_table`, `add_origem_to_clientes_table`
- Sempre definir `down()` funcional
- Índices em colunas de FK, status e campos filtráveis
- Constraints de FK com `onDelete('cascade')` quando faz sentido, `restrict` para entidades principais

### 5.2 Seeders

- `DatabaseSeeder` — seeder mestre para ambiente de dev (rodar com `--seed`)
- Seeders individuais para cada domínio, chamados pelo `DatabaseSeeder`
- Dados realistas (nomes BR, CPFs/CNPJs válidos)
- Usuários de desenvolvimento padrão: `admin@example.com` (role `super-admin`) e `gestor@example.com` (role `gestor`), senha `password`

---

## 6. Testes

### 6.1 Organização

```
tests/
├── Feature/          ← Testes de integração (HTTP, banco real)
│   └── Admin/        ← Testes do backoffice
└── Unit/             ← Testes unitários (sem banco, sem HTTP)
    ├── Services/     ← Testes de Services puros
    └── Models/       ← Testes de comportamento de Models
```

### 6.2 Naming de Testes

```php
// Padrão: test_[contexto]_[ação]_[resultado_esperado]
public function test_cliente_e_criado_com_dados_validos(): void
public function test_listagem_clientes_filtra_por_status(): void
public function test_admin_inativo_nao_consegue_logar(): void
```

### 6.3 Prioridade de Testes

1. **Autenticação e guard admin** — login, logout, usuário inativo
2. **ACL e permissões** — acesso autorizado e negado
3. **Services com regra de negócio** — pelo menos 1 teste por Service crítico
4. **CRUD dos módulos** — criação, edição, exclusão, validação de input
5. **Auditoria** — registro de before/after nas ações sensíveis

---

## 7. Logs e Monitoramento

### 7.1 Logs da Aplicação (Laravel Log)

Usar `Log::channel()` com canais separados:

```php
// config/logging.php — canais customizados
'channels' => [
    'integration' => [
        'driver' => 'daily',
        'path' => storage_path('logs/integration.log'),
        'days' => 30,
    ],
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'days' => 90,
    ],
],
```

### 7.2 Auditoria (activity_log no banco)

**Regra central:** o trait `App\Models\Concerns\Auditavel` é a **verdade crua de
atributos** (created/updated/deleted/restored com diff em `attribute_changes`);
Actions só logam **eventos de domínio** que o diff não expressa (sync de
pivôs/roles, settings, auth, resumos de operação em massa).

- **Todo model novo usa `Auditavel`** — o arch test em `tests/Arch.php` quebra a
  suíte até o model usar o trait ou entrar na whitelist com justificativa.
- O trait usa `logAll()` + `logOnlyDirty()`; sensíveis globais (password, tokens,
  segredos 2FA) ficam em `activitylog.default_except_attributes`. Overrides por
  model: `atributosNaoAuditados()` (ruído/segredos do model),
  `nomeLogAuditoria()` (default = tabela), `descricaoEventoAuditoria()` e
  `rotuloAuditoria()` (rótulo humano gravado em `properties.subject_label`).
- O contexto é carimbado centralmente em `CarimbarContextoNaAtividade`
  (`Activity::creating`): empresa/filial derivadas do subject (fallback tenant
  ativo), causer pelo guard admin, `properties.contexto` (ip/user_agent — só em
  request real) e `impersonado_por`.
- **Gotchas:** mass update/delete via Query Builder (`Model::where()->update()`)
  **não dispara eventos** — itere os models ou logue manualmente; mudanças em
  pivôs (sync/attach/detach) exigem log de domínio manual; em operação que
  reescreve PII (anonimização LGPD), chame `disableLogging()` antes do save.
- Tabela append-only (nunca deletar registros; exceções sancionadas: retenção
  `dias_retencao_logs` e mascaramento LGPD na anonimização)
- Acessível via tela dedicada no admin (`/admin/auditoria`)

### 7.3 Monitoramento

- **Horizon** — Dashboard de filas (`/horizon`)
- **Pulse** — Métricas da aplicação (`/pulse`)
- **Mailpit** — E-mails em desenvolvimento

---

## 8. Segurança

### 8.1 Regras Gerais

- CSRF ativo em todas as rotas web
- Rate limiting em login (5 tentativas / 10 min)
- Sanitização de inputs via FormRequest
- Passwords com `Hash::make()` (bcrypt padrão)
- Autorização por recurso via Policies + `spatie/laravel-permission`
- Dados sensíveis nunca em logs (CPF completo, senhas, tokens)

### 8.2 Autorização

```php
// Sempre validar acesso via Policy/Gate antes da ação
$this->authorize('update', $cliente);

// Ou via Blade directives
@can('clientes.edit')
    <x-shared.button>Editar</x-shared.button>
@endcan
```

---

## 9. Cache (Redis)

### 9.1 Quando Cachear

| Dado                       | TTL  | Invalidação                         |
| -------------------------- | ---- | ----------------------------------- |
| Configurações globais      | 24h  | Ao salvar em ConfiguracaoController |
| Permissões ACL por usuário | 1h   | Ao editar perfil/permissões         |
| Listas de apoio (lookups)  | 1h   | Ao criar/editar o registro          |
| Dashboard KPIs             | 5min | Auto-expira                         |

### 9.2 Quando NÃO Cachear

- Dados que mudam com frequência, registros em edição, dados sensíveis

### 9.3 Padrão

```php
// Cache::remember com TTL explícito e prefixo descritivo
$config = Cache::remember('config:global', 86400, fn () =>
    ConfiguracaoGlobal::all()->pluck('valor', 'chave')
);

// Invalidar ao alterar
Cache::forget('config:global');

// NUNCA usar Cache::forever() — sempre com TTL
// Prefixos: config:, acl:, dashboard:
```

---

## 10. Filas (Horizon)

### 10.1 Filas

| Fila      | Prioridade | Uso                  |
| --------- | :--------: | -------------------- |
| `default` |   Normal   | Jobs gerais          |
| `emails`  |   Normal   | Envio de e-mails     |
| `exports` |   Baixa    | Relatórios CSV/Excel |
| `pdf`     |   Baixa    | Geração de PDFs      |

### 10.2 Retry Policy

- E-mails: 3 tentativas, backoff `[30, 60, 120]`
- Exports/PDF: 1 tentativa (reprocessar manualmente)
- **Todo job deve ser idempotente**

---

## 11. Tratamento de Erros

### 11.1 Exceções Customizadas

```php
// ❌ throw new \Exception('Cliente não encontrado');
// ✅ throw new ClienteNaoEncontradoException($id);
```

### 11.2 Hierarquia

```
app/Exceptions/
├── BusinessRuleException.php
└── ClienteException.php → ClienteNaoEncontradoException, ClienteDuplicadoException
```

### 11.3 Handler (bootstrap/app.php)

- `BusinessRuleException` → feedback amigável (422 ou back with error)
- Exceções de integração → log detalhado no canal `integration` + mensagem genérica ao usuário

---

## 12. Performance

### 12.1 Prevenir N+1

```php
// AppServiceProvider::boot()
Model::preventLazyLoading(! app()->isProduction());
```

### 12.2 Eager Loading obrigatório

```php
// ❌ $clientes = Cliente::all(); → $c->enderecoPrincipal->cidade (N+1)
// ✅ $clientes = Cliente::with('enderecoPrincipal')->get();
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

- **Mailables** → e-mails transacionais (boas-vindas, redefinição de senha, notificação)
- **Notifications** → notificações in-app do admin (sino no header)
- **Sempre assíncrono:** `Mail::to()->queue()` ou Job dedicado na fila `emails`
- Quando houver auditoria de envio, registrar em `email_logs` (destinatário, assunto, tipo, status, timestamp)

---

## 15. Componentização (Inspinia + Blade)

> **Princípio:** nenhum HTML reutilizável é escrito direto em view. Tudo passa por componente Blade catalogado. O Inspinia é apenas **matéria-prima** — o produto final é o catálogo `<x-admin.*>` / `<x-shared.*>`.

### 15.1 Fluxo obrigatório antes de escrever HTML

1. **Consultar o catálogo** → [`docs/template/INSPINIA/CATALOGO-COMPONENTES.md`](../template/INSPINIA/CATALOGO-COMPONENTES.md) e validar o status:
    - 🟢 **pronto:** use direto, sem confirmação
    - 🟡 **a validar:** pare e registre a decisão antes de continuar
    - 🔴 **não iniciado:** componentize primeiro (15.2), depois consuma
2. **Consumir o componente** na view/Livewire. Nunca inlinar HTML reutilizável direto.

### 15.2 Componente não existe no catálogo?

1. Criar a doc técnica em `docs/template/INSPINIA/<Categoria>/<nome>.md` (props, código Blade, plugin, exemplo de uso, classificação, notas).
2. Inserir na tabela correta de [`CATALOGO-COMPONENTES.md`](../template/INSPINIA/CATALOGO-COMPONENTES.md).
3. Criar o `.blade.php` em `resources/views/components/<namespace>/<nome>.blade.php`.
4. Só então consumir.

### 15.3 Ordem de preferência ao estender a UI

| Preferência | Decisão                                   | Exemplo                                                                  |
| :---------: | ----------------------------------------- | ------------------------------------------------------------------------ |
|     1ª      | **♻️ Reuso** de componente existente      | Usar `x-shared.modal`                                                    |
|     2ª      | **🧩 Composição** de componentes menores  | `x-admin.page-header` que compõe `x-shared.breadcrumb` + slot `$actions` |
|     3ª      | **➕ Variação por prop** no mesmo arquivo | `x-admin.data-table :selectable :exportable :column-search`              |
|     4ª      | **✅ Componente novo** — último recurso   | Só se nenhum dos três primeiros couber                                   |

### 15.4 Regras não negociáveis

- **Páginas completas não são componentes.** Pages viram `resources/views/admin/<modulo>/<tela>.blade.php`, nunca `components/`.
- **Nunca duplicar HTML reutilizável.** Se o mesmo bloco aparece 2× em views diferentes, já é candidato a componente.
- **Dark mode obrigatório.** Usar classes Tailwind `dark:*`.
- **Responsividade obrigatória.** Admin é desktop-first (1366×768 mín).
- **Consistência de API.** Namespaces `x-admin.*` / `x-shared.*` — nada de `<x-kpi-card>` solto na raiz.
- **Plugin JS** → sempre documentar dependência, inicialização, necessidade de `wire:ignore`, ID único por instância.
- **Forms de campo** → propagar `$errors->has($name)` e `@error` automaticamente. Nunca renderizar erro manualmente em view.
- **CSS customizado proibido** — usar Tailwind (CLAUDE.md §19 "Anti-patterns").

### 15.5 O que NÃO fazer

```
❌ Copiar página inteira do Inspinia para uma view — decompor em componentes
❌ Escrever <div class="card ..."> direto em view — usar <x-shared.card>
❌ Criar <x-admin.clientes-listagem> — é uma view, não componente
❌ Mudar comportamento de um componente existente com JS inline na view — evoluir o componente
❌ Criar componentes com namespace `<x-kpi-card>` solto — usar namespace correto
❌ Ignorar o catálogo "porque é rápido" — abrir catálogo é parte do processo
❌ Inlinar dependência de plugin JS na view — o wrapper do plugin fica no componente
```
