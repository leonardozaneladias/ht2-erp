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

Só em teste: `Database\Seeders\RolePermissionSeeder`.

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

## A metade que o Composer não carrega — e essa é real

O design system tem um lado PHP/Blade e um lado de assets. O Composer entrega o
primeiro; o segundo continua no app:

|                 | Volume              |
| --------------- | ------------------- |
| `resources/js`  | 18 arquivos, 118 KB |
| `resources/css` | 37 arquivos, 134 KB |

O acoplamento é menor do que parece: só **três** componentes Alpine são nomeados
e definidos em JS (`afRowActions`, `afDatePicker`, `comboBox`); todo o resto usa
`x-data` inline, que viaja no próprio blade. O resto do lado de assets é Tailwind
e o tema Inspinia.

O caminho conhecido é `publishes()` no pacote mais, no app consumidor, um
`@source` do Tailwind 4 apontando para `vendor/ht2ml/core/resources`. Isso mexe
em `vite.config.js` e na configuração do Tailwind — é fatia própria, e é o que
separa "o pacote tem os blades" de "o pacote renderiza sozinho".

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

## As três descobertas automáticas que morrem dentro de um pacote

O Laravel encontra sozinho várias coisas em `app/`. **Nada disso vale dentro de
um pacote**, e as três falham do pior jeito possível: sem erro nenhum.

| Descoberta                              | O que sumiu ao mover                                                                  | Como se manifesta                                          |
| --------------------------------------- | ------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `App\Models\X` → `App\Policies\XPolicy` | `EmpresaPolicy`                                                                       | Autorização **desligada**; `getPolicyFor()` devolve `null` |
| `app/Console/Commands`                  | Os 5 comandos do core, incluindo `access:sync` e `referencia:sync` — passos de deploy | Somem do `artisan`                                         |
| `app/Listeners`                         | `RegistrarLoginAdmin`                                                                 | Histórico de login **para de gravar**                      |

Por isso o `CoreServiceProvider` declara tudo à mão, em três métodos
(`registrarPolicies`, `registrarComandos`, `registrarListeners`), e cada um tem
um teste de guarda em `tests/Feature/Core/` que falha se algo sumir **ou** se
houver classe no pacote que ninguém registra.

Ao mover qualquer coisa nova para o pacote, a pergunta é sempre a mesma: _o
Laravel encontrava isso sozinho?_ Se sim, agora é preciso declarar.

## O que ainda falta para a prova definitiva

O plano define sucesso como _instalar a extensão num Laravel limpo, fora do
monorepo_. Hoje isso **não passa**, e a razão é exatamente esta lista: os 13
símbolos `App\...` e os 11 componentes Blade não têm pacote que os entregue.
A prova fica bloqueada até `ht2ml/core` existir — e esta é a especificação dele.
