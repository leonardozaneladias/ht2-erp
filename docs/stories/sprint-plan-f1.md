# Sprint Plan — F1: Fundação de domínio e API-ready

**Projeto:** Portal ArtFinal  
**Fase:** F1  
**Sprints:** 2 (Sprint 1.1 + Sprint 1.2)  
**Duração por sprint:** 2 semanas (10 dias úteis)  
**Criado em:** 2026-04-19  
**Total:** 34 SP em 14 stories

---

## Épicos da Fase F1

| Épico     | Nome                      | SP        | Stories             |
| --------- | ------------------------- | --------- | ------------------- |
| F1-E1     | Setup & Configuração      | 5 SP      | STORY-001, 002      |
| F1-E2     | Infraestrutura de domínio | 8 SP      | STORY-003, 004, 005 |
| F1-E3     | Camada HTTP               | 8 SP      | STORY-006, 007, 008 |
| F1-E4     | Modelos e banco de dados  | 8 SP      | STORY-009, 010, 011 |
| F1-E5     | Tipos de domínio          | 3 SP      | STORY-012, 013      |
| F1-E6     | Qualidade e CI            | 2 SP      | STORY-014           |
| **Total** |                           | **34 SP** | **14 stories**      |

---

## Sprint 1.1 — "Fundação técnica e infraestrutura"

**Meta:** Setup completo, infraestrutura de domínio, routes skeleton e CI operacional  
**Datas:** 2026-04-21 → 2026-05-02  
**Capacidade:** 17 SP

### Backlog Sprint 1.1

| Story                     | Título                                                     | SP        | Prioridade | Dependências |
| ------------------------- | ---------------------------------------------------------- | --------- | ---------- | ------------ |
| [STORY-001](STORY-001.md) | Instalar e configurar pacotes Composer base                | 3         | Must Have  | Nenhuma      |
| [STORY-002](STORY-002.md) | Configurar guards, Sanctum e CORS                          | 2         | Must Have  | STORY-001    |
| [STORY-003](STORY-003.md) | Trait HasUlid + CorrelationContext + bounded contexts      | 3         | Must Have  | STORY-001    |
| [STORY-004](STORY-004.md) | Service Providers: RateLimiter, Gateway, DomainEvent, Auth | 3         | Must Have  | STORY-002    |
| [STORY-005](STORY-005.md) | Configurar Redis, filas e cache base                       | 2         | Must Have  | STORY-001    |
| [STORY-006](STORY-006.md) | Routes skeleton: api/v1.php e webhook.php                  | 2         | Must Have  | STORY-002    |
| [STORY-014](STORY-014.md) | Pest arch tests + CI GitHub Actions                        | 2         | Must Have  | STORY-003    |
| **Total**                 |                                                            | **17 SP** |            |              |

### Sequência de implementação Sprint 1.1

```
Dia 1-2:  STORY-001 (pacotes) → desbloqueador de tudo
Dia 2-3:  STORY-002 (guards) em paralelo com STORY-003 (HasUlid)
Dia 3-4:  STORY-004 (providers) + STORY-005 (redis/filas)
Dia 4-5:  STORY-006 (routes skeleton)
Dia 6-7:  STORY-014 (arch tests + CI) — roda contra tudo já criado
Dia 8-10: Buffer + revisão + fix de issues do CI
```

### Grafo de dependências Sprint 1.1

```
STORY-001 (base)
  ├─> STORY-002 → STORY-004
  │              → STORY-006
  ├─> STORY-003 → STORY-014
  └─> STORY-005
```

---

## Sprint 1.2 — "Camada HTTP, modelos e tipos de domínio"

**Meta:** Middlewares completos, migrations A+B, models com HasUlid, Enums/DTOs/Actions base  
**Datas:** 2026-05-05 → 2026-05-16  
**Capacidade:** 17 SP

### Backlog Sprint 1.2

| Story                     | Título                                                                   | SP        | Prioridade | Dependências   |
| ------------------------- | ------------------------------------------------------------------------ | --------- | ---------- | -------------- |
| [STORY-007](STORY-007.md) | Middlewares core: AttachRequestId, IdempotencyKeyGuard, RateLimitByActor | 3         | Must Have  | STORY-004, 006 |
| [STORY-008](STORY-008.md) | Middleware ResolveConviteToken + validar GET /api/v1/me                  | 3         | Must Have  | STORY-007      |
| [STORY-009](STORY-009.md) | Migrations bloco A — identidade                                          | 2         | Must Have  | STORY-001      |
| [STORY-010](STORY-010.md) | Migrations bloco B — cadastro + FK portal_users→turmas                   | 3         | Must Have  | STORY-009      |
| [STORY-011](STORY-011.md) | Models base com HasUlid e relacionamentos                                | 3         | Must Have  | STORY-003, 010 |
| [STORY-012](STORY-012.md) | Enums de domínio base                                                    | 1         | Must Have  | STORY-003      |
| [STORY-013](STORY-013.md) | DTOs base + Actions skeleton + Exceções de domínio                       | 2         | Must Have  | STORY-012      |
| **Total**                 |                                                                          | **17 SP** |            |                |

### Sequência de implementação Sprint 1.2

```
Dia 1-2:  STORY-007 (middlewares) + STORY-009 (migrations A) — paralelo
Dia 2-3:  STORY-008 (ResolveConviteToken + MeController)
Dia 3-4:  STORY-010 (migrations B) → STORY-011 (models)
Dia 4-5:  STORY-012 (enums) → STORY-013 (DTOs + Actions)
Dia 6-8:  Integração + testes de aceite
Dia 9-10: Buffer + validação critérios da fase + PR
```

### Grafo de dependências Sprint 1.2

```
Sprint 1.1 (concluída)
  ├─> STORY-007 → STORY-008 ── valida F1 aceite
  ├─> STORY-009 → STORY-010 → STORY-011
  └─> STORY-012 → STORY-013
```

---

## Critérios de aceite da Fase F1 (fim do Sprint 1.2)

- [ ] `php artisan test --compact` passa 100%
- [ ] `./vendor/bin/phpstan analyse --level=6` sem erros
- [ ] `./vendor/bin/pint --dirty` sem alterações
- [ ] `GET /api/v1/me` → 401 sem token, 200 com Sanctum válido
- [ ] `POST /api/v1/convite/{token}/rsvp` resolve token via ResolveConviteToken
- [ ] 100% dos arquivos PHP com `declare(strict_types=1)` (arch test verde)
- [ ] Nenhum ID sequencial em URL ou resposta da API
- [ ] CI verde no GitHub Actions (4 jobs: lint, analyse, test, format)
- [ ] Grafo de dependências FK sem FK circular aberta

---

## Riscos identificados

| Risco                                        | Probabilidade | Impacto | Mitigação                                        |
| -------------------------------------------- | ------------- | ------- | ------------------------------------------------ |
| saloonphp/laravel-plugin requer config extra | Média         | Baixo   | Verificar docs antes de STORY-001                |
| FK circular portal_users → turmas            | Alta          | Médio   | STORY-010 fecha FK em migration separada         |
| PHPStan level 6 falha em spatie/laravel-data | Média         | Médio   | Adicionar stub em phpstan.neon se necessário     |
| CI GitHub Actions sem PostgreSQL configurado | Baixa         | Alto    | Template de workflow já inclui service container |

---

## Definition of Done (F1)

Uma story da F1 está concluída quando:

- [ ] Código escrito e revisado
- [ ] Todos os acceptance criteria atendidos
- [ ] `pint --dirty` limpo
- [ ] `phpstan --level=6` sem erros novos
- [ ] Testes relevantes passando
- [ ] Commit com mensagem convencional: `feat(f1): ...`
- [ ] PR aberto e aprovado antes do merge

---

_Gerado pelo `scrum-master` BMAD em 2026-04-19. Próximo passo: `/developer` para iniciar STORY-001._
