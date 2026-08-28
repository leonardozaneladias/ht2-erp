# Criar um recurso com `make:recurso`

> Objetivo do boilerplate: você se preocupa apenas com **o negócio do cliente**.
> O gerador entrega a stack CRUD inteira já no padrão do projeto; você preenche a
> regra de negócio.

> **O comando mudou de nome em 2026-08-28.** Era `make:modulo`; hoje é
> `make:recurso`, e `make:modulo` cria o **módulo** (a área de negócio). Um
> *recurso* é uma entidade com CRUD — aluno, turma, fatura; um *módulo* é a área
> que reúne vários deles. A forma antiga falha ensinando a nova. Vocabulário
> completo: [`ADR-0021`](architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md).

## TL;DR

```bash
php artisan make:recurso Cliente \
  --fields="nome:string, cnpj:cnpj, email:email:nullable, status:enum(ativo|inativo)" \
  --tenant

npx prettier --write resources/views/livewire/admin/clientes/
php artisan migrate
php artisan access:sync
```

Acesse `/admin/clientes`. Pronto: listagem (PowerGrid com busca/filtros/export), criar/editar, validação, DTO, Actions, Policy, auditoria automática e um teste Feature verde.

## O que é gerado

Para `make:recurso Cliente` o gerador cria (tudo com `declare(strict_types=1)`):

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
| Livewire              | `app/Livewire/Admin/Clientes/{IndexCliente,FormCliente,ClienteTable}.php` (Index e Table declarativos — ver abaixo) |
| Views                 | `resources/views/livewire/admin/clientes/{index-clientes,form-cliente,_acoes,_ficha}.blade.php`                  |
| Teste Feature         | `tests/Feature/Admin/Clientes/ClienteCrudTest.php`                                                               |
| Rotas                 | injetadas em `routes/admin.php` (`admin.clientes.{index,create,edit}`)                                           |
| Permissões            | injetadas em `config/access.php` (`clientes.{listar,criar,editar,deletar,restaurar,excluir_permanente}`)         |
| Menu lateral          | item injetado em `config/admin-menu.php` (seção **Negócio**), visível só p/ super-admin até atribuir a permissão |

### A tabela e a listagem são declarativas

`ClienteTable` estende `HT2ML\Core\Livewire\Grid\RecursoTable` e declara
quatro coisas — o model, a chave do recurso, o prefixo das rotas e a **lista de
campos**. A base deriva `fields()`, `columns()`, `filters()`, a exportação, o
eager-load e as regras de validação a partir dessa lista única:

```php
protected function campos(): array
{
    return [
        Campo::texto('nome', 'Nome')->obrigatorio()->max(120),
        Campo::dinheiro('preco', 'Preço'),              // centavos → R$ 1.234,56, filtro numérico
        Campo::data('vence_em', 'Vencimento'),          // d/m/Y, datepicker
        Campo::booleano('ativo', 'Ativo'),              // Sim/Não, filtro booleano em português
        Campo::relacao('turma_id', 'Turma', 'turma'),   // eager-load automático
        Campo::enum('status', 'Status', StatusCliente::class),
    ];
}
```

Antes, o gerador imprimia quatro listas paralelas sobre os mesmos campos, e elas
divergiam: **toda** coluna saía `searchable()` (inclusive cor hexadecimal e
data), dinheiro ia para a tela em centavos crus, booleano renderizava `0`/`1`, e
campo numérico ou de data nascia sem filtro nenhum.

Quando o desenho não couber, a fuga é graduada: **por campo**
(`->comColuna()`, `->comFiltro()`, `->paraExportar()`, `Campo::personalizado()`),
**por método** (sobrescreva e chame `parent::`), ou **não estenda** — a base é
opt-in, nunca obrigatória.

`--tenant` traz `use RecursoMultiEmpresa;`, que liga as seis composições da
dimensão multiempresa de uma vez. Não há como pegar cinco e esquecer a do
escopo, que é a que vaza linha de outra empresa.

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
php artisan make:recurso Cliente --fields="\
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
php artisan make:recurso Exemplo --tenant --fields="\
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
- `--modulo=rh` — gera o CRUD dentro de um módulo existente (ver "Gerar dentro
  de um módulo" abaixo) em vez de `app/`. A chave vai em kebab-case.
- `--sem-soft-delete` — desativa o soft-delete. **Por padrão** os recursos usam
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
4. **Relacionamentos no Model.** O eager-load NÃO é mais passo manual: declare
   o campo com `Campo::relacao('turma_id', 'Turma', 'turma')` e a
   `RecursoTable` emite o `->with()` sozinha. Passo manual documentado é fonte
   de bug documentada — em vinte telas com FK, era a diferença entre vinte N+1
   e nenhum.

## Gerar dentro de um módulo (HT2 ERP)

Sem `--modulo`, o recurso nasce em `app/` (monólito). Para um recurso
**reutilizável entre clientes**, crie o módulo — que é um pacote Composer — e
gere dentro dele:

```bash
php artisan make:modulo rh                  # casca do pacote em packages/extensao-rh
composer require "ht2ml/extensao-rh:@dev"   # instala (symlink) p/ dev local
php artisan make:recurso Funcionario --modulo=rh --fields="..."
```

A chave do módulo vai em **kebab-case** porque é ela que vira prefixo de
permissão (`rh.funcionarios.listar`), key de seção de menu, namespace de view
(`rh::`) e prefixo de rota (`admin.rh.funcionarios`). Uma forma, um lugar — é o
que impede a permissão de ser calculada por duas fórmulas que discordam, como já
aconteceu.

O CRUD nasce com namespaces do pacote (`HT2ML\Rh\...`), views namespaced e se
integra ao core **sem editá-lo**: rotas via `ModuleRegistry`, permissões e menu
DERIVADOS da chave do recurso pelo `ModuloBuilder`, Livewire e Policy no provider
do pacote.

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
