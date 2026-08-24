---
title: 'ADR-RH-009: Armazenamento seguro de documentos (disco privado, download autorizado, retenção)'
version: 1.0.0
date: 2026-06-16
status: proposed
---

# ADR-RH-009: Armazenamento seguro de documentos (disco privado, download autorizado, retenção)

**Status:** Proposed | **Data:** 2026-06-16 | **Decisores:** HT2 ERP / GDF Sistemas | **Tags:** segurança, lgpd, rh, armazenamento

> Pacote `ht2ml/extensao-rh`, aditivo ao core ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). Reaproveita o `HT2ML\Core\Models\Anexo` (upload polimórfico) do core. Spec de UI/fluxo em [03 §8.3/§10](../03-cadastro-pessoa-documentos.md); LGPD em [01 §8](../01-modelo-de-dominio.md). **Esta decisão é de estratégia/definição — registra a política de armazenamento seguro local, não a implementação.**

## Contexto e problema

Os documentos do RH (RG, CPF, CTPS, comprovantes, ASO, atestados) são **PII** — alguns são **dado de saúde** (atestado/`cid`, LGPD art. 11). O upload já é resolvido pelo `HT2ML\Core\Models\Anexo` polimórfico do core, **mas** o `GerenciadorAnexos` do core hoje grava no **disco `public`** (`store('anexos','public')`) — adequado para logo/branding, **inaceitável** para PII: arquivo em `public` tem **link direto e adivinhável**, é servido sem passar por autorização e pode ser indexado.

Precisa-se de uma **política de armazenamento seguro local** (a Fase 1 não usa storage externo): onde gravar, com que nome, quem pode baixar, por quanto tempo guardar e como auditar — **sem reinventar** o `Anexo` e **sem editar o core** ([ADR-0015](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).

## Drivers da decisão

- **Confidencialidade** — PII e dado de saúde nunca acessíveis por link público/adivinhável.
- **Controle de acesso por ACL hierárquica** — quem vê o funcionário vê seus documentos ([05](../05-organograma-acl-hierarquica.md)); sensíveis exigem permissão dedicada.
- **Reuso do core** — o `Anexo` (metadados + disco por linha) é a base; o RH parametriza, não recria.
- **Aditividade** — não editar o `Anexo`/`GerenciadorAnexos` de forma destrutiva; ajuste aditivo com default preservado.
- **Retenção trabalhista** — guarda legal longa (eSocial/FGTS); exclusão não pode apagar o que a lei exige manter.
- **Auditabilidade** — upload/substituição/exclusão e o **acesso a documento sensível** deixam trilha.

## Alternativas consideradas

### Alt 1 — Manter o disco `public` (comportamento atual do `GerenciadorAnexos`)

- Prós: zero trabalho; URL direta simples.
- Contras: **link público adivinhável**, sem autorização, indexável — **viola LGPD** para PII/saúde. Rejeitada para documentos de RH.

### Alt 2 — Disco **privado** + download por controller autorizado (Policy) + URL assinada temporária (escolhida)

- Prós: arquivo **fora do webroot**; todo acesso passa por **autorização** (ACL + permissão); URL expira; nome físico não-adivinhável. Reusa o `Anexo` (que já guarda o disco por linha).
- Contras: todo download passa por uma rota/controller (custo desprezível no volume de RH); exige parametrizar o disco do `GerenciadorAnexos` (ajuste aditivo).

### Alt 3 — Criptografia do binário em repouso / storage externo (S3 + KMS)

- Prós: confidencialidade máxima; offload de storage.
- Contras: complexidade e dependência externa **fora do escopo da Fase 1** (ambiente local/DDEV). Fica como **evolução** (checksum/hash e cifra de binário são incrementos), sem bloquear a Alt 2.

## Decisão

**Armazenamento privado local, com download sempre autorizado.** Política ([03 §8.3/§10](../03-cadastro-pessoa-documentos.md)):

1. **Disco dedicado `rh_privado`** (`storage/app/private/rh`, fora do webroot). Documentos de RH **nunca** no `public`.
2. **Layout de diretórios**: `rh/{empresa_id}/funcionarios/{funcionario_id}/{tipo_documento_codigo}/{ulid_ou_hash}.{ext}`. Nome físico **não-adivinhável** (ULID/hash); o **nome original** fica em `Anexo.nome_original` (exibição).
3. **Download sempre por controller autorizado por Policy** — a ACL hierárquica ([05](../05-organograma-acl-hierarquica.md)) decide _quem vê o funcionário vê os documentos_; documentos **sensíveis** exigem permissão dedicada (ex.: `cid`/saúde — [01 §8](../01-modelo-de-dominio.md)). O download faz `Storage::disk('rh_privado')->download(...)` numa **rota assinada temporária**; **nunca** `Anexo::url()` (o driver privado não serve URL pública), link público nem `disk('public')`.
4. **Registro de operações** — auditoria via `Auditavel` no `Anexo` (upload/substituição/exclusão) **+** log do **download de documento sensível** (quem baixou o quê, quando) — decisão de registrar o acesso, não só a mutação.
5. **Ciclo de vida** — inclusão / **substituição = versionamento** (novo `Anexo` + soft-delete do anterior) / exclusão (soft-delete **mantém o binário**; force-delete remove o físico no `forceDeleted`). **Retenção trabalhista longa** — não expurgar por rotina.
6. **Confidencialidade/integridade** — `encrypted` para **metadados** sensíveis (ex.: `cid` em atestado/afastamento); **checksum/hash** do arquivo como **evolução** (integridade).
7. **Como reusar o `Anexo` sem ferir o ADR-0015** — o `GerenciadorAnexos` do core grava em `public` com caminho fixo e monta a lista via `Anexo::url()` (que **não** funciona em disco `local`/privado). Logo o RH usa um **componente próprio do pacote** (`GerenciadorAnexosRh`) que **reusa o _model_ `Anexo`** com `disco='rh_privado'`, caminho `rh/{empresa_id}/...` e **download por rota assinada** (`Storage::disk('rh_privado')->download(...)` após a Policy) — **sem editar** o componente do core. _Alternativa:_ parametrizar o `GerenciadorAnexos` do core (disco + caminho + geração de URL, defaults preservados) como mudança **aditiva aprovada** — mais invasiva. Anotado em [03 §8.3](../03-cadastro-pessoa-documentos.md) e nas **notas de reconciliação** do [README](../README.md).

## Consequências

**Positivas:**

- PII/saúde **protegidas**: sem link público, acesso sempre autorizado pela ACL + permissão, URL expira.
- **Reuso** do _model_ `Anexo` (sem recriar upload), via componente próprio do pacote (`GerenciadorAnexosRh`) — **core intocado**; parametrizar o componente do core fica como alternativa aditiva.
- **Versionamento e retenção** atendem à guarda legal trabalhista; trilha de auditoria de mutação **e** de acesso a sensível.

**Negativas / a gerenciar:**

- **Todo download passa por controller/Policy** — sem servir estático direto; custo desprezível, mas exige a rota de download assinada.
- **Ajuste no core** (parametrizar disco) precisa ser feito de forma aditiva e coberto por teste — é o único ponto que toca o componente do core.
- **Gestão de URLs assinadas** (expiração, regeneração) e do disco privado (backup/limpeza de versões antigas respeitando retenção).
- Cifra de binário e checksum são **evolução** — a Fase 1 entrega disco privado + download autorizado, não cifra em repouso.

## Referências

- [03 — Cadastro de Pessoa e Documentos](../03-cadastro-pessoa-documentos.md) (§8.3 upload seguro, §10 LGPD na UI).
- [01 — Modelo de Domínio](../01-modelo-de-dominio.md) (§8 LGPD; `cid`/PCD; `funcionario_documentos`/`atestados`).
- [05 — Organograma e ACL hierárquica](../05-organograma-acl-hierarquica.md) — quem vê o funcionário vê os documentos.
- [ADR-0015: Módulos como pacotes Composer](../../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) — ajuste aditivo no `GerenciadorAnexos` (default preservado).
- [README — Notas de reconciliação](../README.md) — disco privado parametrizável é ponto de implementação registrado.
