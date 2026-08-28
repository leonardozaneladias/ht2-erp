---
title: 'ADR-0021: Módulo, recurso, área de acesso e seção de menu — quatro nomes, quatro coisas'
version: 1.0.0
date: 2026-08-25
status: accepted
---

# ADR-0021: Módulo, recurso, área de acesso e seção de menu — quatro nomes, quatro coisas

**Status:** Accepted | **Data:** 2026-08-25 | **Decisores:** HT2ML | **Tags:** arquitetura, plataforma, vocabulário, extensibilidade

> Nomenclatura (ver [CONTEXT-MAP.md](../../../CONTEXT-MAP.md)): **core** são os pacotes `ht2ml/*`; **produto** é uma aplicação que instala o core; **instância** é um deploy de um produto para um cliente.

## Contexto e problema

"Módulo" queria dizer quatro coisas ao mesmo tempo:

| Sentido | Onde aparecia | Exemplo |
| --- | --- | --- |
| unidade de negócio dentro do produto | `CONTEXT-MAP.md` | (sem implementação) |
| um CRUD | argumento de `make:modulo Funcionario` | Funcionário |
| um pacote de extensão | flag `--module=Rh` | `ht2ml/extensao-rh` |
| **gaveta do catálogo de permissões** | enum `ModuloAcesso`, 11 casos | `tabelas_auxiliares` |

O quarto é o que impede qualquer unificação, e é o mais invisível:
`tabelas_auxiliares` agrupa CNAE (da extensão fiscal) **e** Estados (do core).
Atravessa pacotes por natureza, então nunca poderá ser sinônimo dos outros três.

O custo não era estético. Com os quatro sentidos colapsados num nome, o gerador
recalculava a permissão de listagem por uma segunda fórmula que discordava da
primeira — `departamentos.listar` de um lado, `rh.departamentos.listar` do
outro. O gate negava em silêncio, o seletor de empresas esvaziava, e ninguém
tinha onde olhar para saber qual das duas estava certa.

## Drivers da decisão

- O EduConecta terá **quatro** módulos de negócio (escola, pedagógico, financeiro, cantina) e ~20 telas. Um vocabulário ambíguo multiplica por quatro.
- As gavetas do catálogo de acesso eram um enum fechado de 11 casos: os quatro módulos do produto ou empilhavam ~90 permissões em `negocio` — onde a tela deixa de ser navegável — ou editavam o core, violando o [ADR-0022](ADR-0022-dependencia-de-mao-unica.md).
- Convenção verificável vale mais que convenção documentada. Um nome só é útil se algo o derivar.

## Decisão

Dois eixos independentes. **O que a coisa é:**

| Termo | Definição | Identidade |
| --- | --- | --- |
| **Módulo** | Área de negócio com superfície administrativa própria: ao menos uma permissão e ao menos uma rota ou item de menu | uma `chave` kebab estável (`rh`, `escola`) |
| **Recurso** | Uma entidade com seu CRUD, dentro de um módulo | `chave` plural (`alunos`) |
| **Área de acesso** | Gaveta do catálogo de permissões. Por convenção 1:1 com módulo | mesma `chave` |
| **Seção de menu** | Gaveta da sidebar. Por convenção 1:1 com módulo | mesma `chave` |

**Onde a coisa vive** (inalterado): core · pacote · produto · instância.

**Extensão** é demovida a *envelope*: um pacote que carrega um módulo
(**extensão-módulo**) ou só código sem UI (**extensão-biblioteca**, como
`ht2ml/extensao-documentos`). A regra que fecha a ambiguidade:

> **Um pacote de módulo carrega exatamente um módulo**, e a chave é derivável do
> nome do pacote (`ht2ml/extensao-rh` → `rh`).

A frase correta passa a ser *"o módulo RH, distribuído no pacote
`ht2ml/extensao-rh`"*.

**Submódulo continua proscrito**, agora com resposta pronta: é (i) um recurso,
se for entidade com CRUD, ou (ii) um segundo módulo que declara dependência do
primeiro.

### As gavetas são conjuntos abertos

Área de acesso e seção de menu deixam de ser listas fechadas no core:

- `config('access.areas')` é o catálogo de áreas. As onze do core continuam vindo do enum `ModuloAcesso`, via `AreaDeAcesso::sementeDoEnum()` — o enum não é apagado, porque onze chaves de config, duas telas, três blades e dois testes dependem dele. Abrir o *conjunto* resolve; apagar o *tipo* espalharia risco.
- `ModuleRegistry::areaDeAcesso()` e `ModuleRegistry::secaoDeMenu()` deixam um módulo declarar a própria gaveta.
- `AreaDeAcesso` é o VO que representa qualquer área, venha do enum, do config do produto ou de uma extensão. Ele **nunca lança** para chave desconhecida: devolve uma área não-declarada com rótulo derivado, e `ht2ml:doutor` a aponta pelo nome. A alternativa — `ModuloAcesso::from()` — derrubava a tela de acesso inteira por causa de uma extensão instalada com a área trocada.

Quando extensão e produto descrevem a mesma área, **o produto vence**: o pacote
sugere, quem instala decide. É a mesma semântica que `label` e `icone` do menu
já tinham.

### O tipo do pacote vai em `extra`, não em `type`

```jsonc
"extra": { "ht2ml": { "tipo": "modulo", "chave": "rh" } }   // extensao-rh
"extra": { "ht2ml": { "tipo": "biblioteca" } }              // extensao-documentos
"extra": { "ht2ml": { "tipo": "core" } }                    // core
```

`composer.json → type` **não** é usado. Ele seleciona *installer*, não documenta:
sem um plugin correspondente não faz nada, e com um plugin muda o caminho de
instalação — efeito que não se quer.

`extra.ht2ml.chave` passa a ser a **fonte única** da chave do módulo, e é dela
que se derivam prefixo de permissão, namespace de view, prefixo de rota, key da
seção e key da área. Uma coisa declarada, cinco convenções derivadas.

### Os comandos passam a nomear o que geram

| Antes | Depois |
| --- | --- |
| `make:modulo Funcionario --module=Rh` | `make:recurso Funcionario --modulo=escola` |
| `make:extensao Rh` | `make:modulo escola` |
| — | `make:regra MatriculaValida --modulo=escola` |

`--modulo=escola` nomeia um **módulo**, não um caminho: o comando resolve onde
`escola` mora lendo `extra.ht2ml.chave`. É isso que faz mover um módulo de `app/`
para um pacote não quebrar nada.

`make:modulo Funcionario` (PascalCase singular) deve **falhar ensinando o nome
novo**. Um alias silencioso que faz outra coisa é pior que um erro vermelho.

### Como ficou na implementação (2026-08-28)

Duas coisas saíram diferentes do que esta ADR previa, e ficam registradas aqui
para não parecerem descuido.

**Sem `--pacote`.** A flag existiria para distinguir "módulo como pacote" de
"módulo dentro do produto", mas a segunda forma não existe: a topologia decidida
para o EduConecta é monorepo com path repos, então todo módulo é pacote. Uma
flag que não decide nada é superfície nova sem contrapartida — a mesma doença
que esta revisão está tratando. Quando existir módulo fora de pacote, a flag
entra com um sentido real.

**O discriminante é a grafia, não a flag.** Sem `--pacote`, o que separa a forma
antiga da nova é o argumento: chave de módulo é kebab-case, e o argumento antigo
era uma entidade em PascalCase singular. `--fields` e `--tenant` seguem
declarados no `make:modulo`, marcados REMOVIDO, só para poderem ser recusados
com uma mensagem útil em vez de um erro de opção desconhecida do Symfony.

**`make:extensao` virou lápide, não sumiu.** Some do artisan e o Symfony
responde "Command is not defined" com uma sugestão por semelhança de nome que
não chega em `make:modulo`. Como lápide, ele falha dizendo o nome novo. Pode ser
apagado quando a transição for história.

## Alternativas consideradas

**Manter "módulo" polissêmico e documentar os quatro sentidos** (rejeitada).
É o estado atual, e ele já produziu um gate quebrado em produção. Documentação
não desambigua no momento em que alguém digita o nome.

**Apagar o enum `ModuloAcesso` e usar só strings** (rejeitada). Espalharia o
risco por onze chaves de config, duas telas, três blades e dois testes, sem
resolver nada que abrir o conjunto não resolva.

**Aninhar a tela de acesso em módulo → recurso → ação** (rejeitada). Área mais
prefixo mantêm ~200 permissões navegáveis; três níveis acrescentam cliques sem
acrescentar informação.

**Um comando só, com heurística sobre o argumento** (rejeitada). "Módulo" volta
a ter dois sentidos, agora escondidos numa heurística — o pior lugar possível.

## Consequências

**Positivas**

- Um módulo do produto ganha a própria gaveta na matriz de acesso sem editar o core. Sem isso, os quatro módulos do EduConecta empilhariam ~90 permissões em `negocio`.
- `recurso()->registrar()` (Fase 2) deriva permissões, key de menu, nome de rota, permissão do item e padrão de `active` de **uma** declaração. A classe de bug do `permissaoListagem` deixa de existir por construção: o componente pergunta ao registry qual é sua permissão, em vez de recalculá-la.
- `php artisan ht2ml:doutor` transforma cada convenção numa pergunta com resposta binária, e reprova o CI. Na primeira execução ele encontrou sete ícones em uso no menu que a tela de Gestão de Menus recusava — trocar o ícone de "Bancos" era um caminho sem volta.

**Negativas, e assumidas**

- `make:modulo` muda de sentido. Quem tem o nome na memória muscular vai errar uma vez; o comando falha explicando, o que é o custo aceitável de uma renomeação necessária.
- A convenção "1:1 entre módulo, área e seção" é *convenção*, não invariante: `tabelas_auxiliares` a viola por natureza, e vai continuar violando. O `ht2ml:doutor` verifica a coerência de quem escolheu segui-la, não impõe a todos.
- Migrar `PermissionDefinitionDTO::$modulo` para `$area` deixa a coluna `permissions.modulo` com o nome antigo. Renomear a coluna é migration e toca a tela de acesso; fica para quando o vocabulário novo estiver assentado. O dado é o mesmo dos dois lados.

## Relação com outros ADRs

- Dá vocabulário ao [ADR-0022](ADR-0022-dependencia-de-mao-unica.md), que sem ele não conseguiria nomear o que o core não pode conhecer.
- Refina o [ADR-0015](ADR-0015-modulos-pacotes-composer.md): "módulo distribuído como pacote Composer" passa a ser "módulo, distribuído no pacote X".
- Não altera o [ADR-0012](ADR-0012-spatie-permission-guard-name.md): o guard continua `admin` e a estrutura da tabela `permissions` não muda.
- Supersedes parcial de [ADR-RH-007](../../plans/modules/rh/adrs/ADR-RH-007-rh-familia-modulos-pacote.md), que fala em "família de módulos-pacote": pelo vocabulário desta decisão, é **um** módulo (`rh`) com vários recursos.
