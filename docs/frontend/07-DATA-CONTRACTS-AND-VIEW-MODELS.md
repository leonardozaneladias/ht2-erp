---
title: Data Contracts & View Models — Portal ArtFinal v2 (SPA React)
version: 1.0.0
date: 2026-04-18
status: draft
audience: frontend
related:
    - ../api/api-contract.md
    - ../api/api-conventions.md
    - ../api/error-envelope.md
    - ../prd/PLANEJAMENTO_FRONTEND_REACT.md
    - ./06-ADR/
---

# Data Contracts & View Models — Portal ArtFinal v2

> Especifica, para o SPA React do Portal do Formando, a **modelagem de dados do cliente** e o mapeamento `API payload → ViewModel → UI`. Este documento é o elo entre [`docs/api/api-contract.md`](../api/api-contract.md) (autoridade do servidor) e os componentes React (consumidores). Todos os tipos TS aqui apresentados são conceitualmente compatíveis com o `types.gen.ts` gerado por `openapi-typescript` a partir de [`docs/api/openapi-skeleton.yaml`](../api/openapi-skeleton.yaml).

> Legenda: ✅ confirmado pela API contract/openapi | 💡 inferido a partir de semântica explícita | ❓ pendente de alinhamento com backend antes de F3.

---

## Sumário

- [1. Introdução](#1-introdução)
- [2. Fronteiras: DTO, ViewModel e Formulário](#2-fronteiras-dto-viewmodel-e-formulário)
- [3. Entidades consumidas (TS interfaces)](#3-entidades-consumidas-ts-interfaces)
- [4. View Models por módulo](#4-view-models-por-módulo)
- [5. Mapeamento API → UI (tabela)](#5-mapeamento-api--ui-tabela)
- [6. Contratos por recurso (shapes JSON)](#6-contratos-por-recurso-shapes-json)
- [7. Dados críticos por módulo](#7-dados-críticos-por-módulo)
- [8. Shape esperado de respostas](#8-shape-esperado-de-respostas)
- [9. Convenções ULID](#9-convenções-ulid)
- [10. Helpers `lib/money.ts`, `lib/date.ts`, `lib/idempotency.ts`](#10-helpers-libmoneyts-libdatets-libidempotencyts)
- [11. Codegen vs composição local](#11-codegen-vs-composição-local)
- [Apêndice A — Política de Zod para guarda runtime](#apêndice-a--política-de-zod-para-guarda-runtime)
- [Apêndice B — Pendências com backend](#apêndice-b--pendências-com-backend)

---

## 1. Introdução

### 1.1 Propósito

O SPA React consome exclusivamente a API REST `api/v1`. Toda dado que chega ao componente passa por três camadas bem definidas:

1. **DTO** (Data Transfer Object) — shape gerado automaticamente pelo `openapi-typescript` a partir do `openapi-skeleton.yaml`. **Nunca** editado à mão. Reflete 1:1 o payload JSON da API.
2. **ViewModel** — composição local a partir de um ou mais DTOs, enriquecida com campos derivados (labels PT-BR, cálculos, formatadores). É o shape que a UI consome diretamente.
3. **FormModel** (opcional) — shape do formulário React Hook Form, validado por Zod. Serializa para o DTO de request apenas no momento da mutação.

```
┌──────────────┐    Axios     ┌──────────────┐   mapper    ┌──────────────┐
│  API v1 JSON │─────────────▶│  DTO (gen)   │────────────▶│  ViewModel   │
└──────────────┘              └──────────────┘             └──────────────┘
                                                                    │
                                                                    ▼
                                                           ┌──────────────┐
                                                           │ Componente   │
                                                           │ React (UI)   │
                                                           └──────────────┘
                                                                    ▲
┌──────────────┐     Zod      ┌──────────────┐   mapper    ┌──────────────┐
│ FormModel    │◀─validação───│  Zod schema  │◀────────────│ RHF useForm  │
└──────────────┘              └──────────────┘             └──────────────┘
```

### 1.2 Por que separar DTO de ViewModel

- **DTO é contrato externo.** Muda quando o backend evolui — rebuild obrigatório dos types.gen.ts + ajustes nos mappers.
- **ViewModel é contrato interno** da UI. Pode agregar dados de múltiplos endpoints, injetar cálculos temporais (ex.: `secondsRemaining` derivado de `hold_expires_at`) e normalizar strings PT-BR.
- **Teste mais simples.** O mapper DTO→ViewModel é função pura, testável com Vitest.
- **Troca de backend sem mudar UI.** Se o endpoint for movido, apenas o mapper muda.

### 1.3 Fluxo concreto em uma tela

Exemplo: tela `/portal/financeiro` exibe extrato de parcelas paginado por cursor.

1. Hook `useExtrato()` (em `api/hooks/use-pagamento.ts`) chama `GET /api/v1/me/extrato` via `useInfiniteQuery`.
2. A resposta é do tipo `ExtratoListResponse` (DTO gerado).
3. O hook faz `pages.flatMap(p => p.data).map(toParcelaViewModel)` retornando `ParcelaViewModel[]`.
4. O componente `<ExtratoList>` itera `ParcelaViewModel[]` e usa campos como `valorFormatado` e `statusLabel` — nunca `valor_centavos` direto.

---

## 2. Fronteiras: DTO, ViewModel e Formulário

### 2.1 DTO (`resources/spa/src/api/types.gen.ts`)

- Gerado por `npx openapi-typescript docs/api/openapi-skeleton.yaml -o resources/spa/src/api/types.gen.ts`.
- Export central: `export interface paths` + `export interface components`.
- Acessamos com helpers:

```ts
// resources/spa/src/api/types.ts
import type { components, paths } from './types.gen';

// Shortcut: extrair schema de componente
export type Schema<K extends keyof components['schemas']> = components['schemas'][K];

// Shortcut: extrair response 2xx de um endpoint
export type GetResponse<
    P extends keyof paths,
    M extends keyof paths[P] = 'get' & keyof paths[P],
> = paths[P][M] extends {
    responses: { 200: { content: { 'application/json': infer R } } };
}
    ? R
    : never;
```

- `Schema<'Convite'>` é o DTO puro do convite conforme openapi.
- `GetResponse<'/me'>` é o envelope `{ data: FormandoMe }` conforme openapi.

### 2.2 ViewModel (`resources/spa/src/view-models/<entidade>.ts`)

- Uma interface `XxxViewModel` por entidade exibida.
- Uma função mapper `toXxxViewModel(dto: XxxDto): XxxViewModel`.
- ViewModel sempre em **camelCase**; DTO mantém `snake_case` da API (espelha o JSON).
- Campos derivados sempre prefixados pela sua natureza: `statusLabel`, `valorFormatado`, `linkRsvp`, `secondsRemaining`, etc.
- `any` proibido — se DTO é incerto (ex.: `null` vs objeto), usar `unknown` + type guard.

### 2.3 FormModel (`resources/spa/src/forms/<modulo>/<etapa>.schema.ts`)

- Schema Zod para validação + tipo inferido.
- Serializa para DTO de request via `toRequestPayload(form: FormModel): RequestDto`.
- RHF usa `zodResolver(schema)`; mensagens de erro em PT-BR.

---

## 3. Entidades consumidas (TS interfaces)

> Interfaces abaixo são **referência conceitual**. Em runtime o SPA importa a versão gerada em `types.gen.ts`. A forma aqui escrita corresponde ao que `openapi-typescript` produz para o `openapi-skeleton.yaml` atual (ver [ADR-0004](./06-ADR/) — codegen via openapi-typescript).

### 3.1 `FormandoMe` — resposta de `GET /me` ✅

```ts
// resources/spa/src/api/dto/me.ts (conceitual; real vem de types.gen.ts)

export interface FormandoMeDto {
    id: string; // ULID
    nome: string;
    email: string;
    tipo: 'formando' | 'responsavel' | 'comissao' | 'admin';
    roles: string[];
    abilities: string[]; // ex: ['convites.view', 'reservar']
    formandos: FormandoVinculoDto[];
    links: {
        self: string;
        eventos: string;
        adesoes: string;
        convites: string;
    };
}

export interface FormandoVinculoDto {
    id: string; // ULID
    turma: { id: string; codigo: string };
    evento: { id: string; slug: string };
}
```

### 3.2 `Evento` — resposta de `GET /eventos/{ulid}` e item de `GET /me/eventos` ✅

```ts
export interface EventoDto {
    id: string; // ULID
    slug: string;
    nome: string;
    data_evento: string; // ISO 8601 com offset
    status: 'rascunho' | 'publicado' | 'encerrado' | 'cancelado';
    janelas: {
        abre_rsvp_at: string | null;
        fecha_rsvp_at: string | null;
        abre_mesas_at: string | null;
        fecha_mesas_at: string | null;
    };
    config: {
        hold_ttl_seconds: number;
        permite_troca_assento: boolean;
    };
    turmas: Array<{ id: string; codigo: string }>;
    links: {
        self: string;
        mapa: string;
        convites: string;
        enquetes: string;
        extras: string;
    };
}
```

### 3.3 `Adesao` — item de `GET /me/adesoes` ✅

```ts
export type StatusAdesao = 'rascunho' | 'pendente_pagamento' | 'ativa' | 'cancelada' | 'inadimplente' | 'concluida';

export interface AdesaoDto {
    id: string; // ULID
    status: StatusAdesao;
    evento: { id: string; slug: string };
    pacote: { id: string; nome: string };
    valor_total_centavos: number;
    qtd_parcelas: number;
    confirmada_at: string | null; // ISO 8601
    parcelas_resumo: {
        total: number;
        pagas: number;
        pendentes: number;
        vencidas: number;
    };
    links: {
        self: string;
        extrato: string;
        cancelar: string | null;
    };
}
```

### 3.4 `Parcela` (extrato financeiro) — item de `GET /me/extrato` ✅

```ts
export type TipoMovimentoExtrato =
    | 'parcela_pendente'
    | 'parcela_paga'
    | 'parcela_vencida'
    | 'parcela_cancelada'
    | 'pedido_extra'
    | 'estorno';

export interface ExtratoItemDto {
    id: string; // ULID
    tipo: TipoMovimentoExtrato;
    data_movimento: string; // ISO 8601
    valor_centavos: number;
    descricao: string;
    referencia: { tipo: 'parcela' | 'pedido_extra' | 'pagamento'; id: string };
    links: {
        self: string;
        comprovante: string | null;
        pagar: string | null;
    };
}
```

### 3.5 `PagamentoIntent` — resposta de `POST /pagamentos/intents` e `GET /pagamentos/{ulid}` ✅

```ts
export type StatusPagamento = 'pendente' | 'em_processamento' | 'pago' | 'falho' | 'estornado' | 'cancelado';

export type MetodoPagamento = 'boleto' | 'pix' | 'cartao';

export interface BoletoDto {
    linha_digitavel: string;
    pdf_url: string; // signed URL
    vencimento: string; // ISO 8601 date
}

export interface PixDto {
    qrcode: string; // EMV string
    qrcode_image_url: string; // signed
    expira_em: string; // ISO 8601
}

export interface CartaoDto {
    status_gateway: string;
    ultimos_digitos: string;
    bandeira: 'visa' | 'mastercard' | 'elo' | 'amex' | 'hipercard';
    parcelas: number;
}

export interface PagamentoDto {
    id: string;
    status: StatusPagamento;
    metodo: MetodoPagamento;
    valor_centavos: number;
    pago_em: string | null;
    origem: {
        tipo: 'parcela' | 'pedido_extra';
        id: string;
        descricao: string;
    };
    boleto: BoletoDto | null;
    pix: PixDto | null;
    cartao: CartaoDto | null;
    comprovante_url: string | null;
    links: { self: string };
}
```

### 3.6 `Convite` — item de `GET /me/convites` e `GET /eventos/{ulid}/convites` ✅

```ts
export type StatusConvite = 'emitido' | 'enviado' | 'visualizado' | 'confirmado' | 'recusado' | 'cancelado';

export type TipoConvite = 'nominal' | 'transferivel' | 'cortesia' | 'staff' | 'extra';

export interface ConviteDto {
    id: string;
    codigo: string; // humano, 8 chars
    status: StatusConvite;
    tipo: TipoConvite;
    convidado: {
        nome: string;
        email: string | null;
        telefone: string | null;
    };
    entregue_at: string | null;
    visualizado_at: string | null;
    confirmado_at: string | null;
    links: {
        self: string;
        reemitir: string | null;
        transferir: string | null;
        cancelar: string | null;
    };
}
```

### 3.7 `LoteConvites` — resposta de `POST .../lotes` e `GET .../lotes/{ulid}` ✅

```ts
export type StatusLoteConvites = 'processando' | 'concluido' | 'falha_parcial' | 'falha';

export interface LoteConvitesDto {
    id: string;
    status: StatusLoteConvites;
    qtd_total: number;
    qtd_processados: number;
    qtd_falhados: number;
    iniciado_at: string | null;
    concluido_at: string | null;
    status_url: string; // presente na resposta 202
}
```

### 3.8 `ConvitePublico` (RSVP) — resposta de `GET /convite/{token}` ✅

```ts
export interface ConvitePublicoDto {
    convite: {
        id: string;
        codigo: string;
        tipo: TipoConvite;
        status: StatusConvite;
        convidado: { nome: string };
    };
    evento: {
        id: string;
        nome: string;
        data_evento: string;
        local: { nome: string; endereco: string } | null;
    };
    links: {
        self: string;
        rsvp: string;
    };
}
```

> Nota 💡: o `id` do evento é ULID mas o token de acesso é **opaco** (64 hex). A URL do cliente é `/rsvp/{token}`, nunca expõe ULIDs em plaintext para o convidado externo.

### 3.9 `MapaMesas`, `Mesa`, `Assento` — `GET /eventos/{ulid}/mesas/mapa` ✅

```ts
export type StatusAssentoRuntime = 'livre' | 'hold' | 'confirmada' | 'bloqueada';

export interface AssentoDto {
    id: string;
    numero: number;
    status_runtime: StatusAssentoRuntime;
    reserva_id: string | null;
    hold_expires_at: string | null; // presente só se status_runtime = 'hold'
}

export interface MesaDto {
    id: string;
    numero: string; // string porque "12A", "VIP-3" são válidos
    capacidade: number;
    assentos: AssentoDto[];
}

export interface SetorDto {
    id: string;
    nome: string;
    mesas: MesaDto[];
}

export interface MapaMesasDto {
    mapa: { id: string; nome: string };
    setores: SetorDto[];
    atualizado_em: string; // ISO 8601
    links: {
        self: string;
        reservar: string;
    };
}

// Delta retornado quando ?since=<iso> é usado
export interface MapaDeltaDto {
    deltas: Array<{
        assento_id: string;
        status_runtime: StatusAssentoRuntime;
        reserva_id?: string;
        hold_expires_at?: string;
    }>;
    atualizado_em: string;
}
```

### 3.10 `ReservaAssento` — resposta de `POST /mesas/reservas` e `POST .../confirmar` ✅

```ts
export type StatusReserva = 'hold' | 'confirmada' | 'liberada' | 'cancelada';

export interface ReservaAssentoDto {
    id: string;
    status: StatusReserva;
    mesa: { id: string; numero: string };
    assento: { id: string; numero: number };
    hold_expires_at: string | null;
    confirmado_at: string | null;
    links: {
        self: string;
        confirmar: string | null;
        cancelar: string | null;
        trocar: string | null;
    };
}
```

### 3.11 `PedidoExtra`, `ProdutoExtra` — `/extras/*` ✅

```ts
export interface ProdutoExtraDto {
    id: string;
    nome: string;
    categoria: 'alimentacao' | 'decoracao' | 'audiovisual' | 'servico' | 'outro';
    preco_centavos: number;
    estoque: { tipo: 'infinito' | 'finito'; qtd_restante: number | null };
    descricao: string;
    imagens: Array<{ url: string; alt: string }>;
    links: { self: string; pedido: string };
}

export type StatusPedidoExtra =
    | 'aguardando_pagamento'
    | 'pago'
    | 'cancelado'
    | 'estornado'
    | 'em_preparacao'
    | 'entregue';

export interface ItemPedidoExtraDto {
    produto: { id: string; nome: string };
    quantidade: number;
    preco_unitario_centavos: number;
}

export interface PedidoExtraDto {
    id: string;
    status: StatusPedidoExtra;
    valor_total_centavos: number;
    itens: ItemPedidoExtraDto[];
    pagamento: {
        id: string;
        metodo: MetodoPagamento;
        status: StatusPagamento;
        qrcode?: string;
    } | null;
    links: {
        self: string;
        pagar: string | null;
        cancelar: string | null;
    };
}
```

### 3.12 `Enquete`, `OpcaoEnquete`, `Voto` — `/enquetes/*` ✅

```ts
export type StatusEnquete = 'rascunho' | 'aberta' | 'encerrada' | 'arquivada';
export type TipoEnquete = 'unica' | 'multipla';

export interface OpcaoEnqueteDto {
    id: string;
    rotulo: string;
    ordem: number;
}

export interface EnqueteListItemDto {
    id: string;
    titulo: string;
    tipo: TipoEnquete;
    status: StatusEnquete;
    janela: { abre_at: string | null; fecha_at: string | null };
    permite_edicao: boolean;
    ja_votei: boolean;
    links: { self: string; votar: string | null };
}

export interface EnqueteDetalheDto extends EnqueteListItemDto {
    descricao: string;
    resultado_publico: boolean;
    opcoes: OpcaoEnqueteDto[];
    meu_voto: { opcao_id: string; registrado_at: string } | null;
    resultado: Array<{ opcao_id: string; contagem: number; percentual: number }> | null;
}

export interface VotoDto {
    id: string;
    registrado_at: string;
    opcao: { id: string; rotulo: string };
    links: { self: string };
}
```

### 3.13 `Cota` — `GET /me/cotas` ✅

```ts
export type TipoCota = 'base' | 'transferivel' | 'cortesia' | 'staff' | 'extra';

export interface CotaDto {
    tipo: TipoCota;
    limite: number | null; // null = infinito (ex.: extra)
    utilizados: number;
    saldo: number | null; // null quando limite é null
}

export interface CotasPorEventoDto {
    evento: { id: string; slug: string };
    cotas: CotaDto[];
    links: { self: string; emitir: string };
}
```

### 3.14 Envelopes genéricos (cursor + erro)

```ts
export interface CursorMeta {
    per_page: number;
    next_cursor: string | null;
    prev_cursor: string | null;
}

export interface CursorLinks {
    self: string;
    next: string | null;
    prev: string | null;
}

export interface CursorList<T> {
    data: T[];
    meta: CursorMeta;
    links: CursorLinks;
}

export interface SingleEnvelope<T> {
    data: T;
}

export interface ErrorEnvelope {
    error: ErrorKey;
    message: string;
    details: { fields?: Record<string, string[]> } | Record<string, unknown> | null;
    request_id: string;
    timestamp: string;
}

export type ErrorKey =
    | 'Unauthenticated'
    | 'Forbidden'
    | 'ValidationError'
    | 'NotFound'
    | 'MethodNotAllowed'
    | 'DomainError'
    | 'InvariantViolation'
    | 'AssentoIndisponivel'
    | 'HoldExpirado'
    | 'CotaEsgotada'
    | 'WebhookInvalido'
    | 'GatewayIndisponivel'
    | 'PagamentoDuplicado'
    | 'IdempotencyConflict'
    | 'EndpointSunset'
    | 'PayloadTooLarge'
    | 'RateLimitExceeded'
    | 'ServiceUnavailable'
    | 'InternalServerError';
```

---

## 4. View Models por módulo

### 4.1 `FormandoViewModel` (módulo Auth/Perfil)

```ts
// resources/spa/src/view-models/formando.ts
import type { FormandoMeDto } from '@/api/dto/me';

export interface FormandoViewModel {
    id: string;
    nome: string;
    email: string;
    primeiroNome: string;
    displayName: string; // "Mariana S."
    initialsAvatar: string; // "MS"
    tipo: FormandoMeDto['tipo'];
    isFormando: boolean;
    isResponsavel: boolean;
    isComissao: boolean;
    roles: string[];
    abilities: Set<string>; // Set para checagem O(1)
    vinculos: Array<{
        formandoUlid: string;
        turmaUlid: string;
        turmaCodigo: string;
        eventoUlid: string;
        eventoSlug: string;
    }>;
    // Atalhos
    possuiEvento: boolean;
    eventoPrincipalUlid: string | null;
}

export function toFormandoViewModel(dto: FormandoMeDto): FormandoViewModel {
    const primeiroNome = dto.nome.split(' ')[0] ?? dto.nome;
    const ultimo = dto.nome.split(' ').slice(-1)[0] ?? '';
    const displayName = ultimo && ultimo !== primeiroNome ? `${primeiroNome} ${ultimo.charAt(0)}.` : primeiroNome;
    const initials = (primeiroNome.charAt(0) + (ultimo.charAt(0) || '')).toUpperCase() || '?';

    return {
        id: dto.id,
        nome: dto.nome,
        email: dto.email,
        primeiroNome,
        displayName,
        initialsAvatar: initials,
        tipo: dto.tipo,
        isFormando: dto.tipo === 'formando',
        isResponsavel: dto.tipo === 'responsavel',
        isComissao: dto.tipo === 'comissao',
        roles: dto.roles,
        abilities: new Set(dto.abilities),
        vinculos: dto.formandos.map((v) => ({
            formandoUlid: v.id,
            turmaUlid: v.turma.id,
            turmaCodigo: v.turma.codigo,
            eventoUlid: v.evento.id,
            eventoSlug: v.evento.slug,
        })),
        possuiEvento: dto.formandos.length > 0,
        eventoPrincipalUlid: dto.formandos[0]?.evento.id ?? null,
    };
}

export function can(vm: FormandoViewModel, ability: string): boolean {
    return vm.abilities.has(ability);
}
```

### 4.2 `AdesaoViewModel` (módulo Adesão)

```ts
// resources/spa/src/view-models/adesao.ts
import type { AdesaoDto, StatusAdesao } from '@/api/dto/adesao';
import { formatBRL } from '@/lib/money';
import { formatDateTimePtBr } from '@/lib/date';

export interface AdesaoViewModel {
    id: string;
    status: StatusAdesao;
    statusLabel: string;
    statusColor: 'neutral' | 'warning' | 'success' | 'danger';
    eventoUlid: string;
    eventoSlug: string;
    pacoteNome: string;
    valorTotalCentavos: number;
    valorTotalFormatado: string;
    qtdParcelas: number;
    confirmadaEmFormatado: string | null;
    parcelas: {
        total: number;
        pagas: number;
        pendentes: number;
        vencidas: number;
        percentualPago: number; // 0..100
    };
    podeCancelar: boolean;
    linkSelf: string;
    linkExtrato: string;
}

const STATUS_ADESAO_LABEL: Record<StatusAdesao, string> = {
    rascunho: 'Rascunho',
    pendente_pagamento: 'Aguardando pagamento',
    ativa: 'Ativa',
    cancelada: 'Cancelada',
    inadimplente: 'Inadimplente',
    concluida: 'Concluída',
};

const STATUS_ADESAO_COLOR: Record<StatusAdesao, AdesaoViewModel['statusColor']> = {
    rascunho: 'neutral',
    pendente_pagamento: 'warning',
    ativa: 'success',
    cancelada: 'neutral',
    inadimplente: 'danger',
    concluida: 'success',
};

export function toAdesaoViewModel(dto: AdesaoDto): AdesaoViewModel {
    const { total, pagas } = dto.parcelas_resumo;
    const percentualPago = total > 0 ? Math.round((pagas / total) * 100) : 0;

    return {
        id: dto.id,
        status: dto.status,
        statusLabel: STATUS_ADESAO_LABEL[dto.status],
        statusColor: STATUS_ADESAO_COLOR[dto.status],
        eventoUlid: dto.evento.id,
        eventoSlug: dto.evento.slug,
        pacoteNome: dto.pacote.nome,
        valorTotalCentavos: dto.valor_total_centavos,
        valorTotalFormatado: formatBRL(dto.valor_total_centavos),
        qtdParcelas: dto.qtd_parcelas,
        confirmadaEmFormatado: dto.confirmada_at ? formatDateTimePtBr(dto.confirmada_at) : null,
        parcelas: { ...dto.parcelas_resumo, percentualPago },
        podeCancelar: dto.links.cancelar !== null,
        linkSelf: dto.links.self,
        linkExtrato: dto.links.extrato,
    };
}
```

### 4.3 `ParcelaViewModel` (módulo Financeiro)

```ts
// resources/spa/src/view-models/parcela.ts
import type { ExtratoItemDto, TipoMovimentoExtrato } from '@/api/dto/extrato';
import { formatBRL } from '@/lib/money';
import { formatDatePtBr, diffInDays } from '@/lib/date';

export type ParcelaStatus = 'pendente' | 'paga' | 'vencida' | 'cancelada' | 'outro';

export interface ParcelaViewModel {
    id: string;
    tipo: TipoMovimentoExtrato;
    status: ParcelaStatus;
    statusLabel: string;
    statusColor: 'neutral' | 'success' | 'warning' | 'danger';
    dataMovimento: string; // ISO original
    dataMovimentoFormatada: string; // dd/mm/aaaa
    valorCentavos: number;
    valorFormatado: string; // R$ 1.500,99
    descricao: string;
    diasAteVencimento: number | null; // negativo se já venceu
    podePagar: boolean;
    linkPagar: string | null;
    linkComprovante: string | null;
}

const TIPO_TO_STATUS: Record<TipoMovimentoExtrato, ParcelaStatus> = {
    parcela_pendente: 'pendente',
    parcela_paga: 'paga',
    parcela_vencida: 'vencida',
    parcela_cancelada: 'cancelada',
    pedido_extra: 'outro',
    estorno: 'outro',
};

const STATUS_LABEL: Record<ParcelaStatus, string> = {
    pendente: 'Pendente',
    paga: 'Paga',
    vencida: 'Vencida',
    cancelada: 'Cancelada',
    outro: '—',
};

const STATUS_COLOR: Record<ParcelaStatus, ParcelaViewModel['statusColor']> = {
    pendente: 'warning',
    paga: 'success',
    vencida: 'danger',
    cancelada: 'neutral',
    outro: 'neutral',
};

export function toParcelaViewModel(dto: ExtratoItemDto): ParcelaViewModel {
    const status = TIPO_TO_STATUS[dto.tipo];
    return {
        id: dto.id,
        tipo: dto.tipo,
        status,
        statusLabel: STATUS_LABEL[status],
        statusColor: STATUS_COLOR[status],
        dataMovimento: dto.data_movimento,
        dataMovimentoFormatada: formatDatePtBr(dto.data_movimento),
        valorCentavos: dto.valor_centavos,
        valorFormatado: formatBRL(dto.valor_centavos),
        descricao: dto.descricao,
        diasAteVencimento: status === 'pendente' ? diffInDays(dto.data_movimento) : null,
        podePagar: status === 'pendente' || status === 'vencida',
        linkPagar: dto.links.pagar,
        linkComprovante: dto.links.comprovante,
    };
}
```

### 4.4 `PagamentoViewModel` (módulo Pagamento)

```ts
// resources/spa/src/view-models/pagamento.ts
import type { PagamentoDto, StatusPagamento } from '@/api/dto/pagamento';
import { formatBRL } from '@/lib/money';
import { formatDateTimePtBr } from '@/lib/date';

export interface PagamentoViewModel {
    id: string;
    status: StatusPagamento;
    statusLabel: string;
    statusColor: 'neutral' | 'warning' | 'success' | 'danger';
    metodo: PagamentoDto['metodo'];
    metodoLabel: string;
    valorCentavos: number;
    valorFormatado: string;
    pagoEmFormatado: string | null;
    origemDescricao: string;
    boleto: PagamentoDto['boleto'];
    pix: PagamentoDto['pix'];
    cartao: PagamentoDto['cartao'];
    comprovanteUrl: string | null;
    estaFinalizado: boolean;
    deveFazerPolling: boolean;
}

const STATUS_LABEL: Record<StatusPagamento, string> = {
    pendente: 'Aguardando pagamento',
    em_processamento: 'Processando',
    pago: 'Pago',
    falho: 'Falhou',
    estornado: 'Estornado',
    cancelado: 'Cancelado',
};

const STATUS_COLOR: Record<StatusPagamento, PagamentoViewModel['statusColor']> = {
    pendente: 'warning',
    em_processamento: 'warning',
    pago: 'success',
    falho: 'danger',
    estornado: 'neutral',
    cancelado: 'neutral',
};

const METODO_LABEL: Record<PagamentoDto['metodo'], string> = {
    boleto: 'Boleto bancário',
    pix: 'PIX',
    cartao: 'Cartão de crédito',
};

export function toPagamentoViewModel(dto: PagamentoDto): PagamentoViewModel {
    const finalizado = ['pago', 'falho', 'estornado', 'cancelado'].includes(dto.status);
    return {
        id: dto.id,
        status: dto.status,
        statusLabel: STATUS_LABEL[dto.status],
        statusColor: STATUS_COLOR[dto.status],
        metodo: dto.metodo,
        metodoLabel: METODO_LABEL[dto.metodo],
        valorCentavos: dto.valor_centavos,
        valorFormatado: formatBRL(dto.valor_centavos),
        pagoEmFormatado: dto.pago_em ? formatDateTimePtBr(dto.pago_em) : null,
        origemDescricao: dto.origem.descricao,
        boleto: dto.boleto,
        pix: dto.pix,
        cartao: dto.cartao,
        comprovanteUrl: dto.comprovante_url,
        estaFinalizado: finalizado,
        deveFazerPolling: !finalizado,
    };
}
```

### 4.5 `ConviteViewModel` (módulo Convites)

```ts
// resources/spa/src/view-models/convite.ts
import type { ConviteDto, StatusConvite, TipoConvite } from '@/api/dto/convite';
import { formatDateTimePtBr } from '@/lib/date';

export interface ConviteViewModel {
    id: string;
    codigo: string;
    status: StatusConvite;
    statusLabel: string;
    statusColor: 'neutral' | 'warning' | 'success' | 'danger';
    tipo: TipoConvite;
    tipoLabel: string;
    convidadoNome: string;
    convidadoEmail: string | null;
    convidadoTelefone: string | null;
    entregueEmFormatado: string | null;
    visualizadoEmFormatado: string | null;
    confirmadoEmFormatado: string | null;
    linkRsvpPublico: string | null; // construído a partir de token, não links (ver nota)
    qrCodeDataUrl: string | null; // preenchido depois via lib qrcode
    acoes: {
        podeEditar: boolean;
        podeCancelar: boolean;
        podeReemitir: boolean;
        podeTransferir: boolean;
    };
    linkSelf: string;
}

const STATUS_LABEL: Record<StatusConvite, string> = {
    emitido: 'Emitido',
    enviado: 'Enviado',
    visualizado: 'Visualizado',
    confirmado: 'Confirmado',
    recusado: 'Recusado',
    cancelado: 'Cancelado',
};

const STATUS_COLOR: Record<StatusConvite, ConviteViewModel['statusColor']> = {
    emitido: 'neutral',
    enviado: 'warning',
    visualizado: 'warning',
    confirmado: 'success',
    recusado: 'danger',
    cancelado: 'neutral',
};

const TIPO_LABEL: Record<TipoConvite, string> = {
    nominal: 'Nominal',
    transferivel: 'Transferível',
    cortesia: 'Cortesia',
    staff: 'Staff',
    extra: 'Extra',
};

export function toConviteViewModel(dto: ConviteDto): ConviteViewModel {
    return {
        id: dto.id,
        codigo: dto.codigo,
        status: dto.status,
        statusLabel: STATUS_LABEL[dto.status],
        statusColor: STATUS_COLOR[dto.status],
        tipo: dto.tipo,
        tipoLabel: TIPO_LABEL[dto.tipo],
        convidadoNome: dto.convidado.nome,
        convidadoEmail: dto.convidado.email,
        convidadoTelefone: dto.convidado.telefone,
        entregueEmFormatado: dto.entregue_at ? formatDateTimePtBr(dto.entregue_at) : null,
        visualizadoEmFormatado: dto.visualizado_at ? formatDateTimePtBr(dto.visualizado_at) : null,
        confirmadoEmFormatado: dto.confirmado_at ? formatDateTimePtBr(dto.confirmado_at) : null,
        linkRsvpPublico: null, // derivar do token; a API não retorna token no convite do emissor
        qrCodeDataUrl: null,
        acoes: {
            podeEditar: dto.status === 'emitido',
            podeCancelar: dto.links.cancelar !== null,
            podeReemitir: dto.links.reemitir !== null,
            podeTransferir: dto.links.transferir !== null,
        },
        linkSelf: dto.links.self,
    };
}
```

> ❓ Pendência: o shape de `ConviteDto` atual **não** expõe o `token` público ao emissor. Para o formando visualizar o QR code de compartilhamento, precisamos de um endpoint dedicado (`POST /eventos/{ulid}/convites/{ulid}/tokens`) ou campo `token_publico` no resource quando o emissor é o próprio formando. Ver Apêndice B.

### 4.6 `ConvitePublicoViewModel` (página RSVP)

```ts
// resources/spa/src/view-models/convite-publico.ts
import type { ConvitePublicoDto } from '@/api/dto/convite';
import { formatDateTimePtBr } from '@/lib/date';

export interface ConvitePublicoViewModel {
    conviteId: string;
    codigo: string;
    convidadoNome: string;
    statusConvite: ConvitePublicoDto['convite']['status'];
    jaRespondeu: boolean;
    eventoNome: string;
    eventoDataFormatada: string;
    localNome: string | null;
    localEndereco: string | null;
    linkRsvp: string;
}

export function toConvitePublicoViewModel(dto: ConvitePublicoDto): ConvitePublicoViewModel {
    return {
        conviteId: dto.convite.id,
        codigo: dto.convite.codigo,
        convidadoNome: dto.convite.convidado.nome,
        statusConvite: dto.convite.status,
        jaRespondeu: ['confirmado', 'recusado'].includes(dto.convite.status),
        eventoNome: dto.evento.nome,
        eventoDataFormatada: formatDateTimePtBr(dto.evento.data_evento),
        localNome: dto.evento.local?.nome ?? null,
        localEndereco: dto.evento.local?.endereco ?? null,
        linkRsvp: dto.links.rsvp,
    };
}
```

### 4.7 `ReservaAssentoViewModel` (módulo Seating)

```ts
// resources/spa/src/view-models/reserva.ts
import type { ReservaAssentoDto, StatusReserva } from '@/api/dto/reserva';
import { formatDateTimePtBr } from '@/lib/date';

export interface ReservaAssentoViewModel {
    id: string;
    status: StatusReserva;
    statusLabel: string;
    mesaUlid: string;
    mesaNumero: string;
    assentoUlid: string;
    assentoNumero: number;
    holdExpiresAt: string | null; // ISO original (server)
    holdExpiresAtFormatado: string | null; // rotulado PT-BR
    secondsRemaining: number; // calculado em tempo de render
    estaExpirado: boolean;
    confirmadoEmFormatado: string | null;
    acoes: {
        podeConfirmar: boolean;
        podeCancelar: boolean;
        podeTrocar: boolean;
    };
    links: {
        self: string;
        confirmar: string | null;
        cancelar: string | null;
        trocar: string | null;
    };
}

const STATUS_LABEL: Record<StatusReserva, string> = {
    hold: 'Aguardando confirmação',
    confirmada: 'Confirmada',
    liberada: 'Liberada',
    cancelada: 'Cancelada',
};

export function toReservaAssentoViewModel(dto: ReservaAssentoDto, nowMs: number = Date.now()): ReservaAssentoViewModel {
    const expiresMs = dto.hold_expires_at ? new Date(dto.hold_expires_at).getTime() : 0;
    const secondsRemaining = dto.hold_expires_at ? Math.max(0, Math.floor((expiresMs - nowMs) / 1000)) : 0;
    const estaExpirado = dto.status === 'hold' && secondsRemaining === 0;

    return {
        id: dto.id,
        status: dto.status,
        statusLabel: STATUS_LABEL[dto.status],
        mesaUlid: dto.mesa.id,
        mesaNumero: dto.mesa.numero,
        assentoUlid: dto.assento.id,
        assentoNumero: dto.assento.numero,
        holdExpiresAt: dto.hold_expires_at,
        holdExpiresAtFormatado: dto.hold_expires_at ? formatDateTimePtBr(dto.hold_expires_at) : null,
        secondsRemaining,
        estaExpirado,
        confirmadoEmFormatado: dto.confirmado_at ? formatDateTimePtBr(dto.confirmado_at) : null,
        acoes: {
            podeConfirmar: dto.links.confirmar !== null && !estaExpirado,
            podeCancelar: dto.links.cancelar !== null,
            podeTrocar: dto.links.trocar !== null && !estaExpirado,
        },
        links: dto.links,
    };
}
```

> Nota: `secondsRemaining` deve ser recalculado a cada tick do hold timer (1s). O ViewModel é imutável na renderização; o componente `<HoldCountdown>` usa o `holdExpiresAt` (fonte de verdade servidor) para recomputar. Ver [§4 do Planejamento Frontend](../prd/PLANEJAMENTO_FRONTEND_REACT.md) e `hold-store.ts`.

### 4.8 `AssentoViewModel` + `MapaMesasViewModel`

```ts
// resources/spa/src/view-models/mapa.ts
import type { AssentoDto, MapaMesasDto } from '@/api/dto/mapa';

export interface AssentoViewModel {
    id: string;
    numero: number;
    statusRuntime: AssentoDto['status_runtime'];
    interagivel: boolean; // livre ou hold próprio
    estaDoUsuario: boolean; // preenchido externamente
    rotulo: string; // "Mesa 12 — Assento 2"
}

export interface MesaViewModel {
    id: string;
    numero: string;
    capacidade: number;
    assentos: AssentoViewModel[];
    totalLivres: number;
    totalConfirmados: number;
    totalEmHold: number;
}

export interface SetorViewModel {
    id: string;
    nome: string;
    mesas: MesaViewModel[];
}

export interface MapaMesasViewModel {
    mapaId: string;
    mapaNome: string;
    setores: SetorViewModel[];
    atualizadoEm: string;
    linkReservar: string;
}

export function toMapaMesasViewModel(dto: MapaMesasDto, reservasDoUsuario: Set<string>): MapaMesasViewModel {
    return {
        mapaId: dto.mapa.id,
        mapaNome: dto.mapa.nome,
        setores: dto.setores.map((setor) => ({
            id: setor.id,
            nome: setor.nome,
            mesas: setor.mesas.map((mesa) => {
                const assentos = mesa.assentos.map<AssentoViewModel>((a) => ({
                    id: a.id,
                    numero: a.numero,
                    statusRuntime: a.status_runtime,
                    interagivel: a.status_runtime === 'livre',
                    estaDoUsuario: reservasDoUsuario.has(a.id),
                    rotulo: `Mesa ${mesa.numero} — Assento ${a.numero}`,
                }));
                return {
                    id: mesa.id,
                    numero: mesa.numero,
                    capacidade: mesa.capacidade,
                    assentos,
                    totalLivres: assentos.filter((a) => a.statusRuntime === 'livre').length,
                    totalConfirmados: assentos.filter((a) => a.statusRuntime === 'confirmada').length,
                    totalEmHold: assentos.filter((a) => a.statusRuntime === 'hold').length,
                };
            }),
        })),
        atualizadoEm: dto.atualizado_em,
        linkReservar: dto.links.reservar,
    };
}
```

### 4.9 `CotaViewModel`

```ts
// resources/spa/src/view-models/cota.ts
import type { CotaDto, CotasPorEventoDto, TipoCota } from '@/api/dto/cota';

export interface CotaViewModel {
    tipo: TipoCota;
    tipoLabel: string;
    limite: number | null;
    utilizados: number;
    saldo: number | null;
    saldoLabel: string; // '2 restantes' | 'ilimitado'
    percentualUsado: number; // 0..100
    esgotada: boolean;
}

export interface CotasEventoViewModel {
    eventoUlid: string;
    eventoSlug: string;
    cotas: CotaViewModel[];
    linkEmitir: string;
}

const TIPO_LABEL: Record<TipoCota, string> = {
    base: 'Convites base',
    transferivel: 'Convites transferíveis',
    cortesia: 'Cortesias',
    staff: 'Staff',
    extra: 'Convites extras',
};

export function toCotaViewModel(dto: CotaDto): CotaViewModel {
    const esgotada = dto.limite !== null && dto.saldo !== null && dto.saldo === 0;
    const percentualUsado = dto.limite !== null && dto.limite > 0 ? Math.round((dto.utilizados / dto.limite) * 100) : 0;
    return {
        tipo: dto.tipo,
        tipoLabel: TIPO_LABEL[dto.tipo],
        limite: dto.limite,
        utilizados: dto.utilizados,
        saldo: dto.saldo,
        saldoLabel: dto.saldo === null ? 'ilimitado' : `${dto.saldo} restante${dto.saldo === 1 ? '' : 's'}`,
        percentualUsado,
        esgotada,
    };
}

export function toCotasEventoViewModel(dto: CotasPorEventoDto): CotasEventoViewModel {
    return {
        eventoUlid: dto.evento.id,
        eventoSlug: dto.evento.slug,
        cotas: dto.cotas.map(toCotaViewModel),
        linkEmitir: dto.links.emitir,
    };
}
```

### 4.10 `EnqueteViewModel`

```ts
// resources/spa/src/view-models/enquete.ts
import type { EnqueteDetalheDto, EnqueteListItemDto, StatusEnquete } from '@/api/dto/enquete';
import { formatDateTimePtBr } from '@/lib/date';

export interface EnqueteListItemViewModel {
    id: string;
    titulo: string;
    tipo: EnqueteListItemDto['tipo'];
    status: StatusEnquete;
    statusLabel: string;
    aberta: boolean;
    abreEmFormatado: string | null;
    fechaEmFormatado: string | null;
    jaVotei: boolean;
    permiteEdicao: boolean;
    linkSelf: string;
    linkVotar: string | null;
}

export interface EnqueteDetalheViewModel extends EnqueteListItemViewModel {
    descricao: string;
    opcoes: Array<{ id: string; rotulo: string; ordem: number; percentual: number | null }>;
    meuVotoOpcaoId: string | null;
    resultadoPublico: boolean;
    temResultadoVisivel: boolean;
}

const STATUS_LABEL: Record<StatusEnquete, string> = {
    rascunho: 'Rascunho',
    aberta: 'Aberta',
    encerrada: 'Encerrada',
    arquivada: 'Arquivada',
};

export function toEnqueteListItemViewModel(dto: EnqueteListItemDto): EnqueteListItemViewModel {
    return {
        id: dto.id,
        titulo: dto.titulo,
        tipo: dto.tipo,
        status: dto.status,
        statusLabel: STATUS_LABEL[dto.status],
        aberta: dto.status === 'aberta',
        abreEmFormatado: dto.janela.abre_at ? formatDateTimePtBr(dto.janela.abre_at) : null,
        fechaEmFormatado: dto.janela.fecha_at ? formatDateTimePtBr(dto.janela.fecha_at) : null,
        jaVotei: dto.ja_votei,
        permiteEdicao: dto.permite_edicao,
        linkSelf: dto.links.self,
        linkVotar: dto.links.votar,
    };
}

export function toEnqueteDetalheViewModel(dto: EnqueteDetalheDto): EnqueteDetalheViewModel {
    const base = toEnqueteListItemViewModel(dto);
    const resultadoMap = new Map((dto.resultado ?? []).map((r) => [r.opcao_id, r.percentual]));
    return {
        ...base,
        descricao: dto.descricao,
        opcoes: dto.opcoes.map((o) => ({
            id: o.id,
            rotulo: o.rotulo,
            ordem: o.ordem,
            percentual: resultadoMap.get(o.id) ?? null,
        })),
        meuVotoOpcaoId: dto.meu_voto?.opcao_id ?? null,
        resultadoPublico: dto.resultado_publico,
        temResultadoVisivel: dto.resultado !== null,
    };
}
```

### 4.11 `ProdutoExtraViewModel` / `PedidoExtraViewModel`

```ts
// resources/spa/src/view-models/extras.ts
import type { PedidoExtraDto, ProdutoExtraDto, StatusPedidoExtra } from '@/api/dto/extras';
import { formatBRL } from '@/lib/money';

export interface ProdutoExtraViewModel {
    id: string;
    nome: string;
    categoria: ProdutoExtraDto['categoria'];
    precoCentavos: number;
    precoFormatado: string;
    estoqueLabel: string; // 'Ilimitado' | '42 restantes' | 'Esgotado'
    disponivel: boolean;
    descricao: string;
    imagemPrincipal: { url: string; alt: string } | null;
    linkSelf: string;
    linkPedido: string;
}

const STATUS_PEDIDO_LABEL: Record<StatusPedidoExtra, string> = {
    aguardando_pagamento: 'Aguardando pagamento',
    pago: 'Pago',
    cancelado: 'Cancelado',
    estornado: 'Estornado',
    em_preparacao: 'Em preparação',
    entregue: 'Entregue',
};

export interface PedidoExtraViewModel {
    id: string;
    status: StatusPedidoExtra;
    statusLabel: string;
    valorTotalCentavos: number;
    valorTotalFormatado: string;
    itens: Array<{
        produtoNome: string;
        quantidade: number;
        precoUnitarioFormatado: string;
        subtotalFormatado: string;
    }>;
    pagamentoId: string | null;
    linkSelf: string;
    linkPagar: string | null;
    linkCancelar: string | null;
}

export function toProdutoExtraViewModel(dto: ProdutoExtraDto): ProdutoExtraViewModel {
    const qtd = dto.estoque.qtd_restante;
    const isFinito = dto.estoque.tipo === 'finito';
    const disponivel = !isFinito || (qtd !== null && qtd > 0);
    const estoqueLabel = isFinito
        ? qtd === null || qtd === 0
            ? 'Esgotado'
            : `${qtd} restante${qtd === 1 ? '' : 's'}`
        : 'Ilimitado';

    return {
        id: dto.id,
        nome: dto.nome,
        categoria: dto.categoria,
        precoCentavos: dto.preco_centavos,
        precoFormatado: formatBRL(dto.preco_centavos),
        estoqueLabel,
        disponivel,
        descricao: dto.descricao,
        imagemPrincipal: dto.imagens[0] ?? null,
        linkSelf: dto.links.self,
        linkPedido: dto.links.pedido,
    };
}

export function toPedidoExtraViewModel(dto: PedidoExtraDto): PedidoExtraViewModel {
    return {
        id: dto.id,
        status: dto.status,
        statusLabel: STATUS_PEDIDO_LABEL[dto.status],
        valorTotalCentavos: dto.valor_total_centavos,
        valorTotalFormatado: formatBRL(dto.valor_total_centavos),
        itens: dto.itens.map((i) => ({
            produtoNome: i.produto.nome,
            quantidade: i.quantidade,
            precoUnitarioFormatado: formatBRL(i.preco_unitario_centavos),
            subtotalFormatado: formatBRL(i.preco_unitario_centavos * i.quantidade),
        })),
        pagamentoId: dto.pagamento?.id ?? null,
        linkSelf: dto.links.self,
        linkPagar: dto.links.pagar,
        linkCancelar: dto.links.cancelar,
    };
}
```

### 4.12 `EventoViewModel`

```ts
// resources/spa/src/view-models/evento.ts
import type { EventoDto } from '@/api/dto/evento';
import { formatDateTimePtBr } from '@/lib/date';

export interface EventoViewModel {
    id: string;
    slug: string;
    nome: string;
    dataEventoFormatada: string;
    status: EventoDto['status'];
    estaPublicado: boolean;
    janelas: {
        rsvpAberto: boolean;
        mesasAberto: boolean;
    };
    holdTtlSeconds: number;
    permiteTrocaAssento: boolean;
    links: EventoDto['links'];
}

export function toEventoViewModel(dto: EventoDto, now: Date = new Date()): EventoViewModel {
    const nowMs = now.getTime();
    const inJan = (abre: string | null, fecha: string | null) => {
        const a = abre ? new Date(abre).getTime() : -Infinity;
        const f = fecha ? new Date(fecha).getTime() : Infinity;
        return nowMs >= a && nowMs <= f;
    };
    return {
        id: dto.id,
        slug: dto.slug,
        nome: dto.nome,
        dataEventoFormatada: formatDateTimePtBr(dto.data_evento),
        status: dto.status,
        estaPublicado: dto.status === 'publicado',
        janelas: {
            rsvpAberto: inJan(dto.janelas.abre_rsvp_at, dto.janelas.fecha_rsvp_at),
            mesasAberto: inJan(dto.janelas.abre_mesas_at, dto.janelas.fecha_mesas_at),
        },
        holdTtlSeconds: dto.config.hold_ttl_seconds,
        permiteTrocaAssento: dto.config.permite_troca_assento,
        links: dto.links,
    };
}
```

---

## 5. Mapeamento API → UI (tabela)

| Endpoint                              | DTO principal                       | ViewModel                    | Tela / Componente                         | Formatters aplicados                               |
| ------------------------------------- | ----------------------------------- | ---------------------------- | ----------------------------------------- | -------------------------------------------------- |
| `GET /me`                             | `FormandoMeDto`                     | `FormandoViewModel`          | `/portal/home`, `/portal/perfil`          | `displayName`, `initialsAvatar`, `abilities: Set`  |
| `GET /me/eventos`                     | `CursorList<EventoDto>`             | `EventoViewModel[]`          | Seletor de evento (home)                  | `formatDateTimePtBr`                               |
| `GET /me/adesoes`                     | `CursorList<AdesaoDto>`             | `AdesaoViewModel[]`          | `/portal/home` KPIs, `/portal/financeiro` | `formatBRL`, `statusLabel`, `percentualPago`       |
| `GET /me/extrato`                     | `CursorList<ExtratoItemDto>`        | `ParcelaViewModel[]`         | `/portal/financeiro` lista                | `formatBRL`, `formatDatePtBr`, `diasAteVencimento` |
| `GET /me/cotas`                       | `{ data: CotasPorEventoDto[] }`     | `CotasEventoViewModel[]`     | `/portal/convites` header                 | `saldoLabel`, `percentualUsado`                    |
| `GET /me/convites`                    | `CursorList<ConviteDto>`            | `ConviteViewModel[]`         | `/portal/convites` lista                  | `statusLabel`, `tipoLabel`, datas                  |
| `GET /eventos/{ulid}`                 | `SingleEnvelope<EventoDto>`         | `EventoViewModel`            | Contexto global                           | janelas booleanas                                  |
| `GET /eventos/{ulid}/mesas/mapa`      | `SingleEnvelope<MapaMesasDto>`      | `MapaMesasViewModel`         | `/portal/mesas`                           | contagens por mesa                                 |
| `POST /eventos/{ulid}/mesas/reservas` | `SingleEnvelope<ReservaAssentoDto>` | `ReservaAssentoViewModel`    | `/portal/mesas` → confirm                 | `secondsRemaining`, `estaExpirado`                 |
| `POST .../reservas/{ulid}/confirmar`  | `SingleEnvelope<ReservaAssentoDto>` | `ReservaAssentoViewModel`    | Toast sucesso + atualização mapa          | `confirmadoEmFormatado`                            |
| `POST /pagamentos/intents`            | `SingleEnvelope<PagamentoDto>`      | `PagamentoViewModel`         | `/portal/pagamento/$parcela_ulid`         | `metodoLabel`, `statusLabel`, polling              |
| `GET /pagamentos/{ulid}`              | `SingleEnvelope<PagamentoDto>`      | `PagamentoViewModel`         | idem — polling                            | idem                                               |
| `GET /eventos/{ulid}/extras/catalogo` | `{ data: ProdutoExtraDto[] }`       | `ProdutoExtraViewModel[]`    | `/portal/extras`                          | `precoFormatado`, `estoqueLabel`, `disponivel`     |
| `POST /eventos/{ulid}/extras/pedidos` | `SingleEnvelope<PedidoExtraDto>`    | `PedidoExtraViewModel`       | `/portal/extras` → confirm                | `valorTotalFormatado`                              |
| `GET /eventos/{ulid}/enquetes`        | `{ data: EnqueteListItemDto[] }`    | `EnqueteListItemViewModel[]` | `/portal/enquetes`                        | `statusLabel`, `jaVotei`                           |
| `GET /eventos/{ulid}/enquetes/{ulid}` | `SingleEnvelope<EnqueteDetalheDto>` | `EnqueteDetalheViewModel`    | `/portal/enquetes/[id]` modal             | `percentual`, `temResultadoVisivel`                |
| `GET /convite/{token}` (público)      | `SingleEnvelope<ConvitePublicoDto>` | `ConvitePublicoViewModel`    | `/rsvp/$token`                            | `jaRespondeu`, `eventoDataFormatada`               |

---

## 6. Contratos por recurso (shapes JSON)

> Shapes **copiados** de [`api-contract.md`](../api/api-contract.md) para referência offline. Em caso de divergência, o contrato da API sempre prevalece. O `openapi-skeleton.yaml` é a fonte de verdade formal (lido pelo codegen).

### 6.1 Auth

#### 6.1.1 `POST /api/v1/auth/login` (request/response)

**Request:**

```json
{
    "email": "mariana@usp.br",
    "password": "SenhaSegura#123",
    "mode": "spa",
    "remember": false,
    "device_name": null
}
```

**Response 200 (`mode: spa`):**

```json
{
    "status": "ok",
    "user": {
        "id": "01J5K3B5GTYV8E2F1W0M8P2XQA",
        "email": "mariana@usp.br"
    }
}
```

Após esse login, o cookie `laravel_session` (HttpOnly, Secure, SameSite=lax) fica setado e o SPA pode chamar `GET /me`.

#### 6.1.2 `POST /api/v1/auth/logout`

Sem body. Response **204**.

#### 6.1.3 `GET /api/v1/me`

Ver [§3.1](#31-formandome--resposta-de-get-me-). Response 200 envolve `data: FormandoMeDto`.

### 6.2 Adesão (wizard 7 etapas)

A adesão é multi-etapa no cliente. **Cada etapa só persiste no servidor em pontos de commit definidos**; as demais etapas ficam no `wizard-store` (sessionStorage). Shapes por etapa:

#### Etapa "Dados pessoais" (form local, sem POST ainda)

> **Atualização 2026-04-23 (ver [SPEC-F-001 v0.3.0](../features/foundation/SPEC-F-001-contrato-e-turma.md) + [SPEC-010 v2.0.0](../features/SPEC-010-adesao-publica-codigo-contrato.md)):** wizard agora tem duas etapas adicionais **antes** de "Dados pessoais": "Escolher curso + período" (seleciona 1 turma dentro do contrato) e "Escolher pacote formatura" (`categoria='formatura'`). O form local abaixo é disparado depois dessas duas seleções, com `contrato_ulid`, `turma_ulid` e `pacote_ulid` já persistidos no `adesao-publica-store`.

```ts
interface DadosPessoaisForm {
    cpf: string; // com máscara
    telefone: string;
    data_nascimento: string; // dd/mm/aaaa
    contrato_ulid: string; // do GET /adesao/publico/{codigo-contrato}
    turma_ulid: string; // escolhido em "Escolher curso + período"
    pacote_ulid: string; // escolhido em "Escolher pacote formatura"
}
```

#### Etapa 2 — Responsável financeiro (form local)

```ts
interface Step2Form {
    responsavel_mesmo: boolean;
    responsavel?: {
        nome: string;
        cpf: string;
        email: string;
        telefone: string;
    };
}
```

#### Etapa 3 — Escolha do pacote (GET `/eventos/{ulid}/pacotes`)

> ❓ Pendência: endpoint `/eventos/{ulid}/pacotes` **não está** em `api-contract.md` atual. Ver Apêndice B.

#### Etapa 4 — Plano de pagamento (cálculo local + GET `/adesoes/simular`)

```ts
interface PlanoPagamento {
    qtd_parcelas: number;
    metodo_primeira_parcela: MetodoPagamento;
    metodo_demais: MetodoPagamento;
    data_vencimento_dia: 1 | 5 | 10 | 15 | 20 | 25;
}
```

#### Etapa 5 — Termos (checkbox + persistência)

#### Etapa 6 — Revisão (read-only)

#### Etapa 7 — Pagamento inicial (commit: `POST /adesoes` + `POST /pagamentos/intents`)

> ❓ Pendência: endpoint `POST /api/v1/adesoes` **não está** em `api-contract.md` atual. Precisamos do contrato formal antes de F3.

### 6.3 Financeiro — `GET /me/extrato` cursor paginado

**Response 200:**

```json
{
    "data": [
        {
            "id": "01J...",
            "tipo": "parcela_paga",
            "data_movimento": "2026-03-05T00:00:00-03:00",
            "valor_centavos": 150000,
            "descricao": "Parcela 3/10 — Pacote Premium",
            "referencia": { "tipo": "parcela", "id": "01J..." },
            "links": {
                "self": "...",
                "comprovante": "...",
                "pagar": null
            }
        }
    ],
    "meta": { "per_page": 50, "next_cursor": "eyJpZ...", "prev_cursor": null },
    "links": { "self": "...", "next": "...?page[cursor]=...", "prev": null }
}
```

O cliente sempre usa `useInfiniteQuery`:

```ts
useInfiniteQuery({
    queryKey: ['extrato', filters],
    queryFn: ({ pageParam }) =>
        api.get<CursorList<ExtratoItemDto>>('/me/extrato', {
            params: { 'page[cursor]': pageParam, ...filters },
        }),
    initialPageParam: null as string | null,
    getNextPageParam: (last) => last.data.meta.next_cursor,
});
```

### 6.4 Convites

#### 6.4.1 Individual — `POST /eventos/{ulid}/convites`

**Request:**

```json
{
    "tipo": "nominal",
    "convidado": {
        "nome": "Carlos Alberto",
        "email": "carlos@example.com",
        "telefone": "+55 11 99876-5432"
    },
    "origem_cota": "base"
}
```

**Response 201 + `Location: /eventos/{ulid}/convites/{ulid}`** retorna `ConviteDto`.

Erros prováveis:

- `409 CotaEsgotada` → mostrar banner bloqueando o form.
- `422 ValidationError` → inline nos campos via `details.fields`.

#### 6.4.2 Lote — `POST /eventos/{ulid}/convites/lotes`

**Request:** array `convites: []` (min 1, max 500).
**Headers:** `X-Idempotency-Key: <ulid>` (obrigatório).
**Response 202:** `LoteConvitesDto` com `status_url` para polling.

O cliente faz `useQuery` em `status_url` com `refetchInterval: 3000` até `status === 'concluido'`.

#### 6.4.3 RSVP público — `GET /convite/{token}` + `POST /convite/{token}/rsvp`

Ver [§3.8](#38-convitepublico-rsvp--resposta-de-get-convitetoken-). Request do POST:

```json
{
    "resposta": "confirmo",
    "nome_confirmado": "Carlos Alberto Silva",
    "observacao": "Intolerância a lactose"
}
```

### 6.5 Seating (mapa + reservas)

#### 6.5.1 Mapa snapshot — `GET /eventos/{ulid}/mesas/mapa`

Ver [§3.9](#39-mapamesas-mesa-assento--get-eventosulidmesasmapa-). Durante hold ativo, o cliente pode **optar** por `?since=<iso>` para delta.

#### 6.5.2 Reserva — `POST /eventos/{ulid}/mesas/reservas`

**Request:**

```json
{
    "assento_ulid": "01J...",
    "convite_ulid": "01J...",
    "origem": "formando",
    "observacao": "Próximo à família"
}
```

**Headers:** `X-Idempotency-Key: <ulid>` (obrigatório).
**Response 201:** `ReservaAssentoDto` com `status: 'hold'` e `hold_expires_at`.

#### 6.5.3 Confirmar — `POST .../reservas/{ulid}/confirmar`

Sem body. Response 200: `ReservaAssentoDto` com `status: 'confirmada'`.
**Erros:**

- `410 HoldExpirado` → redirecionar ao mapa, recomeçar.
- `409 InvariantViolation` → status não era `hold`.

#### 6.5.4 Cancelar — `DELETE .../reservas/{ulid}`

Response 204. Body `?motivo=` opcional.

#### 6.5.5 Trocar — `POST .../reservas/{ulid}/trocar`

**Request:**

```json
{
    "assento_destino_ulid": "01J...",
    "origem": "formando"
}
```

**Headers:** `X-Idempotency-Key: <ulid>` (obrigatório).
**Response 200:** `ReservaAssentoDto` novo (nova mesa/assento, status=hold, novo hold_expires_at).

### 6.6 Extras

- `GET /eventos/{ulid}/extras/catalogo` — lista de produtos com `ProdutoExtraDto`.
- `POST /eventos/{ulid}/extras/pedidos` — cria pedido + inicia pagamento inline. Idempotente.
- `GET /eventos/{ulid}/extras/pedidos/{ulid}` — status do pedido.

### 6.7 Pagamentos

- `POST /pagamentos/intents` (idempotente):

```json
{
    "origem_tipo": "parcela",
    "origem_ulid": "01J...",
    "metodo": "boleto"
}
```

- `GET /pagamentos/{ulid}` — polling até `status` final. O ViewModel expõe `deveFazerPolling` (boolean).

### 6.8 Enquetes

- `GET /eventos/{ulid}/enquetes` — lista.
- `GET /eventos/{ulid}/enquetes/{ulid}` — detalhe (inclui `meu_voto` e `resultado` quando público/encerrado).
- `POST /eventos/{ulid}/enquetes/{ulid}/votos`:

```json
// tipo=unica
{ "opcao_ulid": "01J..." }
// tipo=multipla
{ "opcoes_ulids": ["01J...", "01J..."] }
```

---

## 7. Dados críticos por módulo

Classificação de cada tipo de dado para orientar cache, retry e invalidação.

| Módulo     | Recurso                       | Imutável?            | Cacheável? (staleTime)      | Polling? (interval)                    | Invalidação                                      |
| ---------- | ----------------------------- | -------------------- | --------------------------- | -------------------------------------- | ------------------------------------------------ |
| Auth       | `GET /me`                     | Não                  | 5 min                       | Não                                    | Após login, logout, `Unauthenticated`            |
| Auth       | CSRF cookie                   | Token volátil        | per-session (não cacheável) | Não                                    | Antes de toda mutação                            |
| Contexto   | `GET /me/eventos`             | Não                  | 1 min                       | Não                                    | Após mudança de vínculo                          |
| Contexto   | `GET /me/adesoes`             | Não                  | 30 s                        | Não                                    | Após confirmar adesão ou pagamento               |
| Contexto   | `GET /me/cotas`               | Não                  | 30 s                        | Não                                    | Após emitir/cancelar convite                     |
| Contexto   | `GET /me/extrato`             | Não                  | 30 s                        | Após POST /pagamentos (2 s por 10 min) | Após webhook pagamento                           |
| Evento     | `GET /eventos/{ulid}`         | Semi-imutável        | 10 min                      | Não                                    | Manual após admin editar                         |
| Convites   | Lista                         | Não                  | 30 s                        | Não                                    | Após POST/PATCH/DELETE convite                   |
| Convites   | Lote (`GET .../lotes/{ulid}`) | Sim após `concluido` | 0 (polling)                 | 3 s enquanto `processando`             | Nunca (imutável após fim)                        |
| RSVP       | `GET /convite/{token}`        | Não                  | 0 (sempre refetch)          | Não                                    | Após POST rsvp                                   |
| Seating    | `GET .../mesas/mapa` snapshot | Não                  | 0                           | 5 s durante hold ativo                 | Após próprios POST reserva/confirm/cancel/trocar |
| Seating    | `POST reserva` response       | Não (hold expira)    | 0                           | 1 s (para `secondsRemaining`)          | Após POST confirmar/cancelar                     |
| Extras     | `GET catalogo`                | Semi-imutável        | 5 min                       | Não                                    | Manual após admin editar                         |
| Extras     | `GET pedido/{ulid}`           | Não                  | 10 s                        | 3 s enquanto `aguardando_pagamento`    | Após webhook                                     |
| Pagamentos | `GET /pagamentos/{ulid}`      | Não                  | 0                           | 2 s até status final, max 10 min total | Após webhook                                     |
| Enquetes   | Lista                         | Não                  | 5 min                       | Não                                    | Após POST /votos                                 |
| Enquetes   | Detalhe                       | Não                  | 1 min                       | 30 s se `status=aberta`                | Após POST voto                                   |

**Regra geral:** dados sensíveis a invalidação externa (webhook) têm `staleTime` baixo + polling ativo durante operação pendente. Dados semi-imutáveis (evento, catálogo) têm `staleTime` alto e contam com `queryClient.invalidateQueries` manual após mutações admin (fora do SPA, mas relevante em consoles futuros).

---

## 8. Shape esperado de respostas

Toda resposta da API v1 segue **exatamente** um dos 4 shapes abaixo:

### 8.1 Single envelope

```json
{ "data": { "...": "..." } }
```

### 8.2 Cursor list envelope

```json
{
    "data": [{ "...": "..." }],
    "meta": { "per_page": 50, "next_cursor": null, "prev_cursor": null },
    "links": { "self": "...", "next": null, "prev": null }
}
```

### 8.3 Offset list envelope (raro — catálogos e enquetes)

```json
{
    "data": [{ "...": "..." }],
    "meta": { "per_page": 50, "current_page": 1, "last_page": 3, "total": 135 },
    "links": { "self": "...", "first": "...", "last": "...", "next": "...", "prev": null }
}
```

### 8.4 Error envelope

```json
{
    "error": "ValidationError",
    "message": "Dados de entrada inválidos.",
    "details": { "fields": { "email": ["..."] } },
    "request_id": "01J...",
    "timestamp": "2026-04-17T14:32:11Z"
}
```

### 8.5 Regra de branch

```ts
function isError(payload: unknown): payload is ErrorEnvelope {
    return (
        typeof payload === 'object' &&
        payload !== null &&
        'error' in payload &&
        'request_id' in payload &&
        'timestamp' in payload
    );
}
```

O Axios client **nunca** entrega um payload de erro como resposta de sucesso — o interceptor de erro sempre transforma a resposta ≥ 400 em `throw new ApiError(...)`. Ver [08-API-INTEGRATION-CONTRACT.md](./08-API-INTEGRATION-CONTRACT.md) §2.

---

## 9. Convenções ULID

### 9.1 Definição

- **Tamanho fixo:** 26 caracteres.
- **Alfabeto:** Crockford Base32 `0-9A-HJKMNP-TV-Z` (exclui `I`, `L`, `O`, `U`).
- **Case-insensitive** em rota/validação, mas normalizar para uppercase na serialização.

### 9.2 `lib/ulid.ts`

```ts
// resources/spa/src/lib/ulid.ts

const ULID_REGEX = /^[0-9A-HJKMNP-TV-Z]{26}$/i;

export function isUlid(value: unknown): value is string {
    return typeof value === 'string' && ULID_REGEX.test(value);
}

export function assertUlid(value: unknown, fieldName = 'ulid'): string {
    if (!isUlid(value)) {
        throw new TypeError(`${fieldName} inválido: esperado ULID (26 chars Crockford).`);
    }
    return value.toUpperCase();
}

/**
 * Normaliza ULID para exibição em UI técnica (truncado).
 * "01J5K3B5GTYV8E2F1W0M8P2XQA" → "01J5K3…P2XQA"
 */
export function shortUlid(ulid: string, prefix = 6, suffix = 5): string {
    const u = assertUlid(ulid);
    return `${u.slice(0, prefix)}…${u.slice(-suffix)}`;
}

export function ulidsEqual(a: string, b: string): boolean {
    return a.toUpperCase() === b.toUpperCase();
}
```

### 9.3 Uso em TanStack Router

```ts
// resources/spa/src/routes/portal/pagamento/$parcela_ulid.tsx
import { createFileRoute, notFound } from '@tanstack/react-router';
import { isUlid } from '@/lib/ulid';

export const Route = createFileRoute('/portal/pagamento/$parcela_ulid')({
    parseParams: ({ parcela_ulid }) => {
        if (!isUlid(parcela_ulid)) throw notFound();
        return { parcela_ulid: parcela_ulid.toUpperCase() };
    },
    component: PagamentoPage,
});
```

### 9.4 Validação Zod

```ts
import { z } from 'zod';

export const ulidSchema = z
    .string()
    .length(26, { message: 'Deve ter 26 caracteres.' })
    .regex(/^[0-9A-HJKMNP-TV-Z]{26}$/i, { message: 'ULID inválido.' });
```

### 9.5 Política

- **Params de rota:** ULID sempre. Ex.: `/portal/pagamento/$parcela_ulid`, `/portal/mesas/reserva/$reserva_ulid`.
- **IDs numéricos internos:** **proibidos** em URL do cliente. A API nunca devolve `id: number` — sempre `id: string` (ULID).
- **Token público (RSVP):** `/rsvp/$token` usa token opaco (64 hex), não ULID. Validação: `/^[a-f0-9]{64}$/i`.
- **Rotas `me/*`:** não recebem ULID — escopo já é o usuário autenticado.

---

## 10. Helpers `lib/money.ts`, `lib/date.ts`, `lib/idempotency.ts`

### 10.1 `lib/money.ts` — tudo em centavos

```ts
// resources/spa/src/lib/money.ts

/**
 * Converte centavos em string BRL formatada.
 * 150099 → "R$ 1.500,99"
 */
export function formatBRL(centavos: number): string {
    if (!Number.isFinite(centavos) || !Number.isInteger(centavos)) {
        throw new TypeError('formatBRL: esperado int centavos.');
    }
    const reais = centavos / 100;
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(reais);
}

/**
 * Converte string BRL do input do usuário em centavos int.
 * "R$ 1.500,99" | "1500,99" | "1.500,99" → 150099
 */
export function parseBRL(input: string): number {
    const sanitized = input
        .replace(/[^\d,-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.');
    const n = Number.parseFloat(sanitized);
    if (!Number.isFinite(n)) throw new TypeError(`parseBRL: valor inválido "${input}".`);
    return Math.round(n * 100);
}

/**
 * Soma de valores em centavos evitando float drift.
 */
export function somaCentavos(...values: number[]): number {
    return values.reduce((acc, v) => acc + Math.round(v), 0);
}
```

**Uso em componente:**

```tsx
import { formatBRL } from '@/lib/money';
<Text>{formatBRL(parcela.valorCentavos)}</Text>; // "R$ 1.500,99"
```

**Uso em mutação:**

```ts
const payload = {
    valor_centavos: parseBRL(form.valor), // string → int
};
```

### 10.2 `lib/date.ts` — PT-BR + ISO

```ts
// resources/spa/src/lib/date.ts

const DATE_FMT = new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const DATETIME_FMT = new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

const TIME_FMT = new Intl.DateTimeFormat('pt-BR', {
    timeZone: 'America/Sao_Paulo',
    hour: '2-digit',
    minute: '2-digit',
});

function parseIso(iso: string): Date {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) throw new TypeError(`Data ISO inválida: "${iso}".`);
    return d;
}

export function formatDatePtBr(iso: string): string {
    return DATE_FMT.format(parseIso(iso));
}

export function formatDateTimePtBr(iso: string): string {
    return DATETIME_FMT.format(parseIso(iso)) + 'h';
}

export function formatTimePtBr(iso: string): string {
    return TIME_FMT.format(parseIso(iso));
}

/**
 * Diferença em dias entre agora e a data informada.
 * Positivo = no futuro; negativo = no passado.
 */
export function diffInDays(iso: string, now: Date = new Date()): number {
    const target = parseIso(iso);
    const ms = target.getTime() - now.getTime();
    return Math.floor(ms / (1000 * 60 * 60 * 24));
}

/**
 * "há 3 dias" | "em 2 dias" | "ontem" | "hoje"
 */
export function diffToNow(iso: string, now: Date = new Date()): string {
    const d = diffInDays(iso, now);
    if (d === 0) return 'hoje';
    if (d === -1) return 'ontem';
    if (d === 1) return 'amanhã';
    if (d < 0) return `há ${Math.abs(d)} dias`;
    return `em ${d} dias`;
}

/**
 * Segundos restantes até um instante ISO futuro (clamped ≥ 0).
 */
export function secondsUntil(iso: string, now: Date = new Date()): number {
    return Math.max(0, Math.floor((parseIso(iso).getTime() - now.getTime()) / 1000));
}

/**
 * Converte Date local PT-BR para ISO 8601 com offset de São Paulo.
 */
export function toIsoSaoPaulo(date: Date): string {
    const offsetMinutes = -3 * 60; // Brasil não tem mais DST (desde 2019)
    const offsetStr = '-03:00';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const h = String(date.getHours()).padStart(2, '0');
    const mi = String(date.getMinutes()).padStart(2, '0');
    const s = String(date.getSeconds()).padStart(2, '0');
    return `${y}-${m}-${d}T${h}:${mi}:${s}${offsetStr}`;
}
```

### 10.3 `lib/idempotency.ts` — sessionStorage + operation key

```ts
// resources/spa/src/lib/idempotency.ts

const STORAGE_PREFIX = 'idempotency:';

/**
 * Retorna (ou cria e persiste) a idempotency key para uma operação.
 * A key persiste em sessionStorage — sobrevive a reloads durante a aba.
 */
export function getIdempotencyKey(operation: string): string {
    const storageKey = STORAGE_PREFIX + operation;
    const existing = sessionStorage.getItem(storageKey);
    if (existing && isValidKey(existing)) return existing;
    const key = generateKey();
    sessionStorage.setItem(storageKey, key);
    return key;
}

/**
 * Remove a key após a operação concluir com sucesso (ex.: 201).
 */
export function clearIdempotencyKey(operation: string): void {
    sessionStorage.removeItem(STORAGE_PREFIX + operation);
}

/**
 * Limpa todas as keys de idempotência do sessionStorage.
 * Use em logout ou após cancelamento total.
 */
export function clearAllIdempotencyKeys(): void {
    for (const key of Object.keys(sessionStorage)) {
        if (key.startsWith(STORAGE_PREFIX)) sessionStorage.removeItem(key);
    }
}

/**
 * Gera uma UUID v4 via WebCrypto.
 */
function generateKey(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }
    // fallback (não deve ser atingido — Vite target inclui crypto.randomUUID)
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function isValidKey(key: string): boolean {
    return key.length > 0 && key.length <= 80 && /^[\x20-\x7E]+$/.test(key);
}
```

### 10.4 Uso em hook de mutação (pagamento)

```ts
// resources/spa/src/api/hooks/use-pagamento.ts (excerto)
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/api/client';
import { getIdempotencyKey, clearIdempotencyKey } from '@/lib/idempotency';
import type { MetodoPagamento, PagamentoDto } from '@/api/dto/pagamento';
import type { SingleEnvelope } from '@/api/types';

interface CriarIntentInput {
    origem_tipo: 'parcela' | 'pedido_extra';
    origem_ulid: string;
    metodo: MetodoPagamento;
}

export function useCriarPagamentoIntent() {
    const qc = useQueryClient();
    return useMutation({
        mutationKey: ['pagamentos', 'intent'],
        mutationFn: async (input: CriarIntentInput) => {
            const key = getIdempotencyKey(`pagamento:intent:${input.origem_ulid}`);
            const { data } = await api.post<SingleEnvelope<PagamentoDto>>('/pagamentos/intents', input, {
                headers: { 'X-Idempotency-Key': key },
            });
            return data.data;
        },
        onSuccess: (pagamento, input) => {
            clearIdempotencyKey(`pagamento:intent:${input.origem_ulid}`);
            qc.invalidateQueries({ queryKey: ['extrato'] });
            qc.setQueryData(['pagamento', pagamento.id], pagamento);
        },
    });
}
```

---

## 11. Codegen vs composição local

### 11.1 O que vem de `types.gen.ts` (codegen)

- **Todos os schemas OpenAPI** (`components.schemas.*`):
    - DTOs de request (`LoginRequest`, `ConviteCreateRequest`, `ReservaRequest`, `PagamentoIntentRequest`, `VotoRequest`, etc.).
    - DTOs de response (`FormandoMe`, `Evento`, `Adesao`, `Parcela`, `Convite`, `MapaMesas`, `ReservaAssento`, `Pagamento`, `Enquete`, `Voto`).
    - Envelopes (`CursorList<T>` construído manualmente a partir do genérico; o gerado entrega schemas concretos como `ConviteList`).
    - Error envelope (`ErrorResponse`).
    - **Enums** (`StatusAdesao`, `StatusReserva`, `StatusPagamento`, `TipoConvite`, etc.) — como union types string.
- **Paths tipados** (`paths.*.get.parameters`, `paths.*.post.requestBody`, `paths.*.post.responses.201.content.application/json`).

### 11.2 O que compõe localmente

- **ViewModels** (§4): todos os `XxxViewModel`.
- **Formatters** (`lib/money.ts`, `lib/date.ts`, `lib/ulid.ts`).
- **Labels PT-BR** (`STATUS_LABEL`, `TIPO_LABEL`, `METODO_LABEL`).
- **Cores/variantes** (`statusColor`).
- **Derivações** (`secondsRemaining`, `percentualPago`, `diasAteVencimento`, `disponivel`, `saldoLabel`).
- **Cálculos agregados** (`totalLivres`, `totalEmHold` por mesa).
- **FormModels Zod** (schemas por etapa, normalização de input).
- **Rotas derivadas** (construir `/rsvp/{token}` a partir do token retornado).

### 11.3 Tabela resumo

| Artefato                            | Origem                  | Rebuild quando                                   |
| ----------------------------------- | ----------------------- | ------------------------------------------------ |
| `types.gen.ts`                      | `openapi-skeleton.yaml` | CI regenera; commit manual após mudança contrato |
| Interfaces `*Dto`                   | codegen                 | idem                                             |
| Interfaces `*ViewModel`             | manual                  | Mudança de UX; mudança de label; novo cálculo    |
| Funções `toXxxViewModel`            | manual                  | Quando DTO ou ViewModel muda                     |
| `STATUS_*_LABEL` maps               | manual                  | Mudança de texto PT-BR                           |
| `formatBRL`, `formatDatePtBr`, etc. | manual                  | Praticamente imutáveis                           |
| Zod schemas de formulário           | manual                  | Mudança de regra de validação                    |

### 11.4 Política em CI

- `npm run types:check` executa `openapi-typescript` e compara com `types.gen.ts` commitado. Divergência → CI falha.
- `git hook` (lefthook/husky): gerar types antes de commit em MR que toca `openapi-skeleton.yaml`.
- Mappers DTO→ViewModel têm cobertura **≥ 95%** em Vitest (testes unitários por entidade).

---

## Apêndice A — Política de Zod para guarda runtime

### A.1 Quando usar Zod runtime parse

- **Opcional** em respostas da API: confiamos no OpenAPI + codegen.
- **Obrigatório** em:
    - Formulários (validação de input do usuário).
    - Parse de `sessionStorage`/`localStorage` (`wizard-store` restaurado).
    - Parse de query params da URL.
    - Payloads de WebSocket/SSE (se vierem em F5+).

### A.2 Exemplo — restaurar wizard store

```ts
import { z } from 'zod';
import { ulidSchema } from '@/lib/ulid';

const WizardFormSchema = z.object({
    currentStep: z.union([
        z.literal(1),
        z.literal(2),
        z.literal(3),
        z.literal(4),
        z.literal(5),
        z.literal(6),
        z.literal(7),
    ]),
    adesaoUlid: ulidSchema.nullable(),
    formData: z
        .object({
            cpf: z.string().optional(),
            // ...
        })
        .partial(),
});

export type WizardFormState = z.infer<typeof WizardFormSchema>;

export function restoreWizardState(raw: string | null): WizardFormState | null {
    if (!raw) return null;
    try {
        const parsed = WizardFormSchema.parse(JSON.parse(raw));
        return parsed;
    } catch {
        return null; // schema mudou, dados inválidos — recomeça wizard
    }
}
```

---

## Apêndice B — Pendências com backend

Lista de gaps identificados ao mapear UI → contratos. Cada item precisa ser endereçado antes de F3.

| #   | Item                                                                            | Severidade | Ação proposta                                                                         |
| --- | ------------------------------------------------------------------------------- | ---------- | ------------------------------------------------------------------------------------- |
| B1  | `GET /eventos/{ulid}/pacotes` — catálogo de pacotes para o wizard etapa 3       | bloqueador | Adicionar endpoint ao `openapi-skeleton.yaml` + contract                              |
| B2  | `POST /api/v1/adesoes` — commit da adesão ao fim do wizard                      | bloqueador | Especificar request/response, idempotência recomendada                                |
| B3  | `GET /api/v1/adesoes/simular` — cálculo de parcelamento (etapa 4)               | bloqueador | Especificar request/response                                                          |
| B4  | `token_publico` ou endpoint de token em `ConviteResource` para compartilhamento | alto       | Acrescentar `token_publico` **apenas** quando o usuário é o emissor                   |
| B5  | Campo `parcela.vencimento_at` no `ExtratoItemDto`                               | médio      | Hoje `data_movimento` reflete vencimento para `parcela_pendente`; confirmar semântica |
| B6  | Endpoint `GET /me/notificacoes` para central in-app (F6)                        | médio      | Adiar para F6 — não bloqueia F3/F4/F5                                                 |
| B7  | Cabeçalho `X-Correlation-Id` em responses de seating                            | baixo      | Já previsto em `api-conventions.md` §5                                                |
| B8  | `filter[evento_id]` em `/me/convites`                                           | baixo      | Conveniência p/ tela por evento                                                       |

Atualizar `api-CHANGELOG.md` a cada resolução e regenerar `types.gen.ts`.

---

## Referências

- [`PLANEJAMENTO_FRONTEND_REACT.md`](../prd/PLANEJAMENTO_FRONTEND_REACT.md) — documento-mestre do SPA.
- [`api-contract.md`](../api/api-contract.md) — especificação endpoint-por-endpoint.
- [`api-conventions.md`](../api/api-conventions.md) — convenções transversais.
- [`error-envelope.md`](../api/error-envelope.md) — handler global e mapa Throwable→HTTP.
- [`openapi-skeleton.yaml`](../api/openapi-skeleton.yaml) — schema formal.
- [`08-API-INTEGRATION-CONTRACT.md`](./08-API-INTEGRATION-CONTRACT.md) — contrato de integração (como este aqui é consumido em runtime).
- [`09-TECHNICAL-DESIGN-CRITICAL-MODULES.md`](./09-TECHNICAL-DESIGN-CRITICAL-MODULES.md) — como os ViewModels daqui alimentam cada módulo crítico.
