# Ferramentas, Pacotes e Bibliotecas

**Projeto:** Portal ArtFinal — Sistema de Gerenciamento de Formaturas  
**Versão:** 1.0.0  
**Data:** 09/04/2026

---

## 1. Pacotes PHP (Composer) — Essenciais

### 1.1 Core da Aplicação

| Pacote                       | Versão | Finalidade                | Onde Usar                     |
| ---------------------------- | ------ | ------------------------- | ----------------------------- |
| `laravel/framework`          | ^13.0  | Framework base            | Todo o sistema                |
| `laravel/horizon`            | ^5.0   | Dashboard de filas        | Processamento assíncrono      |
| `laravel/pulse`              | ^1.0   | Monitoramento/métricas    | Observabilidade               |
| `livewire/livewire`          | ^3.0   | Componentes reativos      | Admin tables, portal wizard   |
| `spatie/laravel-permission`  | ^6.0   | ACL (Roles & Permissions) | Admin ACL                     |
| `barryvdh/laravel-dompdf`    | ^3.0   | Geração de PDF            | Termos consolidados, boletos  |
| `maatwebsite/excel`          | ^3.1   | Export CSV/Excel          | Relatórios, exportações admin |
| `spatie/laravel-activitylog` | ^4.0   | Log de auditoria          | Audit logs (append-only)      |

### 1.2 Formulários e Validação

| Pacote                           | Versão | Finalidade            | Onde Usar |
| -------------------------------- | ------ | --------------------- | --------- |
| `laravellegends/pt-br-validator` | ^11.0  | Validação CPF/CNPJ BR | Cadastros |

### 1.3 Integração e Comunicação

| Pacote              | Versão | Finalidade                    | Onde Usar        |
| ------------------- | ------ | ----------------------------- | ---------------- |
| `guzzlehttp/guzzle` | ^7.0   | HTTP client                   | API Itaú, ViaCEP |
| `laravel/sanctum`   | ^4.0   | API tokens (se futuro mobile) | API futura       |

### 1.4 Dev e Qualidade

| Pacote                      | Versão | Finalidade                 | Onde Usar           |
| --------------------------- | ------ | -------------------------- | ------------------- |
| `laravel/pint`              | ^1.0   | Code formatter (PSR-12)    | CI/CD, pré-commit   |
| `larastan/larastan`         | ^3.0   | PHPStan para Laravel       | Análise estática    |
| `pestphp/pest`              | ^3.0   | Testing framework          | Testes unit/feature |
| `laravel/telescope`         | ^5.0   | Debug dashboard (dev only) | Desenvolvimento     |
| `barryvdh/laravel-debugbar` | ^3.0   | Debug toolbar (dev only)   | Desenvolvimento     |
| `fakerphp/faker`            | ^1.23  | Geração de dados fake      | Seeders, factories  |

---

## 2. Pacotes NPM (Frontend) — Essenciais

### 2.1 Build e Framework

| Pacote                    | Finalidade                | Onde Usar          |
| ------------------------- | ------------------------- | ------------------ |
| `vite`                    | Bundler/dev server        | Build dos assets   |
| `laravel-vite-plugin`     | Integração Laravel + Vite | Build              |
| `tailwindcss` (v4)        | Framework CSS utilitário  | Todo o frontend    |
| `@tailwindcss/forms`      | Reset de formulários      | Forms admin/portal |
| `@tailwindcss/typography` | Prose/text rico           | Termos, conteúdo   |
| `autoprefixer`            | Compatibilidade CSS       | Build              |

### 2.2 Plugins de UI (do Inspinia ou compatíveis)

| Plugin        | Finalidade                        | Módulo/Tela                         |
| ------------- | --------------------------------- | ----------------------------------- |
| `apexcharts`  | Gráficos interativos              | Dashboard admin, relatórios         |
| `flatpickr`   | Datepicker                        | Todos os formulários com data       |
| `choices.js`  | Select searchable                 | Selects de contrato, instituição    |
| `inputmask`   | Máscara de input                  | CPF, CNPJ, telefone, CEP, monetário |
| `sortablejs`  | Drag-and-drop                     | Reordenação de termos               |
| `dropzone`    | Upload de arquivos                | Fotos, logos                        |
| `sweetalert2` | Alerts/confirmações               | Confirmações de ação                |
| `tinymce`     | Editor WYSIWYG                    | Termos de adesão                    |
| `tom-select`  | Alternativa ao Choices.js/Select2 | Selects avançados                   |

### 2.3 Plugins Opcionais (avaliar necessidade)

| Plugin       | Finalidade            | Quando Usar                  |
| ------------ | --------------------- | ---------------------------- |
| `echarts`    | Gráficos alternativos | Se ApexCharts não atender    |
| `filepond`   | Upload avançado       | Se Dropzone não atender      |
| `tagify`     | Tags input            | Config de dias de vencimento |
| `noUiSlider` | Range slider          | Simulador de parcelas        |
| `chart.js`   | Gráficos leves        | Dashboard portal (formando)  |

---

## 3. Decisões de Pacotes — Justificativas

### 3.1 ACL: `spatie/laravel-permission` vs implementação manual

**Decisão:** Usar `spatie/laravel-permission`.

Justificativa: O sistema precisa de ACL granular (listar, criar, editar, excluir, exportar por módulo). O Spatie Permission oferece isso pronto com cache de permissões, middleware `can`, Blade directives (`@can`, `@role`), e integração nativa com o Laravel. Implementar do zero levaria 2-3 dias para algo que o Spatie resolve em 1 hora.

### 3.2 Auditoria: `spatie/laravel-activitylog` vs implementação manual

**Decisão:** Usar `spatie/laravel-activitylog` como BASE, com customizações.

Justificativa: O PRD exige audit_logs com before/after JSON, actor, módulo, ação, IP e user_agent. O Spatie Activity Log já captura before/after automaticamente via Observers e permite customização do log. A implementação manual seria possível mas reinventaria a roda.

Customização necessária: adicionar campos `modulo` e `ip/user_agent` ao log, configurar via Trait `LogsActivity` nos Models auditáveis.

### 3.3 Export: `maatwebsite/excel`

**Decisão:** Usar `maatwebsite/excel`.

Justificativa: O PRD exige exportação CSV e Excel com filtros aplicados em pelo menos 6 relatórios e na tela de parcelas consolidada. O Maatwebsite é o padrão de mercado para Laravel, com suporte a queued exports (processamento via Horizon para relatórios grandes), formatação de colunas e múltiplas abas.

### 3.4 PDF: `barryvdh/laravel-dompdf`

**Decisão:** Usar DomPDF (via barryvdh/laravel-dompdf).

Justificativa: Os PDFs do sistema são simples (termos de adesão com texto e variáveis, não há necessidade de rendering complexo). DomPDF é leve, não requer binário externo (diferente do Snappy que precisa do wkhtmltopdf), e funciona bem dentro de containers Docker.

### 3.5 Testing: Pest vs PHPUnit

**Decisão:** Usar Pest.

Justificativa: Pest é construído sobre o PHPUnit mas com syntax mais limpa e expressiva. O Laravel 13 já vem com Pest como opção padrão. A syntax `it()`, `expect()` e chaining tornam os testes mais legíveis.

### 3.6 Logs: Canais nativos do Laravel (sem pacote adicional)

**Decisão:** Usar o sistema de logging nativo do Laravel com canais customizados.

Justificativa: O Laravel já oferece canais de log por arquivo (`daily` driver), rotação automática, e suporte a múltiplos canais simultâneos. Para o escopo deste projeto, não há necessidade de serviço externo de logs (como Papertrail, Sentry). Se no futuro for necessário monitoramento de erros em produção, o **Sentry** (`sentry/sentry-laravel`) é a recomendação.

### 3.7 Gateway: Implementação manual com Driver Pattern

**Decisão:** Implementar manualmente usando Driver Pattern.

Justificativa: Não existe um pacote Laravel maduro e mantido para a API específica do Itaú. A implementação manual com Driver Pattern (inspirada no `Mail::driver()` do Laravel) permite trocar de gateway no futuro sem refatorar o sistema.

---

## 4. Mapeamento Pacote ↔ Módulo do PRD

| Módulo PRD                | Pacotes Principais                          |
| ------------------------- | ------------------------------------------- |
| Auth Admin (M01)          | `spatie/laravel-permission`                 |
| Auth Portal (M02)         | Laravel guards nativos                      |
| Contratos (M03)           | —                                           |
| Instituições (M04)        | `inputmask` (CNPJ), Guzzle (ViaCEP)         |
| Produtos/Pacotes (M05)    | `dropzone` (imagem), `sortablejs` (termos)  |
| Programações (M06)        | `flatpickr` (datas)                         |
| Condições Pagamento (M07) | —                                           |
| Descontos (M08)           | `flatpickr` (vigência)                      |
| Termos (M09)              | `tinymce`, `barryvdh/laravel-dompdf`        |
| Formandos (M10)           | `inputmask` (CPF), `dropzone` (foto)        |
| Adesão Wizard (M11)       | Livewire (wizard), `inputmask`, `flatpickr` |
| Portal Área (M12)         | Chart.js (dashboard formando)               |
| Gateway (M13)             | Guzzle (API Itaú), implementação manual     |
| Parcelas/Financeiro (M14) | `maatwebsite/excel`, `apexcharts`           |
| E-mails (M15)             | Laravel Mail nativo, Mailpit (dev)          |
| Relatórios (M16)          | `maatwebsite/excel`, `apexcharts`           |
| Configurações (M17)       | `tagify` (dias vencimento)                  |
| Auditoria (M18)           | `spatie/laravel-activitylog`                |
| ACL (M19)                 | `spatie/laravel-permission`                 |
| Dashboard Admin (M20)     | `apexcharts`                                |

---

## 5. Instalação Inicial — Checklist de Pacotes

### Sprint 1 — Setup do Projeto

```bash
# Dentro do container workspace (make bash)

# === COMPOSER ===
composer require livewire/livewire
composer require spatie/laravel-permission
composer require spatie/laravel-activitylog
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
composer require laravellegends/pt-br-validator

# Dev only
composer require --dev laravel/pint
composer require --dev larastan/larastan
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
composer require --dev laravel/telescope
composer require --dev barryvdh/laravel-debugbar

# === NPM ===
npm install -D tailwindcss @tailwindcss/forms @tailwindcss/typography autoprefixer

# Plugins UI (instalar conforme necessidade por sprint)
npm install apexcharts
npm install flatpickr
npm install choices.js
npm install inputmask
npm install sortablejs
npm install dropzone
npm install sweetalert2

# TinyMCE (via CDN no blade, ou npm)
# Recomendação: usar CDN para facilitar updates
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

# Telescope (dev)
php artisan telescope:install

# Horizon (já instalado na infra)
# Pulse (já instalado na infra)
```

---

## 6. Ferramentas de Desenvolvimento

### 6.1 IDE / Editor

| Ferramenta      | Uso                                          |
| --------------- | -------------------------------------------- |
| **Cursor AI**   | IDE principal (VS Code based + AI)           |
| **Claude Code** | CLI para tarefas de código assistidas por IA |
| **pgAdmin 4**   | Gerenciamento visual do PostgreSQL           |
| **Mailpit**     | Interceptor de e-mails em dev                |

### 6.2 Extensões Recomendadas (Cursor/VS Code)

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

### 6.3 MCP Servers (Claude)

Para organização do projeto via IA, configurar no Claude:

- **Linear MCP** — Controle de tarefas e sprints (se usar Linear)
- **Context7 MCP** — Documentação atualizada de pacotes
- **Google Calendar MCP** — Planejamento de sprints

### 6.4 Integração Contínua (quando configurar)

Para futuro CI/CD com GitHub Actions:

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

## 7. Stack Resumida

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND                              │
│                                                         │
│  Admin (Inspinia + Tailwind 4)    Portal (Tailwind 4)   │
│  ├── Livewire 3                   ├── Livewire 3        │
│  ├── ApexCharts                   ├── Chart.js          │
│  ├── Flatpickr                    ├── Flatpickr         │
│  ├── Choices.js                   ├── Inputmask         │
│  ├── TinyMCE                      └── SweetAlert2       │
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
│  ├── Guzzle (HTTP Client)                               │
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
