# Criar um módulo de negócio com `make:modulo`

> Objetivo do boilerplate: você se preocupa apenas com **o negócio do cliente**.
> O gerador entrega a stack CRUD inteira já no padrão do projeto; você preenche a
> regra de negócio.

## TL;DR

```bash
php artisan make:modulo Produto \
  --fields="nome:string, sku:string:unique, preco:money, descricao:text:nullable, status:enum(rascunho|publicado|arquivado)" \
  --tenant

./vendor/bin/pint && npx prettier --write resources/views/livewire/admin/produtos/
php artisan migrate
php artisan access:sync
```

Acesse `/admin/produtos`. Pronto: listagem (PowerGrid com busca/filtros/export), criar/editar, validação, DTO, Actions, Policy, auditoria automática e um teste Feature verde.

## O que é gerado

Para `make:modulo Produto` o gerador cria (tudo com `declare(strict_types=1)`):

| Camada | Arquivo |
| --- | --- |
| Migration + Factory | `database/migrations/*_create_produtos_table.php`, `database/factories/ProdutoFactory.php` |
| Model | `app/Models/Produto.php` (traits `Auditavel` + `BelongsToEmpresa` se `--tenant`) |
| Enum de status (§5.4) | `app/Enums/StatusProduto.php` (backed, `label()`/`variant()`/`options()`) |
| DTO readonly (§5.5) | `app/DTOs/Admin/ProdutoDTO.php` (`fromArray()` / `paraModel()`) |
| Validação (§5.2) | `app/Http/Requests/Admin/ProdutoRules.php` + `Store/UpdateProdutoRequest.php` |
| Actions (§6) | `app/Actions/Admin/Create|UpdateProdutoAction.php` (`execute()` + transação) |
| Service (§5.6) | `app/Services/Admin/ProdutoService.php` |
| Policy | `app/Policies/ProdutoPolicy.php` (auto-descoberta por convenção) |
| Livewire | `app/Livewire/Admin/Produtos/{IndexProduto,FormProduto,ProdutoTable}.php` |
| Views | `resources/views/livewire/admin/produtos/{index-produtos,form-produto,_acoes}.blade.php` |
| Teste Feature | `tests/Feature/Admin/Produtos/ProdutoCrudTest.php` |
| Rotas | injetadas em `routes/admin.php` (`admin.produtos.{index,create,edit}`) |
| Permissões | injetadas em `config/access.php` (`produtos.{listar,criar,editar,deletar}`) |

## Gramática de `--fields`

`nome:tipo[:modificador][:modificador]`, separados por vírgula.

**Tipos:** `string`, `text`, `integer`, `money` (INTEGER em centavos, §5.3), `boolean`,
`date`, `datetime`, `email`, `cnpj`, `cpf`, `cep`, `phone`,
`enum(a|b|c)`.

**Modificadores:** `nullable`, `unique`.

Cada tipo já mapeia para o componente Blade certo (`money`→input numérico de
centavos, `cnpj`→`x-shared.cnpj-input`, `date`→`x-shared.date-picker`,
`text`→`textarea`, `boolean`→`x-shared.toggle`, `enum`→`x-shared.select`, …).

**Status:** todo módulo nasce com um **Enum de status backed**. Declare
`status:enum(rascunho|publicado|arquivado)` ou omita para o default
`ativo|inativo`.

## Flags

- `--tenant` — vincula à empresa ativa (trait `BelongsToEmpresa`: global scope por
  empresa + auto-preenche `empresa_id`). Use em quase todo módulo de negócio.
- `--force` — sobrescreve arquivos existentes (re-geração). A migration é
  idempotente (pula/limpa pela tabela, não cria duplicada).

## Permissões e o enum `ModuloAcesso`

As permissões do módulo entram em `config/access.php` sob a seção **Negócio**
(`ModuloAcesso::Negocio`). Depois de `php artisan access:sync`, atribua-as aos
perfis na tela de Controle de Acesso (`/admin/acesso`) — ou use `super-admin`, que
tem bypass.

Quer dar ao módulo a **própria seção** na matriz (em vez de cair em "Negócio")?
Crie um case em `app/Enums/ModuloAcesso.php` (com os braços de `match`
correspondentes) e mova as permissões do módulo para a chave dele em
`config/access.php`.

## O que você ainda faz (a regra de negócio)

1. **Migration:** ajuste colunas, índices e relacionamentos específicos.
2. **Regras de negócio:** Actions/Service (cálculos, eventos, integrações).
3. **Validação fina:** `ProdutoRules::regras()` (reutilizada por Livewire e
   FormRequests).
4. **Menu lateral:** adicione o item apontando para `route('admin.produtos.index')`.
5. **Relacionamentos no Model** e eager-load no `datasource()` da Table (evita N+1).

## Limitações conhecidas (v1)

- Pluralização/derivação de nomes usa o inflector (inglês). Para nomes que ele
  erra, ajuste tabela/rota/namespace após gerar.
- `money` usa input numérico (centavos) para ficar correto ponta a ponta; troque
  por `x-shared.money-input` quando adicionar um caster string→centavos no form.
- O item de menu lateral não é injetado automaticamente (adicione manualmente).

## Customizar os stubs

Os templates ficam em `stubs/modulo/*.stub` (tokens `__UPPER_SNAKE__`). Edite-os
para mudar o que todo módulo novo passa a gerar.
