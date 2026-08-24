# 03 — Cadastro de Pessoa e Documentos

> Especificação funcional e técnica do **agregado Funcionário** (cadastro de pessoa + documentos) do módulo de RH. Telas, abas, campos, componentes, validações e fluxo Livewire → DTO → Action. O **schema é definido em [01](01-modelo-de-dominio.md)** (fonte de verdade); aqui só consumimos os nomes de tabelas/colunas/enums de lá.
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · views `rh::` · multi-tenant por `empresa_id`. **eSocial-ready** e **self-service** são decisões fechadas do cliente e moldam várias seções abaixo.

Relacionados: [01](01-modelo-de-dominio.md) · [04](04-catalogos-configuraveis.md) · [05](05-organograma-acl-hierarquica.md) · [06](06-linha-do-tempo.md)

---

## 1. Visão geral da tela

O cadastro de funcionário é um **formulário único em abas** (não um wizard linear), servido pelo componente Livewire `HT2ML\Rh\Livewire\Funcionarios\FormFuncionario` no layout admin (Inspinia). A lista vem de `IndexFuncionario` (com `FuncionarioTable` via PowerGrid); a edição/criação abre o `FormFuncionario`.

As abas espelham os blocos do modelo (01 §3, Bloco B):

| #   | Aba                     | Tabela(s) de origem                             | Cardinalidade                 |
| --- | ----------------------- | ----------------------------------------------- | ----------------------------- |
| 1   | **Identificação**       | `funcionarios` (dados pessoais)                 | 1:1                           |
| 2   | **Documentos pessoais** | `funcionarios` (RG/CPF/PIS)                     | 1:1                           |
| 3   | **Contato e Endereço**  | `funcionario_contatos`, `funcionario_enderecos` | 1:N (linhas repetíveis)       |
| 4   | **Bancário**            | `funcionario_dados_bancarios`                   | 1:N (com `principal`)         |
| 5   | **Dependentes**         | `funcionario_dependentes`                       | 1:N                           |
| 6   | **Contratação**         | `funcionarios` (vínculo/lotação/salário)        | 1:1                           |
| 7   | **Documentos / Anexos** | `funcionario_documentos` (+ `anexos`)           | 1:N                           |
| 8   | **Personalizados**      | `funcionarios.dados_personalizados` (JSONB)     | campos definidos pelo cliente |

> A aba **Personalizados** (8) só aparece quando a empresa tem campos definidos em `campos_personalizados` ([01 §A11](01-modelo-de-dominio.md)); embute o componente genérico que renderiza os campos a partir das definições, agrupados por `grupo`/`ordem`. Mecânica, trait `TemCamposPersonalizados`, validação dinâmica e LGPD por campo em [10](10-campos-personalizados.md).

### Componentes de aba (obrigatórios — nunca `<select>` HTML)

As abas usam os componentes `x-shared.tab-*` em modo **server-driven** (Livewire controla qual aba está ativa, para que erros de validação possam reabrir a aba certa):

```blade
<x-shared.tab-nav :justified="true">
    <x-shared.tab-trigger
        id="identificacao"
        :preline="false"
        :active="$abaAtiva === 'identificacao'"
        :hasError="$this->abaTemErro('identificacao')"
        wire:click="$set('abaAtiva', 'identificacao')"
    >
        Identificação</x-shared.tab-trigger
    >
    {{-- ...demais gatilhos... --}}
</x-shared.tab-nav>

<x-shared.tab-body>
    <x-shared.tab-panel id="identificacao" :active="$abaAtiva === 'identificacao'">
        {{-- campos --}}
    </x-shared.tab-panel>
    {{-- ...demais painéis... --}}
</x-shared.tab-body>
```

- `:preline="false"` → o estado ativo vem de `:active` (servidor), não das variantes Preline; combinamos com `wire:click="$set('abaAtiva', ...)"`.
- `:hasError` desenha o ponto vermelho no gatilho mesmo com a aba inativa — o helper `abaTemErro(string $aba): bool` mapeia chaves do `$errors` para cada aba (ver §11).
- Inputs sempre via `x-shared.*`: `x-shared.input`, `x-shared.textarea`, `x-shared.select-search`, `x-shared.date-picker`, `x-shared.toggle`, `x-shared.cpf-input`, `x-shared.cep-input`, `x-shared.phone-input`, `x-shared.money-input`. **Proibido** `<select>` nativo e `x-shared.select` nativo: usar `x-shared.select-search` (single) ou `:multiple="true"` (multi).

> **Risco/esforço — repeaters.** As **5 coleções repetíveis** do `FormFuncionario` (contatos, endereços, dados bancários, dependentes, documentos) **não têm um componente genérico** no catálogo (não existe `x-shared.repeater`): cada uma é um array de arrays em Livewire, com `adicionar*/remover*`, regra de "um principal" e sync na Action (§12). É o **item de maior complexidade de frontend da Fase 1** — dimensionar o esforço e **avaliar componentizar um repeater reutilizável** (fluxo do catálogo, CLAUDE §9) antes de replicar a lógica 5×.

Campos de `funcionarios` (01 §3 B1, bloco _Dados pessoais_) + lotação/organograma editáveis aqui por conveniência (o histórico verdadeiro é a linha do tempo — [06](06-linha-do-tempo.md)).

| Campo (`funcionarios`)      | Componente                                                 | Validação (resumo)                                          |
| --------------------------- | ---------------------------------------------------------- | ----------------------------------------------------------- |
| `foto_caminho`              | `x-shared.avatar-cropper` (upload p/ disco **privado**)    | imagem, `max:2048`, mime image/\* — ver §10 LGPD            |
| `nome`                      | `x-shared.input`                                           | `required`, `string`, `max:150`                             |
| `nome_social`               | `x-shared.input`                                           | `nullable`, `max:150`                                       |
| `data_nascimento`           | `x-shared.date-picker`                                     | `nullable`, `date`, `before_or_equal:today`                 |
| `sexo`                      | `x-shared.select-search` (opções de `Sexo::options()`)     | `nullable`, `Rule::enum(Sexo::class)`                       |
| `estado_civil`              | `x-shared.select-search` (`EstadoCivil`)                   | `nullable`, `Rule::enum(EstadoCivil::class)`                |
| `escolaridade`              | `x-shared.select-search` (`Escolaridade`)                  | `nullable`, `Rule::enum(Escolaridade::class)` — **eSocial** |
| `raca_cor`                  | `x-shared.select-search` (`RacaCor`)                       | `nullable`, `Rule::enum(RacaCor::class)` — **eSocial**      |
| `nacionalidade_pais_id`     | `x-shared.select-search` (catálogo `paises`)               | `nullable`, `exists:paises,id` — **eSocial**                |
| `naturalidade_municipio_id` | `x-shared.select-search` (catálogo `municipios`)           | `nullable`, `exists:municipios,id` — **eSocial**            |
| `nome_mae`                  | `x-shared.input`                                           | `nullable`, `max:150` · **PII**                             |
| `nome_pai`                  | `x-shared.input`                                           | `nullable`, `max:150` · **PII**                             |
| `departamento_id`           | `x-shared.select-search` (catálogo tenant `departamentos`) | `nullable`, `exists` por empresa                            |
| `cargo_id`                  | `x-shared.select-search` (**catálogo `cargos`/CBO**)       | `nullable`, `exists:cargos,id`                              |
| `gestor_id`                 | `x-shared.select-search` (`funcionarios` da empresa)       | `nullable`, `exists` por empresa, `≠` ele mesmo             |
| `filial_id`                 | `x-shared.select-search` (`filiais` ativas da empresa)     | `nullable`, `exists` por empresa                            |

> **`cargo_id` vem de catálogo, não de enum** — decisão registrada em 01 §0/ADR-RH-002 e travada pelo teste de intenção `tests/Feature/Rh/FuncionarioCargoTest.php`, que espera `FormFuncionario::$cargosDisponiveis` populado pelo `CargoSeeder` (chave `'Administrador'`). O `FormFuncionario` expõe `public array $cargosDisponiveis` carregado no `mount()` via `Cargo::pluck('nome','id')` (ver §11). `cargo_nivel` é cache desnormalizado e **não** é editado na tela — é derivado do cargo escolhido na Action.

### 2.1 Seção PCD / Deficiência (eSocial-ready, dado de saúde — acesso restrito)

Grupo do S-2200 `infoDeficiencia` ([01 §3 B1](01-modelo-de-dominio.md); matriz em [00 §4.1](00-prd.md)). São **toggles** (não texto livre) numa seção própria **dentro da aba Identificação**, marcada com o selo "eSocial" (`x-shared.tooltip`) e sob **visibilidade restrita** — é **categoria especial de dado de saúde (LGPD art. 11)**, tratada com o **mesmo rigor do `cid`**.

| Campo (`funcionarios`)   | Componente          | Validação / nota                                    |
| ------------------------ | ------------------- | --------------------------------------------------- |
| `def_fisica`             | `x-shared.toggle`   | `nullable`, `boolean` · **dado de saúde**           |
| `def_visual`             | `x-shared.toggle`   | `nullable`, `boolean`                               |
| `def_auditiva`           | `x-shared.toggle`   | `nullable`, `boolean`                               |
| `def_mental`             | `x-shared.toggle`   | `nullable`, `boolean`                               |
| `def_intelectual`        | `x-shared.toggle`   | `nullable`, `boolean`                               |
| `reabilitado_readaptado` | `x-shared.toggle`   | `nullable`, `boolean` (reabilitado/readaptado INSS) |
| `beneficiario_cota`      | `x-shared.toggle`   | `nullable`, `boolean` (cota PCD — Lei 8.213/91)     |
| `observacao_pcd`         | `x-shared.textarea` | `nullable` · **PII sensível** (laudo/observações)   |

- **Visibilidade restrita por permissão:** a seção PCD só é exibida/editável com `rh.funcionarios.ver_dados_sensiveis` ([01 §10](01-modelo-de-dominio.md)). Sem ela, a seção é **ocultada** (não apenas desabilitada) e a Action **ignora** qualquer alteração nesses campos — **defesa no servidor**, não só na UI (mesma postura do `cid`, §10).
- **Fora de auditoria:** o grupo PCD está em `atributosNaoAuditados()` ([01 §3 B1](01-modelo-de-dominio.md)) — não vaza para o diff do activitylog.
- **Self-service:** no modo `proprio` (§11), o colaborador **não** vê nem edita o grupo PCD (dado gerido pelo RH).
- **Isolamento físico (alternativa):** se o cliente exigir segregação de armazenamento, o grupo migra para a tabela-filha 1:1 `funcionario_pcd` sem mudar a UI ([01 §3 B1](01-modelo-de-dominio.md)). Cobertura eSocial em [ADR-RH-006](adrs/ADR-RH-006-cobertura-esocial-dados-sensiveis-saude.md).

---

## 3. Aba 2 — Documentos pessoais

Campos de `funcionarios` que identificam a pessoa (CPF/RG/PIS). Documentos digitalizados com arquivo ficam na aba 7.

| Campo (`funcionarios`) | Componente                                      | Validação (resumo)                                                                    |
| ---------------------- | ----------------------------------------------- | ------------------------------------------------------------------------------------- |
| `cpf`                  | `x-shared.cpf-input` (máscara `999.999.999-99`) | `required`, `HT2ML\Core\Rules\Cpf`, **unique `(empresa_id, cpf)`** · **PII**                 |
| `rg`                   | `x-shared.input`                                | `nullable`, `max:20` · **PII**                                                        |
| `rg_orgao_emissor`     | `x-shared.input`                                | `nullable`, `max:20`                                                                  |
| `rg_uf`                | `x-shared.select-search` (UFs de `estados`)     | `nullable`, `size:2`                                                                  |
| `pis_pasep`            | `x-shared.input` (máscara `999.99999.99-9`)     | `nullable`, dígito verificador (regra `PisPasep`) · **PII eSocial**                   |
| `matricula`            | `x-shared.input`                                | `required`, `max:30`, **unique `(empresa_id, matricula)`** (auto-sugerida — ver §3.1) |

- **CPF** reutiliza a `Rule` do core `HT2ML\Core\Rules\Cpf` (valida 11 dígitos, rejeita sequências repetidas e confere os 2 dígitos verificadores). A máscara é só visual; a Rule normaliza para dígitos antes de validar, e a coluna grava 11 dígitos.
- **PIS/PASEP** ganha uma `Rule` nova no pacote (`HT2ML\Rh\Rules\PisPasep`): 11 dígitos + cálculo do dígito verificador (pesos 3,2,9,8,7,6,5,4,3,2). Máscara `999.99999.99-9`; grava só dígitos.
- `pis_pasep` e `raca_cor`/`escolaridade`/nacionalidade (aba 1) são os campos que tornam o cadastro **eSocial-ready** — sinalizados na UI com um selo discreto "eSocial" no rótulo (`x-shared.tooltip`), nunca como `<select>` ou texto livre.

### 3.1 Geração da matrícula

A `matricula` é **auto-sugerida no `mount()` de criação** e editável (override manual). A regra:

- **Sequencial por empresa.** A sugestão é o próximo número da empresa ativa — `max(matricula numérica) + 1` sobre `funcionarios` **incluindo a lixeira** (`withTrashed()`, escopo `empresa_id`), ou um contador dedicado; lacunas são aceitas (cadastros excluídos/editados não são reaproveitados).
- **Formato configurável (zero-pad).** `config('rh.matricula.zero_pad')` (default 6 → `000123`) e `config('rh.matricula.prefixo')` (default vazio) montam a string final. O cliente ajusta sem código.
- **Única por empresa.** `Rule::unique('funcionarios','matricula')->where('empresa_id', …)->ignore($id)` + índice único parcial `(empresa_id, matricula) WHERE deleted_at IS NULL` ([01 §3 B1](01-modelo-de-dominio.md)) — matrícula na lixeira não bloqueia novo cadastro.
- **Override manual.** O RH pode digitar uma matrícula própria (ex.: padrão legado); a validação de unicidade vale igual. A sugestão é só conveniência, resolvida por um helper `SugerirMatricula` (em `src/Support`), nunca no banco como `default`.

---

## 4. Aba 3 — Contato e Endereço (linhas repetíveis)

Duas coleções 1:N independentes, ambas com UI de **linhas repetíveis** (adicionar/remover) e flag `principal`. Em Livewire, são arrays de arrays (`public array $contatos = []`, `public array $enderecos = []`) com métodos `adicionarContato()/removerContato($i)` e equivalentes para endereço; cada remoção marca o item para soft-delete na Action (linhas existentes carregam `id`).

### 4.1 Contatos — `funcionario_contatos` (01 §3 B2)

| Campo          | Componente                                                   | Validação                                  |
| -------------- | ------------------------------------------------------------ | ------------------------------------------ |
| `tipo_contato` | `x-shared.select-search` (`TipoContato`: email/telefone)     | `required`, `Rule::enum`                   |
| `subtipo`      | `x-shared.select-search` (`TipoTelefone`)                    | `required_if` tipo=telefone                |
| `valor`        | `x-shared.input` (email) / `x-shared.phone-input` (telefone) | email → `email`; telefone → dígitos        |
| `whatsapp`     | `x-shared.toggle`                                            | `boolean` (só telefone)                    |
| `principal`    | `x-shared.toggle` (rádio lógico por tipo)                    | **no máx. 1 principal por `tipo_contato`** |
| `observacao`   | `x-shared.input`                                             | `nullable`, `max:120`                      |

### 4.2 Endereços — `funcionario_enderecos` (01 §3 B3)

| Campo                               | Componente                                                         | Validação                                            |
| ----------------------------------- | ------------------------------------------------------------------ | ---------------------------------------------------- |
| `tipo_endereco`                     | `x-shared.select-search` (`TipoEndereco`)                          | `required`, `Rule::enum`                             |
| `cep`                               | `x-shared.cep-input` (autocompleta logradouro/bairro/município/UF) | `nullable`, 8 dígitos                                |
| `tipo_logradouro_id`                | `x-shared.select-search` (`tipos_logradouro`)                      | `nullable`, `exists`                                 |
| `logradouro`                        | `x-shared.input`                                                   | `required`, `max:150`                                |
| `numero` / `complemento` / `bairro` | `x-shared.input`                                                   | `nullable`                                           |
| `municipio_id`                      | `x-shared.select-search` (`municipios`)                            | `nullable`, `exists`; preenche `uf` (desnormalizada) |
| `pais_id`                           | `x-shared.select-search` (`paises`, default Brasil)                | `nullable`, `exists`                                 |
| `principal`                         | `x-shared.toggle`                                                  | **no máx. 1 principal** na coleção                   |

**Regra do "principal" (contatos e endereços):** a UI trata `principal` como rádio lógico — marcar um desmarca os outros do mesmo escopo (por `tipo_contato`, no caso de contatos; global, no de endereços). A validação garante **exatamente um** principal quando há ≥1 linha, alinhada aos índices únicos parciais de 01 (`... WHERE principal = true AND deleted_at IS NULL`). Se o usuário não marcar nenhum, a Action promove o primeiro a principal.

---

## 5. Aba 4 — Bancário + PIX

Coleção 1:N (`funcionario_dados_bancarios`, 01 §3 B4) com `principal` (default a primeira conta). **PII financeira** — fora de auditoria e candidata a `encrypted` (01 §8).

| Campo                        | Componente                                                  | Validação                                             |
| ---------------------------- | ----------------------------------------------------------- | ----------------------------------------------------- |
| `banco_id`                   | `x-shared.select-search` (catálogo `bancos`)                | `nullable`, `exists:bancos,id`                        |
| `agencia` / `agencia_digito` | `x-shared.input`                                            | `nullable`, dígitos                                   |
| `conta` / `conta_digito`     | `x-shared.input`                                            | `nullable`, dígitos · **PII**                         |
| `tipo_conta`                 | `x-shared.select-search` (`TipoContaBancaria`)              | `required`, `Rule::enum`                              |
| `titularidade`               | `x-shared.select-search` (`Titularidade`: própria/terceiro) | `nullable`, `Rule::enum`                              |
| `pix_tipo`                   | `x-shared.select-search` (`TipoChavePix`)                   | `nullable`, `Rule::enum`                              |
| `pix_chave`                  | `x-shared.input` (máscara muda conforme `pix_tipo`)         | `nullable` · **validada por tipo** (abaixo) · **PII** |
| `principal`                  | `x-shared.toggle`                                           | no máx. 1 principal                                   |

**Validação da chave PIX por `TipoChavePix`** — delegada ao método `TipoChavePix::validaFormato(string $chave): bool` do enum (01 §4) e exposta como `Rule` condicional no `FormRequest`:

| `pix_tipo`  | Formato esperado                               |
| ----------- | ---------------------------------------------- |
| `cpf`       | 11 dígitos + DV válido (reusa `HT2ML\Core\Rules\Cpf`) |
| `cnpj`      | 14 dígitos + DV válido                         |
| `email`     | `email:rfc`                                    |
| `celular`   | `+55` + DDD + 9 dígitos (E.164)                |
| `aleatoria` | UUID v4 (36 chars)                             |

Quando `pix_tipo = cpf`, a UI pode pré-preencher com o CPF do funcionário (titularidade própria), mas a chave é validada independentemente.

---

## 6. Aba 5 — Dependentes

Coleção 1:N (`funcionario_dependentes`, 01 §3 B5). Flags governam folha/IR (fundação).

| Campo                        | Componente                                  | Validação                                                          |
| ---------------------------- | ------------------------------------------- | ------------------------------------------------------------------ |
| `nome`                       | `x-shared.input`                            | `required`, `max:150`                                              |
| `grau_parentesco`            | `x-shared.select-search` (`GrauParentesco`) | `required`, `Rule::enum`                                           |
| `cpf`                        | `x-shared.cpf-input`                        | `nullable`, `HT2ML\Core\Rules\Cpf` · **PII**                              |
| `data_nascimento`            | `x-shared.date-picker`                      | `nullable`, `date`, `before_or_equal:today`                        |
| `sexo`                       | `x-shared.select-search` (`Sexo`)           | `nullable`, `Rule::enum`                                           |
| `dependente_ir`              | `x-shared.toggle`                           | `boolean` (pré-marcado se `GrauParentesco::eDependenteIrPadrao()`) |
| `dependente_salario_familia` | `x-shared.toggle`                           | `boolean`                                                          |
| `dependente_plano_saude`     | `x-shared.toggle`                           | `boolean`                                                          |

`GrauParentesco::eDependenteIrPadrao()` (01 §4) liga `dependente_ir` por default para cônjuge/filho/etc.; o usuário pode desmarcar. As três flags são apenas marcadores aqui — a apuração que as consome é fundação de folha ([01 §3 Bloco D / 07]).

---

## 7. Aba 6 — Contratação

Campos de `funcionarios`, bloco _Contratação_ (01 §3 B1). Mudanças posteriores a esses campos (salário, cargo, departamento, demissão) **devem** passar pela linha do tempo ([06](06-linha-do-tempo.md)); na **criação** o `FormFuncionario` define os valores iniciais e a Action grava o evento `admissao`.

| Campo (`funcionarios`)  | Componente                                     | Validação                                              |
| ----------------------- | ---------------------------------------------- | ------------------------------------------------------ |
| `data_admissao`         | `x-shared.date-picker`                         | `required`, `date`                                     |
| `data_demissao`         | `x-shared.date-picker`                         | `nullable`, `date`, **`after_or_equal:data_admissao`** |
| `tipo_vinculo`          | `x-shared.select-search` (`TipoVinculo`)       | `required`, `Rule::enum`                               |
| `regime_trabalho`       | `x-shared.select-search` (`RegimeTrabalho`)    | `required`, `Rule::enum`                               |
| `salario_tipo`          | `x-shared.select-search` (mensal/horista)      | `required`, default `mensal`                           |
| `salario_base_centavos` | `x-shared.money-input` (centavos)              | `nullable`, `integer`, `min:0`                         |
| `status`                | `x-shared.select-search` (`StatusFuncionario`) | `required`, `Rule::enum`, default `ativo`              |

- **Dinheiro em centavos:** `salario_base_centavos` usa `x-shared.money-input` (Alpine entangle: exibe `R$ 0,00`, grava `int` centavos — nunca `float`), coerente com a coluna `INTEGER` de 01 §3 e o ADR-0014.
- **Coerência de datas:** `data_demissao >= data_admissao` (validação + CHECK no banco). Datas de nascimento (abas 1/5) não podem ser futuras.
- `status` e `data_demissao` interagem (§9): demitir (`data_demissao` preenchida) deve levar `status` a `desligado`; a Action concilia.

---

## 8. Aba 7 — Documentos / Anexos

Metadados em `funcionario_documentos` (01 §3 B6); o **binário reaproveita o core**: `HT2ML\Core\Models\Anexo` (MorphTo polimórfico) gerenciado pelo `HT2ML\Core\Livewire\Admin\Shared\GerenciadorAnexos`. Cada linha de documento referencia o tipo no **catálogo tenant `tipos_documento`** ([04](04-catalogos-configuraveis.md)) e, opcionalmente, um `anexo_id`.

### 8.1 Campos por linha de documento

| Campo (`funcionario_documentos`) | Componente                                                     | Validação                                                 |
| -------------------------------- | -------------------------------------------------------------- | --------------------------------------------------------- |
| `tipo_documento_id`              | `x-shared.select-search` (`tipos_documento` ativos da empresa) | `required`, `exists` por empresa                          |
| `numero`                         | `x-shared.input`                                               | `required_if` flag `exige_numero` · **PII**               |
| `orgao_emissor`                  | `x-shared.input`                                               | `required_if` flag `exige_orgao_emissor`                  |
| `uf_emissor`                     | `x-shared.select-search` (UFs)                                 | `nullable`, `size:2`                                      |
| `data_emissao`                   | `x-shared.date-picker`                                         | `nullable`, `date`                                        |
| `data_validade`                  | `x-shared.date-picker`                                         | `required_if` flag `exige_validade`; `after:data_emissao` |
| `observacao`                     | `x-shared.textarea`                                            | `nullable`                                                |
| `anexo_id` (arquivo)             | `x-shared.file-upload` → `GerenciadorAnexos`                   | `required_if` flag `exige_arquivo`                        |

### 8.2 Regras dirigidas pelas flags do tipo

As flags do `tipos_documento` (01 §3 A4: `exige_numero`, `exige_validade`, `exige_orgao_emissor`, `exige_arquivo`) tornam os campos **condicionalmente obrigatórios**. Ao escolher o `tipo_documento_id`, o `FormFuncionario` lê as flags e ajusta a UI (mostra/oculta `required`) e a validação (`required_if`/`Rule::requiredIf(...)`). Ex.: CNH (`exige_validade`) exige `data_validade`; Comprovante de Residência (`exige_arquivo`) exige upload.

### 8.3 Upload seguro (disco **privado**) reaproveitando o core

- O binário entra como `HT2ML\Core\Models\Anexo` com `anexavel_type = HT2ML\Rh\Models\Funcionario` (morph map) e `anexavel_id = funcionario.id`; `funcionario_documentos.anexo_id` aponta para ele (FK `nullOnDelete`).
- O `GerenciadorAnexos` do core hoje grava no **disco `public`** com caminho fixo (`store('anexos','public')`) e monta a lista chamando `Anexo::url()`. Documentos de RH são **sensíveis** → disco **privado**. Como o driver `local`/privado **não** serve `url()` pública, a abordagem fiel ao [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md) é um **componente próprio do pacote** (`GerenciadorAnexosRh`) que **reusa o _model_ `Anexo`** com `disco='rh_privado'` e caminho `rh/{empresa_id}/...` (§8.3 endurecimento), **sem editar** o componente do core. **Acesso sempre por rota de download assinada autorizada por Policy** (`Storage::disk('rh_privado')->download(...)`), **nunca** `Anexo::url()` nem link público. _Alternativa:_ tornar o `GerenciadorAnexos` do core parametrizável (disco + caminho + geração de URL) como mudança **aditiva aprovada** — mais invasiva ([ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)).
- Ciclo de vida do arquivo segue o core: soft-delete do `Anexo` mantém o binário (retenção/auditoria); o arquivo físico só some no force-delete (evento `forceDeleted`). Combina com a guarda legal trabalhista (01 §8).

> **Endurecimento (estratégia — [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)).** O disco dedicado é o **`rh_privado`** (`storage/app/private/rh`, fora do webroot), com layout `rh/{empresa_id}/funcionarios/{funcionario_id}/{tipo_documento_codigo}/{ulid_ou_hash}.{ext}` — nome físico **não-adivinhável**, nome original em `Anexo.nome_original`. O **download é sempre por controller autorizado por Policy** (ACL hierárquica [05](05-organograma-acl-hierarquica.md): quem vê o funcionário vê os documentos; sensíveis exigem permissão dedicada) **+ URL assinada temporária** — nunca link público nem disco `public`. Operações (upload/substituição/exclusão) são auditadas via `Auditavel`; o **acesso a documento sensível** também é logado. **Substituição = versionamento** (novo `Anexo` + soft-delete do anterior); **retenção trabalhista longa** (não expurgar). Cifra de binário/checksum são evolução. O RH usa um **componente próprio** (`GerenciadorAnexosRh`) que reusa o _model_ `Anexo` (disco + caminho + download assinado) — **sem editar** o componente do core; parametrizar o `GerenciadorAnexos` do core (disco + caminho + URL) é alternativa aditiva. Ver [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md).

### 8.4 Relatório/alerta de "documentos a vencer" (mini-spec)

`funcionario_documentos.data_validade` tem índice dedicado (01 §3 B6 / §7) justamente para este relatório. Spec da Fase 1:

- **Janela configurável (dias).** `config('rh.documentos.janela_vencimento_dias')` (default **30**) define o horizonte de "a vencer"; o cliente ajusta sem código. Um documento entra no relatório quando `data_validade <= hoje + janela` (inclui os **já vencidos**, `data_validade < hoje`).
- **Consulta tenant-scoped** por `(empresa_id, data_validade)` (índice de §7), só sobre tipos com `exige_validade` e `data_validade IS NOT NULL`, ordenada por `data_validade` asc (mais urgente no topo).
- **KPI no dashboard de RH** — card `x-admin.kpi-card` com a contagem de documentos a vencer/vencidos da empresa ativa (atalho para a listagem filtrada).
- **Filtro na tabela** — `FuncionarioTable`/uma tabela de documentos ganha um filtro "Vencimento" (a vencer / vencidos / em dia) via `x-shared.select-search`.
- **Badges** — na linha do documento (no form e na listagem): `x-shared.badge` **âmbar** = a vencer (dentro da janela), **vermelho** = vencido. Sem CSS custom (só Tailwind, CLAUDE §9).

### 8.5 Envio de documentos em lote (multi-upload + ZIP)

Além do upload individual (§8.1), a aba Documentos aceita **vários arquivos de uma vez** e um **`.zip`** — reaproveitando o `Anexo`/`GerenciadorAnexos` no disco `rh_privado` (§8.3):

- **Multi-upload** — o Dropzone do core recebe N arquivos numa seleção; cada arquivo vira um `Anexo` + uma linha `funcionario_documentos` (tipo resolvido por §8.6 ou pendente de classificação).
- **ZIP** — o upload de um `.zip` é **extraído no servidor por um job** (fila), que processa cada arquivo interno como no multi-upload. Cobre a "pasta de documentos" que o cliente já tem do colaborador, sem N requisições.
- **Incluir vs substituir** — se já existe documento do mesmo tipo, política configurável: **substituir** (nova versão — novo `Anexo` + soft-delete do anterior, §8.3) ou **adicionar** (mantém ambos). Default: adicionar.
- **Resultado ao usuário** — relatório do lote: **classificados automaticamente** (tipo detectado §8.6), **pendentes** (bandeja de não-classificados §8.6) e **erros** (arquivo corrompido/não suportado). O lote **não falha** por um arquivo fora do padrão.

### 8.6 Detecção de tipo por padrão no nome (tag)

Para classificar automaticamente os arquivos de um lote/ZIP, o nome do arquivo carrega uma **tag/prefixo** mapeada a um `tipos_documento` (por `codigo` — [04 §4](04-catalogos-configuraveis.md)):

- **Convenção configurável** — `config('rh.documentos.tags')` (ou aliases por tipo) mapeia prefixos a códigos: `documento-cpf` → `cpf`, `documento-rg` → `rg`, `comprovante-endereco` → `comprovante_residencia`, … O cliente ajusta sem código.
- **Normalização** — o nome é normalizado antes do match: minúsculas, sem acento, separadores (`-`/`_`/espaço) unificados. `RG_João.pdf` e `documento-rg.png` caem no mesmo tipo `rg`.
- **Fora do padrão → bandeja de não-classificados** — arquivos sem tag reconhecível **não falham** o lote: vão para uma **bandeja** onde o RH associa o tipo manualmente (linha `funcionario_documentos` com `tipo_documento_id` pendente).
- **Complementar à importação** — a planilha de [11](11-importacao-exportacao.md) traz **metadados** de documento; o envio em lote/ZIP traz **binários**. A planilha não carrega arquivo; o lote/ZIP não cria o cadastro da pessoa.

---

## 9. Status e ciclo de vida

`StatusFuncionario` (01 §4): `ativo`, `experiencia`, `afastado`, `ferias`, `desligado`. Exibido como badge (`StatusFuncionario::variant()`), filtrável no `FuncionarioTable`.

- **Admissão:** criar o funcionário grava, na mesma transação, o evento `admissao` em `funcionario_eventos` ([06](06-linha-do-tempo.md)); `status` inicial vem do formulário (`ativo`/`experiencia`).
- **Demissão:** preencher `data_demissao` → a Action leva `status` a `desligado` e grava o evento `desligamento`. Demissão e admissão **disparam eventos de domínio** (consumidos por listeners — provisionamento/revogação de acesso, e-mails) detalhados em [06](06-linha-do-tempo.md). O `FormFuncionario` não muda salário/cargo/departamento "à revelia": alterações desses campos após a criação são modeladas como eventos funcionais (06), não como simples `update` da linha.
- **Afastado/Férias:** derivados de `funcionario_afastamentos` (01 §3 C2); o status é conciliado pela Action de afastamento, não digitado solto nesta tela.

---

## 10. LGPD na UI

Reforça 01 §8 e a regra do core "dados sensíveis nunca em logs":

- **PII mascarada na exibição** (listagens, leitura): CPF como `***.456.789-**`, conta bancária e chave PIX parcialmente ocultas; valor completo só no formulário de edição autorizado. As mesmas colunas estão em `atributosNaoAuditados()` (01 §3) — não vazam para o diff de auditoria.
- **Foto** em **disco privado** com **URL assinada** (`x-shared.avatar-cropper`/`x-shared.avatar` consumindo a URL temporária), nunca `public`.
- **Documentos/anexos** em disco privado, download por controller autorizado (policy) — §8.3.
- **Dado de saúde** (`cid` de afastamento) não aparece nesta tela; tem permissão dedicada e `encrypted` (01 §8 / doc 06).
- Antes de anonimizar um desligado, a Action chama `disableLogging()` (01 §8) e mascara a PII mantendo o esqueleto para obrigações legais.

---

## 11. Self-service vs RH (alinhado à ACL do doc 05)

O vínculo `funcionarios.admin_user_id` (01 §3 Bloco E) liga o funcionário ao seu `AdminUser`. O **Colaborador** acessa o próprio cadastro (escopo "eu"); o **RH** acessa todos da empresa. A matriz de permissões/escopo é definida em [05](05-organograma-acl-hierarquica.md); abaixo, o recorte por campo desta tela:

| Bloco / campo                                                     | Colaborador (próprio cadastro)                                              | Só RH/Gestor                                                          |
| ----------------------------------------------------------------- | --------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| Foto, nome social                                                 | **Vê e edita**                                                              | —                                                                     |
| Contatos (telefone/e-mail)                                        | **Vê e edita**                                                              | —                                                                     |
| Endereços                                                         | **Vê e edita**                                                              | —                                                                     |
| Dados bancários + PIX                                             | **Vê e edita** (próprios)                                                   | RH pode editar                                                        |
| Dependentes                                                       | **Vê e edita**                                                              | RH valida flags IR/sal-família                                        |
| Documentos pessoais (anexar RG/CPF/CNH/comprovantes)              | **Vê e anexa**                                                              | RH valida/aprova                                                      |
| Nome civil, CPF, PIS/PASEP, `matricula`                           | **Só leitura**                                                              | **Só RH edita**                                                       |
| Cargo, departamento, gestor, filial (lotação)                     | **Só leitura**                                                              | **Só RH** (via evento — 06)                                           |
| Contratação (vínculo, regime, salário, admissão/demissão, status) | **Só leitura**                                                              | **Só RH** (salário/cargo via evento — 06)                             |
| `cid` / afastamentos                                              | Não vê detalhe sensível                                                     | RH com permissão dedicada                                             |
| Atestados (enviar pelo portal / acompanhar)                       | **Envia e acompanha** ([12](12-ausencias-faltas-atestados-afastamentos.md)) | RH analisa/aprova                                                     |
| Faltas / afastamentos (consulta)                                  | **Só leitura** (próprios)                                                   | RH/gestor lança ([12](12-ausencias-faltas-atestados-afastamentos.md)) |
| Campos personalizados                                             | Conforme definição (RH edita)                                               | **RH** ([10](10-campos-personalizados.md))                            |

> **Atestado e portal:** o **envio de atestado** pelo colaborador e a **consulta** de faltas/afastamentos são recursos do portal — máquina de estados, abono e ACL em [12](12-ausencias-faltas-atestados-afastamentos.md); a tabela recursos × fase do portal está em [05 §9](05-organograma-acl-hierarquica.md). Atestado **não** é campo do `FormFuncionario` (é entidade própria, [01 §C3](01-modelo-de-dominio.md)).

> Implementação: o `FormFuncionario` recebe um **modo** (`proprio` vs `rh`) derivado da policy/escopo (05); no modo `proprio`, abas/campos fora do recorte do colaborador renderizam **somente leitura** (`readonly`/desabilitado) e a validação/Action ignoram qualquer tentativa de alterar campos vedados (defesa no servidor, não só na UI). As mudanças sujeitas a evento (salário/cargo/departamento/demissão) **nunca** são editáveis pelo colaborador.

### 11.1 Mecânica do modo `proprio` vs `rh` (defesa em três camadas)

1. **Derivação do `$modo` (no `mount()`).** `$modo = 'proprio'` quando o registro editado é o **próprio** funcionário do `AdminUser` logado — resolvido por `FuncionarioAtual` ([05 §2.3](05-organograma-acl-hierarquica.md)): `$ehProprio = $funcionario?->id === app(FuncionarioAtual::class)->resolver()?->id`. Caso contrário (RH/gestor sobre outro registro, autorizado pela policy + subárvore), `$modo = 'rh'`. Super-admin e quem tem `rh.funcionarios.editar` sobre o alvo operam em `rh`.
2. **Renderização condicional (UI).** Um helper `campoEditavel(string $campo): bool` mapeia `($modo, $campo)` segundo a matriz acima; a view passa `:readonly="! $this->campoEditavel('salario_base_centavos')"` (e oculta seções inteiras, como **PCD** §2.1 e `cid`, fora do recorte). A UI desabilitada é **conveniência**.
3. **Defesa no servidor (a barreira real).** `FuncionarioRules::regras()` recebe o `$modo` e **remove do conjunto validado** os campos vedados no modo `proprio`; a `Create/UpdateFuncionarioAction` aplica uma **allowlist por modo** (só persiste os campos que aquele modo pode tocar), de forma que uma request forjada **não** consiga alterar cargo/salário/status/PCD. Campos sujeitos a evento (salário/cargo/departamento/filial/demissão) **nunca** entram na allowlist do `proprio` — passam pela linha do tempo ([06](06-linha-do-tempo.md)), nunca por `update` direto.

> Em uma frase: a **ACL de subárvore** ([05](05-organograma-acl-hierarquica.md)) decide **quais registros** (o colaborador só o dele); o **`$modo`** decide **quais campos** dentro do registro; e a **allowlist da Action** garante que a decisão valha **no servidor**.

### 11.2 Provisionamento de acesso do colaborador (como o funcionário ganha um `AdminUser`)

O self-service (§11, [05 §9](05-organograma-acl-hierarquica.md)) depende do vínculo `funcionarios.admin_user_id` ([01 §3 Bloco E](01-modelo-de-dominio.md)) — mas a maioria dos funcionários nasce **sem** login (`admin_user_id = NULL`). O fluxo **mínimo da Fase 1** para conceder acesso é uma ação **do RH** (nunca auto-serviço), com dois caminhos:

- **(a) Vincular a um `AdminUser` existente** — `x-shared.select-search` lista os `admin_users` da empresa **ainda sem vínculo** (respeita `UNIQUE (empresa_id, admin_user_id)`); grava `funcionarios.admin_user_id`.
- **(b) Criar e convidar** — cria um `AdminUser` com nome/e-mail do funcionário **sem senha**, dispara o **e-mail de definição de senha/convite** (reusa o fluxo de convite do core), atribui o papel padrão de colaborador (por empresa) e grava o vínculo. Job na fila `emails`.

**Listeners de admissão/desligamento** (eventos de domínio da linha do tempo — [06 §4.1](06-linha-do-tempo.md)): o evento `admissao` **pode** disparar o provisionamento (caminho b) quando a empresa configurar "criar acesso na admissão"; o `desligamento` **revoga** o acesso (desativa o `AdminUser` e/ou remove os papéis por empresa), preservando o vínculo histórico. Os listeners são **idempotentes** ([06 §4.1](06-linha-do-tempo.md)). A automação total (provisionar sempre na admissão) é evolução; a Fase 1 entrega a **ação manual "Conceder acesso"** + a **revogação no desligamento**. Detalhe da mecânica do vínculo e do fail-closed em [05 §2](05-organograma-acl-hierarquica.md).

---

## 12. Fluxo técnico (Livewire → Rules → DTO → Action)

Segue o padrão do core (referência viva: `App\Livewire\Admin\Exemplos\FormExemplo` + `App\Http\Requests\Admin\ExemploRules`).

```
FormFuncionario (mount / salvar)
  └─ mount(?int $funcionario):
        carrega $cargosDisponiveis = Cargo::orderBy('nome')->pluck('nome','id')   ← FuncionarioCargoTest
        carrega departamentos/funcoes/bancos/paises/municipios/filiais/tiposDocumento (tenant-scoped)
        se editando: hidrata props (incl. coleções contatos/enderecos/bancarios/dependentes/documentos)
        authorize('create'|'update', ...)  (policy do pacote)
  └─ salvar():
        $dados = $this->validate(FuncionarioRules::regras($this->funcionarioId), attributes: $this->validationAttributes());
        $dto   = FuncionarioDTO::fromArray($dados);   // readonly DTO, inclui DTOs-filhos das coleções
        $funcionarioId === null
            ? $criar->execute($dto)                    // CreateFuncionarioAction
            : $atualizar->execute(Funcionario::findOrFail($funcionarioId), $dto);  // UpdateFuncionarioAction
        $this->notificarAposRedirect('success', 'Funcionário ... com sucesso.');
        $this->redirect(route('rh.funcionarios.index'), navigate: true);
```

### Peças

- **`FuncionarioRules`** (classe estática, `HT2ML\Rh\Http\Requests\FuncionarioRules::regras(?int $ignorarId)`) — única fonte de validação, consumida tanto pelo `FormFuncionario` quanto por `Store/UpdateFuncionarioRequest` (API-ready). Inclui:
    - `cpf` → `HT2ML\Core\Rules\Cpf` + `Rule::unique('funcionarios','cpf')->where('empresa_id', app(TenantContext::class)->empresaAtivaId())->ignore($ignorarId)` (**unique por empresa**; índice parcial `WHERE deleted_at IS NULL` no banco libera CPF da lixeira).
    - `matricula` → `Rule::unique('funcionarios','matricula')->where('empresa_id', ...)->ignore($ignorarId)`.
    - enums via `Rule::enum(...)`; `data_demissao` → `after_or_equal:data_admissao`; coleções via `array` + regras por item (contatos/enderecos/bancarios/dependentes/documentos), incl. PIX por `pix_tipo` e campos de documento por flags do tipo.
- **`FuncionarioDTO`** (`readonly`) — transporta o agregado entre camadas (nunca array solto): escalares de `funcionarios` + listas de DTOs-filhos (`ContatoDTO`, `EnderecoDTO`, `DadosBancariosDTO`, `DependenteDTO`, `DocumentoDTO`). `fromArray()` monta tudo a partir dos dados validados.
- **`CreateFuncionarioAction` / `UpdateFuncionarioAction`** (`execute()`) — orquestram numa **transação**: persistem `funcionarios`; sincronizam as coleções filhas (insert/update/soft-delete por `id`); resolvem `cargo_nivel` a partir do `cargo_id`; garantem **exatamente 1 principal** por coleção (promovendo o primeiro se preciso); na criação gravam o evento `admissao`, na demissão o `desligamento`, disparando os eventos de domínio (06). Services/Actions **não** recebem `Request` nem retornam view/redirect (CLAUDE §5.6).
- **Anexos** — fora do DTO transacional do save: gerenciados pelo `GerenciadorAnexos` (disco privado, §8.3), que cria/remove `Anexo` por conta própria; `funcionario_documentos.anexo_id` é vinculado quando o upload conclui.

### Teste de intenção que trava a fronteira do cargo

`tests/Feature/Rh/FuncionarioCargoTest.php` monta `FormFuncionario` como super-admin com `CargoSeeder` + `RolePermissionSeeder` semeados e a empresa ativa, e afirma:

```php
expect($component->instance()->cargosDisponiveis)->toHaveKey('Administrador');
```

Ou seja: **cargo é catálogo** (`cargosDisponiveis` populado pelo `CargoSeeder`), nunca enum — esta é a verdade que a implementação do `FormFuncionario` precisa honrar.

---

## 13. Checklist de implementação (resumo)

- [ ] `FormFuncionario` com 7 abas fixas `x-shared.tab-*` server-driven (`abaAtiva` + `abaTemErro()`) + aba **Personalizados** condicional (§1), `cargosDisponiveis` no `mount()`.
- [ ] `IndexFuncionario` + `FuncionarioTable` (PowerGrid): filtros por `status`/departamento/cargo, PII mascarada, ação de lixeira (`ComLixeira`).
- [ ] `FuncionarioRules` + `Store/UpdateFuncionarioRequest`; `Rule` nova `PisPasep`; validação PIX por tipo; unique por empresa (cpf, matricula); matrícula auto-sugerida (§3.1, helper `SugerirMatricula` + config de zero-pad/prefixo).
- [ ] Seção **PCD** (§2.1): toggles + `observacao_pcd`, em `atributosNaoAuditados()`, ocultos sem `rh.funcionarios.ver_dados_sensiveis`, ignorados pela Action sem a permissão.
- [ ] `FuncionarioDTO` + DTOs-filhos `readonly`; `CreateFuncionarioAction`/`UpdateFuncionarioAction` (transação, sync de coleções, 1-principal, `cargo_nivel`, eventos admissão/desligamento; **allowlist de campos por `$modo`** — §11.1).
- [ ] `GerenciadorAnexos` parametrizado com disco privado; controller de download por URL assinada + policy.
- [ ] Modo `proprio` vs `rh` (self-service) com campos vedados em só-leitura e **defesa no servidor** (Rules + allowlist da Action, §11.1; alinhado a 05).
- [ ] **Provisionamento de acesso** (§11.2): ação "Conceder acesso" (vincular existente / criar+convidar) + revogação no desligamento (listeners idempotentes — [06](06-linha-do-tempo.md)).
- [ ] Relatório/alerta "documentos a vencer" (§8.4): janela configurável, KPI, filtro na tabela, badges âmbar/vermelho, sobre `(empresa_id, data_validade)`.
- [ ] Aba **Personalizados** (§1): embute o componente genérico de [10](10-campos-personalizados.md); `FuncionarioRules` funde `regrasPersonalizadas()`; persistência em `dados_personalizados` (allowlist por modo §11.1).
- [ ] Documentos em lote/ZIP (§8.5) + detecção por tag (§8.6, `config('rh.documentos.tags')`); bandeja de não-classificados; storage endurecido (§8.3 / [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md)).
- [ ] Pós-tarefa: `pint`, `prettier` nas views `rh::`, `phpstan`, `php artisan test` (inclui `FuncionarioCargoTest`).
