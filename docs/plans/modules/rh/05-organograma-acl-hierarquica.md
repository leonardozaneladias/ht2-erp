# 05 — Organograma e ACL Hierárquica

> Como o módulo de RH decide **quem enxerga quais funcionários**. A resposta não é uma permissão só: é a **interseção de três eixos ortogonais** — tenant (empresa), RBAC (a ação) e organograma (a subárvore de subordinados). Este documento descreve o princípio, o vínculo `Funcionario↔AdminUser`, a hierarquia por `gestor_id`, a subárvore recursiva (WITH RECURSIVE), o empacotamento em trait + serviço, a matriz de casos, os edge cases, o self-service do colaborador e a tela de organograma.
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · `packages/extensao-rh/` · views `rh::` · banco **PostgreSQL 16** · multi-tenant lógico por `empresa_id`. O **schema é definido em [01](01-modelo-de-dominio.md)** (fonte de verdade); aqui só consumimos os nomes de tabelas/colunas/permissões de lá e descrevemos a mecânica de acesso.

Relacionados: [01](01-modelo-de-dominio.md) · [03](03-cadastro-pessoa-documentos.md) · [07](07-jornada-horas-extras-folha.md) · [adrs/ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md)

---

## 1. Princípio: três eixos ortogonais combinados por AND

A visibilidade de um funcionário não é governada por uma única regra. Três perguntas independentes precisam responder "sim" para que uma linha de `funcionarios` apareça para o usuário logado:

```
REGISTROS VISÍVEIS  =  TENANT  AND  RBAC  AND  ORGANOGRAMA
                       (empresa)   (a ação)   (a subárvore)
```

Cada eixo responde a uma pergunta diferente, e os três são **ortogonais** — mexer num não mexe nos outros:

| Eixo            | Pergunta que responde                                          | Mecanismo real (core)                                                                                                      | O que filtra                              |
| --------------- | -------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| **Tenant**      | "Este registro é da empresa em que estou?"                     | Global scope `empresa` do trait `HT2ML\Core\Models\Concerns\BelongsToEmpresa`, alimentado por `HT2ML\Core\Support\Tenancy\TenantContext` | **Quais linhas** (por `empresa_id`)       |
| **RBAC**        | "Eu posso executar **esta ação** (listar/ver/editar/aprovar)?" | `Gate` → `HT2ML\Core\Services\Admin\AccessResolver` (super-admin bypass · deny > grant > role)                                    | **O verbo** (a ação inteira, não a linha) |
| **Organograma** | "Esta pessoa está **na minha subárvore** de subordinados?"     | Trait `HT2ML\Rh\Models\Concerns\VisivelNaHierarquia` + serviço `EscopoOrganograma` (CTE recursiva sobre `gestor_id`)      | **Quais linhas** (por posição na árvore)  |

A distinção mais importante de toda a ACL deste módulo:

> **RBAC decide o _verbo_; Tenant + Organograma decidem _quais linhas_.**
>
> O RBAC não diz "você vê o funcionário 42"; ele diz "você pode **listar** funcionários" (ou não). _Quais_ funcionários a listagem retorna é decidido pelos outros dois eixos — empresa ativa **e** posição no organograma. Um gestor com a permissão `rh.funcionarios.listar` consegue listar; mas a listagem traz só a empresa ativa (tenant) **e** só a sua subárvore (organograma).

### 1.1 A permissão `ver_todos` desliga SÓ o eixo organograma

A permissão `rh.funcionarios.ver_todos` é a chave para a visão ampla do RH. Ela **desliga exclusivamente o eixo organograma** — quem a possui passa a ver todos os funcionários da empresa, sem o recorte de subárvore. Mas:

- **Nunca** desliga o eixo tenant. Mesmo com `ver_todos`, o usuário continua vendo apenas a empresa ativa (ou as empresas que o filtro multi-empresa autorizar — §6). Não existe vazamento cross-empresa por essa via.
- É uma capability separada das ações CRUD: você pode ter `listar` sem `ver_todos` (vê a subárvore) ou `listar` **com** `ver_todos` (vê a empresa toda).

Em termos de teoria de conjuntos, com `V` = conjunto visível, `E` = registros da empresa ativa, `A` = ação permitida (booleano de RBAC), `S` = subárvore do usuário:

```
sem ver_todos:   V = E ∩ S          (e só existe se A = verdadeiro)
com ver_todos:   V = E              (o eixo organograma vira o universo da empresa)
super-admin:     V = todas as empresas autorizadas   (bypass de RBAC e organograma; tenant pelo contexto)
```

Os três eixos são compostos como **AND** justamente para serem seguros por construção: esquecer de aplicar um deles não "abre" os outros, e cada um falha fechado por conta própria.

---

## 2. O vínculo `Funcionario ↔ AdminUser`

O organograma fala de **funcionários** (`funcionarios.gestor_id`); a autenticação fala de **logins** (`admin_users`, guard `admin`). Para que a ACL de subárvore funcione, é preciso responder "**qual funcionário sou eu?**" a partir do usuário logado. Esse é o papel do vínculo.

### 2.1 A FK mora no pacote, o core fica intocado

Conforme [01 §3 Bloco E](01-modelo-de-dominio.md) e [ADR-RH-001](adrs/ADR-RH-001-funcionario-agregado-e-vinculo-adminuser.md), o vínculo é uma coluna em `funcionarios`:

```
funcionarios.admin_user_id   BIGINT  NULL   FK→admin_users  nullOnDelete
UNIQUE (empresa_id, admin_user_id)  WHERE deleted_at IS NULL   -- índice parcial
```

A FK e o índice nascem numa migration **dentro do pacote** (`packages/extensao-rh/database/migrations`), carregada via `loadMigrationsFrom` — o core não recebe nenhuma coluna nova. A relação inversa `AdminUser::funcionario(): HasOne` é declarada num model do pacote (ou via macro/extensão), sem migration no core. Esse é o padrão aditivo do [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md): tudo do RH se acopla por fora.

### 2.2 As duas direções são opcionais (0..1 ↔ 0..1)

O vínculo é **1:1, mas opcional dos dois lados**:

- **Nem todo login é funcionário.** O super-admin, um contador externo, um usuário técnico, um sócio que só consulta relatórios — todos têm `AdminUser` sem `Funcionario` correspondente.
- **Nem todo funcionário tem login.** Um operário de chão de fábrica, um terceirizado, alguém recém-admitido cujo acesso ainda não foi provisionado — existe como `Funcionario`, mas `admin_user_id` é `NULL`. Ele aparece no organograma e nas folhas, mas não loga.

A unicidade `(empresa_id, admin_user_id)` garante que, **dentro de uma empresa**, um login está ligado a no máximo um funcionário. Mas o mesmo `AdminUser` pode ser funcionário em **empresas distintas** (um diretor que é funcionário da Matriz e também da Filial S.A.) — por isso a unicidade é por `empresa_id`, não global.

### 2.3 O serviço `FuncionarioAtual` — "qual funcionário sou eu nesta empresa?"

A resolução é encapsulada no serviço `HT2ML\Rh\Support\Organograma\FuncionarioAtual`. Ele recebe o usuário logado e a empresa ativa e devolve o `Funcionario` correspondente (ou `null`).

Como o mesmo login pode ser funcionário em empresas diferentes, **o cache é por request e chaveado por `(admin_user_id, empresa_id)`** — espelhando a disciplina de cache do `AccessResolver`/`AccessCache` do core, que também memoiza por usuário e, quando preciso, por empresa.

```php
<?php

declare(strict_types=1);

namespace HT2ML\Rh\Support\Organograma;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Rh\Models\Funcionario;
use Illuminate\Support\Facades\Auth;

/**
 * Resolve "qual funcionário é o usuário logado, na empresa ativa".
 * Cache por request chaveado por (admin_user_id, empresa_id): o mesmo login
 * pode ser funcionário em empresas distintas. Espelha a memoização por
 * usuário/empresa do AccessCache do core.
 */
final class FuncionarioAtual
{
    /** @var array<string, Funcionario|null> */
    private array $cache = [];

    public function __construct(private readonly TenantContext $tenant) {}

    /** Resolve a partir do guard admin + empresa ativa. Null se não houver vínculo. */
    public function resolver(): ?Funcionario
    {
        $user = Auth::guard('admin')->user();
        $empresaId = $this->tenant->empresaAtivaId();

        if (! $user instanceof AdminUser || $empresaId === null) {
            return null; // CLI/jobs sem contexto, ou usuário não autenticado.
        }

        $chave = $user->getKey() . ':' . $empresaId;

        return $this->cache[$chave] ??= Funcionario::query()
            // O global scope `empresa` já restringe à empresa ativa; explicitar
            // empresa_id é defesa em profundidade (não custa, e documenta a intenção).
            ->where('empresa_id', $empresaId)
            ->where('admin_user_id', $user->getKey())
            ->first();
    }
}
```

### 2.4 Fail-closed: sem vínculo e sem privilégio → vê ZERO

Esta é a regra de ouro de segurança do módulo:

> Se o usuário **não tem vínculo de funcionário** na empresa ativa **e** não tem `rh.funcionarios.ver_todos` **e** não é super-admin → ele vê **ZERO** funcionários.

Em vez de "sem subárvore conhecida, mostro tudo" (fail-open, perigoso), o serviço de escopo aplica `whereRaw('1=0')` — uma cláusula que nunca casa — e a listagem vem vazia. A ausência de informação nunca amplia o acesso; ela o fecha. Isso impede que um login técnico recém-criado, sem funcionário e sem `ver_todos`, "veja a empresa inteira" por acidente.

---

## 3. A hierarquia: `funcionarios.gestor_id`

A árvore que governa a ACL é a hierarquia **de pessoas**, materializada na self-FK `funcionarios.gestor_id` ([01 §3 B1](01-modelo-de-dominio.md)):

```
funcionarios.gestor_id   BIGINT  NULL   FK→funcionarios (self)   nullOnDelete
CHECK (gestor_id <> id)                 -- anti auto-referência direta
índice (empresa_id, gestor_id)          -- recursão do organograma
```

Características na **Fase 1**:

- **Um gestor direto por pessoa.** `gestor_id` é escalar: cada funcionário aponta para no máximo **um** gestor. Múltiplos gestores (matricial) fica para evolução futura (§12.3).
- **Intra-empresa.** O gestor de um funcionário **tem de ser da mesma empresa**. Isso é validado **na escrita** (a Action que define o gestor rejeita um `gestor_id` de outra empresa) — o banco não tem como impor "mesma empresa" numa FK simples, então a guarda é de aplicação, reforçada pelo fato de o global scope só oferecer candidatos da empresa ativa na UI.
- **`ON DELETE RESTRICT` na prática.** O schema usa `nullOnDelete` físico para não quebrar integridade em force-delete, mas a **regra de negócio é restritiva**: não se desliga/exclui um gestor sem antes **reatribuir seus subordinados** (§7.3). Em outras palavras, a operação de exclusão é bloqueada pela Action enquanto houver subordinados pendurados — comportamento equivalente a `RESTRICT` no nível de domínio.
- **Topo da árvore.** Quem não tem gestor (`gestor_id IS NULL`) é raiz — tipicamente a diretoria. Ver edge cases (§7.1).

### 3.1 Posição no organograma ≠ papel RBAC

Ponto que costuma confundir e merece destaque:

> **"Gestor", "líder", "preposto", "coordenador", "colaborador" são POSIÇÕES no organograma — não são papéis (roles) de RBAC.**

A visibilidade de subárvore **decorre da posição** de uma pessoa na árvore `gestor_id`, não de um papel spatie atribuído ao login. Um "gestor" é simplesmente alguém que **tem subordinados apontando para ele** via `gestor_id`. Se amanhã esse mesmo funcionário deixar de ter subordinados, ele vira folha — sem nenhuma mudança de role. O RBAC (roles/permissions) define **o que a pessoa pode fazer** (listar? aprovar HE? ver CID?); o organograma define **sobre quem**. Os dois se cruzam, mas vivem em planos diferentes:

- As **funções** do RH (líder, preposto, supervisor…) modeladas no catálogo `funcoes` + pivot `funcionario_funcao` ([01 §3 A2/A3](01-modelo-de-dominio.md)) são **vocabulário de negócio/rotulagem**, também ortogonais ao RBAC. Elas descrevem responsabilidades, não concedem acesso por si.
- O acesso é sempre a interseção RBAC × Tenant × Organograma do §1.

### 3.2 Mapa estrutural — as dimensões do organograma (checklist 1.1)

O organograma do cliente não é uma estrutura só: é a composição de **dimensões** que se cruzam sobre a pessoa. A tabela abaixo relaciona cada conceito do briefing à sua âncora no modelo ([01](01-modelo-de-dominio.md)) e diz **se governa a ACL** ([ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md)). A regra de ouro:

> **A espinha do organograma é a hierarquia de PESSOAS (`gestor_id`)** — é ela que governa a ACL (§4). Todo o resto (departamento, centro de custo, cargo, função) é **dimensão organizacional paralela**: descreve/agrupa a pessoa, mas **não** define sozinho quem-vê-quem.

| Dimensão (briefing)              | Âncora no modelo ([01](01-modelo-de-dominio.md))                                                            | Natureza                                                 | Governa ACL?               | Observação                                                                                                                      |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------- | -------------------------------------------------------- | -------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| **Empresa**                      | `funcionarios.empresa_id` (tenant)                                                                          | referência (core)                                        | **Sim** (eixo tenant)      | Isola tudo por `empresa_id`; nunca é desligado (§1.1).                                                                          |
| **Filial / Estabelecimento**     | `funcionarios.filial_id` (nullable)                                                                         | referência (core)                                        | Não                        | Lotação física; entra como **filtro** e como evento de `transferencia_filial` ([06](06-linha-do-tempo.md)).                     |
| **Departamento / Setor**         | `departamentos` + `departamentos.departamento_pai_id` (auto-hierárquico)                                    | catálogo tenant ([04 §2](04-catalogos-configuraveis.md)) | Não (dimensão paralela)    | **Unidade, setor e área são níveis/`tipo` de departamento** — não tabelas novas (ver abaixo). É a "árvore de áreas".            |
| **Centro de custo**              | `centros_custo` + `funcionarios.centro_custo_id` (nullable) — **novo** ([01 §A12](01-modelo-de-dominio.md)) | catálogo tenant opcional                                 | Não                        | Agrupamento gerencial/financeiro; **aditivo** (D1), não bloqueia o B1 mínimo. CRUD em [04 §7.1](04-catalogos-configuraveis.md). |
| **Cargo**                        | `funcionarios.cargo_id` → `cargos` (CBO) + `cargo_nivel` (cache)                                            | referência global (core)                                 | Não                        | `cargo_nivel` é o **organograma de CARGOS** (ranking visual) — distinto do de pessoas (§10).                                    |
| **Função / Extra / Equipe**      | `funcoes` (N:N via `funcionario_funcao`, com vigência)                                                      | catálogo tenant ([04 §3](04-catalogos-configuraveis.md)) | Não                        | **"Equipe" mapeia para função** (líder + membros marcados por `funcao`) — não tabela nova. Rotula responsabilidade.             |
| **Gestor (posição)**             | `funcionarios.gestor_id` (self-FK)                                                                          | espinha do organograma                                   | **Sim** (eixo organograma) | 1 gestor direto/pessoa na Fase 1 (§3). É **a** árvore da ACL (§4).                                                              |
| **Funcionário**                  | `funcionarios` (agregado-raiz)                                                                              | núcleo                                                   | —                          | O nó da árvore; carrega todas as dimensões acima.                                                                               |
| **Subordinação direta/indireta** | recursão sobre `gestor_id` (CTE — §4.2)                                                                     | derivada                                                 | **Sim**                    | Direta = `gestor_id` aponta para mim; indireta = qualquer descendente na CTE.                                                   |

**Unidade / Área / Equipe — como mapeiam (sem entidade nova na Fase 1):**

- **Unidade** e **Área** são **níveis da árvore de `departamentos`** (`departamento_pai_id`): ex. _Unidade Matriz → Área Comercial → Setor Vendas Internas_. Se o cliente quiser rotular o nível, usa-se o `codigo`/`nome` (ou, como evolução, uma coluna `tipo`/`nivel` no departamento). **Não** se criam tabelas `unidades`/`areas` na Fase 1.
- **Equipe** é expressa por **função** (`funcoes` + `funcionario_funcao`): o "líder" tem a função Líder; os "membros da equipe" são os subordinados diretos dele (`gestor_id`) e/ou marcados por uma função comum. **Não** há tabela `equipes` na Fase 1.
- **Promover unidade/área/equipe a entidades próprias** (caso o cliente exija estrutura formal com atributos próprios) é **evolução aditiva** registrada como pendência [PEND-02](13-rastreabilidade-e-pendencias.md) — não bloqueia o início. Glossário em [README](README.md).

> **Por que a base é PESSOAS e não DEPARTAMENTOS** ([ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md)): o cliente pediu "gerente vê líder/preposto/colaboradores **abaixo** (cascata)" — isso é subordinação **de pessoas**, não de áreas. Um gestor pode ter subordinados em departamentos diferentes; um departamento pode ter gente de vários gestores. Ancorar a ACL em `gestor_id` (e não em `departamento_id`) é o que entrega a cascata pedida. O departamento continua sendo dimensão de **lotação/agrupamento** e de relatório, e tem seu próprio responsável (`responsavel_funcionario_id`), mas **não** é o eixo de visibilidade.

---

## 4. A subárvore: WITH RECURSIVE vs Closure Table

Para aplicar o eixo organograma, precisamos do conjunto de IDs da **subárvore** do usuário: ele próprio mais todos os descendentes (subordinados diretos e indiretos, em qualquer profundidade). Há duas técnicas clássicas.

### 4.1 Decisão da Fase 1: WITH RECURSIVE (CTE do Postgres)

**Recomendação: usar uma _Common Table Expression_ recursiva** (`WITH RECURSIVE`) calculada por query, sem nenhuma estrutura auxiliar.

| Critério                         | WITH RECURSIVE (CTE) — **escolhido**                 | Closure Table                                                                |
| -------------------------------- | ---------------------------------------------------- | ---------------------------------------------------------------------------- |
| Schema extra                     | **Zero**                                             | Tabela `funcionario_hierarquia (ancestral_id, descendente_id, profundidade)` |
| Manutenção ao trocar `gestor_id` | **Zero** — a árvore é sempre "ao vivo"               | **Cara e arriscada**: recalcular/reescrever N×M linhas ao mover um ramo      |
| Profundidade                     | **Ilimitada**                                        | Ilimitada                                                                    |
| Custo de leitura                 | Recomputa a cada query (desprezível no volume de RH) | O(1) — um `JOIN` simples                                                     |
| Risco de inconsistência          | Nenhum (não há cópia a sincronizar)                  | Alto (a cópia pode divergir da verdade)                                      |

O **argumento decisivo** é a manutenção: trocar o gestor de alguém (promoção, transferência, reorganização) é uma operação corriqueira no RH. Com a CTE, basta atualizar `gestor_id` numa linha e **toda subárvore passa a refletir a mudança instantaneamente** — não há materialização a invalidar. Com closure table, a mesma troca exige reescrever o fecho transitivo de um ramo inteiro, numa transação delicada e propensa a bugs. O custo da CTE (recomputar por query) é irrelevante no volume típico de um cadastro de pessoal (dezenas a milhares de funcionários por empresa), ainda mais com o índice `(empresa_id, gestor_id)` já previsto em [01 §7](01-modelo-de-dominio.md).

A **closure table fica registrada como evolução de performance** (§12.4): se algum cliente atingir volume/latência que justifique leitura O(1), ela pode ser introduzida como **cache** da subárvore **sem mudar a API do trait** — `EscopoOrganograma` passaria a consultar a tabela materializada em vez da CTE, e o resto do módulo não percebe. A decisão e o trade-off completos estão no [ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md).

### 4.2 O SQL da CTE

A subárvore de um funcionário-raiz `:raiz_id` numa empresa `:empresa_id`:

```sql
WITH RECURSIVE subarvore AS (
    -- termo âncora: o próprio funcionário (a raiz da minha visão)
    SELECT f.id, f.gestor_id
    FROM funcionarios f
    WHERE f.id = :raiz_id
      AND f.empresa_id = :empresa_id
      AND f.deleted_at IS NULL

    UNION                                   -- UNION (não UNION ALL) já corta ciclos por dedup

    -- recursão: quem tem como gestor alguém já presente na subárvore
    SELECT f.id, f.gestor_id
    FROM funcionarios f
    INNER JOIN subarvore s ON f.gestor_id = s.id
    WHERE f.empresa_id = :empresa_id        -- repete o tenant: defesa em profundidade
      AND f.deleted_at IS NULL              -- não recursa por desligados (some da árvore)
)
SELECT id FROM subarvore;
```

Notas de projeto sobre o SQL:

- **Termo âncora = o funcionário-raiz** (o "eu" resolvido por `FuncionarioAtual`, ou qualquer nó intermediário para um líder/preposto). A raiz **sempre se inclui** — daí a auto-visibilidade garantida (§7.6).
- **Recursão por `gestor_id`**: a cada passo, agrega quem aponta (como subordinado) para alguém já no conjunto. Desce a árvore nível a nível.
- **Filtra `deleted_at IS NULL`** nos dois ramos: um nó na lixeira não entra e **não serve de ponte** — seus subordinados não são alcançados por ele (alinhado à regra de não recursar por desligados, §7.3).
- **Repete `empresa_id`** no passo recursivo mesmo que o global scope/anchor já restrinjam: é **defesa em profundidade** e mantém o plano de execução colado no índice `(empresa_id, gestor_id)`.
- **Proteção anti-ciclo**: o `UNION` (com deduplicação) já impede laço infinito — um nó visitado não é reprocessado. Para blindagem explícita, o Postgres oferece a cláusula `CYCLE` (Postgres 14+), que marca e interrompe ciclos detectados:

    ```sql
    WITH RECURSIVE subarvore AS (
        ... termo âncora ...
        UNION ALL
        ... recursão ...
    )
    CYCLE id SET eh_ciclo USING caminho           -- rede de segurança contra dados corrompidos
    SELECT id FROM subarvore WHERE NOT eh_ciclo;
    ```

    Na prática, ciclos **não devem existir** porque são barrados na **escrita** (§7.2); a proteção na leitura é só uma rede de segurança contra dados legados/corrompidos.

> **A CTE exige Postgres.** `WITH RECURSIVE` com `CYCLE` é específico do banco de produção. Os **testes de escopo do organograma rodam na suíte Postgres dedicada** ([08 §7](08-arquitetura-tecnica.md), `@group postgres`), não em SQLite (alinhado à nota do [CLAUDE.md / gotchas] de que FKs e recursos avançados do Postgres não têm paridade no SQLite). Caso algum cenário precise de fallback (suite leve sem Postgres), prever um **caminho iterativo em PHP** que carrega `(id, gestor_id)` da empresa e expande a subárvore em memória com proteção anti-ciclo por conjunto de visitados — mesma semântica, custo maior, usado só como degradação.

---

## 5. Empacotamento: trait `VisivelNaHierarquia` + serviço `EscopoOrganograma`

A mecânica é embrulhada do mesmo jeito que o core embrulha o tenant: **um trait no model adiciona um global scope nomeado, que delega a um serviço**. Assim o organograma "simplesmente acontece" em toda query de `Funcionario`, sem o chamador precisar lembrar.

### 5.1 O trait e o global scope nomeado `organograma`

```php
<?php

declare(strict_types=1);

namespace HT2ML\Rh\Models\Concerns;

use HT2ML\Rh\Support\Organograma\EscopoOrganograma;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dá ao Funcionario o eixo "organograma" da ACL: um global scope nomeado
 * `organograma` que restringe à subárvore do usuário logado.
 *
 * Composto em AND com o scope `empresa` do BelongsToEmpresa (ordem irrelevante).
 * Desative conscientemente com withoutGlobalScope('organograma') — ver §5.4.
 */
trait VisivelNaHierarquia
{
    public static function bootVisivelNaHierarquia(): void
    {
        static::addGlobalScope('organograma', function (Builder $builder): void {
            app(EscopoOrganograma::class)->aplicar($builder);
        });
    }
}
```

O `Funcionario` passa a compor os dois traits:

```php
final class Funcionario extends Model
{
    use \HT2ML\Core\Models\Concerns\BelongsToEmpresa;   // scope 'empresa'  (tenant)
    use VisivelNaHierarquia;                       // scope 'organograma' (subárvore)
    use \Illuminate\Database\Eloquent\SoftDeletes; // scope 'SoftDeletingScope'
    // ... Auditavel, contratos, etc.
}
```

### 5.2 A lógica de `EscopoOrganograma::aplicar()`

O serviço encapsula toda a árvore de decisão. A ordem dos curto-circuitos importa: bypass primeiro, fail-closed por último.

```php
<?php

declare(strict_types=1);

namespace HT2ML\Rh\Support\Organograma;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class EscopoOrganograma
{
    public function __construct(private readonly FuncionarioAtual $funcionarioAtual) {}

    public function aplicar(Builder $builder): void
    {
        $user = Auth::guard('admin')->user();

        // 1) Sem usuário autenticado (CLI, jobs, seeds): NÃO filtra.
        //    Espelha o BelongsToEmpresa, que também não filtra sem contexto.
        if (! $user instanceof AdminUser) {
            return;
        }

        // 2) super-admin: bypass total (igual ao AccessResolver::ehSuperAdmin).
        if ($this->ehSuperAdmin($user)) {
            return;
        }

        // 3) Quem tem ver_todos enxerga a empresa toda: desliga SÓ o organograma.
        //    O eixo tenant (scope 'empresa') permanece ativo e intocado.
        if ($user->can('rh.funcionarios.ver_todos')) {
            return;
        }

        // 4) Resolve "qual funcionário sou eu" na empresa ativa.
        $eu = $this->funcionarioAtual->resolver();

        // 5) Fail-closed: sem vínculo e sem privilégio → vê ZERO.
        if ($eu === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        // 6) Restringe à subárvore (CTE recursiva). A subquery devolve os IDs
        //    de mim + todos os meus subordinados (qualquer profundidade).
        $builder->whereIn(
            $builder->getModel()->getTable() . '.id',
            $this->idsDaSubarvore((int) $eu->getKey(), (int) $eu->getAttribute('empresa_id')),
        );
    }

    // idsDaSubarvore(): monta a CTE do §4.2 (DB::query()->fromSub(...) ou raw bind),
    //   retornando um Builder/closure que o whereIn consome como subquery.
    // ehSuperAdmin(): delega ao AccessResolver/checagem de role super-admin do core.
}
```

Pontos de paridade deliberada com o core:

- **Sem contexto, não filtra** (passo 1) — idêntico ao `BelongsToEmpresa`, que só aplica `where empresa_id` quando há empresa ativa. Jobs e comandos de console que precisem de escopo devem **definir o contexto** explicitamente, como já manda a doc de tenancy.
- **super-admin bypass** (passo 2) — mesma decisão de topo do `AccessResolver::decide()` (`ehSuperAdmin → true`).
- **`ver_todos` via `Gate`** (passo 3) — usa `$user->can(...)`, que passa pelo pipeline RBAC do core (deny > grant > role do `AccessResolver`). Quem concede `ver_todos` é o RBAC; o organograma só **respeita** a decisão.

### 5.3 Composição com `BelongsToEmpresa`: scopes nomeados independentes (AND)

Os dois global scopes — `empresa` e `organograma` — são **nomeados e independentes**. O Eloquent os aplica como cláusulas `WHERE` adicionais na mesma query, e como ambos só **acrescentam** restrições, o efeito líquido é **AND**, com **ordem irrelevante**:

```sql
-- Funcionario::query()->get()  →  SQL conceitual:
SELECT * FROM funcionarios
WHERE funcionarios.empresa_id = :empresa_ativa      -- scope 'empresa'  (BelongsToEmpresa)
  AND funcionarios.id IN ( <CTE da subárvore> )      -- scope 'organograma' (VisivelNaHierarquia)
  AND funcionarios.deleted_at IS NULL                -- SoftDeletes
```

E a própria CTE **repete** `empresa_id`/`deleted_at` internamente (§4.2) — redundância intencional de **defesa em profundidade**: mesmo que alguém desligue o scope `empresa` por engano, a subárvore continua confinada à empresa do "eu".

### 5.4 Desativações conscientes (escape hatches)

Espelhando o `withoutGlobalScope('empresa')` do core e o padrão de troca de scope visto em `HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa` (que faz `withoutGlobalScope('empresa')` e reaplica `whereIn(empresa_id, …)` só sobre a interseção autorizada):

| Escape                                           | Efeito                                                                         | Quando usar                                                                                                                                                          |
| ------------------------------------------------ | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Funcionario::withoutGlobalScope('organograma')` | Remove **só** o eixo organograma → vê toda a empresa (tenant continua valendo) | Telas/relatórios de quem tem `rh.funcionarios.ver_todos`; o multi-empresa do core já segue exatamente esse padrão de tirar um scope e reaplicar um filtro autorizado |
| `Funcionario::withTrashed()`                     | Inclui registros na lixeira (`deleted_at`)                                     | Tela de lixeira (`ComLixeira`), restauração, auditoria                                                                                                               |
| `Funcionario::withoutGlobalScope('empresa')`     | Remove o eixo tenant                                                           | **Excepcional**, só relatórios cross-empresa autorizados — e ainda assim a CTE confina à empresa do "eu"                                                             |

> A regra de uso: **só desligue um scope quando o RBAC já autorizou a visão ampliada.** O padrão correto não é "tirar o scope e mostrar tudo", e sim "tirar o scope e reaplicar a fronteira que o RBAC permite" — exatamente como `FiltraPorMultiEmpresa` faz com `permiteNaEmpresa()` (§6).

---

## 6. Interação com o filtro multi-empresa do core

O eixo tenant não é necessariamente "uma empresa só". O core já oferece, via `HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa`, um filtro que permite a um usuário com a capability `listagens.multi_empresa` (e acesso a 2+ empresas) **incluir outras empresas** numa listagem PowerGrid — sempre limitado pelo RBAC estrito por empresa (`AccessResolver::permiteNaEmpresa($user, $abilityListar, $empresaId)`), com a interseção `selecionadas ∩ elegíveis` blindando contra injeção de `empresa_id` pelo cliente.

No RH, os dois mecanismos **compõem por AND**, cada um no seu eixo:

- **`FiltraPorMultiEmpresa`** opera no **eixo tenant**: troca `withoutGlobalScope('empresa')` por `whereIn(empresa_id, <empresas autorizadas>)`. Amplia _quais empresas_.
- **`VisivelNaHierarquia` / `organograma`** opera no **eixo organograma**: restringe à subárvore. Amplia/restringe _quais pessoas dentro de cada empresa_.

Consequência prática: um usuário com `listagens.multi_empresa` **e** `ver_todos` em duas empresas vê todos os funcionários das empresas selecionadas (organograma desligado, tenant ampliado pela interseção autorizada). Já um usuário multi-empresa **sem** `ver_todos` veria, em cada empresa, apenas a subárvore em que ele é funcionário naquela empresa — e como `FuncionarioAtual` resolve **por empresa** (cache por `(user, empresa)`), o "eu" pode ser uma pessoa diferente em cada tenant. Os eixos não se atropelam: cada um aplica sua cláusula `WHERE`, e o resultado é a interseção.

---

## 7. Matriz de casos

Como os três eixos se combinam para os perfis típicos. Lembrando que **gestor/líder/preposto/colaborador são posições no organograma**, não roles — a coluna "Quem é" descreve a posição na árvore `gestor_id`; a visibilidade **decorre** dela.

| Quem é (posição/privilégio)                                    | Eixo Tenant              | Eixo RBAC (ação)                      | Eixo Organograma                 | Vê                                                                   |
| -------------------------------------------------------------- | ------------------------ | ------------------------------------- | -------------------------------- | -------------------------------------------------------------------- |
| **Super-admin**                                                | empresa ativa (contexto) | bypass (sempre permite)               | bypass                           | **Tudo** da empresa ativa (e do multi-empresa autorizado)            |
| **RH / admin do módulo** (tem `rh.funcionarios.ver_todos`)     | empresa ativa            | tem `listar/editar/...`               | **desligado** por `ver_todos`    | **Todos** os funcionários da empresa (tenant mantido)                |
| **Gestor** (tem subordinados via `gestor_id`)                  | empresa ativa            | tem `listar/editar` (sem `ver_todos`) | sua **subárvore**                | **Sua subárvore** (subordinados diretos e indiretos) **+ ele mesmo** |
| **Líder / Preposto** (nó intermediário)                        | empresa ativa            | idem gestor                           | subárvore a partir do **seu nó** | **Mesma mecânica do gestor, raiz diferente** — quem está abaixo dele |
| **Colaborador folha** (sem subordinados)                       | empresa ativa            | pode ter `rh.self.ver`                | subárvore = só ele               | **Só a si mesmo**                                                    |
| **Login sem vínculo de RH** (sem funcionário, sem `ver_todos`) | empresa ativa            | —                                     | fail-closed (`1=0`)              | **Nada**                                                             |

Leitura da matriz:

- **A diferença entre gestor, líder e preposto é só a raiz da CTE** — a mecânica é idêntica; muda o nó âncora (mais alto na árvore = vê mais gente abaixo). Não há código diferente por "tipo de chefe".
- **`ver_todos` é o único atalho que apaga o eixo organograma** — e mesmo assim não toca o tenant.
- **O colaborador folha e o login-sem-vínculo são casos distintos**: o primeiro tem funcionário (subárvore = {ele}); o segundo não tem (fail-closed = {}).

---

## 8. Edge cases

| #   | Caso                                                          | Comportamento                                                                                                                                                                                                                                                                                                                                                                                                  |
| --- | ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 8.1 | **Funcionário sem gestor** (`gestor_id IS NULL`)              | É **topo** da árvore (diretoria). Aparece como raiz no organograma; sua subárvore é tudo abaixo dele. Ninguém "acima" o enxerga por organograma (só `ver_todos`/super-admin).                                                                                                                                                                                                                                  |
| 8.2 | **Ciclos** (A→B→A)                                            | **Prevenção na escrita**: a Action que define `gestor_id` rejeita (a) **auto-referência** (`gestor_id = id`, também barrada pelo CHECK do banco) e (b) **gestor que já está na própria subárvore do funcionário** (definir como meu chefe alguém que é meu subordinado criaria laço). **Rede de segurança na leitura**: `UNION`/cláusula `CYCLE` na CTE (§4.2) impede laço infinito mesmo com dado corrompido. |
| 8.3 | **Funcionário desligado** (soft-deleted / `status=desligado`) | A CTE filtra `deleted_at IS NULL`, então o desligado **não entra** na subárvore **e não recursa** — não serve de ponte para alcançar quem estava sob ele. **Desligar um gestor exige reatribuir os subordinados** primeiro (§7.3 e 3.•RESTRICT), senão eles ficariam "órfãos" pendurados num nó invisível.                                                                                                     |
| 8.4 | **Troca de gestor** (reorg/promoção)                          | **Instantânea**: como a CTE é "ao vivo", basta atualizar `gestor_id` numa linha — a subárvore de todos os afetados muda na próxima query, **sem materialização a invalidar**. É o benefício decisivo do WITH RECURSIVE sobre closure table (§4.1).                                                                                                                                                             |
| 8.5 | **Múltiplos gestores** (matricial)                            | **Fora de escopo na Fase 1** — `gestor_id` é escalar (1 gestor direto). Evolução futura via pivot (§12.3).                                                                                                                                                                                                                                                                                                     |
| 8.6 | **Auto-visibilidade**                                         | **Sempre**: o termo âncora da CTE é o próprio "eu", então o usuário-funcionário sempre se vê (base do self-service, §9). Mesmo um colaborador folha tem subárvore não-vazia: `{ele}`.                                                                                                                                                                                                                          |

> Onde a prevenção de ciclo vive no código: na **Action de atribuição de gestor** (citada em [02 §B3 checklist](02-fase-1-blueprint.md): "Action de atribuição de gestor com detecção de ciclo"). Ela carrega a subárvore atual do funcionário (a mesma CTE) e recusa qualquer `gestor_id` que já pertença a ela — barrando o ciclo antes de gravar. O CHECK `gestor_id <> id` ([01 §3 B1](01-modelo-de-dominio.md)) cobre o caso degenerado de profundidade 0.

### 8.7 Pseudocódigo da Action de atribuição de gestor

A regra é: **o novo gestor não pode estar na subárvore do funcionário-alvo** (nem ser ele mesmo) — senão "meu chefe é meu subordinado", o que fecha um laço.

```text
AtribuirGestorAction::execute(Funcionario $alvo, ?int $novoGestorId): Funcionario
  1. Topo da árvore: se $novoGestorId === null → grava gestor_id = null (raiz) e retorna.
  2. Auto-referência: se $novoGestorId === $alvo->id → rejeita (LoopDeGestorException).
        (o CHECK gestor_id <> id do banco é a 2ª linha de defesa para profundidade 0.)
  3. Mesma empresa: valida que o novo gestor é da empresa do alvo
        (Rule::exists('funcionarios','id')->where('empresa_id', $alvo->empresa_id));
        candidatos da UI já vêm escopados pelo tenant.
  4. Anti-ciclo (o ponto central): carrega a SUBÁRVORE do alvo pela MESMA CTE do §4.2,
        com raiz = $alvo (inclui o próprio alvo + todos os descendentes):
            $subarvore = EscopoOrganograma::idsDaSubarvore($alvo->id, $alvo->empresa_id);
        se $novoGestorId ∈ $subarvore → rejeita (LoopDeGestorException):
            "não se pode definir como gestor alguém que está na própria subárvore".
  5. Persiste: DB::transaction(fn () => $alvo->update(['gestor_id' => $novoGestorId])).
        (Opcional: registrar um evento funcional de transferência/chefia na linha do tempo — [06].)
```

> **Conjunto verificado = subárvore ∪ {ele mesmo}.** O passo 4 reusa exatamente a CTE recursiva do §4.2 (raiz = alvo), que já inclui o próprio alvo no termo âncora — logo a verificação cobre auto-referência **e** ciclo profundo numa só consulta.
>
> **Custo e `CYCLE`.** A verificação é O(tamanho da subárvore do alvo) — desprezível no volume de RH (e colada no índice `(empresa_id, gestor_id)`). Como o ciclo é barrado **na escrita**, a árvore permanece acíclica por invariante; a cláusula `CYCLE` da CTE de leitura (§4.2) é só **rede de segurança** contra dados legados/corrompidos, não o mecanismo primário. A Action **exige Postgres** (CTE) — testá-la é parte da suíte Postgres ([08 §7](08-arquitetura-tecnica.md)).

---

## 9. Self-service do colaborador

O vínculo `funcionarios.admin_user_id` + a auto-visibilidade (§8.6) habilitam o **portal do colaborador** sem nenhuma regra de ACL nova: o colaborador-folha loga, o eixo organograma resolve a subárvore = `{ele}`, e a ACL **naturalmente retorna só o próprio registro**. Não é um caminho especial — é o caso geral da subárvore com raiz folha.

A partir desse "ele só se vê", o recorte **por campo** é responsabilidade do `FormFuncionario` em **modo `proprio`** (vs `rh`), conforme a **matriz Colaborador × RH detalhada em [03 §11](03-cadastro-pessoa-documentos.md)**. Em resumo:

- **O colaborador vê e edita um subconjunto**: foto, nome social, **contatos** (telefone/e-mail), **endereços**, **dados bancários + PIX** (próprios), **dependentes**, e **anexa** documentos pessoais.
- **Edição sensível é vedada ao colaborador** e fica só com RH/gestor: **cargo, departamento, gestor, filial (lotação)** e **contratação** (vínculo, regime, **salário**, admissão/demissão, status) — e essas mudanças passam pela **linha do tempo** ([06](06-linha-do-tempo.md)), nunca por `update` direto. Nome civil, CPF, PIS/PASEP e matrícula são só-leitura para o colaborador.
- **Defesa no servidor, não só na UI**: no modo `proprio`, campos fora do recorte renderizam só-leitura **e** a validação/Action ignoram qualquer tentativa de alterar campos vedados (a UI desabilitada é conveniência; a barreira real é no servidor). A permissão `rh.self.ver` ([02](02-fase-1-blueprint.md)) governa o acesso ao portal.

> O alinhamento é total com o doc 03: a ACL de subárvore garante **quais registros** (só o dele); o modo `proprio` do form garante **quais campos** dentro desse registro. Os dois eixos do "self-service" — linha e coluna — são tratados em camadas distintas.

### 9.1 Ciclo de vida do vínculo de acesso (provisionar / revogar)

O self-service e a resolução "qual funcionário sou eu" (§2.3) só funcionam quando `funcionarios.admin_user_id` está preenchido. Como a maioria dos funcionários nasce **sem** login (`admin_user_id = NULL`), o vínculo tem um ciclo de vida explícito — e, enquanto não existe, vale o **fail-closed** (§2.4): o `AdminUser` sem vínculo (e sem `ver_todos`) vê **zero**.

| Momento             | O que acontece com o vínculo / acesso                                                                                                                                                                                                                                                                                              |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Sem login**       | `admin_user_id = NULL`. O funcionário existe no organograma/folha, mas não acessa o painel. Fail-closed por construção.                                                                                                                                                                                                            |
| **Conceder acesso** | Ação **do RH** (nunca auto-serviço): (a) **vincular** a um `AdminUser` existente sem vínculo na empresa, ou (b) **criar + convidar** (e-mail de definição de senha, papel de colaborador). Grava `admin_user_id` respeitando `UNIQUE (empresa_id, admin_user_id)`. Mecânica de UI em [03 §11.2](03-cadastro-pessoa-documentos.md). |
| **Admissão**        | O evento de domínio `admissao` **pode** disparar o provisionamento (caminho b) quando a empresa configurar "criar acesso na admissão" — listener idempotente ([06 §4.1](06-linha-do-tempo.md)).                                                                                                                                    |
| **Desligamento**    | O evento `desligamento` **revoga o acesso**: desativa o `AdminUser` e/ou remove os papéis **por empresa** (`admin_user_empresa_role`). O funcionário e o histórico permanecem; um login inativo não acessa (fail-closed).                                                                                                          |

> Segurança: conceder/revogar acesso é operação **do RH** (sob `rh.funcionarios.editar` + a capability de gestão de acesso do core), **nunca** self-service; os listeners de admissão/desligamento são **idempotentes** e toleram reprocessamento ([06 §4.1](06-linha-do-tempo.md)). A automação total (provisionar sempre na admissão) é evolução; a Fase 1 entrega a **ação manual** + a **revogação no desligamento**.

### 9.2 O portal do colaborador como produto (recursos × fase)

A auto-visibilidade (§8.6) + o vínculo + o modo `proprio` ([03 §11](03-cadastro-pessoa-documentos.md)) formam o **portal do colaborador** — um produto que **cresce com as fases** ([09](09-roadmap-fases.md)). Cada recurso aparece quando o módulo que o sustenta entra; tudo sobre o **próprio** registro (subárvore = `{ele}`):

| Recurso (do colaborador, sobre si)                                           | Fase  | Onde                                                   |
| ---------------------------------------------------------------------------- | ----- | ------------------------------------------------------ |
| Ver/editar dados cadastrais próprios (contato, endereço, banco, dependentes) | **1** | [03 §11](03-cadastro-pessoa-documentos.md)             |
| Ver/enviar documentos próprios (anexar RG/CPF/comprovantes)                  | **1** | [03 §8/§11](03-cadastro-pessoa-documentos.md)          |
| Ver a própria linha do tempo (leitura)                                       | **1** | [06 §6.2](06-linha-do-tempo.md)                        |
| Ver a própria escala e horas extras (leitura)                                | **1** | [07](07-jornada-horas-extras-folha.md)                 |
| Enviar atestado e acompanhar o status                                        | **2** | [12 §7](12-ausencias-faltas-atestados-afastamentos.md) |
| Consultar faltas/afastamentos (leitura)                                      | **2** | [12 §7](12-ausencias-faltas-atestados-afastamentos.md) |
| Solicitar férias (workflow)                                                  | **2** | [09 §3](09-roadmap-fases.md)                           |
| Holerite/demonstrativo (PDF)                                                 | **3** | [09 §4](09-roadmap-fases.md)                           |

> O portal **não** é um guard/SPA novo (o projeto é um **único ambiente admin** — CLAUDE): é o **mesmo painel** com escopo `proprio` resolvido pela ACL. Cada recurso reusa a tela do módulo correspondente, restrita à subárvore `{ele}` (§8.6). A Fase 1 entrega o vínculo + self-service de dados/documentos; os recursos avançados seguem as fases dos respectivos módulos ([12](12-ausencias-faltas-atestados-afastamentos.md)/[07](07-jornada-horas-extras-folha.md)/[09](09-roadmap-fases.md)).

### 9.3 Funcionário ↔ usuário ↔ permissões (e multi-empresa)

- **Papel + capability.** O acesso do colaborador é um **papel "colaborador" por empresa** (RBAC de 2 níveis — §1) + a capability `rh.self.ver`. O papel concede os **verbos** de leitura/edição própria; a **ACL de subárvore** (§1) garante que recaiam **só** sobre o próprio registro. Sem `ver_todos` (jamais para colaborador).
- **Multi-empresa (§6).** O mesmo `AdminUser` pode ser **pessoas distintas** em empresas diferentes; `FuncionarioAtual` resolve por `(user, empresa)` (§2.3) — o portal mostra o "eu" da **empresa ativa**, nunca mistura.
- **Convite / primeiro acesso / 2FA.** Provisionar o acesso reusa o **fluxo de convite do core** ([03 §11.2](03-cadastro-pessoa-documentos.md)); o colaborador, como qualquer `AdminUser`, passa pelo **2FA por e-mail** e pela política de **inatividade** do core — **sem** mecanismo de auth novo. A revogação no desligamento (§9.1) desativa o login/papéis, e o fail-closed (§2.4) fecha o acesso de quem perdeu o vínculo.

---

## 10. Organograma de CARGOS vs organograma de PESSOAS

São **duas árvores diferentes**, com propósitos distintos, e é fácil confundi-las:

|                     | Organograma de **CARGOS**                                                                                               | Organograma de **PESSOAS**                            |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| Estrutura           | `cargo_nivel` (SMALLINT) — ranking/nível hierárquico do cargo                                                           | `gestor_id` (self-FK) — quem reporta a quem           |
| Vive em             | `funcionarios.cargo_nivel` (cache desnormalizado do nível do cargo — [01 §0/B1](01-modelo-de-dominio.md))               | `funcionarios.gestor_id`                              |
| Propósito           | **Visual/estrutural**: ordenar/agrupar por senioridade ("nível 1 = diretor, nível 5 = operacional"), faixas, relatórios | **Governa a ACL**: define a subárvore de subordinados |
| Afeta visibilidade? | **Não** — é só ranking                                                                                                  | **Sim** — é a árvore da §4                            |

Pontos importantes:

- **A árvore que governa o acesso é a de PESSOAS** (`gestor_id`). `cargo_nivel` **não** participa do eixo organograma da ACL — um "Analista Sênior" (cargo de nível alto) que não tem subordinados é folha na árvore de pessoas, independentemente do nível do cargo.
- `cargo_nivel` é **cache desnormalizado**: a Action resolve o nível a partir do `cargo_id` escolhido ([01 §B1](01-modelo-de-dominio.md); [03 §2](03-cadastro-pessoa-documentos.md) confirma que não é editado na tela). Serve a ordenação/visualização e a relatórios, não a permissão.
- As duas visões podem coexistir na **tela de organograma**: a árvore principal é a de pessoas (reflete `gestor_id`), e o nível do cargo pode colorir/rotular cada nó.

### 10.1 A tela de organograma (`OrganogramaView`) — spec de UX (checklist 1.2)

A visualização em árvore do organograma é uma **tela custom** — **não há componente nativo** de árvore no catálogo Inspinia/shared. O `OrganogramaView` (Livewire + Alpine, citado em [02 §B3](02-fase-1-blueprint.md)) entra como item **🔴 a componentizar** seguindo o fluxo do catálogo Inspinia (documentar → registrar em [`CATALOGO-COMPONENTES.md`](../../../template/INSPINIA/CATALOGO-COMPONENTES.md) → criar `.blade.php`), **sem `<select>` nativo** e **sem CSS custom** (só Tailwind — CLAUDE §9).

A entrega é uma **árvore incremental**: a Fase 1 entrega a árvore navegável e editável; recursos visuais ricos (pan/zoom, tela cheia, canvas/mapa-mental) entram como camadas incrementais e evolução (§12.5). Decisão de produto **D2**.

#### 10.1.1 Visualização

- **Árvore expandir/recolher** — renderiza a hierarquia a partir de `gestor_id` (a CTE da §4 ou um carregamento de `(id, nome, cargo, cargo_nivel, gestor_id, departamento_id, foto)` da empresa, expandido no cliente). Expand/collapse por nó é **interação Alpine puramente visual** (sem ida ao servidor por nó).
- **Cada nó**: nome, cargo (com `cargo_nivel` para cor/ordenação — organograma de cargos, §10), foto (URL assinada — disco privado, [03 §10](03-cadastro-pessoa-documentos.md)), badge das **funções** ativas (líder/preposto…), departamento, e atalho **"ver detalhes"** → cadastro (sob a própria ACL — §11).
- **Pan / zoom / tela cheia** — **camada incremental** sobre a árvore (controles de zoom, arrastar o canvas, modo tela cheia para organogramas grandes). Não bloqueia a Fase 1 mínima.
- **Canvas/mapa-mental rico** (layout radial, conectores curvos, mini-mapa) — **evolução** (§12.5), não Fase 1.

#### 10.1.2 Interações (sempre via Action, nunca `update` solto)

- **Arrastar-para-reposicionar** (trocar o gestor de alguém arrastando o nó para outro chefe) — reusa **sempre** a `AtribuirGestorAction` com **detecção de ciclo** (§8.7). A UI **nunca** grava `gestor_id` direto: o drag dispara a Action, que valida mesma-empresa + anti-ciclo e, opcionalmente, registra um evento na linha do tempo ([06](06-linha-do-tempo.md)).
- **Definir gestor / responsável**, **alterar hierarquia**, **remover vínculo** (tornar raiz: `gestor_id = null`), **reorganizar** — todas pela mesma Action; o responsável de **departamento** (`responsavel_funcionario_id`) é editado no catálogo ([04 §2](04-catalogos-configuraveis.md)).
- **Criar elemento / relacionar** — criar funcionário já com gestor, ou ligar um existente — atalho para o `FormFuncionario` ([03](03-cadastro-pessoa-documentos.md)) com o gestor pré-preenchido.
- **Ver detalhes** — atalho ao cadastro, **sujeito à ACL** (só abre quem o usuário pode ver — §11).
- Toda mutação estrutural é **guardada por permissão** (`rh.funcionarios.editar` para mover/definir gestor; §11.2) e confinada à subárvore de quem opera (salvo `ver_todos`).

#### 10.1.3 Busca e filtros

- **Busca** por **funcionário** (nome/matrícula), **cargo** e **gestor** — e por **"setor"** (sinônimo de **departamento**, [README — Glossário](README.md)). A busca **realça** e **expande o caminho** até o nó encontrado.
- **Filtros**: **empresa** (multi-empresa, §6), **filial**, **departamento** (e sub-departamentos), **centro de custo** (§3.2 / [04 §7.1](04-catalogos-configuraveis.md)), **situação** (`StatusFuncionario`: ativo/afastado/férias/…). Filtro multi-select via `x-shared.select-search :multiple=true` (nunca `<select>` cru — CLAUDE §19).

#### 10.1.4 Detecção / relatórios estruturais

Derivados da árvore, para o RH sanear a estrutura (escopados pela ACL):

- **Funcionários sem vínculo organizacional** — `gestor_id IS NULL` (raízes — esperado só na diretoria; o resto é alerta) **e/ou** `departamento_id IS NULL` (sem lotação).
- **Departamentos sem responsável** — `departamentos.responsavel_funcionario_id IS NULL` ([04 §2](04-catalogos-configuraveis.md)).
- **Posições vagas** — **relatório derivado** no MVP (ex.: departamento ativo sem nenhum funcionário, ou gestor desligado com subordinados órfãos), **não** uma entidade "vaga/posição" rastreada. Rastrear posição/vaga de verdade (headcount, cargo aberto) é decisão em aberto — [PEND-01](13-rastreabilidade-e-pendencias.md).

#### 10.1.5 Escopo de visão (os 3 eixos valem na tela)

A árvore exibida respeita os mesmos três eixos (§1): quem tem `rh.organograma.ver` mas **não** `ver_todos` enxerga **sua subárvore** como organograma; com `ver_todos`, a **empresa inteira**; multi-empresa amplia o tenant (§6). A permissão dedicada `rh.organograma.ver` ([02 §B3](02-fase-1-blueprint.md)) controla o acesso à tela; as **regras de comportamento** da estrutura (troca de gestor, transferências, desligado, reorganização) estão em §13 e a **segurança/auditoria** em §11.2.

---

## 11. Permissões do eixo

As permissões seguem o padrão `rh.<recurso>.<acao>` do [02 §B3](02-fase-1-blueprint.md) (prefixo `rh.` obrigatório para não colidir com o core — [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)). As que importam para esta ACL:

| Permissão                       | Eixo afetado              | O que faz                                                                                            |
| ------------------------------- | ------------------------- | ---------------------------------------------------------------------------------------------------- |
| `rh.funcionarios.listar`        | RBAC (verbo)              | Pode **listar** funcionários. Não diz _quais_ — isso é tenant + organograma.                         |
| `rh.funcionarios.ver`           | RBAC (verbo)              | Pode **abrir/ver** o cadastro de um funcionário (dentro do que tenant+organograma deixam ver).       |
| `rh.funcionarios.criar`         | RBAC (verbo)              | Pode **criar** funcionário.                                                                          |
| `rh.funcionarios.editar`        | RBAC (verbo)              | Pode **editar** funcionário — restrito à **subárvore** (gestor edita só os seus, salvo `ver_todos`). |
| **`rh.funcionarios.ver_todos`** | **Organograma (desliga)** | **Desliga o eixo organograma**: vê todos os funcionários da empresa. **Nunca** desliga o tenant.     |
| `rh.organograma.ver`            | RBAC (verbo) + escopo     | Acessa a **tela de organograma** (§10.1); o que enxerga ainda obedece subárvore/`ver_todos`.         |
| `rh.self.ver`                   | RBAC (verbo)              | Acessa o **portal do colaborador** (§9).                                                             |

> **Nota sobre editar/aprovar:** as ações de mutação e aprovação **também** são restritas à subárvore. Um gestor sem `ver_todos` só edita quem está sob ele, e — quando o workflow de horas extras seguir a cadeia do organograma ([07](07-jornada-horas-extras-folha.md)) — **só aprova HE de subordinados**. A regra é uniforme: o RBAC concede o **verbo** (editar/aprovar), e o organograma confina **sobre quem** o verbo pode ser exercido. A policy do `Funcionario` checa as duas coisas: a permissão (RBAC) **e** a pertença à subárvore (organograma) — usando a mesma `EscopoOrganograma`/`FuncionarioAtual` para autorizar a ação sobre um alvo específico.

### 11.1 Nota sobre o verbo `listar` vs `ver`/`criar`

O conjunto CRUD padrão do pacote ([02 §"Permissões"](02-fase-1-blueprint.md)) usa `listar, criar, editar, deletar, restaurar, excluir_permanente`. Este documento também menciona `ver` (abrir o cadastro) como ação de leitura individual. Na implementação, alinhar ao registro real de permissões em `config/rh.php`: o eixo RBAC é o mesmo independentemente do nome exato do verbo — o que **nunca** muda é que **o verbo é RBAC; quais linhas é tenant + organograma**.

### 11.2 Segurança e auditoria do organograma

Quem pode **ver** e quem pode **alterar** a estrutura, e como cada mudança fica **rastreável** (atende o checklist 1.4 do cliente). Tudo conferido **no servidor** (Policy + escopo), nunca só na UI.

| Ação na estrutura                                | Permissão (RBAC) + escopo (organograma)                                                                                    | Auditoria / rastreabilidade                                                                                                             |
| ------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| **Ver o organograma**                            | `rh.organograma.ver` + subárvore (ou `ver_todos`)                                                                          | leitura — sem mutação                                                                                                                   |
| **Mover pessoa / trocar gestor** (drag, §10.1.2) | `rh.funcionarios.editar` + alvo **na subárvore** (salvo `ver_todos`); passa pela `AtribuirGestorAction` (anti-ciclo, §8.7) | `activity_log` (quem/quando/diff de `gestor_id`) + **opcional** evento `transferencia_*` na linha do tempo ([06](06-linha-do-tempo.md)) |
| **Definir responsável de departamento**          | `rh.departamentos.editar`                                                                                                  | `activity_log` do `Departamento` (`Auditavel`)                                                                                          |
| **Alterar lotação (departamento/filial)**        | `rh.funcionarios.editar` + subárvore                                                                                       | evento `transferencia_departamento`/`transferencia_filial` **append-only** ([06 §2](06-linha-do-tempo.md))                              |
| **Conceder/revogar acesso do colaborador**       | `rh.funcionarios.editar` + capability de gestão de acesso do core (**nunca** self-service — §9.1)                          | `activity_log`; provisionamento/revogação por listener idempotente ([06 §4.1](06-linha-do-tempo.md))                                    |
| **Ver dados sensíveis no nó** (PCD, etc.)        | permissão dedicada `rh.funcionarios.ver_dados_sensiveis` ([01 §8.1](01-modelo-de-dominio.md))                              | acesso sensível separado do CRUD; matriz em [01 §8.1](01-modelo-de-dominio.md)                                                          |

- **Ações que exigem permissão especial:** mover/reorganizar exige `rh.funcionarios.editar` (não basta `listar`); ver dado sensível de um nó exige `ver_dados_sensiveis`; ver a empresa toda exige `ver_todos`. O super-admin faz bypass (auditado).
- **Identificação de quem alterou:** o `activity_log` (trait `Auditavel`) grava **usuário + data + diff** de cada mudança de `gestor_id`/lotação/responsável; os eventos funcionais gravam `registrado_por_admin_user_id` ([01 §C1](01-modelo-de-dominio.md)) — "quem operou", distinto de "de quem é a história" (§2.3 / [06 §4](06-linha-do-tempo.md)).
- **Consulta a versões anteriores da estrutura:** a estrutura "de antes" é reconstruível pela **linha do tempo** — os eventos `transferencia_*`/`promocao`/`mudanca_cargo` (append-only, com snapshot JSONB) permitem responder "quem era o gestor/departamento de X na data Y" ([06 §3.1](06-linha-do-tempo.md)). O organograma **atual** lê o cache (`gestor_id`); o **histórico** lê os eventos. PII sensível na auditoria segue a matriz de [01 §8.1](01-modelo-de-dominio.md).

---

## 12. Extensões futuras

Todas mantêm a **API do trait/serviço estável** — o resto do módulo não percebe a evolução. Detalhes no [ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md).

| #    | Extensão                                                                          | Como entra (sem quebrar)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ---- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 12.1 | **Visibilidade lateral** (ver colegas do mesmo departamento, não só subordinados) | Nova capability `rh.funcionarios.ver_lateral`: o scope `organograma` passa a ser a **união** "subárvore ∪ mesmo `departamento_id`". A composição AND com tenant não muda; só o conjunto de IDs do `whereIn` cresce.                                                                                                                                                                                                                                                                                                      |
| 12.2 | **Delegação temporária** (cobrir férias de um gestor)                             | Tabela de delegação (`delegante`, `delegado`, vigência). O `EscopoOrganograma` resolve **múltiplas raízes** e a CTE roda com `WHERE id IN (raízes)` no âncora → a subquery vira união de subárvores. Vigência filtra delegações ativas.                                                                                                                                                                                                                                                                                  |
| 12.3 | **Múltiplos gestores** (matricial)                                                | `gestor_id` escalar evolui para pivot `funcionario_gestor` (N:N). A recursão da CTE passa a percorrer o **pivot** em vez da self-FK; a interface do scope (`whereIn(id, subárvore)`) é a mesma.                                                                                                                                                                                                                                                                                                                          |
| 12.4 | **Closure table como cache**                                                      | Materializa `(ancestral_id, descendente_id, profundidade)` para leitura O(1). `EscopoOrganograma` passa a consultar a tabela em vez da CTE, **sem mudar a assinatura** — ativável quando/se o volume exigir (§4.1).                                                                                                                                                                                                                                                                                                      |
| 12.5 | **Organograma visual rico** (canvas/mapa-mental)                                  | Layout radial/em árvore com conectores curvos, mini-mapa, exportação PNG/PDF. A Fase 1 já entrega árvore + expand/collapse + (incremental) pan/zoom/tela cheia (§10.1); o **canvas rico** é camada de apresentação **sobre os mesmos dados** (`gestor_id` + CTE) — não muda modelo nem ACL.                                                                                                                                                                                                                              |
| 12.6 | **Transferência entre empresas** (mover pessoa de um tenant para outro)           | Cross-tenant: hoje o vínculo é **por empresa** (§2.2) e a CTE confina à empresa do "eu" (§4.2). A evolução é um **processo** (desligar na origem + admitir no destino preservando o histórico, ou uma Action de transferência inter-empresa) — exige decidir o que migra (cadastro, eventos, documentos) e o reprovisionamento de papéis por empresa. **Pré-requisito:** romper a premissa "tudo de um `funcionario` é de uma empresa" de forma controlada. Registrado em [PEND-03](13-rastreabilidade-e-pendencias.md). |

O fio condutor de todas as extensões: **elas mexem só no conjunto de IDs que o eixo organograma produz** (`whereIn(id, …)`) ou na **camada de apresentação** — os eixos tenant e RBAC, e a forma como os três se combinam por AND, permanecem exatamente como descrito aqui. É o que torna a ACL extensível sem reescrita. **Responsáveis temporários** (12.2), **múltiplos gestores/matricial** (12.3) e **transferência entre empresas** (12.6) seguem todos como **evolução** — a Fase 1 entrega 1 gestor direto por pessoa, intra-empresa (§3).

---

## 13. Regras e comportamentos da estrutura organizacional

Consolida o **comportamento** do organograma diante das situações do dia a dia (atende o checklist 1.3 do cliente). Onde uma situação já foi tratada nos edge cases (§8), esta seção **referencia** em vez de duplicar.

| Situação                                       | Comportamento na Fase 1                                                                                                                                                                            | Onde / nota                                                  |
| ---------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| **Múltiplo vínculo** (1 pessoa, vários papéis) | Uma pessoa tem **1 gestor direto** (`gestor_id`) **+ N funções** (`funcionario_funcao`, N:N com vigência) **+ 1 departamento** de lotação. Funções acumuláveis; gestor e departamento, singulares. | §3.2 · [04 §3](04-catalogos-configuraveis.md)                |
| **Múltiplos gestores** (matricial)             | **Fora da Fase 1** — `gestor_id` escalar. Evolução por pivot.                                                                                                                                      | §8.5 · §12.3 · [PEND-03](13-rastreabilidade-e-pendencias.md) |
| **Gestor sobre departamentos distintos**       | **Permitido e natural**: a subárvore é de **pessoas** (§3.2), não de departamentos — um gestor pode ter subordinados de vários departamentos. A ACL não se importa com a lotação.                  | §3.2 (por que a base é pessoas)                              |
| **Troca de gestor** (promoção/reorg)           | **Instantânea**: atualiza `gestor_id` numa linha (via `AtribuirGestorAction`, anti-ciclo) e toda a subárvore reflete na próxima query — sem materialização a invalidar.                            | §8.4 · §8.7                                                  |
| **Transferência entre departamentos**          | Muda `departamento_id`; grava evento `transferencia_departamento` (append-only) na linha do tempo e atualiza o cache na mesma transação.                                                           | [06 §2/§4](06-linha-do-tempo.md)                             |
| **Transferência entre filiais**                | Muda `filial_id` (e `departamento_id` se realocar); evento `transferencia_filial`.                                                                                                                 | [06 §2](06-linha-do-tempo.md)                                |
| **Transferência entre empresas**               | **Evolução** (cross-tenant) — processo, não um `update`.                                                                                                                                           | §12.6 · [PEND-03](13-rastreabilidade-e-pendencias.md)        |
| **Substituição temporária** (cobrir férias)    | **Evolução** — delegação com vigência (resolve múltiplas raízes na CTE).                                                                                                                           | §12.2 · [PEND-03](13-rastreabilidade-e-pendencias.md)        |
| **Funcionário afastado**                       | Continua na árvore (segue subordinado/gestor); muda só o `StatusFuncionario` (`afastado`/`ferias`), conciliado pela Action de afastamento. Não some do organograma.                                | [06 §5.2](06-linha-do-tempo.md)                              |
| **Funcionário desligado**                      | A CTE **filtra `deleted_at IS NULL`** e não recursa por desligados — some da árvore **e não serve de ponte**. **Desligar um gestor exige reatribuir os subordinados** antes (RESTRICT de domínio). | §3 (RESTRICT) · §8.3                                         |
| **Cargo / posição vago**                       | Não há entidade "posição" na Fase 1 — vago é **relatório derivado** (§10.1.4).                                                                                                                     | §10.1.4 · [PEND-01](13-rastreabilidade-e-pendencias.md)      |
| **Departamento sem gestor/responsável**        | Permitido; aparece no relatório de "departamentos sem responsável" (`responsavel_funcionario_id IS NULL`).                                                                                         | §10.1.4 · [04 §2](04-catalogos-configuraveis.md)             |
| **Reorganização** (mover um ramo inteiro)      | Trocar o `gestor_id` do topo do ramo move toda a subárvore de uma vez (CTE ao vivo). Cada troca passa pela Action anti-ciclo.                                                                      | §4.1 · §8.7                                                  |
| **Histórico da estrutura**                     | Toda mudança estrutural (gestor/cargo/departamento/filial/salário) é um **evento append-only** (`funcionario_eventos`) — a verdade temporal; o `funcionarios` guarda só o "agora" (cache).         | [06](06-linha-do-tempo.md)                                   |
| **Estruturas antigas preservadas**             | Como os eventos são imutáveis, a estrutura "de antes" é **reconstruível** por data (§11.2); reorganizar hoje não apaga como era ontem.                                                             | §11.2 · [06 §3.1](06-linha-do-tempo.md)                      |

### 13.1 Impactos de uma mudança na estrutura

Mexer no organograma reverbera em vários pontos — o que muda automaticamente e o que **não** existe na Fase 1:

| Impacto                         | O que acontece ao mudar a estrutura                                                                                                                                                   | Fase                                                    |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| **Visibilidade (ACL)**          | A subárvore muda na **próxima query** (CTE ao vivo) — quem ganhou/perdeu subordinados passa a ver/não ver na hora. Sem cache a invalidar.                                             | 1                                                       |
| **Fluxos de aprovação (HE)**    | A aprovação de hora extra segue a **cadeia do organograma** — trocar o gestor muda quem aprova as HE do subordinado.                                                                  | 1 ([07](07-jornada-horas-extras-folha.md))              |
| **Gestores responsáveis**       | Relatórios e filas de trabalho (ex.: atestados pendentes) reescopam pela nova subárvore.                                                                                              | 1–2                                                     |
| **Férias / atestados / faltas** | Aprovação/consulta dessas ausências segue o gestor atual; o histórico do que já foi aprovado **não** muda (append-only).                                                              | 2 ([12](12-ausencias-faltas-atestados-afastamentos.md)) |
| **Avaliações de desempenho**    | Fluxo gestor↔subordinado usaria o organograma — **fora de escopo** (módulo satélite, [09 §8](09-roadmap-fases.md)).                                                                  | satélite                                                |
| **Ponto / espelho**             | Apuração por escala/jornada não depende do gestor; a chefia entra só na aprovação de exceções.                                                                                        | 5 ([09](09-roadmap-fases.md))                           |
| **Relatórios estruturais**      | Headcount por área/centro de custo, vagos/órfãos (§10.1.4) recalculam ao vivo.                                                                                                        | 1                                                       |
| **Notificações**                | **Não há sistema de notificações na Fase 1** — mover alguém **não** dispara aviso automático ao novo gestor/colaborador. Registrado em [PEND-10](13-rastreabilidade-e-pendencias.md). | — (pendência)                                           |
| **Histórico funcional**         | Cada mudança vira evento append-only (sempre).                                                                                                                                        | 1 ([06](06-linha-do-tempo.md))                          |
