# Configurar Redis, filas nomeadas, cache e Horizon base

**ID:** STORY-005  
**Epic:** F1-E2 — Infraestrutura de domínio  
**Priority:** Must Have  
**Story Points:** 2  
**Status:** Not Started  
**Skills:** `configuring-horizon`, `laravel-best-practices`

## User Story

Como **desenvolvedor do Portal ArtFinal**
Quero **ter Redis configurado como driver de cache e fila, com filas nomeadas por prioridade e Horizon com supervisores básicos**
Para que **jobs possam ser despachados para a fila correta desde o início do projeto, sem reconfiguração posterior, e o dashboard `/horizon` esteja operacional**

## Acceptance Criteria

- [ ] `config/queue.php` tem `default` = `redis` (lido de `QUEUE_CONNECTION`, com fallback `'redis'`)
- [ ] `config/queue.php` conexão `redis` tem `retry_after` = `90` (segundos) e `block_for` = `null`
- [ ] `config/queue.php` não usa mais `'database'` como driver padrão — removido como default, mantido na lista de conexões para compatibilidade
- [ ] `config/cache.php` tem `default` = `redis` (lido de `CACHE_STORE`, com fallback `'redis'`)
- [ ] `config/cache.php` store `redis` tem prefixo `CACHE_PREFIX` lido do env, com fallback para `portalartfinal_{APP_ENV}_cache`
- [ ] `config/horizon.php` tem `prefix` = `portalartfinal_horizon:` (lido de `HORIZON_PREFIX`)
- [ ] `config/horizon.php` tem supervisor `supervisor-high` cobrindo as filas `gateway,webhooks` com `balance = 'auto'`, `minProcesses = 1`, `maxProcesses = 5`
- [ ] `config/horizon.php` tem supervisor `supervisor-default` cobrindo as filas `default,emails` com `balance = 'auto'`, `minProcesses = 1`, `maxProcesses = 3`
- [ ] `config/horizon.php` tem supervisor `supervisor-low` cobrindo as filas `exports,pdf` com `balance = 'simple'`, `minProcesses = 1`, `maxProcesses = 2`
- [ ] `config/horizon.php` configura `waits` com thresholds: `gateway` = 30s, `webhooks` = 30s, `default` = 60s, `emails` = 60s, `exports` = 300s, `pdf` = 300s
- [ ] `.env.example` contém todas as variáveis Redis/queue/cache necessárias conforme lista nas Observações técnicas
- [ ] `php artisan horizon:status` executa sem erros de configuração (pode retornar "inactive" — apenas não deve lançar exceção)
- [ ] `./vendor/bin/pint --dirty` passa sem alterações
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros neste arquivo

## Technical Notes

### Arquivos a criar/modificar

- `config/queue.php` — alterar `default` de `database` para `redis`; manter conexão `database` na lista
- `config/cache.php` — confirmar `default = redis`; adicionar prefixo dinâmico por ambiente
- `config/horizon.php` — adicionar blocos `supervisors` com os 3 supervisores (high/default/low) e `waits`
- `.env.example` — adicionar bloco completo de variáveis Redis/queue/Horizon

### Variáveis a adicionar ao .env.example

```dotenv
# Queue
QUEUE_CONNECTION=redis
REDIS_QUEUE=default

# Cache
CACHE_STORE=redis
CACHE_PREFIX=portalartfinal_local_cache

# Redis — filas nomeadas (usadas pelo Horizon)
# Não há variável de env por fila — os nomes são hardcoded no config/horizon.php
# Os nomes são: gateway, webhooks, default, emails, exports, pdf

# Horizon
HORIZON_NAME="Portal ArtFinal"
HORIZON_PREFIX=portalartfinal_horizon:
HORIZON_DOMAIN=

# Redis adicional (se usar conexão separada para filas)
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE_RETRY_AFTER=90
```

### Estrutura esperada dos supervisors em config/horizon.php

```php
'supervisors' => [
    'supervisor-high' => [
        'connection' => 'redis',
        'queue' => ['gateway', 'webhooks'],
        'balance' => 'auto',
        'minProcesses' => 1,
        'maxProcesses' => 5,
        'balanceMaxShift' => 1,
        'balanceCooldown' => 3,
        'tries' => 3,
        'timeout' => 60,
        'nice' => 0,
    ],
    'supervisor-default' => [
        'connection' => 'redis',
        'queue' => ['default', 'emails'],
        'balance' => 'auto',
        'minProcesses' => 1,
        'maxProcesses' => 3,
        'balanceMaxShift' => 1,
        'balanceCooldown' => 3,
        'tries' => 3,
        'timeout' => 90,
        'nice' => 0,
    ],
    'supervisor-low' => [
        'connection' => 'redis',
        'queue' => ['exports', 'pdf'],
        'balance' => 'simple',
        'minProcesses' => 1,
        'maxProcesses' => 2,
        'tries' => 2,
        'timeout' => 300,
        'nice' => 10,
    ],
],
```

### Observações técnicas

- O `config/horizon.php` gerado pelo `horizon:install` já existe no projeto — **não sobrescrever o arquivo inteiro**. Adicionar/modificar apenas as chaves `supervisors`, `waits` e confirmar `prefix`.
- Os nomes de filas são **hardcoded** no `config/horizon.php` (não via env). Isso é intencional — a topologia de filas é uma decisão arquitetural, não de ambiente.
- A fila `gateway` tem `timeout = 60s` e a `pdf` tem `timeout = 300s`. Esses valores devem ser **menores** que o `retry_after` da conexão Redis (90s para gateway — atenção: o timeout de 60s é menor que o retry_after de 90s, o que é correto: o job falha antes de ser recolocado na fila). Para a fila `pdf` com timeout 300s, o `retry_after` da conexão Redis deve ser ajustado para 360s para evitar jobs duplicados. Registrar isso como decisão técnica no arquivo.
- O prefixo de cache `portalartfinal_{APP_ENV}_cache` usa substituição de variável Laravel no `config/cache.php` via `env('APP_ENV', 'production')` — não via string interpolada direto.
- O `waits` do Horizon define o threshold em **segundos** para notificação de `LongWaitDetected`. Configurar por fila é mais preciso que o threshold global.
- Supervisores são blocos **por ambiente** em `config/horizon.php` — o bloco correto é selecionado via `environment()`. Garantir que o bloco dos supervisores esteja dentro do ambiente `local` e `production` (duplicar se necessário, ou usar `default`).

## Dependencies

- **Blocked by:** STORY-001 (Horizon instalado via HorizonServiceProvider já existente), STORY-004 (providers registrados)
- **Blocks:** F1-E3 (middlewares de rate limit usam Redis indiretamente), F1-E4 (Models com jobs usam as filas nomeadas)

## Testing Requirements

- [ ] `php artisan test --compact --filter=STORY005` verde
- [ ] Teste de integração: `Queue::connection('redis')->size('gateway')` retorna `0` (fila existe e está acessível)
- [ ] Teste de integração: `Cache::store('redis')->put('story005-test', true, 10)` e `Cache::store('redis')->get('story005-test')` retorna `true`
- [ ] Teste de configuração: `config('horizon.supervisors.supervisor-high.queue')` contém `'gateway'` e `'webhooks'`
- [ ] Teste de configuração: `config('queue.default')` retorna `'redis'`
- [ ] `php artisan horizon:status` retorna código de saída `0` (sem exceção de configuração)
