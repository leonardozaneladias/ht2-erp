# Core

A plataforma compartilhada: isolamento de dados, controle de acesso, auditoria,
aparência e o mecanismo de extensão sobre o qual os produtos são montados.

## Isolamento de dados

**Empresa**:
A unidade de isolamento de dados. Todo registro pertencente a um produto
multiempresa é dela.
_Avoid_: tenant, organização, conta

**Filial**:
Subdivisão de uma Empresa. Não é unidade de isolamento: filtra, não isola.
_Avoid_: unidade, branch, estabelecimento

**Contexto**:
O par (Empresa, Filial) ativo na sessão de quem está usando o sistema.
_Avoid_: tenant, TenantContext, escopo ativo

## Acesso

**Administrador**:
Quem opera o produto por dentro, pelo backoffice.
_Avoid_: pessoa, operador, usuário admin

**Usuário**:
Quem é atendido pelo produto, por fora. O core define o mecanismo; o
significado é de cada produto — Responsável, Aluno, Graduado.
_Avoid_: pessoa, cliente final, usuário do portal

**Perfil**:
Conjunto nomeado de permissões atribuível a um Administrador, global ou
restrito a uma Empresa.
_Avoid_: papel, role (reservado à API do Spatie)

**Permissão**:
A autorização atômica que um Perfil concede.
_Avoid_: permission (fora da API do Spatie)

**Conta**:
O cadastro do próprio Administrador — dados pessoais, senha, preferências.
É o que ele *é*; o Perfil é o que ele *pode*.
_Avoid_: perfil do usuário
