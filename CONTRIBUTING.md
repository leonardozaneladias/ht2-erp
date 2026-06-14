# Contribuindo

Obrigado por contribuir! Este projeto é um starter kit Laravel + Livewire +
Inspinia (PT-BR). O contexto completo de arquitetura e convenções está em
[`CLAUDE.md`](CLAUDE.md) — leia antes de começar.

## Ambiente

Ambiente oficial: **DDEV + OrbStack** (macOS). Veja `docs/devops/ddev-setup.md`.

```bash
ddev start
make setup     # key, migrate --seed, assets, build
make dev       # Vite (HMR)
```

## Fluxo de trabalho

1. Crie uma **branch dedicada** por feature (`feat/...`, `fix/...`). Não comite
   direto na `main`.
2. Faça **commits por fase**, pequenos e coesos.
3. Abra PR; faça **merge fast-forward** na `main` após o CI verde.

## Padrões de código

Os padrões obrigatórios estão em `CLAUDE.md` §5 (Controller magro, FormRequest,
dinheiro em centavos, Enums backed, DTOs readonly, Services API-ready,
`declare(strict_types=1)`, type hints) e §9 (componentes Blade do catálogo
Inspinia antes de escrever HTML).

## Criando um módulo de negócio

Use o gerador — ele já produz tudo no padrão:

```bash
php artisan make:modulo Produto --fields="nome:string, preco:money, status:enum(ativo|inativo)" --tenant
```

Guia completo: [`docs/criar-modulo.md`](docs/criar-modulo.md).

## Antes de commitar (gate de qualidade)

O `husky` + `lint-staged` roda Pint, Prettier e ESLint nos arquivos staged. Para
rodar tudo manualmente:

```bash
npm run quality   # Pint + ESLint + Prettier + PHPStan (nível 6) + Pest
make test-e2e     # testes de browser (Playwright, no host)
```

CI (GitHub Actions) roda os mesmos gates + auditoria de dependências. Mantenha
tudo verde.

## Commits (Conventional Commits, PT-BR)

```
tipo(escopo): descrição no imperativo
```

Tipos: `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `ci`, `perf`, `style`.
Exemplos: `feat(admin): listagem de clientes`, `fix(infra): corrige fila de e-mails`.
