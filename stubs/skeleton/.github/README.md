# CI do produto

Este workflow vem do skeleton. Ele precisa de **um segredo** para funcionar:

| Segredo          | O que é                                                                 |
| ---------------- | ----------------------------------------------------------------------- |
| `COMPOSER_TOKEN` | Token do GitHub com permissão de **leitura** nos repositórios `ht2ml-*` |

Sem ele, o `composer install` falha com `Repository not found` — os pacotes da
plataforma são privados.

## Por que um token, e não deploy keys

O `composer.json` declara os pacotes por SSH (`git@github.com:...`), que é o que
funciona na máquina de quem desenvolve. O runner não tem essa chave. Em vez de
manter duas formas de declarar o mesmo repositório, o workflow reescreve SSH
para HTTPS autenticado:

```yaml
git config --global url."https://x-access-token:${TOKEN}@github.com/".insteadOf "git@github.com:"
```

Um token só cobre todos os pacotes, e o `composer.json` não muda de forma
dependendo de onde roda.

## Criando o token

Fine-grained token com **Contents: Read-only** nos repositórios `ht2ml-core`,
`ht2ml-skeleton` e as extensões que o produto instalar. Guarde em
_Settings → Secrets and variables → Actions_ como `COMPOSER_TOKEN`.
