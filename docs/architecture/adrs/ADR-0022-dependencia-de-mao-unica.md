---
title: 'ADR-0022: A dependência entre core e extensão é de mão única'
version: 1.0.0
date: 2026-08-25
status: accepted
---

# ADR-0022: A dependência entre core e extensão é de mão única

**Status:** Accepted | **Data:** 2026-08-25 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, fronteira, dependência

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md) e [ADR-0021](ADR-0021-taxonomia-modulo-recurso-area-secao.md)): **core** são os pacotes `ht2ml/*` da plataforma; **produto** é uma aplicação que instala o core; **extensão** é um pacote que carrega um módulo ou uma biblioteca.

## Contexto e problema

Metade desta regra já estava decidida. O [ADR-0015](ADR-0015-modulos-pacotes-composer.md) fixou a direção
"o produto e a extensão não editam o core", e construiu o `ModuleRegistry` para
que não precisassem: rotas, seeders, permissões e itens de menu passaram a ser
**declarados** pelo pacote e **aplicados** pelo core.

A direção inversa — **o core não conhece extensão nenhuma** — nunca foi decidida.
Ela aparecia em prosa, em dois documentos que a tratavam como se já fosse
consenso. E foi violada:

```php
// packages/core/src/Actions/Admin/Menu/AplicarMenuPadraoAction.php
'grupo-tab-rh' => ['label' => 'RH', 'secao_key' => 'tabelas-auxiliares', 'ordem' => 2],
// ...
'ref-cnaes' => ['grupo_key' => 'grupo-tab-cadastros', 'ordem' => 8],
'rh-departamentos' => ['grupo_key' => 'grupo-tab-rh', 'ordem' => 1],
```

Seis chaves de duas extensões, dentro de uma Action do core, sem uma linha de
`use` — o que significa que nenhum arch test de namespace jamais as pegaria.

A violação não foi por descuido. Ela existia porque **faltava canal**: o
`ModuleRegistry` sabia declarar permissão e item de menu, mas não sabia declarar
*ordem* nem *grupo*. Sem canal, quem quisesse a disposição padrão só tinha o
core para escrevê-la. É a lição a registrar: **toda violação desta regra é
sintoma de um canal que falta, e a correção é abrir o canal, nunca abrir exceção.**

## Drivers da decisão

- Um core que conhece extensão pelo nome não é instalável sem ela — o pacote passa a ter dependência não declarada, e o guard "core sozinho num Laravel limpo" reprova.
- Uma extensão nova precisa entrar sem editar o core; senão a plataforma é um monólito com passos extras.
- O EduConecta traz quatro módulos de uma vez. Uma regra que depende de disciplina humana não sobrevive a quatro.

## Decisão

**O core não referencia extensão alguma — nem por classe, nem por literal de string.**

Ele aplica o que foi declarado sem saber de quem é. Quando o core precisa de um
comportamento que só uma extensão sabe fornecer, o corte é no **contrato**: o
core declara a interface, a extensão a implementa, o produto liga as duas. O
padrão já está em uso em `packages/core/src/Contracts/Referencia/`.

A regra vale nos dois formatos porque há duas formas de violá-la, e uma escapa
das ferramentas convencionais:

| Guard | Violação | Verificação |
| --- | --- | --- |
| **A1** | referência de classe (`use HT2ML\Rh\Models\Funcionario`) | arch test, com a lista derivada dos `packages/*/composer.json` |
| **A2** | literal de string (`'grupo-tab-rh'`) | varredura `token_get_all` sobre `packages/core/src`, comparada com as chaves que as configs das extensões declaram |

Os dois estão em `tests/Arch/CoreNaoConheceExtensaoTest.php` e rodam no CI.
Nenhum dos dois tem allowlist de extensões conhecidas: a lista é derivada, para
que uma extensão nova entre no guard sozinha.

### Exceção

Nenhuma. Uma proposta de exceção é o sinal de que falta canal — abra o canal.

## Alternativas consideradas

**Escrever só o guard, sem ADR** (rejeitada). Um teste sem decisão registrada
perde a primeira discussão sobre exceção: "é só uma string", "é temporário",
"é só até o release". O ADR é o que dá ao guard algo em que se apoiar.

**Allowlist de chaves toleradas** (rejeitada). Apodrece na próxima extensão, e
o EduConecta traz quatro. Pior: uma allowlist transforma a pergunta "isto pode?"
em "isto já está na lista?", que é uma pergunta diferente e mais fácil de
responder com "sim".

**Aceitar o literal e proibir só a classe** (rejeitada). É a fronteira que já
existia de fato — e foi exatamente por ela que as seis chaves passaram.

## Consequências

**Positivas**

- `AplicarMenuPadraoAction` (60 linhas) e `MenuPadraoSeeder` deixam de existir. O que elas faziam — agrupar e ordenar — virou declaração na config de cada dono.
- Ganho colateral: uma instalação nova nascia com **23 linhas** em `menu_personalizacoes` que nenhum humano tinha escolhido, e a tela de Gestão de Menus marcava todas como "personalizado". Agora a tabela nasce vazia e cada linha volta a significar uma decisão humana.
- O guard "core sozinho num Laravel limpo" ([ADR-0017](ADR-0017-produto-novo-via-skeleton.md)) passa a ter chance de ser verdade.

**Negativas, e assumidas**

- Cada canal novo é superfície nova no `ModuleRegistry`, e superfície custa manutenção. O contrapeso está escrito: **middleware, comando artisan, listener, tradução e asset não viram canal** — o Laravel já os resolve por pacote, e um canal ali só acrescentaria indireção e uma nova forma de falhar em silêncio.
- O guard A2 é uma varredura de literais: uma chave de extensão que por acaso coincida com uma palavra usada no core dá falso positivo. O custo é uma discussão em PR, e o benefício é pegar a classe de violação que nenhuma outra ferramenta pega.

## Relação com outros ADRs

- Completa o [ADR-0015](ADR-0015-modulos-pacotes-composer.md): ele fixou a direção extensão → core; esta fixa core → extensão.
- Apoia o [ADR-0019](ADR-0019-plataforma-abstrata-sem-produto.md): uma plataforma que conhece um produto pelo nome não é abstrata.
- Depende do vocabulário do [ADR-0021](ADR-0021-taxonomia-modulo-recurso-area-secao.md).
