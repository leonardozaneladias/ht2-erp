# Roadmap de Implementação

## 1. Premissas

- arquitetura híbrida API-first
- admin em Blade/Livewire/Inspinia
- cliente web em React
- mobile em React Native como fase posterior, sem refazer backend
- story points usados apenas como referência relativa

## 2. Macroplanejamento

| Fase | Objetivo                                       | Story Points |
| ---- | ---------------------------------------------- | -----------: |
| F1   | Fundação de domínio, auth e base técnica       |           34 |
| F2   | Admin estrutural e cadastros base              |           40 |
| F3   | Cliente web React e jornada do formando        |           34 |
| F4   | Convites e RSVP                                |           28 |
| F5   | Seating e concorrência                         |           34 |
| F6   | Extras, pagamentos operacionais e enquetes     |           34 |
| F7   | Hardening, observabilidade e relatórios finais |           21 |
| F8   | Mobile MVP                                     |           34 |

## 3. Fase 1 — Fundação

### Objetivos

- modelar evento como agregado central
- criar `api/v1`
- preparar autenticação por perfil
- consolidar estrutura de actions/DTOs

### Entregas

- migrations core
- models base
- guards/admin + sanctum
- policies mínimas
- actions e DTOs do domínio
- roteamento `admin.php`, `api/v1.php`, `webhook.php`

### Dependências

- decisão final sobre multi-evento por turma
- definição do provedor inicial de pagamentos

## 4. Fase 2 — Admin estrutural

### Objetivos

- aproveitar a base Inspinia pronta
- entregar cadastros de operação

### Entregas

- login admin
- gestão de usuários e permissões
- CRUD de instituição, curso, turma e evento
- CRUD de pacotes/produtos e regras comerciais
- dashboard inicial

### Story points sugeridos

| Módulo                |  SP |
| --------------------- | --: |
| Auth admin + ACL      |   8 |
| Cadastros estruturais |  13 |
| Comercial base        |  13 |
| Dashboard inicial     |   6 |

## 5. Fase 3 — Cliente web React

### Objetivos

- habilitar a experiência moderna do formando/comissão
- validar a API em canal externo real

### Entregas

- auth cliente
- dashboard do formando
- extrato e visão de adesão
- carteira de convites
- base visual compartilhável para mobile

### Risco principal

Subestimar o esforço de design system e tentar reaproveitar conceitos do admin diretamente no React.

## 6. Fase 4 — Convites e RSVP

### Entregas

- políticas de cota
- emissão unitária e em lote
- templates de convite
- tokenização segura
- fluxo RSVP
- dashboards de acompanhamento

### Critério de aceite

- convite enviado
- convidado consegue responder
- comissão/admin conseguem acompanhar

## 7. Fase 5 — Seating

### Entregas

- modelagem do mapa
- editor administrativo de mesas
- leitura do mapa no cliente
- hold temporário
- confirmação de assento
- fila de exceções

### Critério de aceite

- assento não pode ser duplamente confirmado em cenário concorrente

### Observação

Esta é a fase de maior risco técnico e merece tempo de testes acima da média.

## 8. Fase 6 — Extras, pagamentos operacionais e enquetes

### Entregas

- catálogo de extras
- fluxo de pedido extra
- pagamento e webhook
- emissão derivada de convite extra
- enquetes com votação

### Subpriorização

1. convites extras
2. pagamento operacional
3. enquetes

## 9. Fase 7 — Hardening e observabilidade

### Entregas

- dashboards finais
- relatórios operacionais
- monitoramento
- auditoria expandida
- revisão de performance
- revisão de segurança

## 10. Fase 8 — Mobile MVP

### Escopo sugerido

- login
- dashboard do formando
- carteira de convites
- RSVP
- seating simplificado
- notificações push

### Fora do escopo inicial do mobile

- edição administrativa pesada
- relatórios
- edição estrutural do evento

## 11. Dependências críticas

| Dependência                        | Impacto                                |
| ---------------------------------- | -------------------------------------- |
| regra de elegibilidade de convites | bloqueia emissão e RSVP                |
| política de seating                | bloqueia modelagem de reserva          |
| política de extras                 | bloqueia pedido e pagamento            |
| escolha do provedor de push        | afeta mobile                           |
| decisão de multi-organização       | afeta autorização e estrutura de dados |

## 12. Sequência recomendada de entrega

1. fundação backend
2. admin estrutural
3. cliente web React
4. convites e RSVP
5. seating
6. extras e enquetes
7. hardening
8. mobile

## 13. Marco de MVP

O MVP executivo acontece ao final da Fase 5, quando houver:

- evento configurável
- adesão/consulta do formando
- convites e RSVP
- mapa de mesas com reserva confiável

O MVP comercial ampliado acontece ao final da Fase 6 com extras pagos.

## 14. Riscos

| Risco                                               | Probabilidade | Impacto | Mitigação                                          |
| --------------------------------------------------- | :-----------: | :-----: | -------------------------------------------------- |
| escopo crescer durante a Fase 3                     |     alta      |  alto   | congelar MVP por capability                        |
| seating exigir regras mais complexas que o esperado |     média     |  alto   | modelar política configurável, mas começar simples |
| mobile pressionar mudanças de API tardias           |     média     |  médio  | validar API com React web antes                    |
| comissão pedir permissões excessivas                |     média     |  médio  | policy-first                                       |

## 15. Próximos passos

1. Validar este roadmap com produto e operação.
2. Fechar as perguntas pendentes do PRD v4.
3. Converter as fases em épicos/issues no Plane.
4. Iniciar pela fundação de domínio e `api/v1`.
