# Módulo: Autenticação Admin

**Sprint:** 15  
**Última Atualização:** —  
**Status:** 🔴 Pendente  
**Referência PRD:** Seção 14.1 (Login Admin), 14.18-14.19 (Usuários e Perfis ACL)

## Escopo

Gerencia toda a autenticação do backoffice administrativo, incluindo login, logout, recuperação de senha, verificação de admin ativo, gestão de usuários admin e perfis ACL com permissões granulares.

## Models Envolvidos

| Model            | Tabela             | Campos Principais                                                     |
| ---------------- | ------------------ | --------------------------------------------------------------------- |
| `AdminUser`      | `admin_users`      | nome, email, password, perfil_id, ativo, last_login_at, last_login_ip |
| `AdminPerfil`    | `admin_perfis`     | nome, descricao, ativo                                                |
| `AdminPermissao` | `admin_permissoes` | nome, modulo, acao, descricao                                         |

## Services e Actions

| Classe | Método Principal | Responsabilidade                   |
| ------ | ---------------- | ---------------------------------- |
| —      | —                | Auth usa guards nativos do Laravel |

## Rotas

| Método | URI                           | Controller@Action                 | Middleware                 |
| ------ | ----------------------------- | --------------------------------- | -------------------------- |
| GET    | /admin/login                  | AuthController@showLogin          | guest:admin                |
| POST   | /admin/login                  | AuthController@login              | guest:admin, throttle:5,10 |
| POST   | /admin/logout                 | AuthController@logout             | auth:admin                 |
| GET    | /admin/forgot-password        | AuthController@showForgotPassword | guest:admin                |
| POST   | /admin/forgot-password        | AuthController@sendResetLink      | guest:admin                |
| GET    | /admin/reset-password/{token} | AuthController@showResetPassword  | guest:admin                |
| POST   | /admin/reset-password         | AuthController@resetPassword      | guest:admin                |

## Components Blade Utilizados

- `x-admin.layout` (auth variant) — Layout da página de login
- `x-shared.input` — Campos de email e senha
- `x-shared.loading-button` — Botão de login com loading
- `x-shared.alert` — Feedback de erro/sucesso
- `x-shared.toggle` — Checkbox "Lembrar-me"

## Regras de Negócio

1. Admin com `ativo = false` NÃO consegue logar (mensagem: "Sua conta está desativada")
2. Limite de 5 tentativas de login em 10 minutos (throttle padrão Laravel)
3. Após login, registrar `last_login_at` e `last_login_ip`
4. Após login, redirecionar para `/admin/dashboard`
5. Recuperação de senha via e-mail com link temporário
6. Toggle de visibilidade da senha no campo

## Telas / UI

**Login:** Usar estilo "Split" do Inspinia (imagem lateral + formulário)  
**Forgot Password:** Formulário simples centralizado  
**Reset Password:** Formulário com nova senha + confirmação + medidor de força

## Testes

| Teste                           | Tipo    | Cenário                                |
| ------------------------------- | ------- | -------------------------------------- |
| test_admin_pode_logar           | Feature | Login com credenciais válidas          |
| test_admin_inativo_nao_loga     | Feature | Login com admin ativo=false            |
| test_throttle_apos_5_tentativas | Feature | 6ª tentativa é bloqueada               |
| test_registra_last_login        | Feature | Verifica last_login_at e ip após login |
| test_logout                     | Feature | Logout e redirect para login           |

## Dependências

- Depende de: Migrations de admin_users, admin_perfis (Sprint 2)
- Dependido por: Todos os módulos admin

## Changelog do Módulo

| Data | Descrição                 |
| ---- | ------------------------- |
| —    | Módulo ainda não iniciado |
