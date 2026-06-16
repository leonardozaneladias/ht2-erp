# 08 — Arquitetura Técnica e Guia de Implementação

Relacionados: [01](01-modelo-de-dominio.md) (fonte de verdade de schema) · [02](02-fase-1-blueprint.md) (blocos B1–B7) · [05](05-organograma-acl-hierarquica.md) (organograma/ACL) · [07](07-jornada-horas-extras-folha.md) (jornada/HE/folha) · [09](09-roadmap-fases.md) (roadmap de longo prazo / por que a fundação é imutável) · [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)

> **O que este documento é.** O **guia de implementação** transversal da Fase 1 do módulo de RH: como erguer o pacote, gerar cada CRUD com o gerador real do core, o que é **GERADO** vs. o que se **ESCREVE À MÃO**, as camadas por recurso, a ordem das migrations, seeds/provisionamento, testes (Pest), qualidade e a sequência mapeada aos blocos B1..B7 do [02](02-fase-1-blueprint.md).
>
> **O que este documento não é.** Não redefine schema (qualquer divergência de coluna/enum/tabela resolve-se **primeiro** no [01](01-modelo-de-dominio.md)) nem detalha a mecânica de organograma/HE/folha (vive em [05](05-organograma-acl-hierarquica.md) e [07](07-jornada-horas-extras-folha.md)).
>
> Pacote: `ht2erp/modulo-rh` · namespace `HT2ERP\Rh\` · `packages/modulo-rh/` · views `rh::` · banco **PostgreSQL 16** · **aditivo ao core** (nunca edita o boilerplate — [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).

---

## 0. Princípios de execução (resumo operacional)

Verificados no código real do gerador (`app/Console/Commands/MakeModuloCommand.php`, `MakeModuloPacoteCommand.php`), nos stubs (`stubs/modulo-pacote/*`) e na infraestrutura de wiring (`app/Support/Modules/ModuleRegistry.php`, `app/Support/Generator/ModuloPacote.php`):

1. **Gerador, não mão.** Toda a casca CRUD nasce de `make:modulo-pacote` + `make:modulo --module=Rh`. A mão entra **depois**, customizando migration/model/Rules/Actions para o que o [01](01-modelo-de-dominio.md) exige além do scaffold.
2. **Aditivo ao core ([ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)).** Rotas via `ModuleRegistry`, permissões mescladas em `config('access.modules')`, menu mesclado em `config('admin-menu')`, Livewire/Policies registrados **explicitamente** no `RhServiceProvider`. Zero edição de arquivos do boilerplate.
3. **Multi-tenant lógico.** Toda tabela usa `App\Models\Concerns\BelongsToEmpresa` (global scope `empresa` + auto-fill no `creating`); `unique` sempre por empresa (`Rule::unique()->where('empresa_id', …)` **+** índice único parcial `WHERE deleted_at IS NULL`).
4. **Lixeira + LGPD por padrão.** `SoftDeletes`/`UsaSoftDeletes` + `ComLixeira` onde aplicável; PII em `atributosNaoAuditados()`; `cid`/financeiro `encrypted`; foto/documentos em disco privado.
5. **Gate de qualidade por bloco.** `./vendor/bin/pint` · `npx prettier --write packages/modulo-rh/` · `./vendor/bin/phpstan analyse` (level 6) · `php artisan test`. Pós-instalação/migração: `php artisan migrate && php artisan access:sync && php artisan cache:clear`.

> **Nota de nomenclatura (catálogo de departamento).** Os documentos de planejamento ([01](01-modelo-de-dominio.md)/[02](02-fase-1-blueprint.md)) são a **fonte de verdade** e nomeiam o catálogo de departamentos como tabela `departamentos`/model `Departamento`. Os testes-semente já presentes no repo (`tests/Feature/Rh/RhLixeiraTest.php`) referenciam `HT2ERP\Rh\Models\Departamento` e a key de menu `rh-departamentos` (ver `App\Actions\Admin\Menu\AplicarMenuPadraoAction`). **Decida a nomenclatura final no [01](01-modelo-de-dominio.md) antes de gerar** e mantenha test + menu coerentes. Este guia usa `Departamento` (seguindo o blueprint); onde você optar por `Departamento`, troque o nome do recurso no `make:modulo` e os identificadores correspondentes — o resto do fluxo é idêntico.

---

## 1. Bootstrap do pacote `[B1]`

### 1.1 Comando

```bash
php artisan make:modulo-pacote Rh
```

O `MakeModuloPacoteCommand` resolve a identidade do pacote via `ModuloPacote::paraNome('Rh')` (a partir de `config/modulos.php`: `vendor=ht2erp`, `namespace=HT2ERP`, `path=packages`, `prefixo_pacote=modulo-`) e gera, a partir de `stubs/modulo-pacote/*`:

- `src/RhServiceProvider.php` (de `service-provider.stub`)
- `config/rh.php` (de `config.stub` — âncoras de permissões e menu)
- `routes/admin.php` (de `routes.stub` — âncora de rotas)
- `README.md`, `.gitignore`
- diretórios vazios com `.gitkeep`: `database/migrations`, `database/factories`, `resources/views`, `tests`
- `composer.json` do pacote (PSR-4 `HT2ERP\Rh\` → `src/`, factories → `database/factories/`, `autoload-dev` `HT2ERP\Rh\Tests\` → `tests/`, `extra.laravel.providers` apontando para `HT2ERP\Rh\RhServiceProvider`)

E **registra o path repository** no `composer.json` raiz (idempotente):

```json
{
    "repositories": [{ "type": "path", "url": "packages/*", "options": { "symlink": true } }]
}
```

### 1.2 Instalação (symlink, desenvolvimento dentro do boilerplate)

```bash
composer require "ht2erp/modulo-rh:@dev"
```

O Composer resolve via symlink; o auto-discovery do Laravel (`extra.laravel.providers`) carrega o `RhServiceProvider`. Em seguida:

```bash
php artisan access:sync && php artisan cache:clear
```

### 1.3 Estrutura de pastas resultante (alvo da Fase 1 completa)

A casca nasce mínima; abaixo a árvore **após** B1–B7 (o `make:modulo --module=Rh` preenche `src/`, `database/`, `resources/`, `tests/` recurso a recurso). Caminhos de pacote confirmados no `mapaArquivosPacote()` do `MakeModuloCommand`:

```
packages/modulo-rh/
├── composer.json
├── README.md
├── .gitignore
├── config/
│   └── rh.php                       # permissões + menu (âncoras make:modulo)
├── routes/
│   └── admin.php                    # require DENTRO do grupo /admin do core
├── src/
│   ├── RhServiceProvider.php        # wiring: rotas/permissões/menu/Livewire/Policy
│   ├── Models/                      # Departamento, Funcao, TipoDocumentoRh, ... Funcionario, HoraExtra (+ filhas, eventos)
│   ├── Enums/                       # StatusFuncionario, TipoVinculo, DiaSemana, ... (ver 01 §4)
│   ├── DTOs/                        # DepartamentoDTO, FuncionarioDTO, HoraExtraDTO, ...
│   ├── Http/Requests/               # Store/Update*Request + *Rules
│   ├── Actions/                     # Create*/Update* + Actions de negócio escritas à mão
│   ├── Services/                    # *Service (API-ready) + EscopoOrganograma, CalculoHoraExtra, ...
│   ├── Policies/                    # DepartamentoPolicy, FuncionarioPolicy, HoraExtraPolicy, ...
│   ├── Livewire/
│   │   ├── Departamentos/                 # IndexDepartamento, FormDepartamento, DepartamentoTable
│   │   ├── Funcionarios/            # IndexFuncionario, FormFuncionario, FuncionarioTable
│   │   ├── HorasExtras/             # IndexHoraExtra, FormHoraExtra, HoraExtraTable
│   │   └── ...                      # demais recursos
│   ├── Support/                     # ProvisionarCatalogosRh (à mão), traits (VisivelNaHierarquia)
│   └── Database/Seeders/            # TabelasLegaisSeeder (à mão)
├── database/
│   ├── migrations/                  # carregadas por loadMigrationsFrom (ordem: ver §5)
│   └── factories/                   # DepartamentoFactory, FuncionarioFactory, ...
├── resources/
│   └── views/livewire/             # views rh:: (index-*, form-*, _acoes, _lixeira-toggle, ...)
└── tests/
    └── Feature/                     # CRUD + tenant scope + policy + regras (Pest)
```

> Namespaces (de `ModuloPacote`): models `HT2ERP\Rh\Models\*`, enums `HT2ERP\Rh\Enums\*`, Livewire `HT2ERP\Rh\Livewire\{Plural}\*`, policies `HT2ERP\Rh\Policies\*`. As views resolvem por `rh::livewire.…` (namespace `rh` registrado por `loadViewsFrom`).

---

## 2. Geração dos CRUDs `[B1–B7]`

Cada recurso geral roda `php artisan make:modulo <Recurso> --module=Rh --tenant --fields="..."`. O `--module=Rh` ativa o **modo pacote**: arquivos vão para `packages/modulo-rh/`, e o comando **integra ao pacote** (não ao core) — injeta rotas em `routes/admin.php` do pacote, permissões+menu em `config/rh.php`, e registra `Livewire::component()` + `Gate::policy()` no `RhServiceProvider` (ver `integrarNoPacote()`).

### 2.1 Tipos de campo suportados (`--fields`), ancorados no `CampoModulo` real

Tokens: `nome:tipo:modificador1:modificador2`. Vírgula separa campos; `enum(a|b|c)`/`multiselect(a|b|c)` usam `|` internamente.

| Tipo                                       | Coluna gerada                     | Cast / validação                             |
| ------------------------------------------ | --------------------------------- | -------------------------------------------- |
| `string` (default)                         | `string(...)`                     | `'string','max:255'`                         |
| `text` / `richtext`                        | `text`                            | `'string'` (richtext sanitizado)             |
| `integer`                                  | `integer`                         | `'integer'`                                  |
| `money`                                    | `integer` (centavos)              | `MoneyCast` · `'integer','min:0'`            |
| `decimal`                                  | `decimal(10,2)`                   | `'decimal:2'`                                |
| `boolean`                                  | `boolean` default false           | `'boolean'`                                  |
| `date` / `datetime`                        | `date` / `timestamp`              | `'date'`                                     |
| `email`                                    | `string`                          | `'email:rfc','max:191'`                      |
| `url`                                      | `string`                          | `'url','max:255'`                            |
| `cnpj` / `cpf` / `cep` / `phone` / `color` | `string(18/14/9/20/9)`            | regra dedicada (`new \App\Rules\Cpf()` etc.) |
| `enum(a\|b\|c)`                            | `string` (+ CHECK — ajuste à mão) | enum cast (se `status`: enum de status)      |
| `multiselect(a\|b\|c)`                     | `json`                            | `'array'`                                    |

Modificadores: `nullable`, `unique`, `aba(Rótulo)` (agrupa o campo numa aba do form). Convenção: **um** campo `status:enum(...)` por recurso vira o enum de status (`statusEnumShort()`), com badge e default.

> **PII / dinheiro / tempo.** O gerador cobre `money` (centavos via `MoneyCast`). Para **minutos** (`*_minutos`), **TIME**, **JSONB** (`snapshot_*`), `encrypted` e CHECKs de enum, gere com o tipo mais próximo (`integer`, `string`, `multiselect`/`text`) e **ajuste a migration/casts à mão** conforme o [01](01-modelo-de-dominio.md). Upload de arquivo (foto/anexo) **não** é scaffoldado — ver [03](03-cadastro-pessoa-documentos.md).

### 2.2 Comandos por recurso (recursos geráveis)

> Os `--fields` abaixo são o **enxoval inicial** (status + colunas escalares simples), suficiente para o gerador produzir migration/model/DTO/Rules/Livewire/views/teste coerentes. FKs (`empresa_id` é automático via `--tenant`; `departamento_id`, `cargo_id`, `funcionario_id`, `*_id`), uniques parciais por tenant, CHECKs de enum, JSONB e colunas de minutos/TIME entram **na customização** pós-geração (ver §2.3). Sempre confira o resultado contra o [01 §3](01-modelo-de-dominio.md).

```bash
# --- B1 · catálogos tenant ---

# departamentos (model Departamento) — hierárquico; departamento_pai_id/responsavel_funcionario_id à mão
php artisan make:modulo Departamento --module=Rh --tenant \
  --fields="codigo:string:nullable, nome:string, descricao:text:nullable, ordem:integer:nullable, ativo:boolean" \
  --menu="Departamentos" --menu-icon="tabler--sitemap"

# funcoes (model Funcao)
php artisan make:modulo Funcao --module=Rh --tenant \
  --fields="nome:string, descricao:text:nullable, cor:color:nullable, ativo:boolean" \
  --menu="Funções" --menu-icon="tabler--badges"

# tipos_documento (model TipoDocumentoRh — nome deliberado p/ não colidir com App\Enums\TipoDocumento)
php artisan make:modulo TipoDocumentoRh --module=Rh --tenant \
  --fields="codigo:string, nome:string, exige_numero:boolean, exige_validade:boolean, exige_orgao_emissor:boolean, exige_arquivo:boolean, sensivel_lgpd:boolean, ativo:boolean" \
  --menu="Tipos de Documento" --menu-icon="tabler--file-text"

# tipos_afastamento (model TipoAfastamento)
php artisan make:modulo TipoAfastamento --module=Rh --tenant \
  --fields="codigo:string, codigo_esocial:string:nullable, nome:string, remunerado:boolean, conta_como_falta:boolean, suspende_contrato:boolean, exige_atestado:boolean, ativo:boolean" \
  --menu="Tipos de Afastamento" --menu-icon="tabler--calendar-off"

# escalas (model Escala) — cabeçalho; escala_dias/escala_funcionario são à mão (B5)
php artisan make:modulo Escala --module=Rh --tenant \
  --fields="nome:string, descricao:text:nullable, tipo:enum(semanal|doze_trinta_seis|revezamento|parcial|personalizada), carga_semanal_minutos:integer:nullable, horas_mensais_divisor:integer, ativo:boolean" \
  --menu="Escalas" --menu-icon="tabler--clock-hour-8"

# rubricas (model Rubrica) — fundação de folha
php artisan make:modulo Rubrica --module=Rh --tenant \
  --fields="codigo:string, codigo_esocial:string:nullable, nome:string, natureza:enum(provento|desconto|informativa), incide_inss:boolean, incide_fgts:boolean, incide_irrf:boolean, referencia_he_tipo:string:nullable, ativo:boolean" \
  --menu="Rubricas" --menu-icon="tabler--receipt"

# --- B2 · agregado-raiz ---

# funcionarios (model Funcionario) — núcleo eSocial-ready; status é o enum de status
php artisan make:modulo Funcionario --module=Rh --tenant \
  --fields="nome:string, nome_social:string:nullable:aba(Dados Pessoais), cpf:cpf:aba(Dados Pessoais), data_nascimento:date:nullable:aba(Dados Pessoais), sexo:enum(masculino|feminino|outro|nao_informado):nullable:aba(Dados Pessoais), matricula:string:aba(Contratação), data_admissao:date:aba(Contratação), data_demissao:date:nullable:aba(Contratação), tipo_vinculo:enum(clt|pj|estagio|temporario|autonomo|aprendiz|terceirizado):aba(Contratação), regime_trabalho:enum(mensalista|horista|comissionado|diarista):aba(Contratação), salario_base_centavos:money:nullable:aba(Contratação), status:enum(ativo|experiencia|afastado|ferias|desligado):aba(Contratação)" \
  --menu="Funcionários" --menu-icon="tabler--user"

# --- B6 · operacional ---

# horas_extras (model HoraExtra) — SEM soft-delete (cancelamento = status)
php artisan make:modulo HoraExtra --module=Rh --tenant --sem-soft-delete \
  --fields="data:date, minutos:integer, tipo:enum(he_50|he_100|noturna|dsr), justificativa:text:nullable, status:enum(rascunho|lancada|aprovada|rejeitada|paga|cancelada)" \
  --menu="Horas Extras" --menu-icon="tabler--clock-plus"
```

Flags úteis (de `MakeModuloCommand`): `--sem-soft-delete` (recursos append-only/ciclo-por-status), `--skip-menu`, `--menu`/`--menu-icon`, `--force` (re-gera; cuidado com customizações já feitas). O resumo do comando lembra os próximos passos (Pint/Prettier, revisar migration, `migrate`, `access:sync`).

### 2.3 GERADO vs ESCRITO À MÃO

**Gerado pelo `make:modulo`** (para cada recurso da §2.2): migration base, factory, model (`BelongsToEmpresa` + `Auditavel` + `SoftDeletes`/`UsaSoftDeletes`), enum de status, DTO `readonly`, `Rules` + `Store/UpdateRequest`, `Create/UpdateAction` (`execute()`), `Service`, `Policy`, Livewire `Index/Form/Table` (PowerGrid com `ComLixeira`/`ExportaPdf`), views `rh::`, teste `*CrudTest`; **e o wiring no pacote**: rotas, permissões CRUD (`rh.<recurso>.{listar,criar,editar,deletar,restaurar,excluir_permanente}`) + item de menu em `config/rh.php`, `Livewire::component()`/`Gate::policy()` no provider.

**Escrito à mão** (o gerador não cobre — é onde mora a regra de negócio do RH):

| À mão                                                                                                                                                                    | Onde                                                 | Bloco       | Referência                                    |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------- | ----------- | --------------------------------------------- |
| Tabelas-filhas de `Funcionario` (contatos, endereços, bancário, dependentes, documentos)                                                                                 | migrations + models + repeaters no `FormFuncionario` | B2          | [03](03-cadastro-pessoa-documentos.md)        |
| FKs/uniques parciais/CHECKs do [01](01-modelo-de-dominio.md) (`gestor_id <> id`, `data_demissao >= data_admissao`, `(empresa_id, cpf)` parcial, …)                       | migration (pós-geração)                              | todos       | [01 §3/§7](01-modelo-de-dominio.md)           |
| `funcionario_eventos` (append-only, JSONB) + Action transacional                                                                                                         | migration + Action de evento                         | B4          | [06](06-linha-do-tempo.md)                    |
| `funcionario_afastamentos` com `cid` `encrypted` + `rh.afastamentos.ver_cid`                                                                                             | migration + model + Policy                           | B4          | [01 §8](01-modelo-de-dominio.md)              |
| Trait `VisivelNaHierarquia` + serviço `EscopoOrganograma` (CTE recursiva)                                                                                                | `src/Support` + `src/Services`                       | B3          | [05](05-organograma-acl-hierarquica.md)       |
| Vínculo `AdminUser::funcionario()` (HasOne) **via model do pacote**, sem migration no core                                                                               | model do pacote                                      | B3          | [05](05-organograma-acl-hierarquica.md)       |
| `escala_dias` (editor 7×turnos) + `escala_funcionario` (vigência, não-sobreposição)                                                                                      | migrations + Action                                  | B5          | [07](07-jornada-horas-extras-folha.md)        |
| Cálculo de HE + snapshot imutável (`percentual_aplicado_bps`, `valor_calculado_centavos`, `snapshot_calculo`) + máquina de estados                                       | Action/Service de cálculo + transições               | B6          | [07](07-jornada-horas-extras-folha.md)        |
| Self-service (portal do colaborador) escopado ao `funcionario` do `AdminUser`                                                                                            | Livewire read-only + Policy `rh.self.ver`            | B3          | [05](05-organograma-acl-hierarquica.md)       |
| `ProvisionarCatalogosRh` (idempotente, por empresa)                                                                                                                      | `src/Support` (análogo a `AplicarMenuPadraoAction`)  | B1          | §6                                            |
| `tabelas_legais` (referência por vigência) + seed                                                                                                                        | migration + `Database/Seeders`                       | B7          | [07 §Folha](07-jornada-horas-extras-folha.md) |
| Permissões especiais (`rh.funcionarios.ver_todos`, `rh.afastamentos.ver_cid`, `rh.horas_extras.{aprovar,rejeitar,...}`, `rh.self.ver`, `rh.tabelas_legais.{listar,ver}`) | `config/rh.php` (à mão, fora das âncoras CRUD)       | B3/B4/B6/B7 | [02](02-fase-1-blueprint.md)                  |

---

## 3. ServiceProvider do pacote (`RhServiceProvider`)

Gerado de `stubs/modulo-pacote/service-provider.stub`. O esqueleto abaixo reflete o stub real (`mergeConfigFrom` no `register()`, rotas via `ModuleRegistry`, `loadViewsFrom`/`loadMigrationsFrom` + merges no `boot()`), com os registros explícitos de Livewire/Policy que o `make:modulo` injeta na âncora à medida que cada recurso é gerado.

```php
<?php

declare(strict_types=1);

namespace HT2ERP\Rh;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

final class RhServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rh.php', 'rh');

        // Registrado no register() para o callback existir ANTES do load de
        // routes/admin.php do core. O require roda DENTRO do grupo /admin, então
        // as rotas do RH herdam prefixo /admin, name "admin." e o middleware
        // (tenant, 2FA, inatividade) sem duplicá-lo.
        ModuleRegistry::routes(function (): void {
            $rotas = __DIR__ . '/../routes/admin.php';
            if (is_file($rotas)) {
                require $rotas;
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rh');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/rh.php' => config_path('rh.php'),
        ], 'rh-config');

        $this->contribuirPermissoes(); // merge config('rh.permissoes') → config('access.modules')['negocio']
        $this->contribuirMenu();       // merge config('rh.menu')        → seção 'negocio' de config('admin-menu')

        // make:modulo registra os componentes Livewire e as policies do módulo acima desta linha
        // Exemplos (injetados pelo gerador, recurso a recurso):
        // \Livewire\Livewire::component('rh.departamentos.index', \HT2ERP\Rh\Livewire\Departamentos\IndexDepartamento::class);
        // \Livewire\Livewire::component('rh.departamentos.form',  \HT2ERP\Rh\Livewire\Departamentos\FormDepartamento::class);
        // \Livewire\Livewire::component('rh-departamentos-table', \HT2ERP\Rh\Livewire\Departamentos\DepartamentoTable::class);
        // \Illuminate\Support\Facades\Gate::policy(\HT2ERP\Rh\Models\Departamento::class, \HT2ERP\Rh\Policies\DepartamentoPolicy::class);
    }
}
```

Pontos de integração (sem tocar o core — [ADR-0015](../../../architecture/adrs/ADR-0015-modulos-pacotes-composer.md)):

- **Rotas** — `App\Support\Modules\ModuleRegistry::routes(...)` acumula closures; o grupo autenticado de `routes/admin.php` (core) itera `routeCallbacks()` e dá `require` em `packages/modulo-rh/routes/admin.php` **dentro** da stack admin. O arquivo de rotas usa prefixos/names **relativos** (ex.: `Route::prefix('rh/departamentos')->name('rh.departamentos.')`).
- **Permissões** — `contribuirPermissoes()` faz `array_merge_recursive` de `config('rh.permissoes')` em `config('access.modules')['negocio']`. Assim `access:sync`, a matriz de acesso e o `RolePermissionSeeder` enxergam as `rh.*`.
- **Menu** — `contribuirMenu()` mescla `config('rh.menu')` nos `items` da seção `negocio` de `config('admin-menu')`. Keys estáveis → personalização do cliente (`MenuPersonalizacao`) sobrevive a updates. O `AplicarMenuPadraoAction` do core **reposiciona** os itens RH (keys `rh-departamentos`, `rh-funcionarios`) para o grupo "RH" da seção **Tabelas Auxiliares** — use keys de menu coerentes com esse Action.
- **Livewire/Policy** — registrados **explicitamente** no `boot()` (não há auto-discovery fora de `App\`). O `make:modulo` injeta `Livewire::component('rh.<recurso>.index|form', ...)`, `Livewire::component('<tag-da-table>', ...)` e `Gate::policy(Model::class, Policy::class)` na âncora.

---

## 4. Camadas por recurso (padrão do core)

Mesma stack do módulo **Exemplo** (`app/Models/Exemplo.php`, `app/Livewire/Admin/Exemplos/*`), agora dentro de `HT2ERP\Rh\`. Cada recurso CRUD do RH replica:

| Camada                         | Responsabilidade                                                                                                                                  | Exemplo no RH                                                                                                     |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| **Model**                      | Eloquent + traits `Auditavel` + `BelongsToEmpresa` + `SoftDeletes` (impl. `UsaSoftDeletes`), `casts()`, relações, `atributosNaoAuditados()` (PII) | `HT2ERP\Rh\Models\Funcionario` (casts de enums/datas/`MoneyCast`; PII fora de auditoria)                          |
| **Enum (backed)**              | Domínio finito + `label()`/`options()`/`variant()` + lógica; coluna `VARCHAR` + CHECK + cast no model                                             | `StatusFuncionario` (`isAtivo()`), `TipoHoraExtra` (`fatorPadraoBps(): int`)                                      |
| **DTO (readonly)**             | Transporte entre camadas; `fromArray()` + `paraModel()`; nunca array genérico                                                                     | `FuncionarioDTO`, `HoraExtraDTO`                                                                                  |
| **FormRequest + Rules**        | Validação de input (nunca no controller/componente); `unique` por tenant                                                                          | `StoreFuncionarioRequest` + `FuncionarioRules` (`Rule::unique()->where('empresa_id', …)`, `new \App\Rules\Cpf()`) |
| **Action (`execute()`)**       | Operação atômica, transacional; recebe DTO; retorna model/DTO                                                                                     | `CreateFuncionarioAction`, `RegistrarEventoFuncionalAction` (à mão), `AprovarHoraExtraAction` (à mão)             |
| **Service (API-ready)**        | Regra reutilizável; **nunca** recebe `Request`; **nunca** retorna view/redirect/json                                                              | `EscopoOrganograma` (subárvore), `CalculoHoraExtraService` (snapshot)                                             |
| **Policy**                     | Autorização por recurso, mapeando `rh.*`                                                                                                          | `FuncionarioPolicy` (`viewAny`→`rh.funcionarios.listar`, `delete`→`rh.funcionarios.deletar`, …)                   |
| **Livewire Index**             | Wrapper fino: `authorize('viewAny', …)` no `mount`, layout, flag `podeCriar`                                                                      | `IndexFuncionario` (espelha `IndexExemplo`)                                                                       |
| **Livewire Form**              | Formulário (abas via `aba(...)`), bind de props, chama Action via DTO; `cargosDisponiveis` p/ selects                                             | `FormFuncionario` (multi-aba + repeaters das filhas)                                                              |
| **Livewire Table (PowerGrid)** | Grid: `fields`/`columns`/`filters`, `ComLixeira` (`excluir`/`restaurar`/`excluirDefinitivo`), badges, export                                      | `FuncionarioTable` (escopa subárvore sem `rh.funcionarios.ver_todos`)                                             |
| **Views (`rh::`)**             | `index-*`, `form-*`, `_acoes`, `_lixeira-toggle`, `_export-pdf`; **sem `<select>` nativo** (`x-shared.select-search`)                             | `rh::livewire.funcionarios.form-funcionario`                                                                      |

Referência canônica de Table: `app/Livewire/Admin/Exemplos/ExemploTable.php` (traits `ComLixeira`/`ExportaPdf`/`FiltraPorMultiEmpresa`/`WithExport`, `datasource()` via `aplicarLixeira(aplicarEscopoMultiEmpresa(...))`, `permissaoListagem()`, `modelClassLixeira()`). Referência de Index: `app/Livewire/Admin/Exemplos/IndexExemplo.php`.

---

## 5. Migrations

**Onde.** Em `packages/modulo-rh/database/migrations`, carregadas por `loadMigrationsFrom` (não publicadas; rodam com `php artisan migrate`). Padrão incremental do core: `create_<tabela>_table` + aditivas (`add_<coluna>_to_<tabela>_table`) para evoluções.

**Ordem de criação** (respeita as FKs — catálogos antes do funcionário; filhas/eventos/HE depois):

1. **Catálogos tenant (B1):** `departamentos`, `funcoes`, `tipos_documento`, `tipos_afastamento`, `escalas`, `rubricas`.
2. **Agregado-raiz (B2):** `funcionarios` (com `departamento_id`, `cargo_id`→`cargos` ref. global, `filial_id`, e já as colunas `gestor_id`/`admin_user_id` nullable de B3).
3. **Filhas (B2):** `funcionario_contatos`, `funcionario_enderecos`, `funcionario_dados_bancarios`, `funcionario_dependentes`, `funcionario_documentos`.
4. **Pivôs/vigência (B3/B5):** `funcionario_funcao`, `escala_dias`, `escala_funcionario`.
5. **Histórico (B4):** `funcionario_eventos` (**sem `deleted_at`**), `funcionario_afastamentos`.
6. **Operacional (B6):** `horas_extras` (**sem `deleted_at`**).
7. **Referência de folha (B7):** `tabelas_legais`.

**Padrões obrigatórios na customização pós-geração** (do [01 §3/§7](01-modelo-de-dominio.md)):

- **Índices em toda FK** + compostos quentes: `(empresa_id, gestor_id)` (recursão do organograma), `(empresa_id, status)`, `(funcionario_id, data)` (HE/afastamentos/eventos), `data_validade` (documentos a vencer).
- **Unique parcial por tenant** (Postgres): `UNIQUE (empresa_id, <coluna>) WHERE deleted_at IS NULL` — ex.: `(empresa_id, cpf)`, `(empresa_id, matricula)`, `(empresa_id, admin_user_id)`. Vigência aberta única: `UNIQUE (funcionario_id) WHERE vigencia_fim IS NULL`.
- **CHECK de enums**: coluna `VARCHAR` + CHECK com a lista de valores do enum (ADR-0010). Outros CHECKs: `gestor_id <> id`, `departamento_pai_id <> id`, `data_demissao IS NULL OR data_demissao >= data_admissao`, `minutos > 0`, `eh_folga OR (entrada IS NOT NULL AND saida IS NOT NULL)`.
- **`empresa_id` redundante** em filhas/pivôs é intencional (uniformiza global scope + unique-por-tenant; o auto-fill garante consistência).
- **Integridade referencial**: catálogos `restrictOnDelete` (não apagar em uso); filhas do funcionário `cascadeOnDelete` físico (só em force-delete); auto-FKs `nullOnDelete`.

---

## 6. Seeds / provisionamento

### 6.1 `ProvisionarCatalogosRh` (catálogos tenant, por empresa, idempotente)

Action à mão em `src/Support`, **análoga a** `App\Actions\Admin\Menu\AplicarMenuPadraoAction` (transação + `firstOrCreate` por chave estável). Semeia, **por empresa ativa**, os defaults do [01 §5](01-modelo-de-dominio.md):

- `tipos_documento` (RG, CPF, CTPS, PIS/PASEP, Título, Reservista, CNH `exige_validade`, Comprovante de Residência `exige_arquivo`, ASO `exige_validade`, …) — `firstOrCreate(['empresa_id','codigo'], [...])`.
- `tipos_afastamento` (códigos eSocial tab. 18: Férias, Atestado ≤15d, INSS >15d `suspende_contrato`, Licença-maternidade, Falta injustificada `conta_como_falta`, …).
- `funcoes` (Líder, Preposto, Supervisor, Coordenador, …) — `firstOrCreate(['empresa_id','nome'], …)`.
- `departamentos` (hierárquico: Financeiro → Contas a Pagar/Receber, RH, Comercial, …).
- `escalas` + `escala_dias` (44h, 40h, 12x36 Diurno/Noturno, Estágio 6h, 30h).
- `rubricas` (Salário Base, HE 50%/100%, Adicional Noturno, DSR, INSS, IRRF, FGTS, VT, Salário-Família).

**Idempotência** (DoD de B1): duas execuções ⇒ contagem estável. Disparada no **gancho de criação de empresa** do core (`TenantContext`/evento) e exposta como **comando/seed de re-provisionamento** para empresas pré-existentes.

### 6.2 `tabelas_legais` (referência global por vigência)

Seed do pacote (`src/Database/Seeders/TabelasLegaisSeeder`): faixas INSS/IRRF + salário-família da competência vigente (`vigencia_inicio`/`vigencia_fim` + `tipo` + payload JSONB). **Fundação** (B7): alimenta cálculos futuros; sem apuração na Fase 1. Atualizável por competência via novo seed/admin.

### 6.3 Pós-instalação (sempre)

```bash
php artisan migrate          # cria as tabelas do pacote (loadMigrationsFrom)
php artisan access:sync      # publica as rh.* (config('access.modules') já as enxerga)
php artisan cache:clear      # invalida cache de menu e de resolução de acesso
```

### 6.4 Notas de infra desta revisão

Pontos de instalação/arquitetura que os incrementos da revisão acrescentam (aditivos — [02 §7](02-fase-1-blueprint.md)):

- **Disco `rh_privado`** — registrar um disk `local` apontando para `storage/app/private/rh` (fora do webroot) em `config/filesystems.php` (merge do pacote ou nota de instalação). O `GerenciadorAnexos` do core ganha a prop `disco` (default `public` preservado) e é instanciado com `disco="rh_privado"` no RH; download por controller assinado + Policy. Layout, retenção e auditoria em [03 §8.3](03-cadastro-pessoa-documentos.md) / [ADR-RH-009](adrs/ADR-RH-009-armazenamento-seguro-documentos.md).
- **Jobs na fila `exports`** — extração de `.zip` de documentos ([03 §8.5](03-cadastro-pessoa-documentos.md)) e importação multi-aba de funcionários ([11](11-importacao-exportacao.md)) rodam assíncronos; o log opcional `importacoes` ([01 §F](01-modelo-de-dominio.md)) guarda status/resumo.
- **Campos personalizados** — trait `TemCamposPersonalizados` em `src/Models/Concerns/`, enum `TipoCampoPersonalizado` em `src/Enums/`, e o componente Livewire genérico de renderização em `src/Livewire/` ([10](10-campos-personalizados.md)); a coluna `funcionarios.dados_personalizados` (JSONB) tem cast `array`.

---

## 7. Testes (Pest)

Vivem em `packages/modulo-rh/tests/Feature/` (gerados por recurso) e em `tests/Feature/Rh/` do app para cenários transversais já presentes (`FuncionarioCargoTest.php`, `RhLixeiraTest.php`). Cobertura por bloco:

| Bloco | O que testar                                                                                                                                                                                                                                         |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| B1    | CRUD de cada catálogo; **tenant scope** (registro de outra empresa não aparece); **idempotência** do `ProvisionarCatalogosRh` (2 execuções ⇒ contagem estável); seeds aplicam os defaults do [01 §5](01-modelo-de-dominio.md).                       |
| B2    | CRUD `funcionarios` + filhas; unique por empresa (CPF/matrícula); PII **fora** do diff de auditoria; upload/serve privado de documento; `FuncionarioCargoTest` (select de cargo lista o catálogo — `cargosDisponiveis` populado pelo `CargoSeeder`). |
| B3    | **Matriz de ACL**: gestor A **não** vê subárvore de B; `rh.funcionarios.ver_todos` libera visão global; self-service negado a terceiros; **rejeição de ciclo** de gestor; vínculo `AdminUser` único por empresa.                                     |
| B4    | Append-only respeitado (sem rota de edição/exclusão de evento; estorno = novo evento); snapshot correto; atualização transacional da coluna "atual" de `funcionarios`; `cid` protegido sem `rh.afastamentos.ver_cid`.                                |
| B5    | Travessia de meia-noite (`saida < entrada`); folga sem horário; unique de turno; **sobreposição de vigência rejeitada**; carga calculada/cacheada.                                                                                                   |
| B6    | **Cálculo de HE** por tipo/regime; **imutabilidade do snapshot** pós-aprovação (recálculo não altera HE aprovada); transições válidas/inválidas da máquina de estados; aprovação negada fora da cadeia/sem permissão.                                |
| B7    | Seed de rubricas/tabelas legais aplica defaults; resolução HE→rubrica via `referencia_he_tipo`; vigência correta da tabela legal por competência.                                                                                                    |

Transversal a todos os recursos com lixeira (padrão de `RhLixeiraTest`): `excluir`→trash, `restaurar`, `excluirDefinitivo`, e **403** para quem só tem `*.listar` (`assertForbidden`).

> Atenção a gotchas do core: `Livewire::test` não enxerga `withSession`; `AuthorizationException` vira HTTP 403; FKs sem cast vêm como string no Postgres.

### 7.1 Suíte Postgres dedicada (`@group postgres`)

O `phpunit.xml` do core usa **SQLite `:memory:`** (rápido, sem serviço). Mas o RH depende de recursos **Postgres-only** que o SQLite não reproduz fielmente — então uma **fração** dos testes roda contra Postgres, marcada com `@group postgres` (Pest: `->group('postgres')`).

**O que exige Postgres (e por quê):**

| Recurso Postgres-only                   | Onde no RH                                                                                                                                                                                                             | Por que SQLite não serve                                                                |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| CTE `WITH RECURSIVE` + `CYCLE`          | `EscopoOrganograma` (subárvore), Action de atribuição de gestor (anti-ciclo) — [05 §4.2/§8.7](05-organograma-acl-hierarquica.md)                                                                                       | SQLite não tem `WITH RECURSIVE ... CYCLE`                                               |
| **Índices únicos parciais** (`WHERE …`) | `(empresa_id, cpf\|matricula\|admin_user_id) WHERE deleted_at IS NULL`; `… WHERE principal = true`; `escala_funcionario … WHERE vigencia_fim IS NULL`; `fator_horas_extras (empresa_id,tipo) WHERE deleted_at IS NULL` | a semântica de índice parcial diverge; a unicidade real precisa ser provada no Postgres |
| **JSONB**                               | `funcionario_eventos.snapshot_*`, `horas_extras.snapshot_calculo`, `tabelas_legais` payload                                                                                                                            | SQLite não tem `JSONB` (vira TEXT) — cast/serialização diferentes                       |
| **CHECK constraints**                   | enums (lista de valores), `minutos > 0`, `gestor_id <> id`, datas, `eh_folga OR (entrada/saida)`                                                                                                                       | SQLite só valida CHECK de forma limitada                                                |

**Lista nominal de testes Postgres (mínimo da Fase 1):** subárvore recursiva (B3) · rejeição de ciclo de gestor (B3) · unicidade parcial de CPF/matrícula na lixeira (B2) · "um principal por tipo/escopo" (B2) · "uma vigência aberta" em `escala_funcionario` (B5) · override único em `fator_horas_extras` (B6) · gravação/leitura do `snapshot_calculo` JSONB (B6) e dos snapshots de evento (B4).

**Infra (conexão + grupo + comando):**

- **Conexão `pgsql_test`** em `config/database.php` — aponta para o Postgres do DDEV num **database de teste** dedicado (ex.: `db_test`); selecionada via `.env.testing`/variável quando o grupo roda. (`RefreshDatabase` migra o schema do pacote por `loadMigrationsFrom`.)
- **Grupo/testsuite** — testes Postgres-dependentes marcados `@group postgres`; uma `<testsuite name="postgres">` no `phpunit.xml` (ou simplesmente `--group=postgres`) os isola.
- **Política de execução:** o fluxo rápido (`make test`/pre-commit) roda **SQLite excluindo** o grupo (`--exclude-group=postgres`); um passo dedicado roda **só** o grupo contra Postgres:

```bash
# rápido (SQLite, default): exclui o grupo Postgres
php artisan test --exclude-group=postgres

# suíte Postgres (DDEV): conexão pgsql_test
ddev exec env DB_CONNECTION=pgsql_test php artisan test --group=postgres

# CI: um job adicional com serviço Postgres roda o grupo
php artisan test --group=postgres   # DB_CONNECTION=pgsql_test no ambiente do job
```

> **Nota técnica — índice parcial não é portável pelo `Blueprint`.** O `Schema`/`Blueprint` do Laravel **não** expressa o `WHERE` de um índice único parcial de forma portável; esses índices entram via **SQL bruto** na migration (`DB::statement('CREATE UNIQUE INDEX … WHERE deleted_at IS NULL')`) — o padrão já visto no core em `add_deleted_at_*`. Em SQLite esse SQL não roda igual (ou é ignorado), então **a garantia de unicidade parcial só é testável em Postgres** — daí a entrada na tabela acima. As migrations do RH devem usar raw SQL para esses índices e os testes correspondentes devem estar em `@group postgres`.

---

## 8. Qualidade (gate antes de cada commit)

```bash
./vendor/bin/pint                              # PSR-12 + Laravel
npx prettier --write packages/modulo-rh/       # Blade/JS/CSS/JSON/MD do pacote
./vendor/bin/phpstan analyse                   # Larastan level 6 — sem warnings
php artisan test                               # Pest verde (suíte RH em Postgres)
```

Commit em **Conventional Commits** PT-BR, escopo `rh`: `feat(rh): adicionar catálogo de departamentos`, `feat(rh): cálculo de hora extra com snapshot imutável`, `test(rh): escopo recursivo do organograma`. O `npm run quality` do core encadeia lint + PHPStan + test.

---

## 9. Sequência de implementação recomendada (mapeada aos blocos B1..B7)

Caminho crítico `B1 → B2 → B3`; depois B4 (paralelo) e a trilha de folha `B5 → B6 → B7` (ver [02 §3](02-fase-1-blueprint.md)).

> **Por que esta ordem importa além da Fase 1.** A Fase 1 não é um fim em si — é o **contrato imutável** que as fases 2–6 (ausências/tempo → folha → eSocial → ponto) consomem **sem retrabalho**: snapshots imutáveis (ADR-0009), cadastro eSocial-ready, `rubricas`/`tabelas_legais`, jornada/escalas. Essa visão de longo prazo e o "porquê" da fundação ser imutável estão em [09 — Roadmap](09-roadmap-fases.md) (em especial [09 §9 — "a fundação é o contrato"](09-roadmap-fases.md)). Decisões de modelagem tomadas aqui (ENUM × CATÁLOGO × REFERÊNCIA, vínculo `funcionario↔admin_user`, append-only) habilitam o futuro — ver os ADRs do módulo.

```bash
# ── B1 · Fundação + catálogos ─────────────────────────────────────────────
php artisan make:modulo-pacote Rh
composer require "ht2erp/modulo-rh:@dev"
php artisan make:modulo Departamento            --module=Rh --tenant --fields="..."   # (ver §2.2)
php artisan make:modulo Funcao           --module=Rh --tenant --fields="..."
php artisan make:modulo TipoDocumentoRh  --module=Rh --tenant --fields="..."
php artisan make:modulo TipoAfastamento  --module=Rh --tenant --fields="..."
php artisan make:modulo Escala           --module=Rh --tenant --fields="..."
php artisan make:modulo Rubrica          --module=Rh --tenant --fields="..."
# À mão: customizar migrations (FKs/uniques parciais/CHECKs), self-relation de Departamento,
#        ProvisionarCatalogosRh + gancho de criação de empresa, TabelasLegaisSeeder.
./vendor/bin/pint && npx prettier --write packages/modulo-rh/
php artisan migrate && php artisan access:sync && php artisan cache:clear
./vendor/bin/phpstan analyse && php artisan test

# ── B2 · Pessoa + documentos ──────────────────────────────────────────────
php artisan make:modulo Funcionario      --module=Rh --tenant --fields="..."
# À mão: 5 tabelas-filhas (+ models/repeaters), enums de B2 com CHECK, documentos via Anexo
#        (disco privado + URL assinada), uniques parciais (CPF/matrícula).
./vendor/bin/pint && npx prettier --write packages/modulo-rh/ && php artisan migrate && php artisan test

# ── B3 · Organograma + ACL + vínculo AdminUser + self-service ─────────────
# À mão: migration do pivot funcionario_funcao; EscopoOrganograma (CTE recursiva) +
#        VisivelNaHierarquia; Action de gestor c/ detecção de ciclo;
#        AdminUser::funcionario() via model do pacote; portal do colaborador (rh.self.ver).
php artisan access:sync && php artisan cache:clear && php artisan test   # suíte em Postgres

# ── B4 · Linha do tempo (paralelo a B3/B5 após B2) ────────────────────────
# À mão: funcionario_eventos (append-only, JSONB) + Action transacional;
#        funcionario_afastamentos (cid encrypted + rh.afastamentos.ver_cid); timeline.

# ── B5 · Jornada / escalas ────────────────────────────────────────────────
# À mão: escala_dias (editor 7×turnos, sem deleted_at) + escala_funcionario (vigência,
#        não-sobreposição, única vigente); cálculo/cache de carga_semanal_minutos.

# ── B6 · Horas extras + workflow ──────────────────────────────────────────
php artisan make:modulo HoraExtra        --module=Rh --tenant --sem-soft-delete --fields="..."
# À mão: CalculoHoraExtraService (snapshot imutável); máquina de estados;
#        aprovação restrita à cadeia do organograma + rh.horas_extras.aprovar.

# ── B7 · Fundação de folha ────────────────────────────────────────────────
# À mão: completar Rubrica (incidências/referencia_he_tipo); tabelas_legais + seed;
#        ponte HE aprovada → rubrica. Sem apuração na Fase 1.
php artisan access:sync && php artisan cache:clear && php artisan test
```

---

## 10. Definition of Done do módulo (Fase 1)

- [ ] `make:modulo-pacote Rh` rodado; pacote instalado via path repository (symlink) e carregando (`/admin` responde com rotas RH).
- [ ] `RhServiceProvider`: rotas via `ModuleRegistry`; permissões mescladas em `config('access.modules')`; menu mesclado (keys coerentes com `AplicarMenuPadraoAction` → grupo "RH" em Tabelas Auxiliares); `Livewire::component()` + `Gate::policy()` de **todos** os recursos.
- [ ] 8 recursos geráveis (Departamento, Funcao, TipoDocumentoRh, TipoAfastamento, Escala, Rubrica, Funcionario, HoraExtra) com a stack completa (model+enum+DTO+Rules+Actions+Service+Policy+Livewire+views+teste).
- [ ] Tabelas à mão entregues: 5 filhas de `funcionarios`, `funcionario_funcao`, `escala_dias`, `escala_funcionario`, `funcionario_eventos`, `funcionario_afastamentos`, `tabelas_legais`.
- [ ] ≈21 tabelas do [01 §9](01-modelo-de-dominio.md) (inclui `fator_horas_extras`) e os 20 enums do [01 §4](01-modelo-de-dominio.md) criados; migrations com índices/uniques parciais/CHECKs; FKs `restrict`/`cascade`/`nullOnDelete` corretas.
- [ ] Multi-tenant em toda tabela (`BelongsToEmpresa`); lixeira (`ComLixeira`) onde aplicável; append-only sem `deleted_at` em `funcionario_funcao`/`escala_dias`/`escala_funcionario`/`funcionario_eventos`/`horas_extras`.
- [ ] LGPD: PII em `atributosNaoAuditados()`; `cid`/financeiro `encrypted`; foto/documentos em disco privado + URL assinada; `rh.afastamentos.ver_cid` separada.
- [ ] Snapshots imutáveis: `funcionario_eventos` e `horas_extras` aprovadas não editáveis (correção = evento/estado novo).
- [ ] `ProvisionarCatalogosRh` idempotente + gancho de criação de empresa + re-provisionamento de empresas existentes; seed de `tabelas_legais`.
- [ ] **Suíte Postgres dedicada** (§7.1, `@group postgres`, conexão `pgsql_test`) verde: subárvore recursiva, rejeição de ciclo, uniques parciais, snapshot JSONB; o fluxo rápido roda SQLite com `--exclude-group=postgres`. Matriz de ACL (subárvore/`ver_todos`/self-service/ciclo) verde.
- [ ] Cálculo de HE com snapshot congelado na aprovação + máquina de estados (transições válidas/inválidas).
- [ ] `access:sync` reconhece todas as `rh.*`; `cache:clear` pós-instalação.
- [ ] Pint + Prettier + PHPStan (level 6) + Pest **verdes**; commits em Conventional Commits (escopo `rh`).
- [ ] Telas Index/Form/Table de todos os recursos funcionais, **sem `<select>` nativo** (`x-shared.select-search`).
