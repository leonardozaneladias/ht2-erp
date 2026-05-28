---
title: 'ADR-007: sessionStorage para X-Idempotency-Key e dados do wizard de adesão'
adr: 007
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - idempotencia
    - storage
    - wizard
    - lgpd
    - seguranca
---

# ADR-007: `sessionStorage` para `X-Idempotency-Key` e dados do wizard de adesão

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, idempotencia, storage, wizard, lgpd, seguranca

## 1. Contexto

O Portal ArtFinal tem dois tipos de estado client-side que precisam **sobreviver a reloads e navegações** mas **não** precisam persistir após o usuário fechar a aba:

1. **`X-Idempotency-Key` por operação crítica** (seating, pagamentos, lotes, trocas, extras, wizard final). ADR-0005 backend determina que mutações idempotentes carreguem esse header; se o usuário clica duas vezes "Confirmar pagamento" ou a rede cai e ele recarrega, a mesma chave evita double-effect no servidor.

2. **Dados do wizard de adesão (7 etapas)**. O formando preenche etapa 1 (dados pessoais), navega para etapa 2 (pacote), etapa 3 (extras), etc. Perder o que já foi preenchido ao recarregar a página (F5 acidental) é UX ruim.

O navegador oferece três opções principais de storage persistente client-side:

- `localStorage` — persiste entre sessões, indefinidamente. Acessível por JS.
- `sessionStorage` — persiste enquanto a aba está aberta. Acessível por JS.
- `IndexedDB` — banco NoSQL client. Acessível por JS.
- Cookies `HttpOnly` — controlados pelo servidor, não acessíveis a JS.

A escolha tem implicações de **segurança (LGPD)** e de **UX**.

## 2. Decisão

**Todo estado client-side descrito no §1 fica em `sessionStorage`, nunca em `localStorage`.** Especificamente:

- `X-Idempotency-Key` por operação: chave `idempotency:<operation>` em `sessionStorage`, valor = `crypto.randomUUID()`.
- Wizard de adesão: `wizardStore` Zustand com middleware `persist` configurado para `sessionStorage` (`name: 'wizard-storage'`).
- Dados temporários de formulário parcialmente preenchido em outras telas (ex.: extras parcialmente escolhidos): `sessionStorage` se precisar sobreviver a reload, caso contrário apenas React state.

**Nunca:**

- `localStorage` para **nada** além de preferências explícitas e não sensíveis (ex.: `theme: dark/light` — aceitável).
- `localStorage` para autenticação (cookies HttpOnly via Sanctum já cobrem, ADR-008).
- `localStorage` para dados pessoais (CPF, telefone, e-mail, valor financeiro).

Limpeza:

- `clearIdempotencyKey(operation)` após resposta de sucesso (201/200).
- `wizardStore.reset()` após conclusão bem-sucedida do wizard (POST /adesoes retornar 201).
- Nada mais: fechar aba limpa `sessionStorage` automaticamente (design do browser).

Fallback:

- Em modo privado de alguns navegadores (Safari), `sessionStorage` pode estar **indisponível**. `lib/idempotency.ts` detecta (`try/catch` + `localStorage` check) e cai para `Map` in-memory. Consequência: se usuário recarregar página na mesma sessão privada, perde a chave — aceitável para o MVP.

## 3. Consequências

### Positivas

- **LGPD-alinhado**: dados sensíveis do wizard (CPF, e-mail, telefone) **não persistem** após fechar a aba — reduzem superfície de exposição em máquinas compartilhadas (lan house, biblioteca, computador familiar).
- **Idempotência confiável**: mesmo F5 acidental no meio do checkout não gera double-charge; a chave sobrevive ao reload.
- **Zero tokens de auth em `sessionStorage`**: autenticação é cookie HttpOnly; SPA não guarda token JWT em nenhum storage acessível por JS (ADR-008).
- **Comportamento esperado pelo usuário**: "fechei a aba, perdi o progresso" é a expectativa natural; "fechei a aba, anos depois meu CPF ainda estava gravado" seria estranho e arriscado.
- **Simplicidade**: Zustand `persist` middleware + `lib/idempotency.ts` implementam tudo em ~30 linhas.

### Negativas

- **Perda de progresso em cenários específicos**: se o navegador crasha antes de chegar ao POST final, o wizard é perdido. Aceitável (caso raro) vs. risco LGPD de `localStorage`.
- **sessionStorage não é compartilhado entre abas**: se o usuário abre o wizard em duas abas, cada uma tem estado próprio. No fluxo wizard isso é **desejável** (evita confusão), mas se for problema, duas abas **podem** submeter com chaves idempotency diferentes. Em seating/pagamento, a chave é por operação+recurso (ex.: `pagamento:01J...`), então a dedupe backend ainda funciona; em wizard, a última submetida vence — aceitável.
- **Modo privado sem sessionStorage**: perde idempotência. Aceitável para minoria de usuários em máquinas atípicas.
- **Sem sync entre dispositivos**: formando que começa no celular e muda para desktop perde o progresso. Para o MVP, fluxo "começou, termine no mesmo device" é a norma; mobile F8 terá estratégia própria.

## 4. Trade-offs

| Ganhamos                                           | Perdemos                                             |
| -------------------------------------------------- | ---------------------------------------------------- |
| LGPD-safe (dados limpos ao fechar aba)             | Crash de navegador no meio do wizard perde progresso |
| Idempotência sobrevive a reload acidental          | Cross-tab e cross-device não suportados              |
| Zero surface para XSS roubar CPF/e-mail de storage | Modo privado raro pode degradar idempotência         |
| Implementação trivial (~30 linhas)                 | Usuário precisa terminar wizard na mesma sessão      |
| Comportamento intuitivo (fechar aba = recomeçar)   | Sem "rascunho" de wizard para retomar depois         |

## 5. Alternativas rejeitadas

### Alt 1: `localStorage` para wizard e idempotência

- **Prós**: persiste entre sessões; usuário pode fechar aba e voltar dias depois.
- **Contras**:
    - **Risco LGPD**: CPF, e-mail, telefone ficam no disco do usuário por tempo indeterminado. Em máquina compartilhada, outro usuário do mesmo navegador pode acessar via DevTools.
    - **Chaves de idempotência obsoletas**: se usuário deixa uma chave de `pagamento:01J...` por 30 dias e volta, a chave ainda existe mas o estado do servidor mudou. Pode causar confusão no backend (chave cacheada já expirou, mas ainda está no localStorage).
    - **Contra recomendação OWASP**: para dados sensíveis, `sessionStorage` é preferido a `localStorage`.

### Alt 2: Backend rascunho persistido (endpoint `POST /adesoes/rascunhos`)

- **Prós**: user-friendly ("salve e continue depois"); cross-device; sem storage local.
- **Contras**:
    - **Esforço backend extra**: tabela `rascunhos`, expurgo, lógica de retomada.
    - **Fluxo típico é single-sitting**: pesquisa mostra que > 90% dos formandos concluem adesão em 15 min.
    - **Fora do escopo MVP**: pode ser feature pós-F7 (aprimoramento).

### Alt 3: IndexedDB

- **Prós**: mais espaço, transacional, estruturado.
- **Contras**:
    - **Overkill para nosso volume**: wizard é ~2 KB; idempotency keys são ~40 bytes cada.
    - **API assíncrona complicada**: requer wrapper (Dexie, idb); adiciona dep.
    - **Mesmo risco de persistência longa** que `localStorage`.

### Alt 4: Cookies HttpOnly para idempotency key

- **Prós**: não acessível por JS (XSS-safe).
- **Contras**:
    - **Cookie precisa ser escrito pelo servidor**: tardio para o SPA gerar chave unicamente no cliente.
    - **Tamanho limitado**: cookies são ~4 KB total — wizard não cabe.
    - **Compartilhado com toda origem**: complexidade para segmentar por operação.

### Alt 5: Zero persistência (só React state)

- **Prós**: simplicidade absoluta; zero storage.
- **Contras**:
    - **Perde progresso em qualquer reload**: UX inaceitável para wizard de 7 etapas.
    - **Idempotência quebrada em reload**: F5 dispara nova chave; backend pode receber double-call.

## 6. Status

**Accepted.** Congelada até evidência de UX problemática.

Evoluções previstas (sem mudar ADR):

- **F7**: telemetria para medir quantos usuários abandonam o wizard e voltam em < 24 h. Se > 10%, priorizar "rascunho no servidor" como feature.
- **F8 mobile**: chave de idempotência em `expo-secure-store` (análogo a `sessionStorage` em termos de escopo por sessão de app).

Checklist operacional:

- [ ] `lib/idempotency.ts` implementado com detecção de sessionStorage indisponível.
- [ ] `wizardStore` usa `persist({ storage: createJSONStorage(() => sessionStorage) })`.
- [ ] Nenhum hook usa `localStorage` exceto `themeStore` (dark/light preference).
- [ ] README do SPA documenta regra "dados sensíveis → sessionStorage".
- [ ] Code review bloqueia PR que usa `localStorage` fora da exceção.

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §0 item 7, §6 (wizard-store), §7 (Idempotência)
- `docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §2.9, §5.1 (idempotência backend)
- `docs/architecture/adrs/ADR-0005-idempotencia-3-camadas.md` — idempotência no backend
- `docs/frontend/05-FRONTEND-SAD.md` §8.5, §11 R6, R8
- ADR-001 (SPA React), ADR-004 (Zustand persist), ADR-006 (seating idempotente), ADR-008 (Sanctum cookie, não JWT em storage)
