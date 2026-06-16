# 10 — Campos Personalizados

> Como o **cliente** acrescenta campos próprios ao cadastro — sem código, sem migration, sem deploy. Define-se um **catálogo de definições** por empresa (tabela `campos_personalizados`) e os **valores** moram numa coluna JSONB na entidade hospedeira (`funcionarios.dados_personalizados`). Um trait reutilizável (`TemCamposPersonalizados`) e um enum (`TipoCampoPersonalizado`) dão o cast, a validação dinâmica e o mapeamento tipo→componente. O **schema é definido em [01](01-modelo-de-dominio.md)** (§A11, §B1, §4.2, §10 — fonte de verdade); aqui detalhamos o modelo, o trait, a UI e o reuso.
>
> Pacote: `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · views `rh::` · banco **PostgreSQL 16** · multi-tenant lógico por `empresa_id`. Decisão de modelagem em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md).

Relacionados: [01](01-modelo-de-dominio.md) · [03](03-cadastro-pessoa-documentos.md) · [11 §7](11-importacao-exportacao.md) · [adrs/ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)

---

## 1. O problema: "flexibilidade total para o cliente ajustar sem código"

O cliente pediu poder **ajustar o sistema sem programar**. Parte disso já é resolvida pelos **catálogos configuráveis** ([04](04-catalogos-configuraveis.md)) — o cliente cria departamentos, funções, tipos de documento. Mas catálogo é _linha em tabela existente_; não cobre o pedido de **campos novos** na ficha da pessoa: "quero registrar o tamanho da camiseta do uniforme", "preciso de uma matrícula legada do sistema antigo", "anotar o número do crachá", "marcar se participa do programa de creche".

Esses são **atributos que não existem no schema** e variam de empresa para empresa. Criar coluna/migration por pedido **não escala** (cada cliente teria um schema diferente — quebra o pacote distribuível) e contraria a evolução aditiva ([01 §6](01-modelo-de-dominio.md)). A resposta é um mecanismo de **campos personalizados**: o cliente **define** os campos pela UI e o sistema os **renderiza, valida e persiste** genericamente.

> **Fronteira com catálogo.** Catálogo ([04](04-catalogos-configuraveis.md)) = o cliente adiciona **linhas** a um conceito que o engenheiro modelou (um departamento a mais). Campo personalizado (este doc) = o cliente adiciona **colunas** (um atributo que o engenheiro não previu). Os dois compõem: um campo personalizado do tipo `select` pode, inclusive, listar opções que o cliente digitou.

---

## 2. Modelo: JSONB-híbrido (definições em tabela + valores em coluna)

A modelagem escolhida ([ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)) é **híbrida**: as **definições** são linhas tipadas numa tabela tenant; os **valores** ficam num documento JSONB na própria entidade. **Não** é EAV (uma linha por valor — explode em joins) nem schemaless (sem governança). É o equilíbrio: governança e UI dirigidas por dados, leitura barata (o valor vem junto com a linha do funcionário, sem join).

```
campos_personalizados (definições, por empresa+entidade)        funcionarios (entidade hospedeira)
┌───────────────────────────────────────────┐                  ┌──────────────────────────────────┐
│ entidade='funcionario'                      │                  │ id, empresa_id, nome, ...          │
│ chave='tamanho_camiseta'  tipo='select'     │   define ───►    │ dados_personalizados  JSONB        │
│ opcoes=["P","M","G","GG"]  obrigatorio=true │                  │   { "tamanho_camiseta": "G",       │
│ chave='matricula_legada'  tipo='texto'      │                  │     "matricula_legada": "A-4471" } │
└───────────────────────────────────────────┘                  └──────────────────────────────────┘
```

### 2.1 As definições — `campos_personalizados` ([01 §A11](01-modelo-de-dominio.md))

Catálogo tenant **meta** (`[E][S][A]`), uma linha por campo definido, chaveado por `(empresa_id, entidade, chave)`. Colunas-chave (schema completo em [01 §A11](01-modelo-de-dominio.md)):

| Coluna          | Papel                                                                                         |
| --------------- | --------------------------------------------------------------------------------------------- |
| `entidade`      | a quem o campo se aplica (`funcionario` na Fase 1) — permite reuso em outros models (§7)      |
| `chave`         | slug `snake_case`, **identifica a chave no JSONB** `dados_personalizados`; único por entidade |
| `rotulo`        | o que o usuário vê (label)                                                                    |
| `tipo`          | enum `TipoCampoPersonalizado` (§3) — dirige componente + validação                            |
| `opcoes`        | JSONB de opções (só `select`/`multi_select`)                                                  |
| `obrigatorio`   | torna o campo `required` na validação dinâmica                                                |
| `sensivel`      | **LGPD** — liga mascaramento + exclusão de auditoria (§6)                                     |
| `grupo`/`ordem` | agrupamento e ordenação na UI                                                                 |
| `regras`        | JSONB de validação extra (`min`/`max`/`regex`), resolvida por tipo (§3)                       |
| `ativo`         | liga/desliga (campo inativo não some dos dados já gravados, só deixa de ser editável)         |

#### Propriedades configuráveis (existentes × a adicionar) — checklist 2.2

O cliente pediu configurar muito mais que rótulo/tipo. A tabela separa o que **já existe no schema MVP** ([01 §A11](01-modelo-de-dominio.md)) do que entra como **evolução** — e **onde** cada nova config mora: a maioria cabe **dentro de `regras` (JSONB)** sem nova coluna; só as estruturais viram **coluna aditiva**.

| Propriedade                    | Existe no MVP? | Onde mora                           | Papel                                          |
| ------------------------------ | -------------- | ----------------------------------- | ---------------------------------------------- |
| `rotulo`                       | ✅ MVP         | coluna                              | label exibido                                  |
| `chave`                        | ✅ MVP         | coluna                              | slug, chave no JSONB                           |
| `tipo`                         | ✅ MVP         | coluna                              | dirige componente + validação (§3)             |
| `ajuda`                        | ✅ MVP         | coluna                              | tooltip/texto de ajuda                         |
| `obrigatorio`                  | ✅ MVP         | coluna                              | `required` na validação                        |
| `ativo`                        | ✅ MVP         | coluna                              | liga/desliga (preserva valores)                |
| `ordem` / `grupo`              | ✅ MVP         | coluna                              | ordenação + seção na UI                        |
| `opcoes`                       | ✅ MVP         | coluna (JSONB)                      | opções de `select`/`multi_select` (§2.3)       |
| `sensivel`                     | ✅ MVP         | coluna                              | LGPD: máscara + fora de auditoria (§6)         |
| `regras` (`min`/`max`/`regex`) | ✅ MVP         | coluna (JSONB)                      | validação extra por tipo                       |
| **`descricao`**                | 🔜 Evolução    | **coluna aditiva**                  | descrição longa (além da `ajuda` curta)        |
| **`valor_padrao`**             | 🔜 Evolução    | **coluna aditiva** (ou em `regras`) | valor inicial pré-preenchido (autofill — §3.2) |
| **`placeholder`**              | 🔜 Evolução    | em `regras`                         | texto-fantasma no input                        |
| **`somente_leitura`**          | 🔜 Evolução    | em `regras`                         | exibe, não edita (≠ `tipo=somente_leitura`)    |
| **`visivel`**                  | 🔜 Evolução    | **coluna aditiva**                  | oculta sem desativar (ex.: campo só de import) |
| **`tamanho_ui`** (largura)     | 🔜 Evolução    | em `regras`                         | grid do formulário (1/2/3 colunas)             |
| **`mascara`**                  | 🔜 Evolução    | em `regras`                         | máscara de input (`tipo=texto_mascara`, §3.2)  |
| **`casas_decimais`**           | 🔜 Evolução    | em `regras`                         | precisão de `decimal`/`porcentagem`            |
| **`formato_data`**             | 🔜 Evolução    | em `regras`                         | exibição de `data`/`data_hora`                 |
| **`mensagem_erro`**            | 🔜 Evolução    | em `regras`                         | mensagem de validação custom                   |
| **`exibir_em_listagem`**       | 🔜 Evolução    | **coluna aditiva**                  | vira coluna no `FuncionarioTable` (§8)         |

> **Regra de faseamento (D3):** o **MVP** entrega as 11 propriedades de cima — suficientes para campos planos úteis. As de evolução que são **só UI/validação** entram **dentro de `regras`** (JSONB já existe — zero migration); as **estruturais** (`descricao`, `valor_padrao`, `visivel`, `exibir_em_listagem`) entram como **colunas aditivas** (`add_*_to_campos_personalizados`) quando o cliente precisar — sempre nullable/com default ([01 §6](01-modelo-de-dominio.md)). Nada disso muda o modelo JSONB-híbrido.

### 2.2 Os valores — `funcionarios.dados_personalizados` ([01 §B1](01-modelo-de-dominio.md))

Uma coluna **`JSONB NULL`** na entidade, mapa `chave → valor`. Exemplo de conteúdo:

```json
{
    "tamanho_camiseta": "G",
    "matricula_legada": "A-4471",
    "participa_creche": true,
    "data_integracao": "2026-03-10"
}
```

- **Cast** no model: `'dados_personalizados' => 'array'` (Eloquent serializa/deserializa JSON ↔ array PHP).
- **Chaves órfãs** (definição apagada/renomeada) **permanecem** no JSONB — não são purgadas (preserva o histórico). A UI só renderiza o que tem definição **ativa**; um relatório de "chaves órfãs" é evolução (§8).
- **Nunca** vira `WHERE` de query operacional quente sem índice (ver §8); filtro pontual usa os operadores JSONB do Postgres.

### 2.3 Opções de seleção (`select`/`multi_select`/`radio`/`grupo_checkbox`) — checklist 2.3

Hoje `opcoes` é uma **lista simples** (`["P","M","G","GG"]`). O cliente pediu mais controle sobre as opções. **Estrutura-alvo** de cada opção (objeto, não só string):

| Atributo | Papel                                        | Fase        |
| -------- | -------------------------------------------- | ----------- |
| `label`  | o que o usuário vê                           | **MVP**     |
| `valor`  | o que é gravado no JSONB (estável, ≠ label)  | **MVP**     |
| `ordem`  | ordenação na lista                           | **MVP**     |
| `ativo`  | opção desativada some das **novas** seleções | 🔜 Evolução |
| `padrao` | opção pré-selecionada (default)              | 🔜 Evolução |

> **MVP:** `opcoes` = lista de `{label, valor}` na `ordem` do array (no mínimo aceitar strings simples, em que `label = valor`). **Evolução:** `ativo`/`padrao` por opção.

**Gestão das opções:**

- **Incluir / editar** opção — livre. **Editar o `label`** é seguro (o `valor` gravado não muda); **mudar o `valor`** de uma opção já usada é tratado como remoção+inclusão (ver §2.5 — preserva o valor antigo como **órfão**, não reescreve registros).
- **Desativar** opção (em vez de excluir) — **preserva** os registros que já a usam (mesma regra de chaves órfãs, §2.2); ela só não aparece em **novas** seleções. **[Evolução]** (depende de `ativo` por opção).
- **Reordenar (drag-drop)** as opções na tela de definição — **[Evolução de UX]** (§4.1).
- **Importar lista** (colar/CSV de opções, ex.: lista de cidades) — **[Evolução]**.
- **Dependência entre opções** (opção de um campo filtra as de outro — "Estado → Cidade") — **[Evolução]**, parte das **regras condicionais** (§2.4).
- **Variação por empresa/filial** — as definições **já são por empresa** (`empresa_id`); variar opções **por filial** dentro da mesma empresa é **[Evolução]** (condição por `filial_id`, §2.4).

### 2.4 Regras condicionais e dependências — checklist 2.4

> **Decisão de faseamento (D3), registrada em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md):** o **MVP da Fase 1 é de campos PLANOS** — sem condicionais. Todo campo definido aparece sempre e valida sempre pelas regras do seu tipo. As **regras condicionais abaixo são [Evolução]**, com o **desenho já pronto** aqui para entrarem sem reabrir a modelagem (vivem em `regras.condicoes`, JSONB — zero migration).

**Modelo das condicionais** (no JSONB `regras.condicoes` de cada definição) — array de regras `{quando: <expressão sobre outro campo>, entao: <efeito>}`:

| Efeito condicional                 | Exemplo                                                                  | Como (alvo)                                                                                              |
| ---------------------------------- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------- |
| **Exibir-se** (show-if)            | "mostrar `numero_crm` só se `categoria = médico`"                        | render condicional (Livewire reativo) + re-checagem no servidor                                          |
| **Obrigatório-se** (required-if)   | "`placa_veiculo` obrigatório se `usa_carro_proprio = sim`"               | `required_if` dinâmico nas Rules                                                                         |
| **Ocultar quando N/A**             | esconder campo irrelevante para o contexto                               | inverso do show-if                                                                                       |
| **Filtrar opções por outro campo** | "Cidade depende do Estado escolhido"                                     | opções dependentes (§2.3) — recarrega `opcoes` por valor pai                                             |
| **Limitar por contexto**           | campo só para `empresa`/`filial`/`departamento`/`cargo`/`tipo_vinculo` X | condição sobre atributo do funcionário/tenant                                                            |
| **Preenchimento automático**       | `valor_padrao`/autofill a partir de outro campo                          | `tipo=autofill`/`valor_padrao` (§2.1, §3.2)                                                              |
| **Bloquear por status**            | campo só editável enquanto `status = experiencia`                        | read-only condicional por `StatusFuncionario`                                                            |
| **Regras por perfil**              | campo editável só por RH, leitura para gestor                            | cruza com permissão da entidade (§9) + allowlist por modo ([03 §11.1](03-cadastro-pessoa-documentos.md)) |

- **Onde é avaliado:** condicionais são **reativas na UI** (Livewire reavalia ao mudar o campo-fonte) **e re-validadas no servidor** (a Action nunca confia só na UI). Um campo oculto por condição também fica **não-obrigatório/ignorado** na validação.
- **Por que fora do MVP:** condicionais multiplicam a complexidade de validação, render e teste; o cliente típico começa com campos planos. Entregar o **desenho** agora (e a coluna `regras` que já as comporta) torna a evolução **aditiva**, sem refazer o motor.

### 2.5 Alterações de definição e preservação de dados — checklist 2.6

Mudar uma definição depois que já há valores gravados é o ponto de risco. Política por tipo de alteração — princípio: **nunca corromper silenciosamente** o que já existe:

| Alteração na definição                    | Permitido?                  | Efeito nos valores já gravados                                                                                                                                                                                                                                               |
| ----------------------------------------- | --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Renomear `rotulo` / `ajuda` / `descricao` | ✅ livre                    | nenhum — o valor no JSONB é chaveado por `chave`, não por rótulo                                                                                                                                                                                                             |
| Reordenar / reagrupar (`ordem`/`grupo`)   | ✅ livre                    | nenhum — só muda a apresentação                                                                                                                                                                                                                                              |
| Tornar **obrigatório**                    | ✅ com ressalva             | registros antigos podem não ter o valor; a obrigatoriedade vale **na próxima edição**, não invalida o passado (a UI sinaliza pendência)                                                                                                                                      |
| Tornar opcional                           | ✅ livre                    | nenhum                                                                                                                                                                                                                                                                       |
| **Mudar o `tipo`**                        | ❌ proibido silenciosamente | mudar `texto`→`numero` quebraria os valores. **Regra:** ou **cria um campo novo** (migra à mão), ou **migração assistida** converte valor a valor com **alerta e prévia** — nunca um `UPDATE` cego. A tela **bloqueia** a troca direta de tipo de um campo com dados (§4.1). |
| Renomear a `chave`                        | ❌ evitar                   | a `chave` é a identidade no JSONB; renomear **órfana** os valores antigos. Tratado como campo novo.                                                                                                                                                                          |
| Desativar opção de `select`               | ✅                          | valores que a usam **permanecem** (órfã na exibição); some só das novas seleções (§2.3)                                                                                                                                                                                      |
| Desativar o campo (`ativo=false`)         | ✅                          | valores **preservados** no JSONB; o campo só deixa de ser editável/renderizado (§2.2)                                                                                                                                                                                        |
| Excluir o campo (lixeira/soft)            | ✅ (restaurável)            | valores **permanecem** como chaves órfãs (preserva histórico); restaurar reexibe                                                                                                                                                                                             |
| Excluir definitivamente                   | ✅ (sob permissão)          | a **definição** some; os **valores órfãos** seguem no JSONB até limpeza explícita (§8, evolução)                                                                                                                                                                             |
| Reutilizar a `chave` em outro módulo      | ✅                          | a `entidade` discrimina (§7) — `funcionario.crm` ≠ `cliente.crm`; sem colisão                                                                                                                                                                                                |

- **Sempre permitido:** ajustar rótulo/ajuda/ordem/grupo/obrigatoriedade e ativar/desativar — operações que **não tocam o valor gravado**.
- **Como os valores são preservados:** tudo se ancora na **`chave`** dentro do JSONB. Enquanto `chave` e `tipo` não mudam, o valor permanece válido e legível. Desativar/excluir **nunca apaga** o valor (só esconde) — disciplina de não destruir dado histórico ([01 §8.2](01-modelo-de-dominio.md)).
- **Impacto em relatórios/filtros/import-export:** desativar/renomear afeta colunas de export e filtros (§8 / [11 §5](11-importacao-exportacao.md)) — a tela "onde é usado" (§4.1) mostra o alcance **antes** de alterar.

---

## 3. Catálogo de tipos — `TipoCampoPersonalizado` (checklist 2.1)

Backed `string` ([01 §4.2](01-modelo-de-dominio.md)). O enum é a **única fonte** do mapeamento "tipo → componente de UI" e "tipo → regra de validação base" — sem `if` espalhado pela view ou pelas Rules. Esta seção documenta a **visão completa** de tipos (o checklist do cliente pede ~30), marcando o que é **[MVP Fase 1]** (8 casos) e o que é **[Evolução]** (aditivo: novo `case` no enum, sem migration de schema — [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)). Princípio (D3): **documentar tudo, implementar o MVP enxuto.**

### 3.1 Tipos do MVP (Fase 1) — 8 casos

| `tipo`         | Componente (`x-shared.*`)            | Regra base                       | `opcoes`? |
| -------------- | ------------------------------------ | -------------------------------- | --------- |
| `texto`        | `x-shared.input`                     | `string` + `max` (de `regras`)   | não       |
| `texto_longo`  | `x-shared.textarea`                  | `string`                         | não       |
| `numero`       | `x-shared.input` (inputmask inteiro) | `integer` + `min`/`max`          | não       |
| `decimal`      | `x-shared.input` (máscara decimal)   | `numeric` + `min`/`max`          | não       |
| `data`         | `x-shared.date-picker`               | `date`                           | não       |
| `booleano`     | `x-shared.toggle`                    | `boolean`                        | não       |
| `select`       | `x-shared.select-search`             | `in:<opcoes>`                    | **sim**   |
| `multi_select` | `x-shared.select-search :multiple`   | `array` + `in:<opcoes>` por item | **sim**   |

### 3.2 Tipos de Evolução (catálogo-alvo) — aditivos

Cada um entra como **novo `case`** do enum (com `componente()`/`regraValidacao()`), reaproveitando componentes que **já existem** no core onde houver (CLAUDE §9). Nenhum exige mudança de schema (continuam em `dados_personalizados` JSONB).

| `tipo` (alvo)      | Componente (`x-shared.*`)                         | Regra base / nota                               | `opcoes`? | Fase                |
| ------------------ | ------------------------------------------------- | ----------------------------------------------- | --------- | ------------------- |
| `texto_mascara`    | `x-shared.input` (Inputmask)                      | `regex` da máscara (de `regras.mascara`)        | não       | Evolução            |
| `porcentagem`      | `x-shared.input` (sufixo %)                       | `numeric` + `min:0`/`max:100`                   | não       | Evolução            |
| `data_hora`        | `x-shared.date-picker` (com hora)                 | `date`                                          | não       | Evolução            |
| `hora`             | `x-shared.input` (mask `HH:MM`)                   | `date_format:H:i`                               | não       | Evolução            |
| `email`            | `x-shared.input`                                  | `email:rfc`                                     | não       | Evolução            |
| `telefone`         | `x-shared.phone-input`                            | regra de telefone BR                            | não       | Evolução            |
| `cpf`              | `x-shared.cpf-input`                              | `new \App\Rules\Cpf()`                          | não       | Evolução            |
| `cnpj`             | `x-shared.input` (mask CNPJ)                      | regra de CNPJ                                   | não       | Evolução            |
| `cep`              | `x-shared.cep-input`                              | regra de CEP                                    | não       | Evolução            |
| `url`              | `x-shared.input`                                  | `url`                                           | não       | Evolução            |
| `radio`            | `x-shared.radio`                                  | `in:<opcoes>` (1 valor, layout aberto)          | **sim**   | Evolução            |
| `grupo_checkbox`   | grupo de `x-shared.toggle`/checkbox               | `array` + `in:<opcoes>` (multi, layout aberto)  | **sim**   | Evolução            |
| `cor`              | `x-shared.color-picker`                           | `regex` hex                                     | não       | Evolução            |
| `arquivo`          | Dropzone → `Anexo` (disco privado)                | binário **fora do JSONB** (guarda `anexo_id`)   | não       | Evolução (ver nota) |
| `imagem`           | Dropzone (image) → `Anexo`                        | idem `arquivo` (mime imagem)                    | não       | Evolução (ver nota) |
| `documento`        | igual `arquivo`, tipado por `tipos_documento`     | metadado + `Anexo`                              | não       | Evolução (ver nota) |
| `relacionado` (FK) | `x-shared.select-search` (dados de outro recurso) | `exists` na tabela-alvo (tenant)                | dinâmico  | Evolução (ver nota) |
| `calculado`        | só-leitura (expressão sobre outros campos)        | derivado, **não editável**                      | não       | Evolução (ver nota) |
| `somente_leitura`  | render só-leitura (rótulo + valor)                | nunca valida entrada (exibe valor fixo/herdado) | não       | Evolução            |
| `autofill`         | `x-shared.input` pré-preenchido (default)         | preenche por regra; ver `valor_padrao` (§2.1)   | não       | Evolução            |

**Exclusões conscientes (não viram tipo de campo personalizado):**

- **`monetario`/dinheiro** — dinheiro é **centavos `INTEGER`** ([ADR-0014](../../../architecture/adrs/ADR-0014-money-integer-centavos.md)); guardar valor monetário como número em JSONB convidaria a **float** e perda de precisão. Se um cliente exigir, entra como tipo `monetario` que **serializa centavos (inteiro)** no JSONB com `MoneyCast` na leitura — **decisão e ressalva em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)**; não no MVP.
- **`senha`/segredo** — **risco LGPD**: não se armazena segredo livre, sem cifra/rotina, num JSONB de cadastro. Segredos usam estruturas dedicadas (`encrypted`, como `two_factor_secret`); **fora** dos campos personalizados.
- **Dado de saúde (art. 11)** — `cid`, PCD e afins **não** são campo personalizado livre; usam as estruturas dedicadas ([01 §8.1](01-modelo-de-dominio.md)). A tela de definição **avisa** disso (§6).

> **Nota sobre `arquivo`/`imagem`/`documento`/`relacionado`/`calculado`:** estes **não** guardam o dado bruto em `dados_personalizados`. Arquivo/imagem/documento guardam um **`anexo_id`** (binário no `Anexo`, disco privado — [03 §8.3](03-cadastro-pessoa-documentos.md)); `relacionado` guarda o **id** do registro-alvo (FK lógica validada por `exists`); `calculado` **não persiste** (deriva de outros campos na leitura). Por isso são Evolução: exigem alça extra além do JSONB plano. Desenho pronto em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md).

### 3.3 Métodos do enum

Consumidos pela view e pelas Rules (valem para MVP e evolução):

- `componente(): string` — devolve o `x-shared.*` a renderizar (jamais `<select>` nativo — CLAUDE §9/§19).
- `regraValidacao(array $regras, ?array $opcoes): array` — monta as regras Laravel (tipo base + `min`/`max`/`regex`/`mascara` de `regras` + `in:` de `opcoes`).
- `aceitaOpcoes(): bool` — `true` para `select`/`multi_select`/`radio`/`grupo_checkbox` (a tela de definição só mostra o editor de opções nesses casos).
- `castValor(mixed $bruto): mixed` — normaliza o valor lido do JSONB para exibição (`booleano`→`bool`, `numero`→`int`, `data`→`Carbon`). O JSONB **guarda string ISO** (ex.: `"2026-03-10"`), nunca um `Carbon` serializado.
- `fase(): string` / `capacidade()` — **[a adicionar na evolução]** método utilitário que indica se o `case` é MVP ou evolução (e capacidades como "aceita opções", "usa anexo", "é calculado"); na Fase 1 a lista de cases **é** só a do MVP (§3.1), então o método é trivial — formaliza-se quando os tipos de evolução entrarem.

---

## 4. Abstração reutilizável: o trait `TemCamposPersonalizados`

O coração reutilizável é o trait `HT2ERP\Rh\Models\Concerns\TemCamposPersonalizados`, aplicado ao `Funcionario` (e a qualquer model futuro que ganhe campos personalizados — §7). Ele resolve as definições da empresa ativa, valida e expõe acessores.

```php
<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Models\Concerns;

use HT2ERP\Rh\Models\CampoPersonalizado;
use HT2ERP\Rh\Enums\TipoCampoPersonalizado;

/**
 * Dá ao model campos definidos pelo cliente: valores em `dados_personalizados`
 * (JSONB) governados pelas definições de `campos_personalizados` da empresa ativa.
 * A entidade é o slug retornado por entidadePersonalizada() (ex.: 'funcionario').
 */
trait TemCamposPersonalizados
{
    /** Slug da entidade nas definições (default: nome da classe em snake). */
    public function entidadePersonalizada(): string
    {
        return 'funcionario';
    }

    /** Definições ATIVAS da empresa ativa para esta entidade (cache por request). */
    public function definicoesPersonalizadas(): \Illuminate\Support\Collection
    {
        // Cache por (empresa_id, entidade) — espelha a disciplina do AccessCache/
        // FuncionarioAtual do core (05 §2.3). O global scope `empresa` já restringe.
        return CampoPersonalizado::query()
            ->where('entidade', $this->entidadePersonalizada())
            ->where('ativo', true)
            ->orderBy('grupo')->orderBy('ordem')
            ->get();
    }

    /** Regras de validação dinâmicas (consumidas pelas Rules — §5). */
    public function regrasPersonalizadas(): array
    {
        return $this->definicoesPersonalizadas()->mapWithKeys(function (CampoPersonalizado $d): array {
            // $d->tipo já é o enum (cast no model CampoPersonalizado — ver "Pontos de projeto").
            $base  = $d->tipo->regraValidacao($d->regras ?? [], $d->opcoes ?? null);
            $regra = $d->obrigatorio ? array_merge(['required'], $base) : array_merge(['nullable'], $base);

            return ["dados_personalizados.{$d->chave}" => $regra];
        })->all();
    }

    public function campoPersonalizado(string $chave): mixed
    {
        return data_get($this->dados_personalizados, $chave);
    }
}
```

Pontos de projeto:

- **Cast** `'dados_personalizados' => 'array'` no `$casts` do model hospedeiro (JSONB ↔ array); e `'tipo' => TipoCampoPersonalizado::class` no model `CampoPersonalizado` (logo `$d->tipo` é o enum, não string).
- **Cache de definições por request** chaveado por `(empresa_id, entidade)` — as definições mudam pouco e são lidas a cada render/validação; mesma disciplina de memoização do `AccessCache` e do `FuncionarioAtual` ([05 §2.3](05-organograma-acl-hierarquica.md)).
- **Validação dinâmica**: `regrasPersonalizadas()` devolve regras já no formato `dados_personalizados.<chave> => [...]`, prontas para fundir no `FuncionarioRules` (§5).
- **Acessores**: `campoPersonalizado('chave')` para leitura; a escrita passa pela Action do funcionário (allowlist por modo — [03 §11.1](03-cadastro-pessoa-documentos.md)).

### 4.1 CRUD e UX de gestão das definições — checklist 2.7

A gestão das **definições** é um CRUD tenant comum, gerado no padrão do módulo ([08](08-arquitetura-tecnica.md)): `IndexCampoPersonalizado` / `FormCampoPersonalizado` / `CampoPersonalizadoTable` (PowerGrid, com `ComLixeira`), `CampoPersonalizadoDTO` readonly, `CampoPersonalizadoRules` e `Create/UpdateCampoPersonalizadoAction`. A tela filtra por **`entidade`** (na Fase 1 só `funcionario`) e valida: `chave` slug único por `(empresa, entidade)`; editor de `opcoes` visível só quando `tipo->aceitaOpcoes()`; `regras` (min/max/regex) por tipo.

**Spec da tela** (o que o RH faz ao gerir campos) — marcando **[MVP]** × **[Evolução]**:

| Recurso da tela                                | Fase        | Nota                                                                                               |
| ---------------------------------------------- | ----------- | -------------------------------------------------------------------------------------------------- |
| Selecionar **módulo/entidade** alvo            | **MVP**     | na Fase 1 só `funcionario` (filtro fixo); o seletor cresce quando outras entidades adotarem (§7)   |
| **Criar** campo + escolher **tipo**            | **MVP**     | tipo da lista do §3.1 (MVP)                                                                        |
| **Configurar propriedades por tipo**           | **MVP**     | só mostra as configs aplicáveis ao tipo (ex.: editor de opções só em `select`); §2.1               |
| **Editor de opções**                           | **MVP**     | label+valor+ordem (§2.3); `ativo`/`padrao` por opção = evolução                                    |
| Definir **validações** (`min`/`max`/`regex`)   | **MVP**     | em `regras` (§2.1/§3)                                                                              |
| **Agrupar por seções** (`grupo`) + **ordenar** | **MVP**     | `grupo`/`ordem`; reordenar por campo de número                                                     |
| **Ativar / desativar**                         | **MVP**     | preserva valores (§2.5)                                                                            |
| **Permissões** da gestão                       | **MVP**     | `rh.campos_personalizados.*` (§9)                                                                  |
| **Reordenar por drag-drop**                    | 🔜 Evolução | UX sobre `ordem` (SortableJS — plugin já no core, CLAUDE §12)                                      |
| **Prévia do formulário** (preview ao vivo)     | 🔜 Evolução | renderiza o componente genérico (§4.2) com as definições atuais, sem salvar                        |
| **Duplicar** um campo                          | 🔜 Evolução | clona a definição (nova `chave`) para acelerar criação em lote                                     |
| **"Onde é usado"**                             | 🔜 Evolução | mostra em quais entidades/telas/exports/filtros o campo aparece — antes de alterar/excluir (§2.5)  |
| **"Já tem dados?"**                            | 🔜 Evolução | conta quantos registros já preencheram o campo — gate para alertar antes de mudança de tipo (§2.5) |
| **Alertas antes de alterações impactantes**    | 🔜 Evolução | confirma mudança de tipo/`chave`/desativação com aviso de impacto (cruza com §2.5)                 |

> **MVP enxuto, UX rica como evolução:** a Fase 1 entrega o CRUD funcional (criar/editar/agrupar/ativar campos planos). Os recursos de **conveniência e segurança operacional** (prévia, duplicar, "onde é usado", "já tem dados?", alertas, drag-drop) entram depois — nenhum muda o schema, só a tela de gestão.

### 4.2 Componente Livewire genérico de renderização

Um componente **único** renderiza os campos a partir das definições — nunca se escreve HTML por campo:

```blade
{{-- <livewire:rh.campos-personalizados :entidade="'funcionario'" wire:model="dadosPersonalizados" /> --}}
@foreach ($definicoes->groupBy('grupo') as $grupo => $campos)
    <section class="space-y-4" wire:key="cp-grupo-{{ $grupo }}">
        @if ($grupo)
            <h4 class="text-sm font-medium text-gray-700">{{ $grupo }}</h4>
        @endif
        @foreach ($campos as $def)
            <x-dynamic-component
                :component="$def->tipo->componente()"
                :label="$def->rotulo"
                wire:model="dadosPersonalizados.{{ $def->chave }}"
                :required="$def->obrigatorio"
                :options="$def->opcoes ?? []"
                :help="$def->ajuda"
            />
        @endforeach
    </section>
@endforeach
```

`x-dynamic-component` resolve o `x-shared.*` certo via `tipo->componente()`. Sem `<select>` nativo, sem CSS custom (Tailwind — CLAUDE §9).

---

## 5. Uso no cadastro de funcionários (cruza com [03](03-cadastro-pessoa-documentos.md))

Na Fase 1 os campos personalizados são aplicados ao **funcionário**, como uma **nova seção/aba "Personalizados"** no `FormFuncionario` ([03 §1](03-cadastro-pessoa-documentos.md)):

- **Render**: a aba "Personalizados" embute o componente genérico (§4.2), agrupando por `grupo`/`ordem`. Aparece **só** se a empresa tiver ≥1 definição ativa para `funcionario`.
- **Validação dinâmica**: `FuncionarioRules::regras()` **funde** `app(Funcionario::class)->regrasPersonalizadas()` ao conjunto fixo ([03 §12](03-cadastro-pessoa-documentos.md)) — os campos personalizados validam pelo mesmo caminho do resto (FormRequest + Livewire). O ponto vermelho de erro na aba (`abaTemErro('personalizados')`) reaproveita o helper de [03 §1](03-cadastro-pessoa-documentos.md).
- **Persistência**: a `Create/UpdateFuncionarioAction` grava `dados_personalizados` como parte do `update`/`create` (uma coluna, dentro da transação do agregado — [03 §12](03-cadastro-pessoa-documentos.md)). A **allowlist por modo** ([03 §11.1](03-cadastro-pessoa-documentos.md)) decide se o colaborador (modo `proprio`) pode tocar cada campo: campos personalizados são **editáveis pelo RH**; expor um subconjunto ao colaborador é configuração futura (na Fase 1, fora do recorte `proprio`).
- **Tela de definições**: o RH gere os campos em `/admin/rh/campos-personalizados` (CRUD de §4.1), sob `rh.campos_personalizados.*`.

---

## 6. LGPD — campos sensíveis tratados dinamicamente

O atributo `sensivel` de cada definição replica, **por dado**, o rigor que o módulo dá ao `cid`/PCD ([01 §8](01-modelo-de-dominio.md)) — só que resolvido **em runtime**, não por lista estática:

- **Mascaramento na exibição**: um campo `sensivel=true` aparece mascarado em listagens/leitura; valor completo só no form de edição autorizado (mesma postura de CPF/PIX — [03 §10](03-cadastro-pessoa-documentos.md)).
- **Fora de auditoria dinamicamente**: o trait remove as **chaves sensíveis** de `dados_personalizados` do diff do activitylog antes de gravar (redação por chave). Se a redação por chave não for viável no projeto, a postura conservadora é excluir a **coluna inteira** de `atributosNaoAuditados()` quando houver ≥1 campo sensível — decisão registrada em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md). Reforça "dados sensíveis nunca em logs" (CLAUDE §19).
- **Sem permissão dedicada por campo na Fase 1**: ver/editar os valores segue a permissão da entidade (`rh.funcionarios.editar`/`ver`); uma permissão por-campo (à la `ver_cid`) é evolução. Campos de **saúde** (art. 11) **não** devem ser modelados como campo personalizado livre — usam as estruturas dedicadas (`cid`, PCD); a tela de definição avisa disso. Matriz única de dados sensíveis em [01 §8.1](01-modelo-de-dominio.md).
- **Auditoria em duas camadas, com responsável e histórico:** (1) **alterações de definição** (`campos_personalizados` é `Auditavel`) — criar/editar/desativar/excluir campo grava no `activity_log` quem/quando/o quê; (2) **alterações de valor** — a coluna `dados_personalizados` entra no diff de auditoria do funcionário (`Auditavel`), **exceto** as chaves `sensivel=true`, redigidas dinamicamente (acima). Quem alterou (usuário + data) sai do `activity_log`; o histórico de uma mudança estrutural relevante (ex.: campo que vira obrigatório) também pode virar nota — sem reescrever valores passados (§2.5).

---

## 7. Reuso em outros módulos (fundação candidata a promoção ao core)

O mecanismo nasce no `modulo-rh`, mas é **agnóstico de domínio**. Adotá-lo em outra entidade (no RH ou num satélite — [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md)) é um passo-a-passo curto:

1. **Coluna**: migration aditiva `add_dados_personalizados_to_<tabela>` (`JSONB NULL`).
2. **Trait**: `use TemCamposPersonalizados` no model + cast `'dados_personalizados' => 'array'`; sobrescrever `entidadePersonalizada()` com o slug da entidade (ex.: `'cliente'`).
3. **Definições**: a tabela `campos_personalizados` já discrimina por `entidade` — a tela de definição (§4.1) filtra pelo novo slug, sem schema novo.
4. **Form + Rules**: embutir o componente genérico (§4.2) e fundir `regrasPersonalizadas()` nas Rules da entidade.

> **Promoção ao core.** Por ser fundação reutilizável (tabela + trait + enum + componente, sem domínio de RH), é **candidata a promoção ao core** — mesma lógica do [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md). Enquanto vive no `modulo-rh`, qualquer satélite que precise depende do `modulo-rh`; promovida, vira infra compartilhada. A decisão de quando promover está em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md). Esta suíte **documenta** o mecanismo no RH (decisão confirmada com o cliente), marcando-o como candidato — não força a promoção agora.

### 7.1 O motor genérico — detalhes de reuso (checklist 2.5)

O cliente pediu uma **base reutilizável** (não só "campos no funcionário"). Pontos que tornam o motor genérico:

- **Discriminador `entidade`** — a tabela `campos_personalizados` separa as definições por `entidade` (`funcionario`, e futuramente `cliente`, `fornecedor`, …). Os valores moram na coluna `dados_personalizados` da própria entidade. Um mesmo `chave` em entidades diferentes **não colide** (`funcionario.crm` ≠ `cliente.crm`).
- **Campos por módulo vs globais** — na Fase 1, cada campo pertence a **uma entidade** (escopo de módulo). Um conceito de **campo global** (mesma definição aplicada a várias entidades) é **[Evolução]**: ou se replica a definição por entidade, ou se introduz um `entidade='*'`/grupo — desenho aberto, não no MVP.
- **Variação por empresa/filial** — as definições já são **por empresa** (`empresa_id`, tenant). Variar **por filial** dentro da empresa (um campo só para a Filial X) é **[Evolução]**, via condicional de contexto (§2.4).
- **Agrupamento em seções e posição** — `grupo` (seção/aba) + `ordem` posicionam o campo no formulário da entidade hospedeira (§4.2); a mesma estrutura serve a qualquer entidade.
- **Armazenamento / consulta / alteração** — armazenamento em JSONB na entidade (§2.2); consulta por operadores JSONB tenant-scoped (§8); alteração preservando dados (§2.5) — **idêntico** em qualquer entidade que adote o trait.
- **Critério de promoção ao core** — promover quando **um segundo módulo/entidade** (no RH ou num satélite — [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md)) precisar do mecanismo: aí a tabela `campos_personalizados` + trait + enum + componente saem do `modulo-rh` para a infra compartilhada do core, sem reabrir esta decisão ([ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)). Até lá, satélites que precisem dependem do `modulo-rh`.

---

## 8. Limites, filtros e evolução

- **Filtro/relatório por campo**: usa os **operadores JSONB do Postgres** — `dados_personalizados ->> 'chave' = 'valor'` (texto), `(dados_personalizados ->> 'chave')::int > 10` (número). Tenant-scoped por `empresa_id`. Exemplo:

    ```sql
    SELECT id, nome FROM funcionarios
    WHERE empresa_id = :empresa
      AND dados_personalizados ->> 'tamanho_camiseta' = 'G';
    ```

- **Índice GIN (evolução, não obrigatório na Fase 1)**: se um campo personalizado virar filtro quente, materializa-se um índice GIN — sem mudar o modelo:

    ```sql
    CREATE INDEX idx_func_dados_personalizados
        ON funcionarios USING GIN (dados_personalizados jsonb_path_ops);
    ```

- **Sem busca livre cross-empresa**: a consulta é sempre dentro do tenant; nunca varrer JSONB de todas as empresas.
- **Tipos futuros**: `dinheiro` (centavos), `arquivo` (anexo via `Anexo`), `referencia` (FK para um catálogo) entram como novos casos do enum — aditivo ([ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md)).
- **Limpeza de chaves órfãs**: comando de manutenção que lista valores sem definição ativa — evolução, não Fase 1.

### 8.1 Listagens, relatórios, dashboards e API (checklist 2.9)

Como os campos dinâmicos aparecem além do formulário — marcando a fase:

- **Listagens dinâmicas** — um campo com `exibir_em_listagem` (§2.1) vira **coluna no `FuncionarioTable`** (PowerGrid), com filtro/ordenação pela expressão JSONB (`dados_personalizados ->> 'chave'`). **[Evolução]** (depende da config `exibir_em_listagem`); no MVP os campos vivem no formulário/ficha.
- **Filtros** — operadores JSONB tenant-scoped (acima); **GIN** como evolução de performance. **[MVP: filtro pontual]**; **[Evolução: filtro como cidadão de 1ª classe na grade]**.
- **Relatórios** — relatórios que cruzam campos personalizados (ex.: "contagem por `tamanho_camiseta`") leem o JSONB por `empresa_id`; **[Evolução]** (relatório dedicado).
- **Dashboards** — KPIs/gráficos sobre campos personalizados (ApexCharts do core) — **[Evolução]**.
- **API** — expor campos personalizados via API (o agregado já é "API-ready" — services retornam DTO; o `dados_personalizados` entra no DTO) é **[Evolução]**, alinhada à fase em que o módulo ganhar API.
- **Import/export** — colunas `cp_<chave>` na planilha (round-trip): detalhado em [11 §5](11-importacao-exportacao.md). **[MVP do import: pós-Fase 1]** (o import multi-aba é pós-Fase 1); o **export simples** da listagem (PowerGrid) já sai na Fase 1, sem os `cp_*` por padrão.

> **Como um campo dinâmico vira coluna de planilha:** a importação/exportação ([11 §5](11-importacao-exportacao.md)) lê as **definições ativas** da empresa e gera uma coluna `cp_<chave>` por campo; o template documenta os valores válidos. Identificação na importação/atualização é pela `chave` (`cp_<chave>`), casada à definição — coluna sem definição é ignorada com aviso; obrigatória ausente vira erro de linha.

---

## 9. Permissões, segurança e auditoria (checklist 2.8)

Quem **cria/edita/desativa/exclui definições** vs quem **vê/preenche/altera valores** — e como tudo é auditado (a proteção de sensíveis e a auditoria em duas camadas estão em §6).

| Permissão ([01 §10](01-modelo-de-dominio.md))                                         | O que cobre                                                                                                                  |
| ------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `rh.campos_personalizados.{listar,criar,editar,deletar,restaurar,excluir_permanente}` | gestão das **definições** (a tela de §4.1, com lixeira)                                                                      |
| _(valores)_ `rh.funcionarios.{ver,editar}`                                            | ver/editar os **valores** em `dados_personalizados` segue a permissão da **entidade** — não há permissão por-valor na Fase 1 |

A edição de definição é operação de **RH/admin** (estrutura o cadastro de todos); a edição de valor é a mesma do cadastro do funcionário. Tudo conferido no servidor (Policy), nunca só na UI.

---

## 10. Faseamento — visão completa, MVP enxuto (checklist 2.10)

> Princípio **D3**: a documentação cobre a **visão completa** (catálogo amplo de tipos, configs, opções, condicionais, motor cross-módulo, UX rica), mas a **Fase 1 implementa só o MVP** — cada recurso acima está marcado **[MVP]** ou **[Evolução]**, sem inflar o código da Fase 1. Decisão em [ADR-RH-008 §Faseamento](adrs/ADR-RH-008-campos-personalizados.md).

- **Fase 1 (incremento de B2 — [02](02-fase-1-blueprint.md)):** tabela `campos_personalizados` + coluna `funcionarios.dados_personalizados` + enum `TipoCampoPersonalizado` (**8 tipos planos**, §3.1) + trait `TemCamposPersonalizados` + tela de definições (CRUD, §4.1) + aba "Personalizados" no `FormFuncionario`, **aplicados ao funcionário**. Campos **planos** (sem condicionais), propriedades essenciais (§2.1), opções `label/valor/ordem` (§2.3).
- **Pós-Fase 1 (tudo aditivo):** tipos de evolução (§3.2, ~20 novos `case`), **regras condicionais** (§2.4), propriedades/opções ricas (§2.1/§2.3), **UX de gestão** avançada (§4.1 — drag-drop/prévia/duplicar/"onde é usado"/"já tem dados?"/alertas), migração assistida de tipo (§2.5), reuso em outras entidades (§7/§7.1), índice GIN + listagens dinâmicas/relatórios/dashboards/API (§8/§8.1), exposição parcial ao colaborador (self-service), e a eventual **promoção ao core**.

---

## 11. Checklist de implementação (Fase 1)

- [ ] Migration `campos_personalizados` ([01 §A11](01-modelo-de-dominio.md): unique `(empresa_id, entidade, chave)` parcial, índices, CHECK do enum); coluna `funcionarios.dados_personalizados` JSONB ([01 §B1](01-modelo-de-dominio.md)); factories.
- [ ] Enum `TipoCampoPersonalizado` ([01 §4.2](01-modelo-de-dominio.md)) com `componente()`, `regraValidacao()`, `aceitaOpcoes()`, `castValor()`.
- [ ] Trait `TemCamposPersonalizados` (cast, `definicoesPersonalizadas()` com cache por `(empresa,entidade)`, `regrasPersonalizadas()`, acessores, redação de chaves sensíveis na auditoria).
- [ ] CRUD das definições: `IndexCampoPersonalizado`/`FormCampoPersonalizado`/`CampoPersonalizadoTable` + DTO + Rules + Actions; editor de `opcoes` só quando `aceitaOpcoes()`; lixeira (`ComLixeira`).
- [ ] Componente Livewire genérico de renderização (`x-dynamic-component` → `x-shared.*`), agrupando por `grupo`/`ordem`; sem `<select>` nativo.
- [ ] `Funcionario` com o trait; aba "Personalizados" no `FormFuncionario`; `FuncionarioRules` funde `regrasPersonalizadas()` ([03 §12](03-cadastro-pessoa-documentos.md)); persistência via `Create/UpdateFuncionarioAction` (allowlist por modo — [03 §11.1](03-cadastro-pessoa-documentos.md)).
- [ ] LGPD: `sensivel` → mascaramento + redação de auditoria dinâmica; aviso "saúde usa estruturas dedicadas".
- [ ] Permissões `rh.campos_personalizados.*` ([01 §10](01-modelo-de-dominio.md)); Policy.
- [ ] Testes Pest: definição (slug único por empresa/entidade); validação dinâmica (obrigatório/tipo/opções); persistência no JSONB; mascaramento de campo sensível; tenant scope; campo inativo não renderiza mas preserva valor.
- [ ] Pós-tarefa: `pint`, `prettier` nas views `rh::`, `phpstan`, `php artisan test`.
