# Superfície do core — o que uma extensão consome

Este documento é **medido, não projetado**. Ele nasceu da primeira extração real
(`ht2ml/extensao-fiscal-br`, PR #73): varrendo o pacote extraído por todo símbolo
que ele importa de fora de si mesmo, sai a lista exata do que o core precisa
exportar para que uma extensão instale **fora do monorepo**.

Quando a medição começou, essa superfície vivia em `App\...`, e nenhuma extensão
instalava em outro projeto — dependia de classes que o Composer não sabia entregar.
Hoje tudo isso está em `ht2ml/core` (namespace `HT2ML\Core\...`), publicado em
`leonardozaneladias/ht2ml-core` (última tag v0.5.0, medido em 2026-10-25). O que
segue é o retrato do que as extensões de fato usam.

## Como reproduzir a medição

```bash
# símbolos PHP do core usados em código de produção das extensões (use + FQN inline)
grep -rnoE '\\?HT2ML\\Core\\[A-Za-z0-9_\\]+' --include='*.php' \
  packages/extensao-*/src packages/extensao-*/database packages/extensao-*/routes \
  packages/extensao-*/config packages/extensao-*/resources \
  | sed -E 's/^([^:]+):[0-9]+:\\?/\1 /' | awk '{split($1,a,"/"); print $2, a[2]}' | sort -u

# componentes Blade
grep -rhoE '<x-[a-z0-9.-]+' --include='*.blade.php' packages/extensao-*/ | grep -v '^<x-slot' | sort -u
```

Contar só linhas `use` subestima: há símbolos citados por nome completo, sem
`use` (`\HT2ML\Core\Casts\MoneyCast::class` num cast, por exemplo). Por isso o
padrão casa as duas formas. `tests/` fica de fora: o que importa é o que o pacote
precisa em produção.

## 30 símbolos PHP, todos em `ht2ml/core`

Medido em 2026-10-25, sobre a branch `feat/extensao-ia` (main `40ae228` + as
extensões `api` e `ia`, ainda em PR). Sete extensões varridas; as duas últimas não
usam símbolo nenhum do core (ver abaixo).

| Símbolo (`HT2ML\Core\…`)                                   | Extensões que usam           | Papel para a extensão                                            |
| ---------------------------------------------------------- | ---------------------------- | ---------------------------------------------------------------- |
| `Support\Modules\ModuleRegistry`                           | rh, fiscal-br, exemplo-demo  | **O canal.** Módulo, rotas, seeders, permissões, menu, catálogos |
| `Models\AdminUser`                                         | rh, fiscal-br, exemplo-demo  | Usuário administrativo, nas policies                             |
| `Models\Concerns\Auditavel`                                | rh, fiscal-br, exemplo-demo  | Registro no activity log (obrigatório — guard de arquitetura)    |
| `Models\Contracts\UsaSoftDeletes`                          | rh, fiscal-br, exemplo-demo  | Torna o soft delete visível ao type-system da lixeira            |
| `Livewire\Concerns\ComLixeira`                             | rh, fiscal-br, exemplo-demo  | Lixeira, restauração e exclusão definitiva                       |
| `Livewire\Concerns\EmiteNotificacoes`                      | rh, fiscal-br, exemplo-demo  | Toasts padronizados                                              |
| `Models\Empresa`                                           | rh, documentos, exemplo-demo | O tenant                                                         |
| `Models\Concerns\BelongsToEmpresa`                         | rh, exemplo-demo             | Escopo por empresa ativa + preenchimento de `empresa_id`         |
| `Support\Tenancy\TenantContext`                            | rh, exemplo-demo             | Empresa/filial ativa                                             |
| `Livewire\Concerns\FiltraPorMultiEmpresa`                  | rh, exemplo-demo             | Filtro multiempresa das listagens                                |
| `Livewire\Concerns\ExportaPdf`                             | rh, exemplo-demo             | Exportação PDF das listagens                                     |
| `DTOs\Admin\Export\ExportavelDTO`                          | rh, exemplo-demo             | Contrato da exportação                                           |
| `Support\Money\Money`                                      | rh, exemplo-demo             | Formatação de dinheiro (centavos)                                |
| `Livewire\Grid\RecursoTable`                               | rh, fiscal-br                | Base declarativa das tabelas                                     |
| `Livewire\Grid\Campo`                                      | rh, fiscal-br                | Declaração de campo da `RecursoTable`                            |
| `Livewire\Concerns\ComFicha`                               | fiscal-br, exemplo-demo      | Drawer de visualização                                           |
| `Livewire\Grid\RecursoMultiEmpresa`                        | rh                           | As seis composições multiempresa de uma vez                      |
| `Livewire\Grid\RecursoIndex`                               | fiscal-br                    | Base declarativa da página de listagem                           |
| `Livewire\Concerns\ComAcoesCrud`                           | fiscal-br                    | Ações de linha padronizadas                                      |
| `Casts\MoneyCast`                                          | rh                           | Cast de coluna em centavos                                       |
| `Rules\Cpf`                                                | rh                           | Validação de CPF                                                 |
| `Models\Referencia\Cargo`                                  | rh                           | Catálogo de cargos                                               |
| `Models\Filial`                                            | exemplo-demo                 | Filial do tenant                                                 |
| `Support\Html\HtmlSanitizer`                               | exemplo-demo                 | Sanitização de `richtext`                                        |
| `Enums\ModuloAcesso`                                       | exemplo-demo                 | Semente das áreas de acesso do core (`ModuloAcesso::Negocio`)    |
| `Support\Referencia\CsvReferenceSeeder`                    | fiscal-br                    | Base dos seeders de catálogo mantidos por CSV                    |
| `Enums\Referencia\OrigemRegistro`                          | fiscal-br                    | `sync` \| `manual` — separa linha do sync de linha do cliente    |
| `Models\Concerns\TemOrigem`                                | fiscal-br                    | Trait que aplica o default de origem                             |
| `Models\Contracts\TemOrigemDeclarada`                      | fiscal-br                    | Torna a trait visível ao PHPStan                                 |
| `Policies\Referencia\Concerns\ProtegeRegistroSincronizado` | fiscal-br                    | Bloqueia escrita em linha `sync`                                 |

Só em teste: `HT2ML\Core\Database\Seeders\RolePermissionSeeder`, além de
`AdminUser`, `Empresa` e `TenantContext`.

Três observações sobre a lista:

- **`ModuloBuilder`, `RecursoBuilder`, `EscopoDeRota` e `AreaDeAcesso` não aparecem.**
  Não porque não sejam usados: o builder chega como retorno de
  `ModuleRegistry::modulo()`, e nenhuma extensão de hoje declara rotas fora do
  escopo Admin nem área própria. São superfície do core mesmo assim — o
  [guia de extensão](guias/criar-extensao.md) os documenta.
- **`ModuloAcesso` é semente, não catálogo.** O enum lista as onze áreas do core. O
  conjunto de áreas é aberto (`config('access.areas')` +
  `ModuleRegistry::areaDeAcesso()`), e uma extensão nova não precisa do enum.
- **`ht2ml/extensao-api` e `ht2ml/extensao-ia` não usam nenhum símbolo do core**
  (conferido com `grep -rn 'HT2ML\\Core' packages/extensao-api packages/extensao-ia`,
  zero resultados) e não declaram `ht2ml/core` no `composer.json`. São bibliotecas
  que só dependem do Laravel. A `extensao-api` depende do **canal** de API do core
  em tempo de execução (`EscopoDeRota::Api`, rotas sob `/api`), mas sem referência
  de classe; por isso o README dela pede `ht2ml/core` ≥ 0.5 (a v0.4.x não tem o
  escopo `Api`).

## Design system Blade — resolvido, e não era o que parecia

Esta seção dizia que os componentes eram "a parte da superfície que mais resiste
ao empacotamento", porque não viajam pelo Composer como classes. **Estava
errado**, e o teste é de duas linhas:

```php
Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components');
// e um `<x-shared.button />` escrito no app resolve para o blade do pacote
```

Sem prefixo, o componente do pacote atende o nome que o consumidor já escreve.
Nenhum dos blades consumidores muda uma linha, e o app hospedeiro continua
vencendo: um componente em `resources/views/components` sobrescreve o do pacote,
então dá para customizar um por vez.

**Os 68 componentes `shared/` e os 31 `admin/` estão em
`packages/core/resources/views/components/`** (contados em 2026-10-25). As extensões
usam 26 deles (fora `x-slot`): 21 `x-shared.*` mais `x-admin.ficha-drawer`,
`x-admin.ficha-section`, `x-admin.form-footer`, `x-admin.page-header` e
`x-admin.row-actions`.

## A metade que o Composer não carrega

O design system tem um lado PHP/Blade e um lado de assets. O Composer entrega o
primeiro. O segundo tem **dois** problemas distintos, e só um deles está
resolvido.

### Resolvido: o Tailwind não enxerga `vendor/`

A detecção automática do Tailwind 4 varre o projeto, mas **pula caminhos
ignorados pelo git** — e `vendor/` é um deles. Medido:

| Arquivo com uma classe única | Classe chega ao CSS? |
| ---------------------------- | -------------------- |
| sob `packages/` (versionado) | sim                  |
| sob `vendor/` (gitignored)   | **não**              |

Num app que instala `ht2ml/core` por Composer, os blades do pacote vivem em
`vendor/ht2ml/core/resources/views`. Sem intervenção, o CSS sai **sem nenhuma
das classes que só o núcleo usa** — o admin renderiza sem estilo, e nada no
monorepo denuncia isso, porque lá `packages/` é versionado e a detecção pega.

O pacote passou a trazer `resources/css/core.css`, com os `@source` relativos
ao próprio arquivo — funcionam igual no monorepo e instalado. O app importa:

```css
@import '../../vendor/ht2ml/core/resources/css/core.css';
```

Provado nos dois sentidos: com o import a classe aparece; sem ele, e com
`packages/` oculto da detecção (simulando o consumidor), some.

### Em aberto: o tema e as dependências npm

Os 40 arquivos de CSS (tema Inspinia, temas de cor, componentes) e os 18 de JS
continuam em `resources/` no app (contados em 2026-10-25). Movê-los para o pacote
esbarra num limite real: **o Composer não carrega dependências npm.** A cadeia atual
importa `choices.js`, `dropzone`, `flatpickr`, `quill`, `preline`, `simplebar`,
`moment`, mais os plugins `@tailwindcss/typography`, `@tailwindcss/forms` e
`@iconify/tailwind4`.

Um pacote Composer com design system resolve isso publicando o CSS/JS e
documentando as dependências npm que o consumidor precisa declarar — é o que
Filament e afins fazem. É uma decisão de design própria, não uma movimentação
de arquivos, e por isso não entrou nesta fatia. Na prática, o skeleton leva
`resources/` e `package.json` do app, e o produto roda `npm install && npm run build`.

Só três componentes Alpine são nomeados em JS (`afRowActions`, `afDatePicker`,
`comboBox`); todo o resto usa `x-data` inline e viaja no próprio blade.

## Dependências de vendor

Toda extensão declara o que usa. O CI prova isso a cada PR: o passo "Cada pacote
declara o que usa" roda o `composer-dependency-analyser` em cada pacote
(ver [`testes-e-guardas.md`](testes-e-guardas.md)). `require` de cada uma, lido dos
`composer.json` em 2026-10-25:

| Extensão                | `extra.ht2ml.tipo` | `require` (além de `php`)                                                                                                                      |
| ----------------------- | ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `extensao-rh`           | `modulo`           | `ht2ml/core`, `illuminate/support`, `livewire/livewire`, `power-components/livewire-powergrid`                                                 |
| `extensao-fiscal-br`    | `modulo`           | `ext-mbstring`, `ht2ml/core`, `illuminate/{contracts,database,support,validation}`, `livewire/livewire`, `power-components/livewire-powergrid` |
| `extensao-exemplo-demo` | `modulo`           | `ht2ml/core`, `illuminate/support`, `livewire/livewire`, `power-components/livewire-powergrid`, `spatie/laravel-permission`                    |
| `extensao-documentos`   | `biblioteca`       | `ht2ml/core`, `illuminate/support`                                                                                                             |
| `extensao-api`          | `biblioteca`       | `dedoc/scramble`, `illuminate/{contracts,http,support}`, `laravel/sanctum`, `symfony/http-foundation`, `symfony/http-kernel`                   |
| `extensao-ia`           | `biblioteca`       | `illuminate/{database,http,support}`                                                                                                           |

No monorepo, `ht2ml/core` aparece como `@dev` (path repository). O `make:modulo`
escreve a restrição a partir da versão **instalada** do core (`^0.4` num produto
com a v0.4.x), e `@dev` só quando o core vem de path repository.

## As quatro descobertas automáticas que morrem dentro de um pacote

O Laravel — e o Livewire — encontram sozinhos várias coisas em `app/`. **Nada
disso vale dentro de um pacote**, e as quatro falham do pior jeito possível: sem
erro nenhum. O comportamento simplesmente deixa de acontecer.

| Descoberta                              | O que sumiu ao mover                                                                  | Como se manifesta                                          |
| --------------------------------------- | ------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `App\Models\X` → `App\Policies\XPolicy` | `EmpresaPolicy`                                                                       | Autorização **desligada**; `getPolicyFor()` devolve `null` |
| `app/Console/Commands`                  | Os 8 comandos do core, incluindo `access:sync` e `referencia:sync` — passos de deploy | Somem do `artisan`                                         |
| `app/Listeners`                         | `RegistrarLoginAdmin`                                                                 | Histórico de login **para de gravar**                      |
| `App\Livewire` → alias por convenção    | As telas do admin                                                                     | 500 com "Unable to find component"                         |

Os 8 comandos são as classes de `packages/core/src/Console/Commands`: `access:sync`,
`access:expirar`, `referencia:sync`, `make:modulo`, `make:recurso`, `make:regra`,
`ht2ml:doutor` e a lápide `make:extensao`. Referência: [`comandos.md`](comandos.md).

Três delas exigem **substituir** a descoberta por declaração explícita. A quarta
é a exceção: `Livewire::addLocation(classNamespace: 'HT2ML\Core\Livewire')`
**restaura** a mesma convenção para o namespace do pacote, então os aliases
seguem idênticos e nenhum blade consumidor muda. Uma linha no lugar de dezenas de
chamadas `Livewire::component()` — e continua valendo para todo componente novo.

O `CoreServiceProvider` cuida das quatro, e cada uma tem teste de guarda em
`tests/Feature/Core/`. Os testes das três primeiras falham nos **dois** sentidos:
se algo sumir do registro, ou se houver classe no pacote que ninguém registra.
Foi essa segunda direção que pegou a `RolePolicy` migrada com a declaração
esquecida para trás.

**A mesma regra vale para toda extensão.** O ServiceProvider dela registra
`Gate::policy()` para cada model (o `make:recurso` escreve essa linha),
`$this->commands([...])` para os comandos (a `extensao-api` faz isso com
`api:openapi`), `Event::listen()` para listeners, e `Livewire::component()` ou
`Livewire::addLocation()` para os componentes. Ao mover qualquer coisa para um
pacote, a pergunta é sempre a mesma: _o framework encontrava isso sozinho?_ Se sim,
agora é preciso declarar — ou ensinar a convenção ao namespace novo, quando houver
como.

## A prova completa: um produto novo, do zero

O plano definia sucesso como _instalar num Laravel limpo, fora do monorepo_. O
teste ficou mais forte que isso — hoje um produto **nasce de um comando**. Os
pacotes são privados e não estão no Packagist, então o `create-project` precisa
apontar o repositório do skeleton e ter credencial de GitHub (chave SSH com acesso
aos repositórios `ht2ml-*`, ou token):

```bash
composer create-project ht2ml/skeleton meu-produto \
  --repository='{"type":"vcs","url":"git@github.com:leonardozaneladias/ht2ml-skeleton.git","no-api":true}'
cd meu-produto
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

Passo a passo, com o que fazer quando a máquina não tem chave SSH:
[`guias/produto-via-skeleton.md`](guias/produto-via-skeleton.md).

Medição original, de 2026-08-24, num diretório vazio, com os pacotes vindo dos
repositórios privados por Composer (`Extracting archive`, não symlink). Não foi
repetida em 2026-10-25:

| Verificação                                       | Resultado                    |
| ------------------------------------------------- | ---------------------------- |
| `ht2ml/core` instalado e descoberto               | ✅                           |
| Migrations do pacote                              | 62 passos                    |
| `municipios` semeados do pacote                   | **5.571**                    |
| Perfis e usuários administrativos                 | 2 e 2                        |
| Permissões no banco                               | 77                           |
| Rotas `admin.*`                                   | 124                          |
| `GET /admin/login`                                | **HTTP 200**, com formulário |
| Classes Tailwind de blades em `vendor/ht2ml/core` | presentes no CSS             |

A última linha fecha o ciclo da correção do `@source`: o Tailwind varreu os
blades **dentro de `vendor/`**, que é o cenário que o monorepo não reproduz.

A parte "core sozinho num Laravel limpo" deixou de ser manual: o job `core-sozinho`
do CI (guard A4) cria um `laravel/laravel`, instala só o `ht2ml/core`, migra, roda
`access:sync` e `ht2ml:doutor`, gera um módulo e um recurso e confere a rota e as
seis permissões. Ver [`testes-e-guardas.md`](testes-e-guardas.md).

### Cinco defeitos que só esta prova revelou

Nenhum deles era detectável de dentro do monorepo — não por falta de cobertura,
por impossibilidade lógica: lá, tudo está instalado.

1. **Rotas do pacote referenciando o app.** `routes/admin.php` levou junto o
   bloco do módulo de exemplo, apontando para `App\Livewire\...`. Dentro do
   monorepo a classe existe; fora, `Invalid route action`.
2. **Migration de vendor sem a dependência.** `create_pulse_tables` importava
   `Laravel\Pulse`, que o core não declara — nem usa.
3. **Os CSVs dos catálogos ficaram no app.** Os seeders foram para o pacote e
   resolviam o caminho em `database_path()`. Só semeavam porque o monorepo por
   acaso tinha os arquivos.
4. **O core não declarava seis pacotes que usa** — PowerGrid (88 usos),
   `maatwebsite/excel`, o QR do 2FA, o dompdf. Funcionava porque o app
   declarava.
5. **`RolePermissionSeeder` concedia permissão de extensão ausente**, guardado
   por um flag de env em vez da realidade. O primeiro `migrate --seed` de
   qualquer produto novo morria. Hoje a permissão `exemplos.listar` do gestor é
   concedida pela própria extensão (`PermissoesDoGestorSeeder`, registrado com
   `ModuleRegistry::seeder()`).
