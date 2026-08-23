# Auditoria tenant-aware (Épico 2 — Segurança & Acesso, Fase C · subsistema 1)

> **Status:** design aprovado (brainstorming), aguardando plano de implementação.
> **Data:** 2026-06-05
> **Contexto:** starter kit Laravel 13 + Livewire 4 + Inspinia, guard `admin`,
> multi-tenant lógico (empresa/filial via `HT2ML\Core\Support\Tenancy\TenantContext`),
> `spatie/laravel-activitylog` v5. Fase A (2FA etc.) e Fase B (impersonation) já
> mescladas na `main`. Este é o 1º subsistema da Fase C; LGPD e endurecimento
> adicional são specs próprios.

---

## 1. Objetivo

Tornar a trilha de auditoria **ciente de tenant** e **completa quanto a eventos de
segurança**:

1. Todo registro de `activity_log` passa a gravar `empresa_id`/`filial_id` do
   contexto ativo.
2. Cobrir os eventos de autenticação hoje não logados (login sucesso/falha,
   bloqueio, logout, falha de 2FA, reset de senha solicitado/aplicado).
3. A tela de auditoria ganha coluna + filtro de empresa e **isola** os registros
   por empresa para usuários não privilegiados.

**Fora de escopo (deferido):** retenção/expurgo/anonimização do log → subsistema
**LGPD**. Rate-limit granular, lockout e alertas → subsistema **endurecimento**.

---

## 2. Decisões (confirmadas no brainstorming)

| #   | Decisão                                   | Escolha                                                                                                                          |
| --- | ----------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Armazenamento de empresa_id/filial_id** | **Colunas reais** em `activity_log` + model custom `HT2ML\Core\Models\Activity` (config `activity_model`).                              |
| 2   | **Isolamento da tela**                    | Coluna + filtro de empresa **sempre presentes**; **isolamento** por empresa ativa como padrão.                                   |
| 3   | **Quem vê cross-empresa**                 | super-admin **ou** nova permissão `auditoria.todas-empresas`. Demais ficam isolados à empresa ativa.                             |
| 4   | **Cobertura de eventos**                  | Ciclo de autenticação completo: login (sucesso/falha), bloqueio, logout, falha de 2FA, reset de senha (solicitação + aplicação). |
| 5   | **Captura dos eventos**                   | Serviço dedicado `AuditoriaSeguranca` + logging explícito nos pontos genuínos (não via eventos nativos do Laravel).              |
| 6   | **Retenção**                              | Deferida ao subsistema LGPD.                                                                                                     |

**Por que logging explícito e não listeners dos eventos de auth do Laravel:** o
login usa `Auth::guard('admin')->validate()` (não `Auth::attempt`), então **não**
emite o evento `Failed` para falha de credenciais; e os `Auth::login()` dos swaps de
impersonation disparariam `Login`/`Logout` indevidamente, exigindo guardas frágeis.
O fluxo 2FA é customizado. Logging explícito nos pontos reais é preciso, casa com o
padrão do projeto (Actions logam via `activity()`) e é imune a esse ruído.

---

## 3. Arquitetura & componentes

### Armazenamento (colunas reais)

- **Migration `add_tenant_to_activity_log`**: adiciona `empresa_id` e `filial_id`
  (`unsignedBigInteger`, **nullable, indexados, SEM FK** — o log é append-only e deve
  sobreviver à exclusão da empresa).
- **`HT2ML\Core\Models\Activity extends Spatie\Activitylog\Models\Activity`**: `$fillable`
  com `empresa_id`/`filial_id`; casts `integer`; relações `empresa()` e `filial()`
  (`belongsTo`, sem global scope — `Empresa`/`Filial` são raiz do tenant).
- **`config/activitylog.php`**: `'activity_model' => HT2ML\Core\Models\Activity::class`.

### Carimbo de contexto (refatorar o listener)

- Extrair o closure inline `Activity::creating` do `AppServiceProvider` para
  **`HT2ML\Core\Support\Audit\CarimbarContextoNaAtividade`** (invokable, testável). Ela:
    1. **sempre** preenche `empresa_id`/`filial_id` a partir do `TenantContext`
       (via `??=`, respeitando valor já setado);
    2. quando personificando, injeta `properties.impersonado_por` (lógica de
       impersonation migrada sem mudança de comportamento).
    - Registrada com `Activity::creating(...)` no `AppServiceProvider::boot()`.

### Eventos de segurança (serviço dedicado)

- **`App\Services\Admin\AuditoriaSeguranca`** (`log_name = 'auth'`):
    - `loginBemSucedido(AdminUser $u, bool $via2fa): void` → `event('login')`.
    - `loginFalhou(string $email): void` → `event('login-falhou')` (sem causer,
      `properties.email`).
    - `loginBloqueado(string $email): void` → `event('login-bloqueado')`.
    - `logout(AdminUser $u): void` → `event('logout')`.
    - `desafio2faFalhou(AdminUser $u): void` → `event('2fa-desafio-falhou')`.
    - `senhaResetSolicitada(string $email): void` → `event('senha-reset-solicitado')`.
    - `senhaResetAplicada(AdminUser $u): void` → `event('senha-reset-aplicado')`.
- **Pontos de chamada:**
    - `Login::authenticate()` — falha (após `RateLimiter::hit`), bloqueio (`tooManyAttempts`),
      sucesso sem-2FA (após `Auth::login`).
    - `TwoFactorChallenge::verificar()` — falha de código, bloqueio, sucesso com-2FA.
    - `LogoutController` — logout **genuíno** (ramo não-impersonation; o ramo de
      impersonation já loga `encerrada`).
    - `ForgotPassword` — solicitação; `ResetPassword` — aplicação.

### Autorização & tela

- Nova permissão **`auditoria.todas-empresas`** no catálogo (`config/access.php`,
  módulo `ModuloAcesso::Auditoria`), publicada via `access:sync`.
- **`AuditoriaTable`** (usar `HT2ML\Core\Models\Activity`):
    - `datasource()`: privilegiado (super-admin **ou** `can('auditoria.todas-empresas')`)
      → sem filtro de tenant; senão → `where('empresa_id', $empresaAtivaId)`, e se
      `$empresaAtivaId === null` → `whereRaw('1 = 0')` (não vaza eventos sem empresa).
      Eager-load `empresa`.
    - Coluna **Empresa** sempre presente (nome via `empresa`; "—" quando null/removida).
    - Filtro `Filter::select` de empresa exibido aos privilegiados.
- **`IndexAuditoria`**: mantém `authorize('auditoria.visualizar')`; o isolamento vive
  no `datasource` (lê `Auth::guard('admin')->user()` + `TenantContext`).

---

## 4. Fluxo de dados

**① CRUD/negócio sob empresa ativa:** Action chama `activity()->log()` →
`CarimbarContextoNaAtividade` preenche `empresa_id`/`filial_id` → persistido com tenant.

**② Evento de autenticação (fora de contexto):** ponto genuíno chama
`AuditoriaSeguranca::<método>` → `activity('auth')->...`. No `creating`, `empresa_id`
resolve para `null` (sem empresa ativa) → balde "sem empresa".

**③ Leitura na tela:** `IndexAuditoria` autoriza → `AuditoriaTable::datasource()`
aplica (ou não) o isolamento conforme privilégio. Privilegiado vê tudo + filtro;
isolado vê só a empresa ativa.

**④ Dados existentes:** colunas adicionadas como `null`, **sem backfill** (tenant
retroativo não é inferível com confiança).

---

## 5. Tratamento de erro & casos de borda

- **Sem empresa ativa** (auth, CLI, jobs, seeders, testes): `empresa_id` null, sem erro.
- **`??=`** preserva `empresa_id` setado explicitamente por uma Action.
- **Empresa excluída**: id permanece no histórico (sem FK); `empresa()` → null → "—".
  Log nunca é apagado por cascata (append-only).
- **Isolado sem empresa ativa**: `whereRaw('1 = 0')` evita que `where('empresa_id', null)`
  (→ `IS NULL`) vaze os eventos de autenticação. Filtro sempre por id concreto, nunca casa null.
- **Privilégio reavaliado por request**: perder a permissão volta a isolar na hora.
- **Model custom**: `activity()` passa a instanciar `HT2ML\Core\Models\Activity`; consultas via
  a classe do spatie continuam na mesma tabela; `AuditoriaTable` usa a classe custom para
  ter `empresa`. Testes de auditoria da impersonation seguem válidos (model estende o do spatie).
- **Refator do listener** preserva exatamente o comportamento de impersonation
  (`ImpersonationAuditoriaTest` continua verde).

---

## 6. Estratégia de testes

Pest/Feature (SQLite em memória) em `tests/Feature/Admin/Auditoria/`:

- **`CarimboContextoTest`**: carimbo de `empresa_id`/`filial_id` com/sem contexto;
  `empresa()` resolve; `??=` preserva valor explícito; regressão de `impersonado_por`.
- **`AuditoriaSegurancaTest`** (integração nos pontos reais): `Login` (falha →
  `login-falhou`; bloqueio → `login-bloqueado`; sucesso sem-2FA → `login`);
  `TwoFactorChallenge` (falha → `2fa-desafio-falhou`; sucesso → `login` `2fa=true`);
  logout genuíno → `logout` (e logout em impersonation **não** duplica); `ForgotPassword`
  → `senha-reset-solicitado`; `ResetPassword` → `senha-reset-aplicado`.
- **`AuditoriaIsolamentoTest`** (via `Livewire::test(AuditoriaTable)`): gestor isolado vê
  só a empresa ativa (não outra, não "sem empresa"); super-admin vê tudo; portador de
  `auditoria.todas-empresas` vê tudo; isolado sem empresa ativa → nada.

Metas: Pint ✓ · PHPStan nível 6 ✓ · suíte completa verde.

---

## 7. Arquivos (resumo)

**Criar:**

- `database/migrations/<ts>_add_tenant_to_activity_log.php`
- `app/Models/Activity.php`
- `app/Support/Audit/CarimbarContextoNaAtividade.php`
- `app/Services/Admin/AuditoriaSeguranca.php`
- `tests/Feature/Admin/Auditoria/{CarimboContexto,AuditoriaSeguranca,AuditoriaIsolamento}Test.php`

**Modificar:**

- `config/activitylog.php` (`activity_model`)
- `config/access.php` (permissão `auditoria.todas-empresas`)
- `app/Providers/AppServiceProvider.php` (registrar o invokable; remover o closure inline)
- `app/Livewire/Admin/Auth/Login.php` (falha/bloqueio/sucesso sem-2FA)
- `app/Livewire/Admin/Auth/TwoFactorChallenge.php` (falha/bloqueio/sucesso com-2FA)
- `app/Http/Controllers/Admin/Auth/LogoutController.php` (logout genuíno)
- `app/Livewire/Admin/Auth/ForgotPassword.php` (solicitação)
- `app/Livewire/Admin/Auth/ResetPassword.php` (aplicação)
- `app/Livewire/Admin/Auditoria/AuditoriaTable.php` (model custom, isolamento, coluna/filtro empresa)

---

## 8. Não objetivos

- Retenção/expurgo/anonimização do log (LGPD).
- Backfill de tenant em registros antigos.
- Alertas de atividade suspeita / lockout / rate-limit granular (endurecimento).
- Logar `Empresa`/`Filial`/`PermissionGrant` via `LogsActivity` (já cobertos pelas Actions).
