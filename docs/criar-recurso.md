# Criar um recurso com `make:recurso`

> Objetivo da plataforma: você se preocupa apenas com **o negócio do cliente**.
> O gerador entrega a stack CRUD inteira já no padrão do projeto; você preenche a
> regra de negócio.

> **O comando mudou de nome em 2026-08-28.** Era `make:modulo`; hoje é
> `make:recurso`, e `make:modulo` cria o **módulo** (a área de negócio). Um
> _recurso_ é uma entidade com CRUD — aluno, turma, fatura; um _módulo_ é a área
> que reúne vários deles. A forma antiga falha ensinando a nova. Vocabulário
> completo: [`ADR-0021`](architecture/adrs/ADR-0021-taxonomia-modulo-recurso-area-secao.md).

## TL;DR

```bash
php artisan make:modulo escola                    # uma vez por área de negócio
composer require ht2ml/extensao-escola:@dev

php artisan make:recurso Cliente --modulo=escola \
  --fields="nome:string, cnpj:cnpj, email:email:nullable, status:enum(ativo|inativo)" \
  --tenant

npx prettier --write packages/extensao-escola/resources/views/livewire/clientes/
php artisan migrate
php artisan access:sync
php artisan ht2ml:doutor                          # confere se tudo o que foi declarado fecha
```

**`--modulo` na prática é obrigatório.** Sem ele o gerador escreve em `app/` e liga
a tela em `routes/admin.php`, `config/access.php` e `config/admin-menu.php` — três
arquivos que, depois da extração, vivem dentro de `ht2ml/core`. O produto não edita
o core ([ADR-0022](architecture/adrs/ADR-0022-dependencia-de-mao-unica.md)), então
o comando recusa **antes de escrever qualquer arquivo** e mostra o caminho com
módulo. Um produto que de fato possua os três arquivos continua usando a forma sem
`--modulo`. O job `core-sozinho` do CI prova a recusa a cada PR.

Acesse `/admin/escola/clientes`. Pronto: listagem (PowerGrid com busca/filtros/export),
criar/editar, validação, DTO, Actions, Policy, auditoria automática e um teste Feature
verde.

> O resumo impresso pelo comando ainda diz `Acesse /admin/clientes` (sem o prefixo do
> módulo). A rota real é `/admin/{modulo}/{recurso}`, como acima.

## Nomes em português: `idioma_inflexao`

O gerador deriva singular, plural, tabela e rota com a inflexão do Laravel, que é
inglesa por padrão. Num domínio em português ela erra: `Materia` vira `Materium`,
`Nota` vira `Notum`, `Avaliacao` pluraliza como `Avaliacaos`. Renomear depois é
migração de dados.

A chave `extensoes.idioma_inflexao` (em
[`packages/core/config/extensoes.php`](../packages/core/config/extensoes.php)) troca o
idioma **só durante a geração**. O default é `null` (inglês). Os valores aceitos são os
do `Illuminate\Support\Pluralizer`: `english`, `french`, `norwegian-bokmal`,
`portuguese`, `spanish`, `turkish`. Num produto, crie `config/extensoes.php` só com
`'idioma_inflexao' => 'portuguese'` — o core usa `mergeConfigFrom`, então as chaves
ausentes continuam vindo do pacote — e faça isso **antes** de gerar o primeiro
recurso. (`php artisan vendor:publish --tag=core-config` publica as cinco configs do
core inteiras, se preferir.)
Os models gerados declaram `$table` explicitamente, então mudar o idioma depois não
renomeia tabela existente.

## O que é gerado

Para `make:recurso Cliente --modulo=escola`, com o pacote em
`packages/extensao-escola`, o gerador cria 19 arquivos (todos com
`declare(strict_types=1)`; caminhos lidos de `MakeRecursoCommand::mapaArquivosPacote()`):

| Camada         | Arquivo (relativo a `packages/extensao-escola/`)                                                           |
| -------------- | ---------------------------------------------------------------------------------------------------------- |
| Migration      | `database/migrations/<timestamp>_create_clientes_table.php`                                                |
| Factory        | `database/factories/ClienteFactory.php`                                                                    |
| Model          | `src/Models/Cliente.php` (`Auditavel`; `BelongsToEmpresa` com `--tenant`; `newFactory()`)                  |
| Enum de status | `src/Enums/StatusCliente.php` (backed, `label()`/`variant()`/`options()`)                                  |
| DTO readonly   | `src/DTOs/ClienteDTO.php` (`fromArray()` / `paraModel()`)                                                  |
| Validação      | `src/Http/Requests/ClienteRules.php`, `StoreClienteRequest.php`, `UpdateClienteRequest.php`                |
| Actions        | `src/Actions/CreateClienteAction.php`, `src/Actions/UpdateClienteAction.php`                               |
| Service        | `src/Services/ClienteService.php`                                                                          |
| Policy         | `src/Policies/ClientePolicy.php`                                                                           |
| Livewire       | `src/Livewire/Clientes/IndexCliente.php`, `FormCliente.php`, `ClienteTable.php`                            |
| Views          | `resources/views/livewire/clientes/index-clientes.blade.php`, `form-cliente.blade.php`, `_ficha.blade.php` |
| Teste Feature  | `tests/Feature/Clientes/ClienteCrudTest.php`                                                               |

E edita quatro arquivos do próprio pacote, sempre num comentário-marcador:

| Arquivo do pacote               | O que entra                                                                               |
| ------------------------------- | ----------------------------------------------------------------------------------------- |
| `routes/admin.php`              | grupo `escola/clientes` com as rotas `admin.escola.clientes.{index,create,edit}`          |
| `config/escola.php`             | uma entrada em `recursos` (`'clientes' => ['label' => …, 'singular' => …, 'icone' => …]`) |
| `src/EscolaServiceProvider.php` | `Livewire::component()` dos três componentes e `Gate::policy()` do model                  |
| `composer.json`                 | `livewire/livewire` no `require`, se ainda não estiver                                    |

Os namespaces são os do pacote (`HT2ML\Escola\...`) e as views usam o namespace do
módulo (`escola::livewire.clientes._ficha`).

Se faltar um marcador, o comando gera os arquivos, diz qual marcador falta e onde, e
sai com código de falha. A tela existe, mas não está ligada — e um script que só olha
o exit code para ali.

O gerador passa o Pint nos diretórios que escreveu. O Prettier nas views fica com você
(o resumo imprime o comando).

### Permissões e menu: derivados, não escritos

A entrada em `config/escola.php` é tudo. O `ModuloBuilder` lê essa entrada no boot
(`->deConfig('escola')` no ServiceProvider) e deriva dela:

- as permissões `escola.clientes.{listar,criar,editar,deletar,restaurar,excluir_permanente}`
  (as duas últimas só com lixeira);
- o item de menu com key `escola-clientes`, rota `admin.escola.clientes.index`,
  permissão `escola.clientes.listar` e `active` em `admin.escola.clientes.*`.

Antes, o gerador escrevia vinte linhas de permissão e sete de menu, e cada linha era
uma chance de divergir da fórmula do catálogo. Já divergiu: o gerador emitia
`departamentos.listar` enquanto o catálogo dizia `rh.departamentos.listar`, e o gate
negava em silêncio.

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

O kebab de cada linha (Ver, Editar, lixeira) vem da `RecursoTable`. Recurso gerado
hoje não tem `_acoes.blade.php`; esse arquivo só existe nas telas antigas.

`--tenant` traz `use RecursoMultiEmpresa;`, que liga as seis composições da
dimensão multiempresa de uma vez. Não há como pegar cinco e esquecer a do
escopo, que é a que vaza linha de outra empresa.

> **Lacuna conhecida (medida em 2026-10-25):** o stub da tabela não declara
> `modulo()`, então a `RecursoTable` calcula as checagens internas dela
> (CTA "Novo registro", restaurar da lixeira, elegibilidade do filtro multiempresa)
> com `clientes.*` em vez de `escola.clientes.*`. O super-admin não percebe, porque
> tem bypass. Até o stub ser corrigido, acrescente na tabela gerada:
>
> ```php
> protected function modulo(): string
> {
>     return 'escola';
> }
> ```
>
> É o que `packages/extensao-rh/src/Livewire/Departamentos/DepartamentoTable.php` faz.

## Lixeira (soft-delete)

Por padrão, todo recurso nasce com **lixeira**: o model usa `SoftDeletes` (coluna
`deleted_at`) e implementa `UsaSoftDeletes`, a Table ganha o toggle "Ver lixeira"
e as ações excluir → restaurar → excluir definitivamente (trait `ComLixeira`), e
as permissões `restaurar` + `excluir_permanente` entram no catálogo. Use
`--sem-soft-delete` para a saída sem lixeira (a entrada no config ganha
`'sem_lixeira' => true`). Detalhes em [`lixeira.md`](lixeira.md).

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

**Status:** todo recurso nasce com um **Enum de status backed**. Declare
`status:enum(rascunho|publicado|arquivado)` ou omita para o default
`ativo|inativo`.

## Dividir o formulário em abas (`aba(...)`)

Para formulários grandes, agrupe os campos em abas com o modificador
`aba(Rótulo)` por campo. **Sem nenhum `aba(...)`, o form continua em card único**
(retrocompatível); com pelo menos um, o gerador emite abas **conectadas ao card**
(`x-shared.tab-nav` + `x-shared.tab-body`, sem o gap antigo).

```bash
php artisan make:recurso Cliente --modulo=escola --fields="\
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

## Recurso `Exemplo` — referência viva (todos os tipos + abas)

O recurso **Exemplo** exercita **todos os tipos suportados** divididos em 4 abas.
Ele vive na extensão `ht2ml/extensao-exemplo-demo`
(`packages/extensao-exemplo-demo/`), aparece no menu sob **Negócio** e abre em
`/admin/exemplos`.

Ele foi gerado antes do modo `--modulo` existir, e por isso mantém as chaves
antigas: rota `admin.exemplos.*`, permissões `exemplos.*` sem prefixo de módulo e
item de menu escrito à mão na config. São exceção consciente
(`tests/Arch/CoerenciaDoModuloTest.php` lista o legado); renomear invalidaria
permissões já atribuídas a perfis.

Para ver o gerador produzir o mesmo recurso hoje, use um módulo de rascunho:

```bash
php artisan make:modulo rascunho
composer require ht2ml/extensao-rascunho:@dev

php artisan make:recurso Exemplo --modulo=rascunho --tenant --fields="\
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

npx prettier --write packages/extensao-rascunho/resources/views/livewire/exemplos/
php artisan migrate && php artisan access:sync && php artisan ht2ml:doutor
```

O resultado abre em `/admin/rascunho/exemplos`, com permissões
`rascunho.exemplos.*`.

## Flags

Lidas da assinatura do comando (`php artisan help make:recurso`):

- `--modulo=escola` — chave do módulo que recebe o recurso, em kebab-case. O
  módulo precisa existir em `packages/extensao-<chave>`; se não existir, o comando
  manda criar com `make:modulo`.
- `--tenant` — vincula à empresa ativa (trait `BelongsToEmpresa`: global scope por
  empresa + auto-preenche `empresa_id`). Use em quase todo recurso de negócio.
  Também traz na Table o trait `RecursoMultiEmpresa`, que compõe
  `FiltraPorMultiEmpresa` (filtro multiempresa nas listagens — veja
  [`multi-empresa.md`](multi-empresa.md)); o filtro só aparece para quem tem a
  permissão `listagens.multi_empresa` e acesso a 2+ empresas.
- `--menu="Rótulo"` — rótulo do recurso (vira `label` na entrada do config; default:
  nome no plural).
- `--menu-icon="tabler--..."` — ícone do item de menu (default `tabler--folder`).
- `--skip-menu` — o recurso entra sem item de menu (`'sem_menu' => true` no config).
  As permissões continuam existindo.
- `--sem-soft-delete` — desativa o soft-delete. **Por padrão** os recursos usam
  soft-delete (`deleted_at`): registros ficam recuperáveis em vez de apagados.
- `--force` — sobrescreve arquivos existentes (re-geração).
- `--module=` — removida em 2026-08-28. Passá-la falha e mostra a forma com `--modulo`.

O item de menu entra na seção indicada por `secao_menu` em `config/{modulo}.php`
(o `make:modulo` gera `'negocio'`), protegido por `{modulo}.{recurso}.listar` —
**visível só para o super-admin** (bypass) até você atribuir a permissão a outros
perfis em `/admin/acesso`.

## Permissões e áreas de acesso

As permissões do recurso entram na **área de acesso** indicada por `modulo_acesso`
em `config/{modulo}.php`. A casca gerada pelo `make:modulo` usa `'negocio'`. Depois
de `php artisan access:sync`, atribua-as aos perfis na tela de Controle de Acesso
(`/admin/acesso`) — ou use `super-admin`, que tem bypass.

Quer dar ao módulo a **própria área** na matriz, em vez de empilhar tudo em
"Negócio"? **Não** edite o enum `HT2ML\Core\Enums\ModuloAcesso`: ele vive no core e
é só a semente das onze áreas do núcleo. O conjunto de áreas é aberto, e há duas
formas de acrescentar uma:

- **No pacote**, no ServiceProvider: `ModuleRegistry::modulo('escola')->areaDeAcesso('Descrição da área')`
  declara a área `escola` (com o rótulo do módulo). Para uma área com outra chave, há
  o canal direto `ModuleRegistry::areaDeAcesso($chave, $label, $descricao, $icone, $variant)`.
  Depois aponte `modulo_acesso` para essa chave em `config/escola.php` — ou remova a
  chave, e a área efetiva passa a ser a do próprio módulo.
- **No produto**, publicando a config de acesso do core e acrescentando a chave em
  `config('access.areas')`. Uma área que o produto já descreve ali vence a
  declaração do pacote: o pacote sugere, quem instala decide.

O mesmo vale para a sidebar: `->secaoDeMenu()` declara a seção do módulo, e
`secao_menu` aponta onde os itens entram. Se a área ou a seção apontada não existir,
`ht2ml:doutor` diz qual e de onde veio a declaração.

## Ficha "Ver" (visualização em drawer)

O recurso já nasce com a opção **Ver** no kebab: drawer largo read-only
(`x-admin.ficha-drawer` + trait `ComFicha`, que a `RecursoIndex` já traz), com os
campos formatados por tipo em `_ficha.blade.php`. Sem permissão nova — a ability
`view` da Policy gerada mapeia `{modulo}.{recurso}.listar`, então um perfil só com
`.listar` consulta sem editar/excluir. Padrão completo:
[`visualizacao.md`](visualizacao.md).

## Regras de validação reutilizáveis: `make:regra`

Uma regra de **uma condição** que vários recursos usam (matrícula válida, código
interno) vira classe própria:

```bash
php artisan make:regra MatriculaValida --modulo=escola --mensagem="Matrícula inválida."
```

Gera `packages/extensao-escola/src/Rules/MatriculaValida.php` (um `ValidationRule`)
e `packages/extensao-escola/tests/Unit/Rules/MatriculaValidaTest.php`. Use-a no
campo com `Campo::...->regra(new MatriculaValida)`. Sem `--modulo`, a regra nasce em
`app/Rules/` e o teste em `tests/Unit/Rules/`.

Validação **cruzada** — a que depende de mais de um campo — não cabe numa regra
isolada: ela mora em `{Recurso}Rules::regras()`, onde o conjunto está visível. O core
já traz `HT2ML\Core\Rules\{Cpf,Cnpj,Pis,TituloEleitor}`.

## Depois de gerar: `ht2ml:doutor`

```bash
php artisan ht2ml:doutor
```

Confere se o que os módulos declararam fecha: áreas de acesso, seções e grupos de
menu, permissões dos itens, rotas, ícones, escopos de rota com hospedeiro e
`extra.ht2ml.chave` do pacote contra a chave declarada no ServiceProvider. Sai com
código 1 se algo não fecha. É o mesmo comando que o CI roda. Referência:
[`comandos.md`](comandos.md).

## O que você ainda faz (a regra de negócio)

1. **Migration:** ajuste colunas, índices e relacionamentos específicos.
2. **Regras de negócio:** Actions/Service (cálculos, eventos, integrações).
3. **Validação fina:** `ClienteRules::regras()` (reutilizada por Livewire e
   FormRequests).
4. **Relacionamentos no Model.** O eager-load NÃO é mais passo manual: declare
   o campo com `Campo::relacao('turma_id', 'Turma', 'turma')` e a
   `RecursoTable` emite o `->with()` sozinha. Passo manual documentado é fonte
   de bug documentada — em vinte telas com FK, era a diferença entre vinte N+1
   e nenhum.

## Por que o recurso nasce num módulo

Um recurso pertence a um módulo, e o módulo é um pacote Composer. Por isso o
fluxo começa em `make:modulo`:

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

Como montar uma extensão de ponta a ponta (biblioteca ou módulo, canais de rota,
seeders, testes, publicação): [`guias/criar-extensao.md`](guias/criar-extensao.md).
Distribuição e manutenção: [`distribuicao-manutencao.md`](distribuicao-manutencao.md) ·
decisão: [`ADR-0015`](architecture/adrs/ADR-0015-modulos-pacotes-composer.md).

## Limitações conhecidas (v1)

- A inflexão é inglesa por padrão; ver `idioma_inflexao` acima. Para nomes que nem
  o idioma configurado acerta, ajuste tabela/rota/namespace após gerar.
- `money` usa input numérico (centavos) para ficar correto ponta a ponta; troque
  por `x-shared.money-input` quando adicionar um caster string→centavos no form.
- **Upload de arquivo/imagem não é scaffoldado**: exige `WithFileUploads`, prop
  não-tipada e disco configurado. Adicione manualmente seguindo o padrão de
  `AbaLogin`/`PerfilConta` (`->store()` + `x-shared.file-upload`).
- A tabela gerada não declara `modulo()` (ver a lacuna em "A tabela e a listagem
  são declarativas").

## Customizar os stubs

Os templates ficam em `packages/core/stubs/modulo/*.stub` (tokens `__UPPER_SNAKE__`);
a casca do `make:modulo` sai de `packages/core/stubs/extensao/`. Eles viajam dentro de
`ht2ml/core`, então não edite a cópia em `vendor/`.

Para mudar o que o seu produto gera, crie `stubs/modulo/<nome>.stub` na raiz do
projeto. A precedência é **por arquivo** (`ResolvedorDeStubs`): o stub do produto vence
o do core, e os demais continuam vindo do core. Mudar só a tabela, por exemplo, é pôr
`stubs/modulo/livewire-table.stub` e herdar os outros 18.
