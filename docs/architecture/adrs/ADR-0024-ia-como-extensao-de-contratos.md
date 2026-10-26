---
title: 'ADR-0024: IA é uma extensão de contratos, com fakes ligados por padrão'
version: 1.0.0
date: 2026-10-25
status: accepted
---

# ADR-0024: IA é uma extensão de contratos, com fakes ligados por padrão

**Status:** Accepted | **Data:** 2026-10-25 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, fronteira, ia, contratos, custo

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md) e [ADR-0021](ADR-0021-taxonomia-modulo-recurso-area-secao.md)): **core** são os pacotes `ht2ml/*` da plataforma; **produto** é uma aplicação que instala o core; **módulo** é uma área de negócio; **extensão** é o pacote que carrega um módulo ou uma biblioteca.

## Contexto e problema

O EduConecta usa IA em três pontos: traduzir comunicados, resumir textos e responder perguntas com um assistente. As interfaces (`TradutorInterface`, `ResumidorInterface`, `AssistenteInterface`), os fakes e um `TraducaoService` com cache viviam em `App\Services\Ia` do produto, ligados no `AppServiceProvider`.

Isso funciona enquanto quem chama a IA é código do produto. Deixa de funcionar quando quem chama é um **módulo**. Os módulos do EduConecta são pacotes (`packages/modulo-*`), e um pacote não pode importar de `App\`: o namespace é do produto que o instala, e outro produto não tem aquela classe. O resultado previsível é cada módulo declarar a própria interface de tradução e o próprio fake, com assinaturas que divergem.

Além disso, IA tem dois problemas que o resto do sistema não tem:

- **Custo por chamada.** Um provedor cobra por token. Um laço, um usuário insistente ou um texto de megabytes viram fatura.
- **Proveniência.** "Qual modelo escreveu isto?" é pergunta de auditoria. Um texto gerado por IA precisa dizer de onde veio.

## Drivers da decisão

- O critério de granularidade do [ADR-0020](ADR-0020-catalogos-universais-no-core.md): **um pacote existe quando um cliente pode plausivelmente não instalá-lo.** Nem todo produto usa IA.
- Um módulo-pacote só pode depender de outro pacote, nunca de `App\`.
- Um produto precisa funcionar de ponta a ponta sem credencial de provedor: em desenvolvimento, no CI e na demonstração.
- A plataforma não deve escolher provedor. Preço, qualidade e contrato com o fornecedor mudam mais rápido que a plataforma.

## Alternativas consideradas

### Alt 1: contratos no core

Rejeitada. Reprova no critério do ADR-0020: todo produto carregaria a tabela de cache e os limiters de IA sem usar. E o core passaria a ter opinião sobre um domínio (tradução de conteúdo, assistente) que nem todo produto tem.

### Alt 2: contratos em cada módulo

Cada módulo declara a interface de que precisa.

Rejeitada. É a divergência descrita acima. Dois módulos no mesmo produto teriam dois tradutores, dois bindings e dois caches. O produto ligaria o provedor real duas vezes.

### Alt 3: contratos no produto, módulos recebem por injeção de um tipo genérico

Rejeitada. Sem um tipo compartilhado, o módulo não tem o que tipar. Um `callable` ou um array perde a assinatura e o `identificador()`.

### Alt 4: uma extensão-biblioteca de contratos (escolhida)

`ht2ml/extensao-ia`, um pacote que módulos e produtos podem exigir. Ele define os contratos e os fakes, e não escolhe provedor.

## Decisão

**IA é a extensão-biblioteca `ht2ml/extensao-ia`: contratos, fakes ligados por padrão, cache de tradução e limites por custo. O provedor real é do produto.**

O pacote não depende de `ht2ml/core` (só de `illuminate/*`), não tem tela e não declara permissão.

### Contratos

| Interface | Método | Retorno |
| --- | --- | --- |
| `TradutorInterface` | `traduzir($texto, $origem, $destino)` | `array{texto}` |
| `ResumidorInterface` | `resumir($titulo, $corpo, $idioma)` | `array{texto}` |
| `AssistenteInterface` | `responder($pergunta, ?array $contexto, $idioma)` | `array{resposta}` |

O contexto do assistente é um **mapa livre**, montado por quem chama (o aluno em foco, o pedido aberto). O que é relevante é de domínio, e o contrato não sabe.

### Fakes ligados por padrão

O provider liga os três fakes com `bindIf`. Um produto sem credencial funciona de ponta a ponta, e a saída do fake é reconhecível (`[pt→en] texto`). Para ligar um provedor real, o produto troca o binding no próprio `AppServiceProvider`, que boota depois:

```php
$this->app->bind(TradutorInterface::class, AnthropicTradutor::class);
```

O pacote não traz nenhum provedor real.

### `identificador()` para proveniência

Toda implementação declara `identificador(): string`, o nome do provedor que atendeu a chamada. Quem grava um texto gerado grava também esse nome. A alternativa óbvia, uma coluna com `default('fake')` na migração, responderia `fake` em silêncio no dia em que um provedor real fosse ligado. Por isso a coluna `provedor` do cache não tem default.

### Cache de tradução compartilhado entre empresas

O `TraducaoService` guarda cada tradução em `ia_traducoes_cache`, com índice único em hash SHA-256 do texto + idioma de destino. O mesmo texto não é traduzido duas vezes para o mesmo idioma, **nem entre empresas**.

Isso é deliberado. O model `TraducaoEmCache` não tem escopo de empresa: um global scope por tenant quebraria o compartilhamento, que é o que economiza a chamada. A tradução de um texto não revela nada que o texto já não diga a quem o tem. `empresa_id` fica só como referência de quem disparou a primeira tradução, sem chave estrangeira. A tabela é cache e pode ser apagada sem perda; por isso também não é auditada.

### Limites por custo

- `TextoParaIa::limitar()` corta o que é **enviado** ao provedor em `config('ia.limite_caracteres')` (20.000). Não corta o que é gravado. O `TraducaoService` aplica o teto antes do cache: o cache poupa a segunda chamada, mas a primeira de um texto enorme custaria igual.
- Dois limiters, por tipo + id do usuário (ou IP): `throttle:ia` (10/min) para resumo e assistente, e `throttle:traducao` (20/min). São dois porque o custo é diferente: repetir uma tradução é grátis (cache), enquanto resumo e assistente geram de novo a cada pedido.

## Consequências

**Positivas**

- Um módulo-pacote tipa contra `HT2ML\Ia\Contracts\TradutorInterface` e funciona em qualquer produto que instale a extensão.
- O produto liga o provedor real num lugar só, e todos os módulos passam a usá-lo.
- CI e demonstração rodam sem credencial e sem custo.
- A pergunta "qual modelo escreveu isto?" tem resposta gravada.

**Negativas, e assumidas**

- Fake ligado por padrão significa que um produto em produção pode responder com texto de fake se ninguém trocar o binding. O `identificador()` torna isso visível nos dados, mas não impede.
- O cache compartilhado entre empresas é uma exceção consciente ao isolamento por empresa do [ADR-0018](ADR-0018-multiempresa-no-core-modo-single-tenant.md). Ela vale só para esta tabela, e o motivo está no model.
- Os contratos são estreitos (três operações). Uma capacidade nova (classificar, extrair) exige versão nova do pacote.

## O que ficou de fora

- **Provedores reais.** Nenhum adaptador (Anthropic ou outro) vem no pacote.
- **Streaming** de resposta do assistente.
- **Contabilidade de custo** (tokens gastos por empresa). Os limiters contam chamadas, não tokens.

## Relação com outros ADRs

- Aplica o critério do [ADR-0020](ADR-0020-catalogos-universais-no-core.md) a uma capacidade.
- Mesmo desenho do [ADR-0023](ADR-0023-api-rest-como-extensao.md): o que tem opinião e nem todo produto usa vai para uma extensão-biblioteca.
- Respeita o [ADR-0022](ADR-0022-dependencia-de-mao-unica.md): o core não conhece a extensão, e a extensão não depende do core.
- Abre uma exceção pontual ao isolamento por empresa do [ADR-0018](ADR-0018-multiempresa-no-core-modo-single-tenant.md) (cache de tradução).
