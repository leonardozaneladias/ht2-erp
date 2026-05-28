# Revisão Crítica do PRD v3.1.0

**Documento analisado:** `docs/PRD_Sistema_Formatura_v3.1.0.md`  
**Data da revisão:** 15/04/2026  
**Objetivo:** identificar lacunas funcionais, ambiguidades arquiteturais e decisões que precisam ser atualizadas para suportar um sistema de gestão de formaturas com web React, futuro mobile React Native e novas frentes de negócio como convites, mesas, enquetes e extras.

## Síntese executiva

O PRD v3.1.0 é forte como documento de **adesão comercial e financeiro**. Ele detalha com boa profundidade contratos, pacotes, programações, descontos, cálculo de parcelas, fluxo de adesão e parte relevante do backoffice. Também há um trabalho maduro de componentização do admin Inspinia no repositório, o que reduz risco de execução da camada administrativa.

Ao mesmo tempo, o documento já mostra sinais claros de limite:

1. Ele não é totalmente autossuficiente, porque várias seções críticas dependem de conteúdo “mantém-se integralmente conforme PRD v2.1”. Isso compromete leitura, versionamento e onboarding.
2. A arquitetura funcional do produto ainda está centrada em **Laravel + Livewire** para portal e admin, enquanto a direção atual já pede **cliente em React** e **mobile em React Native**.
3. O v3 trata muito bem adesão e cobrança, mas cobre pouco ou nada do que agora vira núcleo do produto: **convites, RSVP, mapa de mesas, reserva concorrente, enquetes/votações e venda operacional de convites extras**.
4. O documento fala em “API-ready” na documentação complementar do projeto, mas isso não virou um contrato arquitetural explícito no PRD.
5. Requisitos não funcionais ainda estão subespecificados para um produto com múltiplos perfis, pagamentos, webhooks, concorrência em assentos e múltiplos eventos simultâneos.

Em resumo: o PRD v3.1.0 é uma boa base para o contexto comercial da formatura, mas precisa ser reestruturado no v4 para virar um documento de produto e arquitetura realmente pronto para sustentar **multi-cliente, múltiplos canais e operação do evento**.

## Pontos de melhoria por seção

### 1. Visão geral e escopo

- O objetivo do produto está muito orientado à adesão e ao financeiro, e pouco orientado à jornada completa da formatura.
- Falta separar explicitamente o que é **core da plataforma** do que é **backlog evolutivo**.
- O público “comissão de formatura” aparece pouco como ator operacional com poderes específicos.
- O conceito de “evento” não está consolidado como agregado principal do domínio.

### 2. Arquitetura de alto nível

- Há conflito entre a arquitetura descrita no PRD e a direção atual do produto.
- O portal continua pensado como Livewire, embora a área do cliente agora tenha sido definida em React.
- Não existe decisão formal entre monólito Blade/Livewire, Inertia híbrido ou API-first.
- O futuro mobile aparece como backlog, mas sem impactos refletidos no desenho atual de autenticação, versionamento e contratos de API.

### 3. Módulos do sistema

- Os módulos cobrem comercial, adesão, portal e admin, mas não cobrem de forma estruturada convites, RSVP, mesas e enquetes.
- “Compra de extras” existe, porém como extensão do portal financeiro, não como domínio operacional ligado ao evento.
- Não há módulo de comunicação omnicanal com status de envio, reenvio, bounce e auditoria por convite.

### 4 a 10. Contratos, produtos, programações, descontos, config globais, parcelas e termos

- A base comercial é boa, porém há dependência excessiva de versões anteriores do PRD.
- Falta explicitar quais regras são invariantes de domínio e quais são parametrizações administrativas.
- Não está claro quais dados viram snapshot imutável em adesão, em compra de extra, em convite emitido e em reserva de assento.
- O documento não diferencia adequadamente “produto de adesão” de “item operacional do evento”.

### 11 a 13. Autenticação, portal e área do formando

- O modelo multi-formando está bem encaminhado, mas falta expandir para a experiência de convidados e comissão.
- Não há fluxo claro para convidado sem conta, convidado com token, convidado com convite transferido ou convidado com compra complementar.
- O portal atual é mais uma área financeira do formando do que uma plataforma social do evento.

### 14. Backoffice administrativo

- O nível de detalhamento visual é bom e casa com o trabalho de Inspinia já feito no repositório.
- Ainda assim, o backoffice detalha principalmente o eixo comercial/financeiro.
- Faltam telas e workflows de operação do evento: emissão de convites, upload/importação de convidados, RSVP, seating, filas de aprovação, auditoria operacional, central de comunicação e monitoramento por evento.

### 15 a 17. Pagamentos, e-mails e modelo de dados

- Essas seções estão resumidas demais para o peso que carregam no sistema.
- “Mantém-se integralmente” não é suficiente para pagamentos, webhooks, idempotência, reconciliação e antifraude operacional.
- O modelo de dados listado não reflete novas entidades essenciais de convites, mesas, assentos, reservas, enquetes e votos.

### 18. Requisitos não funcionais

- Segurança e acessibilidade aparecem, mas ainda de forma introdutória.
- Faltam metas de desempenho, disponibilidade, RPO/RTO, observabilidade, rastreabilidade de auditoria, limites de taxa, política de retenção e LGPD.
- Não há tratamento de concorrência para cenários críticos, especialmente escolha de mesas.

### 19 e 20. Stack e cronograma

- O stack técnico já está desatualizado em relação ao repositório e ao direcionamento de produto.
- O cronograma portal-first fez sentido para adesão, mas precisa ser reorganizado por **capacidades de negócio** e **dependências de plataforma**.
- Falta separar entregas do backoffice interno da entrega do app/SPA do cliente.

### 21. Backlog

- O backlog antigo empurra justamente funcionalidades que agora parecem centrais para o produto.
- Mesas online, validação de convites e app mobile não deveriam mais ficar como observações marginais.

## Principais dúvidas a esclarecer

1. O sistema terá um único evento por contrato/turma ou deve suportar múltiplos eventos por turma?
2. A comissão de formatura terá autonomia operacional real ou atuará apenas como perfil restrito de consulta e apoio?
3. Convidados poderão responder presença sem criar conta, usando token mágico do convite?
4. A compra de convites extras exige aprovação manual, aprovação automática por regra ou ambos?
5. A escolha de mesas é por formando, por grupo familiar, por convite individual ou por lote de lugares?
6. Haverá hold temporário de assento com expiração? Se sim, por quanto tempo e em quais etapas?
7. Enquetes serão apenas sociais/promocionais ou também deliberativas, com regra de elegibilidade e peso de voto?
8. O pagamento de extras será sempre online ou deve coexistir com baixa administrativa/manual?
9. O produto é single-tenant por operação da organizadora ou precisa nascer preparado para múltiplas organizadoras/clientes?
10. O app mobile é fase 2/3 com escopo fechado ou apenas uma intenção estratégica ainda sem compromisso de prazo?

## Melhorias recomendadas por impacto

### Alto impacto

- **Formalizar arquitetura híbrida API-first.** Admin interno pode permanecer server-driven, mas web do cliente e mobile devem consumir API versionada desde o início.
- **Transformar “evento” em eixo do domínio.** Convites, mesas, RSVP, enquetes e extras devem orbitar um evento, não apenas um contrato comercial.
- **Reescrever o PRD para ser autossuficiente.** O v4 não deve depender de “conteúdo herdado do v2.1”.
- **Introduzir bounded contexts claros.** Comercial/adesão, operação do evento, convites, seating, pagamentos, comunicação e identidade/acesso.
- **Detalhar concorrência e idempotência.** Reserva de assento, compra de extra e webhook de pagamento exigem regras explícitas.
- **Expandir matriz de atores e permissões.** Admin, comissão, formando, responsável, convidado e operação precisam ter jornadas e poderes distintos.

### Médio impacto

- **Separar produto interno de produto externo.** O backoffice usa Inspinia e componentes Blade já maduros; a experiência do cliente precisa de design system próprio compartilhável com mobile.
- **Reclassificar backlog.** Mesas, convites e RSVP devem migrar de backlog para escopo core do v4.
- **Definir estratégia de notificações.** E-mail, push e mensagens transacionais precisam de eventos, templates, opt-in e rastreamento.
- **Atualizar roadmap para story points e dependências.** O cronograma atual é útil como histórico, mas não como plano arquitetural moderno.

### Baixo impacto

- **Padronizar nomenclatura.** Há termos misturados entre contrato, turma, adesão, portal e produto que podem ser consolidados.
- **Adicionar métricas de sucesso mais objetivas.** Conversão de adesão, taxa de RSVP, ocupação de mesas, SLA de processamento e inadimplência.
- **Separar docs de produto e docs de execução visual.** O material Inspinia é ótimo, mas não deve substituir decisões de produto.

## Conclusão

O PRD v3.1.0 não está “errado”; ele está **incompleto para a ambição atual do produto**. Ele descreve muito bem um sistema de adesão e financeiro, mas ainda não descreve com a mesma maturidade um sistema de gestão de formaturas orientado a evento, convidados, engajamento e múltiplos canais. O v4 precisa preservar o que o v3 já resolveu, mas mudar o eixo arquitetural: de “portal Livewire de adesão” para “plataforma de formatura com domínio API-first e múltiplas interfaces”.
