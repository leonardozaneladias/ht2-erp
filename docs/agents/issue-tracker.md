# Issue tracker: GitHub

Issues e specs deste repo vivem como **GitHub Issues** em
`leonardozaneladias/ht2-erp`. Use o CLI `gh` para todas as operações.

## Convenções

- **Criar issue**: `gh issue create --title "..." --body "..."`. Use heredoc para corpos multilinha.
- **Ler issue**: `gh issue view <número> --comments`, filtrando comentários com `jq` e buscando também as labels.
- **Listar issues**: `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` com os filtros `--label` e `--state` apropriados.
- **Comentar**: `gh issue comment <número> --body "..."`
- **Aplicar / remover labels**: `gh issue edit <número> --add-label "..."` / `--remove-label "..."`
- **Fechar**: `gh issue close <número> --comment "..."`

O repo é inferido de `git remote -v`; o `gh` faz isso automaticamente quando
rodado dentro do clone.

> Títulos e corpos de issues seguem o idioma do projeto (**pt-BR**), como o
> resto da documentação. Ver `CLAUDE.md §4`.

## Pull requests como superfície de triagem

**PRs como superfície de request: não.** _(Mude para `sim` se este repo tratar
PRs externos como pedidos de feature; o `/triage` lê este flag.)_

Quando estiver em `sim`, PRs passam pelas mesmas labels e estados das issues,
usando os equivalentes `gh pr`:

- **Ler um PR**: `gh pr view <número> --comments` e `gh pr diff <número>` para o diff.
- **Listar PRs externos para triagem**: `gh pr list --state open --json number,title,body,labels,author,authorAssociation,comments` e manter apenas `authorAssociation` igual a `CONTRIBUTOR`, `FIRST_TIME_CONTRIBUTOR` ou `NONE` (descartar `OWNER`/`MEMBER`/`COLLABORATOR`).
- **Comentar / rotular / fechar**: `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

O GitHub compartilha um único espaço de numeração entre issues e PRs, então um
`#42` solto pode ser qualquer um dos dois: resolva com `gh pr view 42` e caia
para `gh issue view 42`.

## Quando um skill disser "publicar no issue tracker"

Crie uma GitHub Issue.

## Quando um skill disser "buscar o ticket relevante"

Rode `gh issue view <número> --comments`.

## Operações de wayfinding

Usadas pelo `/wayfinder`. O **mapa** é uma única issue, com issues **filhas**
como tickets.

- **Mapa**: uma issue com a label `wayfinder:map`, contendo o corpo Notas / Decisões-até-aqui / Névoa. `gh issue create --label wayfinder:map`.
- **Ticket filho**: uma issue ligada ao mapa como sub-issue do GitHub (`gh api` no endpoint de sub-issues). Onde sub-issues não estiverem habilitadas, adicione o filho a uma task list no corpo do mapa e coloque `Part of #<mapa>` no topo do corpo do filho. Labels: `wayfinder:<tipo>` (`research`/`prototype`/`grilling`/`task`). Uma vez reivindicado, o ticket é atribuído ao dev que o conduz.
- **Bloqueio**: use as **dependências nativas de issues** do GitHub, a representação canônica e visível na UI. Crie a aresta com `gh api --method POST repos/<owner>/<repo>/issues/<filho>/dependencies/blocked_by -F issue_id=<id-do-bloqueador>`, onde `<id-do-bloqueador>` é o **id numérico de banco** do bloqueador (`gh api repos/<owner>/<repo>/issues/<n> --jq .id`, _não_ o `#number` nem o `node_id`). O GitHub reporta `issue_dependencies_summary.blocked_by` (só bloqueadores abertos — o gate real). Onde dependências não estiverem disponíveis, caia para uma linha `Blocked by: #<n>, #<n>` no topo do corpo do filho. Um ticket está desbloqueado quando todos os bloqueadores estiverem fechados.
- **Consulta de fronteira**: liste os filhos abertos do mapa (`gh issue list --state open`, restrito às sub-issues / task list do mapa), descarte os que tiverem bloqueador aberto (`issue_dependencies_summary.blocked_by > 0`, ou uma issue aberta na linha `Blocked by`) ou assignee; o primeiro na ordem do mapa vence.
- **Reivindicar**: `gh issue edit <n> --add-assignee @me`, a primeira escrita da sessão.
- **Resolver**: `gh issue comment <n> --body "<resposta>"`, depois `gh issue close <n>`, depois anexar um ponteiro de contexto (gist + link) nas Decisões-até-aqui do mapa.
