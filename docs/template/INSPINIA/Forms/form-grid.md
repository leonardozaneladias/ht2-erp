# form-grid — norma de larguras e grades de formulário

Todo formulário do admin usa **grade de 12 colunas** a partir de `md`
(`grid grid-cols-1 gap-4 md:grid-cols-12`), com cada campo num wrapper
`<div class="md:col-span-N">`. Um campo é dimensionado pelo CONTEÚDO que recebe —
CEP não precisa da mesma largura que um nome. A grade nasceu no formulário de
funcionário (B4.9 da 3ª rodada do RH) e virou norma na D18 da 4ª rodada (doc 31).

## Spans por tipo de campo (base `md:grid-cols-12`)

| Conteúdo                                       | Span | Exemplos                      |
| ---------------------------------------------- | ---- | ----------------------------- |
| Nome de pessoa/empresa, logradouro             | 6    | `nome`, `razao_social`        |
| E-mail, select longo (município, cargo)        | 4–5  | `email`, `municipio_id`       |
| Documento mascarado, data, telefone, CEP       | 3    | `cpf`, `data_admissao`, `cep` |
| Matrícula, códigos curtos (CBO, agência)       | 2–3  | `matricula`, `cbo`            |
| UF, dígito, sufixos                            | 2    | `uf`, `conta_digito`          |
| Número de endereço                             | 2    | `numero`                      |
| Textarea, observação, uploads                  | 12   | `observacao_pcd`, dropzones   |
| Toggle/checkbox (alinhar com `flex items-end`) | 2–3  | `principal`, `whatsapp`       |

Regras de composição:

- **Agrupe por assunto na mesma linha** — os campos de um mesmo documento ficam
  juntos (RG: número 3 + órgão emissor 3 + UF 2), não espalhados entre linhas.
- Linhas não precisam somar 12 — sobra à direita é respiro, não defeito. Evite,
  porém, uma linha com um único campo curto órfão.
- **Labels não podem quebrar** no desktop-alvo (1366×768). Se quebrar, encurte o
  label ou alargue o span — nunca deixe a linha desalinhar (era o caso de
  "UF de naturalidade" a 1366).
- Campos condicionais (`@if`) entram na grade com o próprio span; aceite o
  re-fluxo da linha — não reserve espaço vazio para eles.
- **Sub-formulários e cards de repeater usam a MESMA base 12**
  (`x-rh::repeater-card` já vem com ela). Nada de `md:grid-cols-2` novo.

## Grade de leitura (fichas "Ver")

Telas read-only (ficha do funcionário, drawers de ficha) usam
**`sm:grid-cols-2 lg:grid-cols-3`** com `x-shared.field-display` — a divergência
com a base 12 dos formulários é deliberada: consulta pede varredura em colunas
uniformes, não campos dimensionados para digitação.

## Tabelas com números

Colunas de valores (dinheiro, quantidades, horas) alinham **à direita**
(`text-right` no `th` e no `td`) para comparação vertical; texto e datas ficam à
esquerda; a coluna de ações à direita. Vale para `x-shared.static-table` e para
tabelas de painéis (holerites, folha, banco de horas).
