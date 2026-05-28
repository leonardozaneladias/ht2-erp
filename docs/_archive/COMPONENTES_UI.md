# Componentes UI e Stack de Frontend

## 1. Objetivo deste documento

Este documento consolida:

- a leitura do estado atual de componentização do admin
- a estratégia de design system para web React e mobile React Native
- a comparação de UI kits cross-platform
- a recomendação final de stack frontend

## 2. Estado atual do repositório

### 2.1 Admin Inspinia

O projeto já possui uma base valiosa para o backoffice:

- catálogo oficial em `docs/INSPINIA-CATALOGO-COMPONENTES.md`
- mapa tela → componente em `docs/INSPINIA-MAPA-TELAS-COMPONENTES.md`
- previews em `resources/views/admin/dev/components`
- componentes reais em `resources/views/components/admin` e `resources/views/components/shared`

Pontos fortes:

- layout, navegação, feedback, formulários, tabelas e charts já têm direção consolidada
- o esforço de descoberta do template Inspinia já foi feito
- há uma API visual Blade consistente em `x-admin.*` e `x-shared.*`

Pontos fracos:

- a trilha do portal ainda é majoritariamente planejada, não implementada
- não existe catálogo pronto para React web/mobile
- o design system do cliente não deve ser improvisado sobre o admin

### 2.2 Conclusão prática

O admin deve ser preservado e evoluído sobre a base Inspinia existente.  
O cliente web/mobile deve ganhar um design system próprio, compartilhável, sem forçar reescrita do backoffice.

## 3. Estratégia de componentização

### 3.1 Camadas

#### Camada A — Tokens compartilhados

- paleta semântica
- tipografia
- radius
- espaçamento
- grid
- elevação
- ícones
- estados de feedback

#### Camada B — Primitivos compartilhados web/mobile

- button
- input
- select
- checkbox
- radio
- card
- badge
- divider
- modal/sheet
- toast

#### Camada C — Componentes de domínio

- invite-card
- invite-status-chip
- rsvp-timeline
- seat-chip
- table-map
- poll-card
- payment-summary
- extra-product-card

#### Camada D — Admin-only

- `x-admin.data-table`
- `x-admin.drawer`
- `x-admin.kpi-card`
- `x-admin.chart-*`
- `x-admin.sidebar`
- `x-admin.topbar`

### 3.2 O que compartilhar de verdade

Compartilhar **tokens**, padrões de estado e parte dos componentes do cliente web/mobile é ótimo.  
Tentar compartilhar tudo entre Blade/Inspinia e React Native é custo alto e baixo retorno.

Recomendação:

- compartilhar design tokens e semântica de UX
- compartilhar componentes entre React web e React Native
- não forçar compartilhamento físico entre admin Blade e clientes React

## 4. Comparativo de UI kits para React + React Native

**Recorte da pesquisa:** 15/04/2026  
**Critérios:** compartilhamento de código, performance/bundle, comunidade/documentação, curva de aprendizado, aderência ao projeto

| Opção                                       | Versão observada                  | Compartilhamento web/mobile | Performance e bundle                                     | Comunidade/docs                        | Curva de aprendizado                | Leitura                                                    |
| ------------------------------------------- | --------------------------------- | --------------------------- | -------------------------------------------------------- | -------------------------------------- | ----------------------------------- | ---------------------------------------------------------- |
| **Tamagui**                                 | `2.0.0-rc.40`                     | Muito alto                  | Muito forte, com compiler e flattening                   | Forte; GitHub ~13.9k stars             | Média                               | Melhor opção para design system realmente universal        |
| **NativeWind**                              | `4.2.3`                           | Médio                       | Forte; build-time + runtime mínimo                       | Forte; GitHub ~7.8k stars, docs claras | Baixa para time que já usa Tailwind | Excelente como styling layer, não como kit completo        |
| **gluestack-ui**                            | `1.1.73` (`@gluestack-ui/themed`) | Alto                        | Bom; copy-paste e runtime leve                           | Boa; GitHub ~5k stars, docs modernas   | Média                               | Boa opção para time que quer ownership tipo shadcn         |
| **React Native Paper**                      | `5.15.1`                          | Médio                       | Bom para apps RN, menos otimizado para identidade custom | Muito forte; GitHub ~14.3k stars       | Baixa                               | Excelente kit maduro, mas com viés Material                |
| **Dripsy / abordagem design-system enxuta** | sem recomendação de versão        | Médio                       | Bom                                                      | Comunidade menor                       | Média                               | Interessante, mas menos segura para produto de longo prazo |

### 4.1 Tamagui

**Prós**

- compartilhamento forte entre React e React Native
- compiler focado em performance
- kit + style system + tokens no mesmo ecossistema
- bom encaixe para produto com web + mobile reais

**Contras**

- versão observada do pacote principal ainda em RC no npm
- exige curva de adoção maior que Tailwind puro
- muda a mentalidade do time se todos estiverem muito acostumados a utilitários

**Quando usar**

- quando o objetivo é maximizar reuso entre cliente web e mobile
- quando a equipe aceita investir em design system consistente

### 4.2 NativeWind

**Prós**

- familiar para quem já vive em Tailwind
- runtime pequeno e muito bom DX
- ótimo para prototipação e velocidade

**Contras**

- não é um component library; é um styling library
- o time ainda precisará construir um kit próprio
- acessibilidade, variantes e padrões de domínio continuam sendo trabalho da equipe

**Quando usar**

- quando o time prioriza velocidade inicial e domínio de Tailwind
- quando há apetite para construir biblioteca própria em cima dele

### 4.3 gluestack-ui

**Prós**

- filosofia copy-paste com ownership local
- integração com Tailwind/NativeWind
- boa ponte entre web e native

**Contras**

- menor adoção que Tamagui/Paper
- exige disciplina para manter componentes copiados atualizados
- menos plug-and-play que kits tradicionais

**Quando usar**

- quando o time quer um “shadcn cross-platform”
- quando customização total pesa mais que pacote pronto

### 4.4 React Native Paper

**Prós**

- muito maduro
- ótima documentação
- forte acessibilidade e componentes prontos
- também funciona em web via RN Web

**Contras**

- design material pode conflitar com branding do produto
- compartilhamento existe, mas a experiência de design system universal é menos elegante
- web compartilhado tende a ficar mais “app-like” que “produto com identidade própria”

**Quando usar**

- quando prioridade é estabilidade e time RN quer reduzir risco

## 5. Recomendação de UI kit

### 5.1 Recomendação final

**Escolha recomendada para cliente web + mobile: `Tamagui`.**

Motivos:

1. O projeto explicitamente quer React web e futuro React Native.
2. A área do cliente tem muita UI transacional e de status, ideal para um design system universal.
3. Os componentes de domínio do cliente são altamente reaproveitáveis entre web e mobile.
4. O admin já está resolvido com Inspinia, então o novo kit pode focar somente no universo externo.

### 5.2 Segunda melhor opção

Se a equipe quiser maximizar familiaridade com Tailwind e reduzir mudança de mentalidade, a segunda opção recomendada é:

`NativeWind + biblioteca própria de componentes do produto`

Isso reduz risco de adoção, mas aumenta o trabalho de construir o kit de domínio.

## 6. Catálogo proposto de componentes

### 6.1 Shared web/mobile

| Grupo      | Componentes                                                                                                            |
| ---------- | ---------------------------------------------------------------------------------------------------------------------- |
| Primitivos | Button, IconButton, Input, Select, Checkbox, Radio, Switch, Textarea                                                   |
| Feedback   | Toast, Banner, EmptyState, InlineError, LoadingOverlay                                                                 |
| Navegação  | Tabs, Stepper, BottomSheet, Dialog                                                                                     |
| Display    | Card, Badge, Stat, TimelineItem, ListRow                                                                               |
| Domínio    | InviteCard, InviteQuotaCard, RsvpStatusPill, SeatChip, TableLegend, PollOptionCard, ExtraProductCard, PaymentBreakdown |

### 6.2 Admin

O admin mantém o catálogo já documentado localmente, com prioridade para:

- `x-admin.layout`
- `x-admin.sidebar`
- `x-admin.topbar`
- `x-admin.page-header`
- `x-admin.data-table`
- `x-admin.drawer`
- `x-shared.form-family`
- `x-shared.feedback-family`

### 6.3 Componentes novos de domínio no admin

Mesmo com Inspinia pronto, o v4 vai exigir componentes administrativos ainda não cobertos pelo eixo comercial:

- `x-admin.invite-metrics-card`
- `x-admin.rsvp-funnel-card`
- `x-admin.table-map-editor`
- `x-admin.seat-assignment-panel`
- `x-admin.approval-queue-card`
- `x-admin.poll-results-card`

## 7. Stack recomendada para frontend web

| Categoria    | Recomendação           | Justificativa                                 |
| ------------ | ---------------------- | --------------------------------------------- |
| Framework    | React                  | Alinha com direção do produto                 |
| Routing      | React Router 7.14.1    | Ecossistema consolidado e flexível            |
| Server state | TanStack Query 5.99.0  | Cache, invalidação e sincronização excelentes |
| UI state     | Zustand 5.0.12         | Simples, baixo boilerplate                    |
| Forms        | React Hook Form 7.72.1 | Performance e integração boa com Zod          |
| Validation   | Zod 4.3.6              | Schema único, tipagem forte                   |
| HTTP         | Axios 1.15.0           | Interceptors, cancelamento e maturidade       |
| UI kit       | Tamagui 2.0.0-rc.40    | Reuso com mobile                              |

### 7.1 Justificativas rápidas

#### TanStack Query

Melhor escolha para server state porque o produto tem:

- dados assíncronos com invalidação por evento
- telas com filtros e caches
- necessidade de refetch seletivo após RSVP, seating e pagamentos

#### Zustand

Melhor para estado local porque:

- guarda estado de filtros, preferências, drawers e seleção temporária
- não exige a estrutura mais pesada de Redux Toolkit para este cenário

#### React Hook Form + Zod

Combinação ideal para:

- formulários longos
- etapas
- validação compartilhável
- inferência de tipos

#### Axios

Segue recomendado não por “moda”, mas porque o projeto tende a exigir:

- interceptors para auth e correlação
- cancelamento
- tratamento previsível de erros
- integração fácil com Query

## 7.2 Alternativas avaliadas para web

| Categoria    | Alternativa         | Por que não foi a principal                        |
| ------------ | ------------------- | -------------------------------------------------- |
| Server state | Redux Toolkit Query | mais opinado e mais pesado para o volume esperado  |
| UI state     | Redux Toolkit       | excelente, mas boilerplate maior que o necessário  |
| Forms        | Formik              | perde em performance e DX frente a RHF             |
| Validation   | Yup                 | menos alinhado à tipagem moderna do ecossistema TS |

## 8. Stack recomendada para mobile

| Categoria                  | Recomendação               | Justificativa                            |
| -------------------------- | -------------------------- | ---------------------------------------- |
| Runtime                    | Expo                       | Acelera delivery e integrações comuns    |
| Routing                    | Expo Router 55.0.12        | Convenção forte e boa DX                 |
| Server state               | TanStack Query 5.99.0      | Reuso mental e técnico com web           |
| UI state                   | Zustand 5.0.12             | Simples e compartilhável                 |
| Push                       | Expo Notifications 55.0.19 | Mais simples para MVP                    |
| Storage rápido             | MMKV 4.3.1                 | Muito mais performático                  |
| Storage de compatibilidade | AsyncStorage 3.0.2         | Necessário para algumas libs e fallbacks |

### 8.1 MMKV vs AsyncStorage

Recomendação prática:

- usar **MMKV** para preferências, cache local e flags do app
- usar **AsyncStorage** apenas quando alguma biblioteca externa exigir compatibilidade direta

## 8.2 React Navigation vs Expo Router

`Expo Router` é a recomendação final porque acelera a convenção de rotas e simplifica a entrada do time num app Expo.  
`React Navigation` continua sendo a referência de baixo nível e deve ser entendido pelo time, mas não precisa ser a API principal do projeto.

## 9. Referências

- [Tamagui docs](https://tamagui.dev/docs/intro/introduction)
- [Tamagui GitHub](https://github.com/tamagui/tamagui)
- [NativeWind docs](https://www.nativewind.dev/)
- [NativeWind GitHub](https://github.com/nativewind/nativewind)
- [gluestack-ui docs](https://gluestack.io/ui/docs/home/overview/introduction)
- [gluestack-ui GitHub](https://github.com/gluestack/gluestack-ui)
- [React Native Paper](https://reactnativepaper.com/)
- [React Native Paper GitHub](https://github.com/callstack/react-native-paper)
- [TanStack Query](https://tanstack.com/query/latest)
- [React Router](https://reactrouter.com/home)
- [React Hook Form](https://react-hook-form.com/)
- [Zod](https://zod.dev/)
- [Axios](https://axios-http.com/docs/intro)
- [Expo Router](https://docs.expo.dev/router/introduction/)
- [Expo Notifications](https://docs.expo.dev/versions/latest/sdk/notifications/)
- [AsyncStorage](https://react-native-async-storage.github.io/async-storage/)
- [React Native MMKV](https://github.com/mrousavy/react-native-mmkv)
