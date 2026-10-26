# Domain Docs

Como os skills de engenharia devem consumir a documentação de domínio deste
repo ao explorar o código.

## Antes de explorar, leia

- **`CONTEXT-MAP.md`** na raiz: aponta para um `CONTEXT.md` por contexto. Leia cada um que for relevante ao tema.
- O **`CONTEXT.md`** de cada contexto tocado pela tarefa.
- **`docs/architecture/adrs/`**: leia os ADRs que tocam a área em que você vai mexer. Este repo é multi-context, então cheque também as decisões de escopo do módulo. Hoje só o RH tem ADRs próprios, e eles ficam em `docs/plans/modules/rh/adrs/` (`ADR-RH-001` a `ADR-RH-010`), não dentro do pacote.

Se algum desses arquivos não existir, **siga em silêncio**. Não sinalize a
ausência; não sugira criá-los de antemão. O skill `/domain-modeling` (alcançado
via `/grill-with-docs` e `/improve-codebase-architecture`) os cria de forma
preguiçosa, quando termos ou decisões de fato precisarem ser resolvidos.

## Estrutura de arquivos

Repo **multi-context** (a presença de `CONTEXT-MAP.md` na raiz é o sinal):

```
/
├── CONTEXT-MAP.md                     ← índice dos contextos
├── docs/architecture/adrs/            ← decisões do sistema (ADR-0002…ADR-0022, com lacunas na numeração)
├── docs/plans/modules/rh/adrs/        ← decisões do módulo de RH (ADR-RH-001…ADR-RH-010)
├── app/
│   └── CONTEXT.md                     ← glossário do contexto core (o código vive em packages/core)
└── packages/
    ├── core/                          ← ht2ml/core
    └── extensao-rh/                   ← módulo de RH (ainda sem CONTEXT.md; glossário não escrito)
```

Cada módulo de negócio novo (`packages/extensao-*`, ver
[ADR-0015](../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) e
[ADR-0021](../architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)) entra
como um contexto próprio: um `CONTEXT.md` na raiz do pacote, uma linha nova no
`CONTEXT-MAP.md` e, se tiver decisões próprias, ADRs próprios. Nenhum pacote
tem `CONTEXT.md` ou `docs/adr/` hoje.

> Atenção ao caminho dos ADRs: aqui é **`docs/architecture/adrs/`** (plural, sob
> `architecture/`), não o `docs/adr/` que os templates genéricos assumem.

## Use o vocabulário do glossário

Quando sua saída nomear um conceito de domínio (título de issue, proposta de
refatoração, hipótese, nome de teste), use o termo como definido no `CONTEXT.md`
daquele contexto. Não escorregue para sinônimos que o glossário evita
explicitamente.

Se o conceito de que você precisa ainda não está no glossário, isso é um sinal:
ou você está inventando linguagem que o projeto não usa (reconsidere), ou existe
uma lacuna real (anote para o `/domain-modeling`).

## Sinalize conflitos com ADRs

Se sua saída contradiz um ADR existente, traga isso à tona explicitamente em vez
de sobrescrever em silêncio:

> _Contradiz o ADR-0015 (módulos como pacotes Composer), mas vale reabrir porque…_
