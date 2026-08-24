# Impersonation de usuário (Épico 2 — Segurança & Acesso, Fase B)

> **Status:** design aprovado (brainstorming), aguardando plano de implementação.
> **Data:** 2026-06-05
> **Contexto:** starter kit Laravel 13 + Livewire 4 + Inspinia, guard `admin`,
> multi-empresa/filial (multi-tenant lógico). Fase A já entregue e mesclada na
> `main` (2FA TOTP, confirmação de senha, endurecimento de login, timeout de
> inatividade). Esta é a Fase B do Épico 2.

---

## 1. Objetivo

Permitir que um administrador autorizado **"entre como" outro usuário admin**
(act-as) para suporte e diagnóstico, vendo e operando o painel exatamente como o
alvo — com **banner persistente**, **saída a qualquer momento**, **teto de tempo**,
**trilha de auditoria** e **guardas de hierarquia + tenant**. É um subsistema
autocontido da Fase B; auditoria tenant-aware, LGPD e endurecimento adicional são
specs próprios (Fase C), tratados separadamente.

---

## 2. Decisões (confirmadas no brainstorming)

| #   | Decisão                     | Escolha                                                                                                                                                                                                                                                             |
| --- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Quem personifica quem**   | Hierarquia + permissão: exige `usuarios.impersonar` **E** só personifica quem está estritamente abaixo no nível (`HierarchyResolver::podeGerir`) e em empresa que o ator acessa. super-admin personifica qualquer não-super-admin. Nunca a si mesmo, nunca inativo. |
| 2   | **O que pode fazer**        | Act-as **com travas de segurança**: opera plenamente como o alvo, mas bloqueia ações sobre a conta do alvo (trocar senha, gerir 2FA/recovery codes) e impede personificação aninhada.                                                                               |
| 3   | **Fricção de entrada**      | Reconfirmar senha (`ConfirmsPassword`) **+ motivo obrigatório**; ambos registrados na auditoria.                                                                                                                                                                    |
| 4   | **Duração**                 | Teto de tempo dedicado configurável (default 30 min) + saída manual. Ao expirar, volta automaticamente ao usuário original.                                                                                                                                         |
| 5   | **Atribuição na auditoria** | Ações ficam no **alvo** (`causer` = alvo), mas cada registro ganha `properties.impersonado_por` (id/nome do real). Eventos dedicados de início/fim guardam `causer` = real + motivo.                                                                                |
| 6   | **Abordagem**               | Sob medida sobre o guard `admin`, espelhando o padrão de "contexto ativo" (`TenantContext`). Sem dependência nova.                                                                                                                                                  |

**Por que sob medida e não um pacote (`lab404/laravel-impersonate`):** o projeto usa
guard `admin` customizado e RBAC próprio (`AccessResolver` — **não** usamos o
`$user->can()` do spatie para resolver). Pacotes assumem o guard padrão e a
resolução do spatie; integrá-los com motivo/teto de tempo/atribuição de auditoria/
travas exigiria estender tudo mesmo assim. O esforço sob medida é pequeno e dá
controle total sobre as cinco decisões acima.

---

## 3. Arquitetura & componentes

### Estado (sessão)

- **`HT2ML\Core\Support\Impersonation\ImpersonationContext`** — espelha `TenantContext`.
  Chaves de sessão: `impersonate.original_id`, `impersonate.started_at`,
  `impersonate.motivo`.
  Métodos: `iniciar(int $originalId, string $motivo)`, `ativo(): bool`,
  `originalId(): ?int`, `motivo(): ?string`, `iniciadoEm(): ?CarbonInterface`,
  `expirado(int $minutos): bool`, `encerrar(): void`,
  `garantirNaoPersonificando(): void` (aborta ações sensíveis quando ativo).

### Operações (Actions API-ready — recebem models, nunca `Request`)

- **`HT2ML\Core\Actions\Admin\Impersonation\IniciarImpersonationAction`**
  `execute(AdminUser $ator, AdminUser $alvo, string $motivo): void` — revalida
  elegibilidade (defense-in-depth), grava o contexto, `Auth::guard('admin')->login($alvo)`,
  invalida o cache de acesso, loga o evento de início.
- **`HT2ML\Core\Actions\Admin\Impersonation\EncerrarImpersonationAction`**
  `execute(): void` — reloga o original (ou logout se inválido), loga o evento de
  fim, limpa o contexto, invalida o cache de acesso.

### Autorização

- Nova permissão **`usuarios.impersonar`** no módulo Usuários (`config/access.php`),
  publicada via `access:sync`.
- **`AdminUserPolicy::impersonate(AdminUser $auth, AdminUser $alvo): bool`**
  = `can('usuarios.impersonar') && hierarchy->podeGerir($auth, $alvo)`.

### Entrada (UI)

- **`HT2ML\Core\Livewire\Admin\Impersonation\IniciarImpersonation`** — modal acionado por
  ação de linha "Entrar como" na tabela de Usuários (`IndexUsuarios`). Usa o trait
  `ConfirmsPassword` + campo `motivo` (obrigatório, mín. 5 / máx. 255 chars) →
  `Gate::authorize('impersonate', $alvo)` → chama a Action → redireciona ao
  `admin.dashboard`.

### Saída + banner (global)

- **`POST /admin/impersonation/sair`** (`admin.impersonation.sair`) →
  **`HT2ML\Core\Http\Controllers\Admin\ImpersonationController@sair`** (thin) →
  `EncerrarImpersonationAction`.
- **`x-admin.impersonation-banner`** (Blade) no topo do `.wrapper` em
  `layout.blade.php`, renderizado só quando `ativo()`. Mostra "Você está como
  **NOME** · motivo · tempo restante" + botão **Sair** (form POST). Contagem
  regressiva cosmética via Alpine (lê `started_at` + teto).

### Expiração + travas

- **`HT2ML\Core\Http\Middleware\EncerrarImpersonationExpirada`** — registrado logo após
  `admin.auth` na cadeia autenticada: se `ativo()` e `expirado()`, reverte ao
  original + flash "Personificação expirada".
- **`EnsureTwoFactorEnabled`** passa a **ignorar** a exigência de 2FA enquanto
  `ativo()` (senão forçaria configurar o 2FA do alvo).
- Pontos sensíveis (`SegurancaConta` para 2FA, troca de senha, iniciar nova
  personificação) chamam `ImpersonationContext::garantirNaoPersonificando()`.

### Auditoria (atribuição "ao alvo, marcando o real")

- Eventos dedicados: `activity()->causedBy($ator)->performedOn($alvo)
->withProperties(['motivo' => $motivo])->log('Personificação iniciada'|'encerrada')`.
- Ações _durante_ a personificação: listener em `Activity::creating` (registrado no
  `AppServiceProvider`) injeta `properties.impersonado_por = [id, nome]` quando
  `ativo()`; o `causer` permanece o alvo (resolução padrão do activitylog).

### Config

- **`SegurancaSettings::impersonation_timeout_minutos`** (int, default 30).
  Sem item de menu novo — entrada via ação de linha em Usuários; saída via banner.

---

## 4. Fluxo de dados

**① Iniciar:** linha "Entrar como" → modal valida motivo + reconfirma senha →
`Gate::authorize('impersonate')` → `IniciarImpersonationAction` (revalida no
servidor: não-self · alvo ativo · `podeGerir` · acesso tenant · não-aninhada) →
grava contexto → `Auth::login($alvo)` → invalida cache → evento de início →
redireciona ao dashboard. No request seguinte, `DefinirContextoTenant` re-hidrata
empresa/filial a partir do alvo; o banner aparece.

**② Agir (cada request):** `admin.auth` → `EncerrarImpersonationExpirada` →
`CheckInactivity` → `EnsureTwoFactorEnabled` (pula 2FA quando ativo) →
`DefinirContextoTenant`. `Auth::user()` = alvo; RBAC/tenant resolvem como o alvo.
Cada `activity()` recebe `properties.impersonado_por` via listener.

**③ Sair (manual):** botão do banner → `POST admin.impersonation.sair` →
`EncerrarImpersonationAction`: reloga o original, evento de fim, limpa contexto →
redireciona.

**④ Expirar (automático):** `EncerrarImpersonationExpirada` reverte + flash.

**⑤ Logout durante a personificação:** `LogoutController` limpa as chaves de
impersonation antes de encerrar a sessão (sem estado órfão).

---

## 5. Tratamento de erro & casos de borda

**Elegibilidade (recusada na Action, não só na UI):** auto-personificação · alvo
inativo · nível ≥ ao do ator (inclui alvo super-admin) · sem acesso à empresa do
alvo · sem permissão (403) · já personificando (aninhada).

**Input:** `motivo` vazio/curto → validação; senha incorreta/janela expirada →
`ConfirmsPassword` re-pede.

**Durante:** gerir 2FA / trocar senha / nova personificação → `garantirNaoPersonificando()`
aborta com aviso; `EnsureTwoFactorEnabled` não força 2FA do alvo.

**Ciclo de vida:** expiração reverte automaticamente · original inválido ao sair →
**logout completo** · alvo desativado/excluído durante a sessão → cai para logout
defensivo · sair sem personificar → no-op idempotente · logout limpa as chaves ·
sessão regenerada/troca de empresa → chaves persistem (banner e teto continuam).

**Segurança/auditoria:** motivo + reconfirmação gravados no evento de início ·
cache de acesso invalidado nas duas trocas de identidade · banner sempre
server-side (o ator não age sem o aviso visível).

---

## 6. Estratégia de testes

Pest/Feature (SQLite em memória) em `tests/Feature/Admin/Impersonation/`:

- **`IniciarImpersonationTest`** — entrada feliz + todos os bloqueios de
  elegibilidade + validação de motivo/senha + re-hidratação de tenant para o alvo.
- **`EncerrarImpersonationTest`** — saída manual; expiração via `Carbon::setTestNow()`;
  original desativado → logout completo; sair sem personificar (no-op); logout limpa
  as chaves.
- **`ImpersonationTravasTest`** — 2FA/senha/nova personificação bloqueados durante;
  `EnsureTwoFactorEnabled` não força 2FA do alvo.
- **`ImpersonationAuditoriaTest`** — eventos início/fim com `causer` = real + motivo;
  ação durante grava `properties.impersonado_por` e `causer` = alvo.
- **`ImpersonationBannerTest`** — banner renderiza (com rota de saída) só quando ativo.

Helpers reaproveitados: `criarAdminUser()`, criação de `Empresa`/papéis,
`TenantContext`. Metas: Pint ✓ · PHPStan nível 6 ✓ · suíte verde.

---

## 7. Arquivos (resumo)

**Criar:**

- `app/Support/Impersonation/ImpersonationContext.php`
- `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php`
- `app/Actions/Admin/Impersonation/EncerrarImpersonationAction.php`
- `app/Http/Middleware/EncerrarImpersonationExpirada.php`
- `app/Http/Controllers/Admin/ImpersonationController.php`
- `app/Livewire/Admin/Impersonation/IniciarImpersonation.php` (+ view)
- `resources/views/components/admin/impersonation-banner.blade.php`
- `tests/Feature/Admin/Impersonation/*` (5 arquivos)

**Modificar:**

- `config/access.php` (permissão `usuarios.impersonar`)
- `app/Policies/AdminUserPolicy.php` (`impersonate`)
- `app/Settings/SegurancaSettings.php` (+ migration de settings em `database/settings/`, padrão spatie) — `impersonation_timeout_minutos`
- `app/Providers/AppServiceProvider.php` (listener `Activity::creating`)
- `app/Http/Middleware/EnsureTwoFactorEnabled.php` (pular quando ativo)
- `app/Livewire/Admin/Conta/SegurancaConta.php` (travas)
- `app/Http/Controllers/Admin/Auth/LogoutController.php` (limpar chaves)
- `routes/admin.php` (rota de saída + middleware de expiração na cadeia)
- `resources/views/components/admin/layout.blade.php` (banner no `.wrapper`)
- `app/Livewire/Admin/Usuarios/IndexUsuarios.php` (+ view: ação "Entrar como")

---

## 8. Fora de escopo (Fase C, specs próprios)

- Auditoria tenant-aware (empresa_id/filial_id no `activity_log`) e cobertura de
  eventos de segurança (login, falha de login, 2FA on/off, troca de acessos).
- LGPD (export de dados pessoais, anonimização, retenção/expurgo).
- Endurecimento adicional (rate-limit granular, lockout de conta, alertas).
