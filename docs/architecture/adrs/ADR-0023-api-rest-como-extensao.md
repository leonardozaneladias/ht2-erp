---
title: 'ADR-0023: API REST é extensão — canal no core, Sanctum e contrato na extensao-api'
version: 1.0.0
date: 2026-10-25
status: accepted
---

# ADR-0023: API REST é extensão — canal no core, Sanctum e contrato na `extensao-api`

**Status:** Accepted | **Data:** 2026-10-25 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, fronteira, api, sanctum, openapi, webhook

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md) e [ADR-0021](ADR-0021-taxonomia-modulo-recurso-area-secao.md)): **core** são os pacotes `ht2ml/*` da plataforma; **produto** é uma aplicação que instala o core; **módulo** é uma área de negócio; **extensão** é o pacote que carrega um módulo ou uma biblioteca.

## Contexto e problema

A plataforma nasceu de um produto com API pública (o Portal ArtFinal). Quatro ADRs daquela época tratavam de API: versionamento `api/v1` (ADR-0001), Sanctum em modo duplo (ADR-0003), OpenAPI gerado pelo Scramble (ADR-0007) e webhook com HMAC e idempotência (ADR-0013). Os quatro foram retirados no commit `8a8b58b` (2026-05-29), junto com o domínio ArtFinal. A plataforma ficou só com o painel.

O EduConecta trouxe a API de volta: o backend dele serve uma API REST v1 consumida pelo app mobile do responsável. Ao montar essa API, o produto esbarrou em três faltas:

- **Um módulo não tinha onde pendurar uma rota de API.** O `ModuleRegistry` oferecia o painel (`/admin`), rotas públicas e webhooks. Uma rota sem sessão e sem CSRF, sob `/api`, obrigava a editar o `routes/api.php` do produto, que é exatamente o que o [ADR-0015](ADR-0015-modulos-pacotes-composer.md) quis evitar.
- **O contrato da API era reescrito por produto.** Envelope de resposta, paginação, idioma, envelope de erro, limiter, tabela de tokens e a checagem de OpenAPI viviam em `App\` do EduConecta. Um segundo produto com API copiaria tudo.
- **O `bootstrap/app.php` chamava `statefulApi()`.** Isso pendura o middleware do Sanctum no grupo `api`. A plataforma não instala o Sanctum, então a primeira rota de API de um módulo estouraria "class not found".

A pergunta é onde cada peça mora: no core, numa extensão ou no produto.

## Drivers da decisão

- O critério de granularidade do [ADR-0020](ADR-0020-catalogos-universais-no-core.md): **um pacote existe quando um cliente pode plausivelmente não instalá-lo.** Um produto só de painel não tem API, e não deveria carregar o Sanctum e o Scramble por isso.
- A dependência é de mão única ([ADR-0022](ADR-0022-dependencia-de-mao-unica.md)): quando falta canal, abre-se o canal no core. Ninguém edita o core para pendurar uma rota.
- Quem é o usuário de uma API (um responsável, um integrador, um app) é decisão de domínio. O core não sabe, e não deveria saber.
- O contrato de uma API publicada é difícil de mudar. Um app nas lojas não é atualizado à força.

## Alternativas consideradas

### Alt 1: API inteira no core

Sanctum, envelope, OpenAPI e o canal de rota, tudo em `ht2ml/core`.

Rejeitada. Reprova no critério do ADR-0020: todo produto passaria a carregar `laravel/sanctum`, `dedoc/scramble` e a tabela `personal_access_tokens`, inclusive os que nunca terão API. E o core teria de escolher um guard, o que é decisão do produto.

### Alt 2: API inteira no produto

Cada produto monta a própria camada, como o EduConecta fez.

Rejeitada. É a cópia que motivou este ADR. E sem canal no core, cada módulo com API obrigaria a editar o `routes/api.php` do produto.

### Alt 3: canal no core, contrato numa extensão-biblioteca (escolhida)

O core ganha só o canal de rota, sem dependência nova. Tudo que tem dependência ou opinião vai para `ht2ml/extensao-api`.

## Decisão

**A API REST é uma extensão. O core só abre o canal.**

### No core: o canal `EscopoDeRota::Api`

O enum `EscopoDeRota` ganha o caso `Api`, com o atalho `ModuloBuilder::rotasDeApi()`. O hospedeiro é `packages/core/routes/api.php`.

| O core decide | O módulo decide |
| --- | --- |
| grupo `api` do Laravel (sem sessão, sem CSRF) | autenticação (`auth:sanctum` ou outro guard) |
| prefixo `/api` e name `api.`, impostos | versão (`v1`), como prefixo dentro do próprio arquivo de rotas |
| | middleware de cada rota |

O prefixo e o name são impostos pela mesma razão do webhook: uma rota sem sessão precisa ser reconhecível no `route:list`. A autenticação não é do escopo, porque o core não conhece o usuário da API. O `bootstrap/app.php` deixa de chamar `statefulApi()`.

O core não ganha nenhuma dependência com isto. É só roteamento.

### Na extensão: `ht2ml/extensao-api`

Uma extensão-biblioteca (`extra.ht2ml.tipo = biblioteca`), sem tela, sem permissão e sem dependência de `ht2ml/core`. Instalar o pacote já liga tudo:

- **Sanctum**: depende de `laravel/sanctum` e traz a migração de `personal_access_tokens`, idempotente (`hasTable`), para não quebrar o produto que já publicou a dele.
- **Envelope de resposta**: `ApiResponse` (`sucesso`, `pagina`, `erro`), `PaginacaoApi` (padrão e teto configuráveis) e a base `ApiResource`. Nunca se serializa um model cru.
- **Idioma**: o middleware `DefinirIdioma` resolve o locale por `Accept-Language`, depois pelo contrato `TemIdiomaPreferido` do usuário, depois pelo default da aplicação.
- **Erros**: `RenderizadorDeErroApi` faz toda exceção sob `api/*` sair como `{success: false, message, data: {}, errors}`, no idioma pedido. `ForcarRespostaJson` evita que uma rota de API sem `Accept` responda com redirect para o login do painel.
- **Usuário inativo**: o alias `api.principal-ativo` barra o usuário cujo model implementa `PodeSerDesativado` e está inativo.
- **Limiter**: `api`, por tipo + id do usuário (ou IP). Dois models autenticáveis com o mesmo id não dividem o balde. O produto que registrar o próprio limiter depois vence.
- **OpenAPI**: `php artisan api:openapi` grava `docs/openapi.json`; com `--verificar`, falha se o arquivo não descreve mais a API. É o portão de CI.

### O que vem dos ADRs retirados

Os ADRs de 2026-04 traziam argumentos que não dependiam do ArtFinal. Este ADR os herda, com as adaptações abaixo.

**Do ADR-0001 (versionamento `api/v1`).** Versão explícita na URL, e não em header: é visível, cacheável, aparece em log e em `route:list`, e as ferramentas de inspeção (curl, navegador) mostram URLs diferentes para versões diferentes. Sem versão, qualquer mudança incompatível exige coordenar todos os clientes, e um app publicado em loja não é atualizado à força. **A diferença:** lá o prefixo `api/v1` era global. Aqui o core impõe só `/api`, e **a versão fica com o módulo**. Módulos evoluem em ritmos diferentes; o `v2` de um não pode arrastar os outros.

**Do ADR-0003 (Sanctum).** Sanctum em vez de Passport (OAuth2 é complexidade que a plataforma não precisa) e em vez de JWT próprio (revogar token sem blacklist é problema, e fica fora do caminho oficial do Laravel). Um middleware só (`auth:sanctum`), tokens revogáveis individualmente, guard `admin` do painel separado. **A diferença:** o modo SPA com cookie (`statefulApi()`) não entra. A extensão entrega o modo token. O produto que precisar de SPA na mesma origem liga o modo stateful no próprio bootstrap.

**Do ADR-0007 (OpenAPI pelo Scramble).** A especificação é gerada a partir do código (FormRequest, Resource, tipos), sem anotação em controller. Anotação duplica o que já está no FormRequest e diverge em silêncio; spec escrita à mão diverge em semanas. **A diferença:** em vez de só servir a spec numa rota, o arquivo é versionado no produto e o CI confere (`api:openapi --verificar`). A comparação ignora `servers`, que o Scramble deriva do `APP_URL`. E o envelope de erro documentado é o real, não o padrão do Laravel.

**Do ADR-0013 (webhook com HMAC).** Validar a assinatura antes de qualquer persistência, sobre o corpo bruto, com `hash_equals`. IP de origem não prova autenticidade. **A diferença:** isto não foi para a `extensao-api`. Webhook não é API REST: é o canal `EscopoDeRota::Webhook`, que já era do core. O middleware `webhook.assinatura:{chave_de_config}[,{header}]` está no core (`ValidarAssinaturaWebhook`): HMAC-SHA256 do corpo bruto, aceita o prefixo `sha256=`, responde 401 para assinatura ausente ou errada e **503 quando o segredo não está configurado**, em vez de validar contra vazio. O parâmetro é a chave de config, nunca o segredo. A idempotência por evento e o processamento em job, que aquele ADR também fixava, continuam sendo do módulo que recebe o webhook.

## Consequências

**Positivas**

- Um módulo com API declara as rotas no próprio provider (`->rotasDeApi(...)`), sem editar o produto.
- Um produto só de painel não carrega Sanctum, Scramble nem a tabela de tokens.
- O segundo produto com API ganha envelope, erros em três idiomas, limiter e portão de OpenAPI com um `composer require`.
- O core continua sem saber quem é o usuário de uma API.

**Negativas, e assumidas**

- O envelope é opinião. Um produto que precise de outro formato (JSON:API, por exemplo) não usa a extensão, ou sobrescreve o renderizador.
- O canal `Api` pendura as rotas no grupo `api` do produto. O que o produto põe no grupo (o skeleton põe `throttle:api`) vale para todos os módulos.
- `ht2ml/extensao-api` exige um core com o canal `Api` (≥ 0.5, publicado em 2026-10-25).

## O que ficou de fora

- **Login, logout e `me` genéricos.** Dependem de quem é o usuário e de qual guard o autentica. Por enquanto são do produto.
- **Registro de aparelho (`device_tokens`)** para notificação push. Mesmo motivo.
- **Modo SPA com cookie** do Sanctum (ver acima).
- **Política de depreciação** (`Deprecation`, `Sunset`) que o ADR-0001 previa. Nenhum módulo tem uma segunda versão ainda.
- **Contratos de IA.** Ficam em `ht2ml/extensao-ia` ([ADR-0024](ADR-0024-ia-como-extensao-de-contratos.md)).

## Relação com outros ADRs

- Aplica o critério do [ADR-0020](ADR-0020-catalogos-universais-no-core.md) a uma capacidade, não a um catálogo.
- Segue o [ADR-0022](ADR-0022-dependencia-de-mao-unica.md): faltava canal, e o canal foi aberto no core.
- Recupera, adaptados, os argumentos dos ADRs retirados 0001, 0003, 0007 e 0013 (ver [índice](../index.md#números-retirados)).
- Não altera o [ADR-0012](ADR-0012-spatie-permission-guard-name.md): o guard do painel continua `admin`.
