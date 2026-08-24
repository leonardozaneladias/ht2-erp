# LGPD (Épico 2 — Fase C · subsistema 3, último)

> **Status:** design aprovado (brainstorming), aguardando plano de implementação.
> **Data:** 2026-06-05
> **Contexto:** starter kit Laravel 13 + Livewire 4 + Inspinia, guard `admin`,
> multi-empresa/filial. Admin INTERNO (não é portal de clientes finais), então
> "consentimento" não se aplica. Fases A/B/C-1 (auditoria)/C-2 (endurecimento) do
> Épico 2 já na `main`. Este é o último subsistema da Fase C.

---

## 1. Objetivo

Atender os direitos LGPD do titular sobre os usuários administrativos: **acesso/
portabilidade** (export dos dados pessoais), **esquecimento** (anonimização
irreversível) e **retenção/expurgo** do `activity_log` (aplicar a política + expurgo
agendado e manual).

---

## 2. Decisões (confirmadas no brainstorming)

| #   | Decisão               | Escolha                                                                                                                                                                 |
| --- | --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Esquecimento**      | **Anonimização** irreversível da PII (mantém a linha + o `activity_log` append-only, com o causer preservado). Não é hard-delete.                                       |
| 2   | **Formato do export** | **JSON + PDF** (portabilidade + legível).                                                                                                                               |
| 3   | **Retenção**          | **Agendado** (`activitylog:clean` diário + aplicar `dias_retencao_logs` ao config) **+ botão manual** "Expurgar agora" (super-admin).                                   |
| 4   | **Permissões**        | **Dedicadas**: `usuarios.exportar-dados` e `usuarios.anonimizar` + hierarquia. Anonimização exige reconfirmação de senha + confirmação digitada. Expurgo = super-admin. |
| 5   | **Abordagem**         | Actions + controller (download), coluna `anonimizado_em`, modal Livewire. Sem pacote externo.                                                                           |

---

## 3. Arquitetura & componentes

### Armazenamento & permissões

- Migration `add_anonimizado_em_to_admin_users`: `anonimizado_em` (timestamp nullable).
  `AdminUser`: cast `datetime`, fillable, `estaAnonimizado(): bool`.
- `config/access.php` (módulo `ModuloAcesso::Usuarios`): `usuarios.exportar-dados`,
  `usuarios.anonimizar`.

### Export (Action + controller + rotas)

- **`HT2ML\Core\Actions\Admin\Lgpd\ExportarDadosUsuarioAction::execute(AdminUser): array`** —
  monta a PII estruturada:
    - **perfil**: nome, email, avatar_url, last_login_at/ip, status do 2FA (booleano,
      **nunca** o secret), ativo, anonimizado_em, datas;
    - **acessos**: papéis globais (spatie), empresas/filiais acessíveis, papéis por
      empresa, `permission_grants`;
    - **atividades**: `activity_log` onde o usuário é causer/subject — as **1000 mais
      recentes** (created_at, log_name, event, descrição).
- **`HT2ML\Core\Http\Controllers\Admin\LgpdController`** (thin): `exportarJson` (`response()->json`
  com `Content-Disposition: attachment`, `JSON_PRETTY_PRINT`) e `exportarPdf`
  (`Pdf::loadView('admin.lgpd.export', ...)->download()`). Cada um `authorize('exportarDados', $usuario)`
  e loga `activity('lgpd')->event('exportado')`.
- Rotas `GET /admin/usuarios/{usuario}/lgpd/json` e `/pdf` (nomes `admin.usuarios.lgpd.json`/`.pdf`).
- View `resources/views/admin/lgpd/export.blade.php` (HTML do PDF; sem CSS custom — usa estilos inline mínimos do dompdf).
- `AdminUserPolicy::exportarDados(auth, usuario)` = `can('usuarios.exportar-dados')`.

### Anonimização

- **`HT2ML\Core\Actions\Admin\Lgpd\AnonimizarUsuarioAction::execute(AdminUser $ator, AdminUser $alvo): void`**:
  guardas (não-self · não-super-admin · `HierarchyResolver::podeGerir` · não-já-anonimizado),
  em `DB::transaction`: `forceFill` com PII neutra (`nome`="Usuário anonimizado",
  `email`="anonimizado-{id}@removido.local", senha `Hash::make(Str::random(40))`,
  `avatar_url`/`last_login_ip`/`two_factor_secret`/`two_factor_recovery_codes`/
  `two_factor_confirmed_at`/`bloqueado_ate`/`perfil_ativo_role_id`/`empresa_ativa_id`/
  `filial_ativa_id` = null, `ativo`=false, `anonimizado_em`=now()), desfaz vínculos
  (`syncRoles([])`, `empresasAcessiveis()->detach()`, `filiaisAcessiveis()->detach()`,
  `papeisPorEmpresa()->detach()`, `permissionGrants()->delete()`), loga
  `activity('lgpd')->causedBy($ator)->performedOn($alvo)->event('anonimizado')`.
- `AdminUserPolicy::anonimizar(auth, usuario)` = `can('usuarios.anonimizar') && podeGerir`.
- UI: **`HT2ML\Core\Livewire\Admin\Lgpd\AnonimizarUsuario`** (modal montado em `IndexUsuarios`,
  acionado por ação de linha "Anonimizar (LGPD)" na `UsuariosTable`) — `ConfirmsPassword`
    - campo `confirmacao` que deve ser "ANONIMIZAR" → `Gate::authorize('anonimizar')` →
      a Action. Login já é barrado (senha embaralhada + `ativo=false`, Fase C-2). Badge
      "Anonimizado" na coluna de status; as ações de linha (editar/toggle/impersonate/
      desbloquear/exportar/anonimizar) **somem** para linhas anonimizadas.

### Retenção / expurgo

- `SettingsRuntimeApplier::aplicarSeguranca`: se `dias_retencao_logs > 0`, aplica a
  `config('activitylog.clean_after_days')`.
- `routes/console.php`: `Schedule::command('activitylog:clean')->daily();` (requer
  `schedule:run` no cron — documentado).
- **`HT2ML\Core\Actions\Admin\Lgpd\ExpurgarLogsAction::execute(): void`** — roda o
  `activitylog:clean` (`Artisan::call`) e loga `activity('lgpd')->event('logs-expurgados')`.
- Botão "Expurgar logs antigos" em `IndexAuditoria`, visível só a super-admin → a Action.

---

## 4. Fluxo de dados

**① Export:** ação de linha → rotas `lgpd.json`/`.pdf` → controller autoriza →
`ExportarDadosUsuarioAction` monta o array → JSON (attachment) ou PDF (dompdf). Auditado.

**② Anonimização:** ação de linha → modal (confirmação "ANONIMIZAR" + reconfirma senha)
→ `Gate::authorize('anonimizar')` → `AnonimizarUsuarioAction` (revalida guardas, transação:
PII neutra + senha embaralhada + `ativo=false` + desfaz vínculos + `anonimizado_em` + log).
Resultado: não autentica, sem acessos, linha preservada no log como "Usuário anonimizado".

**③ Retenção:** setting aplicado por request → config; `activitylog:clean` diário
(agendado) e/ou manual (super-admin) apaga registros mais antigos que o teto. Auditado.

**④ Export pós-anonimização:** reflete os valores neutros (a PII real já não existe).

---

## 5. Tratamento de erro & casos de borda

- **Guardas da anonimização** recusam com mensagem: self · super-admin · sem hierarquia ·
  já-anonimizado. Confirmação digitada/senha erradas → erros de validação. Transação faz
  rollback em falha.
- **E-mail neutro** único por id (sem colisão `unique`). **`activity_log` preservado**
  (causer mantido → exibido "Usuário anonimizado").
- **Export**: JSON completo; PDF limita às 1000 atividades mais recentes (dompdf lento).
  Secret do 2FA nunca exportado. Sem permissão → 403; `{usuario}` inexistente → 404.
- **Retenção**: aplica só se `dias_retencao_logs > 0` (aba já valida `min:1`); o expurgo é
  **global por idade** (não tenant-scoped); manual só super-admin; falha do command → flash
  de erro. As 3 operações LGPD são **auditadas**.
- **Scheduler**: `Schedule::command` só roda com `schedule:run` (cron) — documentado; o
  botão manual cobre ambientes sem cron.

---

## 6. Estratégia de testes

Pest/Feature (SQLite) em `tests/Feature/Admin/Lgpd/`:

- **`AnonimizarUsuarioTest`**: anonimiza (PII neutra, senha não confere, `ativo=false`,
  `anonimizado_em`, vínculos desfeitos, evento `anonimizado` com causer, `causer_id`
  preservado); guardas (self/super-admin/hierarquia/já-anonimizado).
- **`AnonimizarModalTest`** (`Livewire::test`): confirmação + senha → anonimiza; confirmação
  errada → erro; `assertForbidden` para operador sem permissão.
- **`ExportarDadosTest`** (HTTP): JSON 200 + attachment + seções; PDF 200 `application/pdf`;
  secret do 2FA ausente; sem permissão → 403.
- **`RetencaoLogsTest`**: `SettingsRuntimeApplier` aplica `dias_retencao_logs` ao config;
  `ExpurgarLogsAction` apaga atividade antiga e mantém a recente; expurgo manual só super-admin.
- **`CatalogoLgpdTest`**: `access:sync` publica as 2 permissões.

Metas: Pint ✓ · PHPStan nível 6 ✓ · suíte completa verde.

---

## 7. Arquivos (resumo)

**Criar:**

- `database/migrations/<ts>_add_anonimizado_em_to_admin_users.php`
- `app/Actions/Admin/Lgpd/{ExportarDadosUsuarioAction,AnonimizarUsuarioAction,ExpurgarLogsAction}.php`
- `app/Http/Controllers/Admin/LgpdController.php`
- `app/Livewire/Admin/Lgpd/AnonimizarUsuario.php` (+ view)
- `resources/views/admin/lgpd/export.blade.php`
- `tests/Feature/Admin/Lgpd/{AnonimizarUsuario,AnonimizarModal,ExportarDados,RetencaoLogs,CatalogoLgpd}Test.php`

**Modificar:**

- `app/Models/AdminUser.php` (`anonimizado_em` + `estaAnonimizado()`)
- `config/access.php` (2 permissões)
- `app/Policies/AdminUserPolicy.php` (`exportarDados`, `anonimizar`)
- `app/Services/Admin/Settings/SettingsRuntimeApplier.php` (aplicar `dias_retencao_logs`)
- `routes/console.php` (`Schedule::command('activitylog:clean')->daily()`)
- `routes/admin.php` (rotas de export)
- `app/Livewire/Admin/Usuarios/UsuariosTable.php` (ações "Exportar"/"Anonimizar", badge, ocultar ações p/ anonimizado)
- `resources/views/livewire/admin/usuarios/index-usuarios.blade.php` (montar o modal)
- `app/Livewire/Admin/Auditoria/IndexAuditoria.php` (+ view: botão "Expurgar logs")

---

## 8. Não objetivos

- Consentimento / base legal (admin interno, não se aplica).
- Portal de autoatendimento do titular (admin opera em nome do titular).
- Anonimização/reescrita retroativa da PII DENTRO de registros antigos do `activity_log`
  (append-only; o causer é resolvido pelo usuário já anonimizado).
- Configurar o cron/`schedule:run` (infra do ambiente; documentado, fora do código).
- Export de dados de empresas/filiais (escopo é a PII do usuário admin).
