---
title: 'ADR-0010: Enums PHP 8.1+ backed em todo campo enumerado'
version: 1.0.0
date: 2026-04-18
status: accepted
---

# ADR-0010: Enums PHP 8.1+ backed em todo campo enumerado

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Engenharia Laravel | **Tags:** tipagem, enum, dominio

## Contexto e problema

O domínio tem dezenas de campos com valores finitos: `StatusReserva` (hold/confirmada/cancelada/expirada/bloqueada), `StatusAdesao`, `StatusConvite`, `StatusPagamento`, `StatusPedidoExtra`, `TipoConvite`, `OrigemReserva`, `PerfilAtor`, `StatusEnquete`, etc. Código `status == 'pendente'` em PHP simples:

- Quebra silenciosamente com typo ("pendete").
- Não reporta inconsistência quando um valor novo é adicionado.
- Não carrega label/cor/traduções consistentes.
- Não gera contrato verificável com o DB.

## Drivers da decisão

- Domínio tem máquinas de estado (ADR-0008) que dependem de comparação confiável.
- Eloquent `$casts` suporta backed enums nativamente desde Laravel 9.
- Scramble (ADR-0007) mapeia backed enums direto para OpenAPI `enum`.
- `Rule::enum(...)` em FormRequest valida input automaticamente.

## Alternativas consideradas

### Alt 1: Strings livres com constantes de classe

- Prós: compatível com PHP antigo.
- Contras: sem segurança de tipo; comparação por string; sem hidratação automática.

### Alt 2: Classes Value Object personalizadas

- Prós: mais flexível que enum.
- Contras: boilerplate; sem integração nativa com Eloquent `$casts`; sem suporte em OpenAPI.

### Alt 3: Backed enums PHP 8.1+ (escolhida)

- Prós: nativos, tipados, suportados por Eloquent, FormRequest (`Rule::enum`), Scramble; permitem métodos de comportamento (`label()`, `color()`, `isHold()`, `isAtiva()`); simples de testar.
- Contras: exige PHP ≥ 8.1 (já em 8.4 neste projeto).

## Decisão

**Todo campo com valores finitos usa backed enum**. Padrão obrigatório:

```php
enum StatusReserva: string
{
    case Hold = 'hold';
    case Confirmada = 'confirmada';
    // ...

    public function label(): string { /* PT-BR UI */ }
    public function color(): string { /* Tailwind */ }
    public function isHold(): bool { return $this === self::Hold; }
    public function isAtiva(): bool { /* derivado */ }
}
```

Regras de uso:

1. **Model** declara `$casts[<campo>] = <EnumClass>::class` — sem isso, `->where('status', StatusReserva::Hold)` falha.
2. **FormRequest** usa `Rule::enum(<EnumClass>::class)`.
3. **DTO** recebe o enum tipado (`public readonly StatusReserva $status`).
4. **Resource** serializa via `$this->status->value`.
5. **Migration** declara `$table->string('status', 20)` + CHECK constraint Postgres com lista explícita (defesa em profundidade — se alguém escreve SQL bruto, cai na constraint).

Enums ficam em `app/Enums/<Contexto>/`.

## Consequências positivas

- Typos pegos em compile-time/IDE; `match` exaustivo ajuda a cobrir todos os cases.
- OpenAPI gera `enum: [hold, confirmada, ...]` automaticamente.
- Labels/cores centralizados — UI admin, e-mails e API consomem o mesmo vocabulário.
- Refatorar um valor (ex.: `'pendente'` → `'aguardando_pagamento'`) é uma busca/substituição assistida pelo IDE e enforceable no DB via CHECK.

## Consequências negativas

- Migrations existentes precisam CHECK constraint em paralelo (extra ~3 linhas por migration enumerada).
- Cada enum vira um arquivo por contexto. Aceito — é o ponto.

## Ligações

- §0 princípio 7, §3.4, §4.3 (CHECK status_valido), Apêndice D #9 do PLANEJAMENTO_BACKEND_APIV1.md
- ADR-0008 (state-machine), ADR-0007 (Scramble/OpenAPI)
- SAD arc42 seção "Conceitos de corte transversal — Tipagem"
