# ht2erp/modulo-rh

Módulo **Rh** do HT2 ERP.

Pacote Composer instalável que adiciona o módulo Rh a qualquer
instalação do HT2 ERP, sem editar o core (ver ADR-0015).

## Instalar em um cliente

```bash
composer require ht2erp/modulo-rh
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

Durante o desenvolvimento o pacote vive em `packages/rh` do
boilerplate (path repository, symlink). Para gerar recursos CRUD dentro dele:

```bash
php artisan make:modulo Recurso --module=Rh --fields="..."
```

Ao estabilizar, promova a um repositório Git próprio e versione por tag (semver).
