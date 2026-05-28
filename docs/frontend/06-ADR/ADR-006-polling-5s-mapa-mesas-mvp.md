---
title: 'ADR-006: Polling 5s no mapa de mesas (MVP) — sem WebSocket'
adr: 006
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - seating
    - realtime
    - performance
    - mvp
---

# ADR-006: Polling 5s no mapa de mesas (MVP) — sem WebSocket/Reverb

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, seating, realtime, performance, mvp

## 1. Contexto

O mapa de mesas (F5, `/portal/mesas`) é a funcionalidade de maior concorrência do Portal ArtFinal: vários formandos podem estar olhando o mesmo layout de evento e tentando segurar mesas simultaneamente. O backend implementa **hold pessimista com `hold_expires_at` (5 min default)** + locking de linha (ADR-0006 backend + technical design seating). Isso garante **zero double-booking** no servidor.

Porém, do lado do SPA, a pergunta é: **como o usuário que está olhando o mapa sabe que uma mesa específica acabou de ser segurada por outro usuário?**

Opções:

1. **Polling de leitura periódica** (TanStack Query `refetchInterval`).
2. **WebSocket / Server-Sent Events** (Laravel Reverb publicando eventos `MesaSegurada`, `MesaLiberada`, `MesaConfirmada`).
3. **Notificação push nativa** (Web Push API, mas o navegador precisa estar com aba ativa de qualquer forma).

O MVP (F3-F6) precisa entregar seating funcional em poucas sprints. A equipe é pequena. Qualquer infra realtime adiciona:

- Configuração de Reverb (Laravel WebSocket server) ou broker.
- Autenticação de canal privado via Sanctum.
- Reconnection logic no cliente.
- Fallback para polling em redes instáveis.
- Monitoramento adicional (Horizon não cobre WebSocket).

## 2. Decisão

**No MVP, o mapa de mesas usa polling HTTP a cada 5 segundos enquanto a tela está visível, via TanStack Query `refetchInterval: 5000`.** WebSocket/Reverb é **explicitamente adiado para F7+**, e só entrará se as métricas de carga justificarem.

Detalhes operacionais:

- **Hook**: `use-seating.ts` define `useMesas(eventoUlid)` com:
    ```typescript
    useQuery({
        queryKey: queryKeys.mesas(eventoUlid),
        queryFn: () => api.get(`/eventos/${eventoUlid}/mesas`).then((r) => r.data),
        refetchInterval: hasActiveHold ? 5000 : 30000,
        staleTime: 0,
    });
    ```
- **Polling só enquanto há hold ativo** (usuário dentro do fluxo de reserva). Se nenhum hold, polling relaxado para 30 s ou pausado.
- **Pausa quando tab inativo**: TanStack Query tem `refetchOnWindowFocus` que retoma; polling para quando aba sai de foco.
- **Reconciliação de hold timer**: a cada resposta do polling, `holdStore` compara `hold_expires_at` retornado com o que está local. Se servidor marca hold expirado antes do cliente, cliente limpa imediatamente.
- **Backpressure**: se resposta > 2 s ou erro 5xx consecutivo, Query faz backoff exponencial (1 retry, depois 10 s, depois 30 s).

## 3. Consequências

### Positivas

- **Simplicidade operacional radical**: zero infra nova. Sem Reverb, sem Redis pub/sub custom, sem WebSocket reverse proxy em Nginx.
- **TanStack Query nativo** já provê `refetchInterval`, retries, backoff, pause-on-hidden.
- **Latência aceitável para o caso de uso**: hold é 5 min; atualização a cada 5 s significa pior caso de ~5 s de dessincronização visual. UX mostra "Mesa já reservada por outro usuário" se tentar segurar mesa ocupada.
- **Menos estado para debugar**: polling é stateless-ish. WebSocket tem estado de conexão, reconexão, authorization, channel subscription — mais superfície de bug.
- **Alinhado com FG4/FG5 do SAD** — primeira entrega "boring and stable".
- **Compatível com mobile F8**: polling funciona em redes móveis instáveis mais graciosamente que WebSocket persistente (que drena bateria).

### Negativas

- **Carga no backend**: com 200 formandos simultâneos no mapa, são ~40 req/s de `GET /eventos/:ulid/mesas`. Em pior pico, 200 req/s. O controller precisa ser rápido (< 100 ms) e cacheável (Redis com TTL 2 s).
- **Latência visual**: outro usuário segura uma mesa; polling seu só captura ~2.5 s depois. Durante esse intervalo, você pode **tentar** clicar na mesa e receber 409 Conflict do servidor. UX precisa de toast claro "Mesa acabou de ser reservada — escolha outra".
- **Não é "realtime" de verdade**: se produto/stakeholders esperam animação "mesa piscando" ao vivo, polling de 5 s **não** entrega.
- **Potencial de thundering herd**: se 500 formandos abrem mapa às 20:00 para evento popular, são picos coordenados a cada 5 s. Mitigação: jitter no `refetchInterval` (+-500 ms random) em F7.

## 4. Trade-offs

| Ganhamos                                             | Perdemos                                                 |
| ---------------------------------------------------- | -------------------------------------------------------- |
| Zero infra realtime no MVP                           | Latência de atualização ~5 s (não é "ao vivo")           |
| Sem deploy de WebSocket, sem config de canal privado | UX ocasional de "mesa sumiu" — precisa toast bem escrito |
| Robustez em redes instáveis (HTTP > WS em 3G)        | Backend precisa rota performática e cacheada             |
| TanStack Query cobre tudo (retry, pause, refetch)    | Produto precisa aceitar a latência de 5 s no MVP         |
| Trivial de debugar (DevTools Network)                | Scale em picos coordenados exige jitter em F7            |

## 5. Alternativas rejeitadas

### Alt 1: Laravel Reverb + WebSocket

- **Prós**: realtime verdadeiro (< 100 ms); menos carga no servidor a longo prazo (push > pull).
- **Contras**:
    - **Infra nova**: Reverb precisa processo WebSocket separado, configuração Nginx para proxy `ws://`, TLS em prod.
    - **Estado de conexão**: reconnection, heartbeat, authorization de canal privado por Sanctum.
    - **Complexidade mobile F8**: Expo RN precisa `expo-websockets` ou `pusher-js`; conexão WS em redes 3G é menos robusta.
    - **Tempo de desenvolvimento**: estima-se 3-5 dias adicionais de dev + testes para integrar Reverb no seating — não cabe na janela do MVP.
- **Revisão em F7+**: se métricas de F6 mostrarem picos de carga ou latência inaceitável, este é o caminho.

### Alt 2: Server-Sent Events (SSE)

- **Prós**: unidirecional server→client, mais simples que WS, HTTP regular.
- **Contras**:
    - **Não padrão em Laravel**: sem suporte first-class, exige endpoint custom + long-running PHP process.
    - **Proxy Nginx precisa buffer off**: ajuste sensível.
    - **Não resolve** muito mais que polling adaptativo — e polling tem melhor DX no TanStack Query.

### Alt 3: Polling muito curto (1 s)

- **Prós**: quase realtime; baixa latência.
- **Contras**:
    - **Carga 5× maior**: inviável em picos.
    - **Ruído em DevTools e logs**: 1 req/s por usuário é spam.
    - **Bateria mobile**: 1 req/s drena mais que WebSocket idle.

### Alt 4: Polling adaptativo por ETag / 304 Not Modified

- **Prós**: mesmo polling, mas `GET` com `If-None-Match` evita payload quando nada mudou.
- **Contras**:
    - **Backend precisa gerar ETag** estável para o bundle de mesas do evento. Custo inicial.
    - **Não elimina carga de round-trip** — só payload. A rota ainda é chamada 40 req/s.
- **Revisão em F7**: implementar ETag no endpoint `GET /eventos/:ulid/mesas` é otimização evolutiva sem mudar contrato — **vale a pena** mesmo que polling permaneça.

### Alt 5: WebSocket em F3 (build it right)

- **Prós**: "fazer certo desde o começo"; evitar retrabalho.
- **Contras**:
    - **Contradiz disciplina de MVP**: F3 precisa entregar login + wizard + dashboard + financeiro + pagamento. Acrescentar Reverb atrasa seating.
    - **Decisão prematura**: sem dados de carga real, podemos estar otimizando para problema inexistente.

## 6. Status

**Accepted.** Revisão obrigatória em **F7** com base em:

1. **Carga medida**: req/s em `GET /eventos/:ulid/mesas` no pico.
2. **Latência percebida**: tempo médio entre "outro usuário segura mesa" e "eu vejo mesa indisponível" (instrumentar com `X-Request-Id` + timestamps).
3. **UX feedback**: se usuários relatam confusão com "mesa sumiu", priorizar WS.
4. **Custo de infra**: Reverb em prod (processo, memória, TLS, monitoring).

Se dois dos quatro critérios acima justificarem, abrir ADR-009 para migração a Reverb.

### Otimização incremental dentro do MVP (sem mudar decisão)

- [ ] F5: implementar polling conforme especificado.
- [ ] F6: adicionar jitter random ±500 ms para evitar thundering herd.
- [ ] F6: backend cachear resposta de `GET /eventos/:ulid/mesas` em Redis com TTL 2 s.
- [ ] F7: implementar ETag no endpoint para responder 304 quando sem mudança.

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §10 (Mapa de Mesas)
- `docs/architecture/technical-design-seating.md` — design backend do seating
- `docs/architecture/adrs/ADR-0006-concorrencia-seating.md` — hold pessimista backend
- `docs/frontend/05-FRONTEND-SAD.md` §6.4 (runtime seating), §11 R2
- ADR-004 (TanStack Query), ADR-007 (idempotência)
