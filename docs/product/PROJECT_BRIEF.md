---
title: Project Brief — Portal ArtFinal v2 (Backend API v1)
version: 1.0.0
date: 2026-04-17
status: draft
---

# Project Brief — Portal ArtFinal v2

> Brief executivo do projeto **Plataforma de Gestão de Formaturas — Backend API v1**.
> Fontes: [`PRD_v4.md`](../prd/PRD_v4.md), [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md), [`REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md), [`ROADMAP.md`](../prd/ROADMAP.md).
> Documentos irmãos: [PRD expandido](./PRD_EXPANDED.md) · [User flows](./user-flows.md) · [Jornadas](./journeys-personas.md) · [Telas macro](./macro-screens.md) · [SRS](./SRS.md).

---

## 1. Visão

O Portal ArtFinal v2 deixa de ser um “portal de adesão” e passa a ser uma **plataforma de evento** que opera de ponta a ponta a jornada de uma formatura: planejamento acadêmico, gestão comercial de adesões e pacotes, emissão e controle de convites, RSVP, mapa de mesas com reserva concorrente, enquetes, venda de extras com pagamento online, comunicação transacional, dashboards e auditoria.

A plataforma é construída como **monólito modular Laravel 13** com **API v1 pública** consumida por múltiplos canais:

- **Admin/backoffice** em Blade/Livewire + Inspinia (aproveita o investimento já feito).
- **Portal do formando** em React SPA consumindo `api/v1`.
- **App mobile futuro** em React Native consumindo a mesma `api/v1`.
- **Core de domínio** desacoplado em Actions, DTOs, Policies, Jobs e Events.

O eixo é claro: **o core é fonte de verdade, a API é o contrato, e cada canal é uma interface sobre o mesmo modelo de domínio**.

---

## 2. Problema

Organizadoras de formatura e comissões de formandos operam hoje com ferramentas fragmentadas:

- planilhas para controle de formandos, parcelas e pagamentos;
- formulários soltos para convidados e RSVP manual;
- mapas de mesa em PowerPoint, Excel ou arquivos estáticos;
- pagamentos paralelos para convites extras, muitas vezes por PIX direto;
- mensageria manual em grupo de WhatsApp como único canal de comunicação.

Isso produz retrabalho contínuo, falhas de comunicação, **conflito de assentos** na véspera, dificuldade de auditoria, baixa previsibilidade financeira e uma experiência ruim tanto para o formando quanto para o convidado.

O produto atual do cliente (v3.1.0) resolve parte do eixo comercial (adesão, parcelas, pagamentos) mas **não trata o evento como agregado central**. Convites, RSVP, seating, extras e enquetes vivem no backlog ou em anexos operacionais soltos.

---

## 3. Solução

O v4 entrega um backend API-first único, com domínio centralizado e trilha de auditoria, expondo cinco capabilities ponta a ponta:

1. **Cadastro acadêmico e configuração de evento** (organização, instituição, curso, turma, evento, janelas, lotes).
2. **Comercial e adesão** (pacotes, produtos, parcelamento, pagamento base, snapshots imutáveis).
3. **Convites e RSVP** (cota por formando, emissão unitária/lote, token revogável, funil RSVP completo).
4. **Seating com concorrência real** (mapa, mesa, assento, hold temporário, confirmação transacional, troca auditada).
5. **Extras pagos e enquetes** (catálogo, elegibilidade, pedido, pagamento, emissão derivada, votação com janela).

Sobre isso, três camadas transversais:

- **Comunicação transacional** (e-mails, push, templates versionados).
- **Observabilidade e auditoria** (activity log, request-id, webhook log).
- **Relatórios operacionais** (ocupação, RSVP, financeiro, engajamento).

---

## 4. Proposta de valor

| Público                | Valor principal                                                                                         |
| ---------------------- | ------------------------------------------------------------------------------------------------------- |
| Organizadora (admin)   | Controle, rastreabilidade e velocidade operacional. Um único lugar para operar N eventos simultâneos.   |
| Comissão               | Visibilidade e autonomia com permissões granulares, sem comprometer dados críticos da organizadora.     |
| Formando               | Clareza financeira, carteira de convites, escolha de mesa online e compra de extras em poucos cliques.  |
| Responsável financeiro | Previsibilidade de parcelas, comprovantes acessíveis e comunicação clara sobre vencimentos.             |
| Convidado              | Fluxo de RSVP em menos de 2 minutos, sem cadastro pesado. Token curto, revogável e seguro.              |
| Operação/recepção      | Acesso rápido e confiável ao status de convite, RSVP e reserva durante janelas críticas.                |
| Equipe de engenharia   | Core desacoplado, contratos estáveis, testes de arquitetura e ambiente idempotente para escalar canais. |

---

## 5. Personas

### 5.1 Primárias (MVP)

#### Administrador da organizadora

Responsável por configurar o sistema, criar eventos, importar dados, gerir permissões, acompanhar pagamentos, RSVP, ocupação e exceções operacionais.
Drivers: controle, rastreabilidade, evitar retrabalho.

#### Formando

Dono da adesão e da cota de convites. Emite convites, acompanha pagamentos, compra extras, escolhe mesa quando a janela abrir, responde enquetes.
Drivers: clareza, velocidade, confiança no processo.

#### Convidado

Recebe convite por link/token, confirma ou recusa presença, eventualmente seleciona assento.
Drivers: simplicidade, menos fricção, segurança.

#### Comissão de formatura

Perfil semi-operacional. Acompanha aderência da turma, convites emitidos, pendências de RSVP, ocupação do salão, publica enquetes.
Drivers: visibilidade e autonomia controlada.

### 5.2 Secundárias

#### Responsável financeiro

Pode ser diferente do formando (pai/mãe/financeiro da família). Recebe cobranças, concluí pagamentos e visualiza comprovantes.

#### Operação do evento

Equipe de retaguarda, recepção, suporte na noite do evento. Acessa rapidamente o status de convites e reservas, atua em exceções.

---

## 6. Objetivos SMART

| #   | Objetivo                                                                                | Indicador verificável                             | Prazo        |
| --- | --------------------------------------------------------------------------------------- | ------------------------------------------------- | ------------ |
| 1   | Entregar API v1 estável com OpenAPI publicada e consumida por React SPA                 | `docs/api/openapi.json` publicado + SPA em F3     | Fim da F3    |
| 2   | Reduzir tempo de RSVP percebido pelo convidado para ≤ 2 minutos médios                  | Tempo médio medido entre `enviado` e `confirmado` | Piloto F4    |
| 3   | Garantir zero conflito de assento confirmado após a abertura pública do mapa            | 0 casos de `AssentoIndisponivel` em `confirmada`  | Piloto F5    |
| 4   | Suportar 95% de pedidos extras concluídos sem intervenção manual                        | `pago / (pago+pendente+falhou)` de `PedidoExtra`  | Piloto F6    |
| 5   | Emitir lote de 200 convites em ≤ 5 minutos                                              | Tempo médio do `EmitirLoteConvitesJob`            | F4           |
| 6   | Atingir P95 ≤ 500 ms em listagens críticas e ≤ 700 ms em reservas                       | Métricas Pulse + APM em produção                  | F7           |
| 7   | Atingir 99,5% de uptime mensal no primeiro ciclo completo                               | Monitoramento de disponibilidade                  | F7 / go-live |
| 8   | Mobile MVP (login, dashboard, convites, RSVP, seating simplificado) consumindo `api/v1` | Build Expo publicado em TestFlight/Play Internal  | F8           |

---

## 7. Métricas de sucesso (KPIs quantitativos)

Extraídas diretamente de [`PRD_v4.md §1.6`](../prd/PRD_v4.md).

### 7.1 Produto

| Métrica                                              | Meta inicial |
| ---------------------------------------------------- | ------------ |
| Conversão de adesão do fluxo iniciado para concluído | ≥ 65%        |
| Taxa de RSVP respondido por convite emitido          | ≥ 75%        |
| Tempo médio de confirmação de presença               | ≤ 2 min      |
| Taxa de conflito de assento após liberação pública   | < 0,5%       |
| Taxa de pagamento bem-sucedido de extras             | ≥ 95%        |

### 7.2 Operação

| Métrica                                                    | Meta inicial |
| ---------------------------------------------------------- | ------------ |
| Tempo para emitir lote de convites                         | ≤ 5 min      |
| Tempo para reprocessar webhook com idempotência            | ≤ 1 min      |
| Tempo para refletir assento reservado em todos os clientes | ≤ 3 s        |
| Exportação de relatório operacional                        | ≤ 30 s       |

### 7.3 Qualidade de plataforma

| Métrica                              | Meta inicial  |
| ------------------------------------ | ------------- |
| Uptime mensal                        | ≥ 99,5%       |
| P95 de API para listagens críticas   | ≤ 500 ms      |
| P95 de reserva de assento            | ≤ 700 ms      |
| Falhas não tratadas em jobs críticos | 0 silenciosas |

---

## 8. Escopo MVP vs Fora do escopo

### 8.1 Incluído no MVP (F1–F6)

- Cadastro estrutural: organização, instituição, curso, turma, evento, lote, status.
- Adesão comercial completa: pacotes, produtos, programações, descontos, parcelamento, pagamentos.
- Autenticação segregada por perfil (admin, comissão, formando) + token de convidado.
- Convites (nominal, transferível, cortesia, staff, extra) + RSVP com estados rastreáveis.
- Mapa de mesas, mesas, assentos, holds temporários, confirmação transacional, troca auditada.
- Venda de convites extras com pagamento online e emissão derivada.
- Enquetes simples e múltipla escolha com janela e elegibilidade.
- E-mails transacionais e push (quando mobile entrar em F8).
- Dashboards operacionais essenciais + exportações CSV/Excel.
- Auditoria append-only em todas as ações críticas.
- API v1 versionada + OpenAPI publicado via Scramble.

### 8.2 Fora do escopo inicial

- Check-in presencial por QR Code em escala de operação (avaliado pós-MVP).
- Marketplace de fornecedores terceiros.
- Recursos avançados de live event (streaming, interação ao vivo).
- Networking social entre convidados.
- BI analítico avançado além dos relatórios operacionais.
- Ranking/votação com cardinalidade avançada (entra em iteração futura).
- SMS/WhatsApp (canal contemplado como futuro na arquitetura, fora do MVP).

---

## 9. Hipóteses e riscos

### 9.1 Hipóteses

1. O admin continuará operado por usuário técnico-operacional da organizadora — Blade/Livewire atende.
2. A experiência do formando é o grande driver de adoção — justifica o custo de um SPA React.
3. O provedor inicial de pagamentos (Itaú) cobre PIX, boleto e cartão para o primeiro piloto.
4. Convidado quase nunca cria conta — RSVP via token descartável é suficiente no MVP.
5. A organizadora aceita rollout por evento piloto (não big-bang).

### 9.2 Riscos e mitigações

| Risco                                                | Prob. | Impacto | Mitigação                                                                             |
| ---------------------------------------------------- | :---: | :-----: | ------------------------------------------------------------------------------------- |
| Seating exigir regras mais complexas que o MVP prevê | média |  alto   | Política configurável, começar com assento individual, habilitar por flag.            |
| Escopo crescer na F3 (React SPA)                     | alta  |  alto   | Congelar MVP por capability, usar TanStack Query + Zustand sem inventar.              |
| Mobile forçar breaking change na API tardiamente     | média |  médio  | Validar API com SPA web antes do F8; contratos versionados desde o dia 1.             |
| Comissão pedir permissões excessivas                 | média |  médio  | Policy-first, auditoria completa, revisão de permissões por evento.                   |
| Webhook do gateway chegar duplicado ou fora de ordem | alta  |  alto   | `webhook_eventos` com UNIQUE por `(provider, gateway_reference)` + jobs idempotentes. |
| Performance cair na abertura pública do mapa         | média |  alto   | Cache por evento, SSE/polling controlado, índices dedicados em `reservas_assentos`.   |
| Ocorrência LGPD com dados de convidado (e-mail, CPF) | baixa |  alto   | Tokenização de convite, expurgo de logs sensíveis, retenção configurada.              |

---

## 10. Marcos (Fases F1–F8)

Referência direta de [`ROADMAP.md`](../prd/ROADMAP.md).

| Fase | Foco                                           |  SP | Marcos entregáveis                                                         |
| ---- | ---------------------------------------------- | --: | -------------------------------------------------------------------------- |
| F1   | Fundação de domínio, auth e base técnica       |  34 | Migrations core, actions/DTOs base, `api/v1` esqueleto, Sanctum, policies. |
| F2   | Admin estrutural e cadastros base              |  40 | Auth admin + ACL, CRUD estrutural, CRUD comercial, dashboard inicial.      |
| F3   | Cliente web React e jornada do formando        |  34 | Auth SPA, dashboard, extrato, carteira de convites, design system web.     |
| F4   | Convites e RSVP                                |  28 | Cota, emissão unit/lote, token, RSVP, dashboards de acompanhamento.        |
| F5   | Seating e concorrência                         |  34 | Mapa, editor admin, leitura no cliente, hold, confirmação, fila exceções.  |
| F6   | Extras, pagamentos operacionais e enquetes     |  34 | Catálogo extras, pedido, pagamento, emissão derivada, votação.             |
| F7   | Hardening, observabilidade e relatórios finais |  21 | Dashboards finais, relatórios, monitoramento, revisão segurança.           |
| F8   | Mobile MVP                                     |  34 | Login, dashboard, convites, RSVP, seating simplificado, push.              |

**Marco MVP executivo:** fim da F5 (evento configurável + adesão + convites/RSVP + seating confiável).
**Marco MVP comercial ampliado:** fim da F6 (extras pagos).

---

## 11. Dependências

### 11.1 Dependências externas

- **Gateway de pagamento** (inicial: Itaú) com webhook confiável, assinatura HMAC e endpoint de consulta.
- **Provedor transacional de e-mail** (Mailgun, Postmark ou SES).
- **Provedor de push** (Expo Push para mobile em F8).
- **Storage privado** (S3/R2/MinIO) para anexos e PDFs gerados.
- **Observabilidade** (Sentry ou equivalente para error tracking).

### 11.2 Dependências internas

1. Domínio de **evento** precisa existir antes de convites e seating.
2. `api/v1` precisa existir antes da área React.
3. Pagamentos e comunicação dependem de **eventos de domínio** confiáveis.
4. Seating depende de **identidade de convidados e RSVP** minimamente estruturados.
5. Mobile (F8) depende de **contratos estáveis da API**, não do admin.

### 11.3 Decisões pendentes bloqueantes (Apêndice B do planejamento)

- Convidado pode comprar extras diretamente ou apenas via formando responsável?
- Comissão pode aprovar compras extras e trocas de assento?
- Vários salões/mapas por evento?
- Um formando pode ter mais de um evento ativo no mesmo período?
- SLA contratual superior a 99,5%?

---

## 12. Investimento em Story Points

| Fase                            | Story Points |
| ------------------------------- | -----------: |
| F1                              |           34 |
| F2                              |           40 |
| F3                              |           34 |
| F4                              |           28 |
| F5                              |           34 |
| F6                              |           34 |
| F7                              |           21 |
| F8                              |           34 |
| **Total MVP executivo (F1–F5)** |      **170** |
| **Total MVP ampliado (F1–F6)**  |      **204** |
| **Total plataforma (F1–F8)**    |      **259** |

Story points são referência relativa conforme [`ROADMAP.md §1`](../prd/ROADMAP.md), não compromisso de prazo absoluto.

---

## 13. Critérios de prontidão do MVP

Para o MVP ser considerado pronto (não basta a tela existir):

1. **Auditoria mínima** dos fluxos críticos ativa e consultável.
2. **Monitoramento de fila e webhook** com alertas.
3. **Testes de concorrência** em seating aprovados (zero duplicidade).
4. **Trilha operacional** para suporte e atendimento documentada.
5. **OpenAPI publicado** e validado pelo consumidor SPA.
6. **P95 dentro das metas** em ambiente de homologação com carga representativa.
7. **Snapshots imutáveis** verificados em adesão, pagamento, convite e reserva confirmada.
8. **Runbook de incidentes** (webhook duplicado, reserva órfã, pagamento aprovado sem webhook).

---

## 14. Referências

- [PRD v4](../prd/PRD_v4.md)
- [Planejamento Backend API v1](../prd/PLANEJAMENTO_BACKEND_APIV1.md)
- [Regras de Negócio](../prd/REGRAS_NEGOCIO.md)
- [Roadmap](../prd/ROADMAP.md)
- [CLAUDE.md](../../CLAUDE.md)
