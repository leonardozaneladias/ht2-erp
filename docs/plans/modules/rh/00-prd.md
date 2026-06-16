# 00 — PRD: Módulo de RH

Relacionados: [01](01-modelo-de-dominio.md) · [02](02-fase-1-blueprint.md) · [05](05-organograma-acl-hierarquica.md) · [07](07-jornada-horas-extras-folha.md) · [09](09-roadmap-fases.md)

> **Product Requirements Document** do módulo de **Recursos Humanos / Departamento Pessoal** do HT2 ERP.
> Pacote Composer `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · views `rh::` · **aditivo ao core** (ADR-0015), nunca o edita.
> A **fonte de verdade de schema** (tabelas, colunas, enums, permissões) é o [01 — Modelo de Domínio](01-modelo-de-dominio.md). Este documento define o _porquê_, o _para quem_ e o _o quê_ (escopo); o _como_ mora nos blueprints técnicos.

---

## 1. Visão e problema

### 1.1 Visão

> Transformar a gestão de pessoas de PMEs multi-empresa de um **arquivo de papel e planilhas avulsas** em um **cadastro 100% digital, rastreável e eSocial-ready**, onde RH/DP, gestores e o próprio colaborador operam o mesmo sistema com a visibilidade certa para cada um, sobre a base multi-tenant já existente do ERP.

O RH é o **super módulo de pessoas** do ERP: o registro único e confiável de _quem trabalha em cada empresa_, com todo o histórico funcional, os documentos seguros, o organograma vivo e a base de cálculo de horas extras e folha. Tudo isso configurável pelo próprio cliente, sem código.

### 1.2 O problema (dores reais das PMEs alvo)

PMEs com várias empresas/filiais na mesma gestão hoje sofrem com:

| Dor                              | Sintoma no dia a dia                                                       | Custo                                                        |
| -------------------------------- | -------------------------------------------------------------------------- | ------------------------------------------------------------ |
| **Cadastro em papel / planilha** | Ficha do colaborador num arquivo físico ou Excel por filial, sem padrão    | Retrabalho, dado divergente, perda de ficha                  |
| **Documentos espalhados**        | RG/CTPS/ASO em pastas, e-mail, WhatsApp; cópias soltas                     | Risco LGPD, documento vencido sem aviso, sem rastreabilidade |
| **Sem histórico funcional**      | Promoção, reajuste e transferência sobrescrevem o dado anterior            | Impossível responder "qual era o salário em jan/24?"         |
| **Sem visibilidade hierárquica** | Todo mundo vê tudo, ou ninguém vê nada; gestor sem acesso à própria equipe | Vazamento interno de PII / gestor travado                    |
| **Hora extra no caderno**        | HE anotada à mão, calculada na planilha, aprovada no boca a boca           | Erro de cálculo, passivo trabalhista, sem auditoria          |
| **Multi-empresa sem isolamento** | Mesma planilha mistura colaboradores de empresas diferentes                | Vazamento entre empresas, papéis sem escopo                  |
| **Configuração travada em TI**   | Cada tipo de documento/afastamento/escala novo depende de chamado          | Cliente refém do fornecedor                                  |

### 1.3 Por que **um super módulo** (e não telas avulsas)

Os dados de pessoas são fortemente acoplados: o **funcionário** é o agregado-raiz que amarra contatos, endereços, dados bancários, dependentes, documentos, histórico, afastamentos, jornada e horas extras — e é a **chave da ACL hierárquica** (o organograma define quem enxerga quem). Fatiar isso em módulos independentes quebraria a coerência transacional (ex.: um evento de promoção precisa gravar o histórico _e_ atualizar o "atual" na mesma transação) e a visibilidade. Um módulo único, coeso, entrega o cadastro digital, a segurança documental, o organograma e a fundação de folha como um sistema integrado.

### 1.4 Ambição de produto: o RH como **família de módulos**

O objetivo de longo prazo é um RH **muito completo**. Mas "completo" não significa um único pacote monolítico: significa um **núcleo coeso** (`ht2erp/modulo-rh` — o agregado-raiz funcionário, Departamento Pessoal, jornada/HE, fundação de folha, eSocial e ponto, nas fases de [09](09-roadmap-fases.md)) **cercado por módulos-pacote satélites** que cobrem os demais eixos estratégicos de RH, cada um aditivo ao core pelo mesmo padrão do [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md):

- **SST — Saúde e Segurança do Trabalho** (`ht2erp/modulo-sst`): ASO/PCMSO, EPI, CAT, PGR; eSocial **S-2210/S-2220/S-2240** (obrigação acessória que conversa com a Fase 4 deste módulo).
- **Benefícios**: VT/VR/VA, plano de saúde/odontológico, coparticipação (estende `rubricas`).
- **Recrutamento & Seleção (ATS)** (`ht2erp/modulo-ats`): vagas, candidatos, pipeline, banco de talentos.
- **Treinamento & Desenvolvimento**: cursos, trilhas, certificações, matriz de competências.
- **Avaliação de Desempenho**: ciclos, metas/OKR, 9-box, feedback; Onboarding/Offboarding e Clima.

> **A Fase 1 deste PRD é a entrega corrente do núcleo** — não dos satélites. Os eixos acima são **visão de produto** (roadmap e fronteira de pacotes em [09](09-roadmap-fases.md); estratégia em [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md)), registrados aqui para que as decisões de modelagem do núcleo não fechem portas para eles. Eles **não** ampliam o escopo da Fase 1.

---

## 2. Objetivos e métricas de sucesso

| #   | Objetivo                                   | Métrica de sucesso (Fase 1)                                                                                                                                             |
| --- | ------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| O1  | **Cadastro 100% digital e eSocial-ready**  | 100% dos campos pessoais/contratuais do colaborador capturados no sistema; domínios (sexo, raça/cor, vínculo, afastamento) usam códigos oficiais BR sem digitação livre |
| O2  | **Documentos seguros e rastreáveis**       | Todo documento com upload em disco **privado** + URL assinada; relatório de "documentos a vencer"; zero PII em log de auditoria                                         |
| O3  | **Autonomia do cliente (zero-código)**     | Cliente cria/edita departamentos, funções, tipos de documento, tipos de afastamento, escalas e rubricas pela UI, sem deploy                                             |
| O4  | **Organograma com visibilidade por nível** | ACL hierárquica funcionando: gestor enxerga a própria subárvore; colaborador, só a si; RH/admin, a empresa toda                                                         |
| O5  | **Cálculo automático de HE**               | HE lançada → percentual e valor calculados pelo sistema (snapshot imutável); aprovação congela o valor; vira rubrica                                                    |
| O6  | **Base pronta para folha**                 | Catálogo de rubricas + tabelas legais (INSS/IRRF) por vigência semeados e versionados, prontos para a apuração de fases futuras                                         |
| O7  | **Isolamento multi-tenant garantido**      | Nenhum dado de RH atravessa empresas; `unique` sempre por `empresa_id`; papéis por empresa respeitados                                                                  |
| O8  | **Histórico funcional íntegro**            | Toda mudança de cargo/departamento/salário gera evento append-only; linha do tempo reconstrói qualquer estado passado                                                   |

**Anti-metas (o que sucesso _não_ significa na Fase 1):** transmitir eSocial, fechar folha mensal/holerite, controlar banco de horas, gerir férias com workflow, marcar ponto em dispositivo. Ver §5.

---

## 3. Personas

### P1 — RH / DP (operador do módulo)

- **Quem é:** analista de RH ou departamento pessoal; opera o módulo no dia a dia, geralmente para mais de uma empresa do grupo.
- **Objetivos:** admitir/cadastrar rápido e completo; manter documentos em dia; registrar afastamentos, promoções e reajustes; aprovar/conferir horas extras; configurar os catálogos da empresa.
- **Dores que o módulo resolve:** fim do papel e da planilha; documento vencido avisado; histórico sem sobrescrever; HE auditável.
- **O que faz no sistema:** CRUD completo de funcionários e filhas; upload de documentos; lança eventos de histórico e afastamentos (inclusive CID, com permissão dedicada); configura `departamentos`, `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`, `rubricas`; opera a lixeira.
- **Permissões típicas:** o conjunto `rh.*` amplo, incluindo `rh.afastamentos.ver_cid`, `rh.*.deletar/restaurar/excluir_permanente`.

### P2 — Gestor / Líder (chefe de equipe)

- **Quem é:** coordenador, supervisor ou encarregado; tem subordinados no organograma (`funcionarios.gestor_id`).
- **Objetivos:** ver e acompanhar a **própria equipe**; lançar horas extras dos subordinados; consultar dados funcionais de quem lidera.
- **Dores que o módulo resolve:** antes ou via travado, ou via planilha aberta a todos — agora vê exatamente a sua subárvore.
- **O que faz no sistema:** consulta a subárvore (recursiva) que lidera; lança HE para subordinados (`horas_extras.lancado_por_admin_user_id`); aprova HE conforme política; **não** vê PII sensível além do necessário nem outras equipes.
- **Permissões típicas:** `rh.funcionarios.ver` (escopado pela ACL hierárquica à subárvore), `rh.horas_extras.lancar`, eventualmente `rh.horas_extras.aprovar`.

### P3 — Colaborador (self-service)

- **Quem é:** funcionário comum, vinculado a um `admin_user` (FK `funcionarios.admin_user_id`, 1:1 opcional).
- **Objetivos:** consultar os **próprios** dados, conferir documentos, manter contato/endereço/dados bancários atualizados.
- **Dores que o módulo resolve:** dependia do RH para qualquer consulta/atualização cadastral.
- **O que faz no sistema:** loga e vê **apenas o próprio** registro (resolução "qual funcionário sou eu" via `admin_user_id`); **edita um subconjunto** de campos permitidos (ex.: telefone, e-mail pessoal, endereço, dados bancários), sob a ACL hierárquica; **não** altera cargo, salário, status nem dado de terceiros.
- **Permissões típicas:** capacidade de self-service restrita ao próprio vínculo (detalhe em [05](05-organograma-acl-hierarquica.md)).

### P4 — Super-admin / Admin do módulo

- **Quem é:** administrador global (super-admin é **sempre global**, spatie) ou admin do módulo de RH na empresa.
- **Objetivos:** governar o módulo: configurar permissões, atribuir papéis, garantir conformidade (LGPD/auditoria), administrar todas as empresas.
- **Dores que o módulo resolve:** governança central com escopo por empresa, sem abrir tudo a todos.
- **O que faz no sistema:** instala/registra o módulo (permissões e menu via `boot()` do pacote); atribui papéis globais e por empresa (`admin_user_empresa_role`); concede permissões sensíveis (`ver_cid`, `excluir_permanente`); audita; conduz anonimização LGPD.
- **Permissões típicas:** super-admin faz _bypass_ (precedência no `AccessResolver`); admin do módulo recebe o conjunto `rh.*` no escopo da empresa ativa.

---

## 4. Escopo IN — Fase 1 (épicos)

Épicos com IDs **RF-NN** (requisito funcional). Cada épico tem documento técnico de detalhe (ponteiros).

### RF-01 — Cadastro completo de pessoa (agregado-raiz) → [03](03-cadastro-pessoa-documentos.md)

Núcleo `funcionarios` (dados pessoais + contratação, eSocial-ready) e filhas 1:N: `funcionario_contatos` (e-mails/telefones), `funcionario_enderecos`, `funcionario_dados_bancarios`, `funcionario_dependentes`. Validação por FormRequest (CPF, PIS, datas); domínios via enums oficiais; foto em disco privado. Reaproveita referências globais (`cargos`/CBO, `bancos`, `paises`, `municipios`, `tipos_logradouro`).

### RF-02 — Documentos com upload seguro → [03](03-cadastro-pessoa-documentos.md)

`funcionario_documentos` (metadados) com binário no `Anexo` do core (`App\Models\Anexo`, relação polimórfica `anexavel`, disco privado por `disco`). Flags do tipo (`exige_numero/validade/orgao_emissor/arquivo/sensivel_lgpd`) dirigem o formulário. Relatório de "documentos a vencer" (índice em `data_validade`).

### RF-03 — Catálogos configuráveis com seeds → [02](02-fase-1-blueprint.md)

CRUD tenant-scoped de `departamentos` (com sub-departamentos), `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`+`escala_dias`, `rubricas`. Seeds padrão idempotentes por empresa via Action `ProvisionarCatalogosRh` (na criação da empresa, `firstOrCreate`). Catálogos híbridos: a _linha_ é do cliente, as _colunas-flag_ dão comportamento.

### RF-04 — Organograma + ACL hierárquica → [05](05-organograma-acl-hierarquica.md)

Árvore de pessoas (`funcionarios.gestor_id`, self-FK) e de departamentos (`departamentos.departamento_pai_id`). Visibilidade por nível: resolução "qual funcionário sou eu" via `funcionarios.admin_user_id`; subárvore recursiva (CTE) define o que cada usuário enxerga. Camada **adicional** ao RBAC de 2 níveis (não o substitui). `AdminUser` ganha relação inversa `funcionario(): HasOne` no model do pacote (core intocado).

### RF-05 — Self-service do colaborador → [05](05-organograma-acl-hierarquica.md)

Colaborador comum loga e vê os **próprios** dados; **edita um subconjunto** de campos (contato, endereço, dados bancários) sob a ACL hierárquica; nunca cargo/salário/status nem dado de terceiros. _(Decisão de escopo confirmada com o cliente — ver §8.)_

### RF-06 — Linha do tempo / histórico funcional → [06](06-linha-do-tempo.md)

`funcionario_eventos` append-only com snapshot JSONB (ADR-0009): admissão, promoção, alteração/reajuste salarial, transferência de departamento/filial, mudança de cargo, início/fim de afastamento, desligamento. A Action grava o evento **e** atualiza as colunas "atuais" desnormalizadas em `funcionarios` na mesma transação. Sem `deleted_at` (correção = evento de estorno). Inclui `funcionario_afastamentos` (com CID sensível).

### RF-07 — Jornada / escalas → [07](07-jornada-horas-extras-folha.md)

Escalas reutilizáveis (`escalas` cabeçalho + `escala_dias` por dia×turno) criadas pelo cliente; atribuição com vigência e histórico (`escala_funcionario`, no máx. uma vigência aberta). Tipos via enum `TipoEscala`; carga semanal e divisor mensal (base do valor-hora).

### RF-08 — Horas extras com cálculo + aprovação → [07](07-jornada-horas-extras-folha.md)

`horas_extras`: lançamento (gestor) → cálculo automático (percentual por `TipoHoraExtra`, override de fator por empresa) → workflow de aprovação (`StatusHoraExtra`: rascunho/lancada/aprovada/rejeitada/paga/cancelada). Aprovação **congela** valor e percentual em snapshot imutável (`snapshot_calculo` JSONB). HE aprovada referencia uma **rubrica** (`rubrica_id`) — ponte para a folha.

### RF-09 — Fundação de folha → [07 §Folha](07-jornada-horas-extras-folha.md)

Catálogo `rubricas` (proventos/descontos/informativa, com incidências INSS/FGTS/IRRF e `codigo_esocial`) + `tabelas_legais` (referência por vigência: INSS, IRRF, salário-família, payload JSONB de faixas). **Apenas fundação:** alimenta cálculos futuros; **não** há apuração mensal nesta fase (ver §5).

---

## 4.1 Matriz de cobertura do S-2200 (eSocial-ready)

"eSocial-ready" é uma afirmação **verificável**, não um slogan. A tabela abaixo mapeia cada **grupo do evento S-2200** (Cadastramento Inicial / Admissão) à cobertura da Fase 1: **✅ coberto** (campo/estrutura já existe) · **⏳ Fase 4** (adiado para a transmissão — está no Escopo OUT, §5). A Fase 1 **não transmite**; ela garante que o **dado exista no formato certo** para a Fase 4 gerar o XML sem migração. Decisão completa em [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md); schema em [01](01-modelo-de-dominio.md).

| Grupo do S-2200                                          | Conteúdo                                                                | Fase 1 | Onde no modelo ([01](01-modelo-de-dominio.md))                                  |
| -------------------------------------------------------- | ----------------------------------------------------------------------- | ------ | ------------------------------------------------------------------------------- |
| `trabalhador` (identificação)                            | nome, CPF, sexo, raça/cor, estado civil, grau de instrução, nome social | ✅     | `funcionarios` §B1 (enums `Sexo`/`RacaCor`/`EstadoCivil`/`Escolaridade`)        |
| `nascimento`                                             | data de nascimento, país de nascimento/nacionalidade, naturalidade      | ✅     | `data_nascimento`, `nacionalidade_pais_id`, `naturalidade_municipio_id`         |
| `endereco` (Brasil)                                      | logradouro, CEP, município, UF                                          | ✅     | `funcionario_enderecos` §B3                                                     |
| `endereco` (exterior)                                    | endereço no exterior                                                    | ⏳     | adiado → §5 (ligado a trabalhador estrangeiro)                                  |
| `contato`                                                | telefone, e-mail                                                        | ✅     | `funcionario_contatos` §B2                                                      |
| `dependente`                                             | dependentes (IR, salário-família, plano de saúde)                       | ✅     | `funcionario_dependentes` §B5                                                   |
| **`infoDeficiencia` (PCD)**                              | def. física/visual/auditiva/mental/intelectual, reabilitado, cota       | ✅     | **grupo PCD** em `funcionarios` §B1 (esta revisão)                              |
| `infoContrato` → `codCateg`                              | categoria do trabalhador (Tabela 01)                                    | ✅     | derivado: `TipoVinculo::codCategEsocial()` ([01 §4.1](01-modelo-de-dominio.md)) |
| `infoContrato` → matrícula, dtAdm, remuneração           | matrícula, data de admissão, salário                                    | ✅     | `funcionarios` (contratação) §B1                                                |
| `infoContrato` → cargo/função                            | cargo (CBO), funções                                                    | ✅     | `cargo_id` (referência CBO), `funcoes`/`funcionario_funcao`                     |
| `vinculo` → PIS/PASEP                                    | identificação previdenciária                                            | ✅¹    | `pis_pasep` (nullable na Fase 1; **obrigatório na validação — Fase 4**)         |
| `afastamento` / `desligamento` (no S-2200)               | afastamento/desligamento no cadastramento inicial                       | ✅²    | `funcionario_afastamentos`, evento `desligamento` ([06](06-linha-do-tempo.md))  |
| `infoContrato` → tpRegTrab / tpRegPrev                   | regime trabalhista (CLT/estatutário) / previdenciário (RGPS/RPPS)       | ⏳     | adiado → §5                                                                     |
| `infoContrato` → tpAdmissao / indAdmissao / natAtividade | tipo/indicativo de admissão, natureza urbano/rural                      | ⏳     | adiado → §5                                                                     |
| **`trabEstrangeiro`**                                    | data de chegada, casado com BR, filhos BR, etc.                         | ⏳     | adiado → §5                                                                     |

> ¹ `pis_pasep` existe (nullable) para cadastro progressivo; a **obrigatoriedade** é da validação eSocial (Fase 4). ² Estrutura presente; a montagem do grupo no XML do S-2200 é Fase 4. Os **códigos** (`codCateg`, raça/cor, grau de instrução, etc.) seguem as tabelas oficiais do eSocial e devem ser **reconfirmados contra o leiaute vigente** na Fase 4.

---

## 5. Escopo OUT — Fase 1 (fronteiras explícitas)

Itens deliberadamente **fora** da Fase 1, com destino mapeado no roadmap → [09](09-roadmap-fases.md):

| Fora do escopo                                                             | Por que sai da Fase 1                                                                                                                                                | Onde entra                         |
| -------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| **Folha completa / apuração mensal / holerite**                            | Fase 1 entrega só a _fundação_ (rubricas + tabelas legais); apuração é projeto próprio                                                                               | [09](09-roadmap-fases.md)          |
| **eSocial transmitido**                                                    | Modelo é eSocial-**ready** (domínios e códigos oficiais), mas **não** gera/transmite eventos agora                                                                   | [09](09-roadmap-fases.md)          |
| **Trabalhador estrangeiro (`trabEstrangeiro`)**                            | Grupo do S-2200 de baixa incidência nas PMEs-alvo, com muitos campos próprios (chegada, casamento, filhos BR, endereço no exterior); entra quando houver transmissão | [09](09-roadmap-fases.md) — Fase 4 |
| **Admissão detalhada (tpAdmissao/indAdmissao, natAtividade urbano/rural)** | Campos do `infoContrato` exigidos só na **geração do XML**; a Fase 1 grava o essencial (matrícula, dtAdm, vínculo, salário)                                          | [09](09-roadmap-fases.md) — Fase 4 |
| **Regime trabalhista/previdenciário (tpRegTrab/tpRegPrev)**                | CLT/estatutário e RGPS/RPPS são derivados/fixados na transmissão; o público-alvo é CLT/RGPS                                                                          | [09](09-roadmap-fases.md) — Fase 4 |
| **Banco de horas**                                                         | HE da Fase 1 é cálculo + aprovação + rubrica; compensação/saldo é evolução                                                                                           | [09](09-roadmap-fases.md)          |
| **Férias com workflow**                                                    | Afastamento "Férias" existe como tipo; o _workflow_ de programação/aprovação/aviso é fase futura                                                                     | [09](09-roadmap-fases.md)          |
| **Marcação de ponto em dispositivo**                                       | Jornada/escala definem a _expectativa_; coleta de marcação (REP/app) é fase futura                                                                                   | [09](09-roadmap-fases.md)          |

> As decisões de escopo da Fase 1 foram **confirmadas com o cliente** (ver §8): folha entra só como **fundação**; **self-service** do colaborador (consulta + edição parcial sob ACL) está **dentro**; o modelo é **eSocial-ready** (campos/catálogos com domínios e códigos oficiais brasileiros) **sem transmitir agora**.

---

## 6. Requisitos não-funcionais (RNF)

| #      | RNF                                    | Como é atendido                                                                                                                                                                                                                                                                                                                                                       |
| ------ | -------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RNF-01 | **Isolamento multi-tenant**            | Toda tabela de negócio tem `empresa_id` + trait `App\Models\Concerns\BelongsToEmpresa` (global scope `empresa`, auto-fill no `creating`); tenant ativo via `App\Support\Tenancy\TenantContext` (sessão); `unique` sempre por empresa (índice parcial composto `WHERE deleted_at IS NULL`)                                                                             |
| RNF-02 | **RBAC de 2 níveis + ACL hierárquica** | Papéis globais (spatie) **+** papéis por empresa (`admin_user_empresa_role`), resolvidos por `App\Services\Admin\AccessResolver` (super-admin faz bypass) **somados** à nova ACL de visibilidade por organograma (subárvore recursiva). A ACL **complementa**, não substitui, o RBAC                                                                                  |
| RNF-03 | **LGPD**                               | PII fora do diff de auditoria (`atributosNaoAuditados()`); CID = dado de saúde (art. 11) com `encrypted` + permissão dedicada `rh.afastamentos.ver_cid`; financeiro (`conta`, `pix_chave`) e foto em disco privado/URL assinada; retenção trabalhista via soft-delete + eventos append-only; anonimização alinhada ao fluxo do core (`anonimizado_em` no `AdminUser`) |
| RNF-04 | **Auditoria automática**               | Trait `App\Models\Concerns\Auditavel` (spatie/activitylog) em todas as tabelas; `activity_log` append-only; PII nunca registrada                                                                                                                                                                                                                                      |
| RNF-05 | **Performance**                        | Organograma via **CTE recursiva no PostgreSQL**; toda FK indexada; compostos quentes (`(empresa_id, gestor_id)`, `(funcionario_id, data)`, `(empresa_id, status)`); colunas "atuais" desnormalizadas em `funcionarios` evitam varrer o histórico; vigências abertas via `IS NULL` indexado                                                                            |
| RNF-06 | **i18n pt-BR**                         | UI, mensagens, validação e labels de enum em Português; código/tabelas/colunas em inglês técnico (convenção do core)                                                                                                                                                                                                                                                  |
| RNF-07 | **Desktop-first**                      | Painel backoffice (mín. 1366×768), Inspinia + Livewire 4 + Tailwind 4; componentes do catálogo Inspinia (sem `<select>` nativo, sem CSS custom)                                                                                                                                                                                                                       |
| RNF-08 | **Lixeira**                            | `deleted_at` (SoftDeletes) + trait `App\Livewire\Concerns\ComLixeira` com 3 níveis por módulo (`deletar`→lixeira, `restaurar`, `excluir_permanente`→force-delete); models implementam `App\Models\Contracts\UsaSoftDeletes`                                                                                                                                           |
| RNF-09 | **Empacotamento aditivo (ADR-0015)**   | `ht2erp/modulo-rh`: migrations via `loadMigrationsFrom`; permissões e menu mesclados em runtime no `boot()` (`ModuleRegistry`/config); **nunca edita o core**                                                                                                                                                                                                         |

---

## 7. Premissas, riscos e dependências

### 7.1 Premissas

- O **core já provê**: tenancy (`BelongsToEmpresa`/`TenantContext`), RBAC de 2 níveis (`AccessResolver` + `admin_user_empresa_role`), auditoria (`Auditavel`), upload polimórfico (`App\Models\Anexo`), lixeira (`ComLixeira`/`UsaSoftDeletes`), registro de módulos-pacote (`ModuleRegistry`, ADR-0015) e o gerador `make:modulo --module=Rh`.
- Referências globais já semeadas no core: `cargos` (CBO), `bancos`, `paises`, `estados`, `municipios`, `tipos_logradouro`.
- `AdminUser` (guard `admin`) já tem `encrypted` cast (padrão `two_factor_secret`) e coluna LGPD `anonimizado_em` — reaproveitados pelo RH.
- Greenfield: a Fase 1 cria as tabelas já completas; evoluções futuras seguem migrations aditivas (`add_<coluna>_to_<tabela>`).

### 7.2 Riscos

| Risco                                                   | Impacto                                                   | Mitigação                                                                                                                                 |
| ------------------------------------------------------- | --------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| **Sensibilidade LGPD do CID** (dado de saúde)           | Vazamento = sanção legal                                  | `encrypted` + permissão dedicada + fora de auditoria; acesso só com `rh.afastamentos.ver_cid`                                             |
| **Profundidade/ciclos no organograma**                  | Recursão custosa ou loop                                  | CTE recursiva indexada; CHECK anti-auto-referência (`gestor_id <> id`, `departamento_pai_id <> id`); ciclos profundos validados na Action |
| **eSocial-ready ≠ eSocial transmitido**                 | Expectativa do cliente desalinhada                        | Fronteira explícita (§5) e registro do escopo confirmado (§8); roadmap claro em [09](09-roadmap-fases.md)                                 |
| **Catálogo `cargos` (CBO) vs cargo próprio do cliente** | Cliente pode querer cargos não-CBO com CRUD               | Decisão atual: reaproveitar `cargos` + `cargo_nivel`; promover a `cargos_empresa` é evolução aditiva (ADR-RH-002)                         |
| **Fundação de folha sem apuração**                      | Rubricas/tabelas semeadas mas "sem uso" visível na Fase 1 | Comunicar que é base versionada para fases futuras; HE já consome rubrica como prova de conceito                                          |
| **Acoplamento ao core**                                 | Mudança no core quebra o pacote                           | Tudo aditivo (ADR-0015); só FKs nullable para `admin_users`; relação inversa via método no pacote, sem migration no core                  |

### 7.3 Dependências (do core, não recriar)

- `App\Services\Admin\AccessResolver` (precedência: super-admin > deny > grant > role) e comando `access:sync`.
- `App\Support\Modules\ModuleRegistry` + config de permissões/menu (registro do pacote no `boot()`).
- `App\Models\Anexo` (upload polimórfico, disco privado).
- `App\Models\Concerns\BelongsToEmpresa` + `App\Support\Tenancy\TenantContext`.
- `App\Livewire\Concerns\ComLixeira` + `App\Models\Contracts\UsaSoftDeletes`.
- `App\Models\Concerns\Auditavel` (spatie/activitylog).
- Gerador `make:modulo` com flag de pacote (`--module=Rh`) e `make:modulo-pacote` (casca do pacote).
- ADRs aplicáveis: [0004](../../../architecture/adrs/ADR-0004-ulid-publico-bigint-interno.md) (ULID aspiracional — RH usa `id`/`slug`/`matricula`), [0009](../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md) (snapshots JSONB), [0010](../../../architecture/adrs/ADR-0010-enums-php-backed.md) (enums backed), [0014](../../../architecture/adrs/ADR-0014-money-integer-centavos.md) (dinheiro em centavos), [0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) (módulos-pacote).

---

## 8. Decisões de escopo confirmadas (registro)

Decisões tomadas **com o cliente** e que governam o recorte da Fase 1:

1. **Folha — só fundação.** A Fase 1 inclui a **fundação** de folha (catálogo `rubricas`, `tabelas_legais` INSS/IRRF por vigência, e HE aprovada virando rubrica), mas **não** inclui apuração mensal, holerite nem eSocial transmitido (fases futuras — [09](09-roadmap-fases.md)).
2. **Self-service do colaborador — dentro.** O colaborador comum **loga e vê os próprios dados**, podendo **editar alguns campos**, sempre sob a **ACL hierárquica** (RF-05).
3. **Modelo eSocial-ready — sim, transmitir — não.** Campos e catálogos já contemplam **domínios e códigos oficiais brasileiros** (raça/cor, afastamentos tab. 18, rubricas tab. 03, etc.), **sem transmitir** ao eSocial nesta fase.

> Estas três decisões são a referência para resolver dúvidas de fronteira durante a implementação. Mudanças de escopo devem atualizar este PRD e o roadmap [09](09-roadmap-fases.md).
