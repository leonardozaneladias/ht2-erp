# Multi-empresa & Multi-filial (multi-tenant lógico)

> Várias **empresas** e **filiais** convivem na mesma instância, com **isolamento
> de dados** por empresa, **papéis por empresa** e **branding** que muda conforme
> a empresa ativa. É um multi-tenant **lógico** (single-database), não multi-database.

---

## 1. Conceitos

| Conceito          | Onde vive                                                                                                                                           |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Empresa (tenant)  | tabela `empresas` (`HT2ML\Core\Models\Empresa`)                                                                                                            |
| Filial            | tabela `filiais` (`HT2ML\Core\Models\Filial`), `belongsTo` empresa; toda empresa nasce com a **Matriz**                                                    |
| Acesso do usuário | pivots `admin_user_empresa` (com `todas_filiais`) e `admin_user_filial`                                                                             |
| Contexto ativo    | `HT2ML\Core\Support\Tenancy\TenantContext` (sessão: `tenant.empresa_id` / `tenant.filial_id`) + colunas `admin_users.empresa_ativa_id` / `filial_ativa_id` |

O contexto ativo é hidratado a cada requisição admin autenticada pelo middleware
`HT2ML\Core\Http\Middleware\DefinirContextoTenant`, que revalida o acesso e cai para a
empresa padrão quando necessário. `HT2ML\Core\Support\Tenancy\TenantResolver` decide as
empresas/filiais acessíveis (super-admin enxerga todas as ativas).

A troca de empresa/filial acontece pelo seletor da topbar
(`HT2ML\Core\Livewire\Admin\Tenancy\SeletorEmpresaFilial`), que delega às actions
`DefinirEmpresaAtivaAction` / `DefinirFilialAtivaAction` (persistem coluna + sessão
e invalidam o cache de acesso).

---

## 2. Isolamento de dados — criando um módulo de negócio

Todo registro de negócio deve ter `empresa_id`. Use o trait `BelongsToEmpresa`:

```php
// migration
$table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
$table->index('empresa_id');

// model
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\BelongsToEmpresa;

class Cliente extends Model
{
    use Auditavel;        // auditoria automática (created/updated/deleted com diff)
    use BelongsToEmpresa; // global scope por empresa ativa + auto-preenche no create
}
```

> `Auditavel` é **obrigatório** (arch test). Opcional: defina `rotuloAuditoria(): string`
> no model para um rótulo humano na tela de auditoria (default: `nome`/`name`/`titulo`/`email`).
> Detalhes em `docs/devops/conventions.md` §7.2.

Com isso:

- **Leitura** é filtrada automaticamente pela empresa ativa (global scope `empresa`).
- **Criação** preenche `empresa_id` com a empresa ativa quando não informado.
- **`unique` deve ser por empresa**: `Rule::unique('clientes', 'cpf')->where('empresa_id', $empresaId)`.
- **Escape consciente** (relatórios cross-empresa autorizados): `Cliente::withoutGlobalScope('empresa')`.
- **Sem empresa ativa** (CLI/jobs) o scope não filtra — defina o contexto:
  `app(TenantContext::class)->definirEmpresa($id)` antes de operar, e re-hidrate o
  contexto no `handle()` de jobs (passe `empresa_id`/`filial_id` no payload).

`filial_id` é **opcional**: use em registros de filial (não em dados corporativos da empresa).

---

## 3. RBAC por empresa (dois níveis)

O controle de acesso é customizado (`AccessResolver` lê um snapshot próprio, não o
`$user->can()` do spatie). Os papéis efetivos de um usuário numa empresa são a **união**:

1. **Papéis globais** — atribuídos via spatie (`$user->assignRole(...)`, tabela
   `model_has_roles`). Valem em **todas** as empresas (ex.: `super-admin`, papéis "do grupo").
   Geridos no **formulário de Usuários** e exibidos em leitura no hub.
2. **Papéis por empresa** — tabela `admin_user_empresa_role` (`$user->papeisPorEmpresa()` /
   `$user->rolesNaEmpresa($empresaId)`). Valem só na empresa. Geridos no **hub de Controle de
   Acesso** (`PainelPessoa`), no escopo da **empresa ativa**, via `SyncRolesEmpresaAction`.

O `AccessCache` chaveia o snapshot por `(usuário, empresa ativa)` e une os dois níveis;
`HierarchyResolver` calcula o nível efetivo no contexto da empresa. **super-admin é sempre
global** e imune (bypass no Gate). Papéis protegidos (`config('access.protected_roles')`)
não são atribuíveis no escopo por empresa.

Conceder a um usuário **acesso** a uma empresa (independente de papéis) é feito na aba
**Empresas** do formulário de usuário (`SyncAcessoEmpresaAction`, permissão `empresas.acessos`).

### Filtro multi-empresa nas listagens (trait `FiltraPorMultiEmpresa`)

Por padrão, cada listagem (PowerGrid) mostra **só a empresa ativa** (global scope `empresa`).

A **lixeira** (trait `ComLixeira`) compõe **por fora** deste escopo:
`aplicarLixeira($this->aplicarEscopoMultiEmpresa(Model::query()))` — os global scopes
`empresa` e `SoftDeletingScope` são independentes. Ver [`lixeira.md`](lixeira.md).

> O trait `HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa` dá a usuários autorizados um
> **multiselect de empresa (e filial)** para ver registros de várias empresas/filiais de uma vez,
> com uma coluna **Empresa** (e **Filial**) identificando cada linha.

O recurso aparece somente quando o usuário **tem a permissão global `listagens.multi_empresa`**
**e** é elegível em **2+ empresas**. Caso contrário, a listagem se comporta exatamente como hoje
(só a empresa ativa).

**Segurança (invariantes):**

1. **Elegibilidade = RBAC estrito por empresa.** No multiselect só aparecem empresas onde o
   usuário tem a permissão `listar` do módulo _naquela_ empresa — via
   `AccessResolver::permiteNaEmpresa($user, $ability, $empresaId)`, que monta o snapshot pleno
   de papéis do tenant (global ∪ `admin_user_empresa_role`), **sem** a lente do perfil ativo
   (que vale só para a empresa ativa). super-admin é elegível em tudo.
2. **Escopo da query = `selecionadas ∩ elegíveis`** (e filiais `∩ acessíveis`). A intersecção é
   aplicada no `datasource()` após `withoutGlobalScope('empresa')` — valores de `empresa_id`/
   `filial_id` vindos do cliente **nunca** ampliam o escopo.
3. O **filtro de filial** só existe em models com coluna `filial_id` + relação `filial()`. A
   filial **estreita** dentro das empresas selecionadas; sem empresa selecionada, atua dentro da
   **empresa ativa** (o escopo base segue por empresa — filial não restringe linhas por si só). As
   opções do multiselect são rotuladas como **"Empresa — Filial"** para desambiguar filiais
   homônimas em empresas diferentes.

**Como aplicar numa tabela tenant** (o gerador `make:recurso --tenant` já injeta isto):

```php
use HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa;

final class ExemploTable extends PowerGridComponent
{
    use ExportaPdf;
    use FiltraPorMultiEmpresa;
    use WithExport;

    protected function permissaoListagem(): string { return 'exemplos.listar'; }

    // Opcional: habilita a dimensão filial (model com filial_id + relação filial()).
    // protected function modeloMultiEmpresa(): string { return Exemplo::class; }

    public function datasource(): Builder { return $this->aplicarEscopoMultiEmpresa(Exemplo::query()); }
    public function fields(): PowerGridFields { return $this->camposMultiEmpresa(PowerGrid::fields()->add('id')/* ... */); }
    public function columns(): array { return [...$this->colunasMultiEmpresa(), /* ... */]; }
    public function filters(): array { return [...$this->filtrosMultiEmpresa(), /* ... */]; }
}
```

Como o escopo é aplicado no `datasource()`, a paginação/busca e a **exportação** (Excel/CSV nativo
e PDF) herdam o mesmo filtro automaticamente. No PDF, as colunas **Empresa**/**Filial** são
adicionadas via os helpers `cabecalhosMultiEmpresa()`/`linhaMultiEmpresa()` quando o recurso está
ativo, alinhadas às colunas da tela.

A permissão `listagens.multi_empresa` é única e global (libera o recurso em todas as listagens) —
concedida ao `gestor` no seeder, que recebe também `exemplos.listar` e acesso às
2 empresas de demonstração (assim o filtro fica de fato demonstrável); super-admin já bypassa.

---

## 4. Branding por empresa ativa

`HT2ML\Core\Services\Admin\Settings\BrandingService` resolve, nesta ordem:

1. Empresa ativa (logo, favicon, cores em `empresas`);
2. Settings da instância (`BrandingSettings`);
3. Fallback estático (`config/branding.php`).

Login e Setup Wizard (sem empresa ativa) usam o branding da instância. As cores são
emitidas como CSS custom properties no `<head>` (sem rebuild).

O **título do documento** também é white-label: `BrandingService::tituloPagina($titulo)`
gera "{Página} — {Empresa ativa}" (fallback: nome do sistema), usado pelo `x-admin.layout`.
O favicon segue a mesma precedência (empresa → instância → estático).

### Fonte de verdade: `GeneralSettings` × tabela `empresas`

Os dois coexistem com papéis distintos — não migrar um para o outro:

- **`GeneralSettings`/`BrandingSettings`** = identidade da **instância/sistema**
  (nome do sistema, slogan, branding default, dados do cliente contratante coletados
  no Setup). Vale quando não há empresa no contexto (login, setup, e-mails).
- **`empresas`** = dado **fiscal/operacional do tenant** (CNPJ, endereço, filiais,
  branding específico). Vale dentro do painel com empresa ativa.

A 1ª empresa criada no Setup pode espelhar os dados de `GeneralSettings`, mas a partir
daí evoluem de forma independente.

---

## 5. Bootstrap

`migrate:fresh --seed` e o **Setup Wizard** criam a 1ª empresa + filial Matriz e vinculam
o super-admin (definindo-a como ativa). Em uma instalação nova, o Setup coleta os dados do
cliente, cria a empresa e o primeiro super-admin já com acesso.

---

## 6. Gestão de Empresas/Filiais

Módulo em `/admin/empresas` (`App\Livewire\Admin\Empresas\*`), permissões `empresas.*`
(catálogo em `config/access.php`). O CRUD de empresa inclui dados cadastrais e a paleta de
cores de branding; toda empresa criada nasce com a filial Matriz.
