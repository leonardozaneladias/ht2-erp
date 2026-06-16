# 05 — Organograma e ACL Hierárquica

> Como o módulo de RH decide **quem enxerga quais funcionários**. A resposta não é uma permissão só: é a **interseção de três eixos ortogonais** — tenant (empresa), RBAC (a ação) e organograma (a subárvore de subordinados). Este documento descreve o princípio, o vínculo `Funcionario↔AdminUser`, a hierarquia por `gestor_id`, a subárvore recursiva (WITH RECURSIVE), o empacotamento em trait + serviço, a matriz de casos, os edge cases, o self-service do colaborador e a tela de organograma.
>
> Pacote: `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · `packages/modulo-rh/` · views `rh::` · banco **PostgreSQL 16** · multi-tenant lógico por `empresa_id`. O **schema é definido em [01](01-modelo-de-dominio.md)** (fonte de verdade); aqui só consumimos os nomes de tabelas/colunas/permissões de lá e descrevemos a mecânica de acesso.

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
| **Tenant**      | "Este registro é da empresa em que estou?"                     | Global scope `empresa` do trait `App\Models\Concerns\BelongsToEmpresa`, alimentado por `App\Support\Tenancy\TenantContext` | **Quais linhas** (por `empresa_id`)       |
| **RBAC**        | "Eu posso executar **esta ação** (listar/ver/editar/aprovar)?" | `Gate` → `App\Services\Admin\AccessResolver` (super-admin bypass · deny > grant > role)                                    | **O verbo** (a ação inteira, não a linha) |
| **Organograma** | "Esta pessoa está **na minha subárvore** de subordinados?"     | Trait `HT2ERP\Rh\Models\Concerns\VisivelNaHierarquia` + serviço `EscopoOrganograma` (CTE recursiva sobre `gestor_id`)      | **Quais linhas** (por posição na árvore)  |

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

A FK e o índice nascem numa migration **dentro do pacote** (`packages/modulo-rh/database/migrations`), carregada via `loadMigrationsFrom` — o core não recebe nenhuma coluna nova. A relação inversa `AdminUser::funcionario(): HasOne` é declarada num model do pacote (ou via macro/extensão), sem migration no core. Esse é o padrão aditivo do [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md): tudo do RH se acopla por fora.

### 2.2 As duas direções são opcionais (0..1 ↔ 0..1)

O vínculo é **1:1, mas opcional dos dois lados**:

- **Nem todo login é funcionário.** O super-admin, um contador externo, um usuário técnico, um sócio que só consulta relatórios — todos têm `AdminUser` sem `Funcionario` correspondente.
- **Nem todo funcionário tem login.** Um operário de chão de fábrica, um terceirizado, alguém recém-admitido cujo acesso ainda não foi provisionado — existe como `Funcionario`, mas `admin_user_id` é `NULL`. Ele aparece no organograma e nas folhas, mas não loga.

A unicidade `(empresa_id, admin_user_id)` garante que, **dentro de uma empresa**, um login está ligado a no máximo um funcionário. Mas o mesmo `AdminUser` pode ser funcionário em **empresas distintas** (um diretor que é funcionário da Matriz e também da Filial S.A.) — por isso a unicidade é por `empresa_id`, não global.

### 2.3 O serviço `FuncionarioAtual` — "qual funcionário sou eu nesta empresa?"

A resolução é encapsulada no serviço `HT2ERP\Rh\Support\Organograma\FuncionarioAtual`. Ele recebe o usuário logado e a empresa ativa e devolve o `Funcionario` correspondente (ou `null`).

Como o mesmo login pode ser funcionário em empresas diferentes, **o cache é por request e chaveado por `(admin_user_id, empresa_id)`** — espelhando a disciplina de cache do `AccessResolver`/`AccessCache` do core, que também memoiza por usuário e, quando preciso, por empresa.

```php
<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Support\Organograma;

use App\Models\AdminUser;
use App\Support\Tenancy\TenantContext;
use HT2ERP\Rh\Models\Funcionario;
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

- **Um gestor direto por pessoa.** `gestor_id` é escalar: cada funcionário aponta para no máximo **um** gestor. Múltiplos gestores (matricial) fica para evolução futura (§9.3).
- **Intra-empresa.** O gestor de um funcionário **tem de ser da mesma empresa**. Isso é validado **na escrita** (a Action que define o gestor rejeita um `gestor_id` de outra empresa) — o banco não tem como impor "mesma empresa" numa FK simples, então a guarda é de aplicação, reforçada pelo fato de o global scope só oferecer candidatos da empresa ativa na UI.
- **`ON DELETE RESTRICT` na prática.** O schema usa `nullOnDelete` físico para não quebrar integridade em force-delete, mas a **regra de negócio é restritiva**: não se desliga/exclui um gestor sem antes **reatribuir seus subordinados** (§7.3). Em outras palavras, a operação de exclusão é bloqueada pela Action enquanto houver subordinados pendurados — comportamento equivalente a `RESTRICT` no nível de domínio.
- **Topo da árvore.** Quem não tem gestor (`gestor_id IS NULL`) é raiz — tipicamente a diretoria. Ver edge cases (§7.1).

### 3.1 Posição no organograma ≠ papel RBAC

Ponto que costuma confundir e merece destaque:

> **"Gestor", "líder", "preposto", "coordenador", "colaborador" são POSIÇÕES no organograma — não são papéis (roles) de RBAC.**

A visibilidade de subárvore **decorre da posição** de uma pessoa na árvore `gestor_id`, não de um papel spatie atribuído ao login. Um "gestor" é simplesmente alguém que **tem subordinados apontando para ele** via `gestor_id`. Se amanhã esse mesmo funcionário deixar de ter subordinados, ele vira folha — sem nenhuma mudança de role. O RBAC (roles/permissions) define **o que a pessoa pode fazer** (listar? aprovar HE? ver CID?); o organograma define **sobre quem**. Os dois se cruzam, mas vivem em planos diferentes:

- As **funções** do RH (líder, preposto, supervisor…) modeladas no catálogo `funcoes` + pivot `funcionario_funcao` ([01 §3 A2/A3](01-modelo-de-dominio.md)) são **vocabulário de negócio/rotulagem**, também ortogonais ao RBAC. Elas descrevem responsabilidades, não concedem acesso por si.
- O acesso é sempre a interseção RBAC × Tenant × Organograma do §1.

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

A **closure table fica registrada como evolução de performance** (§9.4): se algum cliente atingir volume/latência que justifique leitura O(1), ela pode ser introduzida como **cache** da subárvore **sem mudar a API do trait** — `EscopoOrganograma` passaria a consultar a tabela materializada em vez da CTE, e o resto do módulo não percebe. A decisão e o trade-off completos estão no [ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md).

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

namespace HT2ERP\Rh\Models\Concerns;

use HT2ERP\Rh\Support\Organograma\EscopoOrganograma;
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
    use \App\Models\Concerns\BelongsToEmpresa;   // scope 'empresa'  (tenant)
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

namespace HT2ERP\Rh\Support\Organograma;

use App\Models\AdminUser;
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

Espelhando o `withoutGlobalScope('empresa')` do core e o padrão de troca de scope visto em `App\Livewire\Concerns\FiltraPorMultiEmpresa` (que faz `withoutGlobalScope('empresa')` e reaplica `whereIn(empresa_id, …)` só sobre a interseção autorizada):

| Escape                                           | Efeito                                                                         | Quando usar                                                                                                                                                          |
| ------------------------------------------------ | ------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Funcionario::withoutGlobalScope('organograma')` | Remove **só** o eixo organograma → vê toda a empresa (tenant continua valendo) | Telas/relatórios de quem tem `rh.funcionarios.ver_todos`; o multi-empresa do core já segue exatamente esse padrão de tirar um scope e reaplicar um filtro autorizado |
| `Funcionario::withTrashed()`                     | Inclui registros na lixeira (`deleted_at`)                                     | Tela de lixeira (`ComLixeira`), restauração, auditoria                                                                                                               |
| `Funcionario::withoutGlobalScope('empresa')`     | Remove o eixo tenant                                                           | **Excepcional**, só relatórios cross-empresa autorizados — e ainda assim a CTE confina à empresa do "eu"                                                             |

> A regra de uso: **só desligue um scope quando o RBAC já autorizou a visão ampliada.** O padrão correto não é "tirar o scope e mostrar tudo", e sim "tirar o scope e reaplicar a fronteira que o RBAC permite" — exatamente como `FiltraPorMultiEmpresa` faz com `permiteNaEmpresa()` (§6).

---

## 6. Interação com o filtro multi-empresa do core

O eixo tenant não é necessariamente "uma empresa só". O core já oferece, via `App\Livewire\Concerns\FiltraPorMultiEmpresa`, um filtro que permite a um usuário com a capability `listagens.multi_empresa` (e acesso a 2+ empresas) **incluir outras empresas** numa listagem PowerGrid — sempre limitado pelo RBAC estrito por empresa (`AccessResolver::permiteNaEmpresa($user, $abilityListar, $empresaId)`), com a interseção `selecionadas ∩ elegíveis` blindando contra injeção de `empresa_id` pelo cliente.

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
| 8.5 | **Múltiplos gestores** (matricial)                            | **Fora de escopo na Fase 1** — `gestor_id` é escalar (1 gestor direto). Evolução futura via pivot (§9.3).                                                                                                                                                                                                                                                                                                      |
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

### 10.1 A tela de organograma

A visualização em árvore do organograma é uma **tela custom** — **não há componente nativo** de árvore no catálogo Inspinia/shared para isso.

- **Construção**: componente **Livewire + Alpine** (`OrganogramaView`, citado em [02 §B3](02-fase-1-blueprint.md)) que **renderiza a partir de `gestor_id`** — monta a hierarquia (a mesma CTE da §4, ou um carregamento de `(id, nome, cargo, gestor_id)` da empresa expandido no cliente) e desenha os nós com expand/collapse via Alpine (interação puramente visual, sem ida ao servidor por nó).
- **Escopo**: a árvore exibida respeita os mesmos três eixos — quem tem `rh.organograma.ver` mas não `ver_todos` enxerga **sua subárvore** como organograma; com `ver_todos`, a empresa inteira. A permissão dedicada `rh.organograma.ver` ([02 §B3](02-fase-1-blueprint.md)) controla o acesso à tela.
- **Cada nó**: nome, cargo (e `cargo_nivel` para cor/ordenação), foto (URL assinada — disco privado, [03 §10](03-cadastro-pessoa-documentos.md)), e atalho para o cadastro (sujeito à própria ACL). Editar a estrutura (arrastar para trocar gestor) reusa a **Action de atribuição de gestor com detecção de ciclo** (§8.2) — a UI nunca grava `gestor_id` sem passar por ela.
- Como não existe componente pronto, ele entra como item **custom** a ser componentizado seguindo o fluxo do catálogo Inspinia (documentar → registrar → criar `.blade.php`), sem `<select>` nativo e sem CSS custom (Tailwind).

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

---

## 12. Extensões futuras

Todas mantêm a **API do trait/serviço estável** — o resto do módulo não percebe a evolução. Detalhes no [ADR-RH-003](adrs/ADR-RH-003-acl-hierarquica-organograma.md).

| #    | Extensão                                                                          | Como entra (sem quebrar)                                                                                                                                                                                                                |
| ---- | --------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 12.1 | **Visibilidade lateral** (ver colegas do mesmo departamento, não só subordinados) | Nova capability `rh.funcionarios.ver_lateral`: o scope `organograma` passa a ser a **união** "subárvore ∪ mesmo `departamento_id`". A composição AND com tenant não muda; só o conjunto de IDs do `whereIn` cresce.                     |
| 12.2 | **Delegação temporária** (cobrir férias de um gestor)                             | Tabela de delegação (`delegante`, `delegado`, vigência). O `EscopoOrganograma` resolve **múltiplas raízes** e a CTE roda com `WHERE id IN (raízes)` no âncora → a subquery vira união de subárvores. Vigência filtra delegações ativas. |
| 12.3 | **Múltiplos gestores** (matricial)                                                | `gestor_id` escalar evolui para pivot `funcionario_gestor` (N:N). A recursão da CTE passa a percorrer o **pivot** em vez da self-FK; a interface do scope (`whereIn(id, subárvore)`) é a mesma.                                         |
| 12.4 | **Closure table como cache**                                                      | Materializa `(ancestral_id, descendente_id, profundidade)` para leitura O(1). `EscopoOrganograma` passa a consultar a tabela em vez da CTE, **sem mudar a assinatura** — ativável quando/se o volume exigir (§4.1).                     |

O fio condutor de todas as extensões: **elas mexem só no conjunto de IDs que o eixo organograma produz** (`whereIn(id, …)`). Os eixos tenant e RBAC, e a forma como os três se combinam por AND, permanecem exatamente como descrito aqui — é o que torna a ACL extensível sem reescrita.
