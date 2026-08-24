# Superfície do core — o que uma extensão consome

Este documento é **medido, não projetado**. Ele nasceu da primeira extração real
(`ht2ml/extensao-fiscal-br`, PR #73): varrendo o pacote extraído por todo símbolo
que ele importa de fora de si mesmo, sai a lista exata do que o core precisa
exportar para que uma extensão instale **fora do monorepo**.

Enquanto essa superfície viver em `App\...`, nenhuma extensão instala em outro
projeto — ela depende de classes que o Composer não sabe entregar. É esse o
conteúdo de `ht2ml/core` previsto na Fase 2 do plano.

> **Estado:** `ht2ml/core` **existe** e já entrega 9 dos 13 símbolos. As quatro
> pendências e os 11 componentes Blade estão marcadas na tabela abaixo.

## Como reproduzir a medição

```bash
cd packages/<extensao>
grep -rhoE '^use +[A-Za-z0-9_\\]+' --include='*.php' . | sort -u   # símbolos PHP
grep -rhoE '<x-[a-z0-9.-]+' --include='*.blade.php' . | sort -u    # componentes Blade
```

Tudo que não começar com o namespace do próprio pacote é superfície do core ou
dependência de vendor.

## 13 símbolos PHP, todos exigidos em produção

**Os quinze vivem em `ht2ml/core`.** A medição original está satisfeita.

| Símbolo                                                    | Onde vive       | Papel para a extensão                                                                                                |
| ---------------------------------------------------------- | --------------- | -------------------------------------------------------------------------------------------------------------------- |
| `Support\Modules\ModuleRegistry`                           | ✅ `ht2ml/core` | **O canal.** Rotas, seeders, permissões, itens de menu e catálogos de referência                                     |
| `Support\Referencia\CsvReferenceSeeder`                    | ✅ `ht2ml/core` | Base dos seeders de catálogo mantidos por CSV                                                                        |
| `Enums\ModuloAcesso`                                       | ✅ `ht2ml/core` | Os módulos de acesso do core                                                                                         |
| `Enums\Referencia\OrigemRegistro`                          | ✅ `ht2ml/core` | `sync` \| `manual` — separa linha do sync de linha do cliente                                                        |
| `Exceptions\Referencia\ImportacaoReferenciaException`      | ✅ `ht2ml/core` | Faz o seed falhar alto em vez de gravar catálogo vazio                                                               |
| `Models\Concerns\TemOrigem`                                | ✅ `ht2ml/core` | Trait que aplica o default de origem                                                                                 |
| `Models\Contracts\TemOrigemDeclarada`                      | ✅ `ht2ml/core` | Torna a trait visível ao PHPStan                                                                                     |
| `Models\Contracts\UsaSoftDeletes`                          | ✅ `ht2ml/core` | Mesmo padrão, para soft delete                                                                                       |
| `Policies\Referencia\Concerns\ProtegeRegistroSincronizado` | ✅ `ht2ml/core` | Bloqueia escrita em linha `sync`                                                                                     |
| `Models\Concerns\Auditavel`                                | ✅ `ht2ml/core` | Registro no activity log                                                                                             |
| `Models\AdminUser`                                         | ✅ `ht2ml/core` | Usuário administrativo. **168 arquivos** o referenciam; arrasta `Empresa`, `Filial`, a config de auth e as factories |
| `Livewire\Concerns\ComAcoesCrud`                           | ✅ `ht2ml/core` | Ações de linha padronizadas                                                                                          |
| `Livewire\Concerns\ComFicha`                               | ✅ `ht2ml/core` | Drawer de visualização                                                                                               |
| `Livewire\Concerns\ComLixeira`                             | ✅ `ht2ml/core` | Lixeira, restauração e exclusão definitiva                                                                           |
| `Livewire\Concerns\EmiteNotificacoes`                      | ✅ `ht2ml/core` | Toasts padronizados                                                                                                  |

Só em teste: `HT2ML\Core\Database\Seeders\RolePermissionSeeder`.

## Design system Blade — resolvido, e não era o que parecia

Esta seção dizia que os componentes eram "a parte da superfície que mais resiste
ao empacotamento", porque não viajam pelo Composer como classes. **Estava
errado**, e o teste é de duas linhas:

```php
Blade::anonymousComponentPath(__DIR__ . '/../resources/views/components');
// e um `<x-shared.button />` escrito no app resolve para o blade do pacote
```

Sem prefixo, o componente do pacote atende o nome que o consumidor já escreve.
Nenhum dos ~200 blades consumidores muda uma linha, e o app hospedeiro continua
vencendo: um componente em `resources/views/components` sobrescreve o do pacote,
então dá para customizar um por vez.

**68 componentes `shared/` e o `admin/row-actions` já estão em `ht2ml/core`.** Os
30 `admin/` restantes ficam para a fatia de branding/menu/settings, porque
dependem de `BrandingService`, `AppearanceService`, `MenuService`,
`LoginSettings`, `SegurancaSettings`, `NotificacaoService` e
`ImpersonationContext`.

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

Os 37 arquivos de CSS (tema Inspinia, temas de cor, componentes) e os 18 de JS
continuam em `resources/` no app. Movê-los para o pacote esbarra num limite
real: **o Composer não carrega dependências npm.** A cadeia atual importa
`choices.js`, `dropzone`, `flatpickr`, `quill`, `preline`, `simplebar`,
`moment`, mais os plugins `@tailwindcss/typography`, `@tailwindcss/forms` e
`@iconify/tailwind4`.

Um pacote Composer com design system resolve isso publicando o CSS/JS e
documentando as dependências npm que o consumidor precisa declarar — é o que
Filament e afins fazem. É uma decisão de design própria, não uma movimentação
de arquivos, e por isso não entrou nesta fatia.

Só três componentes Alpine são nomeados em JS (`afRowActions`, `afDatePicker`,
`comboBox`); todo o resto usa `x-data` inline e viaja no próprio blade.

## Dependências de vendor

`ht2ml/extensao-fiscal-br` passou a declará-las (PR desta medição). As demais
extensões ainda não — `packages/extensao-rh/composer.json` declara apenas
`php: ^8.4`, embora use Livewire e PowerGrid.

| Pacote                                                                                       | Por quê                              |
| -------------------------------------------------------------------------------------------- | ------------------------------------ |
| `illuminate/contracts`, `illuminate/database`, `illuminate/support`, `illuminate/validation` | Model, migration, facades, `Rule`    |
| `livewire/livewire`                                                                          | Componentes e atributos              |
| `power-components/livewire-powergrid`                                                        | As tabelas                           |
| `laravel/framework` (dev)                                                                    | Os testes bootam a aplicação inteira |

## As quatro descobertas automáticas que morrem dentro de um pacote

O Laravel — e o Livewire — encontram sozinhos várias coisas em `app/`. **Nada
disso vale dentro de um pacote**, e as quatro falham do pior jeito possível: sem
erro nenhum. O comportamento simplesmente deixa de acontecer.

| Descoberta                              | O que sumiu ao mover                                                                  | Como se manifesta                                          |
| --------------------------------------- | ------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `App\Models\X` → `App\Policies\XPolicy` | `EmpresaPolicy`                                                                       | Autorização **desligada**; `getPolicyFor()` devolve `null` |
| `app/Console/Commands`                  | Os 5 comandos do core, incluindo `access:sync` e `referencia:sync` — passos de deploy | Somem do `artisan`                                         |
| `app/Listeners`                         | `RegistrarLoginAdmin`                                                                 | Histórico de login **para de gravar**                      |
| `App\Livewire` → alias por convenção    | As 64 telas do admin                                                                  | 500 com "Unable to find component"                         |

Três delas exigem **substituir** a descoberta por declaração explícita. A quarta
é a exceção: `Livewire::addLocation(classNamespace: 'HT2ML\Core\Livewire')`
**restaura** a mesma convenção para o namespace do pacote, então os aliases
seguem idênticos e nenhum blade consumidor muda. Uma linha no lugar de 64
chamadas `Livewire::component()` — e continua valendo para todo componente novo.

O `CoreServiceProvider` cuida das quatro, e cada uma tem teste de guarda em
`tests/Feature/Core/`. Os testes das três primeiras falham nos **dois** sentidos:
se algo sumir do registro, ou se houver classe no pacote que ninguém registra.
Foi essa segunda direção que pegou a `RolePolicy` migrada com a declaração
esquecida para trás.

Ao mover qualquer coisa nova para o pacote, a pergunta é sempre a mesma: _o
framework encontrava isso sozinho?_ Se sim, agora é preciso declarar — ou
ensinar a convenção ao namespace novo, quando houver como.

## A prova completa: um produto novo, do zero

O plano definia sucesso como _instalar num Laravel limpo, fora do monorepo_. O
teste ficou mais forte que isso — hoje um produto **nasce de um comando**:

```bash
composer create-project ht2ml/skeleton meu-produto
cd meu-produto
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

Medido num diretório vazio, com os pacotes vindo dos repositórios privados por
Composer (`Extracting archive`, não symlink):

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
   qualquer produto novo morria.
