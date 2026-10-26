# Ferramentas e Pacotes

**Versão:** 1.1.0 · versões conferidas no `composer.json`, `composer.lock`, `package.json` e `package-lock.json` em 2026-10-25.

---

## 1. Pacotes PHP (Composer) — Essenciais

Restrição = o que o `composer.json` da raiz pede. Travada = a versão no `composer.lock`. Os pacotes `ht2ml/*` (core e extensões) entram por path repository (`@dev`) a partir de `packages/`; o `composer.json` de cada pacote declara as próprias dependências.

### 1.1 Core da Aplicação

| Pacote                                | Restrição | Travada | Finalidade                       | Onde Usar                     |
| ------------------------------------- | --------- | ------- | -------------------------------- | ----------------------------- |
| `laravel/framework`                   | ^13.0     | 13.31.0 | Framework base                   | Todo o sistema                |
| `laravel/horizon`                     | ^5.0      | 5.48.3  | Dashboard de filas               | Processamento assíncrono      |
| `laravel/pulse`                       | ^1.0      | 1.8.1   | Monitoramento/métricas           | Observabilidade               |
| `laravel/tinker`                      | ^3.0      | 3.0.2   | REPL                             | Diagnóstico                   |
| `livewire/livewire`                   | ^4.2      | 4.4.4   | Componentes reativos             | Tabelas, formulários, modais  |
| `power-components/livewire-powergrid` | ^6.10     | 6.11.0  | Tabelas Livewire                 | Listagens (`RecursoTable`)    |
| `spatie/laravel-permission`           | ^8.0      | 8.3.0   | ACL (Roles & Permissions)        | Admin ACL                     |
| `spatie/laravel-activitylog`          | ^5.0      | 5.1.0   | Log de auditoria                 | Audit logs (append-only)      |
| `spatie/laravel-settings`             | ^3.9      | 3.9.0   | Configurações tipadas no banco   | Tela de Configurações         |
| `spatie/laravel-backup`               | ^10.3     | 10.3.2  | Backup de banco e arquivos       | Operação                      |
| `pragmarx/google2fa-laravel`          | ^3.0      | 3.0.1   | 2FA TOTP                         | Login admin                   |
| `bacon/bacon-qr-code`                 | ^3.1      | 3.1.1   | QR code                          | Ativação do 2FA               |
| `barryvdh/laravel-dompdf`             | ^3.1      | 3.1.2   | Geração de PDF                   | Documentos, relatórios em PDF |
| `maatwebsite/excel`                   | ^3.1      | 3.1.69  | Export CSV/Excel                 | Relatórios, exportações admin |
| `openspout/openspout`                 | ^5.7      | 5.11.3  | Leitura/escrita de planilhas     | Exportações                   |
| `guzzlehttp/guzzle`                   | ^7.10     | 7.15.5  | Cliente HTTP                     | Integrações                   |

### 1.2 Dev e Qualidade

| Pacote                                  | Restrição | Travada | Finalidade                               | Onde Usar             |
| --------------------------------------- | --------- | ------- | ---------------------------------------- | --------------------- |
| `laravel/pint`                          | ^1.24     | 1.30.5  | Code formatter (PSR-12)                  | CI, pré-commit        |
| `larastan/larastan`                     | ^3.9      | 3.11.0  | PHPStan para Laravel                     | Análise estática      |
| `pestphp/pest`                          | ^4.4.1    | 4.7.3   | Testing framework                        | Testes                |
| `pestphp/pest-plugin-browser`           | ^4.3      | 4.3.1   | Testes de browser (Playwright)           | `make test-e2e`, CI   |
| `shipmonk/composer-dependency-analyser` | ^1.8      | 1.8.4   | Confere se cada pacote declara o que usa | CI (job `quality`)    |
| `laravel/boost`                         | ^2.4      | 2.7.0   | Contexto do projeto para agentes de IA   | Desenvolvimento       |
| `barryvdh/laravel-debugbar`             | ^4.2      | 4.4.3   | Debugbar                                 | Desenvolvimento       |
| `fakerphp/faker`                        | ^1.23     | 1.24.1  | Geração de dados fake                    | Seeders, factories    |

---

## 2. Pacotes NPM (Frontend) — Essenciais

### 2.1 Build e Framework

| Pacote                    | Restrição | Finalidade                   | Onde Usar        |
| ------------------------- | --------- | ---------------------------- | ---------------- |
| `vite`                    | ^8.2.2    | Bundler/dev server           | Build dos assets |
| `laravel-vite-plugin`     | ^3.2.0    | Integração Laravel + Vite    | Build            |
| `tailwindcss`             | ^4.3.1    | Framework CSS utilitário     | Todo o frontend  |
| `@tailwindcss/vite`       | ^4.3.3    | Plugin do Tailwind para Vite | Build            |
| `@tailwindcss/forms`      | ^0.5.11   | Reset de formulários         | Forms admin      |
| `@tailwindcss/typography` | ^0.5.20   | Prose/text rico              | Conteúdo         |
| `@iconify/tailwind4`      | ^1.2.3    | Ícones (tabler) em build     | Menu, botões     |
| `preline`                 | 5.0.0     | Componentes JS (abas etc.)   | Admin            |

Dev: `eslint`, `prettier` (com `prettier-plugin-blade` e `prettier-plugin-tailwindcss`), `husky`, `lint-staged`, `@commitlint/cli` e `playwright`.

### 2.2 Plugins de UI (do Inspinia ou compatíveis)

| Plugin        | Restrição | Finalidade           | Uso                                 |
| ------------- | --------- | -------------------- | ----------------------------------- |
| `apexcharts`  | ^7.1.0    | Gráficos interativos | Dashboard admin, relatórios         |
| `flatpickr`   | ^4.6.13   | Datepicker           | Formulários com data                |
| `choices.js`  | ^11.2.4   | Select searchable    | Selects avançados                   |
| `inputmask`   | ^5.0.10   | Máscara de input     | CPF, CNPJ, telefone, CEP, monetário |
| `sortablejs`  | ^1.15.7   | Drag-and-drop        | Reordenação de listas               |
| `dropzone`    | ^6.2.0    | Upload de arquivos   | Uploads com drag-and-drop           |
| `sweetalert2` | ^11.26.25 | Alerts/confirmações  | Confirmações de ação                |
| `quill`       | ^2.0.3    | Editor WYSIWYG       | Conteúdo rich text                  |
| `cropperjs`   | ^1.6.3    | Recorte de imagem    | Avatar (ver §3.7)                   |
| `simplebar`   | ^6.3.3    | Scroll customizado   | Sidebar, sino de notificações       |
| `moment`      | ^2.30.1   | Datas                | Exposto em `window.moment`          |

Não estão instalados: TinyMCE, DataTables, FullCalendar, Leaflet.

---

## 3. Decisões de Pacotes — Justificativas

### 3.1 ACL: `spatie/laravel-permission` vs implementação manual

**Decisão:** Usar `spatie/laravel-permission`.

Justificativa: A aplicação precisa de ACL granular (listar, criar, editar, excluir, exportar por módulo). O Spatie Permission oferece isso pronto com cache de permissões, middleware `can`, Blade directives (`@can`, `@role`), e integração nativa com o Laravel. Implementar do zero levaria 2-3 dias para algo que o Spatie resolve em 1 hora.

### 3.2 Auditoria: `spatie/laravel-activitylog` vs implementação manual

**Decisão:** Usar `spatie/laravel-activitylog` como BASE, com customizações.

Justificativa: O log de auditoria precisa de before/after JSON, actor, módulo, ação, IP e user_agent. O Spatie Activity Log já captura before/after automaticamente via Observers e permite customização do log. A implementação manual seria possível mas reinventaria a roda.

Customização adotada: o trait `HT2ML\Core\Models\Concerns\Auditavel` (sobre o `LogsActivity` do Spatie) em todo model, e o contexto (empresa, filial, IP, user agent, impersonação) carimbado por `CarimbarContextoNaAtividade`. Ver [`conventions.md` §7.2](conventions.md#72-auditoria-activity_log-no-banco).

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

### 3.7 Avatar cropper: `cropperjs` v1 (não migrar para v2 por ora)

**Decisão:** Manter `cropperjs` no v1 (hoje `^1.6.3`); não migrar para o v2.

Justificativa: O v2 é uma reescrita completa em Web Components (`<cropper-canvas>`, `<cropper-image>`, `<cropper-selection>`), orientada a seleções móveis/redimensionáveis. Nosso `avatar-cropper.js` precisa do oposto — **máscara circular fixa e centrada, com a imagem se movendo/ampliando por baixo** — caso em que o v1 (`dragMode: 'move'` + crop box travado) encaixa direto e o v2 exigiria recriar template, matemática de zoom (agora relativo, via matriz) e a máscara, com ganho marginal. O v1.6.2 **não tem CVE** (ausente do `npm audit`) e o componente é usado em produção (`form-usuario`, `perfil-conta`). O caret `^1.6.3` já bloqueia o major 2.0 e ainda recebe correções 1.x; o `.github/dependabot.yml` ignora o bump automático de major (security updates seguem passando).

**Revisitar se:** surgir CVE no v1, o componente precisar de recursos só do v2, ou o v2 amadurecer um modo "imagem move sob seleção fixa". Nesse caso, reescrever `resources/js/admin/avatar-cropper.js` para a API de Web Components.

---

## 4. Instalação

Os pacotes já estão declarados. Não há checklist de `composer require`: basta instalar o que está travado.

```bash
ddev composer install
ddev npm ci
make setup        # 1x: key:generate, migrate --seed, horizon:install,
                  # vendor:publish --tag=pulse-dashboard, npm run build
```

As configs publicadas ficam em `config/` da raiz (`permission.php`, `activitylog.php`, `horizon.php`, `pulse.php`, `backup.php`, `livewire-powergrid.php` etc.). As configs do core (`access.php`, `admin-menu.php`, `branding.php`, `extensoes.php`, `settings.php`) vivem em `packages/core/config/`.

O Pulse não tem comando `pulse:install`: o `make setup` publica só o dashboard (`--tag=pulse-dashboard`).

---

## 5. Ferramentas de Desenvolvimento

### 5.1 IDE / Editor

| Ferramenta      | Uso                                             |
| --------------- | ----------------------------------------------- |
| **VS Code**     | IDE (com Intelephense + Blade formatter)        |
| **Claude Code** | CLI para tarefas de código assistidas por IA    |
| **`ddev psql`** | Acesso ao PostgreSQL (ou GUI via porta exposta) |
| **Mailpit**     | Interceptor de e-mails em dev (`ddev mailpit`)  |

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

### 5.3 Integração Contínua

Já configurada em `.github/workflows/ci.yml`. Ver [`ci-cd.md`](ci-cd.md).

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
│  ├── Quill                                              │
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
│  ├── Spatie Settings · Spatie Backup                    │
│  ├── PowerGrid (tabelas)                                │
│  ├── Google2FA (2FA TOTP)                               │
│  ├── DomPDF (PDFs)                                      │
│  ├── Maatwebsite Excel (Exports)                        │
│  ├── Pulse (Monitoramento)                              │
│  └── Horizon (Filas)                                    │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                    INFRA                                 │
│                                                         │
│  DDEV + OrbStack                                        │
│  ├── Nginx                                              │
│  ├── PHP-FPM 8.4                                        │
│  ├── PostgreSQL 16                                      │
│  ├── Redis                                              │
│  ├── Horizon (worker / daemon)                          │
│  └── Mailpit (embutido)                                 │
└─────────────────────────────────────────────────────────┘
```
