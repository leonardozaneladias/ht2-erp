# Histórico

Documentos que já não descrevem o estado atual da plataforma. Ficam aqui para consulta: decisões, planos entregues e rascunhos que nunca viraram realidade.

Não use nada desta pasta como instrução. Cada arquivo abre com um aviso que diz o que ele descrevia, quando, e onde está a versão atual, se houver.

Arquivados em 2026-10-25.

## Conteúdo

### `superpowers/` — planos e specs entregues

Seis designs (`specs/`) e seis planos de implementação (`plans/`) de maio e junho de 2026. Todos foram entregues; o código vive em `packages/core`. Os checkboxes dos planos nunca foram marcados.

| Tema                                   | Spec                                                                                           | Plano                                                                                     |
| -------------------------------------- | ---------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| Telas de autenticação em Livewire      | [login-auth-design](superpowers/specs/2026-05-27-login-auth-design.md)                         | [login-auth-implementation](superpowers/plans/2026-05-27-login-auth-implementation.md)    |
| Auditoria ciente de empresa/filial     | [auditoria-tenant-aware-design](superpowers/specs/2026-06-05-auditoria-tenant-aware-design.md) | [auditoria-tenant-aware](superpowers/plans/2026-06-05-auditoria-tenant-aware.md)          |
| Endurecimento de segurança             | [endurecimento-seguranca-design](superpowers/specs/2026-06-05-endurecimento-seguranca-design.md) | [endurecimento-seguranca](superpowers/plans/2026-06-05-endurecimento-seguranca.md)      |
| Impersonation de usuário               | [impersonation-fase-b-design](superpowers/specs/2026-06-05-impersonation-fase-b-design.md)     | [impersonation-fase-b](superpowers/plans/2026-06-05-impersonation-fase-b.md)              |
| LGPD                                   | [lgpd-design](superpowers/specs/2026-06-05-lgpd-design.md)                                     | [lgpd](superpowers/plans/2026-06-05-lgpd.md)                                              |
| Minha conta (perfil e preferências)    | [perfil-conta-design](superpowers/specs/2026-06-05-perfil-conta-design.md)                     | [perfil-conta](superpowers/plans/2026-06-05-perfil-conta.md)                              |

### `devops/` — rascunhos de abril de 2026

Escritos em 2026-04-18 como modelo, antes do projeto existir. Descrevem ferramentas e comandos que nunca foram instalados (Envoy, Docker Compose, Sentry, workflows de deploy).

- [ci-cd.md](devops/ci-cd.md) — pipeline de CI/CD planejado. O CI real está em [../devops/ci-cd.md](../devops/ci-cd.md).
- [runbook-deploy.md](devops/runbook-deploy.md) — runbook de deploy planejado. Não existe workflow de deploy.
- [monitoring-alerts.md](devops/monitoring-alerts.md) — observabilidade planejada (Sentry, Grafana). Sentry não foi instalado.

### Avulsos

- [auditoria-ux.md](auditoria-ux.md) — revisão de usabilidade do painel admin feita em 2026-06-12.
