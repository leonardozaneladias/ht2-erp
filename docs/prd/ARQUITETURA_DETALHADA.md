# Arquitetura Detalhada

## 1. Resumo da decisão

O sistema será implementado como **monólito modular em Laravel 13**, com fronteiras explícitas de contexto, `API v1` pública para cliente web e mobile, e backoffice interno server-driven em Blade/Livewire com Inspinia.

Essa decisão equilibra:

- reaproveitamento do boilerplate e do catálogo Inspinia já existentes
- necessidade de contratos estáveis para React web e React Native
- simplicidade operacional de deploy
- menor custo cognitivo que uma arquitetura de microservices

## 2. Leitura do estado atual do repositório

O repositório atual mostra três sinais relevantes:

1. O admin já tem forte trilha de componentização em `resources/views/components/admin` e `resources/views/components/shared`.
2. As rotas de negócio ainda são placeholders; o domínio de produto ainda não foi implementado.
3. A documentação interna já usa o princípio “API-ready”, mas a camada externa do produto ainda não havia sido reorganizada para isso.

Em outras palavras: a base visual do admin está relativamente madura, mas a base de domínio ainda está em momento ideal para consolidar a arquitetura correta antes da implementação real.

## 3. Estilo arquitetural

### 3.1 Monólito modular

O sistema fica em uma única aplicação Laravel, porém organizado por contextos de negócio e camadas claras:

- HTTP Web/Admin
- HTTP API v1
- Actions
- Data/DTOs
- Models
- Services de integração
- Jobs e listeners
- Policies

O objetivo é permitir evolução sem pagar o custo prematuro de múltiplos serviços, bancos ou pipelines independentes.

### 3.2 Interface strategy

| Interface      | Papel                                                              |
| -------------- | ------------------------------------------------------------------ |
| Blade/Livewire | Backoffice interno e fluxos administrativos                        |
| API v1         | Fonte oficial para cliente web React, mobile e integrações futuras |
| Jobs/Webhooks  | Processamento assíncrono e integração externa                      |

### 3.3 Bounded contexts

Os módulos técnicos devem espelhar os bounded contexts do domínio:

- `CadastroEvento`
- `AdesaoComercial`
- `Convites`
- `Seating`
- `Engajamento`
- `Extras`
- `Comunicacao`
- `IdentidadeEAcesso`

Não é necessário criar um módulo Composer por contexto. Basta refletir essas divisões em namespaces, actions, DTOs, policies e testes.

## 4. Estrutura sugerida

```text
app/
├── Actions/
│   ├── Adesao/
│   ├── Convites/
│   ├── Seating/
│   ├── Extras/
│   ├── Enquetes/
│   └── Eventos/
├── Data/
│   ├── Adesao/
│   ├── Convites/
│   ├── Seating/
│   ├── Extras/
│   └── Api/
├── Http/
│   ├── Web/
│   │   ├── Admin/
│   │   └── Shared/
│   ├── Api/
│   │   └── V1/
│   └── Middleware/
├── Jobs/
├── Listeners/
├── Models/
├── Policies/
├── Services/
│   ├── Gateway/
│   ├── Storage/
│   ├── Messaging/
│   └── Notifications/
└── Support/
```

### 4.1 Rotas

```text
routes/
├── admin.php        # backoffice interno
├── portal.php       # apenas landing/web shell se necessário
├── api/
│   └── v1.php       # API pública
└── webhook.php      # integrações externas
```

### 4.2 Regra de ouro

Toda regra de domínio precisa ser consumível por:

- controller web/admin
- controller API
- job
- listener
- command

Isso impede acoplamento de regra a Blade, Livewire, React ou React Native.

## 5. Padrões aplicados

### 5.1 Actions para lógica de negócio

Cada operação relevante do domínio vira uma action invocável:

- `EmitirConviteAction`
- `RegistrarRsvpAction`
- `ReservarAssentoAction`
- `ExpirarHoldAssentoAction`
- `CriarPedidoExtraAction`
- `ConfirmarPagamentoExtraAction`
- `PublicarEnqueteAction`

### 5.2 DTOs para transporte de dados

DTOs devem ser usados em:

- entrada validada de request
- payloads de integração
- resposta de serviços externos
- contratos entre actions

### 5.3 Policies e permissões

Permissões de backoffice ficam em `spatie/laravel-permission`.

Policies devem proteger:

- evento
- convite
- mapa de mesas
- pedido extra
- enquete
- relatórios

### 5.4 Jobs e eventos

Processos obrigatoriamente assíncronos:

- envio de e-mails
- emissão em massa de convites
- geração de PDFs
- reprocessamento de webhooks
- sincronização de notificações push
- recalculações operacionais caras

Eventos de domínio recomendados:

- `ConviteEmitido`
- `RsvpRegistrado`
- `AssentoReservado`
- `AssentoLiberado`
- `PedidoExtraPago`
- `EnqueteEncerrada`

## 6. Decisão Inertia vs API-first

### 6.1 Critérios de avaliação

| Critério                                 | Inertia     | API-first  |
| ---------------------------------------- | ----------- | ---------- |
| Entrega rápida de SPA web única          | forte       | média      |
| Reuso com mobile                         | fraco       | forte      |
| Contrato público estável                 | implícito   | explícito  |
| Evolução para integrações externas       | média       | forte      |
| Aproveitamento do admin Blade existente  | indiferente | forte      |
| Complexidade de autenticação multi-canal | média       | controlada |

### 6.2 Conclusão

Para este produto, **API-first vence** como estratégia principal.  
Inertia só faria sentido se o cliente web fosse o único canal relevante e se o mobile continuasse indefinido. Como isso já não é verdade, seria uma otimização local com custo futuro alto.

## 7. Autenticação e autorização

### 7.1 Admin

- autenticação por sessão/cookie
- guard próprio
- permissões e papéis granulares
- MFA opcional na fase 2

### 7.2 Cliente web React

Se o front ficar no mesmo domínio principal do backend:

- usar Sanctum com SPA auth e cookies `SameSite`

Se o front ficar em domínio separado:

- manter Sanctum ou token de sessão dedicado com CORS e CSRF controlados

### 7.3 Mobile

- Sanctum com tokens pessoais ou fluxo tokenizado first-party
- refresh/revogação controlados
- armazenamento seguro no device

### 7.4 Convidados

- acesso preferencial por token mágico de convite
- sem exigir cadastro completo
- com possibilidade de upgrade de identidade se o negócio pedir

## 8. Modelo de dados e persistência

### 8.1 PostgreSQL

PostgreSQL é a melhor escolha para o v4 por:

- consistência transacional forte
- índices parciais úteis para reservas ativas
- suporte robusto a JSONB para configurações e snapshots
- locks e constraints úteis em concorrência de assentos

### 8.2 Estratégia de snapshots

Devem ser versionados ou snapshotados:

- preço e regra de adesão
- termos aceitos
- configuração de convite no momento da emissão
- composição da mesa/assento no momento da confirmação
- preço/regra do extra no momento do pagamento

### 8.3 Constraints críticas

Exemplos de constraints esperadas:

- um assento não pode ter mais de uma reserva ativa
- um convite não pode ser associado a dois convidados ativos simultâneos
- um voto por ator elegível por enquete
- um webhook externo não pode ser processado duas vezes com o mesmo identificador idempotente

## 9. Concorrência e consistência

### 9.1 Seating

Seating exige a combinação de:

- unique constraint
- idempotency key
- transação curta
- expiração automática de hold
- notificação de atualização para o cliente

### 9.2 Pagamentos

Pagamentos exigem:

- event sourcing leve por `pagamento_eventos`
- reprocessamento idempotente
- separação entre `intent`, `authorized`, `paid`, `failed`, `refunded`

### 9.3 Operações em lote

Emissão em massa de convites, importações e exportações devem ser enfileiradas e ter status de processamento.

## 10. API pública

### 10.1 Princípios

- `/api/v1` desde o início
- responses estáveis
- paginação padronizada
- ids públicos quando necessário
- erros previsíveis e documentados

### 10.2 Domínios de endpoint

```text
/api/v1/auth/*
/api/v1/me/*
/api/v1/eventos/*
/api/v1/formandos/*
/api/v1/convites/*
/api/v1/rsvp/*
/api/v1/mesas/*
/api/v1/enquetes/*
/api/v1/extras/*
/api/v1/pagamentos/*
```

### 10.3 BFF versus API genérica

Não recomendamos um BFF separado agora. O Laravel pode servir a API diretamente, com resources específicos por contexto. Se no futuro o app mobile pedir agregações muito específicas, isso pode ser resolvido com endpoints compostos, sem nova camada de serviço.

## 11. Frontend architecture

### 11.1 Cliente web React

Responsabilidades:

- fluxo do formando
- carteira de convites
- RSVP
- seating
- extras
- notificações

Stack recomendada:

- React Router
- TanStack Query
- Zustand
- React Hook Form
- Zod
- Axios
- Tamagui

### 11.2 Mobile

Responsabilidades:

- mesma API
- foco em consulta, confirmação, extras, notificações e ações rápidas
- UI derivada do mesmo design system do cliente web

## 12. Admin architecture

### 12.1 Motivo para manter Blade/Livewire

O admin tem:

- fluxo interno
- forte dependência do catálogo Inspinia já validado
- muito CRUD, filtros, tabelas e painéis
- pouca necessidade de reuso direto com mobile

Isso favorece Blade/Livewire para velocidade de execução.

### 12.2 Regra de implementação

Admin pode continuar server-driven, mas:

- não pode concentrar regra de negócio
- não pode criar contrato paralelo ao da API
- não pode inventar estados não existentes no core

## 13. Integrações externas

### 13.1 Pagamentos

Devem ficar atrás de um serviço interno com contrato estável:

- `PaymentGatewayContract`
- drivers por provedor
- DTOs de request/response
- webhooks tratados via action idempotente

### 13.2 E-mail e push

Notificações devem sair de eventos do domínio. O canal é detalhe de infraestrutura.

### 13.3 Arquivos

Uploads de comprovantes, assets e mapas devem usar storage abstraído; o sistema não deve depender de disco local.

## 14. Observabilidade

### 14.1 Logs

Logs estruturados com:

- `request_id`
- `actor_type`
- `actor_id`
- `evento_id`
- `correlation_id`

### 14.2 Métricas

- filas
- tempo de reserva de assento
- tempo de processamento de webhook
- taxa de RSVP
- falhas de pagamento

### 14.3 Tracing funcional

Mesmo sem tracing distribuído completo no início, o sistema deve conseguir reconstruir a linha:

`convite emitido -> convidado acessou -> RSVP -> reserva -> pagamento extra`

## 15. Estratégia de testes

### 15.1 Feature tests

- autenticação
- APIs
- fluxos de RSVP
- seating
- pedidos extras
- webhooks

### 15.2 Unit tests

- actions puras
- regras de cota
- políticas de elegibilidade
- cálculo de disponibilidade de assento

### 15.3 Tests de arquitetura

Usar Pest Architecture para garantir:

- strict types
- actions sem acoplamento HTTP
- controllers finos
- policies em namespaces esperados

## 16. Riscos arquiteturais

| Risco                                                     | Impacto | Mitigação                                                |
| --------------------------------------------------------- | ------- | -------------------------------------------------------- |
| Adiar a API pública e concentrar lógica em Blade/Livewire | alto    | estabelecer `api/v1` desde o início                      |
| Subestimar concorrência de seating                        | alto    | modelar hold + lock + unique constraint desde a fundação |
| Duplicar contratos entre admin e React                    | alto    | resources e DTOs comuns, actions únicas                  |
| Misturar regra de comissão com regra de admin             | médio   | matriz de permissões e policies por ator                 |
| Overengineering com módulos demais cedo                   | médio   | monólito modular, sem microservices por enquanto         |

## 17. Bibliotecas Laravel recomendadas

**Recorte da pesquisa:** 15/04/2026  
**Critério:** robustez, adoção, aderência ao contexto do projeto e compatibilidade com Laravel 13

| Biblioteca                    | Versão observada | Caso de uso no projeto                            | Prós                                           | Contras                                                       | Link                                                |
| ----------------------------- | ---------------- | ------------------------------------------------- | ---------------------------------------------- | ------------------------------------------------------------- | --------------------------------------------------- |
| `spatie/laravel-permission`   | `7.3.0`          | papéis e permissões para admin/comissão           | muito consolidada, boa integração com policies | pode ficar confusa sem governança de naming                   | https://github.com/spatie/laravel-permission        |
| `spatie/laravel-medialibrary` | `11.21.0`        | logos, assets de evento, uploads organizados      | poderosa, conversões e organização de media    | mais complexa que upload simples                              | https://github.com/spatie/laravel-medialibrary      |
| `spatie/laravel-activitylog`  | `5.0.0`          | auditoria append-only                             | ótima aderência ao requisito de trilha         | precisa curadoria para não logar ruído                        | https://github.com/spatie/laravel-activitylog       |
| `laravel/sanctum`             | `4.3.1`          | autenticação SPA/mobile/first-party API           | recomendação oficial Laravel para SPA/mobile   | exige atenção a cookies/CORS quando houver múltiplos domínios | https://laravel.com/docs/13.x/sanctum               |
| `laravel/horizon`             | `5.45.6`         | filas, retries, visibilidade operacional          | painel sólido para Redis queues                | precisa disciplina de filas e supervisores                    | https://laravel.com/docs/13.x/horizon               |
| `laravel/pulse`               | `1.7.3`          | métricas internas e saúde da aplicação            | observabilidade Laravel-first                  | não substitui error tracking dedicado                         | https://github.com/laravel/pulse                    |
| `maatwebsite/excel`           | `3.1.68`         | exportação e importação de relatórios             | padrão de mercado no ecossistema Laravel       | imports grandes exigem fila e memória controlada              | https://github.com/SpartnerNL/Laravel-Excel         |
| `barryvdh/laravel-dompdf`     | `3.1.2`          | PDFs de termos, convites e comprovantes           | simples de integrar e já presente no projeto   | limitações visuais em layouts muito sofisticados              | https://github.com/barryvdh/laravel-dompdf          |
| `saloonphp/laravel-plugin`    | `4.2.0`          | integração limpa com gateways e serviços externos | excelente para connectors/requests tipados     | adiciona um padrão novo para o time aprender                  | https://github.com/saloonphp/laravel-plugin         |
| `spatie/laravel-data`         | `4.21.0`         | DTOs para actions e API                           | forte tipagem e serialização limpa             | introduz dependência arquitetural nova                        | https://github.com/spatie/laravel-data              |
| `sentry/sentry-laravel`       | `4.25.0`         | error tracking de produção                        | acelera detecção de falhas reais               | custo externo e governança de alertas                         | https://github.com/getsentry/sentry-laravel         |
| `league/flysystem-aws-s3-v3`  | `3.32.0`         | storage privado/público em nuvem                  | integração natural com Laravel filesystem      | depende da estratégia de cloud adotada                        | https://github.com/thephpleague/flysystem-aws-s3-v3 |

### 17.1 Recomendações por categoria

#### Permissões

- Recomendação: `spatie/laravel-permission`
- Motivo: resolve RBAC do admin e da comissão com maturidade e baixo risco.

#### Mídia e uploads

- Recomendação principal: `spatie/laravel-medialibrary`
- Complemento: `league/flysystem-aws-s3-v3` se a estratégia de storage for S3-compatível.

#### Pagamentos e integrações externas

- Recomendação principal: `saloonphp/laravel-plugin`
- Motivo: o projeto depende de integrações específicas; uma abstração tipada é mais útil que um pacote de billing opinionado para Stripe/Paddle.

#### Filas e processamento assíncrono

- Recomendação: `laravel/horizon`
- Complemento: `laravel/pulse` para visibilidade operacional.

#### Auth SPA/Mobile

- Recomendação: `laravel/sanctum`

#### Relatórios e exportação

- Recomendação: `maatwebsite/excel` e `barryvdh/laravel-dompdf`

#### Auditoria e observabilidade

- Recomendação: `spatie/laravel-activitylog`, `laravel/pulse` e `sentry/sentry-laravel`

### 17.2 O que não recomendamos como primeira escolha

- `Passport` como padrão: complexo demais para first-party SPA/mobile sem necessidade real de OAuth2 completo.
- `Cashier` como centro da estratégia financeira: excelente para Stripe/Paddle, mas não resolve a natureza de integração bancária específica do projeto.
- uploads em disco local como padrão de produção: operacionalmente frágeis para crescimento.

## 18. Decisões finais

1. **Admin:** Blade/Livewire/Inspinia.
2. **Cliente web e mobile:** API-first.
3. **Core:** actions + DTOs + policies + jobs.
4. **Banco:** PostgreSQL + Redis.
5. **Concorrência crítica:** seating e pagamentos tratados como first-class concerns.
