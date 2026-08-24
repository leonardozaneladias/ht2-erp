# ht2ml/extensao-exemplo-demo

Extensão **ExemploDemo**.

Pacote Composer instalável que adiciona o módulo ExemploDemo a qualquer
instalação de qualquer produto, sem editar o core (ver ADR-0015).

## Instalar em um cliente

```bash
composer require ht2ml/extensao-exemplo-demo
php artisan migrate --force
php artisan access:sync
php artisan cache:clear
```

Depois, atribua as permissões do módulo aos perfis em `/admin/acesso`.

## Customização por cliente

Publique a config e ajuste permissões/menu sem tocar no pacote:

```bash
php artisan vendor:publish --tag=exemplo-demo-config
```

## Desenvolvimento

Durante o desenvolvimento o pacote vive em `packages/exemplo-demo` do
boilerplate (path repository, symlink). Para gerar recursos CRUD dentro dele:

```bash
php artisan make:modulo Recurso --module=ExemploDemo --fields="..."
```

Ao estabilizar, promova a um repositório Git próprio e versione por tag (semver).
