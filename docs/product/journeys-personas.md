---
title: Jornadas e Personas — Portal ArtFinal v2
version: 1.0.0
date: 2026-04-18
status: draft
---

# Jornadas e Personas — Portal ArtFinal v2

> Mapeamento de 6 personas-chave com perfil, tarefas, touchpoints, dores, ganhos esperados e jornada em tabela. Base para design de telas, testes de UX e priorização.
> Fontes: [`PRD_v4.md`](../prd/PRD_v4.md), [`REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md), [`PLANEJAMENTO_BACKEND_APIV1.md`](../prd/PLANEJAMENTO_BACKEND_APIV1.md), [`PRD_EXPANDED.md`](./PRD_EXPANDED.md).
> Documentos irmãos: [Brief](./PROJECT_BRIEF.md) · [User flows](./user-flows.md) · [Telas macro](./macro-screens.md) · [SRS](./SRS.md).

---

## Sumário

- [1. Personas](#1-personas)
    - [1.1 Admin (organizadora)](#11-admin-organizadora)
    - [1.2 Comissão de formatura](#12-comissão-de-formatura)
    - [1.3 Formando](#13-formando)
    - [1.4 Responsável financeiro](#14-responsável-financeiro)
    - [1.5 Convidado](#15-convidado)
    - [1.6 Operação (dia do evento)](#16-operação-dia-do-evento)
- [2. Resumo comparativo das personas](#2-resumo-comparativo-das-personas)
- [3. Mapa de calor por fase do ciclo](#3-mapa-de-calor-por-fase-do-ciclo)

---

## 1. Personas

### 1.1 Admin (organizadora)

**Perfil.** Funcionário(a) da empresa organizadora, 25–45 anos, conhece o sistema a fundo, opera múltiplos eventos em paralelo, alta familiaridade com dashboards e planilhas. Geralmente a partir de desktop (1366×768 mínimo). Responsável por cadastro de instituições, criação de eventos, configuração de pacotes, ACL, aprovação de exceções e relatórios.

**Tarefas-chave.**

1. Cadastrar organização, instituição, curso, turma, evento.
2. Criar pacotes comerciais com produtos e regras de parcelamento.
3. Configurar janelas operacionais do evento (`abre_rsvp_at`, `abre_mesas_at`, `fecha_mesas_at`).
4. Definir cotas padrão de convites e regras de estoque de extras.
5. Desenhar mapa de mesas (setores, mesas, assentos).
6. Aprovar pedidos extras que exigem validação.
7. Emitir relatórios financeiros, de adesão e de presença.
8. Gerenciar usuários (ACL via `spatie/laravel-permission`).
9. Revogar tokens de convidado comprometidos.
10. Reprocessar webhooks falhos e auditar trilha.

**Touchpoints.**

- Painel Admin (Blade + Livewire + Inspinia) em `/admin`.
- Horizon em `/horizon` (monitorar filas).
- Pulse em `/pulse` (métricas).
- Scramble em `/docs/api` (referência API).
- E-mail transacional (alertas).

**Dores atuais (mundo sem sistema).**

- Planilhas paralelas que desincronizam.
- Controle manual de cotas por WhatsApp com a comissão.
- Sem rastreabilidade de quem alterou o quê.
- Sem reconciliação automática de pagamentos.
- Retrabalho ao gerar relatórios.

**Ganhos esperados.**

- Cadastro estruturado com validação PT-BR (CPF/CNPJ) e auditoria append-only.
- Reconciliação automática via webhook idempotente.
- Dashboards em tempo real (KPIs + Pulse).
- Exports Excel/PDF sob demanda via fila `exports`.
- ACL granular e revogável.

**Jornada — ciclo de um evento (do cadastro ao pós-evento).**

| Fase         | Objetivo          | Ação                                            | Ferramenta                                         | Emoção                        | Oportunidade                                  |
| ------------ | ----------------- | ----------------------------------------------- | -------------------------------------------------- | ----------------------------- | --------------------------------------------- |
| Pré-venda    | Cadastrar cliente | Criar Organização + Instituição + Curso         | `/admin/cadastros/*` (Livewire CRUD)               | Neutro                        | Importação em lote via CSV                    |
| Pré-venda    | Modelar oferta    | Criar Pacote + Produtos + condições             | `/admin/comercial/pacotes`                         | Concentrado                   | Clonar pacote de evento anterior              |
| Planejamento | Abrir evento      | Publicar evento e definir janelas               | `/admin/cadastros/eventos/{id}/publicar`           | Ansioso (precisa bater datas) | Validação visual de coerência `abre <= fecha` |
| Planejamento | Desenhar mapa     | Configurar mesas/assentos/setores               | `/admin/seating/mapas`                             | Criativo                      | Biblioteca de templates (salão padrão)        |
| Lançamento   | Comunicar         | Disparar campanha de adesão                     | `/admin/comunicacao/campanhas`                     | Entusiasmado                  | Templates versionados                         |
| Operação     | Monitorar         | Dashboard KPIs (adesões, cota, RSVP, pagamento) | `/admin/dashboard`                                 | Vigilante                     | Alerta Slack em conflitos anômalos            |
| Operação     | Aprovar exceções  | Pedido extra, troca forçada de assento          | `/admin/extras/pedidos`, `/admin/seating/reservas` | Decidido                      | Justificativa obrigatória no fluxo            |
| Pós-operação | Reconciliar       | Revisar webhooks, pagamentos, inadimplentes     | `/admin/pagamentos`, `/admin/relatorios`           | Cansado                       | Reconciliação automática reduz carga manual   |
| Pós-evento   | Auditar           | Exportar relatórios finais                      | `/admin/relatorios` (queue `exports`)              | Aliviado                      | Anonimização LGPD automática após 90 dias     |

---

### 1.2 Comissão de formatura

**Perfil.** 3–7 formandos eleitos pela turma, 21–25 anos, operam a partir do celular/notebook, responsáveis por representar a turma junto à organizadora. Acesso com permissões restritas (`role:comissao`) e escopo **por evento**.

**Tarefas-chave.**

1. Visualizar lista da turma (quem aderiu, quem não).
2. Acompanhar funil de RSVP em tempo real.
3. Validar lista de convidados antes do dia do evento.
4. Sugerir ajustes no mapa de mesas (se habilitado).
5. Consultar resultados de enquetes publicadas.
6. Comunicar-se com a organizadora por comentário contextual (feature futura).

**Touchpoints.**

- Admin restrito `/admin/comissao/*` (apenas leitura + permissões pontuais).
- Portal React (mesmas rotas de formando, filtros por turma/evento).
- E-mail com digest semanal.

**Dores atuais.**

- Dependência de planilha compartilhada pela organizadora.
- Informação desatualizada.
- Falta de visibilidade do funil.

**Ganhos esperados.**

- Dashboard filtrável por turma com dados live.
- Scope por evento (não vê dados de outras turmas).
- Exports restritos (apenas da própria turma).

**Jornada — sprint de RSVP.**

| Fase     | Objetivo             | Ação                                  | Ferramenta                                    | Emoção                     | Oportunidade                      |
| -------- | -------------------- | ------------------------------------- | --------------------------------------------- | -------------------------- | --------------------------------- |
| Abertura | Comunicar turma      | Compartilhar link de área do formando | E-mail/WhatsApp + `/admin/comissao/dashboard` | Motivado                   | Botão "gerar link compartilhável" |
| Meio     | Monitorar            | Ver % RSVP confirmado por dia         | `/admin/comissao/rsvp`                        | Preocupado (se taxa baixa) | Reminders automáticos             |
| Ajuste   | Identificar ausentes | Filtro `rsvp=pendente` + `dias=3+`    | Tabela com badge                              | Proativo                   | Ação em lote: "cutucar"           |
| Dia D-7  | Validar              | Conferir lista final                  | Export Excel (queue)                          | Ansioso                    | Selo "lista fechada"              |
| Evento   | Apoiar               | Acompanhar check-in                   | App mobile futuro                             | Alerta                     | Dashboard de presença realtime    |

---

### 1.3 Formando

**Perfil.** Estudante universitário(a), 21–26 anos, **mobile-first**, alta expectativa de UX moderna (inspirações: iFood, Nubank). Paga parcelado, emite convites para família/amigos, escolhe mesa, compra extras, vota em decisões da turma.

**Tarefas-chave.**

1. Fazer login na área do formando.
2. Visualizar plano de pagamento e próxima parcela.
3. Emitir convites (nominais, transferíveis).
4. Acompanhar RSVP dos seus convidados.
5. Reservar assento para si (+ convidados).
6. Comprar convite extra, kit, upgrade.
7. Pagar via PIX/cartão/boleto.
8. Votar em enquetes (padrinho, tema, cerimonialista).
9. Trocar de assento se mudar de ideia.

**Touchpoints.**

- SPA React em `portal.artfinal.com.br` (desktop e mobile).
- App React Native (F8 — pós-MVP).
- E-mail de convite e recibos.
- Push notifications (F8).
- WhatsApp transacional (pós-MVP).

**Dores atuais.**

- Planilhas confusas com parcelas.
- Convites em papel, sem rastreio.
- "Brigas" por mesa resolvidas em grupo de WhatsApp.
- Não sabe quais convidados confirmaram.
- Pagamento sem comprovante.

**Ganhos esperados.**

- Carteira de convites digital, transferível.
- Funil de RSVP claro por convidado.
- Mapa de mesas com hold de 5 min (ninguém "rouba" assento).
- Boleto/PIX/cartão integrados.
- Extras em 1 clique com emissão automática do convite.

**Jornada — ciclo completo.**

| Fase           | Objetivo            | Ação                                       | Ferramenta                   | Emoção           | Oportunidade                           |
| -------------- | ------------------- | ------------------------------------------ | ---------------------------- | ---------------- | -------------------------------------- |
| Descoberta     | Entender pacote     | Ler termos, preços, inclusões              | Landing + site comercial     | Curioso          | FAQ dinâmico                           |
| Adesão         | Contratar           | Escolher pacote + parcelas + dados         | SPA `/adesao/wizard`         | Decisivo         | Wizard 7 etapas com salvar rascunho    |
| Financeiro     | Pagar               | Abrir intent PIX/boleto/cartão             | `/financeiro` + gateway Itaú | Cauteloso        | QR Code grande, comprovante automático |
| Comunidade     | Emitir convite      | Preencher dados do convidado e enviar      | `/convites/novo`             | Animado          | Templates personalizáveis              |
| Acompanhamento | Ver quem confirmou  | Listar convidados com status               | `/convites` tabela           | Ansioso          | Badge colorido + reenviar 1 clique     |
| Escolha        | Reservar mesa       | Explorar mapa + clicar assento + confirmar | `/mesas` mapa interativo     | Estratégico      | Timer visível do hold 5min             |
| Extras         | Comprar             | Adicionar kit, upgrade, convite extra      | `/extras` catálogo           | Oportunista      | Upsell contextual                      |
| Enquetes       | Votar               | Escolher opção e enviar                    | `/enquetes`                  | Engajado         | Resultado parcial visível              |
| Véspera        | Confirmar logística | Ver endereço, horário, mesa                | `/meu-evento`                | Ansioso positivo | QR Code da mesa                        |
| Dia do evento  | Chegar              | Mostrar QR/app na entrada                  | App mobile (F8)              | Eufórico         | Check-in 1-tap                         |
| Pós-evento     | Revisar             | Baixar recibo, fotos                       | `/eventos/{id}/pos`          | Nostálgico       | Galeria oficial integrada              |

---

### 1.4 Responsável financeiro

**Perfil.** Pai, mãe, padrinho ou patrocinador que paga (parcial ou totalmente) a adesão do formando. 40–60 anos. Nem sempre tecnicamente fluente; prioriza boletos e segurança. Pode ser adicionado como segundo perfil da mesma conta (F7 — role `responsavel`).

**Tarefas-chave.**

1. Receber link para pagar parcela.
2. Pagar via boleto, PIX ou cartão.
3. Receber comprovante em e-mail.
4. Acompanhar parcelas em aberto/pagas.
5. Solicitar renegociação (canal externo).

**Touchpoints.**

- E-mail com link direto da parcela (+ QR Code).
- Página de pagamento pública com código de parcela + CPF.
- Recibo PDF via e-mail.

**Dores atuais.**

- Confusão sobre "já pago ou não".
- Boleto vencido sem aviso.
- Dificuldade em entender conta corrente do formando.

**Ganhos esperados.**

- Link único por parcela, válido 7 dias.
- Lembretes automáticos 3 dias antes do vencimento.
- Recibo PDF com logo e detalhamento.
- Histórico simples em 1 tela.

**Jornada — pagamento mensal.**

| Fase        | Objetivo      | Ação                                    | Ferramenta                       | Emoção    | Oportunidade                |
| ----------- | ------------- | --------------------------------------- | -------------------------------- | --------- | --------------------------- |
| Notificação | Ser avisado   | Receber e-mail/SMS com link             | Mail + futuro SMS                | Neutro    | Preferência de canal        |
| Acesso      | Abrir parcela | Clicar no link ou acessar portal        | Landing `/pagar/{token}`         | Focado    | Página limpa, 1 CTA         |
| Escolha     | Método        | PIX, boleto ou cartão                   | Tela de seleção                  | Decidido  | PIX em destaque             |
| Pagamento   | Executar      | Abrir app do banco e pagar              | Gateway Itaú                     | Cauteloso | QR grande + copia/cola      |
| Confirmação | Ver recebido  | Receber e-mail com "Pagamento recebido" | Mail transacional                | Aliviado  | Link para extrato           |
| Histórico   | Revisar       | Ver parcelas anteriores                 | `/pagamentos/historico` (futuro) | Tranquilo | Bloco de parcelas em aberto |

---

### 1.5 Convidado

**Perfil.** Amigo, parente ou profissional convidado pelo formando. Não tem conta no sistema, não quer criar uma. Acessa via link recebido em e-mail/WhatsApp. Interage só para: ver convite, confirmar presença, eventualmente escolher assento (se política permitir).

**Tarefas-chave.**

1. Abrir link do convite (token de 64 chars, SHA-256).
2. Ver dados do evento (data, local, formando).
3. Confirmar/recusar presença.
4. Opcionalmente escolher assento se elegível.
5. Adicionar ao calendário.

**Touchpoints.**

- Link público `/api/v1/convite/{token}` (renderizado pelo front).
- E-mail ou WhatsApp (pós-MVP) recebido do formando.
- Evento rico (.ics) anexo.

**Dores atuais.**

- Convite de papel que esquece em casa.
- Sem forma digital de confirmar.
- Sem detalhes claros (horário, endereço, dress code).

**Ganhos esperados.**

- Confirmação em ≤ 2 minutos, sem cadastro.
- Convite sempre acessível no link.
- Lembrete automático 3 dias antes.

**Jornada — do recebimento ao dia do evento.**

| Fase            | Objetivo      | Ação                                | Ferramenta                              | Emoção         | Oportunidade                      |
| --------------- | ------------- | ----------------------------------- | --------------------------------------- | -------------- | --------------------------------- |
| Recebimento     | Ser convidado | Receber e-mail/WhatsApp             | Mail + link com token                   | Feliz/surpreso | Imagem rica do convite            |
| Primeira visita | Ver detalhes  | Clicar e ler página do convite      | `/convite/{token}` (status=visualizado) | Curioso        | CTA grande "Confirmar"            |
| Decisão         | Responder     | Escolher Confirmar / Recusar        | Form simples                            | Engajado       | Auto-preencher nome do e-mail     |
| Lembrete        | Ser lembrado  | Receber e-mail D-3                  | Mail queued                             | Neutro         | Atualização do RSVP se data mudou |
| Véspera         | Conferir      | Reabrir convite, ver endereço, mesa | Link mantém estado                      | Empolgado      | Botão "adicionar ao calendário"   |
| Dia do evento   | Chegar        | Apresentar convite na entrada       | QR Code no link                         | Satisfeito     | Check-in automático (F8)          |

---

### 1.6 Operação (dia do evento)

**Perfil.** Equipe de recepção/staff da organizadora no dia do evento. 20–40 anos, operando tablets ou celulares corporativos. Responsável por: receber formandos e convidados, validar identidade, resolver exceções de última hora.

**Tarefas-chave.**

1. Fazer check-in por QR Code do convite.
2. Validar que convite está `confirmado` e dentro do evento correto.
3. Em caso de problema, consultar admin por rádio.
4. Remarcar assento em caso de troca de última hora (admin-assistido).
5. Marcar convite como `inutilizado` após uso (one-way).

**Touchpoints.**

- Tablet com app web simplificado `/admin/operacao` (modo offline-first ideal).
- Leitor de QR Code (câmera do device).
- Rádio/Slack com admin central.

**Dores atuais.**

- Listas impressas desatualizadas.
- Demora para resolver "está na lista?" → fila na porta.
- Sem trilha de quem já entrou.

**Ganhos esperados.**

- Busca por código do convite em ≤ 1 seg.
- Validação 1-tap com selo visual (verde/vermelho).
- Registro automático de horário de check-in (auditoria).

**Jornada — noite do evento.**

| Fase           | Objetivo             | Ação                               | Ferramenta        | Emoção     | Oportunidade                  |
| -------------- | -------------------- | ---------------------------------- | ----------------- | ---------- | ----------------------------- |
| Pré-abertura   | Preparar             | Testar app, carregar lista offline | `/admin/operacao` | Focado     | Modo avião com sync posterior |
| Chegada        | Recepcionar          | Escanear QR do convite             | Câmera + app      | Ritmo alto | Som/feedback imediato         |
| Validação      | Confirmar identidade | Ver dados + status                 | Card do convidado | Atento     | Foto opcional cadastrada      |
| Ok             | Liberar entrada      | Tap "Inutilizar (entrou)"          | Botão grande      | Direto     | Auto-move para próximo        |
| Exceção        | Resolver             | Chamar admin por rádio             | Slack/Rádio       | Alerta     | Chat in-app com admin         |
| Fim de entrada | Relatório            | Ver taxa de presença               | Dashboard live    | Satisfeito | Export CSV da noite           |

---

## 2. Resumo comparativo das personas

| Persona                | Canal principal                     | Autenticação                                | Perfil ACL                          | Frequência de uso |
| ---------------------- | ----------------------------------- | ------------------------------------------- | ----------------------------------- | ----------------- |
| Admin                  | Desktop `/admin` (Blade + Livewire) | Sessão `auth:admin`                         | `role:admin`, todas as permissões   | Diária            |
| Comissão               | Desktop/mobile (subset do admin)    | Sessão `auth:admin` + role                  | `role:comissao` + escopo por evento | 1–3× semana       |
| Formando               | Mobile-first SPA                    | `auth:sanctum` (cookie SPA ou token mobile) | `role:formando`                     | Semanal           |
| Responsável financeiro | E-mail → link público               | Token de parcela (curto, um-uso)            | Anônimo autenticado por token       | Mensal            |
| Convidado              | E-mail/WhatsApp → link público      | Token de convite 64 chars                   | Anônimo autenticado por token       | 2–4× ciclo        |
| Operação               | Tablet dedicado                     | Sessão `auth:admin` + role                  | `role:operacao`                     | Noite do evento   |

---

## 3. Mapa de calor por fase do ciclo

Legenda: 🟦 uso intenso · 🟩 uso moderado · ⬜ pouco/nenhum uso.

| Fase do ciclo       | Admin | Comissão | Formando | Resp. Financ. | Convidado | Operação |
| ------------------- | ----- | -------- | -------- | ------------- | --------- | -------- |
| Cadastro de evento  | 🟦    | ⬜       | ⬜       | ⬜            | ⬜        | ⬜       |
| Adesão comercial    | 🟩    | ⬜       | 🟦       | 🟩            | ⬜        | ⬜       |
| Pagamento mensal    | 🟩    | ⬜       | 🟩       | 🟦            | ⬜        | ⬜       |
| Emissão de convites | ⬜    | 🟩       | 🟦       | ⬜            | ⬜        | ⬜       |
| RSVP                | ⬜    | 🟦       | 🟩       | ⬜            | 🟦        | ⬜       |
| Mapa de mesas       | 🟩    | 🟩       | 🟦       | ⬜            | 🟩        | ⬜       |
| Compras extras      | 🟩    | ⬜       | 🟦       | 🟩            | ⬜        | ⬜       |
| Enquetes            | 🟩    | 🟩       | 🟦       | ⬜            | ⬜        | ⬜       |
| Dia do evento       | 🟦    | 🟦       | 🟦       | ⬜            | 🟦        | 🟦       |
| Pós-evento          | 🟦    | 🟩       | 🟩       | 🟩            | ⬜        | ⬜       |

---

## 4. Princípios transversais de UX

Valem para todas as personas e derivam do [Brief](./PROJECT_BRIEF.md) §Princípios:

1. **Confiança acima de estética.** Feedback claro sobre o que aconteceu (estado, timestamp, próximos passos).
2. **Operações idempotentes percebidas.** Se o usuário clicar duas vezes, não gera efeito duplo.
3. **Mensagens em PT-BR claras.** Nunca expor mensagens técnicas no front ("Integrity constraint violation" → "Este assento já foi reservado").
4. **Janela sempre visível.** Quando operação depende de `abre_at/fecha_at`, mostrar countdown e bloqueio suave.
5. **LGPD por padrão.** Mascarar CPF nas telas, nunca logar tokens ou senhas.
6. **Atalhos para admin.** Admin ganha hotkeys e tabelas filtráveis; nunca forçar navegação em 10 cliques.

---

## 5. Referências cruzadas

- Fluxos detalhados: [`user-flows.md`](./user-flows.md).
- Telas por persona: [`macro-screens.md`](./macro-screens.md).
- Requisitos formais (RF-XXX): [`SRS.md`](./SRS.md).
- Regras de negócio: [`../prd/REGRAS_NEGOCIO.md`](../prd/REGRAS_NEGOCIO.md).
