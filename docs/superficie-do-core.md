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

Nove já vivem em `ht2ml/core`. Os quatro pendentes estão marcados.

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
| `Models\Concerns\Auditavel`                                | ⏳ `app/`       | Registro no activity log                                                                                             |
| `Models\AdminUser`                                         | ⏳ `app/`       | Usuário administrativo. **168 arquivos** o referenciam; arrasta `Empresa`, `Filial`, a config de auth e as factories |
| `Livewire\Concerns\ComAcoesCrud`                           | ⏳ `app/`       | Ações de linha padronizadas                                                                                          |
| `Livewire\Concerns\ComFicha`                               | ⏳ `app/`       | Drawer de visualização                                                                                               |
| `Livewire\Concerns\ComLixeira`                             | ⏳ `app/`       | Lixeira, restauração e exclusão definitiva                                                                           |
| `Livewire\Concerns\EmiteNotificacoes`                      | ⏳ `app/`       | Toasts padronizados                                                                                                  |

Só em teste: `Database\Seeders\RolePermissionSeeder`.

## 11 componentes Blade do design system

`x-admin.page-header` · `x-admin.ficha-drawer` · `x-admin.ficha-section` ·
`x-admin.form-footer` · `x-shared.button` · `x-shared.card` ·
`x-shared.field-display` · `x-shared.input` · `x-shared.select-search` ·
`x-shared.textarea` · `x-slot`

Estes não viajam pelo Composer como classes: dependem de o app hospedeiro
registrar o mesmo namespace de componentes. É a parte da superfície que mais
resiste ao empacotamento.

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

## O que ainda falta para a prova definitiva

O plano define sucesso como _instalar a extensão num Laravel limpo, fora do
monorepo_. Hoje isso **não passa**, e a razão é exatamente esta lista: os 13
símbolos `App\...` e os 11 componentes Blade não têm pacote que os entregue.
A prova fica bloqueada até `ht2ml/core` existir — e esta é a especificação dele.
