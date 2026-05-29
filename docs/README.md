# Documentação Técnica

Hub da documentação do boilerplate Laravel Admin (Inspinia + Livewire). O contexto e as convenções principais estão em [`CLAUDE.md`](../CLAUDE.md) na raiz.

## Estrutura

| Pasta | Conteúdo |
| ----- | -------- |
| [`template/INSPINIA/`](template/INSPINIA/CATALOGO-COMPONENTES.md) | Catálogo de componentes Blade (fonte de verdade) + docs de cada componente Inspinia |
| [`architecture/`](architecture/index.md) | ADRs de padrões reutilizáveis (monólito modular, ULID, idempotência, enums backed, dinheiro em centavos, Horizon, Spatie Permission) |
| [`devops/`](devops/index.md) | Setup, convenções, CI/CD, infra Docker, runbook de deploy, monitoramento e segurança |
| `superpowers/` | Planos e specs de implementação ativos |

## Por onde começar

1. **Criar um componente/tela** → [`template/INSPINIA/CATALOGO-COMPONENTES.md`](template/INSPINIA/CATALOGO-COMPONENTES.md)
2. **Decisões de arquitetura** → [`architecture/index.md`](architecture/index.md)
3. **Ambiente local e operação** → [`devops/index.md`](devops/index.md)
4. **Exemplo de referência do stack** → módulo Usuários Admin em `app/Livewire/Admin/Usuarios/`, `app/Services/Admin/AdminUserService.php`, `app/Actions/Admin/*Action.php` (mostra FormRequest + Service + Action + DTO + Policy + Livewire + Activity Log + testes Pest aplicados juntos)
