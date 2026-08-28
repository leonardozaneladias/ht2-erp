# ht2ml/extensao-rh

Módulo **Rh** do HT2 ERP.

Pacote Composer instalável que adiciona o módulo Rh a qualquer
instalação do HT2 ERP, sem editar o core (ver ADR-0015).

## Instalar em um cliente

```bash
composer require ht2ml/extensao-rh
php artisan migrate --force
php artisan access:sync
php artisan cache:clear
```

Depois, atribua as permissões do módulo aos perfis em `/admin/acesso`.

## Customização por cliente

Publique a config e ajuste permissões/menu sem tocar no pacote:

```bash
php artisan vendor:publish --tag=rh-config
```

## Desenvolvimento

Durante o desenvolvimento o pacote vive em `packages/extensao-rh` do
monorepo (path repository, symlink). Para gerar recursos CRUD dentro dele:

```bash
php artisan make:recurso Recurso --modulo=rh --fields="..."
```

Para cortar um release (extrai para `erp-module-rh` via `git subtree split` + tag semver):

```bash
make release-modulo slug=rh versao=v0.1.0
```

Ver [ADR-0016](../../docs/architecture/adrs/ADR-0016-instancias-por-cliente.md) e
`docs/distribuicao-manutencao.md`.
