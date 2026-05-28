# Padronização Completa, Sprints Detalhadas, Agentes IA e Gestão de Projeto

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 09/04/2026

---

## PARTE 1 — PADRONIZAÇÃO COMPLETA DO CÓDIGO

### 1. Visão Geral dos Formatadores

O projeto usa uma **pirâmide de formatação** onde cada ferramenta cuida de uma camada:

```
┌─────────────────────────────┐
│     .editorconfig           │ ← Configuração base (tabs, charset, newlines)
├─────────────────────────────┤
│     Laravel Pint            │ ← PHP code style (PSR-12 + Laravel preset)
├─────────────────────────────┤
│     Prettier                │ ← JS, CSS, Blade, JSON, Markdown
│     + plugin-blade          │
│     + plugin-tailwindcss    │
├─────────────────────────────┤
│     PHPStan (Larastan)      │ ← Análise estática PHP (tipos, erros)
├─────────────────────────────┤
│     Husky + lint-staged     │ ← Automação pré-commit
└─────────────────────────────┘
```

---

### 2. Arquivos de Configuração

#### 2.1 `.editorconfig` — Base universal

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_style = space
indent_size = 4
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{js,ts,jsx,tsx,vue,css,scss,json,yaml,yml}]
indent_size = 2

[*.blade.php]
indent_size = 4

[Makefile]
indent_style = tab
```

#### 2.2 `pint.json` — Laravel Pint (PHP formatter)

```json
{
    "preset": "laravel",
    "rules": {
        "align_multiline_comment": {
            "comment_type": "phpdocs_only"
        },
        "array_indentation": true,
        "array_syntax": {
            "syntax": "short"
        },
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        },
        "combine_consecutive_issets": true,
        "combine_consecutive_unsets": true,
        "concat_space": {
            "spacing": "one"
        },
        "declare_strict_types": true,
        "fully_qualified_strict_types": true,
        "global_namespace_import": {
            "import_classes": true,
            "import_constants": true,
            "import_functions": true
        },
        "method_argument_space": {
            "on_multiline": "ensure_fully_multiline"
        },
        "no_empty_comment": true,
        "no_unused_imports": true,
        "not_operator_with_space": false,
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "method_public",
                "method_protected",
                "method_private"
            ]
        },
        "ordered_imports": {
            "sort_algorithm": "alpha",
            "imports_order": ["const", "class", "function"]
        },
        "single_line_empty_body": true,
        "trailing_comma_in_multiline": {
            "elements": ["arguments", "arrays", "match", "parameters"]
        },
        "yoda_style": false
    },
    "exclude": ["node_modules", "vendor", "storage", "bootstrap/cache"]
}
```

#### 2.3 `.prettierrc` — Prettier (JS, CSS, Blade, JSON)

Sim, o nome é **Prettier** — é esse que você estava tentando lembrar.

```json
{
    "printWidth": 120,
    "semi": true,
    "singleQuote": true,
    "tabWidth": 4,
    "trailingComma": "all",
    "useTabs": false,
    "endOfLine": "lf",
    "bracketSameLine": false,
    "htmlWhitespaceSensitivity": "css",
    "plugins": ["prettier-plugin-blade", "prettier-plugin-tailwindcss"],
    "overrides": [
        {
            "files": ["*.blade.php"],
            "options": {
                "parser": "blade",
                "tabWidth": 4,
                "bladePhpFormatting": "safe",
                "bladePhpFormattingTargets": ["directiveArgs", "echo"],
                "bladeDirectiveArgSpacing": "space",
                "bladeEchoSpacing": "space",
                "bladeComponentPrefixes": ["x", "livewire"]
            }
        },
        {
            "files": ["*.{js,ts,jsx,tsx,vue,css,scss}"],
            "options": {
                "tabWidth": 2,
                "singleQuote": true
            }
        },
        {
            "files": ["*.json"],
            "options": {
                "tabWidth": 2
            }
        }
    ]
}
```

#### 2.4 `.prettierignore` — O que o Prettier ignora

```
vendor/
node_modules/
storage/
bootstrap/cache/
public/build/
public/vendor/
*.min.js
*.min.css
laradock/
```

#### 2.5 `phpstan.neon` — Análise estática PHP

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 6
    paths:
        - app/
    excludePaths:
        - app/Console/Kernel.php
    checkMissingIterableValueType: false
    treatPhpDocTypesAsCertain: false
```

#### 2.6 `.eslintrc.json` — ESLint (JS, qualidade de código)

```json
{
    "env": {
        "browser": true,
        "es2024": true,
        "node": true
    },
    "extends": ["eslint:recommended", "prettier"],
    "parserOptions": {
        "ecmaVersion": "latest",
        "sourceType": "module"
    },
    "rules": {
        "no-console": "warn",
        "no-unused-vars": "warn",
        "prefer-const": "error",
        "no-var": "error"
    }
}
```

---

### 3. Automação Pré-Commit (Husky + lint-staged)

Isso garante que **nenhum código fora do padrão entre no Git**. Roda automaticamente no `git commit`.

#### 3.1 Instalação

```bash
npm install --save-dev husky lint-staged
npx husky init
```

#### 3.2 `.husky/pre-commit`

```bash
#!/bin/sh
npx lint-staged
```

#### 3.3 `lint-staged` no `package.json`

```json
{
    "lint-staged": {
        "*.php": ["./vendor/bin/pint --dirty"],
        "*.blade.php": ["prettier --write"],
        "*.{js,ts,jsx,tsx,vue}": ["eslint --fix", "prettier --write"],
        "*.{css,scss}": ["prettier --write"],
        "*.{json,md,yaml,yml}": ["prettier --write"]
    }
}
```

#### 3.4 Como funciona na prática

```
git add .
git commit -m "feat(portal): implementar etapa 3 do wizard"

# Automaticamente:
# 1. Arquivos .php alterados → roda Pint
# 2. Arquivos .blade.php → roda Prettier (formata HTML + ordena classes Tailwind)
# 3. Arquivos .js/.css → roda ESLint + Prettier
# 4. Se tudo passou → commit aceito
# 5. Se algo falhou → commit bloqueado, mostra o erro
```

---

### 4. Scripts NPM Completos

Adicione ao `package.json`:

```json
{
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "format": "prettier --write 'resources/**/*.{blade.php,js,css,json}'",
        "format:check": "prettier --check 'resources/**/*.{blade.php,js,css,json}'",
        "lint:php": "./vendor/bin/pint",
        "lint:php:check": "./vendor/bin/pint --test",
        "lint:js": "eslint resources/js/ --fix",
        "lint:js:check": "eslint resources/js/",
        "analyse": "./vendor/bin/phpstan analyse",
        "test": "php artisan test",
        "quality": "npm run lint:php:check && npm run format:check && npm run analyse && npm run test",
        "prepare": "husky"
    }
}
```

#### Adicione ao `Makefile`:

```makefile
# === Qualidade de Código ===
lint:          ## Formatar PHP + JS + Blade
	docker compose exec workspace bash -c "cd /var/www && ./vendor/bin/pint"
	npm run format

lint-check:    ## Verificar formatação sem alterar
	docker compose exec workspace bash -c "cd /var/www && ./vendor/bin/pint --test"
	npm run format:check

analyse:       ## Análise estática PHP
	docker compose exec workspace bash -c "cd /var/www && ./vendor/bin/phpstan analyse"

quality:       ## Check completo (lint + analyse + test)
	docker compose exec workspace bash -c "cd /var/www && ./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && php artisan test"
```

---

### 5. Padronização de Uploads de Arquivos

#### 5.1 Estrutura de Discos (config/filesystems.php)

```php
'disks' => [
    // Uploads públicos (acessíveis via URL)
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',
        'visibility' => 'public',
    ],

    // Uploads privados (não acessíveis via URL direta)
    'private' => [
        'driver' => 'local',
        'root' => storage_path('app/private'),
        'visibility' => 'private',
    ],
],
```

#### 5.2 Organização de Pastas de Upload

```
storage/app/
├── public/                     ← Acessível via /storage (symlink)
│   ├── instituicoes/           ← Logos de instituições
│   │   └── {instituicao_id}/
│   │       └── logo.jpg
│   ├── produtos/               ← Imagens de pacotes/produtos
│   │   └── {produto_id}/
│   │       └── imagem.jpg
│   ├── formandos/              ← Fotos de formandos
│   │   └── {formando_id}/
│   │       └── foto.jpg
│   └── termos/                 ← PDFs de termos consolidados (cache)
│       └── {adesao_id}/
│           └── termos-consolidados.pdf
│
└── private/                    ← NÃO acessível via URL
    ├── boletos/                ← PDFs de boletos gerados
    │   └── {parcela_id}/
    │       └── boleto-{nosso_numero}.pdf
    ├── exports/                ← Relatórios exportados (temporários)
    │   └── relatorio-{tipo}-{timestamp}.xlsx
    └── backups/                ← Backups (se configurado)
```

#### 5.3 Padrões de Upload

```php
// Trait para padronizar uploads
trait HasUploadableImage
{
    public function uploadImage(
        UploadedFile $file,
        string $folder,
        string $filename = 'imagem',
    ): string {
        $extension = $file->getClientOriginalExtension();
        $path = "{$folder}/{$this->id}";
        $name = "{$filename}.{$extension}";

        return $file->storeAs($path, $name, 'public');
    }

    public function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}

// Regras de validação padrão
class ImageValidationRules
{
    public static function logo(): array
    {
        return ['image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:min_width=100,min_height=100'];
    }

    public static function foto(): array
    {
        return ['image', 'mimes:jpg,jpeg,png', 'max:3072', 'dimensions:min_width=200,min_height=200'];
    }

    public static function produto(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }
}
```

---

### 6. Outras Padronizações

#### 6.1 Configuração do VS Code/Cursor (`.vscode/settings.json`)

```json
{
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "[php]": {
        "editor.defaultFormatter": "open-vscode.open-vscode-pint"
    },
    "[blade]": {
        "editor.defaultFormatter": "esbenp.prettier-vscode"
    },
    "files.associations": {
        "*.blade.php": "blade"
    },
    "tailwindCSS.includeLanguages": {
        "blade": "html"
    },
    "tailwindCSS.experimental.classRegex": [
        ["@apply\\s+([^;]*)", "([\\w-/:]+)"],
        ["class=\"([^\"]*?)\"", "([\\w-/:]+)"]
    ],
    "emmet.includeLanguages": {
        "blade": "html"
    },
    "php.validate.executablePath": "",
    "files.eol": "\n",
    "files.trimTrailingWhitespace": true,
    "files.insertFinalNewline": true
}
```

#### 6.2 Extensões recomendadas (`.vscode/extensions.json`)

```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "esbenp.prettier-vscode",
        "onecentlin.laravel-blade",
        "shufo.vscode-blade-formatter",
        "bradlc.vscode-tailwindcss",
        "amiralizadeh9480.laravel-extra-intellisense",
        "calebporzio.better-phpunit",
        "mikestead.dotenv",
        "editorconfig.editorconfig",
        "xdebug.php-debug",
        "open-vscode.open-vscode-pint",
        "dbaeumer.vscode-eslint",
        "naoray.laravel-goto-components"
    ]
}
```

#### 6.3 Padronização de Rotas

```php
// Padrão: resource routes com nomes explícitos
Route::resource('instituicoes', InstituicaoController::class)
    ->names('admin.instituicoes')
    ->except(['destroy']); // nunca delete real, sempre soft-delete/inativar

// Sub-recursos em rotas aninhadas
Route::prefix('produtos/{produto}')->name('admin.produtos.')->group(function () {
    Route::resource('programacoes', ProgramacaoController::class)->only(['store', 'update', 'destroy']);
    Route::resource('condicoes', CondicaoPagamentoController::class)->only(['store', 'update', 'destroy']);
    Route::resource('descontos', DescontoController::class)->only(['store', 'update', 'destroy']);
});
```

#### 6.4 Padronização de Responses

```php
// Redirecionamentos sempre com flash message
return redirect()->route('admin.contratos.index')
    ->with('success', 'Contrato criado com sucesso.');

return redirect()->back()
    ->with('error', 'Não foi possível processar a solicitação.');

// JSON para Livewire/AJAX
return response()->json([
    'success' => true,
    'message' => 'Operação realizada com sucesso.',
    'data' => $result,
], 200);
```

#### 6.5 Padronização de Logs

```php
// Padrão: contexto sempre como segundo argumento
Log::channel('gateway')->info('Boleto gerado com sucesso', [
    'parcela_id' => $parcela->id,
    'nosso_numero' => $nossoNumero,
    'valor_centavos' => $parcela->valor_cobrado_centavos,
]);

Log::channel('webhook')->warning('Assinatura de webhook inválida', [
    'ip' => request()->ip(),
    'payload_size' => strlen(request()->getContent()),
]);

// NUNCA logar dados sensíveis completos
// ❌ ERRADO: Log::info('CPF: ' . $formando->cpf);
// ✅ CERTO: Log::info('Formando processado', ['cpf_masked' => '***.' . substr($cpf, -4)]);
```

---

## PARTE 2 — DETALHAMENTO E DIVISÃO DE SPRINTS EM TAREFAS

### 7. Filosofia de Quebra de Tarefas

Cada sprint de 7 dias do PRD deve ser quebrada em **tarefas atômicas de 2-4 horas**. A regra é: se uma tarefa leva mais de meio dia, ela precisa ser subdividida.

### 8. Template de Quebra de Sprint

```markdown
## Sprint XX — [Título da Sprint]

**Duração:** 7 dias  
**Objetivo:** [1 frase que resume a entrega]  
**Critério de Aceite:** [O que o cliente vai validar]

### Dia 1-2: Fundação

- [ ] T01 — Criar migration(s) para [tabelas] (~2h)
- [ ] T02 — Criar Model(s) com relationships e casts (~2h)
- [ ] T03 — Criar Enum(s) necessário(s) (~1h)
- [ ] T04 — Criar Factory(ies) e atualizar Seeder (~2h)
- [ ] T05 — Criar FormRequest(s) com validações (~2h)

### Dia 3-4: Lógica

- [ ] T06 — Criar Service(s) principal(is) (~3h)
- [ ] T07 — Criar Action(s) se necessário (~2h)
- [ ] T08 — Criar testes unitários para Services (~3h)
- [ ] T09 — Criar Controller(s) magro(s) (~1h)
- [ ] T10 — Definir rotas (~0.5h)

### Dia 5-6: Interface

- [ ] T11 — Criar/adaptar componente(s) Blade do Inspinia (~3h)
- [ ] T12 — Criar view(s) de listagem (~3h)
- [ ] T13 — Criar view(s) de formulário (~3h)
- [ ] T14 — Criar componente(s) Livewire (~3h)
- [ ] T15 — Integrar plugins JS necessários (~2h)

### Dia 7: Finalização

- [ ] T16 — Testes feature (fluxo completo) (~2h)
- [ ] T17 — Review de código e formatação (Pint + Prettier) (~1h)
- [ ] T18 — Atualizar .docs/modules/XX-nome.md (~1h)
- [ ] T19 — Commit final e merge para develop (~0.5h)
- [ ] T20 — Preparar demo para validação do cliente (~1h)
```

### 9. Exemplo Detalhado: Sprint 4 Quebrada

```markdown
## Sprint 04 — Portal: Layout + Home + Código da Turma + Resumo

**Duração:** 7 dias
**Objetivo:** Portal acessível pelo celular com entrada por código funcional
**Critério de Aceite:** Portal acessível, visual agradável, código de turma funcional

### Dia 1: Setup do Portal

- [ ] T01 — Criar layout master do portal (resources/views/portal/layouts/app.blade.php) (~3h)
    - Header responsivo mobile-first
    - Footer com links
    - Container principal
    - Slot para conteúdo
    - Dark/light mode support
- [ ] T02 — Criar layout guest (sem auth) para telas públicas (~1h)
- [ ] T03 — Configurar CSS entry point portal.css com Tailwind (~1h)
- [ ] T04 — Configurar JS entry point portal.js (~1h)
- [ ] T05 — Criar componente x-portal.header (~2h)

### Dia 2: Home + Componentes Base

- [ ] T06 — Criar componente x-portal.footer (~1h)
- [ ] T07 — Criar tela Home do portal (landing com input de código) (~3h)
- [ ] T08 — Criar componente x-shared.input (reutilizável) (~1h)
- [ ] T09 — Criar componente x-shared.button (reutilizável) (~1h)
- [ ] T10 — Criar componente x-shared.alert (reutilizável) (~1h)
- [ ] T11 — Testar responsividade da Home em mobile (~1h)

### Dia 3: Código da Turma

- [ ] T12 — Criar Livewire StepCodigoTurma (~3h)
    - Input com validação em tempo real
    - Feedback visual (código válido/inválido)
    - Busca no banco por codigo_turma
    - Redirect para resumo se válido
- [ ] T13 — Criar ContratoResolverService (~2h)
- [ ] T14 — Criar middleware AdesaoContratoResolver (~1h)
- [ ] T15 — Definir rotas em routes/portal.php (~0.5h)

### Dia 4: Resumo da Turma + Draft

- [ ] T16 — Criar tela de Resumo da Turma (~3h)
    - Dados da instituição (nome, logo)
    - Dados do contrato (turma, conclusão)
    - Cursos disponíveis
    - Botão "Iniciar Adesão"
- [ ] T17 — Criar migration para adesao_drafts (se não existir) (~1h)
- [ ] T18 — Criar AdesaoDraftService (~2h)
    - Criar draft
    - Salvar estado de cada etapa
    - Recuperar draft existente
    - Limpar draft expirado

### Dia 5: Middleware e Navegação

- [ ] T19 — Criar middleware AdesaoStateGuard (impede pulo de etapas) (~2h)
- [ ] T20 — Criar componente x-portal.wizard-progress (~2h)
    - Barra visual de 7 etapas
    - Etapa ativa destacada
    - Etapas concluídas com check
    - Responsivo (horizontal em desktop, vertical em mobile)
- [ ] T21 — Criar WizardShell Livewire (container do wizard) (~2h)

### Dia 6: Polish e Testes

- [ ] T22 — Testar fluxo: Home → Código → Resumo em mobile (~2h)
- [ ] T23 — Testar edge cases: código inválido, contrato inativo (~1h)
- [ ] T24 — Ajustar responsividade e UX (~2h)
- [ ] T25 — Criar teste feature: test_codigo_turma_valido (~1h)
- [ ] T26 — Criar teste feature: test_codigo_turma_invalido (~1h)

### Dia 7: Documentação e Entrega

- [ ] T27 — Rodar Pint + Prettier em tudo (~0.5h)
- [ ] T28 — Rodar PHPStan e corrigir warnings (~1h)
- [ ] T29 — Atualizar .docs/modules/11-adesao-wizard.md (~1h)
- [ ] T30 — Commit: "feat(portal): implementar layout, home e código da turma" (~0.5h)
- [ ] T31 — Preparar demo para cliente (testar no celular) (~1h)
- [ ] T32 — Merge para develop + tag se necessário (~0.5h)
```

---

## PARTE 3 — AGENTES IA (ROLES E RESPONSABILIDADES)

### 10. O que são os Agentes

Cada "agente" é um prompt com role, contexto e skills específicos que você carrega no Claude quando precisa de ajuda em uma área específica. Não são ferramentas separadas — são **modos de operação do Claude** com instruções especializadas.

### 11. Catálogo de Agentes

#### 🏗️ Agente: Arquiteto de Software

```
Você é o Arquiteto de Software do projeto Portal ArtFinal.

Seu papel:
- Tomar decisões de arquitetura (quando usar Service vs Action vs Job)
- Definir a estrutura de novos módulos
- Revisar a organização de código e pastas
- Identificar débito técnico e propor refatorações
- Validar se a implementação segue os padrões de .docs/ARCHITECTURE-GUIDE.md

Contexto:
- PRD: .docs/PRD_v3.0.md
- Padrões: .docs/CONVENTIONS.md
- Stack: Laravel 13, Livewire 3, PostgreSQL 16

Quando consultado, sempre:
1. Referencie o padrão específico que se aplica
2. Dê exemplos de código concretos
3. Aponte riscos e trade-offs
4. Sugira testes para validar a decisão
```

#### 💻 Agente: Desenvolvedor Backend

```
Você é o Desenvolvedor Backend do projeto Portal ArtFinal.

Seu papel:
- Implementar Models, Services, Actions, Jobs, Controllers
- Criar migrations, seeders e factories
- Implementar regras de negócio seguindo o PRD
- Escrever testes unitários e feature
- Seguir rigorosamente os padrões de .docs/CONVENTIONS.md

Regras que você SEMPRE segue:
1. Controllers com máximo 5-7 linhas por método
2. TODA validação em FormRequest
3. Valores monetários em centavos (int)
4. Enums PHP 8.1+ para campos finitos
5. DTOs readonly para transporte entre camadas
6. Declare strict_types em todo arquivo PHP

Ao implementar, sempre:
1. Verifique se o Service/Action já está nomeado no PRD (seção 20.3)
2. Crie o FormRequest antes do Controller
3. Adicione type hints e return types em tudo
4. Escreva pelo menos 1 teste para cada Service
```

#### 🎨 Agente: Desenvolvedor Frontend

```
Você é o Desenvolvedor Frontend do projeto Portal ArtFinal.

Seu papel:
- Criar e adaptar componentes Blade a partir do Inspinia
- Implementar componentes Livewire reativos
- Configurar plugins JS (ApexCharts, Flatpickr, Choices.js, etc.)
- Garantir responsividade (mobile-first no portal, desktop-first no admin)
- Implementar dark mode

Referências:
- Catálogo de componentes: .docs/TEMPLATE-MAP-AND-COMPONENTS.md
- Template: Inspinia (Tailwind 4)

Regras:
1. Todo elemento visual reutilizável vira componente Blade
2. Usar @props para tipagem de componentes
3. Classes Tailwind sempre, nunca CSS customizado exceto variáveis de tema
4. Alpine.js só para interações visuais locais (toggle, collapse)
5. Livewire para qualquer interação que envolva dados do servidor
6. Nunca copiar página inteira do Inspinia — decompor em componentes
```

#### 📋 Agente: PO/Scrum Master

```
Você é o Product Owner e Scrum Master do projeto Portal ArtFinal.

Seu papel:
- Quebrar sprints em tarefas atômicas de 2-4 horas
- Priorizar tarefas dentro da sprint
- Identificar dependências entre módulos
- Preparar critérios de aceite para o cliente
- Monitorar progresso e identificar riscos

Contexto:
- PRD: .docs/PRD_v3.0.md (seção 20 — Cronograma)
- Total: 26 sprints de 7 dias, estratégia Portal-First

Quando pedido para quebrar uma sprint:
1. Leia o detalhamento da sprint no PRD
2. Identifique as entregas
3. Quebre cada entrega em tarefas de 2-4h
4. Organize por dia (7 dias)
5. Identifique a ordem de dependência
6. Defina critérios de aceite claros
7. Estime o esforço total
```

#### 🔍 Agente: Reviewer de Código

```
Você é o Code Reviewer do projeto Portal ArtFinal.

Seu papel:
- Revisar código antes de merges
- Verificar aderência aos padrões
- Identificar bugs, edge cases e vulnerabilidades
- Sugerir melhorias de performance
- Garantir que testes cobrem os cenários críticos

Checklist de review:
1. [ ] Controller magro? (máx 5-7 linhas por método)
2. [ ] Validação em FormRequest?
3. [ ] Valores monetários em centavos?
4. [ ] Enums em vez de strings mágicas?
5. [ ] Type hints em tudo?
6. [ ] Sem lógica de negócio no Controller?
7. [ ] Testes para cenários críticos?
8. [ ] Sem dados sensíveis em logs?
9. [ ] N+1 queries tratadas (eager loading)?
10. [ ] Componente Blade reutilizável em vez de HTML duplicado?
```

#### 📊 Agente: DBA / Modelagem de Dados

```
Você é o DBA do projeto Portal ArtFinal.

Seu papel:
- Revisar e otimizar migrations
- Definir índices para queries frequentes
- Validar relacionamentos e constraints
- Otimizar queries Eloquent
- Configurar PostgreSQL

Contexto:
- Banco: PostgreSQL 16
- Modelo: 31 tabelas (ver PRD seção 17)
- Padrões: snake_case para tabelas/colunas, plural para tabelas

Ao revisar migrations:
1. Verificar índices em FKs e campos de filtro
2. Verificar constraints (unique, check)
3. Verificar ON DELETE behavior (cascade vs restrict)
4. Verificar tipos de coluna adequados (não usar VARCHAR para CPF, usar CHAR(11))
5. Verificar default values
6. Verificar down() funcional
```

#### 📝 Agente: Documentador

```
Você é o Documentador do projeto Portal ArtFinal.

Seu papel:
- Manter a documentação modular atualizada (.docs/modules/)
- Atualizar o CHANGELOG.md
- Documentar decisões técnicas
- Criar e atualizar o CLAUDE.md
- Documentar novos componentes Blade no catálogo

Template de módulo: .docs/PROMPTS-AND-MEMORY.md (seção 4)
Padrão: Cada módulo tem seu próprio .md em .docs/modules/

Regra: documentação é atualizada NO MESMO COMMIT que o código.
```

### 12. Como Usar os Agentes na Prática

No Claude (chat ou Claude Code), inicie a conversa com:

```
Atue como [NOME DO AGENTE] do projeto Portal ArtFinal.

[Contexto adicional se necessário]

Tarefa: [o que você precisa]
```

No Claude Code, você pode criar um arquivo `.claude/agents/` com cada agente salvo para referência rápida.

---

## PARTE 4 — LINEAR: ANÁLISE E RECOMENDAÇÃO

### 13. O que é o Linear

O Linear é uma plataforma de gestão de projetos focada em times de desenvolvimento. Ele é rápido, tem interface limpa, atalhos de teclado, e é muito popular entre startups e times de produto.

### 14. Por que o Linear é uma BOA escolha para este projeto

O Linear se encaixa muito bem no Portal ArtFinal por vários motivos:

**Foco em desenvolvimento** — O Linear foi feito para times de software. Sprints (chamados "Cycles"), issues, labels, e roadmaps são first-class citizens. Diferente do Jira que é pesado e cheio de configuração, o Linear é leve e opinado.

**Cycles = Sprints** — O conceito de Cycles do Linear mapeia perfeitamente para as sprints de 7 dias do PRD. Você cria um Cycle por sprint, adiciona as issues, e acompanha o progresso.

**Velocidade** — O Linear é notoriamente rápido. Tudo carrega instantaneamente, com atalhos de teclado para tudo. Para um dev solo ou time pequeno, isso faz diferença real.

**Labels para organização** — Você pode criar labels para separar: `admin`, `portal`, `gateway`, `infra`, `docs`, `bug`, `debt`.

**Integração com GitHub** — Issues do Linear se conectam automaticamente com PRs e branches do GitHub. Quando você faz merge de um PR, a issue pode ser automaticamente movida para "Done".

**MCP Server** — O Linear tem MCP server, o que significa que o Claude pode criar e gerenciar issues diretamente na conversa.

### 15. Estrutura Recomendada no Linear

```
Workspace: HT2ML TECH
│
└── Project: Portal ArtFinal
    │
    ├── Cycles (= Sprints)
    │   ├── Sprint 01 — Setup do Projeto (7 dias)
    │   ├── Sprint 02 — Migrations e Models (Grupo 1)
    │   ├── Sprint 03 — Migrations (Grupo 2) + Seeders
    │   └── ...
    │
    ├── Labels
    │   ├── 🔧 admin
    │   ├── 🌐 portal
    │   ├── 🏦 gateway
    │   ├── 🔨 infra
    │   ├── 📝 docs
    │   ├── 🐛 bug
    │   ├── ⚡ debt
    │   └── 🧪 test
    │
    ├── Status
    │   ├── Backlog
    │   ├── Todo
    │   ├── In Progress
    │   ├── In Review
    │   └── Done
    │
    └── Priorities
        ├── 🔴 Urgent
        ├── 🟠 High
        ├── 🟡 Medium
        └── 🟢 Low
```

### 16. Alternativas ao Linear

Se por algum motivo o Linear não servir (custo, preferência pessoal), estas são boas alternativas:

| Ferramenta          | Melhor Para                        | Custo                 |
| ------------------- | ---------------------------------- | --------------------- |
| **Linear**          | Dev solo/time pequeno, velocidade  | Grátis até 250 issues |
| **GitHub Projects** | Se já usa GitHub, zero setup extra | Grátis                |
| **Plane**           | Open-source, self-hosted, completo | Grátis (self-hosted)  |
| **Notion**          | Se quer combinar docs + tasks      | Grátis básico         |

**Minha recomendação:** O Linear é a melhor opção para este projeto. É feito para o seu perfil de uso (dev solo/pequeno time, projeto de software, sprints curtas). O plano gratuito cobre bem a fase inicial, e o upgrade para o pago só seria necessário se o time crescer. A integração com GitHub e o MCP do Claude são bônus reais.

Se você quiser simplicidade máxima e já usa GitHub, o **GitHub Projects** é zero custo e zero setup adicional — mas é menos sofisticado que o Linear para gestão de sprints.

---

## PARTE 5 — RESUMO DE TODAS AS PADRONIZAÇÕES

### 17. Checklist Completo de Padronizações

| #   | Padronização                              | Ferramenta                  | Arquivo de Config             |
| --- | ----------------------------------------- | --------------------------- | ----------------------------- |
| 1   | Formatação base (charset, tabs, newlines) | EditorConfig                | `.editorconfig`               |
| 2   | Code style PHP (PSR-12 + Laravel)         | Laravel Pint                | `pint.json`                   |
| 3   | Formatação JS/CSS/Blade/JSON/MD           | Prettier                    | `.prettierrc`                 |
| 4   | Ordenação de classes Tailwind             | prettier-plugin-tailwindcss | `.prettierrc`                 |
| 5   | Formatação de templates Blade             | prettier-plugin-blade       | `.prettierrc`                 |
| 6   | Qualidade de código JS                    | ESLint                      | `.eslintrc.json`              |
| 7   | Análise estática PHP (tipos, erros)       | PHPStan/Larastan            | `phpstan.neon`                |
| 8   | Automação pré-commit                      | Husky + lint-staged         | `.husky/`, `package.json`     |
| 9   | Testes automatizados                      | Pest                        | `phpunit.xml`                 |
| 10  | Convenção de commits                      | Conventional Commits        | `.docs/CONVENTIONS.md`        |
| 11  | Branching strategy                        | Git Flow simplificado       | `.docs/CONVENTIONS.md`        |
| 12  | Naming de código                          | Convention doc              | `.docs/CONVENTIONS.md`        |
| 13  | Estrutura de pastas                       | Architecture doc            | `.docs/ARCHITECTURE-GUIDE.md` |
| 14  | Organização de uploads                    | Filesystem config           | `config/filesystems.php`      |
| 15  | Organização de documentação               | Docs modulares              | `.docs/modules/*.md`          |
| 16  | Contexto para IA                          | CLAUDE.md                   | `CLAUDE.md`                   |
| 17  | Extensões de IDE                          | VS Code config              | `.vscode/extensions.json`     |
| 18  | Settings de IDE                           | VS Code config              | `.vscode/settings.json`       |
| 19  | Gestão de projeto                         | Linear                      | Externo (app.linear.app)      |
| 20  | Tarefas por sprint                        | Template de quebra          | `.docs/PROMPTS-AND-MEMORY.md` |

### 18. Comando de Instalação Completo (Sprint 1)

```bash
# Dentro do container workspace (make bash)

# === PHP Formatter ===
# Pint já vem com Laravel 13

# === NPM: Prettier + Plugins ===
npm install --save-dev \
  prettier \
  prettier-plugin-blade \
  prettier-plugin-tailwindcss \
  eslint \
  eslint-config-prettier \
  husky \
  lint-staged

# === Inicializar Husky ===
npx husky init

# === Composer: Análise Estática ===
composer require --dev larastan/larastan

# === Criar configs (se não existirem) ===
# Copiar os conteúdos deste documento para:
# .editorconfig, pint.json, .prettierrc, .prettierignore
# phpstan.neon, .eslintrc.json
# .vscode/settings.json, .vscode/extensions.json

# === Verificar que tudo funciona ===
./vendor/bin/pint --test          # PHP style check
npx prettier --check "resources/" # Blade/JS/CSS check
./vendor/bin/phpstan analyse      # Static analysis
php artisan test                  # Tests

# === Primeiro commit de padronização ===
git add .
git commit -m "chore(infra): configurar formatadores (Pint, Prettier, ESLint, Husky)"
```
