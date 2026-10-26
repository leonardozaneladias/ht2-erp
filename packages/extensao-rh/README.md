# ht2ml/extensao-rh

Módulo **RH** (chave `rh`) da plataforma HT2ML: departamento pessoal.

Pacote Composer instalável que adiciona o módulo a qualquer produto, sem editar o
core (ver [ADR-0015](../../docs/architecture/adrs/ADR-0015-modulos-pacotes-composer.md)
e [ADR-0021](../../docs/architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md)).
Hoje entrega dois recursos: **departamentos** e **funcionários**.

## Instalar num produto

O pacote é privado e vem por VCS do repositório `leonardozaneladias/ht2ml-extensao-rh`
(o skeleton já declara o repositório no `composer.json`).

```bash
composer require ht2ml/extensao-rh
php artisan migrate --force
php artisan access:sync
php artisan cache:clear
php artisan ht2ml:doutor
```

Depois, atribua as permissões do módulo (`rh.departamentos.*`, `rh.funcionarios.*`)
aos perfis em `/admin/acesso`.

## Customização por cliente

As permissões e os itens de menu não são escritos à mão: o `ModuloBuilder` os deriva
da chave de cada recurso em `config/rh.php`. O que a config decide é onde as
contribuições entram (`modulo_acesso`, `secao_menu`), os grupos, e o rótulo, ícone,
grupo e ordem de cada recurso. Para mudar isso sem tocar no pacote:

```bash
php artisan vendor:publish --tag=rh-config
```

## Desenvolvimento

No monorepo da plataforma o pacote vive em `packages/extensao-rh` (path repository,
symlink). Para gerar um recurso CRUD dentro dele:

```bash
php artisan make:recurso Recurso --modulo=rh --fields="..."
```

Para cortar um release (subtree split para `leonardozaneladias/ht2ml-extensao-rh` + tag
semver), a partir do monorepo:

```bash
make release-modulo pacote=extensao-rh versao=v0.2.0
```

`pacote` é o nome do diretório em `packages/`. Ordem dos releases e cuidados com
breaking change: [`docs/devops/release.md`](../../docs/devops/release.md).
