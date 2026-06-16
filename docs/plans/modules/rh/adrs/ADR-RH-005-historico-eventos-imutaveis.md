---
title: 'ADR-RH-005: Histórico funcional como eventos imutáveis'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-005: Histórico funcional como eventos imutáveis

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** historico, auditoria, rh

> Pacote `ht2erp/modulo-rh` (namespace `HT2ERP\Rh\`), aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). `Funcionario` é o agregado-raiz ([ADR-RH-001](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md)). Schema canônico em [01 — Modelo de Domínio](../01-modelo-de-dominio.md).

## Contexto e problema

A vida funcional do colaborador é uma sequência de **fatos**: admissão, promoção, alteração salarial, reajuste, transferência de departamento/filial, mudança de cargo, início/fim de afastamento, desligamento. O cliente precisa consultar essa **linha do tempo** como informação de negócio (a ficha do funcionário mostra o histórico), e a guarda trabalhista (eSocial/FGTS) exige retenção longa.

Ao mesmo tempo, as **listagens** precisam do estado **atual** (cargo, departamento, filial, salário de hoje) sem varrer o histórico inteiro a cada linha. E o histórico de um salário/lotação que vigorou no passado **não pode mudar** quando o valor atual muda — sob pena de adulterar o registro do que de fato ocorreu.

A pergunta: como modelar o histórico funcional para que ele seja (a) **consultável como modelo de negócio**, (b) **imutável** (o passado não se reescreve) e (c) **barato** de ler no caminho quente das listagens?

## Drivers da decisão

- Linha do tempo como **modelo de negócio** consultável (não um log técnico de baixo nível).
- **Imutabilidade** do passado: o que vigorou fica congelado ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)).
- Estado **atual** barato nas listagens (sem varrer o histórico) — desnormalização controlada.
- Retenção trabalhista longa (eSocial/FGTS): não expurgar.
- Granularidade por evento (cada mudança é uma linha com seu motivo, autor e snapshot).

## Alternativas consideradas

### Alt 1: Colunas versionadas no próprio `funcionarios` (SCD na própria tabela)

- Prós: tudo na entidade; sem tabela extra.
- Contras: perde **granularidade** — não há "uma linha por mudança" com motivo/autor; reconstruir a sequência de eventos vira engenharia reversa de colunas `*_anterior`. Mistura estado atual com histórico na mesma linha. Rejeitada.

### Alt 2: Uma tabela por tipo de evento (`promocoes`, `transferencias`, `reajustes`…)

- Prós: cada tipo com suas colunas específicas.
- Contras: **explode o schema** (uma tabela por tipo); a timeline unificada vira `UNION` de N tabelas heterogêneas; adicionar um tipo de evento é uma migration nova. Fragmenta a linha do tempo. Rejeitada.

### Alt 3: Usar só o `activity_log` (auditoria spatie) como histórico

- Prós: já existe; captura mudanças automaticamente.
- Contras: o `activity_log` é **auditoria técnica append de baixo nível** (quem mudou qual coluna, quando) — append-only por compliance, **não** um modelo de negócio consultável. Não tem `data_evento` (data de efeito ≠ data do registro), `motivo`, `tipo_evento` semântico nem snapshot de negócio estruturado. Forçá-lo a ser histórico de RH mistura responsabilidades e amarra o negócio ao formato da ferramenta de auditoria. Rejeitada (auditoria e histórico de negócio são camadas distintas).

### Alt 4: Tabela única de eventos append-only com snapshot JSONB (escolhida)

- Prós: uma timeline unificada, granular (uma linha por fato, com `tipo_evento`, `data_evento`, `motivo`, autor), imutável e com snapshot de negócio ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)); estado atual desnormalizado no agregado para leitura barata; adicionar um tipo é só um case de enum.
- Contras: o estado atual fica em **dois lugares** (evento + colunas atuais) — consistência depende de a Action escrever ambos na mesma transação; JSONB não é consultável por JOIN (é o ponto — é histórico, não operação).

## Decisão

O histórico funcional é a tabela **`funcionario_eventos`, append-only**, com snapshot JSONB ([01 §3 C1](../01-modelo-de-dominio.md)):

- **Sem `deleted_at`** e **sem rota de edição/exclusão** — é append-only. `tipo_evento` (enum `TipoEventoFuncional` com `afetaSalario()`/`afetaLotacao()`), `data_evento` (data de **efeito**, distinta do `created_at`), `motivo`, `registrado_por_admin_user_id`, os estados novos (`cargo_id`, `departamento_id`, `filial_id`, `salario_centavos`, `salario_anterior_centavos`) e `snapshot_anterior`/`snapshot_novo` (JSONB, [ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)).
- A **Action** de registro grava o evento **e** atualiza as colunas "atuais" do `funcionario` (cargo/departamento/filial/salário/`cargo_nivel`) **na mesma transação**, conforme `afetaSalario()`/`afetaLotacao()`. As colunas atuais são **cache** para listagens baratas; a **verdade temporal** está nos eventos.
- **Correção = evento compensatório** (estorno): nunca se edita ou apaga um evento; registra-se um novo evento que corrige. Isso preserva a trilha real (o que foi registrado, quando, por quem) e respeita a retenção trabalhista (não expurgar `funcionario_eventos`).

A imutabilidade aqui é a mesma invariante de [ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md): o snapshot é escrito uma vez e lido muitas, nunca usado em `WHERE` operacional. A **diferença de camada** em relação ao `activity_log`: este último é auditoria técnica (mudou-coluna-X); `funcionario_eventos` é **modelo de negócio** (houve-uma-promoção-com-este-motivo-nesta-data-de-efeito) — as duas coexistem, com responsabilidades distintas.

Afastamentos (`funcionario_afastamentos`) são fatos com início/fim e **são** soft-deletáveis (correção de lançamento) — entram na timeline visual junto dos eventos, mas seguem regra própria ([01 §3 C2](../01-modelo-de-dominio.md)); o `cid` é dado de saúde (LGPD art. 11). Mecânica completa em [06 — Linha do tempo](../06-linha-do-tempo.md).

## Consequências

**Positivas:**

- Timeline unificada, granular e auditável: cada fato é uma linha com tipo, data de efeito, motivo, autor e snapshot — consultável como negócio.
- Passado imutável ([ADR-0009](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md)): mudar o salário/lotação de hoje não reescreve o que vigorou ontem; correção é evento novo, rastreável.
- Listagens baratas: estado atual desnormalizado no agregado evita varrer o histórico (índice `(funcionario_id, data_evento)` cobre a timeline).
- Adicionar um tipo de evento é um case de enum, não uma tabela nova; retenção trabalhista atendida (append-only, não expurgar).
- Separação limpa de camadas: auditoria técnica (`activity_log`) e histórico de negócio (`funcionario_eventos`) não se confundem.

**Negativas / a gerenciar:**

- Estado atual em dois lugares (evento + colunas atuais): a consistência **depende** de a Action escrever ambos na mesma transação — fora da Action, há risco de divergência (regra: toda mudança de estado funcional passa pela Action de evento).
- Sem edição/exclusão de evento: um lançamento errado exige evento de estorno (não há "desfazer" simples) — treinar o usuário e oferecer UX clara de correção.
- JSONB de snapshot não é consultável por JOIN/agregação — é o ponto (histórico, não operação); relatórios analíticos usam as colunas estruturadas do evento, não o JSONB.
- A tabela cresce continuamente (append-only) — particionamento só se o volume exigir (não na Fase 1).

## Referências

- [ADR-0009: Snapshots JSONB imutáveis](../../../../architecture/adrs/ADR-0009-snapshots-jsonb-imutaveis.md) — invariante de imutabilidade aplicada ao snapshot do evento.
- [ADR-RH-001: Funcionário como agregado-raiz e vínculo com AdminUser](ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md) — o agregado é dono da transação evento+estado.
- [ADR-0015: Módulos de negócio como pacotes Composer distribuíveis](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — tabela e Action vivem no pacote, core intocado.
- `spatie/laravel-activitylog` (`activity_log`, trait `Auditavel` do core) — auditoria técnica, camada distinta do histórico de negócio.
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§3 C1 `funcionario_eventos`, §3 C2 `funcionario_afastamentos`, §4 `TipoEventoFuncional`, §8 LGPD) · [06 — Linha do tempo](../06-linha-do-tempo.md).
