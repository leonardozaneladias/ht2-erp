# Domain Docs

Como os skills de engenharia devem consumir a documentação de domínio deste
repo ao explorar o código.

## Antes de explorar, leia

- **`CONTEXT-MAP.md`** na raiz: aponta para um `CONTEXT.md` por contexto. Leia cada um que for relevante ao tema.
- O **`CONTEXT.md`** de cada contexto tocado pela tarefa.
- **`docs/architecture/adrs/`**: leia os ADRs que tocam a área em que você vai mexer. Este repo é multi-context, então cheque também `packages/<módulo>/docs/adr/` para decisões de escopo do módulo.

Se algum desses arquivos não existir, **siga em silêncio**. Não sinalize a
ausência; não sugira criá-los de antemão. O skill `/domain-modeling` (alcançado
via `/grill-with-docs` e `/improve-codebase-architecture`) os cria de forma
preguiçosa, quando termos ou decisões de fato precisarem ser resolvidos.

## Estrutura de arquivos

Repo **multi-context** (a presença de `CONTEXT-MAP.md` na raiz é o sinal):

```
/
├── CONTEXT-MAP.md                     ← índice dos contextos
├── docs/architecture/adrs/            ← decisões do sistema (ADR-0002…ADR-0016)
├── app/
│   └── CONTEXT.md                     ← contexto core (monólito modular)
└── packages/
    └── modulo-rh/
        ├── CONTEXT.md                 ← contexto do módulo de RH
        └── docs/adr/                  ← decisões do próprio módulo
```

Cada módulo de negócio novo (`packages/modulo-*`, ver
[ADR-0015](../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)) entra
como um contexto próprio: um `CONTEXT.md` na raiz do pacote, uma linha nova no
`CONTEXT-MAP.md` e, se tiver decisões próprias, um `docs/adr/` local.

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
