# 14 — Auditoria Final e Validação da Suíte (pré-desenvolvimento)

> **Registro da auditoria final** da documentação pré-desenvolvimento do módulo de RH (Fase 1). Consolida o que foi analisado, corrigido, complementado e decidido, e atesta que a suíte está **pronta para orientar o desenvolvimento ponto a ponto**. Snapshot de processo — o conteúdo permanente vive nos docs de produto (00–13 + ADRs); aqui ficam o laudo, as decisões e a ordem de uso.
>
> **Data:** 2026-06-16 · Pacote `ht2ml/extensao-rh` · banco **PostgreSQL 16** · fontes de verdade: schema/permissões [01](01-modelo-de-dominio.md), LGPD [01 §8.1](01-modelo-de-dominio.md).

Relacionados: [README](README.md) · [00](00-prd.md) · [01](01-modelo-de-dominio.md) · [02](02-fase-1-blueprint.md) · [13](13-rastreabilidade-e-pendencias.md)

---

## 1. Escopo e método

Auditoria de **verificação** (não reescrita): a suíte já estava madura. O trabalho foi **corrigir inconsistências verificadas, complementar lacunas pontuais, consolidar e registrar decisões**, preservando o que já estava correto. Método: inventário → varredura por dimensão (links, contagens, terminologia, cobertura) **verificada contra os arquivos** (não só por amostragem) → correções cirúrgicas → adições de alto valor → validação final. Princípio: nada removido silenciosamente; divergências de negócio viram decisão registrada ou pendência sinalizada, nunca escolha arbitrária.

---

## 2. Documentos analisados

**14 documentos** (`README` + `00`–`13`) e **10 ADRs** (`ADR-RH-001..010`), todos em `docs/plans/modules/rh/`, mais a verificação de alinhamento com o core (`docs/multi-empresa.md`, `docs/lixeira.md`, `docs/criar-modulo.md`, ADRs `ADR-0009/0010/0014/0015`). Inventário detalhado e finalidade de cada doc: [README — Índice da suíte](README.md).

- **Saúde dos links:** ~960 links internos `.md` verificados — **0 quebrados**.
- **Placeholders de código** (`TODO`/`TBD`/`FIXME`): **0**. Único marcador intencional: `(a definir)` para biometria (Fase 6) na matriz LGPD — esperado.
- **Cobertura:** **0 tabelas órfãs**, **0 funcionalidades órfãs** ("sem órfãos" de [13 §1](13-rastreabilidade-e-pendencias.md) confirmado verdadeiro).

---

## 3. Decisões confirmadas (nesta auditoria)

Decisões de negócio confirmadas com o responsável e **registradas** nos docs de produto:

| ID           | Decisão                                                                                                      | Onde foi registrada                                                                                            |
| ------------ | ------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- |
| **D-GESTOR** | Gestor **vê** a subárvore (leitura) + **lança e aprova** HE conforme política; não edita cadastro.           | [01 §10.1](01-modelo-de-dominio.md) · [00 §8](00-prd.md) · [07 §5.3](07-jornada-horas-extras-folha.md) · RN-39 |
| **D-COLAB**  | Colaborador edita os próprios **contato, endereço e dados bancários** no portal; nunca cargo/salário/status. | [01 §10.1](01-modelo-de-dominio.md) · [00 §8](00-prd.md) · [03 §11](03-cadastro-pessoa-documentos.md) · RN-23  |
| **D-CARGO**  | Fase 1 usa **só CBO** (`cargos` global + `cargo_nivel`); `cargos_empresa` é evolução aditiva.                | [00 §8](00-prd.md) · [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md) · RN-13                       |
| **D-HE**     | HE **sem** salário ou escala vigente na data é **bloqueada + alerta**, nunca estimada. **Resolve PEND-07.**  | [07 §3.2.1](07-jornada-horas-extras-folha.md) · [13 §2](13-rastreabilidade-e-pendencias.md) · RN-35            |

---

## 4. Achados e tratamento

### 4.1 Corrigidos (inconsistências reais, verificadas)

| #   | Achado                                                                                       | Tratamento                                                                                                                         |
| --- | -------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| 1   | **Contagem de tabelas divergente** — README "≈21" × `01 §9` "≈24" × real 25.                 | Desambiguado por escopo: **20 núcleo (7 blocos) → 23 obrigatórias → 25 com 2 opcionais**. Alinhado em README, `01 §9`, `02`, `08`. |
| 2   | **Contagem de enums** — "20 enums do §4" ignorava os 4 de §4.2.                              | Corrigido para **24 enums** (`§4` + `§4.2`) em `02` e `08`.                                                                        |
| 3   | **4 refs de seção informais** em `06` (`§abas`, `§contratação`, `§status`, `§self-service`). | Substituídas pelos números reais do `03` (`§1`, `§7`, `§9`, `§11`).                                                                |
| 4   | **Sem matriz de permissões por perfil** (só "permissões típicas" textuais).                  | Criada **matriz Perfil × permissão** em [01 §10.1](01-modelo-de-dominio.md); `00 §3` e README passam a referenciá-la.              |
| 5   | **Glossário** sem distinção `funcionário`×`colaborador` e demais sinônimos.                  | Ampliado o glossário do README (funcionário/colaborador, gestor/líder/chefe, afastamento×falta×atestado×ocorrência).               |
| 6   | **Autoridade de `gestor_id` ambígua** (form × Action × evento).                              | Nota de reconciliação no README: muda **só via Action** (anti-ciclo) + auditoria; mudança isolada não é evento de timeline.        |
| 7   | **Tópico "Relatórios/consultas" disperso** (lacuna estrutural).                              | Consolidado em [13 §6](13-rastreabilidade-e-pendencias.md) — relatórios da Fase 1 com origem, fase e escopo.                       |

### 4.2 Refutados (apontados em diagnóstico preliminar, mas **falsos** — não alterados)

- "PRD `§5` lista faltas/atestados/afastamentos no Escopo OUT, causando confusão" → **falso**: o `§5` não tem o item; a fronteira está correta em [00 §4.2](00-prd.md).
- "Glossário central ausente" → existe no [README](README.md). "Autoridade de permissões indefinida" → [01 §10](01-modelo-de-dominio.md) já é a fonte com notas de reconciliação. "LGPD dispersa" → consolidada em [01 §8.1](01-modelo-de-dominio.md).

### 4.3 Complementado

- **Catálogo de regras de negócio** `RN-01..60` ([13 §5](13-rastreabilidade-e-pendencias.md)) — invariantes rastreáveis por área, ligadas a doc/§ e bloco/fase.
- Registro das **4 decisões** (D-\*) nos docs de produto e na §3 deste laudo.

---

## 5. Mudanças aplicadas (por arquivo)

| Arquivo                              | Mudança                                                                                                                 |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| `README.md`                          | contagem de tabelas/enums; ref. à matriz de permissões; glossário ampliado; nota de `gestor_id`; índice (13 + este 14). |
| `00-prd.md`                          | `§3` aponta a matriz de permissões; `§8` registra as 4 decisões (item 4).                                               |
| `01-modelo-de-dominio.md`            | `§9` contagem; nova `§10.1` **matriz de atribuição por perfil**.                                                        |
| `02-fase-1-blueprint.md`             | cobertura de tabelas/enums desambiguada (núcleo × total).                                                               |
| `06-linha-do-tempo.md`               | 4 refs de seção corrigidas para números.                                                                                |
| `07-jornada-horas-extras-folha.md`   | nova `§3.2.1` — pré-condição de base de cálculo (D-HE).                                                                 |
| `08-arquitetura-tecnica.md`          | DoD: contagem de tabelas/enums desambiguada.                                                                            |
| `13-rastreabilidade-e-pendencias.md` | PEND-07 baixada (resolvida); nova `§5` catálogo RN-xx; nova `§6` relatórios; título/resumo/nota de manutenção.          |
| `14-auditoria-e-validacao.md`        | **novo** — este laudo.                                                                                                  |

Sem alteração: `03`, `04`, `05`, `09`, `10`, `11`, `12` e os ADRs (já consistentes; apenas referenciados).

---

## 6. Pendências remanescentes

Registro único em [13 §2](13-rastreabilidade-e-pendencias.md). Situação após a auditoria:

- **PEND-07** → **resolvida** (D-HE).
- **Bloqueante restante:** **PEND-08** (disco `rh_privado` na instalação) — resolvível dentro da Fase 1, antes de B2.
- **Não bloqueantes** (evolução / fase futura): PEND-01, 02, 03, 04, 05, 06 (parcial), 09, 10, 11, 12 — todas com default documentado e fase para resolver.

Nenhuma contradição ou dependência bloqueante **não identificada** permanece.

---

## 7. Riscos e dependências

- **Riscos** (detalhe em [00 §7.2](00-prd.md)): sensibilidade LGPD do CID/PCD; ciclos no organograma; "eSocial-ready ≠ transmitido"; fundação de folha sem apuração visível; acoplamento ao core. Todos com mitigação documentada.
- **Dependências de início** ([00 §7.3](00-prd.md) · [13 §2](13-rastreabilidade-e-pendencias.md)): core provê tenancy/RBAC/auditoria/anexos/lixeira/`ModuleRegistry`/gerador; referências globais (`cargos`/CBO, `bancos`, `municipios`…) semeadas; **disco `rh_privado`** a registrar na instalação (PEND-08); testes de organograma exigem **Postgres** (CTE recursiva).
- **Sequência de blocos:** caminho crítico `B1 → B2 → B3`, com B4/B5→B6→B7 em paralelo ([13 §4](13-rastreabilidade-e-pendencias.md)).

---

## 8. Validação final

### 8.1 Cobertura dos 16 tópicos

| #   | Tópico                             | Onde                                                                                                                                                                                                            |
| --- | ---------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Visão geral                        | [00 §1](00-prd.md)                                                                                                                                                                                              |
| 2   | Escopo funcional                   | [00 §4–5](00-prd.md) · [02](02-fase-1-blueprint.md)                                                                                                                                                             |
| 3   | Atores e perfis                    | [00 §3](00-prd.md) · matriz [01 §10.1](01-modelo-de-dominio.md)                                                                                                                                                 |
| 4   | Cadastros principais               | [03](03-cadastro-pessoa-documentos.md) · [04](04-catalogos-configuraveis.md)                                                                                                                                    |
| 5   | Regras de negócio                  | **[13 §5](13-rastreabilidade-e-pendencias.md)** (RN-xx) + specs                                                                                                                                                 |
| 6   | Fluxos operacionais                | [03](03-cadastro-pessoa-documentos.md) · [05](05-organograma-acl-hierarquica.md) · [06](06-linha-do-tempo.md) · [07 §5](07-jornada-horas-extras-folha.md) · [12](12-ausencias-faltas-atestados-afastamentos.md) |
| 7   | Permissões                         | [01 §10](01-modelo-de-dominio.md) + **matriz por perfil [01 §10.1](01-modelo-de-dominio.md)**                                                                                                                   |
| 8   | Dados e relacionamentos            | [01 §2–3](01-modelo-de-dominio.md)                                                                                                                                                                              |
| 9   | Documentos e arquivos              | [03 §8](03-cadastro-pessoa-documentos.md) · [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)                                                                                                    |
| 10  | Importações e exportações          | [11](11-importacao-exportacao.md)                                                                                                                                                                               |
| 11  | Relatórios e consultas             | **[13 §6](13-rastreabilidade-e-pendencias.md)**                                                                                                                                                                 |
| 12  | Históricos e auditorias            | [06](06-linha-do-tempo.md) · [01 §8.2](01-modelo-de-dominio.md)                                                                                                                                                 |
| 13  | Segurança e privacidade            | [01 §8](01-modelo-de-dominio.md) · [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md) · [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)                                  |
| 14  | Dependências entre funcionalidades | [02 §2–3](02-fase-1-blueprint.md) · [13 §1](13-rastreabilidade-e-pendencias.md)                                                                                                                                 |
| 15  | Pendências                         | [13 §2](13-rastreabilidade-e-pendencias.md)                                                                                                                                                                     |
| 16  | Sequência de desenvolvimento       | [13 §4](13-rastreabilidade-e-pendencias.md) · [02](02-fase-1-blueprint.md) · [09](09-roadmap-fases.md)                                                                                                          |

### 8.2 Checklist de validação

- [x] Todas as funcionalidades previstas documentadas (matriz [13 §1](13-rastreabilidade-e-pendencias.md), sem órfãos).
- [x] Sem contradições (achados refutados em §4.2; reais corrigidos em §4.1).
- [x] Sem duplicidades nocivas (fontes únicas: schema/permissões `01`, LGPD `01 §8.1`, permissões por perfil `01 §10.1`, pendências `13 §2`, regras `13 §5`).
- [x] Termos padronizados (glossário ampliado no README).
- [x] Regras de negócio claras e rastreáveis (RN-01..60).
- [x] Fluxos completos com responsáveis (specs 03/05/06/07/12 + matriz por perfil).
- [x] Permissões ligadas às funcionalidades (matriz por recurso `01 §10` + por perfil `01 §10.1`).
- [x] Dados e relacionamentos documentados (`01 §2–3`).
- [x] Casos alternativos/exceções considerados (edge cases em 05/07/12; estornos; fail-closed).
- [x] Dependências identificadas (`02 §2` + `13 §1/§4` + `00 §7.3`).
- [x] Requisitos de segurança contemplados (`01 §8` + ADRs).
- [x] Pendências registradas (`13 §2`); PEND-07 resolvida; só PEND-08 bloqueia (não-oculta).
- [x] Sequência de desenvolvimento coerente (`13 §4`).
- [x] Critérios de conclusão por etapa (`02 §4` DoD + `13 §3` gate).
- [x] Rastreabilidade ponta a ponta (requisito ↔ doc ↔ regra ↔ permissão ↔ entidade ↔ bloco ↔ DoD).
- [x] Links internos íntegros (0 quebrados); contagens consistentes; sem placeholders indevidos.

**Conclusão:** a suíte está **consistente, completa, rastreável e sem dependências bloqueantes não identificadas** — apta a guiar o desenvolvimento ponto a ponto.

---

## 9. Ordem recomendada de uso da documentação

**Antes de começar (entender o porquê e o como):**

1. [README](README.md) → [00 — PRD](00-prd.md) → [09 — Roadmap](09-roadmap-fases.md) — visão, escopo, fases.
2. [01 — Modelo de domínio](01-modelo-de-dominio.md) (fonte de verdade) → [02 — Blueprint](02-fase-1-blueprint.md) → [08 — Arquitetura](08-arquitetura-tecnica.md) — o que construir e como.

**Ao implementar cada bloco (ponto a ponto):**

1. [13 §1](13-rastreabilidade-e-pendencias.md) — localize a funcionalidade (doc, entidades, dependências, DoD).
2. **Spec do bloco** (03–12) — leia o detalhe funcional.
3. [13 §5](13-rastreabilidade-e-pendencias.md) — confira as **RN** da área (invariantes a respeitar).
4. [01 §10/§10.1](01-modelo-de-dominio.md) — permissões do recurso e atribuição por perfil.
5. [13 §6](13-rastreabilidade-e-pendencias.md) — relatórios/consultas que o bloco entrega.
6. [13 §3](13-rastreabilidade-e-pendencias.md) (gate) + [02 §4](02-fase-1-blueprint.md) (DoD) — antes de avançar.

**Em qualquer dúvida de fronteira/decisão:** [00 §8](00-prd.md) (decisões) + [13 §2](13-rastreabilidade-e-pendencias.md) (pendências) + este laudo §3.

> Manutenção: este laudo é um **snapshot**. Mudanças posteriores atualizam os docs de produto e, quando relevantes, registram-se como nova pendência/decisão em [13](13-rastreabilidade-e-pendencias.md) — não neste arquivo.
