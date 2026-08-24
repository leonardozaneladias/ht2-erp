# Endurecimento adicional de segurança (Épico 2 — Fase C · subsistema 2)

> **Status:** design aprovado (brainstorming), aguardando plano de implementação.
> **Data:** 2026-06-05
> **Contexto:** starter kit Laravel 13 + Livewire 4 + Inspinia, guard `admin`,
> multi-empresa/filial. Fase A (2FA, endurecimento de login, timeout), Fase B
> (impersonation) e Fase C-1 (auditoria tenant-aware) já na `main`. Este é o 2º
> subsistema da Fase C. LGPD é spec próprio (pendente).

---

## 1. Objetivo

Endurecer o acesso administrativo com: **lockout de conta** após falhas de login,
**rate-limit configurável** (cobrindo o reset de senha hoje desprotegido), **alertas
de atividade suspeita por e-mail** aos super-admins, e a **aplicação do status
`ativo`** (corrige um gap real: hoje um admin desativado ainda consegue logar).

---

## 2. Decisões (confirmadas no brainstorming)

| #   | Decisão                | Escolha                                                                                                                                                   |
| --- | ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Entrega de alertas** | E-mail aos super-admins, **event-driven**, via `Notification` em fila (in-app é Épico 3).                                                                 |
| 2   | **Lockout**            | **Por conta, temporário**: coluna durável `bloqueado_ate`; contagem de falhas no cache; auto-expira; super-admin desbloqueia manualmente; dispara alerta. |
| 3   | **Rate-limit**         | Cobrir `ForgotPassword::sendLink` **+** tornar os limites de login/2FA/reset **configuráveis** em `SegurancaSettings`.                                    |
| 4   | **Gatilhos de alerta** | Lockout de conta · início de personificação · login de super-admin (este último atrás de toggle, por ser ruidoso).                                        |
| 5   | **Status `ativo`**     | Bloquear no login (mensagem) **+** middleware derruba a sessão de quem for desativado.                                                                    |
| 6   | **Abordagem**          | Serviços dedicados + coluna `bloqueado_ate` + `Notification` em fila. Sem pacote externo.                                                                 |

---

## 3. Arquitetura & componentes

### Armazenamento & configuração

- **Migration `add_bloqueado_ate_to_admin_users`**: `bloqueado_ate` (timestamp
  nullable) — o lock durável. `AdminUser`: cast `datetime`, fillable, método
  `estaBloqueada(): bool` (`bloqueado_ate?->isFuture()`).
- **`SegurancaSettings`** (+ migration em `database/settings/`): `login_max_tentativas`
  (5), `login_janela_minutos` (1), `lockout_max_falhas` (10), `lockout_duracao_minutos`
  (15), `alertas_seguranca_habilitados` (true), `alerta_login_super_admin` (false).
  Renderizados em `AbaSeguranca` + view.

### Lockout (serviço)

- **`HT2ML\Core\Services\Admin\Security\ControleLockout`**:
    - `estaBloqueada(AdminUser): bool`.
    - `registrarFalha(string $email): void` — incrementa o contador por e-mail no
      `RateLimiter` (chave `lockout:<email>`, decay = `lockout_duracao_minutos`); ao
      atingir `lockout_max_falhas`, **se o usuário existir**, grava
      `bloqueado_ate = now + lockout_duracao_minutos`, limpa o contador e dispara
      `AlertaSeguranca::contaBloqueada`.
    - `liberar(AdminUser): void` — `bloqueado_ate = null` + limpa o contador.

### Rate-limit configurável (helper)

- **`HT2ML\Core\Services\Admin\Security\LimiteTentativas`**: `excedido(string $chave): bool`,
  `registrar(string $chave): void`, `limpar(string $chave): void` — lê
  `login_max_tentativas`/`login_janela_minutos` de `SegurancaSettings` (com piso
  `max(1, …)`). Refatora os `RateLimiter` inline de `Login` e `TwoFactorChallenge` e
  **adiciona throttle ao `ForgotPassword::sendLink`** (chave por e-mail+IP).

### Alertas (Notification em fila)

- **`HT2ML\Core\Enums\TipoAlertaSeguranca`** (`ContaBloqueada`, `PersonificacaoIniciada`,
  `LoginSuperAdmin`) — label/descrição PT-BR.
- **`HT2ML\Core\Notifications\AlertaSegurancaNotification`** (`implements ShouldQueue`, fila
  `emails`, canal `mail`): assunto + corpo a partir do tipo + contexto.
- **`HT2ML\Core\Services\Admin\Security\AlertaSeguranca`**: `contaBloqueada(AdminUser)`,
  `personificacaoIniciada(AdminUser $ator, AdminUser $alvo)`, `superAdminLogou(AdminUser)`.
  Cada um respeita `alertas_seguranca_habilitados` (e o de super-admin respeita
  `alerta_login_super_admin`), resolve `AdminUser::role('super-admin')->where('ativo', true)->get()`
  e faz `Notification::send(...)`.
- **Gatilhos**: `ControleLockout` (lockout), `IniciarImpersonationAction` (personificação),
  `Login`/`TwoFactorChallenge` (login de super-admin).

### Enforcement de `ativo`

- **`Login::authenticate()`**: após validar credenciais, checa `estaBloqueada`
  (recusa "conta temporariamente bloqueada") e `ativo` (recusa "conta desativada");
  não autentica nesses casos.
- **`HT2ML\Core\Http\Middleware\GarantirContaAtiva`** (cadeia autenticada, após `admin.auth`):
  desloga + redireciona ao login quem ficou `ativo = false` durante a sessão.

### Desbloqueio manual (UI)

- Ação de linha "Desbloquear" em `UsuariosTable` (visível quando bloqueado) →
  `ControleLockout::liberar`, autorizada por `usuarios.editar` +
  `HierarchyResolver::podeGerir`; badge "Bloqueada" no status.

---

## 4. Fluxo de dados

**① Login** (`Login::authenticate`): throttle (`LimiteTentativas`) → valida credenciais
→ se falha: `registrar` + `ControleLockout::registrarFalha` + `loginFalhou`; se ok:
checa `estaBloqueada` (recusa) e `ativo` (recusa) → senão limpa throttle +
`ControleLockout::liberar` + segue (2FA/login). Login efetivo de super-admin →
`AlertaSeguranca::superAdminLogou`.

**② Lockout** (`registrarFalha`): contador por e-mail; ao atingir o limite e havendo
usuário → `bloqueado_ate = now + duração`, limpa contador, `AlertaSeguranca::contaBloqueada`.

**③ Personificação** (`IniciarImpersonationAction`): após o swap já existente →
`AlertaSeguranca::personificacaoIniciada(ator, alvo)`.

**④ Sessão de inativo** (`GarantirContaAtiva`): `Auth::user()->ativo === false` →
logout + redireciona login com aviso.

**⑤ Alerta** (`AlertaSegurancaNotification`): `Notification::send($superAdminsAtivos, …)`
enfileira → e-mail (assunto + evento + quem/quando). Respeita os toggles.

**⑥ Desbloqueio** (`UsuariosTable`): ator autorizado → `ControleLockout::liberar`.

---

## 5. Tratamento de erro & casos de borda

- **Lockout auto-expira** (`estaBloqueada` checa futuro); sem job de limpeza.
- **E-mail inexistente**: conta no throttle, **não** bloqueia (sem usuário) e não alerta.
- **Login ok / desbloqueio** limpam `bloqueado_ate` + contador. Contador de lockout
  tem chave própria (por e-mail), separada do throttle de login (por e-mail+IP).
- **`ativo`**: `GarantirContaAtiva` só no grupo autenticado, redireciona ao login (fora
  do grupo) → sem loop. Auto-desativação já é impedida pelo `ToggleAdminUserStatusAction`.
- **Alertas**: e-mail em fila não bloqueia o fluxo; sem worker → atrasa; mailer
  `log`/SMTP off → vai ao log, sem crash; sem super-admins → `Notification::send([])`
  é no-op; toggles desligam alertas.
- **Rate-limit**: settings com piso `max(1, …)`, lidos a cada tentativa; `ForgotPassword`
  throttled responde de forma consistente (não revela existência do e-mail).
- **Migração**: `bloqueado_ate` nullable default null — usuários existentes intactos.

---

## 6. Estratégia de testes

Pest/Feature (SQLite em memória) em `tests/Feature/Admin/Seguranca/`:

- **`LockoutContaTest`**: N falhas → `bloqueado_ate` futuro; conta bloqueada recusa
  login mesmo com senha correta; auto-expira; login ok/`liberar` limpam; e-mail
  inexistente conta mas não bloqueia.
- **`ContaAtivaTest`**: login de inativo recusado ("conta desativada"); `GarantirContaAtiva`
  desloga quem foi desativado; ativo segue `assertOk`.
- **`RateLimitConfigTest`**: limites lidos de `SegurancaSettings` (2 → barra na 3ª);
  `ForgotPassword::sendLink` throttled; piso `max(1, …)`; regressão do `EndurecimentoLoginTest`
  (default 5) verde.
- **`AlertaSegurancaTest`** (`Notification::fake()`): lockout/personificação/login-super-admin
  enviam aos super-admins ativos; toggles suprimem; sem super-admins → no-op.
- **`DesbloqueioContaTest`** (`Livewire::test(UsuariosTable)`): autorizado desbloqueia;
  sem permissão/hierarquia → bloqueado.

Metas: Pint ✓ · PHPStan nível 6 ✓ · suíte completa verde.

---

## 7. Arquivos (resumo)

**Criar:**

- `database/migrations/<ts>_add_bloqueado_ate_to_admin_users.php`
- `database/settings/<ts>_add_endurecimento_settings.php`
- `app/Services/Admin/Security/{ControleLockout,LimiteTentativas,AlertaSeguranca}.php`
- `app/Enums/TipoAlertaSeguranca.php`
- `app/Notifications/AlertaSegurancaNotification.php`
- `app/Http/Middleware/GarantirContaAtiva.php`
- `tests/Feature/Admin/Seguranca/{LockoutConta,ContaAtiva,RateLimitConfig,AlertaSeguranca,DesbloqueioConta}Test.php`

**Modificar:**

- `app/Models/AdminUser.php` (`bloqueado_ate` cast/fillable + `estaBloqueada()`)
- `app/Settings/SegurancaSettings.php` (6 propriedades novas)
- `app/Livewire/Admin/Auth/Login.php` (throttle configurável + `estaBloqueada`/`ativo` + alerta super-admin + `registrarFalha`)
- `app/Livewire/Admin/Auth/TwoFactorChallenge.php` (throttle configurável + alerta super-admin)
- `app/Livewire/Admin/Auth/ForgotPassword.php` (throttle)
- `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php` (alerta de personificação)
- `app/Livewire/Admin/Configuracao/AbaSeguranca.php` (+ view, + DTO/Action de save) — novos campos
- `app/Livewire/Admin/Usuarios/UsuariosTable.php` (ação Desbloquear + badge)
- `routes/admin.php` (middleware `GarantirContaAtiva` na cadeia autenticada)

---

## 8. Não objetivos

- Notificações **in-app** (sino/database) — Épico 3.
- Alertas além dos 3 gatilhos (ex.: novo dispositivo/geolocalização).
- Retenção/expurgo do `activity_log` e export LGPD — subsistema LGPD.
- Bloqueio por IP/firewall, captcha — fora do escopo.
- Scheduler de limpeza de locks (auto-expiram por `bloqueado_ate`).
