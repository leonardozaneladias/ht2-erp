# Produto via skeleton

Como criar um produto novo sobre a plataforma, o que vem nele, o que customizar e como
mantê-lo atualizado. A decisão está no
[ADR-0017](../architecture/adrs/ADR-0017-produto-novo-via-skeleton.md): produto novo
nasce de `composer create-project ht2ml/skeleton`, e o core chega por Composer. Ninguém
clona a base para começar um produto, e `bin/init-project.sh` está aposentado.

O skeleton é **gerado** do monorepo da base por `bin/release-skeleton.sh`
([runbook](../devops/release.md#skeleton-binrelease-skeletonsh)). Não se edita o
repositório `ht2ml-skeleton`; muda-se a base e corta-se um release.

## 1. Credencial

O skeleton, o core e as extensões são **privados** e não estão no Packagist. O
`composer.json` do skeleton declara cada pacote como repositório VCS por SSH
(`git@github.com:leonardozaneladias/ht2ml-*.git`).

- **Com chave SSH** que tem acesso aos repositórios `ht2ml-*`: nada a fazer.
- **Sem chave SSH** (máquina nova, container): use um token do GitHub com
  **Contents: Read-only** nos repositórios `ht2ml-*`, nos dois lugares que o Composer
  consulta — a reescrita de URL do git e a autenticação HTTP do Composer:

    ```bash
    git config --global url."https://x-access-token:${TOKEN}@github.com/".insteadOf "git@github.com:"
    composer config --global github-oauth.github.com "${TOKEN}"
    ```

    É o mesmo par que o CI do produto usa (seção 6). Só a reescrita resolve os refs e
    falha no download; só o token falha na leitura dos refs.

## 2. Criar

```bash
composer create-project ht2ml/skeleton meu-produto \
  --repository='{"type":"vcs","url":"git@github.com:leonardozaneladias/ht2ml-skeleton.git","no-api":true}'
cd meu-produto
```

O `--repository` diz ao Composer onde está o `ht2ml/skeleton`, que o Packagist não
conhece. Para fixar uma série, use `ht2ml/skeleton:^0.3` no lugar de `ht2ml/skeleton`.

> Não executado na escrita deste guia (o `composer` desta máquina roda num container sem
> a chave SSH). A forma segue a documentação do Composer para `create-project
--repository` e o `composer.json` publicado do skeleton.

O `post-create-project-cmd` do skeleton copia `.env.example` para `.env` (se não
existir), roda `key:generate`, cria `database/database.sqlite` e tenta
`migrate --graceful`. O `.env.example` aponta para PostgreSQL e Redis (`DB_HOST=db`,
o padrão do DDEV); sem esse banco no ar, a migração é pulada sem erro.

O skeleton v0.4.0 (2026-10-25) pede `ht2ml/core: ^0.5.0`. Se um dia o skeleton ficar
para trás do core (a v0.3.3 ainda pedia `^0.1.3`), suba o core logo depois de criar:

```bash
composer require ht2ml/core:^0.5
```

Depois, com o banco configurado no `.env`:

```bash
php artisan migrate --seed

# Os assets NÃO vêm prontos: o Composer não carrega dependência npm, e o Tailwind
# precisa gerar o CSS varrendo os blades — inclusive os de vendor/ht2ml/core.
npm install && npm run build
```

`/admin/login` responde. Os seeders criam `admin@example.com` / `password`
(super-admin) e `gestor@example.com` / `password`. Troque as senhas antes de qualquer
deploy.

## 3. O que vem

Um app Laravel que **instala** `ht2ml/core` por Composer. O que o core entrega — auth com
2FA, multiempresa, perfis e permissões, auditoria, configurações, menu, design system,
catálogos de referência e os comandos `make:modulo`/`make:recurso`/`ht2ml:doutor` — vive
em `vendor/ht2ml/core`, não no repositório do produto.

No repositório do produto vem o app hospedeiro, copiado da base pela lista de inclusão
do `release-skeleton.sh`: `app/` (`Models/User.php`, três providers e `CONTEXT.md`), `bootstrap/`,
`config/`, `database/` (inclui `database/settings/` e o `DatabaseSeeder`), `public/`,
`resources/` (CSS, JS e views do app), `routes/`, `lang/`, `storage/`, `composer.json`,
`package.json`, `vite.config.js`, `pint.json`, `eslint.config.js` e dotfiles. Trocados
pelos de `stubs/skeleton/`:

- `phpunit.xml` — só as suítes `Unit` e `Feature` (as suítes `Extensoes`, `Arch` e
  `Browser` são do monorepo);
- `phpstan.neon` — analisa só `app/`, e inclui `vendor/ht2ml/core/database/migrations`
  para o Larastan conhecer os models do core;
- `.github/` — o CI do produto (seção 6) e o Dependabot.

Em `tests/` vêm `TestCase.php`, `Pest.php` e um `SmokeTest` que pede `/admin/login`.

Não vem: `.ddev/`, `docs/`, `bin/`, `packages/`, `Makefile`, `CHANGELOG.md` — são
ferramentas do monorepo.

## 4. O que customizar

| O quê                                | Onde                                                                                                                                                                                             |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Nome, URL, banco, filas              | `.env` (`APP_NAME` vem como `"HT2ML Platform"`)                                                                                                                                                  |
| Empresa, marca, cores, tela de login | Setup Wizard (`/admin/setup`) e `/admin/configuracoes`, gravados no banco                                                                                                                        |
| Inflexão dos geradores em português  | `config/extensoes.php` com `'idioma_inflexao' => 'portuguese'`, **antes** do primeiro recurso                                                                                                    |
| Configs do core                      | `php artisan vendor:publish --tag=core-config` publica `access`, `admin-menu`, `branding`, `extensoes` e `settings`; ou crie só o arquivo com as chaves que mudam (o core usa `mergeConfigFrom`) |
| Componente Blade do core             | o mesmo caminho em `resources/views/components/` vence o do pacote                                                                                                                               |
| Stubs do gerador                     | `stubs/modulo/<nome>.stub` vence o do core, arquivo a arquivo                                                                                                                                    |
| Ambiente local                       | o produto escolhe (DDEV, Sail); o skeleton não traz `.ddev/`                                                                                                                                     |

O que **não** se customiza: arquivos dentro de `vendor/ht2ml/*`. O produto não edita o
core ([ADR-0022](../architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)); quando
falta um gancho, o caminho é um PR na base que crie o gancho.

### O negócio do produto

O negócio entra como **módulo**, mesmo dentro de um único produto. O `make:modulo` funciona
no produto: cria `packages/extensao-<chave>/` e registra o path repository no
`composer.json` do produto.

```bash
php artisan make:modulo escola
composer require ht2ml/extensao-escola:@dev
php artisan make:recurso Aluno --modulo=escola --fields="nome:string, cpf:cpf" --tenant
php artisan migrate && php artisan access:sync && php artisan ht2ml:doutor
```

Sem `--modulo`, o `make:recurso` recusa: o produto não tem os arquivos de rota, acesso e
menu onde ligaria a tela. Guias: [`criar-recurso.md`](../criar-recurso.md) e
[`criar-extensao.md`](criar-extensao.md).

## 5. Instalar extensões

Só o core entra por padrão. As extensões publicadas já estão declaradas como
repositórios no `composer.json` do skeleton:

```bash
composer require ht2ml/extensao-rh          # RH / departamento pessoal
composer require ht2ml/extensao-fiscal-br   # CNAE, CFOP, NCM
composer require ht2ml/extensao-documentos  # numeração sequencial
composer require ht2ml/extensao-exemplo-demo # vitrine do design system

php artisan migrate && php artisan access:sync && php artisan ht2ml:doutor
```

`ht2ml/extensao-api` e `ht2ml/extensao-ia` ainda não foram publicadas (2026-10-25): os
repositórios não existem, e a `extensao-api` depende do canal de API do core (`EscopoDeRota::Api`),
que só sai na próxima versão do core. Quando saírem, o `composer.json` de um produto criado
de um skeleton antigo precisa ganhar os repositórios:

```bash
composer config repositories.ht2ml-extensao-api vcs git@github.com:leonardozaneladias/ht2ml-extensao-api.git
```

## 6. CI do produto

O skeleton traz `.github/workflows/ci.yml`, `.github/dependabot.yml` e um
`.github/README.md` que explica as escolhas. Resumo:

- **Um job só** (Pint, PHPStan, Prettier e `php artisan test` com SQLite em memória),
  porque preparar o ambiente custa mais que o paralelismo economiza num produto pequeno.
- **Só em `pull_request` para `main`**, sem gatilho de push. Isso só é seguro com a
  proteção _Require branches to be up to date before merging_ ligada na `main`; sem ela,
  reative o push.
- **Rascunho não roda** (`if: draft == false`) e push novo cancela o run anterior.
- **Dependabot mensal**, um grupo por ecossistema.

### `COMPOSER_TOKEN`

O único segredo. Token do GitHub com **Contents: Read-only** nos repositórios `ht2ml-*`
(só _Metadata_ não basta: a API responde 200 e o download falha com 403).

Ele entra em **dois lugares** do workflow:

1. a reescrita de URL do git (`url."https://x-access-token:${TOKEN}@github.com/".insteadOf "git@github.com:"`),
   que deixa o Composer ler os refs dos repositórios SSH;
2. `COMPOSER_AUTH` (`{"github-oauth":{"github.com":"${TOKEN}"}}`), que cobre o download.

E fica em **dois cofres**: _Settings → Secrets and variables → Actions_ (PRs normais) e
_→ Dependabot_ (PRs do Dependabot, que não enxergam os segredos de Actions). Sem a segunda
cópia, todo PR do Dependabot falha no `composer install`.

Se o token for _fine-grained_ com repositórios listados um a um, cada extensão nova
publicada precisa ser acrescentada a ele.

Para um produto com API, acrescente o portão do OpenAPI:

```yaml
- name: OpenAPI em dia com o código
  run: php artisan api:openapi --verificar
```

## 7. Atualizar o core

```bash
composer update ht2ml/core          # dentro da restrição (ex.: ^0.4 → 0.4.x mais nova)
php artisan migrate                 # migrations novas do pacote
php artisan access:sync             # permissões novas do catálogo
php artisan ht2ml:doutor            # as contribuições dos módulos ainda fecham?
```

Em produção, `migrate --force` e, se o menu ou o ACL estiverem em cache,
`php artisan cache:clear`.

Na série `0.x`, uma minor nova é breaking para o Composer: `^0.4` não aceita `0.5.0`.
Leia as notas do release do core e suba explicitamente:

```bash
composer require ht2ml/core:^0.5
```

Extensões seguem o mesmo ciclo, com `composer update ht2ml/extensao-<nome>`.

O produto **não** recebe atualização por `git merge`: não tem `upstream`, não tem histórico
comum com a base. Isso é o que o separa de uma instância por clone
([`fluxo-desenvolvimento.md`](../fluxo-desenvolvimento.md) §0 e §7).

## 8. Quando algo não funciona

| Sintoma                                                  | Causa provável                                                                             |
| -------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `Could not find package ht2ml/skeleton`                  | faltou o `--repository`                                                                    |
| `Repository not found` / `Permission denied (publickey)` | sem chave SSH com acesso, ou token sem acesso ao repositório                               |
| `404` no download com os refs resolvendo                 | só a reescrita de URL foi feita; falta o token no Composer (`COMPOSER_AUTH`)               |
| `does not match the constraint` no `ht2ml/core`          | restrição do skeleton antiga; `composer require ht2ml/core:^<versão>`                      |
| Toda tela com 500 e "Vite manifest not found"            | faltou `npm install && npm run build`                                                      |
| Admin sem estilo                                         | `resources/css/admin.css` perdeu o `@import` de `vendor/ht2ml/core/resources/css/core.css` |
| Tela gerada abre em 403 para quem não é super-admin      | faltou `php artisan access:sync`, ou a permissão não foi atribuída ao perfil               |
| `ht2ml:doutor` sai com 1                                 | leia a saída: ela diz a contribuição, o alvo e o que falta                                 |
