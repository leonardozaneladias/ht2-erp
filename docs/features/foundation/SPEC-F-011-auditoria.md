---
title: SPEC-F-011 — Auditoria append-only
version: 0.1.0
date: 2026-04-19
status: stub
feature_id: SPEC-F-011
fase: foundation
story_points: 2
depends_on: []
unlocks: []
---

# SPEC-F-011 — Auditoria append-only

> **Fundacional.** Formaliza o uso de `spatie/laravel-activitylog` como camada transversal de auditoria. Hoje mencionado em CLAUDE.md §13 e PLANEJAMENTO_BACKEND §13, mas sem padrão único para todos os SPECs seguirem.

---

## 1. Escopo

### 1.1 Coberto

- Convenção de uso do `LogsActivity` trait em models críticos
- Convenção de `causer`/`subject` (quem fez / em que)
- Campos `properties` padronizados (ip, user_agent, request_id)
- Retenção: auditoria é **append-only** (nunca UPDATE ou DELETE)
- Dashboard de auditoria (admin) — escopo mínimo

### 1.2 Fora do escopo

- Auditoria de consultas SELECT (compliance PCI) — fora MVP
- Exportação de logs para SIEM externo — v2+

---

## 2. Models que devem logar

| Model           | Eventos          | Atributos logados                                         |
| --------------- | ---------------- | --------------------------------------------------------- |
| `Adesao`        | created, updated | status, valor_total_centavos, responsáveis, origem_adesao |
| `Parcela`       | updated          | status, valor_reajustado_centavos, pago_at                |
| `Pagamento`     | created, updated | status, gateway_reference, metodo                         |
| `Contrato`      | created, updated | status, adesao_publica_ativa, evento_id                   |
| `TermoContrato` | created          | versao, hash_conteudo                                     |
| `AceiteTermo`   | created          | conteudo_renderizado (truncado), hash                     |
| `ConviteToken`  | created, updated | status                                                    |
| `PortalUser`    | created, updated | status (mudança de `incompleto` → `ativo`)                |
| `WebhookEvento` | created          | provider, tipo_evento                                     |

---

## 3. Propriedades padronizadas

Todo log deve incluir via `withProperties`:

```php
[
    'ip'         => $request->ip(),
    'user_agent' => $request->userAgent(),
    'request_id' => $request->header('X-Request-Id'),
    'origem'     => 'admin|portal|publico|webhook|job',
]
```

Para adesões via SPEC-010 (anônima), adiciona:

```php
[
    'codigo_turma_usado' => 'MED-USP-2026',
    'cpf_hash'           => 'sha256(...)',
    'jwt_jti'            => 'ulid',
]
```

---

## 4. Padrão de uso (convenção)

```php
// Model
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Adesao extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('adesao');
    }
}
```

```php
// Action — log manual quando fluxo é complexo
activity('adesao')
    ->performedOn($adesao)
    ->causedBy($user)
    ->event(ActivityEvent::Created)
    ->withProperties([...])
    ->log('Adesão criada via código público');
```

---

## 5. Dashboard de auditoria (admin)

Escopo mínimo para MVP:

- Filtro por: data, usuário, tipo de evento, model, origem
- Busca full-text em `description`
- Visualização de `attribute_changes` (diff antes/depois) em JSON formatado
- **Não permite delete ou edit** — botões removidos
- Export CSV (fila `exports`)

---

## 6. Retenção

- Logs em `activity_log` ficam **indefinidamente** no MVP
- Reavaliar após 1 ano (volume esperado: 1-2 GB/ano para 100 formandos)
- Job futuro `ArquivarLogsAntigosJob` move logs > 2 anos para cold storage (S3 Glacier)

---

## 7. Pontos a expandir na versão `draft`

- [ ] Lista completa de models com `LogsActivity` e atributos exatos
- [ ] Cobertura de rastreabilidade: garantir que nenhum write "importante" escapa do log
- [ ] Views de auditoria específicas: timeline por formando, timeline por adesão
- [ ] Alerta Sentry em eventos críticos: cancelamento de adesão pós-pagamento, reembolso, reset de senha admin
- [ ] Teste de arch: `it('todo Model em App\Models\* usa LogsActivity')` com exceções whitelistadas

---

## 8. Referências

- [`CLAUDE.md`](../../../CLAUDE.md) §13 — pacote spatie/activitylog
- [`docs/prd/PLANEJAMENTO_BACKEND_APIV1.md` §13](../../prd/PLANEJAMENTO_BACKEND_APIV1.md) — snapshots e governança
- [`docs/_archive/PRD_Sistema_Formatura_v3.1.0.md`](../../_archive/PRD_Sistema_Formatura_v3.1.0.md) §14.22 — dashboard de auditoria original

---

_**Estado:** `stub`._
