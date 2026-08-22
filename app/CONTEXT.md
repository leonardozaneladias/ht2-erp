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
