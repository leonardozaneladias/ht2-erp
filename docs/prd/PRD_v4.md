# PRD v4 — Plataforma de Gestão de Formaturas

**Versão:** 4.0.0  
**Data:** 15/04/2026  
**Status:** proposta arquitetural pronta para alinhamento de produto e início de implementação  
**Base de revisão:** `docs/PRD_Sistema_Formatura_v3.1.0.md`, `docs/01-ARCHITECTURE-GUIDE.md`, `docs/04-TEMPLATE-MAP-AND-COMPONENTS.md`, catálogo Inspinia e análise do boilerplate atual  
**Documentos complementares:** `ANALISE_CRITICA_PRD_V3.md`, `ARQUITETURA_DETALHADA.md`, `COMPONENTES_UI.md`, `REGRAS_NEGOCIO.md`, `SEGURANCA.md`, `PERFORMANCE.md`, `ROADMAP.md`

---

## 1. Visão Geral do Produto

### 1.1 Objetivo

A Plataforma de Gestão de Formaturas é um sistema modular para operar a jornada completa da formatura, cobrindo:

- planejamento acadêmico e operacional por turma e evento
- gestão comercial de adesões, pacotes e cobrança
- gestão de convites, RSVP e presença
- mapa de mesas e reserva online com controle de concorrência
- enquetes e votações com regras de elegibilidade
- venda e pagamento de convites extras
- operação administrativa, comunicação e auditoria

O produto deixa de ser apenas um “portal de adesão” e passa a ser uma **plataforma de evento com eixo comercial + eixo operacional + eixo de experiência do convidado**.

### 1.2 Problema que o produto resolve

Hoje, organizadoras e comissões de formatura costumam operar em ferramentas fragmentadas: planilhas para controle de formandos, formulários soltos para convidados, mensagens manuais para RSVP, mapas de mesa em arquivos estáticos, pagamentos paralelos para extras e pouca rastreabilidade. Isso gera retrabalho, falhas de comunicação, conflito de assentos, dificuldade de auditoria e baixa previsibilidade operacional.

O v4 propõe um sistema único, com domínio centralizado, trilha de auditoria e múltiplas interfaces para:

- reduzir custo operacional da organizadora
- dar autonomia controlada à comissão
- simplificar a experiência do formando
- permitir resposta e confirmação fluida dos convidados
- preparar o negócio para web React e futuro app mobile sem reescrever o core

### 1.3 Personas

#### Administrador da organizadora

Responsável pelo backoffice central. Precisa configurar contratos, eventos, quotas, produtos, regras, permissões, dashboards, relatórios, pagamentos, importações e auditoria. Valor principal: controle, rastreabilidade e velocidade operacional.

#### Comissão de formatura

Atua como perfil semi-operacional. Precisa acompanhar aderência, convites emitidos, RSVP, ocupação de mesas, campanhas de comunicação, enquetes e pendências da turma. Valor principal: visibilidade e autonomia sem comprometer dados críticos.

#### Formando

É o usuário principal da área do cliente. Precisa aderir, acompanhar pagamentos, indicar ou gerenciar convidados, selecionar mesa quando elegível, responder enquetes, comprar extras e consultar status do evento. Valor principal: clareza, velocidade e confiança.

#### Responsável financeiro

Pode ser diferente do formando. Precisa receber cobranças, acompanhar débitos, concluir pagamentos e visualizar comprovantes. Valor principal: previsibilidade financeira e comunicação clara.

#### Convidado

É um usuário de baixa fricção. Precisa receber convite, confirmar presença, eventualmente selecionar assento quando permitido e acessar instruções do evento. Valor principal: fluxo simples, sem necessidade de cadastro pesado.

#### Operação do evento

Equipe de retaguarda, recepção e suporte. Precisa consultar situação de convites, reservas, pagamentos extras e ocorrências. Valor principal: acesso rápido e confiável durante janelas críticas.

### 1.4 Escopo do v4

#### Incluído no core

- cadastro estrutural: organizadora, instituição, curso, turma, evento, lote e status
- adesão comercial: pacotes, produtos, programações, descontos, parcelamento, pagamentos
- autenticação e autorização segregadas por perfil
- gestão de convidados e convites
- RSVP com estados rastreáveis
- mapa de mesas, mesas, assentos, holds e confirmações
- enquetes e votações
- venda de convites extras e cobrança online
- central de notificações e e-mails transacionais
- dashboards, relatórios e auditoria
- API versionada para área do cliente web e futuro mobile

#### Fora do core inicial

- check-in presencial por QR Code em escala de operação
- marketplace de fornecedores terceiros
- recursos avançados de live event
- recursos de networking social entre convidados
- BI analítico avançado além dos relatórios operacionais

### 1.5 Limites do produto

- O admin não precisa compartilhar componentes com o cliente web/mobile; ele compartilha regras de domínio e contratos de dados.
- O sistema não nasce como marketplace aberto para terceiros.
- O mobile entra como consumidor do mesmo backend, não como backend paralelo.
- O v4 não pressupõe microservices. A escolha é por **monólito modular Laravel**, com separação clara de contextos e API pública versionada.

### 1.6 Métricas de sucesso

#### Produto

| Métrica                                              | Meta inicial |
| ---------------------------------------------------- | ------------ |
| Conversão de adesão do fluxo iniciado para concluído | >= 65%       |
| Taxa de RSVP respondido por convite emitido          | >= 75%       |
| Tempo médio de confirmação de presença               | <= 2 minutos |
| Taxa de conflito de assento após liberação pública   | < 0,5%       |
| Taxa de pagamento bem-sucedido de extras             | >= 95%       |

#### Operação

| Métrica                                                    | Meta inicial   |
| ---------------------------------------------------------- | -------------- |
| Tempo para emitir lote de convites                         | <= 5 minutos   |
| Tempo para reprocessar webhook com idempotência            | <= 1 minuto    |
| Tempo para refletir assento reservado em todos os clientes | <= 3 segundos  |
| Exportação de relatório operacional                        | <= 30 segundos |

#### Qualidade de plataforma

| Métrica                              | Meta inicial  |
| ------------------------------------ | ------------- |
| Uptime mensal                        | >= 99,5%      |
| P95 de API para listagens críticas   | <= 500 ms     |
| P95 de reserva de assento            | <= 700 ms     |
| Falhas não tratadas em jobs críticos | 0 silenciosas |

---

## 2. Decisões Arquiteturais

### 2.1 Recomendação principal

O v4 adota **arquitetura híbrida API-first**:

- **Admin/backoffice:** Laravel 13 + Blade/Livewire + Inspinia, aproveitando o investimento já existente em componentização.
- **Área do cliente web:** React consumindo `API v1` do Laravel.
- **App mobile futuro:** React Native consumindo a mesma `API v1`.
- **Core do domínio:** Actions, DTOs, policies, jobs, events e services desacoplados da interface.

### 2.2 Por que não Inertia.js como estratégia principal

Inertia funciona muito bem quando o produto é essencialmente um monólito Laravel com uma única interface SPA web. Este não é mais o caso. O produto agora precisa:

- sustentar web React e mobile React Native
- ter contratos estáveis entre múltiplos clientes
- oferecer jornadas guest/tokenizadas
- expor estados de convite, RSVP, assento e pagamento a mais de um canal
- preparar integrações operacionais futuras sem acoplamento à UI web

Por isso, **Inertia não é a melhor decisão principal** para o produto. Seu custo cresce quando a API inevitavelmente precisa nascer depois para mobile e integrações. No nosso cenário, isso criaria duas superfícies de contrato: uma implícita pela camada Inertia e outra explícita para mobile.

### 2.3 Por que manter o admin em Blade/Livewire

O repositório atual já tem forte investimento no admin Inspinia:

- catálogo de componentes consolidado
- mapa tela → componente detalhado
- dezenas de componentes `x-admin.*` e `x-shared.*` já implementados
- rotas de preview e estrutura de tema resolvidas

Reescrever esse admin em React agora teria alto custo e baixo retorno imediato. O valor do React está na experiência do cliente e na futura convergência com mobile. O valor do admin está em velocidade de entrega, produtividade e reuso do trabalho já feito.

### 2.4 Decisão formal

| Contexto               | Decisão                                   |
| ---------------------- | ----------------------------------------- |
| Backoffice interno     | Blade/Livewire + Inspinia                 |
| Cliente web            | React SPA/SSR leve consumindo API         |
| Mobile                 | React Native consumindo API               |
| Core de negócio        | API-ready desde o dia 1                   |
| Webhooks e integrações | Jobs idempotentes + actions               |
| Versionamento público  | `api/v1` desde a primeira entrega externa |

### 2.5 Diagrama de arquitetura

```text
                                   +----------------------+
                                   |  Admin Backoffice    |
                                   | Blade + Livewire     |
                                   | Inspinia Components  |
                                   +----------+-----------+
                                              |
                                              | session/cookie
                                              v
+----------------------+          +-----------+------------+          +----------------------+
| Cliente Web React    |  HTTPS   |    Laravel 13 Core     |  Queue   | Horizon + Workers    |
| Formando/Comissão    +--------->+ API v1 + Web Admin      +--------->+ jobs, mails, webhooks|
| Guest flows token    |          | Actions + DTOs + Policy |          +----------------------+
+----------------------+          +-----------+------------+
                                              |
                                              | SQL / cache / events
                                              v
                                   +----------+-----------+
                                   | PostgreSQL + Redis   |
                                   | dados + locks + cache|
                                   +----------+-----------+
                                              ^
                                              |
                                              | HTTPS token/Sanctum
+----------------------+                      |
| App Mobile RN        +----------------------+
| formandos/comissão   |
+----------------------+

External services:
- gateway de pagamento
- SMTP / provider transacional
- storage de arquivos
- observabilidade / error tracking
```

### 2.6 Princípios arquiteturais

1. **Monólito modular** antes de microservices.
2. **API pública versionada** para todas as interfaces externas.
3. **Core independente da camada HTTP**.
4. **Concorrência tratada explicitamente** onde houver disputa por recurso.
5. **Idempotência obrigatória** em pagamentos, reservas e webhooks.
6. **Eventos e jobs** para tarefas assíncronas e integração.
7. **Auditoria append-only** para ações críticas.
8. **Snapshots comerciais e operacionais** para preservar histórico.

---

## 3. Bounded Contexts do Produto

### 3.1 Cadastro Acadêmico e Evento

Responsável por instituições, cursos, turmas, eventos, cronograma, local, setores e regras operacionais.

### 3.2 Comercial e Adesão

Responsável por contratos, pacotes, produtos, programações de valor, descontos, condições de pagamento, adesão, parcelas e pagamentos base.

> **Modelo atualizado (2026-04-23) — ver [SPEC-F-001 v0.3.0](../features/foundation/SPEC-F-001-contrato-e-turma.md).** O **Contrato** é a entidade central deste contexto (`Contrato hasMany Turmas`). Um contrato pode agrupar múltiplas turmas (combinações curso + ano + semestre) e é identificado publicamente por `contratos.codigo_acesso` (VARCHAR 32, humano-legível, ex.: `ARTFINAL-USP-MED-2026`). **Pacotes** ganham coluna `categoria` enum `['formatura','extra']`: o wizard público de adesão ([SPEC-010 v2.0.0](../features/SPEC-010-adesao-publica-codigo-contrato.md)) mostra apenas `formatura` (exige seleção de exatamente 1); `extra` (convites adicionais, mesas premium) só aparece no portal autenticado após adesão concretizada. Condições de pagamento por método (PIX, boleto, cartão) são definidas em `condicoes_pagamento` do contrato (ver [SPEC-F-005](../features/foundation/SPEC-F-005-descontos-condicoes.md) §3.1).

### 3.3 Convites e RSVP

Responsável por cotas, lotes, emissão, reemissão, transferência, confirmação de presença e histórico de entrega.

### 3.4 Seating

Responsável por mapa do salão, mesas, assentos, grupos, holds temporários, confirmações e conflitos.

### 3.5 Engajamento

Responsável por enquetes, opções, votos, janelas de votação e resultados.

### 3.6 Extras e Cobrança Operacional

Responsável por convites extras, upgrades de mesa, produtos adicionais e seus pagamentos.

### 3.7 Comunicação e Auditoria

Responsável por templates, eventos de comunicação, disparos, status, reenvio, logs e trilha de auditoria.

---

## 4. Jornadas Principais

### 4.1 Jornada do formando

1. Recebe acesso à turma/evento.
2. Realiza adesão ou vincula-se à adesão existente.
3. Acompanha pagamento e status.
4. Gerencia convidados e visualiza cotas.
5. Compra extras se elegível.
6. Seleciona mesa/assentos quando a janela abrir.
7. Responde ou participa de enquetes.
8. Recebe atualizações do evento.

### 4.2 Jornada do convidado

1. Recebe convite por link/token.
2. Valida identidade mínima.
3. Responde presença.
4. Opcionalmente visualiza assento ou seleciona lugar quando permitido.
5. Recebe confirmação e instruções.

### 4.3 Jornada da comissão

1. Acompanha adesão e status de turma.
2. Monitora convites emitidos e pendências de RSVP.
3. Acompanha ocupação de mesas.
4. Publica ou acompanha enquetes.
5. Atua em filas de aprovação quando autorizado.

### 4.4 Jornada do administrador

1. Configura estrutura acadêmica e eventos.
2. Define produtos, quotas, regras e permissões.
3. Libera janelas operacionais.
4. Monitora pagamentos, RSVP, seating e comunicação.
5. Exporta dados, audita ocorrências e corrige exceções.

---

## 5. Mapeamento de Entidades

### 5.1 Diagrama ER textual

```text
Organizacao
  └──< Instituicao
          └──< Turma >── Curso
                 └──< Evento
                        ├──< LoteConvite
                        │     └──< Convite >── Convidado
                        │              └── RSVP
                        ├──< MapaMesa
                        │     └──< Mesa
                        │            └──< Assento
                        │                   └── ReservaAssento
                        ├──< Enquete
                        │     └──< OpcaoEnquete
                        │             └──< Voto
                        ├──< ProdutoExtra
                        │     └──< PedidoExtra
                        │             └──< PedidoExtraItem
                        └──< Notificacao

Turma
  └──< Formando
         ├──< Adesao
         │     ├──< Parcela
         │     │     └──< Pagamento
         │     └──< AdesaoProduto
         ├──< FormandoConvidado
         └──< ReservaAssento

Users e acesso
AdminUser
ComissaoUser
PortalUser >──< Formando
```

### 5.2 Entidades principais

#### Organizacao

Representa a empresa organizadora ou cliente dono da operação. É o contêiner lógico de configurações, acessos, integrações e segregação operacional.

#### Instituicao, Curso e Turma

Modelam o contexto acadêmico. Turma é a unidade de agrupamento do formando, mas um evento pode reunir uma ou várias turmas.

#### Evento

Novo agregado central do v4. Toda operação social e logística orbita o evento: convites, RSVP, mesas, enquetes, extras e comunicação.

#### Formando

Representa o participante principal. Mantém vínculo com turma, usuário do portal, adesão, convidados e reservas.

#### Adesao

Registro da contratação comercial do formando. Possui snapshots de regras comerciais no momento da contratação.

#### Convite

Unidade operacional de acesso de convidado. Pode ser nominal ou transferível, gratuito ou pago, com ou sem assento vinculado, com status rastreável e histórico de entrega.

#### RSVP

Resposta do convidado ao convite. Não substitui o convite; complementa seu ciclo de vida.

#### MapaMesa, Mesa, Assento e ReservaAssento

Estrutura física do salão e a relação transacional de ocupação. `ReservaAssento` é a entidade que resolve concorrência, hold, confirmação, cancelamento e histórico.

#### Enquete, OpcaoEnquete e Voto

Domínio de engajamento. Cada enquete define elegibilidade, janela temporal, cardinalidade do voto e política de edição.

#### ProdutoExtra e PedidoExtra

Domínio de venda operacional pós-adesão: convites extras, upgrades, kits, itens adicionais.

### 5.3 Cardinalidades relevantes

| Relação                  | Cardinalidade                           |
| ------------------------ | --------------------------------------- |
| Instituição → Turmas     | 1:N                                     |
| Turma → Formandos        | 1:N                                     |
| Evento → Turmas          | N:N ou 1:N, conforme modelo operacional |
| Formando → Adesões       | 1:N histórico, 1:1 ativa por evento     |
| Evento → Convites        | 1:N                                     |
| Convite → RSVP           | 1:0..1 ativo, N histórico de mudanças   |
| Evento → Mesas           | 1:N                                     |
| Mesa → Assentos          | 1:N                                     |
| Assento → ReservaAssento | 1:N histórico, 0..1 ativa               |
| Evento → Enquetes        | 1:N                                     |
| Enquete → Votos          | 1:N                                     |
| Formando → PedidosExtra  | 1:N                                     |

### 5.4 Atributos principais por entidade

#### Evento

- `nome`
- `slug`
- `status`
- `data_evento`
- `local_nome`
- `timezone`
- `abre_rsvp_at`
- `abre_mesas_at`
- `fecha_mesas_at`
- `config_json`

#### Convite

- `codigo`
- `tipo`
- `status`
- `formando_id`
- `evento_id`
- `convidado_nome`
- `convidado_email`
- `convidado_telefone`
- `token_acesso`
- `is_extra`
- `pedido_extra_id`
- `entregue_at`
- `confirmado_at`

#### ReservaAssento

- `evento_id`
- `mesa_id`
- `assento_id`
- `convite_id`
- `formando_id`
- `status`
- `hold_expires_at`
- `confirmado_at`
- `origin`
- `idempotency_key`

#### Enquete

- `titulo`
- `descricao`
- `tipo`
- `status`
- `abre_at`
- `fecha_at`
- `regra_elegibilidade`
- `permite_edicao`

---

## 6. Regras de Negócio Core

### 6.1 Adesão comercial

O v4 preserva a base sólida do v3 para adesão:

- contratos, pacotes, descontos, programações e parcelamento continuam como núcleo comercial
- a adesão gera snapshots de preço, condição, desconto, termo e contexto do contrato
- alterações posteriores de preço não retroagem sobre adesões concluídas, salvo política explícita de reajuste

### 6.2 Gestão de cotas de convites

Cada evento define uma política de cota por formando. A cota pode ser composta por:

- convites inclusos na adesão
- convites condicionais por lote
- convites extras pagos
- reservas operacionais para comissão ou staff

Regras:

1. Convite incluso e convite extra são coisas distintas, mas compartilham ciclo operacional.
2. Um convite só entra em uso após emissão e entrega.
3. Transferência de convite gera trilha de auditoria.
4. Cancelamento de convite pode devolver cota, conforme janela configurada.
5. O sistema precisa impedir oversubscription por formando e por evento.

### 6.3 RSVP

O ciclo de RSVP deve contemplar:

- `nao_enviado`
- `enviado`
- `visualizado`
- `confirmado`
- `recusado`
- `pendente_revisao`
- `cancelado`

Regras:

1. O convite não precisa virar conta de usuário completa para o convidado responder.
2. O token do convite deve ser de uso seguro e revogável.
3. Uma resposta posterior pode substituir a anterior somente enquanto a janela estiver aberta.
4. Mudança manual feita por admin ou comissão deve ficar auditada.

### 6.4 Escolha de mesas e concorrência

Esta é a regra mais sensível do v4.

#### Janela operacional

- seleção de mesas só abre quando `abre_mesas_at <= now()`
- após `fecha_mesas_at`, só admin ou operação podem alterar

#### Hold temporário

- ao iniciar escolha de assento, o sistema cria um hold com expiração curta
- hold padrão recomendado: 5 minutos
- holds expirados liberam o assento automaticamente

#### Regra transacional

- só pode existir uma reserva ativa por assento
- a confirmação final deve ocorrer dentro de transação e com proteção por chave única/lock
- o frontend nunca é fonte de verdade da ocupação

#### Políticas possíveis

- assento individual
- bloco de assentos por formando
- mesa inteira por família/grupo

O produto deve suportar as três por configuração, mas o MVP deve começar com **assento individual + agrupamento visual por mesa**.

### 6.5 Venda de convites extras

Convite extra é tratado como pedido comercial operacional.

Regras:

1. O produto extra pode ter estoque, janela, preço e regra de elegibilidade.
2. A venda pode ser automática ou exigir aprovação.
3. Enquanto não pago, o pedido não cria convite utilizável.
4. Webhook confirmado converte pedido em convites emitíveis.
5. Estorno ou cancelamento deve invalidar convites ainda não usados.

### 6.6 Enquetes e votações

Tipos suportados:

- enquete simples de uma opção
- enquete múltipla escolha
- votação por ranking futuro

Regras:

1. Elegibilidade pode ser por formando, comissão ou convidado confirmado.
2. Uma pessoa só vota uma vez por enquete, salvo `permite_edicao=true`.
3. O resultado pode ser público, parcial ou apenas administrativo.
4. Toda votação precisa de carimbo temporal e trilha mínima de origem.

### 6.7 Aprovação e rejeição

Fluxos que podem exigir fila de aprovação:

- compra de convites extras fora da regra padrão
- liberação manual de cota adicional
- alteração administrativa após fechamento de janela
- troca excepcional de assento
- emissão manual de convite não previsto

Estados recomendados:

- `rascunho`
- `pendente_aprovacao`
- `aprovado`
- `rejeitado`
- `cancelado`
- `executado`

### 6.8 Comunicação

Todo domínio relevante deve emitir eventos de negócio:

- adesão concluída
- pagamento confirmado
- convite emitido
- RSVP confirmado
- assento reservado
- pedido extra aprovado
- enquete aberta/encerrada

Esses eventos alimentam e-mail, push, logs e dashboards.

### 6.9 Capacidades funcionais mínimas por módulo

#### Cadastro e operação de evento

O sistema precisa permitir configurar o evento de forma completa e auditável. Isso inclui local, data, timezone, janelas operacionais, regras de RSVP, política de seating, lotes de convites e catálogo de extras. A configuração não pode ficar espalhada por telas sem contrato; ela deve compor um modelo de evento claro, versionável e consultável.

Capacidades mínimas:

- criar e editar evento
- associar uma ou mais turmas ao evento
- configurar janela de RSVP
- configurar janela de seating
- configurar cotas e lotes
- bloquear áreas/mesas/setores

#### Convites

Convites precisam ser tratados como entidade operacional de primeira classe. O sistema não pode depender apenas do “nome do convidado em planilha” ou de envios ad hoc.

Capacidades mínimas:

- emissão unitária
- emissão em lote
- reenvio
- cancelamento
- transferência quando política permitir
- rastreamento de visualização e resposta

#### RSVP

RSVP não é apenas um campo booleano. O produto precisa operar o funil completo:

- convite emitido
- convite visualizado
- pendência de resposta
- resposta confirmada ou recusada
- exceção ou intervenção administrativa

#### Seating

O seating precisa nascer já preparado para disputa de recurso. Isso significa que a interface pode ser elegante, mas o backend deve ser explicitamente transacional.

Capacidades mínimas:

- consulta do mapa
- hold temporário
- confirmação final
- troca controlada
- liberação automática por expiração

#### Extras

O domínio de extras precisa fechar o ciclo inteiro:

- catálogo
- elegibilidade
- estoque
- aprovação quando aplicável
- pagamento
- emissão operacional derivada

#### Enquetes

Enquetes devem ser tratadas como capability de engajamento, não apenas como formulário solto:

- criação
- publicação
- janela
- elegibilidade
- votação
- resultado

### 6.10 Fluxos ponta a ponta prioritários

#### Fluxo 1 — Adesão até liberação operacional

1. Formando conclui adesão.
2. Sistema confirma pagamento ou condição mínima.
3. Sistema ativa a relação do formando com o evento.
4. Sistema calcula e libera cotas de convites.
5. Sistema habilita módulos dependentes, como extras e enquetes.

Esse fluxo é importante porque transforma o comercial em capacidade operacional. Sem ele, o restante do produto fica inconsistente.

#### Fluxo 2 — Convite até RSVP

1. Convite é emitido.
2. Convite é enviado por canal configurado.
3. Convidado acessa o link/token.
4. O sistema registra visualização.
5. O convidado confirma ou recusa.
6. O sistema atualiza contadores e dashboards.

O sistema deve continuar íntegro mesmo se o convidado acessar mais de uma vez, abandonar o fluxo ou responder fora da janela.

#### Fluxo 3 — RSVP até seating

1. RSVP confirmado marca elegibilidade.
2. Na abertura da janela, o convidado/formando acessa o mapa.
3. O sistema exibe disponibilidade atual.
4. O usuário tenta reservar um assento.
5. O backend cria hold.
6. O usuário confirma.
7. O assento é definitivamente reservado.

Esse fluxo exige a combinação de UX clara com garantias fortes de consistência.

#### Fluxo 4 — Pedido extra até emissão

1. Formando escolhe extra.
2. Regra de elegibilidade é validada.
3. Se necessário, pedido entra em aprovação.
4. Pagamento é iniciado.
5. Webhook confirma.
6. Sistema emite convites extras ou libera o recurso comprado.

Esse fluxo fecha a monetização operacional do evento e não pode depender de baixa manual como padrão.

---

## 7. Catálogo de Componentes UI

### 7.1 Estratégia geral

O produto passa a ter dois universos de interface:

- **Admin:** usa Inspinia + Blade/Livewire + catálogo já em construção no repositório
- **Cliente web e mobile:** usam design system compartilhável, orientado a React e React Native

### 7.2 Camadas de UI

#### Camada 1 — Design tokens

Compartilhada conceitualmente entre admin, web e mobile:

- cores semânticas
- tipografia
- espaçamentos
- radius
- sombras
- estados de feedback
- ícones por status

#### Camada 2 — Componentes compartilhados web/mobile

- button
- input
- select
- badge
- card
- stepper
- status-pill
- empty-state
- list-row
- seat-chip
- invite-card
- poll-card
- payment-summary

#### Camada 3 — Componentes específicos do admin

Com base no trabalho existente no repositório:

- `x-admin.layout`
- `x-admin.sidebar`
- `x-admin.topbar`
- `x-admin.page-header`
- `x-admin.data-table`
- `x-admin.drawer`
- `x-admin.kpi-card`
- `x-admin.chart-*`

#### Camada 4 — Componentes específicos do cliente web/mobile

- onboarding de formando
- carteira de convites
- timeline de RSVP
- mapa de mesas interativo
- checkout de extras
- central de notificações

### 7.3 Estado atual do boilerplate

O repositório já possui boa maturidade de UI administrativa:

- catálogo Inspinia consolidado
- cerca de 57 componentes admin/shared marcados como implementados no catálogo local
- previews visuais em `/admin/dev/components`
- portal React/mobile ainda inexistentes

Conclusão: o investimento de UI já feito deve ser preservado para o admin e não substituído.

### 7.4 Diretriz de consistência

Mesmo sem compartilhar o mesmo framework visual entre admin e cliente, o produto deve compartilhar:

- naming de estados
- semântica de ações
- sistema de ícones
- estrutura de feedback
- status operacional
- guidelines de acessibilidade

---

## 8. Stack Recomendada

### 8.1 Backend e plataforma

| Camada                | Recomendação              |
| --------------------- | ------------------------- |
| Backend               | Laravel 13                |
| Banco                 | PostgreSQL 16             |
| Cache, sessão e locks | Redis                     |
| Queue orchestration   | Horizon                   |
| Monitoramento interno | Pulse                     |
| Auth SPA/mobile       | Sanctum                   |
| Permissões            | Spatie Laravel Permission |
| Auditoria             | Spatie Activitylog        |
| Exportação            | Laravel Excel             |
| PDFs                  | DomPDF                    |

### 8.2 Cliente web

| Camada                | Recomendação      |
| --------------------- | ----------------- |
| Framework             | React             |
| Roteamento            | React Router 7    |
| Estado de servidor    | TanStack Query 5  |
| Estado local/UI       | Zustand 5         |
| Formulários           | React Hook Form 7 |
| Validação             | Zod 4             |
| HTTP                  | Axios 1           |
| UI kit cross-platform | Tamagui 2         |

### 8.3 Mobile

| Camada                         | Recomendação                         |
| ------------------------------ | ------------------------------------ |
| Framework                      | React Native com Expo                |
| Roteamento                     | Expo Router                          |
| Estado de servidor             | TanStack Query 5                     |
| Estado local/UI                | Zustand 5                            |
| Push                           | Expo Notifications                   |
| Storage geral                  | MMKV                                 |
| Storage compatível/ecossistema | AsyncStorage quando exigido por libs |

### 8.4 Admin

| Camada      | Recomendação                                |
| ----------- | ------------------------------------------- |
| Framework   | Laravel Blade + Livewire 4                  |
| Base visual | Inspinia + Tailwind 4                       |
| Tabelas     | `x-admin.data-table`                        |
| Feedback    | `x-shared.toast`, `x-shared.confirm-dialog` |

### 8.5 Observação importante

O repositório e a documentação interna ainda carregam referências a Livewire para o portal. No v4, isso deixa de ser decisão de produto e passa a ser apenas legado do boilerplate. O portal externo deve ser tratado como **cliente React**.

---

## 9. Requisitos Não Funcionais

### 9.1 Segurança

- autenticação segregada por perfil e canal
- Sanctum para SPA e mobile
- tokens de convite curtos, revogáveis e auditáveis
- autorização por policy e permissão granular
- proteção LGPD para dados pessoais de formandos e convidados
- rate limiting em login, convite e RSVP
- idempotência em pagamentos, reservas e webhooks

### 9.2 Escalabilidade

- múltiplos eventos simultâneos
- jobs desacoplados para envio, geração de PDF, reconciliação e notificações
- cache seletivo para mapas, contadores e lookups
- índices dedicados para consultas por evento, status e janela temporal

### 9.3 Performance

- P95 de API crítica abaixo de 500 ms
- queries com eager loading e paginação cursor onde fizer sentido
- invalidação de cache orientada a evento
- páginas de admin com filtros e exportações desacopladas do request síncrono quando necessário

### 9.4 Disponibilidade

- meta de uptime 99,5%
- fallback para reprocessamento de webhook
- observabilidade mínima com logs estruturados, fila de falhas e error tracking
- backup de banco com política definida de retenção

### 9.5 Acessibilidade

- aderência mínima a WCAG 2.1 AA
- navegação por teclado em fluxos essenciais
- feedback textual e não apenas por cor
- contrastes adequados
- áreas touch compatíveis com mobile

### 9.6 Governança de dados e versionamento

O v4 precisa distinguir claramente entre dados mestres e dados transacionais.

#### Dados mestres

São dados configuráveis, que podem evoluir com o tempo:

- evento
- turma
- lote
- produto
- regra de cota
- configuração de janela

#### Dados transacionais

São fatos históricos que precisam preservar o contexto do momento:

- adesão concluída
- pagamento recebido
- convite emitido
- RSVP registrado
- reserva confirmada
- voto realizado
- pedido extra aprovado

#### Implicação prática

Sempre que um fato transacional depender de um conjunto de regras que possam mudar depois, o sistema deve persistir snapshot ou referência versionada suficiente para reconstrução histórica.

Exemplos obrigatórios:

- preço da adesão
- termo aceito
- regra de convite aplicada
- condição de extra no momento da compra
- composição da reserva de assento confirmada

### 9.7 Estratégia de rollout

Este produto não deve estrear em “big bang”. A recomendação é rollout controlado:

- habilitar features por evento piloto
- usar flags para seating, extras e enquetes
- liberar fluxos externos em ondas
- monitorar primeiro ciclo completo antes de escalar

Isso reduz risco em áreas sensíveis como concorrência de assento e reprocessamento de pagamento.

---

## 10. Roadmap de Implementação

### 10.1 Fases

| Fase | Objetivo                                   |
| ---- | ------------------------------------------ |
| F1   | Fundação de domínio e API-ready backend    |
| F2   | Admin core aproveitando Inspinia existente |
| F3   | Cliente web React para formando/comissão   |
| F4   | Convites, RSVP e comunicação               |
| F5   | Seating e concorrência de assentos         |
| F6   | Extras, pagamentos operacionais e enquetes |
| F7   | Mobile React Native                        |
| F8   | Hardening, observabilidade e go-live       |

### 10.2 Dependências macro

1. O domínio de evento precisa existir antes de convites e seating.
2. A API v1 precisa existir antes da área React.
3. Pagamentos e comunicação precisam de eventos de domínio confiáveis.
4. Seating depende de identidade de convidados e RSVP minimamente estruturados.
5. O mobile depende de contratos estáveis da API, não do admin.

### 10.3 Story points por macrocapacidade

| Macrocapacidade                  | Estimativa |
| -------------------------------- | ---------: |
| Fundação backend e auth          |         34 |
| Admin estrutural e CRUDs base    |         40 |
| Cliente web React                |         34 |
| Convites e RSVP                  |         28 |
| Mapa de mesas                    |         34 |
| Extras e pagamentos operacionais |         21 |
| Enquetes e votações              |         13 |
| Observabilidade e hardening      |         13 |
| Mobile MVP                       |         34 |

### 10.4 Critério de corte para MVP

O MVP operacional do v4 deve incluir:

- cadastro de evento
- adesão e acompanhamento do formando
- convites e RSVP
- seleção de mesa com hold
- compra de convites extras
- dashboards essenciais

Enquetes podem entrar no MVP estendido se a equipe mantiver folga de capacidade.

Para o MVP ser considerado realmente pronto, não basta a tela existir. Também precisam estar validados:

- auditoria mínima dos fluxos críticos
- monitoramento de fila e webhook
- testes dos cenários de concorrência relevantes
- trilha operacional suficiente para suporte e atendimento

---

## 11. Principais Mudanças em Relação ao v3.1.0

1. O eixo do produto passa de “portal de adesão” para “plataforma de evento”.
2. O evento vira agregado explícito do domínio.
3. Convites, RSVP, seating e enquetes saem do backlog e entram no core.
4. A arquitetura deixa de ser implicitamente Livewire-first e passa a ser explicitamente API-first para canais externos.
5. O admin reaproveita o investimento real já feito em Inspinia e Blade.
6. O modelo de dados cresce para suportar convidados, assentos, reservas e pedidos extras.
7. Os requisitos não funcionais ficam orientados a concorrência, idempotência, observabilidade e LGPD.

---

## 12. Assunções Documentadas

1. PostgreSQL continua sendo o banco recomendado.
2. O produto permanecerá como monólito modular no horizonte de MVP e escala inicial.
3. O admin continuará em Laravel/Blade/Livewire por razões de aproveitamento do trabalho já realizado.
4. A área do cliente web será React e o mobile compartilhará a mesma API.
5. O gateway financeiro pode continuar sendo Itaú no contexto atual, mas o desenho do domínio não deve depender de um único provedor.

---

## 13. Perguntas Pendentes

1. O convidado poderá comprar extras diretamente ou apenas via formado responsável?
2. A comissão terá permissão de aprovar compras extras e trocas de assento?
3. Haverá vários salões/mapas por evento?
4. O sistema precisa suportar mais de um evento por formando no mesmo período?
5. Existe exigência contratual de SLA acima de 99,5%?

---

## 14. Referências

### Repositório e documentação interna

- `docs/PRD_Sistema_Formatura_v3.1.0.md`
- `docs/01-ARCHITECTURE-GUIDE.md`
- `docs/INSPINIA-CATALOGO-COMPONENTES.md`
- `docs/INSPINIA-MAPA-TELAS-COMPONENTES.md`
- `docs/04-TEMPLATE-MAP-AND-COMPONENTS.md`
- `CLAUDE.md`

### Laravel e ecossistema

- [Laravel Authentication](https://laravel.com/docs/13.x/authentication)
- [Laravel Routing / API Routes](https://laravel.com/docs/13.x/routing#api-routes)
- [Laravel Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Laravel Horizon](https://laravel.com/docs/13.x/horizon)
- [Laravel Pulse](https://github.com/laravel/pulse)
- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Laravel Activitylog](https://github.com/spatie/laravel-activitylog)
- [Spatie Laravel Medialibrary](https://github.com/spatie/laravel-medialibrary)
- [Laravel Excel](https://github.com/SpartnerNL/Laravel-Excel)
- [Barryvdh DomPDF](https://github.com/barryvdh/laravel-dompdf)
- [Saloon Laravel Plugin](https://github.com/saloonphp/laravel-plugin)

### Frontend e mobile

- [Tamagui](https://tamagui.dev/docs/intro/introduction)
- [NativeWind](https://www.nativewind.dev/)
- [gluestack-ui](https://gluestack.io/ui/docs/home/overview/introduction)
- [React Native Paper](https://reactnativepaper.com/)
- [TanStack Query](https://tanstack.com/query/latest)
- [React Router](https://reactrouter.com/home)
- [React Hook Form](https://react-hook-form.com/)
- [Zod](https://zod.dev/)
- [Axios](https://axios-http.com/docs/intro)
- [Expo Router](https://docs.expo.dev/router/introduction/)
- [Expo Notifications](https://docs.expo.dev/versions/latest/sdk/notifications/)
- [AsyncStorage](https://react-native-async-storage.github.io/async-storage/)
- [React Native MMKV](https://github.com/mrousavy/react-native-mmkv)
