# Criar um módulo de negócio com `make:modulo`

> Objetivo do boilerplate: você se preocupa apenas com **o negócio do cliente**.
> O gerador entrega a stack CRUD inteira já no padrão do projeto; você preenche a
> regra de negócio.

## TL;DR

```bash
php artisan make:modulo Cliente \
  --fields="nome:string, cnpj:cnpj, email:email:nullable, status:enum(ativo|inativo)" \
  --tenant

./vendor/bin/pint && npx prettier --write resources/views/livewire/admin/clientes/
php artisan migrate
php artisan access:sync
```

Acesse `/admin/clientes`. Pronto: listagem (PowerGrid com busca/filtros/export), criar/editar, validação, DTO, Actions, Policy, auditoria automática e um teste Feature verde.

## O que é gerado

Para `make:modulo Cliente` o gerador cria (tudo com `declare(strict_types=1)`):

| Camada                | Arquivo                                                                                                          |
| --------------------- | ---------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| Migration + Factory   | `database/migrations/*_create_clientes_table.php`, `database/factories/ClienteFactory.php`                       |
| Model                 | `app/Models/Cliente.php` (traits `Auditavel` + `BelongsToEmpresa` se `--tenant`)                                 |
| Enum de status (§5.4) | `app/Enums/StatusCliente.php` (backed, `label()`/`variant()`/`options()`)                                        |
| DTO readonly (§5.5)   | `app/DTOs/Admin/ClienteDTO.php` (`fromArray()` / `paraModel()`)                                                  |
| Validação (§5.2)      | `app/Http/Requests/Admin/ClienteRules.php` + `Store/UpdateClienteRequest.php`                                    |
| Actions (§6)          | `app/Actions/Admin/Create                                                                                        | UpdateClienteAction.php` (`execute()` + transação) |
| Service (§5.6)        | `app/Services/Admin/ClienteService.php`                                                                          |
| Policy                | `app/Policies/ClientePolicy.php` (auto-descoberta por convenção)                                                 |
| Livewire              | `app/Livewire/Admin/Clientes/{IndexCliente,FormCliente,ClienteTable}.php`                                        |
| Views                 | `resources/views/livewire/admin/clientes/{index-clientes,form-cliente,_acoes,_ficha}.blade.php`                  |
| Teste Feature         | `tests/Feature/Admin/Clientes/ClienteCrudTest.php`                                                               |
| Rotas                 | injetadas em `routes/admin.php` (`admin.clientes.{index,create,edit}`)                                           |
| Permissões            | injetadas em `config/access.php` (`clientes.{listar,criar,editar,deletar,restaurar,excluir_permanente}`)         |
| Menu lateral          | item injetado em `config/admin-menu.php` (seção **Negócio**), visível só p/ super-admin até atribuir a permissão |

## Lixeira (soft-delete)

Por padrão, todo módulo nasce com **lixeira**: o model usa `SoftDeletes` (coluna
`deleted_at`) e implementa `UsaSoftDeletes`, a Table ganha o toggle "Ver lixeira"
e as ações excluir → restaurar → excluir definitivamente (trait `ComLixeira`), e
as permissões `restaurar` + `excluir_permanente` entram no catálogo. Use
`--sem-soft-delete` para a saída antiga (sem lixeira). Detalhes em
[`lixeira.md`](lixeira.md).

## Gramática de `--fields`

`nome:tipo[:modificador][:modificador]`, separados por vírgula.

**Tipos:** `string`, `text`, `richtext`, `integer`, `money` (INTEGER em centavos,
§5.3), `decimal` (10,2), `boolean`, `date`, `datetime`, `email`, `url`, `cnpj`,
`cpf`, `cep`, `phone`, `color`, `enum(a|b|c)`, `multiselect(a|b|c)`.

**Modificadores:** `nullable`, `unique`, `aba(Rótulo)`.

Cada tipo já mapeia para o componente Blade certo (`money`→input numérico de
centavos, `decimal`→input `step=0.01`, `cnpj`→`x-shared.cnpj-input`,
`date`→`x-shared.date-picker`, `text`→`textarea`, `richtext`→`x-shared.rich-editor`,
`boolean`→`x-shared.toggle`, `url`→input `type=url`, `color`→`x-shared.color-picker`,
`enum`→`x-shared.select`, `multiselect`→`x-shared.select-search` (múltiplo), …).

> `enum(a|b|c)` **não-status** vira um `<select>` simples (string validada por
> `Rule::in`); só o campo chamado `status` ganha um Enum backed dedicado + badge.
> `multiselect(a|b|c)` grava `json` (cast `array`). `richtext` é sanitizado com
> `HtmlSanitizer` no DTO antes de persistir.

**Status:** todo módulo nasce com um **Enum de status backed**. Declare
`status:enum(rascunho|publicado|arquivado)` ou omita para o default
`ativo|inativo`.

## Dividir o formulário em abas (`aba(...)`)

Para formulários grandes, agrupe os campos em abas com o modificador
`aba(Rótulo)` por campo. **Sem nenhum `aba(...)`, o form continua em card único**
(retrocompatível); com pelo menos um, o gerador emite abas **conectadas ao card**
(`x-shared.tab-nav` + `x-shared.tab-body`, sem o gap antigo).

```bash
php artisan make:modulo Cliente --fields="\
  nome:string:aba(Identificação),\
  cnpj:cnpj:aba(Identificação),\
  email:email:aba(Contato),\
  telefone:phone:nullable:aba(Contato),\
  status:enum(ativo|inativo):aba(Contato)"
```

Regras de agrupamento:

- A ordem das abas segue a **primeira aparição** de cada rótulo nos campos.
- Campos regulares **sem** `aba(...)` caem na **primeira** aba.
- O `status` (quando não recebe `aba(...)`) vai para a **última** aba — ou declare
  `status:enum(...):aba(Rótulo)` para escolher.
- O rótulo pode ter espaços/acentos (ex.: `aba(Datas & Mídia)`); o id do painel é o
  slug (`aba-datas-midia`). Evite vírgula/dois-pontos no rótulo (separadores da gramática).
- Cada aba ganha um indicador de erro (`:has-error`) que acende quando algum campo
  dela falha na validação, mesmo com a aba inativa.

## Módulo `Exemplo` — referência viva (todos os tipos + abas)

Para entender o gerador na prática, o módulo **Exemplo** foi criado pelo próprio
comando, exercitando **todos os tipos suportados** divididos em 4 abas. Aparece no
menu lateral sob **Negócio** (só para super-admin) e abre em `/admin/exemplos` —
é referência viva; recrie/edite à vontade. Este é o comando completo:

```bash
php artisan make:modulo Exemplo --tenant --fields="\
  nome:string:aba(Identificação),\
  slug:string:unique:aba(Identificação),\
  site:url:nullable:aba(Identificação),\
  descricao:richtext:nullable:aba(Identificação),\
  email:email:aba(Contato),\
  telefone:phone:nullable:aba(Contato),\
  cep:cep:nullable:aba(Contato),\
  cnpj:cnpj:nullable:aba(Contato),\
  cpf:cpf:nullable:aba(Contato),\
  preco:money:aba(Comercial),\
  custo:decimal:aba(Comercial),\
  quantidade:integer:aba(Comercial),\
  cor:color:nullable:aba(Comercial),\
  categoria:enum(servico|produto|assinatura):aba(Comercial),\
  tags:multiselect(vip|novo|promo):nullable:aba(Comercial),\
  destaque:boolean:aba(Datas e status),\
  data_inicio:date:aba(Datas e status),\
  publicado_em:datetime:nullable:aba(Datas e status),\
  status:enum(rascunho|publicado|arquivado):aba(Datas e status)"

./vendor/bin/pint && npx prettier --write resources/views/livewire/admin/exemplos/
php artisan migrate && php artisan access:sync
```

## Flags

- `--tenant` — vincula à empresa ativa (trait `BelongsToEmpresa`: global scope por
  empresa + auto-preenche `empresa_id`). Use em quase todo módulo de negócio. Também
  injeta na Table o trait `FiltraPorMultiEmpresa` (filtro multi-empresa nas listagens —
  veja `docs/multi-empresa.md`); o recurso só aparece para quem tem a permissão
  `listagens.multi_empresa` e acesso a 2+ empresas.
- `--menu="Rótulo"` — rótulo do item de menu (default: nome no plural, ex.: "Produtos").
- `--menu-icon="tabler--..."` — ícone do item de menu (default `tabler--folder`).
- `--skip-menu` — não injeta o item no menu lateral.
- `--force` — sobrescreve arquivos existentes (re-geração). A migration é
  idempotente (pula/limpa pela tabela, não cria duplicada).
- `--module=Rh` — gera o CRUD dentro de um módulo-pacote existente (ver
  "Gerar dentro de um módulo-pacote" abaixo) em vez de `app/`.
- `--sem-soft-delete` — desativa o soft-delete. **Por padrão** os módulos usam
  soft-delete (`deleted_at`): registros ficam recuperáveis em vez de apagados.

O item de menu entra na seção **Negócio** de `config/admin-menu.php` com
`permission => '{modulo}.listar'` — **visível só para o super-admin** (bypass) até
você atribuir a permissão a outros perfis em `/admin/acesso`.

## Permissões e o enum `ModuloAcesso`

As permissões do módulo entram em `config/access.php` sob a seção **Negócio**
(`ModuloAcesso::Negocio`). Depois de `php artisan access:sync`, atribua-as aos
perfis na tela de Controle de Acesso (`/admin/acesso`) — ou use `super-admin`, que
tem bypass.

Quer dar ao módulo a **própria seção** na matriz (em vez de cair em "Negócio")?
Crie um case em `app/Enums/ModuloAcesso.php` (com os braços de `match`
correspondentes) e mova as permissões do módulo para a chave dele em
`config/access.php`.

## Ficha "Ver" (visualização em drawer)

O módulo já nasce com a opção **Ver** no kebab: drawer largo read-only
(`x-admin.ficha-drawer` + trait `ComFicha` no Index), com os campos formatados
por tipo em `_ficha.blade.php`. Sem permissão nova — a ability `view` mapeia
`{modulo}.listar`, então um perfil só com `.listar` consulta sem editar/excluir.
Padrão completo (e como adotar em módulo legado): [`docs/visualizacao.md`](visualizacao.md).

## O que você ainda faz (a regra de negócio)

1. **Migration:** ajuste colunas, índices e relacionamentos específicos.
2. **Regras de negócio:** Actions/Service (cálculos, eventos, integrações).
3. **Validação fina:** `ProdutoRules::regras()` (reutilizada por Livewire e
   FormRequests).
4. **Relacionamentos no Model** e eager-load no `datasource()` da Table (evita N+1).

## Gerar dentro de um módulo-pacote (HT2 ERP)

Por padrão o `make:modulo` gera em `app/` (monólito). Para criar um módulo de negócio
**reutilizável entre clientes** como pacote Composer, gere dentro de um módulo-pacote:

```bash
php artisan make:modulo-pacote Rh                  # casca do pacote em packages/modulo-rh
composer require "ht2erp/modulo-rh:@dev"           # instala (symlink) p/ dev local
php artisan make:modulo Funcionario --module=Rh --fields="..."   # CRUD dentro do pacote
```

O CRUD nasce com namespaces do pacote (`HT2ERP\Rh\...`), views namespaced (`rh::`) e se
integra ao core **sem editá-lo** (rotas via `ModuleRegistry`, permissões/menu via
`config/rh.php` publicável, Livewire/Policy no provider do pacote). O pacote de RH
registra a **própria seção** "Recursos Humanos" na sidebar (e o módulo
`recursos_humanos` na matriz de acesso) em vez de cair em "Negócio". Sem `--module`, o
comportamento é o atual (gera em `app/`).

Guia completo de distribuição e manutenção:
[`distribuicao-manutencao.md`](distribuicao-manutencao.md) · decisão: [`ADR-0015`](architecture/adrs/ADR-0015-modulos-pacotes-composer.md).

## Limitações conhecidas (v1)

- Pluralização/derivação de nomes usa o inflector (inglês). Para nomes que ele
  erra, ajuste tabela/rota/namespace após gerar.
- `money` usa input numérico (centavos) para ficar correto ponta a ponta; troque
  por `x-shared.money-input` quando adicionar um caster string→centavos no form.
- **Upload de arquivo/imagem não é scaffoldado**: exige `WithFileUploads`, prop
  não-tipada e disco configurado. Adicione manualmente seguindo o padrão de
  `AbaLogin`/`PerfilConta` (`->store()` + `x-shared.file-upload`).

## Customizar os stubs

Os templates ficam em `stubs/modulo/*.stub` (tokens `__UPPER_SNAKE__`). Edite-os
para mudar o que todo módulo novo passa a gerar.
