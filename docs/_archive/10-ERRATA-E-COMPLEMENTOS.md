# Errata, Correções e Complementos

**Projeto:** Portal ArtFinal  
**Data da Revisão:** 09/04/2026  
**Revisor:** Revisão completa de consistência entre todos os 9 documentos, PRD v3.0, INFRA.md e análise do Inspinia

---

## PARTE 1 — ERRATA E CORREÇÕES OBRIGATÓRIAS

### 🔴 Correção 1: Versão do Laravel — INFRA.md diz 13.x, Docs dizem 12

**O problema:** O `INFRA.md` (documento de infraestrutura do Leonardo) especifica **Laravel 13.x**. Todos os outros documentos que criamos referenciam **Laravel 12**.

**Onde corrigir:**

| Documento                  | Linha/Seção      | Atual                | Corrigir Para        |
| -------------------------- | ---------------- | -------------------- | -------------------- |
| `01-ARCHITECTURE-GUIDE.md` | Linha 7 (header) | Laravel 12           | Laravel 13           |
| `01-ARCHITECTURE-GUIDE.md` | Seção 2 (árvore) | —                    | Confirmar versão     |
| `02-CONVENTIONS.md`        | —                | —                    | —                    |
| `03-TOOLS-AND-PACKAGES.md` | Linha 15         | `^12.0`              | `^13.0`              |
| `03-TOOLS-AND-PACKAGES.md` | Linha 311        | Laravel 12 (PHP 8.4) | Laravel 13 (PHP 8.4) |
| `05-PROMPTS-AND-MEMORY.md` | CLAUDE.md        | Laravel 12           | Laravel 13           |
| `08-PADRONIZACAO.md`       | —                | —                    | —                    |
| `09-LINEAR-GUIDE.md`       | —                | —                    | —                    |

**Decisão necessária:** Leonardo, confirme qual versão está usando. O INFRA.md diz 13.x, o PRD diz 12. Se o Laradock já está com Laravel 13 instalado, todos os docs devem ser 13. Se ainda é 12, o INFRA.md é que precisa corrigir.

---

### 🔴 Correção 2: PRD menciona Metronic — Projeto usa Inspinia

**O problema:** O PRD v3.0 foi escrito quando a decisão de template era Metronic. Depois, Leonardo decidiu usar o **Inspinia** (Tailwind 4). O PRD menciona "Metronic" **15+ vezes** nas seções 2.2, 14.x e 19.

**O que fazer:**

1. **NÃO alterar o PRD** — ele é documento do cliente, mantém como referência histórica
2. **Adicionar uma nota de divergência** no início do `ARCHITECTURE-GUIDE.md` e no `TEMPLATE-MAP-AND-COMPONENTS.md` explicando:

```markdown
> **Nota sobre Template:** O PRD v3.0 referencia "Metronic" como template do admin.
> Esta decisão foi atualizada para **Inspinia Multipurpose Admin Dashboard (Tailwind CSS 4)**.
> Todas as referências a componentes "Metronic" no PRD devem ser lidas como equivalentes
> no Inspinia. A funcionalidade é a mesma (DataTables, Modals, Tabs, Cards, etc.),
> apenas o template de origem mudou.
```

3. **No `CLAUDE.md`** adicionar na seção "Importante":

```
- O PRD menciona "Metronic" como template — foi substituído por Inspinia (Tailwind 4)
```

---

### 🔴 Correção 3: Nomes de arquivos na árvore do doc 01 inconsistentes

**O problema:** A árvore de pastas `.docs/` no `01-ARCHITECTURE-GUIDE.md` lista nomes diferentes dos arquivos reais criados.

**Corrigir a árvore de:**

```
│   ├── TEMPLATE-MAP.md                 ← Mapeamento do Inspinia
│   ├── TEMPLATE-COMPONENTS.md          ← Catálogo de componentes Blade do Inspinia
│   ├── TOOLS-AND-PACKAGES.md           ← Pacotes, plugins e ferramentas
│   ├── PROMPTS.md                      ← Prompts base para IA
│   ├── INFRA.md                        ← Infraestrutura Laradock
│   ├── CHANGELOG.md                    ← Histórico de mudanças do sistema
```

**Para:**

```
│   ├── TEMPLATE-MAP-AND-COMPONENTS.md  ← Mapeamento do Inspinia + catálogo de componentes Blade
│   ├── TOOLS-AND-PACKAGES.md           ← Pacotes, plugins e ferramentas
│   ├── PROMPTS-AND-MEMORY.md           ← Prompts base para IA e contexto de memória
│   ├── PADRONIZACAO-SPRINTS-AGENTES.md ← Formatadores, sprints detalhadas, agentes IA
│   ├── LINEAR-GUIDE.md                 ← Guia completo do Linear + MCP
│   ├── INFRA.md                        ← Infraestrutura Laradock
│   ├── INSPINIA-ANALISE.md             ← Análise original do template Inspinia
│   ├── CHANGELOG.md                    ← Histórico de mudanças do sistema
```

---

### 🟡 Correção 4: Template do Portal não está definido nos docs

**O problema:** O PRD recomenda **Preline UI** ou **design custom** para o Portal do Formando. Os documentos de arquitetura e template focam quase exclusivamente no admin (Inspinia). O portal precisa de uma definição.

**Adicionar ao `TEMPLATE-MAP-AND-COMPONENTS.md`** uma seção:

```markdown
## PARTE 3 — ESTRATÉGIA DO TEMPLATE DO PORTAL

O Portal do Formando é mobile-first e tem identidade visual própria,
separada do admin. Existem 3 opções:

### Opção A: Tailwind puro + componentes custom (RECOMENDADA)

- Design sob medida com Tailwind CSS 4
- Componentes Blade customizados para wizard, cards de pacote, extrato
- Usar a skill frontend-design do Claude para criar os componentes
- Vantagem: controle total, sem peso de template desnecessário
- Desvantagem: mais tempo de desenvolvimento visual

### Opção B: Preline UI (recomendação original do PRD)

- Framework open-source Tailwind com wizard, pricing cards, dashboard
- npm install preline
- Vantagem: componentes prontos de wizard e formulário
- Desvantagem: mais uma dependência, risco de conflito com Inspinia

### Opção C: Reutilizar componentes do Inspinia adaptados

- Usar os componentes shared do Inspinia com skin diferente para o portal
- Vantagem: consistência de componentes base
- Desvantagem: pode ficar com "cara de admin" no portal

### Decisão: [PENDENTE — definir antes da Sprint 4]
```

---

## PARTE 2 — CONTEÚDO FALTANTE NOS DOCUMENTOS

### 📋 Tema 1: Estratégia de Cache (Redis)

**Onde adicionar:** `02-CONVENTIONS.md` ou `03-TOOLS-AND-PACKAGES.md`

````markdown
## Estratégia de Cache (Redis)

### Quando cachear

- Configurações globais (TTL: 24h, invalidar ao salvar)
- Permissões ACL por admin_user (TTL: 1h, invalidar ao editar perfil)
- Programação ativa de um produto (TTL: 1h, invalidar ao criar/editar programação)
- Contagem de formandos por contrato (TTL: 15min)
- Dashboard KPIs (TTL: 5min)

### Quando NÃO cachear

- Dados de formando (mudam frequentemente)
- Parcelas (status muda com webhooks)
- Drafts de adesão (curta duração)
- Qualquer dado que envolva valores monetários em tempo real

### Padrão de uso

```php
// Usar Cache::remember com TTL explícito
$config = Cache::remember('config:global', 86400, function () {
    return ConfiguracaoGlobal::all()->pluck('valor', 'chave');
});

// Invalidar explicitamente ao alterar
Cache::forget('config:global');

// Nunca usar Cache::forever() — sempre com TTL
```
````

### Prefixo de cache

Usar prefixos descritivos: `config:`, `acl:`, `programacao:`, `dashboard:`

````

---

### 📋 Tema 2: Estratégia de Filas (Horizon)

**Onde adicionar:** `02-CONVENTIONS.md` ou `03-TOOLS-AND-PACKAGES.md`

```markdown
## Estratégia de Filas (Horizon)

### Filas definidas

| Fila | Prioridade | Uso |
|------|:----------:|-----|
| `default` | Normal | Jobs gerais |
| `emails` | Normal | Envio de e-mails (Mailables) |
| `gateway` | Alta | Processamento de pagamentos |
| `webhooks` | Alta | Processamento de webhooks recebidos |
| `exports` | Baixa | Geração de relatórios CSV/Excel |
| `pdf` | Baixa | Geração de PDFs de termos |

### Configuração do Horizon (config/horizon.php)
```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['gateway', 'webhooks', 'default', 'emails', 'exports', 'pdf'],
            'balance' => 'auto',
            'maxProcesses' => 5,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['gateway', 'webhooks', 'default', 'emails', 'exports', 'pdf'],
            'balance' => 'auto',
            'maxProcesses' => 3,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],
],
````

### Retry Policy

- Jobs de gateway: 3 tentativas com backoff exponencial (10s, 60s, 300s)
- Jobs de e-mail: 3 tentativas com delay de 30s
- Jobs de export: 1 tentativa (reprocessar manualmente se falhar)
- Todos os jobs devem ser **idempotentes** (executar 2x não causa problema)

### Padrão de Job

```php
class SendPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public string $queue = 'emails';

    public function __construct(
        private readonly Parcela $parcela,
    ) {}

    public function handle(LembreteVencimentoMail $mail): void
    {
        // Verificar se ainda faz sentido enviar (idempotência)
        if ($this->parcela->status !== StatusParcela::PENDENTE) {
            return;
        }
        // ...
    }
}
```

````

---

### 📋 Tema 3: Tratamento de Erros e Exceções

**Onde adicionar:** `02-CONVENTIONS.md`

```markdown
## Tratamento de Erros

### Exceções Customizadas
Criar exceções para cada domínio de erro, nunca usar Exception genérica:

```php
// ❌ ERRADO
throw new \Exception('Programação não encontrada');

// ✅ CERTO
throw new ProgramacaoNaoEncontradaException($produto, $dataAdesao);
````

### Hierarquia de Exceções

```
app/Exceptions/
├── AdesaoException.php              ← Base para erros de adesão
│   ├── ProgramacaoNaoEncontradaException.php
│   ├── ProdutoIndisponivelException.php
│   └── DraftExpiradoException.php
├── PagamentoException.php           ← Base para erros de pagamento
│   ├── GatewayIndisponivelException.php
│   ├── BoletoGeracaoException.php
│   └── WebhookInvalidoException.php
├── FinanceiroException.php          ← Base para erros financeiros
│   ├── ParcelaMinimaException.php
│   └── DescontoInvalidoException.php
└── BusinessRuleException.php        ← Violação genérica de regra de negócio
```

### Tratamento no Handler

```php
// bootstrap/app.php (Laravel 12+)
->withExceptions(function (Exceptions $exceptions) {
    // Erros de negócio → feedback amigável ao usuário
    $exceptions->renderable(function (BusinessRuleException $e, Request $request) {
        if ($request->wantsJson()) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        return back()->with('error', $e->getMessage());
    });

    // Erros de gateway → log + feedback genérico
    $exceptions->renderable(function (PagamentoException $e, Request $request) {
        Log::channel('gateway')->error($e->getMessage(), $e->getContext());
        return back()->with('error', 'Erro ao processar pagamento. Tente novamente.');
    });
})
```

````

---

### 📋 Tema 4: Performance e N+1

**Onde adicionar:** `02-CONVENTIONS.md`

```markdown
## Performance

### Eager Loading obrigatório
Nunca fazer queries em loop. Sempre usar eager loading:

```php
// ❌ N+1 — PROIBIDO
$contratos = Contrato::all();
foreach ($contratos as $contrato) {
    echo $contrato->instituicao->nome; // 1 query por contrato
}

// ✅ Eager loading
$contratos = Contrato::with('instituicao')->get();
````

### Prevenir N+1 em desenvolvimento

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // Em dev, lança exceção ao detectar N+1
    Model::preventLazyLoading(! app()->isProduction());

    // Em produção, apenas loga
    Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
        Log::warning("N+1 detectado: {$model}::{$relation}");
    });
}
```

### Índices de banco obrigatórios

Adicionar índice em toda coluna que aparece em:

- WHERE (filtros)
- ORDER BY (ordenação)
- JOIN (relacionamentos / FKs)
- Campos de status, tipo, data usados em queries frequentes

````

---

### 📋 Tema 5: Internacionalização (pt_BR)

**Onde adicionar:** `02-CONVENTIONS.md`

```markdown
## Localização pt_BR

### Configuração (.env)
```env
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
````

### Mensagens de validação

```bash
# Publicar traduções
php artisan lang:publish

# Instalar pacote de tradução pt_BR
composer require lucascudo/laravel-pt-br-localization --dev
php artisan vendor:publish --tag=laravel-pt-br-localization
```

### Datas

```php
// Sempre usar Carbon com locale
Carbon::setLocale('pt_BR');

// Formatação BR
$data->format('d/m/Y');           // 09/04/2026
$data->translatedFormat('d \de F \de Y'); // 09 de abril de 2026
```

### Valores monetários

```php
// Helper MoneyHelper::format()
MoneyHelper::format(150099); // "R$ 1.500,99"

// Nunca usar number_format direto — sempre via helper
```

````

---

### 📋 Tema 6: Padrão de E-mails

**Onde adicionar:** `03-TOOLS-AND-PACKAGES.md` ou `02-CONVENTIONS.md`

```markdown
## Padrão de E-mails

### Usar Mailables (não Notifications para e-mails)
- Mailables para e-mails transacionais (adesão, boleto, lembrete)
- Notifications apenas para notificações in-app do admin (sino no header)

### Estrutura de Mailable
```php
class AdesaoConcluidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly Adesao $adesao,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Adesão Confirmada — ' . $this->adesao->contrato->nome_turma,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.adesao-concluida',
            with: [
                'formando' => $this->adesao->formando,
                'pacotes' => $this->adesao->produtos,
                'parcelas' => $this->adesao->parcelas,
            ],
        );
    }
}
````

### Dispatch sempre via Job (async)

```php
// ❌ NUNCA enviar síncrono
Mail::to($email)->send(new AdesaoConcluidaMail($adesao));

// ✅ SEMPRE via queue
Mail::to($email)->queue(new AdesaoConcluidaMail($adesao));
// ou via Job dedicado
SendEmailAdesaoJob::dispatch($adesao)->onQueue('emails');
```

### Log de e-mails

Todo e-mail enviado deve ser registrado na tabela `email_logs`:

- destinatário, assunto, tipo, status (enviado/falhou), timestamp

```

---

## PARTE 3 — MAPA DE REFERÊNCIAS CRUZADAS (CORRIGIDO)

Após as correções, esta é a tabela definitiva de como os documentos se conectam:

```

CLAUDE.md (raiz)
└── referencia todos os docs em .docs/

.docs/
├── PRD_v3.0.md ← Fonte de verdade de negócio
│ (nota: menciona Metronic → ler como Inspinia)
├── ARCHITECTURE-GUIDE.md ← Estrutura de pastas, padrões Laravel, separação Admin/Portal
│ └── referencia: CONVENTIONS.md, TOOLS-AND-PACKAGES.md, TEMPLATE-MAP-AND-COMPONENTS.md
├── CONVENTIONS.md ← Commits, naming, code style, testes, logs, segurança
│ + cache, filas, erros, performance, i18n (complementos)
├── TOOLS-AND-PACKAGES.md ← Pacotes, justificativas, mapeamento pacote↔módulo
│ + padrão de e-mails, padrão de PDF
├── TEMPLATE-MAP-AND-COMPONENTS.md ← Inspinia: o que usar/ignorar, catálogo de componentes
│ + estratégia do template do Portal
├── PROMPTS-AND-MEMORY.md ← CLAUDE.md, memória, prompts por fase, checklists
├── PADRONIZACAO-SPRINTS-AGENTES.md ← Pint, Prettier, ESLint, Husky, uploads,
│ quebra de sprints, 6 agentes IA
├── LINEAR-GUIDE.md ← Setup Linear, labels, templates, MCP (Claude Code + Cursor)
├── INFRA.md ← Laradock, Docker, containers, troubleshooting
├── INSPINIA-ANALISE.md ← Análise original do template (documento do Leonardo)
├── CHANGELOG.md ← Histórico de versões
│
└── modules/ ← 20 arquivos, um por módulo
├── 01-auth-admin.md ← (exemplo preenchido)
└── 02 a 20... ← (placeholders)

```

---

## PARTE 4 — CHECKLIST DE APLICAÇÃO DAS CORREÇÕES

Após ler este documento, execute estas correções nos arquivos:

- [ ] **Decidir versão Laravel** (12 ou 13) e atualizar TODOS os docs
- [ ] **Doc 01:** Corrigir árvore de pastas `.docs/` com nomes reais dos arquivos
- [ ] **Doc 01:** Adicionar nota sobre Metronic → Inspinia na seção 1
- [ ] **Doc 01:** Adicionar referência ao PADRONIZACAO e LINEAR-GUIDE na árvore
- [ ] **Doc 02:** Adicionar seção de Cache (Redis)
- [ ] **Doc 02:** Adicionar seção de Tratamento de Erros
- [ ] **Doc 02:** Adicionar seção de Performance (N+1)
- [ ] **Doc 02:** Adicionar seção de Localização pt_BR
- [ ] **Doc 03:** Adicionar seção de Padrão de E-mails
- [ ] **Doc 03:** Adicionar seção de Padrão de Filas (Horizon)
- [ ] **Doc 03:** Corrigir versão do Laravel se necessário
- [ ] **Doc 04:** Adicionar PARTE 3 — Estratégia do Template do Portal
- [ ] **Doc 04:** Adicionar nota sobre Metronic → Inspinia
- [ ] **Doc 05:** Atualizar CLAUDE.md com nota sobre Metronic e versão Laravel
- [ ] **Doc 05:** Adicionar referências aos docs 08 e 09 no CLAUDE.md
- [ ] **Renomear arquivos** ao colocar em `.docs/` conforme mapa da Parte 3
```
