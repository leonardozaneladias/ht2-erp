---
title: 'ADR-003: Tamagui v2 como design system único (web + React Native F8)'
adr: 003
date: 2026-04-18
status: accepted
deciders:
    - leonardo
    - gustavo
tags:
    - frontend
    - design-system
    - tamagui
    - cross-platform
    - mobile
---

# ADR-003: Tamagui v2 como design system único (web + React Native F8)

**Status:** Accepted | **Data:** 2026-04-18 | **Decisores:** Leonardo, Gustavo | **Tags:** frontend, design-system, tamagui, cross-platform, mobile

## 1. Contexto

O planejamento frontend (§9) e o roadmap (F8) definem que o Portal ArtFinal terá, além do SPA web, um **app mobile React Native + Expo SDK 53**. O requisito contratual é que **o máximo possível de código de UI seja reutilizado** entre web e mobile — idealmente, hooks de API, stores e componentes visuais de médio nível.

A camada de apresentação precisa resolver simultaneamente:

- Primitivos visuais tipados (Button, Input, Sheet, Stack, Text).
- Sistema de tokens de design (cores, espaçamento, tipografia) coerente.
- Dark mode fácil de configurar.
- Responsividade por breakpoints (web) **e** por orientação (mobile).
- Compatibilidade tanto com DOM (web) quanto com `react-native` (RN).

A decisão tradicional no ecossistema React web — **shadcn/ui** — é excelente para web, mas é construída sobre Radix Primitives, que são DOM-only. Reutilizar shadcn no React Native é inviável sem reescrever cada primitivo.

Estamos em abril/2026 e Tamagui v2 (lançado em 2024-2025) amadureceu para ser o **principal concorrente cross-platform** nesse nicho. A alternativa é manter dois design systems paralelos (shadcn no web + RN Elements / Tamagui só no mobile), o que duplica esforço e gera divergência visual.

## 2. Decisão

**Tamagui v2 é o design system único do Portal ArtFinal em todas as plataformas client:** SPA web (F3-F7) e app React Native Expo (F8).

Detalhes operacionais:

- **Primitivos Tamagui** (`View`, `Text`, `Stack`, `XStack`, `YStack`, `Button`, `Input`, `Sheet`, `Card`) são a fundação. Usamos apenas primitivos — nenhum componente de alto nível (Tamagui não tem).
- **Wrappers locais** em `components/ui/`: cada primitivo Tamagui vira um componente local que aplica defaults do produto (labels PT-BR, variantes, defaults de acessibilidade). Ex.: `<AppButton>` wrappa `<Button>` do Tamagui com `size="$4"` padrão e `accessibilityLabel` obrigatório.
- **Tokens**: `tamagui.config.ts` define tokens de cor, espaçamento, tipografia e radius. Tokens **mapeados para as variáveis CSS do Inspinia (admin)** para coerência visual mínima entre admin e portal.
- **Dark mode**: built-in do Tamagui (`themes: { light, dark }`) + `useThemeName()` no app.
- **Tailwind + Tamagui convivem?** Apenas para layouts rápidos e utilitários que Tamagui não cobre (grid CSS, utilitários one-off). Em componentes cross-platform, Tamagui vence.
- **shadcn/ui proibido** no portal. Radix Primitives também (Radix é DOM-only).

## 3. Consequências

### Positivas

- **Reuso F8 real**: estima-se que 60-80% dos componentes de UI web serão importados no app RN sem alteração estrutural — apenas substituição de navegação (TanStack Router → Expo Router) e de storage (cookie → SecureStore).
- **Tokens consistentes**: a mesma paleta de cor e escala de espaçamento entre admin e portal reduz atrito visual para equipe comercial que transita entre os dois.
- **Dark mode grátis** em ambos os canais.
- **TypeScript profundo**: Tamagui tem tipos para cada token (`<Button size="$3">` é autocompletado e checado).
- **Zero-runtime compile** (opcional em F7): Tamagui pode compilar JSX para CSS estático, reduzindo bundle e latência.
- **Primitivos composáveis** reduzem a necessidade de bibliotecas específicas (ex.: `<Sheet>` substitui 80% dos casos de Modal/Drawer).

### Negativas

- **Maturidade em React 19**: Tamagui v2 funciona em React 19 mas bugs transitórios aparecem em transições de tema e em SSR. **Risco monitorado** (§SAD §11 R1). Mitigação: pin de versão minor, fallback documentado para Radix caso um primitivo específico quebre.
- **Curva de aprendizado**: Tamagui tem API própria (`styled`, tokens `$`) distinta de Tailwind ou CSS-in-JS comuns.
- **Comunidade menor** que shadcn (30k stars vs 70k em abril/2026). Ecossistema de exemplos menor.
- **Bundle inicial** ~40 KB gzip (runtime) contra ~15 KB de Radix. Mitigação: `@tamagui/vite-plugin` em F7 para zero-runtime extraction.
- **Debug visual**: não há "DevTools Tamagui". Inspecionar estilos fica via DOM inspector + console — similar a styled-components.

## 4. Trade-offs

| Ganhamos                                                   | Perdemos                                                          |
| ---------------------------------------------------------- | ----------------------------------------------------------------- |
| DS único web + RN (F8 reuso real)                          | Maturidade shadcn/Radix (5 anos de comunidade vs 2 de Tamagui)    |
| Tokens unificados, dark mode built-in                      | Curva de aprendizado inicial (~2 dias por dev)                    |
| Tipagem de tokens (`$space-3`) no IDE                      | Bundle ~40 KB maior até F7 (compilador liga)                      |
| Primitivos composáveis reduzem libs externas               | Dependência de um DS ainda em estabilização                       |
| Coerência visual admin ↔ portal via tokens compartilhados | Impossível usar shadcn (exclusivo) se quisermos manter cross-plat |

## 5. Alternativas rejeitadas

### Alt 1: shadcn/ui + Radix Primitives

- **Prós**: padrão de facto em 2025-2026 para web React; 70k stars; ecossistema massivo; Copy&Paste de componentes prontos.
- **Contras**:
    - **Web only**: Radix Primitives é DOM. React Native não consome nada disso.
    - **Reuso mobile zero**: teríamos que escrever **tudo de novo** em RN Elements ou similar.
    - **Dois DS paralelos** divergem em meses — cor primária no web não bate com cor primária no mobile sem disciplina de tokens via JSON.
- **Veredicto**: excelente se F8 não existisse. Com F8 contratual, perde por não ser cross-platform.

### Alt 2: Chakra UI v3

- **Prós**: API madura, tokens built-in, ecossistema robusto.
- **Contras**:
    - **Web only** (não tem suporte oficial para RN).
    - **Bundle pesado** (~65 KB gzip base).
    - **Emotion-based** runtime — mais lento que Tamagui compilado.

### Alt 3: Material UI (MUI)

- **Prós**: pela mais madura (since 2014), enterprise-grade, acessibilidade forte.
- **Contras**:
    - **Web only**.
    - **Estética Material** pouco alinhada com identidade de "evento de formatura" (mais ousada, editorial).
    - **Bundle pesado** (~90 KB gzip base).

### Alt 4: NativeBase v3

- **Prós**: cross-platform (web + RN); foi o principal concorrente de Tamagui até 2024.
- **Contras**:
    - **Maintenance mode**: Nativebase v4 foi descontinuado em 2024; equipe migrou para Gluestack. Usar NB v3 seria adotar lib sem futuro.
    - **Performance inferior** a Tamagui em benchmarks (2024): 2-3× mais slow em renders complexos.

### Alt 5: Gluestack UI

- **Prós**: sucessor espiritual do NativeBase; cross-platform; tokens; Tailwind-like.
- **Contras**:
    - **Mais jovem que Tamagui** (lançou fim 2024).
    - **Comunidade menor** (~5k stars em abril/2026).
    - **Performance**: similar a Tamagui, sem vantagem clara.
    - **Decisão de tempo**: Tamagui já está escolhido no planejamento frontend §1; Gluestack seria mudança retroativa.

### Alt 6: Design system próprio (do zero)

- **Prós**: controle total, zero deps.
- **Contras**:
    - **Custo proibitivo**: primitivos cross-platform bem feitos (acessibilidade, tokens, dark mode, keyboard nav) são 3-6 meses de um time dedicado.
    - **Reinventar a roda**: o que Tamagui resolve em 2 horas (instalar + configurar) levaria semanas.

## 6. Status

**Accepted.** Congelada para F3-F7. Em F7 revisar:

- Se Tamagui v2 tiver bugs bloqueantes em React 19, avaliar:
    - (a) manter Tamagui e aplicar workarounds;
    - (b) migrar para Gluestack (mesmo perfil, mais jovem);
    - (c) separar DS: Tamagui no mobile F8, shadcn no web (abandonando reuso cross-platform — decisão de último recurso).

Critério de sucesso em F7: ≥ 70% dos componentes `components/ui/` importáveis no app RN sem alteração estrutural (F8 valida).

## Ligações

- `docs/prd/PLANEJAMENTO_FRONTEND_REACT.md` §1 (stack), §9 (Design System)
- `docs/frontend/05-FRONTEND-SAD.md` §1.2 FG1, §4.2, §11 R1, R4
- `docs/INSPINIA-CATALOGO-COMPONENTES.md` — tokens de referência (admin)
- ADR-001 (SPA React puro), ADR-004 (stack TanStack)
