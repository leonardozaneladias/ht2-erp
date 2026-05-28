# Estratégia de Segurança

## 1. Objetivo

Este documento define a estratégia mínima de segurança do v4, cobrindo autenticação, autorização, dados pessoais, pagamentos, uploads, logs e operação.

## 2. Princípios

1. Menor privilégio por perfil.
2. Separação clara entre admin, comissão, formando, responsável e convidado.
3. Proteção explícita para dados pessoais e operacionais.
4. Idempotência e auditabilidade em ações críticas.
5. Defaults seguros na API e no admin.

## 3. Identidade e autenticação

### 3.1 Admin

- guard dedicado
- sessão com expiração configurável
- throttle de login
- possibilidade de MFA em etapa posterior
- revogação administrativa imediata

### 3.2 Cliente web React

- Sanctum para first-party SPA
- cookies seguros quando mesmo domínio
- CSRF habilitado nos fluxos stateful

### 3.3 Mobile

- Sanctum com tokens revogáveis
- storage seguro para credenciais
- política clara de expiração e reautenticação

### 3.4 Convidados

- acesso por token mágico vinculado ao convite
- token de escopo mínimo
- validade controlada
- revogação manual possível

## 4. Autorização

### 4.1 Modelo

- `spatie/laravel-permission` para papéis e permissões do backoffice
- policies para recursos e ações sensíveis
- gates apenas para casos globais simples

### 4.2 Perfis mínimos

| Perfil              | Escopo                             |
| ------------------- | ---------------------------------- |
| Admin master        | todas as ações                     |
| Operação financeira | pagamentos, relatórios, exceções   |
| Operação evento     | convites, RSVP, seating            |
| Comissão            | acesso controlado por evento/turma |
| Formando            | apenas recursos próprios           |
| Convidado           | apenas convite/token próprio       |

### 4.3 Regras importantes

1. Comissão nunca herda permissões administrativas amplas por conveniência.
2. Convidado nunca navega por id numérico previsível.
3. Admin sem permissão de evento não vê dados daquele contexto operacional.

## 5. Proteção de dados e LGPD

### 5.1 Dados sensíveis do domínio

- nome completo
- CPF/documentos
- e-mail
- telefone
- dados financeiros
- histórico de presença
- preferências operacionais

### 5.2 Regras

1. Coletar apenas o mínimo necessário por ator.
2. Separar claramente dado de identificação e dado operacional.
3. Ter política de retenção para convites e logs.
4. Anonimizar ou pseudonimizar exportações quando possível.
5. Restringir downloads massivos por perfil.

### 5.3 Logs

Logs não devem registrar:

- token bruto
- senha
- dados completos de cartão
- payloads sensíveis sem mascaramento

## 6. API Security

### 6.1 Regras gerais

- `api/v1` protegida por autenticação quando necessário
- recursos públicos apenas quando explicitamente públicos
- rate limiting por rota e por ator
- validação forte de input
- uso de resources/DTOs para evitar exposição excessiva

### 6.2 Recomendações

- limitar endpoints de RSVP e convites por token e IP
- limitar tentativas de login por identidade e IP
- aplicar ids públicos ou slugs quando a exposição for externa
- usar headers de correlação para rastreio

## 7. Convites e tokens

### 7.1 Regras de emissão

- token deve ser aleatório e não enumerável
- hash do token deve ser persistido quando viável
- link de acesso precisa ser revogável

### 7.2 Regras de uso

1. Token expirado não pode ser reutilizado.
2. Token revogado precisa de mensagem funcional ao usuário.
3. Transferência de convite deve invalidar o token anterior, se a política exigir.

## 8. Seating seguro

### 8.1 Riscos

- corrida por assento
- manipulação de payload no client
- replay de request
- reserva órfã

### 8.2 Mitigações

- unique constraint em reserva ativa
- idempotency key
- revalidação no commit
- job de expiração de hold
- logs operacionais por tentativa

## 9. Pagamentos e integrações

### 9.1 Boas práticas

- nunca confiar em callback do frontend como confirmação financeira
- tratar webhook como evento externo não confiável até validação
- registrar cada evento recebido
- usar assinatura/HMAC quando provedor suportar
- reconciliar estado local com estado do provedor

### 9.2 Dados de pagamento

- não armazenar PAN ou dados sensíveis de cartão
- armazenar apenas referências/tokens retornados pelo provedor
- mascarar identificadores exibidos

## 10. Uploads e arquivos

### 10.1 Riscos

- upload malicioso
- MIME spoofing
- arquivos executáveis
- exposição indevida por URL pública

### 10.2 Regras

1. Validar extensão e MIME real.
2. Gerar nome seguro no servidor.
3. Não servir arquivo privado por path direto.
4. Usar storage abstraído com URLs assinadas quando necessário.
5. Submeter uploads relevantes a varredura futura se o volume justificar.

## 11. Sessão, cookies e dispositivos

### 11.1 Sessões

- timeout para admin mais agressivo
- invalidação após reset de senha ou revogação
- opção de logout global por usuário

### 11.2 Cookies

- `Secure`
- `HttpOnly`
- `SameSite` compatível com o canal

## 12. Auditoria

### 12.1 Eventos mínimos auditáveis

- login bem-sucedido e falho
- emissão/cancelamento/transferência de convite
- alteração de cota
- resposta e edição de RSVP
- ação sobre reserva de assento
- aprovação/rejeição de pedido extra
- baixa manual financeira
- alteração de permissão

### 12.2 Estrutura

Cada log deve conter:

- ator
- recurso
- ação
- contexto do evento
- antes/depois quando aplicável
- request id
- timestamp

## 13. Observabilidade e resposta a incidentes

### 13.1 Alertas importantes

- explosão de falhas em webhook
- aumento de conflito de assentos
- fila travada
- pico anormal de rate limiting
- erro 5xx em endpoints críticos

### 13.2 Processo

1. detectar
2. conter
3. corrigir
4. auditar
5. registrar aprendizado

## 14. Checklist de segurança para implementação

- usar Form Request em toda entrada HTTP
- aplicar policies em recursos críticos
- usar `auth:sanctum` e guards separados onde necessário
- proteger webhooks com assinatura
- mascarar dados sensíveis em logs
- impedir enumeração de convites
- implementar rate limiting por contexto
- usar storage privado para documentos sensíveis

## 15. Referências

- [Laravel Authentication](https://laravel.com/docs/13.x/authentication)
- [Laravel Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Laravel Routing](https://laravel.com/docs/13.x/routing)
- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Activitylog](https://github.com/spatie/laravel-activitylog)
