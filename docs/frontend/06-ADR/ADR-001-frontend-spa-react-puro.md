---
title: 'ADR-001: Portal é SPA React 19 puro (sem Blade/Livewire no portal)'
adr: 001
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - spa
    - react
    - arquitetura
    - portal
---

# ADR-001: Portal é SPA React 19 puro (sem Blade/Livewire no portal)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, spa, react, arquitetura, portal

## 1. Contexto

O Portal ArtFinal v2 tem duas faces distintas:

- **Admin (backoffice)** — usado por equipe comercial e operacional. Já consolidado em **Blade + Livewire 3 + Inspinia/Tailwind 4**, com desempenho e DX adequados ao perfil operacional.
- **Portal (formando e convidado)** — usado por milhares de formandos e convidados, com fluxos críticos (wizard de adesão 7 etapas, seating com hold, pagamento, RSVP público). Requisito contratual de **F8 mobile** (React Native + Expo) exige reaproveitamento máximo de código de camada de apresentação.

A questão é: **qual tecnologia serve o portal?** Livewire é a escolha "caminho do menor esforço" dentro do ecossistema Laravel, mas tem implicações fortes para mobile e para a separação cliente ↔ servidor.

O planejamento backend (§0 item 2) e o planejamento frontend (§0 itens 1-2) estabelecem que o portal deve consumir exclusivamente `api/v1` — um contrato que serve web e mobile. Se o portal usar Livewire, ele consumiria o banco e serviços diretamente via controllers Livewire, quebrando essa premissa.

## 2. Decisão

**O Portal é um SPA React 19 puro, servido pelo Laravel apenas como shell `resources/views/spa.blade.php`.** Nenhuma view Blade do portal é permitida além desse shell. Toda interação com o backend ocorre via `/api/v1` (com Sanctum stateful cookie). A arquitetura é idêntica, em termos de contrato, à que o app mobile usará em F8.

Consequências operacionais:

- **Roteamento**: TanStack Router v1 file-based, com 11 rotas (`docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §5).
- **Servir o shell**: catch-all em `routes/portal.php` que captura qualquer URL que não comece com `api`, `sanctum`, `admin`, `horizon`, `pulse` e renderiza `spa.blade.php`.
- **Build**: Vite independente em `resources/spa/vite.config.ts`, output em `public/spa/`.
- **Admin fora do escopo**: o admin permanece Blade + Livewire; não há unificação de template nem de camada visual entre admin e portal.

## 3. Consequências

### Positivas

- **Reuso mobile garantido**: hooks, stores e componentes Tamagui são idênticos em F8 (React Native).
- **Contrato único** (`api/v1`) serve web, mobile e futuros clientes; zero duplicação de endpoints específicos para SPA (ADR-002).
- **Independência de ciclo de vida**: o SPA evolui e faz deploy de assets independente do backend (mesmo pipeline, mas bundles separados).
- **Testabilidade desacoplada**: SPA testável isoladamente com Vitest + RTL + MSW; backend testável com Pest.
- **DX previsível para perfil de dev React**: contratação de talento frontend é mais ampla que "Laravel + Livewire full-stack".
- **Alinhamento com ADR-0001 backend** (API-first) e ADR-0002 backend (monólito modular): o monólito expõe uma única superfície HTTP para qualquer cliente.

### Negativas

- **Duas stacks de UI no mesmo repositório**: admin é Blade/Livewire, portal é React. Manutenção pede duas expertises.
- **Sem renderização server-side do portal**: initial paint depende de JS. Mitigação: shell `spa.blade.php` minimalista (< 5 KB), skeleton UI nas rotas críticas, Vite code-splitting em F7.
- **CSRF-cookie requer primeira requisição extra** (`GET /sanctum/csrf-cookie`) antes de toda mutação — latência adicional ~50 ms em dev, aceitável. Interceptor Axios automatiza (§SAD §6.1).
- **Debug ponta-a-ponta** pede duas DevTools (Laravel Debugbar + React DevTools). Mitigação: `X-Request-Id` em todo header (§SAD §8.4) para correlação.

## 4. Trade-offs

| Ganhamos                                                                | Perdemos                                                         |
| ----------------------------------------------------------------------- | ---------------------------------------------------------------- |
| Reuso 100% com mobile em F8                                             | Simplicidade de "full-stack Laravel" — agora são duas stacks     |
| Contrato HTTP estável reusável por integrações futuras                  | SSR grátis que o Livewire daria (mitigado com shell + skeletons) |
| Separação forte frontend ↔ backend (tests independentes)               | Primeira request extra para CSRF em dev                          |
| Ecosistema React/TypeScript com DevEx superior em formulários complexos | Curva de aprendizado para devs Laravel puros                     |
| Possibilidade de substituir React no futuro sem impacto no backend      | Mais arquivos, mais build steps, mais pipeline                   |

## 5. Alternativas rejeitadas

### Alt 1: Livewire 3 para o portal

- **Prós**: stack única (Laravel), menor curva, componentes Blade/Livewire já usados no admin.
- **Contras**:
    - **Impossibilita mobile F8**: Livewire assume browser + DOM + cookie de sessão web; React Native não consome Livewire.
    - **Acopla o portal ao ciclo de release do backend**: toda mudança de UI requer deploy Laravel.
    - **Wizard de 7 etapas em Livewire** força estado no servidor a cada tick → latência perceptível e carga extra em Redis.
    - **Seating com polling 5s**: Livewire `wire:poll` funciona, mas compete com outros wire:poll globais e sobrecarrega o servidor.
    - **Duplicação de contrato**: backend precisaria expor controllers Livewire **e** `api/v1` para mobile — dobra a superfície de manutenção.

### Alt 2: Inertia.js + React

- **Prós**: integração Laravel ↔ React; mantém rotas server-side; muitos projetos usam.
- **Contras**:
    - **Não serve mobile**: Inertia é protocolo HTTP específico, diferente de REST puro. Mobile F8 teria que consumir API REST separada → duplicação.
    - **Atrelado ao Laravel**: migração de backend (improvável mas possível) seria dolorosa.
    - **SSR parcial quebra o mental model** quando o Portal precisa ser uma SPA com state client-side complexo (hold timer, wizard persist, polling).
    - **Contradiz ADR-0001 backend** (API-first desde dia 1).

### Alt 3: SPA React mas com API "interna" diferente de `api/v1`

- **Prós**: permitiria endpoints otimizados para o SPA (ex.: bulk queries, resposta moldada para telas).
- **Contras**:
    - **Duplica contrato**: mobile teria que consumir `api/v1` e SPA teria endpoints próprios — duas fontes de verdade de domínio, divergem com o tempo.
    - **Viola ADR-002 frontend** (API-first exclusiva).
    - **Esforço extra** sem benefício claro: `api/v1` com sparse fields (`$this->when(...)`) em Resources cobre 100% dos casos.

### Alt 4: Next.js (RSC + SSR)

- **Prós**: SSR grátis, SEO, code-splitting automático, ecossistema gigante.
- **Contras**:
    - **Portal formando não precisa de SEO** (é área autenticada).
    - **Custo operacional**: Next.js standalone exige processo Node.js separado, deploy separado, proxy reverso.
    - **Complexidade**: RSC + server actions + data fetching de três formas competem com TanStack Query, que escolhemos para o Portal.
    - **Não resolve mobile**: ainda precisaríamos de React Native em F8.

### Alt 5: Vue 3 + Inertia / Nuxt

- **Prós**: comunidade Laravel cresceu com Vue.
- **Contras**:
    - **Cross-platform inferior**: Vue Native morreu; `NativeScript-Vue` é marginal. React Native é padrão de fato para mobile cross-platform.
    - **Ecosistema TS em Vue** é mais jovem e menos rígido que em React.
    - **Time não tem expertise Vue** relevante; escolher Vue geraria curva desnecessária.

## 6. Status

**Accepted.** Decisão congelada até F8 inclusive. Revisão apenas se:

- Tamagui v2 provar-se inviável em React 19 **e** não houver alternativa cross-platform (cenário remoto).
- Requisito de F8 (mobile) for descontinuado por produto.

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §0 (princípios não negociáveis), §2 (estrutura), §11 (backend prerequisites)
- `docs/architecture/SAD-arc42.md` §1.1 (visão geral do produto)
- `docs/frontend/05-FRONTEND-SAD.md` §1, §3, §5, §6
- ADR-002 (API-first exclusiva), ADR-003 (Tamagui cross-platform), ADR-004 (stack TanStack), ADR-008 (Sanctum dual-mode)
- Backend: ADR-0001 (API-first `api/v1`), ADR-0002 (monólito modular), ADR-0003 (Sanctum dual-mode)
