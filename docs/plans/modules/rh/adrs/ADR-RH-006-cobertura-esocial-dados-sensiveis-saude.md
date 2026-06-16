---
title: 'ADR-RH-006: Cobertura eSocial da Fase 1 e dados sensíveis de saúde (PCD/CID)'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-006: Cobertura eSocial da Fase 1 e dados sensíveis de saúde (PCD/CID)

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** esocial, lgpd, rh, modelagem

> Pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Schema canônico em [01 — Modelo de Domínio](../01-modelo-de-dominio.md); matriz de cobertura em [00 §4.1](../00-prd.md).

## Contexto e problema

A Fase 1 afirma entregar um cadastro **"eSocial-ready"**. Sem uma definição **verificável**, isso é só um slogan — e abre duas lacunas concretas:

1. **O que "eSocial-ready" cobre, exatamente?** O evento **S-2200** (Cadastramento Inicial do Vínculo / Admissão) tem vários grupos (trabalhador, nascimento, contato, endereço, dependente, `infoDeficiencia`, `infoContrato`, `trabEstrangeiro`…). Sem mapear grupo a grupo, corre-se o risco de (a) achar que está pronto e descobrir campos faltando na Fase 4, ou (b) inchar a Fase 1 com campos que só servem à transmissão.
2. **Como tratar o grupo PCD/Deficiência?** A inclusão do grupo `infoDeficiencia` (deficiência física/visual/auditiva/mental/intelectual, reabilitado/readaptado, beneficiário de cota) introduz **dado de saúde** — **categoria especial** pela **LGPD art. 11**, o mesmo patamar do `cid` de afastamento ([ADR-RH-005](ADR-RH-005-historico-eventos-imutaveis.md)). Tratá-lo como campo cadastral comum seria uma violação de privacidade.

A restrição estrutural do [ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) vale: tudo aditivo, colunas nullable (cadastro progressivo), core intocado.

## Drivers da decisão

- **"eSocial-ready" verificável**: cada grupo do S-2200 com status explícito (coberto / adiado), de modo que a Fase 4 gere o XML **sem migração de dados**.
- **Escopo da Fase 1 contido**: cobrir o que é **cadastral e barato** (PCD, `codCateg` derivado); adiar o que só existe para a **transmissão** (trabalhador estrangeiro, regimes, admissão detalhada).
- **LGPD art. 11**: PCD e CID são dado de saúde — mesmo rigor (fora de auditoria, acesso por permissão dedicada, defesa no servidor).
- **Aditivo** ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)) e enums de domínio oficiais ([ADR-0010](../../../../architecture/adrs/ADR-0010-enums-php-backed.md)).

## Alternativas consideradas

### Cobertura eSocial

**Alt 1 — Cobrir todo o S-2200 já na Fase 1** (incl. estrangeiro, regimes, admissão detalhada). Prós: transmissão pronta sem lacuna. Contras: **explode o escopo** com campos usados só na geração do XML (Fase 4), muitos de baixíssima incidência no público-alvo (estrangeiro); engorda o cadastro sem valor imediato. Rejeitada.

**Alt 2 — Não preparar nada; eSocial do zero na Fase 4.** Prós: Fase 1 mínima. Contras: **migração de dados pesada** depois (backfill de raça/cor, naturalidade, PCD em toda a base) e "eSocial-ready" viraria propaganda falsa. Rejeitada.

**Alt 3 — Cobrir os grupos cadastrais essenciais; adiar os de transmissão (escolhida).** Os domínios que **moram na pessoa** (trabalhador, nascimento, contato, endereço BR, dependente, **PCD**, `codCateg`, contratação básica) entram na Fase 1 no formato oficial; os que são **da transmissão** (estrangeiro, regimes, admissão detalhada, endereço no exterior) ficam para a Fase 4. Equilíbrio entre "pronto" e "enxuto", auditável pela matriz [00 §4.1](../00-prd.md).

### Armazenamento do grupo PCD

**Alt A — Colunas embutidas em `funcionarios`** (nullable). Prós: simples, mesma transação/agregado, uma query. Contras: dado sensível convive com o resto — **mitigado** por `atributosNaoAuditados()` + permissão dedicada + UI restrita.

**Alt B — Tabela-filha 1:1 `funcionario_pcd`.** Prós: isolamento físico/coluna. Contras: join a mais e overkill se o cliente não exige segregação de armazenamento.

Decisão: **Alt A por padrão; Alt B documentada como evolução** quando houver exigência de isolamento físico — sem mudar a UI ([01 §3 B1](../01-modelo-de-dominio.md)).

## Decisão

1. **"eSocial-ready" da Fase 1 = grupos cadastrais do S-2200 no formato oficial, sem transmitir.** Enums de domínio (`Sexo`, `RacaCor`, `EstadoCivil`, `Escolaridade`), `codCateg` derivado, PIS, naturalidade/nacionalidade, dependentes, contatos/endereços e o grupo **PCD** presentes; a montagem/envio do XML é Fase 4. Cobertura grupo a grupo na **matriz [00 §4.1](../00-prd.md)**.
2. **Adiado para a Fase 4** (registrado no Escopo OUT, [00 §5](../00-prd.md)): `trabEstrangeiro`, `tpRegTrab`/`tpRegPrev` (regime trabalhista/previdenciário), `tpAdmissao`/`indAdmissao`/`natAtividade` (admissão detalhada, urbano/rural), endereço no exterior.
3. **`codCateg` sem coluna nova:** derivado do vínculo por `TipoVinculo::codCategEsocial(): ?int` ([01 §4.1](../01-modelo-de-dominio.md)), com mapeamento à Tabela 01 do eSocial a reconfirmar contra o leiaute vigente na Fase 4.
4. **PCD = dado de saúde (LGPD art. 11), mesmo rigor do CID:** colunas nullable em `funcionarios` (`def_fisica`, `def_visual`, `def_auditiva`, `def_mental`, `def_intelectual`, `reabilitado_readaptado`, `beneficiario_cota`, `observacao_pcd`), em `atributosNaoAuditados()`, sob a permissão dedicada **`rh.funcionarios.ver_dados_sensiveis`** ([01 §10](../01-modelo-de-dominio.md)); UI com seção restrita + selo "eSocial" ([03 §2.1](../03-cadastro-pessoa-documentos.md)) e **defesa no servidor** (a Action ignora o grupo sem a permissão). Isolamento físico via tabela-filha 1:1 `funcionario_pcd` é evolução aditiva.
5. **PIS/PASEP nullable na Fase 1** (cadastro progressivo); **obrigatório na validação eSocial** (Fase 4).

## Consequências

**Positivas:**

- "eSocial-ready" deixa de ser slogan: a matriz [00 §4.1](../00-prd.md) torna a afirmação **auditável** e a Fase 4 gera o S-2200 sem migração de dados cadastrais.
- Escopo da Fase 1 contido: PCD e `codCateg` são **baratos** (colunas/método), e o que é caro/transmissão fica explicitamente adiado.
- LGPD reforçada: PCD e CID recebem o **mesmo** tratamento de categoria especial — sem dado de saúde vazando para auditoria ou para quem não tem a permissão.

**Negativas / a gerenciar:**

- Os **códigos** do eSocial (`codCateg`, raça/cor, grau de instrução, tabela 18 de afastamentos, tabela 03 de rubricas) seguem leiautes que **mudam por versão** — devem ser **reconfirmados contra o leiaute vigente** (S-1.x) na Fase 4; o mapeamento de `codCategEsocial()` é um ponto de manutenção.
- PCD embutido em `funcionarios` exige **disciplina de permissão** (a coluna existe junto do resto) — daí `ver_dados_sensiveis` + `atributosNaoAuditados()` + defesa no servidor serem obrigatórios, não opcionais.
- Mais um par de permissões sensíveis a gerir (`ver_dados_sensiveis`, além de `ver_cid`) — a matriz de acesso ganha duas linhas de confidencialidade.
- A fronteira "ready ≠ transmitido" precisa ser comunicada ao cliente (expectativa) — reforçada em [00 §5/§8](../00-prd.md).

## Referências

- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — tudo aditivo, colunas nullable, core intocado.
- [ADR-0010: Enums PHP backed](../../../../architecture/adrs/ADR-0010-enums-php-backed.md) — domínios oficiais do eSocial como enums com CHECK.
- [ADR-RH-001: Funcionário como agregado-raiz](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md) — LGPD/PII centralizadas no agregado.
- [ADR-RH-005: Histórico funcional como eventos imutáveis](ADR-RH-005-historico-eventos-imutaveis.md) — tratamento do `cid` (dado de saúde) que o PCD espelha.
- [00 — PRD §4.1 (matriz S-2200) / §5 (escopo OUT)](../00-prd.md) · [01 — Modelo de Domínio §3 B1 (PCD), §4.1 (`codCateg`), §8 (LGPD), §10 (permissões)](../01-modelo-de-dominio.md) · [03 §2.1 (seção PCD)](../03-cadastro-pessoa-documentos.md).
