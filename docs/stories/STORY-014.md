# Pest arch tests + CI GitHub Actions

**ID:** STORY-014
**Epic:** F1-E6 — Qualidade e CI
**Priority:** Must Have
**Story Points:** 2
**Status:** Not Started
**Skills:** `pest-testing`, `laravel-quality`, `php-best-practices`

## User Story

Como **desenvolvedor da equipe**
Quero **ter testes de arquitetura automatizados com Pest e um pipeline CI no GitHub Actions**
Para que **violações arquiteturais sejam detectadas em cada PR e a qualidade mínima (lint, análise estática, testes) seja garantida antes de qualquer merge**

## Acceptance Criteria

- [ ] `tests/Architecture/F1ArchTest.php` existe e contém os 5 testes de arquitetura listados abaixo
- [ ] Arch test `'todos os arquivos PHP têm strict_types'` passa: todos os arquivos em `app/` possuem `declare(strict_types=1)`
- [ ] Arch test `'controllers não usam Eloquent diretamente'` passa: nenhuma classe em `App\Http\Controllers` usa `Illuminate\Database\Eloquent\Model` ou chama métodos como `::where()`, `::find()`, `::create()` diretamente
- [ ] Arch test `'models não dependem de Http'` passa: nenhuma classe em `App\Models` importa nada de `Illuminate\Http`
- [ ] Arch test `'DTOs são readonly ou têm propriedades tipadas'` passa: todas as classes em `App\Data` têm propriedades com type hints
- [ ] Arch test `'Enums são backed string ou int'` passa: todas as classes em `App\Enums` são backed enums
- [ ] `.github/workflows/ci.yml` existe com 4 jobs: `lint`, `analyse`, `test`, `format`
- [ ] Job `lint` executa `./vendor/bin/pint --test`
- [ ] Job `analyse` executa `./vendor/bin/phpstan analyse --level=6`
- [ ] Job `test` executa `./vendor/bin/pest --compact`
- [ ] Job `format` executa `npx prettier --check resources/`
- [ ] CI usa PHP 8.4 e serviço PostgreSQL 16
- [ ] CI faz cache de dependências Composer via `actions/cache` com key baseada em `composer.lock`
- [ ] CI é disparado em `push` e `pull_request` para as branches `main` e `develop`
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros

## Technical Notes

### Arquivos a criar/modificar

- `tests/Architecture/F1ArchTest.php` — suite de arch tests Pest 4 para F1
- `.github/workflows/ci.yml` — pipeline CI completo com 4 jobs paralelos

### Observações técnicas

**Arch tests com Pest 4:**

```php
<?php

declare(strict_types=1);

arch('todos os arquivos PHP têm strict_types')
    ->expect('App')
    ->toUseStrictTypes();

arch('controllers não usam Eloquent diretamente')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'Illuminate\Database\Eloquent\Model',
        'Illuminate\Support\Facades\DB',
    ]);

arch('models não dependem de Http')
    ->expect('App\Models')
    ->not->toUse('Illuminate\Http');

arch('DTOs são readonly ou têm propriedades tipadas')
    ->expect('App\Data')
    ->toBeReadonly();

arch('Enums são backed string ou int')
    ->expect('App\Enums')
    ->toBeStringBackedEnum()
    ->orToBeIntBackedEnum();
```

Nota: verificar disponibilidade de `toBeStringBackedEnum()` no Pest 4 — se não existir, usar `toBeEnum()` com assertion complementar. Caso `toBeReadonly()` não cubra classes `Data` do spatie (que não são readonly por definição), ajustar para `toHaveConstructorWithTypedProperties()` ou fazer assertion manual via reflexão.

**CI GitHub Actions — estrutura do `.github/workflows/ci.yml`:**

```yaml
name: CI

on:
    push:
        branches: [main, develop]
    pull_request:
        branches: [main, develop]

jobs:
    lint:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.4'
                  coverage: none
            - name: Cache Composer
              uses: actions/cache@v4
              with:
                  path: vendor
                  key: composer-${{ hashFiles('composer.lock') }}
                  restore-keys: composer-
            - run: composer install --no-interaction --prefer-dist --optimize-autoloader
            - run: ./vendor/bin/pint --test

    analyse:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.4'
                  coverage: none
            - name: Cache Composer
              uses: actions/cache@v4
              with:
                  path: vendor
                  key: composer-${{ hashFiles('composer.lock') }}
                  restore-keys: composer-
            - run: composer install --no-interaction --prefer-dist --optimize-autoloader
            - run: ./vendor/bin/phpstan analyse --level=6

    test:
        runs-on: ubuntu-latest
        services:
            postgres:
                image: postgres:16
                env:
                    POSTGRES_DB: portal_artfinal_test
                    POSTGRES_USER: postgres
                    POSTGRES_PASSWORD: postgres
                ports:
                    - 5432:5432
                options: >-
                    --health-cmd pg_isready
                    --health-interval 10s
                    --health-timeout 5s
                    --health-retries 5
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.4'
                  extensions: pdo_pgsql
                  coverage: none
            - name: Cache Composer
              uses: actions/cache@v4
              with:
                  path: vendor
                  key: composer-${{ hashFiles('composer.lock') }}
                  restore-keys: composer-
            - run: composer install --no-interaction --prefer-dist --optimize-autoloader
            - name: Preparar .env de teste
              run: |
                  cp .env.example .env.testing
                  php artisan key:generate --env=testing
            - run: ./vendor/bin/pest --compact
              env:
                  DB_CONNECTION: pgsql
                  DB_HOST: 127.0.0.1
                  DB_PORT: 5432
                  DB_DATABASE: portal_artfinal_test
                  DB_USERNAME: postgres
                  DB_PASSWORD: postgres

    format:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: actions/setup-node@v4
              with:
                  node-version: '20'
            - run: npm ci
            - run: npx prettier --check "resources/**"
```

O serviço PostgreSQL no job `test` usa a variável `APP_ENV=testing`. Garantir que `.env.example` tenha `APP_KEY=` vazio (gerado em tempo de CI) e `DB_CONNECTION=pgsql` como padrão.

Os 4 jobs são independentes e rodam em paralelo (sem `needs:`), reduzindo tempo total de CI.

## Dependencies

- **Blocked by:** STORY-012 (arch test de enums verifica `App\Enums`), STORY-013 (arch test de DTOs verifica `App\Data`)
- **Blocks:** Nenhuma

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY014` verde
- [ ] `./vendor/bin/pest tests/Architecture/F1ArchTest.php --compact` passa com 5 testes verdes
- [ ] Pipeline CI executa sem erros em branch `develop` após merge das stories STORY-012 e STORY-013
- [ ] Job `lint` falha corretamente se um arquivo PHP sem `strict_types` for adicionado ao PR (validar localmente com `pint --test`)
