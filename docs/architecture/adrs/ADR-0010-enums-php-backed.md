---
title: 'ADR-0010: Enums PHP backed em todo campo enumerado'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0010: Enums PHP backed em todo campo enumerado

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel | **Tags:** tipagem, enum, dominio

## Contexto e problema

O domínio tem diversos campos com valores finitos: `StatusPedido` (pendente/confirmado/cancelado/expirado), `StatusPagamento`, `StatusUsuario`, `TipoRegistro`, etc. Código `status == 'pendente'` em PHP simples:

- Quebra silenciosamente com typo ("pendete").
- Não reporta inconsistência quando um valor novo é adicionado.
- Não carrega label/cor/traduções consistentes.
- Não gera contrato verificável com o DB.

## Drivers da decisão

- O domínio tem máquinas de estado que dependem de comparação confiável.
- Eloquent `$casts` suporta backed enums nativamente desde Laravel 9.
- `Rule::enum(...)` em FormRequest valida o input automaticamente.

## Alternativas consideradas

### Alt 1: Strings livres com constantes de classe

- Prós: compatível com PHP antigo.
- Contras: sem segurança de tipo; comparação por string; sem hidratação automática.

### Alt 2: Classes Value Object personalizadas

- Prós: mais flexível que enum.
- Contras: boilerplate; sem integração nativa com Eloquent `$casts`.

### Alt 3: Backed enums PHP 8.1+ (escolhida)

- Prós: nativos, tipados, suportados por Eloquent e FormRequest (`Rule::enum`); permitem métodos de comportamento (`label()`, `color()`, `isPendente()`, `isAtivo()`); simples de testar.
- Contras: exige PHP ≥ 8.1 (já em 8.4 neste projeto).

## Decisão

**Todo campo com valores finitos usa backed enum**. Padrão obrigatório:

```php
enum StatusPedido: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    // ...

    public function label(): string { /* PT-BR UI */ }
    public function color(): string { /* Tailwind */ }
    public function isPendente(): bool { return $this === self::Pendente; }
    public function isAtivo(): bool { /* derivado */ }
}
```

Regras de uso:

1. **Model** declara `$casts[<campo>] = <EnumClass>::class` — sem isso, `->where('status', StatusPedido::Pendente)` falha.
2. **FormRequest** usa `Rule::enum(<EnumClass>::class)`.
3. **DTO** recebe o enum tipado (`public readonly StatusPedido $status`).
4. **Apresentação** consome `label()`/`color()` para renderizar badges e textos.
5. **Migration** declara `$table->string('status', 20)` + CHECK constraint Postgres com lista explícita (defesa em profundidade — se alguém escreve SQL bruto, cai na constraint).

Enums ficam em `app/Enums/<Contexto>/`.

## Consequências positivas

- Typos pegos em compile-time/IDE; `match` exaustivo ajuda a cobrir todos os cases.
- Labels/cores centralizados — UI admin e e-mails consomem o mesmo vocabulário.
- Refatorar um valor (ex.: `'pendente'` → `'aguardando_pagamento'`) é uma busca/substituição assistida pelo IDE e enforceable no DB via CHECK.

## Consequências negativas

- Migrations existentes precisam CHECK constraint em paralelo (extra ~3 linhas por migration enumerada).
- Cada enum vira um arquivo por contexto. Aceito — é o ponto.

## Ligações

- ADR-0009 (snapshots)
