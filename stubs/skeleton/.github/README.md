# CI do produto

## O segredo que ele precisa

| Segredo          | O que é                                                                |
| ---------------- | ---------------------------------------------------------------------- |
| `COMPOSER_TOKEN` | Token do GitHub com **Contents: Read-only** nos repositórios `ht2ml-*` |

Sem ele o `composer install` falha com `Repository not found` — os pacotes da
plataforma são privados.

### Por que um token, e não deploy keys

O `composer.json` declara os pacotes por SSH (`git@github.com:...`), que é o que
funciona na máquina de quem desenvolve. O runner não tem essa chave. Em vez de
manter duas formas de declarar o mesmo repositório, o workflow reescreve SSH
para HTTPS autenticado:

```yaml
git config --global url."https://x-access-token:${TOKEN}@github.com/".insteadOf "git@github.com:"
```

Um token só cobre todos os pacotes.

## Como este CI economiza minutos

Repositório privado consome os minutos da conta — repositório público não
consome nada. Se este produto puder ser público, a otimização abaixo perde a
razão de existir.

**1. Um job, não três.** Cada job paga `checkout` + setup do PHP +
`composer install` + setup do Node + `npm ci` antes de fazer qualquer coisa
útil. Separar qualidade de testes dobra esse preparo para ganhar um
paralelismo que um produto com poucos testes não aproveita. Quando a suíte
crescer a ponto de o tempo de teste dominar o de preparo, aí vale dividir.

**2. Sem gatilho de push na `main`.** Ligue **"Require branches to be up to
date before merging"** na proteção da `main`. Com isso, a branch precisa estar
atualizada para mergear, então o run do PR já é o resultado do merge — e um run
de push depois seria o mesmo teste outra vez. É metade dos minutos.

> Se você não ligar essa proteção, **reative o gatilho de push**: sem ela, dois
> PRs que passam isolados podem quebrar juntos e ninguém vê.

**3. Rascunho não roda.** O job tem `if: draft == false`. Empurre quantas vezes
quiser enquanto trabalha; marque _Ready for review_ quando quiser o veredito.

**4. `concurrency` com `cancel-in-progress`.** Push novo cancela o run anterior
da mesma branch — ninguém lê o resultado de um commit já substituído.

**5. Dependabot mensal e agrupado.** Cada PR dele custa um ciclo de CI. Semanal
com três grupos gerava ~21 PRs por mês; mensal com um grupo por ecossistema gera 3. Atualização de **segurança** ignora o agendamento e continua chegando na
hora.

## O que dá para fazer além da configuração

- **Menos PRs, maiores.** Agrupe trabalho relacionado. Cada PR custa um ciclo,
  independente de ter mudado uma linha ou trezentas.
- **Rode o portão localmente antes de abrir.** `./vendor/bin/pint --test`,
  `./vendor/bin/phpstan analyse`, `php artisan test`. Um PR que abre já verde
  gasta um ciclo; um que abre vermelho gasta um por tentativa.
- **Abra como rascunho** enquanto a coisa não está pronta.
