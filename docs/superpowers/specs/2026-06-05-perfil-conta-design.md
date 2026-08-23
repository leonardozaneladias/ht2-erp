# Perfil & Conta (Épico 3 — subsistema 1)

> **Status:** design aprovado (brainstorming), aguardando plano de implementação.
> **Data:** 2026-06-05
> **Contexto:** starter kit Laravel 13 + Livewire 4 + Inspinia, guard `admin`,
> multi-empresa/filial. Substitui os placeholders `admin.perfil.*`/`admin.conta.*`
> por uma área "Minha conta" real. A aba **Segurança/2FA já existe** (Épico 2 Fase A,
> `SegurancaConta`). O Subsistema 2 do Épico 3 (Notificações in-app) é separado.

---

## 1. Objetivo

Dar ao admin autenticado uma área de **autoatendimento da própria conta**: ver/editar
seus dados (avatar, nome), trocar a própria senha, consultar o histórico de logins e
definir preferências pessoais (idioma e fuso horário). Tudo **self-service** — cada
usuário opera somente sobre a própria conta.

---

## 2. Decisões (confirmadas no brainstorming)

| #   | Decisão               | Escolha                                                                                                      |
| --- | --------------------- | ------------------------------------------------------------------------------------------------------------ |
| 1   | **Arquitetura de IA** | Uma área `/admin/conta` com **abas** (Perfil · Segurança · Preferências). `/admin/perfil` entra na 1ª aba.   |
| 2   | **Composição**        | Página tabulada + **componentes Livewire aninhados** por aba; reaproveita o `SegurancaConta` existente.      |
| 3   | **Capacidades**       | Avatar + nome; **trocar senha**; **preferências idioma/fuso**; **histórico de logins**. (E-mail só leitura.) |
| 4   | **Sessões**           | **Histórico de logins** (tabela própria, registra cada login). NÃO enumera/encerra sessões (driver Redis).   |
| 5   | **Abordagem**         | Componentes Livewire + Actions enxutas; 2 migrations (colunas de preferência + tabela de histórico).         |

---

## 3. Arquitetura & componentes

### Página tabulada (parent)

- **`App\Livewire\Admin\Conta\MinhaConta`** — Livewire full-page (`#[Layout]` +
  `#[Url] public string $aba = 'perfil'`). Renderiza `x-shared.tab-nav` e monta, inline,
  o componente aninhado da aba ativa. `$aba` sincroniza com a query string
  (`/admin/conta?aba=seguranca`) → abas deep-linkáveis, sem recarga.

### Rotas

- `GET /admin/conta` → `MinhaConta` (nome `admin.conta`; default `aba=perfil`).
- `admin.perfil.show` e `admin.conta.edit` deixam de ser placeholders → **redirect** para
  `admin.conta` (preservam links da topbar/sidebar).
- `admin.conta.seguranca` → redirect para `admin.conta?aba=seguranca` (mantém o deep link).
- `admin.conta.notificacoes` → **permanece placeholder** (central do Subsistema 2).
- Topbar/sidebar: "Meu perfil" e "Configurações da conta" passam a apontar para `admin.conta`.

### Abas (componentes aninhados)

- **Perfil** → `App\Livewire\Admin\Conta\PerfilConta` (`WithFileUploads`):
    - **Avatar**: upload (`image`, `mimes:jpg,jpeg,png,webp`, `max:2048`) → disco `public`
      em `avatars/` via `SettingsFileUploadService::substituir` (apaga o anterior) → grava o
      caminho em `avatar_url`. Remoção volta ao fallback de iniciais.
    - **Nome** editável; **e-mail** somente leitura; resumo (read-only) de papéis globais/
      empresas e do último login.
    - Salva via **`App\Actions\Admin\Conta\AtualizarPerfilAction`** (nome + avatar).
- **Segurança** → painel que monta três blocos:
    - **`App\Livewire\Admin\Conta\TrocarSenha`** (novo): senha atual + nova + confirmação;
      valida a atual contra o hash + regras `Password::defaults()`; ao salvar atualiza o hash,
      **regenera a sessão** e audita `activity('conta')->event('senha_alterada')`. A senha
      atual é a trava (sem modal extra de reconfirmação).
    - **`App\Livewire\Admin\Conta\SegurancaConta`** (existente, 2FA) — ajustado para render
      como painel (sem `#[Layout]` próprio quando aninhado).
    - **`App\Livewire\Admin\Conta\HistoricoLogins`** (novo, leitura): últimos ~10 acessos.
- **Preferências** → `App\Livewire\Admin\Conta\PreferenciasConta`: selects de **idioma**
  (`locale`) e **fuso horário** (`timezone`); salva nas novas colunas. Locales = os que a
  instância oferece; timezones = lista do PHP (`DateTimeZone`), destacando o Brasil.

### Componente compartilhado

- **`x-shared.avatar`** (novo): renderiza a imagem quando há `avatar_url`, senão **iniciais**
  do nome com cor determinística. Passa a ser usado na topbar/sidebar/perfil (consolida o
  `<img>` solto de hoje).

### Modelo de dados

- Migration `add_locale_timezone_to_admin_users`: `locale` (string, nullable) e `timezone`
  (string, nullable) em `admin_users`. Nulo = herda o padrão da instância (`GeneralSettings`).
- Migration `create_admin_login_history`: tabela `admin_login_history` — `id`,
  `admin_user_id` (FK `cascadeOnDelete`), `ip_address` (string 45, nullable), `user_agent`
  (string, nullable), `created_at` (index em `admin_user_id` + `created_at`). **Sem
  `empresa_id`** — histórico pessoal/global (o login ocorre antes do contexto de empresa).
- Model **`HT2ML\Core\Models\LoginHistory`** (`belongsTo` AdminUser). `AdminUser`: `$fillable`
  ganha `locale`/`timezone`; relação `loginHistory(): HasMany`.

---

## 4. Fluxo de dados

**① Registro de login.** Listener **`App\Listeners\RegistrarLoginAdmin`** no evento
`Illuminate\Auth\Events\Login` (filtrando o guard `admin`): atualiza `last_login_at`/
`last_login_ip` **e** insere uma linha em `admin_login_history` (IP + user-agent da request).
**Personificação é ignorada** (o `IniciarImpersonationAction` faz `Auth::login` do alvo →
detecto via flag/`TenantContext` de impersonação e não registro). Se já houver código setando
`last_login_*` no `Login.php`, migra para o listener (fonte única).

**② Aplicação das preferências.** Middleware **`App\Http\Middleware\AplicarPreferenciasUsuario`**
(no grupo `admin.auth`, após o contexto de tenant): `App::setLocale($user->locale ?? localeDaInstância)`
por request. O **fuso** é aplicado só na **exibição** (helper/Blade formata datas no `timezone`
do usuário); não altera `config('app.timezone')` global (que afetaria gravação). Nulo = herda
a instância.

**③ Edição do perfil.** `PerfilConta` → `AtualizarPerfilAction` (nome + avatar via
`SettingsFileUploadService`). **④ Troca de senha.** `TrocarSenha` valida a atual → atualiza
hash → regenera sessão → audita. **⑤ Preferências.** `PreferenciasConta` grava `locale`/
`timezone`. **⑥ Histórico.** `HistoricoLogins` lê os últimos N do próprio usuário.

---

## 5. Tratamento de erro & casos de borda

- **Troca de senha**: senha atual errada → erro de validação; nova == atual → bloqueia; nova
  fraca → `Password::defaults()`; sucesso → regenera a sessão (não derruba o próprio usuário).
- **Avatar**: tipo/tamanho inválido → erro; upload novo apaga o anterior; remoção volta ao
  fallback de iniciais.
- **Preferências**: `locale` fora da lista permitida ou `timezone` inválido (`in:` + lista do
  PHP) → erro; nulos aceitos (herdam a instância).
- **Self-service**: todos os componentes operam sobre `auth('admin')->user()`, nunca recebem
  id externo → sem risco cross-user/cross-empresa. Histórico é do próprio usuário (sem escopo
  de empresa).
- **Conta anonimizada/bloqueada** não acessa o painel (barrado no login, Épico 2) → não chega
  a estas telas.

---

## 6. Estratégia de testes

Pest/Feature (SQLite) + `Livewire::test` em `tests/Feature/Admin/Conta/`:

- **`PerfilContaTest`**: atualiza nome; upload de avatar salva e troca o arquivo (`Storage::fake`);
  fallback de iniciais quando `avatar_url` nulo.
- **`TrocarSenhaTest`**: troca com senha atual correta; rejeita atual errada / nova fraca / nova
  igual; audita `senha_alterada`.
- **`PreferenciasContaTest`**: salva `locale`/`timezone`; middleware aplica o locale; nulo herda
  a instância.
- **`HistoricoLoginsTest`**: o listener registra no login real e **não** registra em personificação;
  a aba lista os últimos N (ordem desc).
- **`MinhaContaTest`**: `?aba=` seleciona a aba; redirects de `admin.perfil.show`/`admin.conta.edit`/
  `admin.conta.seguranca` apontam para `admin.conta`.
- **`AvatarComponentTest`**: `x-shared.avatar` renderiza imagem com URL e iniciais sem URL.

Metas: Pint ✓ · PHPStan nível 6 ✓ · suíte completa verde.

---

## 7. Arquivos (resumo)

**Criar:**

- `database/migrations/<ts>_add_locale_timezone_to_admin_users.php`
- `database/migrations/<ts>_create_admin_login_history.php`
- `app/Models/LoginHistory.php`
- `app/Livewire/Admin/Conta/{MinhaConta,PerfilConta,TrocarSenha,HistoricoLogins,PreferenciasConta}.php` (+ views)
- `app/Actions/Admin/Conta/AtualizarPerfilAction.php`
- `app/Listeners/RegistrarLoginAdmin.php`
- `app/Http/Middleware/AplicarPreferenciasUsuario.php`
- `resources/views/components/shared/avatar.blade.php`
- `tests/Feature/Admin/Conta/{PerfilConta,TrocarSenha,PreferenciasConta,HistoricoLogins,MinhaConta,AvatarComponent}Test.php`

**Modificar:**

- `app/Models/AdminUser.php` (`locale`/`timezone` no `$fillable`; relação `loginHistory()`)
- `routes/admin.php` (rota `admin.conta` + redirects dos placeholders)
- `app/Livewire/Admin/Conta/SegurancaConta.php` (+ view) — render como painel quando aninhado
- `resources/views/components/admin/topbar.blade.php` e `sidebar.blade.php` (links → `admin.conta`; usar `x-shared.avatar`)
- `app/Providers/AppServiceProvider.php` (registrar o listener `RegistrarLoginAdmin`)
- `bootstrap/app.php` (registrar o middleware `AplicarPreferenciasUsuario` no grupo `admin.auth`)
- `app/Livewire/Admin/Auth/Login.php` — se setava `last_login_*`, mover para o listener

---

## 8. Não objetivos

- Trocar e-mail (fica somente leitura neste subsistema).
- Enumerar/encerrar sessões ativas (optamos por histórico de logins; driver de sessão é Redis).
- Preferências de notificação e central de notificações (**Subsistema 2 — Notificações in-app**).
- Realtime/broadcasting.
- Gestão de avatar/dados de outros usuários (a tela de Usuários do admin já cobre a edição administrativa).
