# Módulo de RH — Suíte de Documentação (Fase 1)

Documentação de produto e técnica do **super módulo de RH** do HT2 ERP. Cobre da gestão completa da pessoa (cadastro, documentos, histórico) à jornada, horas extras e organograma com ACL hierárquica — entregue como **pacote Composer** `ht2erp/modulo-rh` (`HT2ERP\Rh\`), aditivo ao core (ver [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).

> **Status:** planejamento (greenfield). O pacote `packages/modulo-rh` ainda não existe — está declarado no `composer.json` e há testes de intenção (`tests/Feature/Rh/`). Esta suíte é o blueprint para implementá-lo. **Nada aqui é código de produção.**

---

## Índice da suíte

| #   | Documento                                                                                          | Para quê                                                                                                         |
| --- | -------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| —   | **[README.md](README.md)**                                                                         | Este índice, cobertura de requisitos, glossário, permissões (resumo; canônicas em 01 §10)                        |
| 00  | **[00-prd.md](00-prd.md)**                                                                         | PRD: visão, personas, objetivos, escopo IN/OUT, matriz S-2200, requisitos não-funcionais                         |
| 01  | **[01-modelo-de-dominio.md](01-modelo-de-dominio.md)**                                             | **Fonte de verdade** de schema: ≈21 tabelas, enums, seeds, LGPD, **permissões canônicas (§10)**                  |
| 02  | **[02-fase-1-blueprint.md](02-fase-1-blueprint.md)**                                               | Decomposição da Fase 1 em 7 blocos (B1–B7), dependências, DoD                                                    |
| 03  | **[03-cadastro-pessoa-documentos.md](03-cadastro-pessoa-documentos.md)**                           | Spec do cadastro completo (abas, validações) + documentos seguros                                                |
| 04  | **[04-catalogos-configuraveis.md](04-catalogos-configuraveis.md)**                                 | Catálogos CRUD pelo cliente (departamento, função, tipos, escala, rubrica) + seeds                               |
| 05  | **[05-organograma-acl-hierarquica.md](05-organograma-acl-hierarquica.md)**                         | Organograma + ACL de visibilidade por hierarquia + self-service                                                  |
| 06  | **[06-linha-do-tempo.md](06-linha-do-tempo.md)**                                                   | Histórico funcional como eventos imutáveis + afastamentos                                                        |
| 07  | **[07-jornada-horas-extras-folha.md](07-jornada-horas-extras-folha.md)**                           | Jornada/escalas, cálculo de HE, workflow, fundação de folha                                                      |
| 08  | **[08-arquitetura-tecnica.md](08-arquitetura-tecnica.md)**                                         | Guia de implementação como pacote: comandos, camadas, testes                                                     |
| 09  | **[09-roadmap-fases.md](09-roadmap-fases.md)**                                                     | Roadmap até a marcação de ponto em dispositivo (fase final)                                                      |
| 10  | **[10-campos-personalizados.md](10-campos-personalizados.md)**                                     | Campos definidos pelo cliente (JSONB-híbrido): definições + valores + trait reutilizável                         |
| 11  | **[11-importacao-exportacao.md](11-importacao-exportacao.md)**                                     | Importação/exportação Excel do funcionário (multi-aba, round-trip, assíncrono)                                   |
| 12  | **[12-ausencias-faltas-atestados-afastamentos.md](12-ausencias-faltas-atestados-afastamentos.md)** | Ausências: atestado com workflow + faltas/ocorrências + fronteira com o afastamento ([06](06-linha-do-tempo.md)) |
| ADR | **[adrs/](adrs/)**                                                                                 | 10 decisões de arquitetura do módulo (ADR-RH-001..010)                                                           |

**Por onde começar:** Produto/PO → 00 → 09. Dev → 01 → 02 → 08 → specs (03–12). QA → 02 (DoD) + specs. Arquitetura → ADRs + 05 + 07. Revisão complementar (campos personalizados, import/export, ausências) → 10 · 11 · 12 + ADR-RH-008..010.

---

## Cobertura dos requisitos do cliente

Cada pedido do briefing original mapeado ao documento que o cobre:

| Requisito do cliente                                               | Onde está                                                                                                                                                                                 |
| ------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Cadastro completo da pessoa                                        | [03](03-cadastro-pessoa-documentos.md) · [01 §B1](01-modelo-de-dominio.md)                                                                                                                |
| Upload de documentos salvos de forma segura                        | [03](03-cadastro-pessoa-documentos.md) (reusa `Anexo`, disco privado)                                                                                                                     |
| Linha do tempo (promoções de cargo, salário…)                      | [06](06-linha-do-tempo.md)                                                                                                                                                                |
| Qual departamento/setor a pessoa é                                 | [04](04-catalogos-configuraveis.md) · [01](01-modelo-de-dominio.md) (`departamentos`)                                                                                                     |
| Cargo                                                              | [04](04-catalogos-configuraveis.md) (referência CBO `cargos`)                                                                                                                             |
| Extras (líder, preposto, etc.)                                     | [04](04-catalogos-configuraveis.md) (`funcoes` N:N)                                                                                                                                       |
| Carga de trabalho (dias da semana, horário de cada dia)            | [07](07-jornada-horas-extras-folha.md) (`escalas`/`escala_dias`)                                                                                                                          |
| Hora extra com cálculo automático                                  | [07](07-jornada-horas-extras-folha.md)                                                                                                                                                    |
| Gestor preenche as horas extras                                    | [07](07-jornada-horas-extras-folha.md) (lançamento) + [05](05-organograma-acl-hierarquica.md) (restrito à equipe)                                                                         |
| Organograma dos cargos e hierarquia                                | [05](05-organograma-acl-hierarquica.md)                                                                                                                                                   |
| Mini-ACL: gerente vê líder/preposto/colaboradores abaixo (cascata) | [05](05-organograma-acl-hierarquica.md)                                                                                                                                                   |
| CRUD de tipos de documento, cargo, departamento, extras            | [04](04-catalogos-configuraveis.md)                                                                                                                                                       |
| Padrão já preenchido nas seeders                                   | [01 §5](01-modelo-de-dominio.md) + [04](04-catalogos-configuraveis.md)                                                                                                                    |
| Flexibilidade total para o cliente ajustar sem código              | [04](04-catalogos-configuraveis.md) + [ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)                                                                                         |
| Self-service do colaborador _(decisão de escopo)_                  | [05](05-organograma-acl-hierarquica.md) + [03](03-cadastro-pessoa-documentos.md)                                                                                                          |
| Modelo eSocial-ready _(decisão de escopo)_                         | [01](01-modelo-de-dominio.md) + [03](03-cadastro-pessoa-documentos.md) · matriz S-2200 em [00 §4.1](00-prd.md) · [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md) |
| Fundação de folha _(decisão de escopo)_                            | [07](07-jornada-horas-extras-folha.md)                                                                                                                                                    |
| Fases seguintes até marcação de ponto em dispositivo               | [09](09-roadmap-fases.md)                                                                                                                                                                 |

### Requisitos do briefing complementar (revisão)

As 7 necessidades novas trazidas pelo cliente nesta revisão e onde cada uma é tratada (posicionamento por fase em [09 §1.1](09-roadmap-fases.md); incrementos da Fase 1 × pós-Fase 1 em [02 §7](02-fase-1-blueprint.md)):

| #   | Necessidade                                    | Onde                                                                                                                                                      |
| --- | ---------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Campos personalizados pelo cliente             | [10](10-campos-personalizados.md) · [01 §A11](01-modelo-de-dominio.md) · [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)                           |
| 2   | Importação/exportação Excel de funcionários    | [11](11-importacao-exportacao.md) · [01 §F](01-modelo-de-dominio.md)                                                                                      |
| 3   | Documentos em lote (multi-arquivo + ZIP + tag) | [03 §8.5/§8.6](03-cadastro-pessoa-documentos.md)                                                                                                          |
| 4   | Proteção/armazenamento de documentos           | [03 §8.3](03-cadastro-pessoa-documentos.md) · [01 §8](01-modelo-de-dominio.md) · [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)         |
| 5   | Acesso do funcionário (portal)                 | [05 §9](05-organograma-acl-hierarquica.md) · [03 §11](03-cadastro-pessoa-documentos.md)                                                                   |
| 6   | Controle/envio de atestados                    | [12](12-ausencias-faltas-atestados-afastamentos.md) · [01 §C3](01-modelo-de-dominio.md) · [ADR-RH-010](adrs/ADR-RH-010-atestados-workflow-e-ausencias.md) |
| 7   | Faltas / atestados / afastamentos              | [12](12-ausencias-faltas-atestados-afastamentos.md) · [06 §5](06-linha-do-tempo.md) · [01 §C4](01-modelo-de-dominio.md)                                   |

### Decisões de escopo confirmadas com o cliente (Fase 1)

1. **Folha:** a Fase 1 entrega a **fundação** (`rubricas`, `tabelas_legais`, HE→rubrica). Apuração/holerite/eSocial transmitido são fases futuras.
2. **Self-service:** o colaborador loga e vê os próprios dados (edita um subconjunto), sob a ACL hierárquica.
3. **eSocial-ready:** campos e catálogos já contemplam domínios/códigos oficiais, sem transmitir.

---

## Roadmap (visão de uma linha)

| Fase  | Tema                                                                                       | Status                     |
| ----- | ------------------------------------------------------------------------------------------ | -------------------------- |
| **1** | Cadastro, documentos, catálogos, organograma+ACL, jornada, horas extras, fundação de folha | **planejada (esta suíte)** |
| 2     | Ausências e tempo (férias, banco de horas)                                                 | futura                     |
| 3     | Folha de pagamento completa (apuração, holerite)                                           | futura                     |
| 4     | eSocial (eventos S-1000…S-2299)                                                            | futura                     |
| 5     | Controle de ponto — espelho/folha de ponto                                                 | futura                     |
| 6     | **Marcação de ponto integrada em dispositivo** (REP/biometria/app)                         | futura (objetivo final)    |

As 6 fases acima são o **eixo Departamento Pessoal → Folha → eSocial → Ponto** do `modulo-rh`. Para um RH "muito completo", os **eixos estratégicos** vizinhos entram como **módulos-pacote satélites** (mesma estratégia aditiva do ADR-0015 — ex.: `ht2erp/modulo-sst`, `ht2erp/modulo-ats`), não como inchaço do `modulo-rh`:

- **SST — Saúde e Segurança** (ASO/PCMSO, EPI, CAT, PGR; eSocial **S-2210/S-2220/S-2240**) · **Benefícios** (VT/VR/VA, plano de saúde) · **Recrutamento & Seleção (ATS)** · **Treinamento & Desenvolvimento** · **Avaliação de Desempenho** (ciclos, OKR, 9-box).

Detalhe das fases e dos eixos satélites em [09-roadmap-fases.md](09-roadmap-fases.md); a estratégia "RH como família de módulos-pacote" em [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md).

---

## Permissões `rh.*` (resumo)

> **Fonte de verdade: [01 §10 — Permissões canônicas](01-modelo-de-dominio.md).** Esta tabela é um **resumo**; o que `access:sync` publica e os slugs exatos (snake_case, `rh.<recurso>.<acao>`) estão em 01 §10. Divergência de vocabulário corrige-se **lá primeiro**.

O `make:modulo` gera o CRUD padrão `listar/criar/editar/deletar/restaurar/excluir_permanente`; recursos especiais têm verbos próprios. Mescladas em `config('access.modules')['negocio']` pelo `RhServiceProvider` (ADR-0015) e sincronizadas por `php artisan access:sync`.

| Recurso                                                                                                                     | Permissões (slugs canônicos — 01 §10)                                                                                   |
| --------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| Funcionários                                                                                                                | CRUD + lixeira · **`ver_todos`** (desliga o escopo hierárquico) · **`ver_dados_sensiveis`** (grupo PCD — dado de saúde) |
| Catálogos (`departamentos`, `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`, `rubricas`, `fator_horas_extras`) | CRUD + lixeira                                                                                                          |
| Documentos do funcionário                                                                                                   | `listar` · `criar` · `editar` · `deletar` (opcional; default: aba do funcionário)                                       |
| Linha do tempo (`eventos`)                                                                                                  | `listar` · `registrar` (append-only)                                                                                    |
| Afastamentos                                                                                                                | CRUD + lixeira · **`ver_cid`** (dado de saúde, LGPD) — UI rotula `criar`="registrar", `editar`="encerrar"               |
| Horas extras                                                                                                                | `listar` · `lancar` · `aprovar` · `estornar` · `marcar_paga` · `ver_valores` (sem lixeira; ciclo por status)            |
| Organograma / Self-service                                                                                                  | `rh.organograma.ver` · `rh.self.ver`                                                                                    |
| Pivôs de vigência                                                                                                           | `rh.funcoes_funcionario.{atribuir,encerrar}` · `rh.escala_funcionario.{atribuir,encerrar}`                              |
| Tabelas legais                                                                                                              | `rh.tabelas_legais.{listar,ver}` (referência — leitura)                                                                 |

Prefixo `rh.` obrigatório (anti-colisão, ADR-0015). Convivem com o RBAC de dois níveis (papéis globais + por empresa) e a ACL hierárquica ([05](05-organograma-acl-hierarquica.md)). `rh.funcionarios.ver` (abrir um cadastro) é subsumido por `listar` no conjunto gerado (ver nota em 01 §10).

**Permissões da revisão (canônicas em [01 §10](01-modelo-de-dominio.md)):** `rh.campos_personalizados.{listar,criar,editar,deletar,restaurar,excluir_permanente}` (gestão das **definições**; editar os valores segue `rh.funcionarios.editar` — [10](10-campos-personalizados.md)) · `rh.atestados.{listar,criar,editar,analisar,aprovar,rejeitar,deletar,restaurar,excluir_permanente}` **+ `ver_cid`** (dado de saúde — [12](12-ausencias-faltas-atestados-afastamentos.md)) · `rh.ocorrencias.{listar,criar,editar,deletar,restaurar,excluir_permanente}` _(+ `abonar` opcional)_ · `rh.funcionarios.{importar,exportar}` (planilha — [11](11-importacao-exportacao.md)).

---

## Glossário

- **Departamento** (sinônimo: _Setor_) — unidade organizacional, com sub-departamentos por auto-referência. A entidade é `Departamento` por consistência com os testes/menu já no repo; "setor" é o termo coloquial equivalente.
- **Organograma** — árvore de **pessoas** (`gestor_id`) que governa a ACL de visibilidade; distinto do organograma de **cargos** (níveis).
- **ACL hierárquica** — terceiro eixo de acesso: um usuário vê apenas a subárvore de subordinados (tenant **AND** RBAC **AND** organograma).
- **Função / Extra** — papel funcional acumulável (líder, preposto, supervisor…), N:N com vigência.
- **Jornada / Escala** — definição de dias e horários de trabalho, reutilizável e com vigência.
- **Rubrica** — item de folha (provento/desconto) com regras de incidência (INSS/FGTS/IRRF).
- **Vigência** — par início/fim que versiona uma atribuição (escala, função) no tempo.
- **Snapshot imutável** — cópia congelada de um cálculo/estado (ADR-0009); usada na HE aprovada e na linha do tempo.
- **Lixeira** — soft-delete com 3 níveis (deletar→lixeira, restaurar, excluir_permanente).

---

## Notas de reconciliação (para a implementação)

Pontos onde a documentação encontra o código existente — decididos aqui para o time não tropeçar:

- **Departamento vs Setor** — adotado `Departamento` (`HT2ERP\Rh\Models\Departamento`, tabela `departamentos`), alinhado ao teste `tests/Feature/Rh/RhLixeiraTest.php` e ao item de menu `rh-departamentos`. "Setor" é sinônimo de UI.
- **Menu** — os itens do RH ficam no grupo **RH** da seção **Tabelas Auxiliares** (ver `AplicarMenuPadraoAction`), não na seção "Negócio" do stub padrão.
- **Cargo** — reaproveita a referência global `cargos` (CBO), satisfazendo `tests/Feature/Rh/FuncionarioCargoTest.php` (`cargosDisponiveis`); cargos próprios por empresa ficam como evolução ([ADR-RH-002](adrs/ADR-RH-002-fronteira-enum-vs-catalogo.md)).
- **Upload seguro** — o `GerenciadorAnexos` do core hoje grava no disco `public`; para documentos de RH (PII) deve-se parametrizar o disco para **privado** (`rh_privado`) + URL assinada + download por Policy ([03 §8.3](03-cadastro-pessoa-documentos.md) · [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)). O multi-upload/ZIP e a detecção por tag reusam o mesmo `Anexo`/Dropzone ([03 §8.5/§8.6](03-cadastro-pessoa-documentos.md)).
- **Linha do tempo (UI)** — `x-admin.timeline-table` existe mas é especializado em "programações" (períodos); a decisão registrada é **criar `x-admin.event-timeline`** (eventos pontuais), não generalizar o existente ([06 §6.1](06-linha-do-tempo.md)).
- **Organograma (CTE)** — a subárvore usa `WITH RECURSIVE` (Postgres); os testes do organograma rodam em **Postgres**, não SQLite ([05](05-organograma-acl-hierarquica.md) · [08](08-arquitetura-tecnica.md)).
- **ULID** — não há `HasUlid` no core; o módulo usa `id` interno (ADR-0004 fica como evolução).
