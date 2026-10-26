# Documentação Técnica

Hub da documentação da **HT2ML Platform**, a plataforma de backoffice administrativo (Laravel + Livewire + Inspinia) sobre a qual se constroem produtos. O código da plataforma é o pacote `ht2ml/core`, em [`packages/core/`](../packages/core/README.md). O contexto e as convenções principais estão em [`CLAUDE.md`](../CLAUDE.md), e o glossário em [`CONTEXT-MAP.md`](../CONTEXT-MAP.md).

## Por onde começar

1. **Criar um produto novo** → [`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md)
2. **Criar um módulo e seus recursos** → [`guias/criar-extensao.md`](guias/criar-extensao.md) + [`criar-recurso.md`](criar-recurso.md)
3. **Criar um componente ou tela** → [`template/INSPINIA/CATALOGO-COMPONENTES.md`](template/INSPINIA/CATALOGO-COMPONENTES.md)
4. **Entender uma decisão de arquitetura** → [`architecture/index.md`](architecture/index.md)
5. **Subir o ambiente local** → [`devops/ddev-setup.md`](devops/ddev-setup.md) e [`devops/index.md`](devops/index.md)
6. **Exemplo de referência do stack** → o módulo Usuários Admin, no core: `packages/core/src/Livewire/Admin/Usuarios/`, `packages/core/src/Services/Admin/AdminUserService.php` e `packages/core/src/Actions/Admin/*Action.php`. Mostra FormRequest + Service + Action + DTO + Policy + Livewire + Activity Log + testes Pest aplicados juntos.

## Guias

| Documento | Conteúdo |
| --- | --- |
| [`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md) | Produto novo com `composer create-project` do skeleton, o que customizar e como atualizar o core |
| [`guias/criar-extensao.md`](guias/criar-extensao.md) | Extensão de ponta a ponta: módulo ou biblioteca, `ModuloBuilder`, escopos de rota, suíte, publicação |
| [`criar-recurso.md`](criar-recurso.md) | Gerador de CRUD (`make:recurso --modulo=`): tipos de campo, flags, stubs |
| [`comandos.md`](comandos.md) | Comandos artisan da plataforma (`ht2ml:doutor`, geradores, `access:sync`, `referencia:sync`…) |
| [`testes-e-guardas.md`](testes-e-guardas.md) | Suítes de teste, guardas de arquitetura (A1, A2, A4), analisador de dependências e CI |

## Funcionalidades do core

| Documento | Conteúdo |
| --- | --- |
| [`multi-empresa.md`](multi-empresa.md) | Empresas, filiais, contexto ativo e como tornar um recurso multiempresa |
| [`configuracoes-sistema.md`](configuracoes-sistema.md) | Configurações tipadas, branding, temas e Setup Wizard |
| [`lixeira.md`](lixeira.md) | Soft delete com três níveis de permissão |
| [`visualizacao.md`](visualizacao.md) | Ficha de visualização ("Ver") dos registros |
| [`superficie-do-core.md`](superficie-do-core.md) | O que uma extensão consome do core, medido nas extensões |

## Distribuição e fluxo de trabalho

| Documento | Conteúdo |
| --- | --- |
| [`fluxo-desenvolvimento.md`](fluxo-desenvolvimento.md) | Branches, PRs e o caminho de uma correção entre a plataforma, produtos e instâncias |
| [`distribuicao-manutencao.md`](distribuicao-manutencao.md) | **Runbook**: instância de cliente, propagar correções, releases |
| [`devops/release.md`](devops/release.md) | Ordem de release (core → extensões → skeleton), semver e breaking change |
| [`../CHANGELOG.md`](../CHANGELOG.md) | Mudanças, com as ações pós-merge de cada uma |

## Pastas

| Pasta | Conteúdo |
| --- | --- |
| [`architecture/`](architecture/index.md) | ADRs da plataforma, com status e os números retirados |
| [`devops/`](devops/index.md) | Setup DDEV, convenções de código e Git, CI, infra, ferramentas e segurança |
| [`template/INSPINIA/`](template/INSPINIA/CATALOGO-COMPONENTES.md) | Catálogo de componentes Blade (fonte de verdade) + docs de cada componente Inspinia |
| [`plans/modules/rh/`](plans/modules/rh/README.md) | Planejamento do módulo de RH (PRD, modelo de domínio, blueprint, ADRs do módulo) |
| [`agents/`](agents/domain.md) | Configuração lida pelas skills de agentes: [issue tracker](agents/issue-tracker.md) e [domain docs](agents/domain.md) |
| [`historico/`](historico/README.md) | Documentos que descrevem um estado passado: planos e specs entregues, auditorias datadas, docs de devops aposentados. Não use como referência do estado atual |
