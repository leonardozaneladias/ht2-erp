# 09 — Roadmap de Fases

> Visão de longo prazo do **módulo de RH** (`ht2erp/modulo-rh`). O cliente pediu um super módulo de RH entregue **em fases**, com a **marcação de ponto integrada no dispositivo como a ÚLTIMA fase**. Este documento posiciona o que a Fase 1 entrega (detalhada no blueprint) dentro do todo e descreve as fases seguintes — objetivo, entregas, dependências e critérios de entrada — para que cada decisão de modelagem feita agora habilite o futuro sem retrabalho.
>
> Pacote: `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · multi-tenant lógico por `empresa_id` · banco **PostgreSQL 16**. Schema é definido em [01](01-modelo-de-dominio.md) (fonte de verdade); este roadmap não introduz schema novo.

Relacionados: [00](00-prd.md) · [02](02-fase-1-blueprint.md) · [07](07-jornada-horas-extras-folha.md)

---

## 1. Visão geral

A sequência é **ascendente em complexidade legal**: cada fase consome a fundação imutável construída antes (cadastro eSocial-ready → tempo → cálculo de folha → transmissão fiscal → ponto). O ponto eletrônico vem por último porque depende de tudo o que o antecede (jornada, banco de horas, HE, folha) já estar consolidado.

| Fase          | Tema                             | Entrega-chave                                                                                                                                                                                | Depende de                                                              |
| ------------- | -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| **1**         | Cadastro, organograma e fundação | Cadastro eSocial-ready, catálogos, organograma + ACL hierárquica, self-service, linha do tempo, jornada/escalas, HE com cálculo+aprovação, **fundação de folha** (rubricas + tabelas_legais) | core (empresa/filial, admin_users, anexos, referências)                 |
| **2**         | Gestão de ausências e tempo      | Férias (aquisitivo/concessivo + workflow), afastamentos avançados, **banco de horas**, calendário de equipe/feriados                                                                         | Fase 1 (jornada, HE aprovada, afastamentos, organograma)                |
| **3**         | Folha de pagamento completa      | Apuração mensal, proventos/descontos, INSS/FGTS/IRRF, 13º e férias, **holerite PDF**, fechamento de competência                                                                              | Fase 1 (rubricas, tabelas_legais, HE) + Fase 2 (banco de horas, férias) |
| **4**         | eSocial                          | Eventos de tabelas e não-periódicos (S-1000…S-2299) e periódicos (S-1200/S-1210); geração, transmissão e retornos                                                                            | Fase 1 (cadastro eSocial-ready) + Fase 3 (folha apurada)                |
| **5**         | Controle de ponto (espelho)      | Folha de ponto, marcações manuais/importadas, tratamento de inconsistências, integração com HE/banco de horas, **AFD/AEJ**                                                                   | Fase 1 (jornada/escalas, HE) + Fase 2 (banco de horas)                  |
| **6** (FINAL) | Marcação de ponto em dispositivo | REP/relógio (Portaria MTP 671/2021), biometria, app mobile com geolocalização, sync online/offline, fechamento automático                                                                    | Fase 5 (espelho de ponto, AFD/AEJ)                                      |

> As fases podem ser fatiadas em incrementos `1.x`/`2.x`/etc. e algumas se sobrepõem no tempo (ex.: começar a Fase 5 enquanto a Fase 4 amadurece), desde que respeitadas as dependências da coluna direita.

---

## 2. Fase 1 — Cadastro, organograma e fundação (em andamento)

Detalhada em [02 — Fase 1 (blueprint)](02-fase-1-blueprint.md), que organiza a entrega em **7 blocos** sobre o schema de [01](01-modelo-de-dominio.md):

1. **Catálogos configuráveis** — `departamentos` (hierárquicos), `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`/`escala_dias`, `rubricas`, provisionados por empresa na criação (`ProvisionarCatalogosRh`).
2. **Cadastro de pessoa, documentos e anexos** — `funcionarios` (eSocial-ready) + filhas (contatos, endereços, dados bancários, dependentes, documentos reusando `anexos`). Ver [03](03-cadastro-pessoa-documentos.md).
3. **Organograma + ACL hierárquica** — árvore por `gestor_id`/`departamento_pai_id`, vínculo 1:1 `funcionario↔admin_user`, subárvore recursiva e self-service. Ver [05](05-organograma-acl-hierarquica.md).
4. **Linha do tempo** — `funcionario_eventos` append-only com snapshot JSONB; a Action grava o evento e atualiza as colunas "atuais" na mesma transação. Ver [06](06-linha-do-tempo.md).
5. **Jornada / escalas** — escalas reutilizáveis, atribuição com vigência, cálculo de carga semanal e valor-hora. Ver [07](07-jornada-horas-extras-folha.md).
6. **Horas extras** — lançamento, **cálculo** (fator por `TipoHoraExtra` + base por `RegimeTrabalho`), **workflow de aprovação** e snapshot imutável de cálculo. Ver [07](07-jornada-horas-extras-folha.md).
7. **Fundação de folha** — catálogo `rubricas` (com incidências INSS/FGTS/IRRF) e referência `tabelas_legais` (faixas por vigência).

### Onde fica a fronteira da Fase 1

A Fase 1 entrega a **fundação** de folha e tempo, não a apuração:

- **Fundação de folha, não folha** — existem `rubricas` (proventos/descontos com flags de incidência) e `tabelas_legais` (INSS/IRRF/salário-família por competência), mas **não há apuração mensal, holerite nem eSocial transmitido**. Esses catálogos são o **contrato** que a Fase 3 consome.
- **Ponto "manual" via HE** — não há folha de ponto nem dispositivo. O registro de tempo extra acontece pelo **lançamento de hora extra feito pelo gestor** (`horas_extras`, lançado por `admin_user`, com aprovação). É o substituto operacional do ponto até a Fase 5/6.
- **Sem dispositivo** — nenhuma marcação automática, REP, biometria ou app. Toda entrada de tempo é humana.

O valor entregue: empresa cadastrada e operando com pessoas, organograma, documentos, ausências básicas (afastamentos) e horas extras calculadas e aprovadas — com os **snapshots imutáveis** (HE, eventos) já gravados no formato que a folha e o eSocial exigirão.

---

## 3. Fase 2 — Gestão de ausências e tempo

**Objetivo.** Maturar o controle de tempo e ausências para além do afastamento simples da Fase 1: gerir **férias** com rigor legal (período aquisitivo/concessivo), **afastamentos avançados** com workflow, e introduzir o **banco de horas** que compensa as HE aprovadas — fechando o ciclo "trabalhei a mais → compenso depois" sem cair na folha.

**Entregas principais.**

- **Férias** — apuração do **período aquisitivo** (12 meses a partir da admissão / da última concessão), do **período concessivo** (12 meses seguintes para gozo), saldo de dias e abono pecuniário; **programação** (agendamento de gozo, incl. fracionamento conforme reforma trabalhista); **workflow de aprovação** (solicitação do funcionário/gestor → aprovação → efetivação) gerando evento na linha do tempo. Modelado sobre `funcionarios` + `funcionario_eventos` (status `ferias` já existe em `StatusFuncionario`).
- **Afastamentos avançados** — sobre `funcionario_afastamentos`/`tipos_afastamento` (já com flags eSocial e `cid` cifrado): workflow de solicitação/aprovação, controle de prazo previsto × efetivo, prorrogação, e regras derivadas das flags (`suspende_contrato`, `conta_como_falta`, `remunerado`).
- **Banco de horas** — saldo de horas por funcionário alimentado pela **compensação de HE aprovadas** (consome `horas_extras` com `status = aprovada`); lançamentos de crédito/débito, regras de expiração/acordo coletivo e extrato. É a alternativa "compenso" ao "pago em folha".
- **Calendário de equipe / feriados** — feriados nacionais/estaduais/municipais e pontos facultativos por empresa, visão de calendário da equipe (quem está de férias/afastado), base para o cálculo de dias úteis usado por férias, banco de horas e (futuro) ponto.

**Dependências.** Fase 1: jornada/escalas (dias úteis e carga), `horas_extras` aprovadas (origem do crédito de banco de horas), `funcionario_afastamentos`, organograma (quem aprova).

**Critérios de entrada.** Fase 1 fechada com HE em produção (lançamento + aprovação + snapshot funcionando) e linha do tempo gravando eventos; escalas atribuídas aos funcionários (necessário para apurar dias úteis e saldo de férias).

---

## 4. Fase 3 — Folha de pagamento completa

**Objetivo.** Transformar a **fundação de folha** da Fase 1 em **apuração real**: calcular a remuneração mensal de cada funcionário, gerar holerite e fechar a competência — consumindo, sem retrabalho, os catálogos e snapshots já existentes.

**Entregas principais.**

- **Apuração mensal** — motor que, por competência e funcionário, consome `rubricas` (incidências), **HE aprovadas** (`horas_extras`, via `rubrica_id`/`referencia_he_tipo`) e `tabelas_legais` (faixas vigentes) para montar o demonstrativo. Resultado gravado como **snapshot imutável** por competência (mesmo padrão ADR-0009 dos snapshots de HE/eventos).
- **Proventos e descontos** — composição de rubricas de provento e desconto (salário, HE, adicionais, VT, etc.), incluindo lançamentos avulsos por competência.
- **Encargos legais** — cálculo de **INSS** (faixas progressivas), **FGTS** e **IRRF** (com dedução de dependentes, a partir de `funcionario_dependentes.dependente_ir`), a partir das `tabelas_legais` por vigência.
- **13º salário e férias** — cálculo da gratificação natalina (1ª/2ª parcela) e do recibo de férias (consumindo o saldo apurado na Fase 2), com suas incidências próprias.
- **Holerite PDF** — demonstrativo de pagamento por funcionário/competência (reusa `barryvdh/laravel-dompdf` do core), com fila dedicada `pdf`.
- **Fechamento de competência** — trava da competência (impede reabertura/alteração após fechada), congelando o snapshot e habilitando os eventos periódicos do eSocial.

**Dependências.** Fase 1: `rubricas` (com incidências), `tabelas_legais`, `horas_extras` aprovadas, `funcionarios`/`funcionario_dependentes`. Fase 2: banco de horas (o que foi compensado **não** vira pagamento), saldo/recibo de férias.

**Critérios de entrada.** Catálogo de `rubricas` mapeado às necessidades do cliente; `tabelas_legais` carregadas e validadas para as competências-alvo; HE em produção com snapshot de cálculo confiável; férias da Fase 2 apurando saldo corretamente.

---

## 5. Fase 4 — eSocial

**Objetivo.** Cumprir a obrigação acessória: **gerar, transmitir e tratar retornos** dos eventos do eSocial, aproveitando que o cadastro já nasceu eSocial-ready (Fase 1) e que a folha já é apurada (Fase 3).

**Entregas principais.**

- **Eventos de tabelas** — **S-1000** (informações do empregador), **S-1005** (estabelecimentos), **S-1010** (rubricas — alimentado por `rubricas.codigo_esocial`).
- **Eventos não-periódicos** — **S-2200** (admissão), **S-2206** (alteração contratual), **S-2230** (afastamento temporário — alimentado por `tipos_afastamento.codigo_esocial`/`funcionario_afastamentos`), **S-2299** (desligamento).
- **Eventos periódicos** — **S-1200** (remuneração) e **S-1210** (pagamentos), gerados a partir da folha **fechada** da Fase 3.
- **Geração, transmissão e retornos** — montagem dos XML conforme leiautes vigentes, assinatura/envio aos webservices, fila própria, e **tratamento de retornos** (recibos, rejeições, reprocessamento) com status por evento e trilha de auditoria.

**Dependências.** Fase 1: cadastro eSocial-ready (enums de domínio do eSocial — `RacaCor`, `EstadoCivil`, etc. —, `codigo_esocial` em rubricas/afastamentos, PII completa). Fase 3: folha fechada por competência (origem de S-1200/S-1210).

**Critérios de entrada.** Folha apurando e fechando competências de forma estável; certificado digital e ambiente eSocial (produção restrita/produção) configurados; cadastros sem pendências de campos obrigatórios do eSocial.

---

## 6. Fase 5 — Controle de ponto (espelho)

**Objetivo.** Introduzir o **espelho de ponto** (folha de ponto) com marcações **manuais ou importadas** — sem ainda integrar o dispositivo físico. É a camada que formaliza a jornada realizada e passa a **alimentar** a HE e o banco de horas, no lugar do lançamento manual de HE da Fase 1.

**Entregas principais.**

- **Folha de ponto (espelho)** — registro diário de marcações por funcionário, confrontado com a **escala vigente** (Fase 1) para apurar horas trabalhadas, extras, atrasos, faltas e adicional noturno.
- **Marcações manuais / importadas** — entrada manual de batidas e **importação** de arquivos (ex.: AFD de relógios já existentes), preparando o terreno para a coleta automática da Fase 6.
- **Tratamento de inconsistências** — detecção e correção de batidas faltantes/duplicadas/fora de ordem, com justificativa e aprovação (workflow), gerando trilha auditável.
- **Integração com HE e banco de horas** — o saldo apurado no espelho **gera** lançamentos de `horas_extras` e créditos/débitos de banco de horas (Fase 2), unificando a origem do tempo extra.
- **AFD / AEJ** — geração dos arquivos legais **AFD** (Arquivo Fonte de Dados) e **AEJ** (Arquivo Eletrônico de Jornada) conforme a legislação de ponto.

**Dependências.** Fase 1: jornada/escalas (referência da jornada esperada), `horas_extras`. Fase 2: banco de horas (destino do saldo compensável).

**Critérios de entrada.** Escalas atribuídas e confiáveis; banco de horas e HE operando; definição com o cliente de quais relógios/arquivos existentes serão importados (formato AFD).

---

## 7. Fase 6 (FINAL) — Marcação de ponto integrada em dispositivo

**Objetivo.** O objetivo final do módulo: **coletar a marcação de ponto diretamente no dispositivo**, em conformidade legal, eliminando a entrada manual. Fecha o ciclo iniciado na Fase 1 — agora a jornada realizada chega sozinha ao sistema e flui para espelho → HE/banco de horas → folha → eSocial.

**Entregas principais.**

- **REP / relógio de ponto** — integração com Registrador Eletrônico de Ponto conforme a **Portaria MTP 671/2021** (REP-C convencional, REP-A alternativo e **REP-P** via programa), incluindo as regras de inviolabilidade e o NSR (Número Sequencial de Registro).
- **Biometria** — identificação por biometria (digital/facial) na marcação, com tratamento de cadastro biométrico e LGPD (dado sensível, art. 11 — mesmo rigor do `cid`).
- **App mobile com geolocalização** — marcação por aplicativo com captura de **geolocalização** (cerca virtual/geofencing), foto opcional e vínculo ao funcionário.
- **Sincronização online/offline** — coleta resiliente: o dispositivo/app marca offline e **sincroniza** quando há rede, com deduplicação por NSR e garantia de não perder/duplicar batida.
- **Fechamento automático** — a marcação coletada **fecha automaticamente** o espelho da Fase 5 (apura jornada, gera HE/banco de horas) sem intervenção manual, restando apenas o tratamento de exceções.

**Dependências.** Fase 5: espelho de ponto, tratamento de inconsistências e AFD/AEJ (o dispositivo passa a ser a **origem** das batidas que o espelho já sabe processar).

**Critérios de entrada.** Espelho de ponto maduro processando marcações importadas; homologação dos dispositivos/REP e do app; conformidade LGPD do tratamento biométrico aprovada; AFD/AEJ validados.

---

## 8. Eixos estratégicos vizinhos (módulos-pacote satélites)

As 6 fases acima são o **eixo Departamento Pessoal → Folha → eSocial → Ponto** do `ht2erp/modulo-rh`. Um RH "muito completo" abrange mais eixos — mas eles **não** incham o `modulo-rh`: entram como **módulos-pacote satélites**, cada um aditivo ao core pelo mesmo padrão do [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md), tendo o `modulo-rh` como dependência quando precisam do cadastro de pessoa. A fronteira: **`modulo-rh` = pessoa + DP + jornada/HE + folha + eSocial + ponto**; os satélites cobrem o resto.

| Eixo / módulo candidato                                | Escopo                                                                            | Conversa com                                                                                                            | Prioridade                             |
| ------------------------------------------------------ | --------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| **SST — Saúde e Segurança** (`ht2erp/modulo-sst`)      | ASO/PCMSO, exames ocupacionais, **EPI**, **CAT**, PGR, treinamentos de NR         | eSocial **S-2210** (CAT), **S-2220** (monitoramento da saúde/ASO), **S-2240** (agentes nocivos) — casa com a **Fase 4** | **Alta** (obrigação acessória eSocial) |
| **Benefícios**                                         | VT/VR/VA, plano de saúde/odontológico, coparticipação                             | estende `rubricas`/folha (Fase 3) e `funcionario_dependentes`                                                           | Média                                  |
| **Recrutamento & Seleção (ATS)** (`ht2erp/modulo-ats`) | vagas, candidatos, pipeline/funil, banco de talentos                              | alimenta a **admissão** (Fase 1: cria `funcionarios` a partir do candidato aprovado)                                    | Média                                  |
| **Treinamento & Desenvolvimento**                      | cursos, trilhas, certificações, matriz de competências, validade de treinamentos  | cruza com SST (treinamentos de NR) e com Desempenho                                                                     | Média                                  |
| **Avaliação de Desempenho**                            | ciclos, metas/OKR, 9-box, feedback 360; Onboarding/Offboarding; pesquisa de Clima | usa o organograma ([05](05-organograma-acl-hierarquica.md)) para o fluxo gestor↔subordinado                            | Média                                  |

> **Por que satélites e não fases do `modulo-rh`:** cada eixo tem domínio, telas e cadência próprios; empacotá-los à parte ([ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)) mantém o núcleo coeso, permite ativar/vender por cliente e evita um monólito. A estratégia "RH como família de módulos-pacote" está no [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md). **Nada disso é Fase 1** — é visão de produto; a Fase 1 entrega o **núcleo** (cadastro + organograma + jornada/HE + fundação de folha).

---

## 9. Nota de arquitetura — a fundação é o contrato

As fases podem ser **fatiadas em incrementos** (`1.x`, `2.x`, …) e priorizadas conforme a necessidade do cliente, mas a regra de ouro é uma só: **a fundação construída na Fase 1 é o contrato que habilita tudo o que vem depois.** Concretamente:

- **`rubricas` + `tabelas_legais`** (fundação de folha) são consumidas, sem alteração de schema, pela apuração da Fase 3 e pelos eventos do eSocial na Fase 4 (`codigo_esocial`).
- **Snapshots imutáveis de cálculo** (ADR-0009) — `horas_extras.snapshot_calculo`, `funcionario_eventos.snapshot_*` e, na Fase 3, o snapshot de folha por competência — garantem que o valor apurado **no passado** permaneça reproduzível mesmo após mudança de tabelas/fatores. É o que torna folha e eSocial auditáveis.
- **Jornada/escalas** definem a "jornada esperada" contra a qual o espelho de ponto (Fase 5) e o dispositivo (Fase 6) apuram a realizada.
- **Cadastro eSocial-ready** (enums de domínio, `codigo_esocial`, PII completa) evita migração de dados quando a transmissão entrar na Fase 4.

Por isso a evolução é **aditiva** (ver [01 §6](01-modelo-de-dominio.md)): cada fase adiciona tabelas e colunas `NULL`/com `default` sobre o pacote `ht2erp/modulo-rh`, sem reescrever a fundação nem tocar o core. Decisões estruturais de fronteira (ENUM × CATÁLOGO × REFERÊNCIA, vínculo `funcionario↔admin_user`) ficam registradas em ADRs do módulo (`adrs/`).
