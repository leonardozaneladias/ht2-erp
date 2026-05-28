# Estratégia de Performance

## 1. Objetivo

Este documento define as otimizações recomendadas para manter boa experiência de uso no admin, no cliente web React e no futuro mobile, com foco especial em listagens, convites, seating e pagamentos.

## 2. Metas de referência

| Cenário                          | Meta      |
| -------------------------------- | --------- |
| P95 de leitura simples de API    | <= 300 ms |
| P95 de leitura crítica de API    | <= 500 ms |
| P95 de reserva de assento        | <= 700 ms |
| Reprocessamento de webhook       | <= 60 s   |
| Dashboard admin com cache quente | <= 2 s    |
| Exportações síncronas pequenas   | <= 30 s   |

## 3. Banco de dados

### 3.1 PostgreSQL

Usar PostgreSQL 16 com foco em:

- índices compostos por `evento_id`, `status`, `created_at`
- índices parciais para reservas ativas
- JSONB apenas quando a natureza do dado for realmente semi-estruturada
- foreign keys explícitas

### 3.2 Índices recomendados

#### Convites

- `(evento_id, status)`
- `(formando_id, status)`
- `(codigo)` único
- `(token_hash)` único ou altamente seletivo

#### RSVP

- `(convite_id, status)`
- `(evento_id, updated_at)`

#### Seating

- `(evento_id, mesa_id, status)`
- unique parcial para `assento_id` com reserva ativa
- `(hold_expires_at)` para job de limpeza

#### Extras e pagamentos

- `(pedido_extra_id, status)`
- `(gateway_reference)` único
- `(evento_id, status, paid_at)`

## 4. Estratégia de queries

### 4.1 Regras

1. Toda listagem grande deve ser paginada.
2. Todo endpoint crítico deve ter eager loading explícito.
3. Agregações caras devem usar cache ou materialização leve.
4. Evitar carregar mapa de evento inteiro quando a tela usa apenas um setor.

### 4.2 Exemplos de telas sensíveis

- dashboard admin
- listagem consolidada de convites
- funil de RSVP
- mapa de mesas
- tela financeira consolidada

## 5. Cache

### 5.1 O que cachear

- configuração de evento
- contadores agregados de convites/RSVP
- mapa de mesas em modo leitura
- resultados de enquete em modo leitura
- opções estáticas e lookups

### 5.2 O que não cachear sem cuidado

- disponibilidade final de assento durante disputa
- status financeiro sensível imediatamente após pagamento
- permissões por usuário sem invalidadores adequados

### 5.3 Estratégia

- Redis como camada principal
- invalidar por evento de domínio
- TTL curto para dashboards
- cache por evento para evitar mistura de contexto

## 6. Filas e processamento assíncrono

### 6.1 Deve ir para fila

- envios de e-mail
- notificações push
- emissão em massa de convites
- geração de PDF
- importações
- exportações pesadas
- reprocessamento de webhook
- limpeza de holds expirados

### 6.2 Horizon

Separar filas ao menos em:

- `default`
- `notifications`
- `webhooks`
- `exports`
- `critical-seating`

`critical-seating` pode ter baixa concorrência e prioridade controlada para evitar starvation.

## 7. Seating performance

### 7.1 Problema

O mapa de mesas mistura leitura intensa com gravações concorrentes.

### 7.2 Estratégia recomendada

- endpoint leve para snapshot inicial do mapa
- endpoint incremental para atualizações
- write path curto e transacional para reservar
- job de expiração para holds
- invalidação pontual por mesa/setor

### 7.3 Regras

1. Não recalcular mapa inteiro a cada reserva.
2. Não enviar payload gigante para mobile em toda mudança.
3. Preferir atualizar apenas mesa/setor afetado.

## 8. Frontend performance

### 8.1 Cliente web

- TanStack Query com cache por chave de evento
- paginação e filtros debounced
- lazy loading de telas secundárias
- virtualização onde houver listas longas

### 8.2 Mobile

- cache local com MMKV para preferências e alguns estados
- queries curtas e reuso de cache
- payloads menores para redes móveis

## 9. Admin performance

### 9.1 Inspinia + Blade/Livewire

O admin não deve virar SPA genérica. Otimizações esperadas:

- filtros bem definidos
- partial reloads em Livewire quando fizer sentido
- exportações assíncronas
- componentes de tabela com paginação real

### 9.2 Dashboards

Não recalcular tudo em tempo real a cada request. Usar:

- cache de 1 a 5 minutos
- jobs de pré-agregação quando o volume crescer

## 10. Observabilidade de performance

### 10.1 Métricas importantes

- tempo de endpoint por rota
- tamanho médio de payload
- latência de reserva de assento
- tempo de fila por job crítico
- falhas e retries em webhook

### 10.2 Ferramentas

- Pulse para telemetria interna
- Horizon para filas
- Debugbar em desenvolvimento
- Sentry ou similar para erros de produção

## 11. Evolução planejada

### Fase 1

- índices corretos
- cache básico
- filas separadas
- instrumentação mínima

### Fase 2

- materialização de métricas operacionais
- websockets ou polling otimizado para seating
- exportações sempre assíncronas

### Fase 3

- particionamento de tabelas muito grandes se o volume exigir
- CDN e edge caching para assets públicos

## 12. Anti-patterns a evitar

- N+1 em dashboard e listagens administrativas
- usar cache para esconder query ruim
- payload completo do mapa em toda interação
- exports síncronos gigantes em request web
- lógica pesada de disponibilidade no frontend

## 13. Referências

- [Laravel Horizon](https://laravel.com/docs/13.x/horizon)
- [Laravel Pulse](https://github.com/laravel/pulse)
- [Laravel Routing](https://laravel.com/docs/13.x/routing)
