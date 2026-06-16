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

---

## 3. Enum `TipoCampoPersonalizado` — tipo dirige componente e validação

Backed `string` ([01 §4.2](01-modelo-de-dominio.md)). É a **única fonte** do mapeamento "tipo → componente de UI" e "tipo → regra de validação base" — sem `if` espalhado pela view ou pelas Rules.

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

Métodos do enum (consumidos pela view e pelas Rules):

- `componente(): string` — devolve o nome do componente `x-shared.*` a renderizar (jamais `<select>` nativo — CLAUDE §9/§19).
- `regraValidacao(array $regras, ?array $opcoes): array` — monta o array de regras Laravel (tipo base + `min`/`max`/`regex` de `regras` + `in:` de `opcoes`).
- `aceitaOpcoes(): bool` — `true` para `select`/`multi_select` (a tela de definição só mostra o editor de opções nesses casos).
- `castValor(mixed $bruto): mixed` — normaliza o valor lido do JSONB (ex.: `booleano` → `bool`, `numero` → `int`, `data` → `Carbon`) **para exibição** — só leitura; o JSONB **guarda string ISO** (ex.: `"2026-03-10"`), nunca um `Carbon` serializado.

> Dinheiro é **exceção consciente**: valores monetários personalizados deveriam usar centavos (`INTEGER`, ADR-0014). Na Fase 1, dinheiro **não** é tipo de campo personalizado (evita float em JSONB); se o cliente exigir, entra como tipo `dinheiro` (centavos) numa evolução — registrado em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md).

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

### 4.1 CRUD das definições (Service/Action + tela)

A gestão das **definições** é um CRUD tenant comum, gerado no padrão do módulo ([08](08-arquitetura-tecnica.md)): `IndexCampoPersonalizado` / `FormCampoPersonalizado` / `CampoPersonalizadoTable` (PowerGrid, com `ComLixeira`), `CampoPersonalizadoDTO` readonly, `CampoPersonalizadoRules` e `Create/UpdateCampoPersonalizadoAction`. A tela filtra por **`entidade`** (na Fase 1 só `funcionario`) e valida: `chave` slug único por `(empresa, entidade)`; editor de `opcoes` visível só quando `tipo->aceitaOpcoes()`; `regras` (min/max/regex) por tipo.

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
- **Sem permissão dedicada por campo na Fase 1**: ver/editar os valores segue a permissão da entidade (`rh.funcionarios.editar`/`ver`); uma permissão por-campo (à la `ver_cid`) é evolução. Campos de **saúde** (art. 11) **não** devem ser modelados como campo personalizado livre — usam as estruturas dedicadas (`cid`, PCD); a tela de definição avisa disso.

---

## 7. Reuso em outros módulos (fundação candidata a promoção ao core)

O mecanismo nasce no `modulo-rh`, mas é **agnóstico de domínio**. Adotá-lo em outra entidade (no RH ou num satélite — [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md)) é um passo-a-passo curto:

1. **Coluna**: migration aditiva `add_dados_personalizados_to_<tabela>` (`JSONB NULL`).
2. **Trait**: `use TemCamposPersonalizados` no model + cast `'dados_personalizados' => 'array'`; sobrescrever `entidadePersonalizada()` com o slug da entidade (ex.: `'cliente'`).
3. **Definições**: a tabela `campos_personalizados` já discrimina por `entidade` — a tela de definição (§4.1) filtra pelo novo slug, sem schema novo.
4. **Form + Rules**: embutir o componente genérico (§4.2) e fundir `regrasPersonalizadas()` nas Rules da entidade.

> **Promoção ao core.** Por ser fundação reutilizável (tabela + trait + enum + componente, sem domínio de RH), é **candidata a promoção ao core** — mesma lógica do [ADR-RH-007](adrs/ADR-RH-007-rh-familia-modulos-pacote.md). Enquanto vive no `modulo-rh`, qualquer satélite que precise depende do `modulo-rh`; promovida, vira infra compartilhada. A decisão de quando promover está em [ADR-RH-008](adrs/ADR-RH-008-campos-personalizados.md). Esta suíte **documenta** o mecanismo no RH (decisão confirmada com o cliente), marcando-o como candidato — não força a promoção agora.

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

---

## 9. Permissões

| Permissão ([01 §10](01-modelo-de-dominio.md))                                         | O que cobre                                                                                                                  |
| ------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| `rh.campos_personalizados.{listar,criar,editar,deletar,restaurar,excluir_permanente}` | gestão das **definições** (a tela de §4.1, com lixeira)                                                                      |
| _(valores)_ `rh.funcionarios.{ver,editar}`                                            | ver/editar os **valores** em `dados_personalizados` segue a permissão da **entidade** — não há permissão por-valor na Fase 1 |

A edição de definição é operação de **RH/admin** (estrutura o cadastro de todos); a edição de valor é a mesma do cadastro do funcionário. Tudo conferido no servidor (Policy), nunca só na UI.

---

## 10. Faseamento

- **Fase 1 (incremento de B2 — [02](02-fase-1-blueprint.md)):** tabela `campos_personalizados` + coluna `funcionarios.dados_personalizados` + enum `TipoCampoPersonalizado` + trait `TemCamposPersonalizados` + tela de definições + aba "Personalizados" no `FormFuncionario`, **aplicados ao funcionário**.
- **Pós-Fase 1:** reuso em outras entidades (§7), tipos novos (§8), índice GIN, filtros/relatórios avançados por campo, exposição parcial ao colaborador (self-service), e a eventual **promoção ao core**.

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
