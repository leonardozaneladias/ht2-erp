---
title: 'ADR-0020: Catálogos de uso universal ficam no core; só o de domínio vira extensão'
version: 1.0.0
date: 2026-08-23
status: accepted
---

# ADR-0020: Catálogos de uso universal ficam no core; só o de domínio vira extensão

**Status:** Accepted | **Data:** 2026-08-23 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, fronteira, catálogos, referência

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md)): **core** são os pacotes `ht2ml/*`; **produto** é uma aplicação que instala o core; **extensão** é uma unidade de negócio distribuída como pacote.

## Contexto e problema

O [ADR-0019](ADR-0019-plataforma-abstrata-sem-produto.md) fixou que a plataforma é abstrata e que nenhum produto vive dentro dela. Aplicando isso aos dez catálogos de referência, um estudo anterior propôs uma fronteira **purista, à la Odoo**: só o que é ISO ficaria no core (`paises`, `moedas`), e todo dado de país viraria pacote de localização — `ht2ml/localizacao-br` com `estados`, `municipios`, `tipos_logradouro`, `bancos` e `cargos`.

Essa fronteira foi aprovada e chegou a ser preparada: o [PR #72](https://github.com/leonardozaneladias/ht2-erp/pull/72) construiu a costura que permitiria ao core funcionar sem o pacote — três contratos (`FonteDeUnidadesFederativas`, `FonteDeMunicipios`, `FonteDeCargos`), um serviço que os implementa, e formulários que degradam de `select` para campo de texto quando a fonte está ausente.

Antes de executar a extração, o dono levantou a objeção que reabre a questão:

> "Pensando que esse core/plataforma sempre será usado para clientes grandes, não consigo enxergar nenhuma situação em que não vamos utilizar esses dados."

## Drivers da decisão

- O critério de granularidade já fixado: **um pacote existe quando um cliente pode plausivelmente não instalá-lo.**
- A plataforma é brasileira e atende clientes brasileiros. Internacionalização não está no horizonte.
- Custo recorrente (coordenação de versões) pesa mais que custo único (extração).
- O ADR-0019 proíbe **produto** dentro da plataforma — não dado de referência.

## O que a medição mostrou

| Catálogo           | Linhas | Consumidor fora do próprio CRUD |
| ------------------ | -----: | ------------------------------- |
| `paises`           |    193 | nenhum                          |
| `moedas`           |     35 | nenhum                          |
| `estados`          |     27 | `FormEmpresa`                   |
| `municipios`       |  5.571 | `FormEmpresa`                   |
| `tipos_logradouro` |     28 | nenhum                          |
| `bancos`           |    478 | nenhum                          |
| `cargos`           |     22 | `FormUsuario`, extensão de RH   |

Os sete somam **6.361 linhas e 188 KB de CSV**. Nenhum tem chave estrangeira apontando para ele: `filial.estado` é `string size:2`, `filial.cidade` é texto livre, `usuarios.cargo` é `string max:120`. São fontes de `<select>`, não integridade referencial.

## Alternativas consideradas

### Alt 1: fronteira purista, à la Odoo (rejeitada — era a decisão anterior)

Só ISO no core; dado de país em pacote de localização, auto-instalado pelo skeleton.

O precedente parecia forte: Odoo, ERPNext e Magento fazem exatamente isso. **Mas os três são produtos globais vendidos em muitos países.** A camada de localização deles existe porque um cliente francês genuinamente não quer 5.571 municípios brasileiros. Copiar a estrutura de um produto global para uma plataforma nacional importa o custo de coordenação sem importar o benefício.

E ela reprova no critério que a própria casa fixou: se nenhum cliente plausivelmente pula `localizacao-br`, ele não é um pacote — é um subdiretório com cerimônia de release.

### Alt 2: catálogos universais no core (escolhida)

Os sete ficam. Só sai o que pertence a um domínio.

### Alt 3: extrair mas manter instalado por padrão

Rejeitada: é a Alt 1 com o custo pago e o benefício não colhido. A matriz de versão continua existindo, e o caminho "pacote ausente" continua sem nunca executar.

## Decisão

**Catálogo de uso universal fica no core. Só vira extensão o catálogo que pertence a um domínio.**

A linha é a do critério de granularidade, não a da origem do dado:

| Fica no core                                                                        | Vira extensão                                                                                                  |
| ----------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `paises`, `moedas`, `estados`, `municipios`, `tipos_logradouro`, `bancos`, `cargos` | `cnaes`, `cfops`, `ncms` — [`ht2ml/extensao-fiscal-br`](https://github.com/leonardozaneladias/ht2-erp/pull/73) |
| Endereço, dinheiro e ocupação: todo cliente usa                                     | Classificação fiscal: só ERP e sistema fiscal usam                                                             |

**`ht2ml/localizacao-br` não será criado.**

Ser dado brasileiro não é o critério — se fosse, `cargos` (CBO) sairia junto com `cnaes`, e o `FormUsuario` do **core** passaria a depender de uma extensão, invertendo a regra de dependência que o ADR-0015 estabelece.

## Consequências

**Positivas**

- Uma camada a versionar em vez de duas; sem matriz de compatibilidade core × localização.
- `FormEmpresa` e `FormUsuario` deixam de ter dois comportamentos.
- Produto novo nasce com endereço, banco e cargo funcionando, sem `require` extra.

**Negativas, e assumidas**

- O core carrega 5.571 municípios brasileiros. Para clientes grandes e brasileiros, 188 KB é ruído — mas é uma dívida real se a plataforma um dia sair do Brasil. Reabrir este ADR é o caminho, e a extração de `fiscal-br` já provou que o mecanismo funciona.
- O alvo de enxugamento do core muda: **42 permissões e 7 itens de menu** só de catálogo. A meta anterior de "~41 permissões no core" passa para **~71**.

**A resolver**

A costura do PR #72 fica sem propósito. O binding em `AppServiceProvider::registrarCatalogos()` é **incondicional**, então `app()->bound(FonteDeUnidadesFederativas::class)` é sempre verdadeiro e o ramo de degradação para campo de texto é inalcançável em produção. Ele só é exercitado por `DegradacaoSemCatalogoTest`, que desfaz o binding à mão — um caminho de código que existe para satisfazer o próprio teste.

O serviço `CatalogoDeLocalidades` **não** está nessa conta: manter o Livewire longe do Eloquent é a convenção da casa, independente de empacotamento.

## Relação com outros ADRs

- Refina o [ADR-0019](ADR-0019-plataforma-abstrata-sem-produto.md): "abstrata" proíbe produto, não dado de referência.
- Aplica o critério de granularidade do plano de plataforma; não altera o [ADR-0015](ADR-0015-modulos-pacotes-composer.md).
- Não altera a decisão de que linha sincronizada é somente-leitura e linha do cliente é editável (coluna `origem`), que vale para os sete catálogos do core e para os três da extensão fiscal.
