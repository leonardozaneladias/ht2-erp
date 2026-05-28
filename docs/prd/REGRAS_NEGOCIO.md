# Regras de Negócio

## 1. Objetivo

Este documento especifica as regras de negócio que não podem ficar implícitas em tela, serviço ou integração. Ele serve como referência para modelagem, API, testes, permissões e UX.

## 2. Regras gerais do domínio

### 2.1 Hierarquia operacional

1. A organizadora administra o sistema.
2. A turma reúne formandos.
3. O evento é o centro operacional de convites, RSVP, mesas, extras e enquetes.
4. O formando é o dono primário da sua adesão e da sua cota.
5. O convidado existe por convite, não por cadastro obrigatório.

### 2.2 Janela operacional

Toda funcionalidade sensível pode ser condicionada por janela:

- adesão
- compra de extras
- RSVP
- escolha de mesas
- enquetes

Fora da janela:

- usuário comum não altera
- comissão altera apenas se tiver permissão
- admin pode atuar com justificativa

### 2.3 Fonte de verdade

- tabela e estado persistido são fonte de verdade
- frontend nunca confirma sozinho disponibilidade de assento, cota ou pagamento
- integrações externas nunca mudam estado sem passar por action idempotente

## 3. Adesão e status comercial

### 3.1 Status de adesão

Estados mínimos:

- `rascunho`
- `pendente_pagamento`
- `ativa`
- `cancelada`
- `inadimplente`
- `concluida`

### 3.2 Regras

1. Uma adesão ativa pertence a um formando em um evento/turma.
2. O forming pode ter histórico de adesões, mas somente uma ativa por contexto operacional.
3. O snapshot comercial da adesão não deve ser alterado retroativamente.
4. Parcelas e pagamentos são derivados da adesão confirmada.

## 4. Cotas de convites

### 4.1 Conceitos

- **cota base:** convites inclusos por regra do pacote/contrato
- **cota bônus:** convites concedidos por decisão administrativa
- **cota extra:** convites adquiridos em pedido pago
- **cota reservada:** convites bloqueados para operação/comissão

### 4.2 Regras

1. O sistema calcula disponibilidade como:

`cota_total - convites_emitidos_ativos - convites_reservados`

2. Cancelar convite pode ou não devolver cota, conforme política do evento.
3. Transferir convite não altera cota, apenas a identidade operacional do convidado.
4. Convite pago e já utilizado não volta para a cota automaticamente.
5. Convite extra aprovado mas não pago não gera direito operacional.

## 5. Convites

### 5.1 Tipos

- nominal
- transferível
- cortesia
- staff
- extra pago

### 5.2 Status do convite

- `rascunho`
- `emitido`
- `enviado`
- `visualizado`
- `confirmado`
- `recusado`
- `cancelado`
- `inutilizado`

### 5.3 Regras

1. Um convite precisa estar ao menos em `emitido` para ser acessível externamente.
2. O link/token de convite deve poder ser revogado.
3. Convite `cancelado` não pode participar de RSVP nem seating.
4. Convite `confirmado` pode ser pré-requisito para seating, conforme configuração do evento.
5. Convite extra herda vínculo com o pedido que o originou.

## 6. RSVP

### 6.1 Estado

- `pendente`
- `confirmado`
- `recusado`
- `cancelado`
- `expirado`

### 6.2 Regras

1. RSVP é sempre vinculado a um convite.
2. O convite pode existir sem RSVP confirmado.
3. Respostas podem ser alteradas até o fechamento da janela.
4. Uma alteração tardia gera log com ator, data e motivo.
5. Se o evento exigir confirmação para liberar seating, apenas RSVP `confirmado` conta como elegível.

### 6.3 Regras de UX

- convidado deve conseguir responder em poucos passos
- o fluxo não pode exigir cadastro completo para simples confirmação
- o sistema deve exibir mensagem clara quando a janela estiver fechada

## 7. Mapa de mesas e assentos

## 7.1 Entidades

- mapa
- setor
- mesa
- assento
- reserva
- hold

### 7.2 Status da reserva

- `hold`
- `confirmada`
- `cancelada`
- `expirada`
- `bloqueada`

### 7.3 Regras de concorrência

1. Um assento só pode ter uma reserva ativa por vez.
2. Toda tentativa de reserva cria `idempotency_key`.
3. O backend valida novamente a disponibilidade no commit final.
4. Holds expiram automaticamente.
5. A expiração devolve disponibilidade imediatamente.

### 7.4 Regras de negócio

1. O evento pode operar por:
    - assento individual
    - bloco de assentos
    - mesa inteira
2. O MVP começa com assento individual.
3. Formando não pode reservar mais assentos que sua disponibilidade operacional.
4. Convite cancelado invalida reserva associada, salvo override administrativo.
5. Troca de assento após confirmação exige:
    - liberação do antigo
    - nova validação do destino
    - log de auditoria

### 7.5 Regras administrativas

1. Admin pode bloquear mesa ou assento.
2. Comissão só pode editar mapa se tiver permissão explícita.
3. Operação pode atuar em modo assistido no dia do evento.

## 8. Venda de convites extras

### 8.1 Estado do pedido extra

- `rascunho`
- `pendente_aprovacao`
- `aguardando_pagamento`
- `pago`
- `cancelado`
- `expirado`
- `estornado`

### 8.2 Regras

1. Pedido pode exigir aprovação antes do checkout.
2. Estoque pode ser:
    - ilimitado
    - por evento
    - por lote
    - por formando
3. Somente pedido `pago` gera convites operacionais.
4. Estorno pode invalidar convites ainda não utilizados.
5. Admin pode gerar pagamento manual, mas isso precisa de justificativa.

## 9. Pagamentos

### 9.1 Estados

- `criado`
- `pendente`
- `autorizado`
- `pago`
- `falhou`
- `cancelado`
- `estornado`

### 9.2 Regras

1. Webhook externo não pode aplicar efeito duas vezes.
2. Falha de comunicação não implica falha de pagamento sem reconciliação.
3. Status de pagamento impacta:
    - adesão
    - pedido extra
    - emissão de convite extra
4. Toda mudança de estado deve gravar evento.

## 10. Enquetes e votações

### 10.1 Tipos

- escolha simples
- múltipla escolha
- ranqueamento futuro

### 10.2 Elegibilidade

Possíveis bases:

- formando com adesão ativa
- comissão
- convidado com RSVP confirmado
- subconjunto do evento

### 10.3 Regras

1. Cada enquete define se voto é secreto ou auditável.
2. Cada enquete define se resultado é público.
3. Cada ator elegível só pode votar conforme cardinalidade configurada.
4. Voto fora da janela é rejeitado.
5. Se `permite_edicao=false`, o primeiro voto fecha a ação do ator.

## 11. Aprovação e rejeição

### 11.1 Objetos que podem entrar em aprovação

- pedido extra
- aumento de cota
- troca excepcional de assento
- emissão manual de convite
- participação tardia em enquete fechada

### 11.2 Regras

1. Toda aprovação precisa de ator e timestamp.
2. Toda rejeição precisa de motivo.
3. Mudança posterior de decisão cria nova versão do histórico; não sobrescreve.

## 12. Notificações

### 12.1 Gatilhos

- convite emitido
- RSVP pendente há X dias
- RSVP confirmado
- janela de mesas aberta
- hold próximo de expirar
- pagamento aprovado
- compra extra aprovada
- enquete aberta

### 12.2 Regras

1. Notificação nunca substitui estado de domínio.
2. Reenvio é permitido, mas auditado.
3. Canal pode variar por perfil:
    - e-mail
    - push
    - SMS/WhatsApp futuro

## 13. Exceções operacionais

### 13.1 Exemplos

- convidado sem e-mail
- convidado duplicado
- pagamento aprovado sem webhook
- reserva órfã por falha de client
- mesa bloqueada após reservas já existentes

### 13.2 Regra

Toda exceção operacional precisa:

- aparecer em fila administrativa
- permitir resolução manual controlada
- registrar o que aconteceu antes e depois

## 14. Regras de auditoria

Devem ser auditados obrigatoriamente:

- emissão, cancelamento e transferência de convite
- confirmação e alteração de RSVP
- criação, expiração, confirmação e cancelamento de reserva
- aprovação, rejeição e cancelamento de pedido extra
- baixa manual de pagamento
- alteração administrativa após fechamento de janela

## 15. Regras para testes

Cada regra de negócio deste documento precisa de ao menos um dos seguintes tipos de teste:

- unit test de rule/action
- feature test de endpoint
- teste de integração com webhook simulado

Prioridade máxima de cobertura:

1. concorrência de assento
2. idempotência de pagamento
3. cálculo de cota
4. emissão de convite extra após pagamento
5. elegibilidade de enquete
