# Ferramentas e Pacotes

**Versão:** 1.0.0

---

## 1. Pacotes PHP (Composer) — Essenciais

### 1.1 Core da Aplicação

| Pacote                       | Versão | Finalidade                | Onde Usar                       |
| ---------------------------- | ------ | ------------------------- | ------------------------------- |
| `laravel/framework`          | ^13.0  | Framework base            | Todo o sistema                  |
| `laravel/horizon`            | ^5.0   | Dashboard de filas        | Processamento assíncrono        |
| `laravel/pulse`              | ^1.0   | Monitoramento/métricas    | Observabilidade                 |
| `livewire/livewire`          | ^4.0   | Componentes reativos      | Tabelas, formulários, modais    |
| `spatie/laravel-permission`  | ^7.0   | ACL (Roles & Permissions) | Admin ACL                       |
| `spatie/laravel-activitylog` | ^5.0   | Log de auditoria          | Audit logs (append-only)        |
| `barryvdh/laravel-dompdf`    | ^3.0   | Geração de PDF            | Documentos, relatórios em PDF   |
| `maatwebsite/excel`          | ^3.1   | Export CSV/Excel          | Relatórios, exportações admin   |

### 1.2 Dev e Qualidade

| Pacote                      | Versão | Finalidade                 | Onde Usar           |
| --------------------------- | ------ | -------------------------- | ------------------- |
| `laravel/pint`              | ^1.0   | Code formatter (PSR-12)    | CI/CD, pré-commit   |
| `larastan/larastan`         | ^3.0   | PHPStan para Laravel       | Análise estática    |
| `pestphp/pest`              | ^4.0   | Testing framework          | Testes unit/feature |
| `fakerphp/faker`            | ^1.23  | Geração de dados fake      | Seeders, factories  |

---

## 2. Pacotes NPM (Frontend) — Essenciais

### 2.1 Build e Framework

| Pacote                    | Finalidade                | Onde Usar          |
| ------------------------- | ------------------------- | ------------------ |
| `vite`                    | Bundler/dev server        | Build dos assets   |
| `laravel-vite-plugin`     | Integração Laravel + Vite | Build              |
| `tailwindcss` (v4)        | Framework CSS utilitário  | Todo o frontend    |
| `@tailwindcss/forms`      | Reset de formulários      | Forms admin        |
| `@tailwindcss/typography` | Prose/text rico           | Conteúdo           |
| `autoprefixer`            | Compatibilidade CSS       | Build              |

### 2.2 Plugins de UI (do Inspinia ou compatíveis)

| Plugin        | Finalidade                        | Uso                                 |
| ------------- | --------------------------------- | ----------------------------------- |
| `apexcharts`  | Gráficos interativos              | Dashboard admin, relatórios         |
| `flatpickr`   | Datepicker                        | Formulários com data                |
| `choices.js`  | Select searchable                 | Selects avançados                   |
| `inputmask`   | Máscara de input                  | CPF, CNPJ, telefone, CEP, monetário |
| `sortablejs`  | Drag-and-drop                     | Reordenação de listas               |
| `dropzone`    | Upload de arquivos                | Uploads com drag-and-drop           |
| `sweetalert2` | Alerts/confirmações               | Confirmações de ação                |
| `tinymce`     | Editor WYSIWYG                    | Conteúdo rich text                  |

---

## 3. Decisões de Pacotes — Justificativas

### 3.1 ACL: `spatie/laravel-permission` vs implementação manual

**Decisão:** Usar `spatie/laravel-permission`.

Justificativa: A aplicação precisa de ACL granular (listar, criar, editar, excluir, exportar por módulo). O Spatie Permission oferece isso pronto com cache de permissões, middleware `can`, Blade directives (`@can`, `@role`), e integração nativa com o Laravel. Implementar do zero levaria 2-3 dias para algo que o Spatie resolve em 1 hora.

### 3.2 Auditoria: `spatie/laravel-activitylog` vs implementação manual

**Decisão:** Usar `spatie/laravel-activitylog` como BASE, com customizações.

Justificativa: O log de auditoria precisa de before/after JSON, actor, módulo, ação, IP e user_agent. O Spatie Activity Log já captura before/after automaticamente via Observers e permite customização do log. A implementação manual seria possível mas reinventaria a roda.

Customização típica: adicionar campos `modulo` e `ip/user_agent` ao log, configurar via Trait `LogsActivity` nos Models auditáveis.

### 3.3 Export: `maatwebsite/excel`

**Decisão:** Usar `maatwebsite/excel`.

Justificativa: É o padrão de mercado para Laravel, com suporte a queued exports (processamento via Horizon para relatórios grandes), formatação de colunas e múltiplas abas.

### 3.4 PDF: `barryvdh/laravel-dompdf`

**Decisão:** Usar DomPDF (via barryvdh/laravel-dompdf).

Justificativa: DomPDF é leve, não requer binário externo (diferente do Snappy que precisa do wkhtmltopdf), e funciona bem dentro de containers Docker. Adequado para PDFs com texto e variáveis.

### 3.5 Testing: Pest vs PHPUnit

**Decisão:** Usar Pest.

Justificativa: Pest é construído sobre o PHPUnit mas com syntax mais limpa e expressiva. A syntax `it()`, `expect()` e chaining tornam os testes mais legíveis.

### 3.6 Logs: Canais nativos do Laravel (sem pacote adicional)

**Decisão:** Usar o sistema de logging nativo do Laravel com canais customizados.

Justificativa: O Laravel já oferece canais de log por arquivo (`daily` driver), rotação automática, e suporte a múltiplos canais simultâneos. Se no futuro for necessário monitoramento de erros em produção, o **Sentry** (`sentry/sentry-laravel`) é a recomendação.

---

## 4. Instalação Inicial — Checklist de Pacotes

```bash
# Dentro do container workspace (make bash)

# === COMPOSER ===
composer require livewire/livewire
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require laravel/horizon
composer require laravel/pulse
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel

# Dev only
composer require --dev laravel/pint
composer require --dev larastan/larastan
composer require --dev pestphp/pest pestphp/pest-plugin-laravel

# === NPM ===
npm install -D tailwindcss @tailwindcss/forms @tailwindcss/typography autoprefixer

# Plugins UI (instalar conforme necessidade)
npm install apexcharts
npm install flatpickr
npm install choices.js
npm install inputmask
npm install sortablejs
npm install dropzone
npm install sweetalert2

# TinyMCE (via CDN no blade, ou npm)
```

### Publicação de Configs

```bash
# Spatie Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Spatie Activity Log
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"

# DomPDF
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"

# Excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config

# Horizon
php artisan horizon:install

# Pulse
php artisan pulse:install
```

---

## 5. Ferramentas de Desenvolvimento

### 5.1 IDE / Editor

| Ferramenta      | Uso                                          |
| --------------- | -------------------------------------------- |
| **VS Code**     | IDE (com Intelephense + Blade formatter)     |
| **Claude Code** | CLI para tarefas de código assistidas por IA |
| **pgAdmin 4**   | Gerenciamento visual do PostgreSQL           |
| **Mailpit**     | Interceptor de e-mails em dev                |

### 5.2 Extensões Recomendadas (VS Code)

```json
{
    "recommendations": [
        "bmewburn.vscode-intelephense-client",
        "shufo.vscode-blade-formatter",
        "onecentlin.laravel-blade",
        "amiralizadeh9480.laravel-extra-intellisense",
        "bradlc.vscode-tailwindcss",
        "austenc.tailwind-docs",
        "calebporzio.better-phpunit",
        "mikestead.dotenv",
        "editorconfig.editorconfig",
        "xdebug.php-debug"
    ]
}
```

### 5.3 Integração Contínua (quando configurar)

Para CI/CD com GitHub Actions:

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
    tests:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.4'
                  extensions: pdo_pgsql, redis
            - run: composer install
            - run: ./vendor/bin/pint --test
            - run: ./vendor/bin/phpstan analyse
            - run: php artisan test
```

---

## 6. Stack Resumida

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND                              │
│                                                         │
│  Admin (Inspinia + Tailwind 4)                          │
│  ├── Livewire 4                                         │
│  ├── ApexCharts                                         │
│  ├── Flatpickr                                          │
│  ├── Choices.js                                         │
│  ├── TinyMCE                                            │
│  ├── Inputmask                                          │
│  ├── SortableJS                                         │
│  ├── Dropzone                                           │
│  └── SweetAlert2                                        │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                    BACKEND                               │
│                                                         │
│  Laravel 13 (PHP 8.4)                                   │
│  ├── Spatie Permission (ACL)                            │
│  ├── Spatie Activity Log (Auditoria)                    │
│  ├── DomPDF (PDFs)                                      │
│  ├── Maatwebsite Excel (Exports)                        │
│  ├── Pulse (Monitoramento)                              │
│  └── Horizon (Filas)                                    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                    INFRA                                 │
│                                                         │
│  Docker (Laradock)                                      │
│  ├── Nginx                                              │
│  ├── PHP-FPM 8.4                                        │
│  ├── PostgreSQL 16                                      │
│  ├── Redis                                              │
│  ├── Horizon (worker)                                   │
│  ├── Mailpit                                            │
│  └── pgAdmin 4                                          │
└─────────────────────────────────────────────────────────┘
```
