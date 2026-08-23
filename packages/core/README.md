# ht2ml/core

O núcleo da plataforma. Hoje contém o que a medição em
[`docs/superficie-do-core.md`](../../docs/superficie-do-core.md) mostrou que uma
extensão consome — e cresce a cada fatia, até que `app/` guarde só código de
produto ([ADR-0017](../../docs/architecture/adrs/ADR-0017-produto-novo-via-skeleton.md)).

## O que já está aqui

| Símbolo | Papel |
| --- | --- |
| `Support\Modules\ModuleRegistry` | **O canal.** Rotas, seeders, permissões, itens de menu e catálogos de referência |
| `Support\Referencia\CsvReferenceSeeder` | Base dos seeders de catálogo mantidos por CSV |
| `Exceptions\Referencia\ImportacaoReferenciaException` | Faz o seed falhar alto em vez de gravar catálogo vazio |
| `Enums\ModuloAcesso` | Os módulos de acesso do core |
| `Enums\Referencia\OrigemRegistro` | `sync` \| `manual` — separa linha do sync de linha do cliente |
| `Models\Concerns\TemOrigem` | Trait que aplica o default de origem |
| `Models\Contracts\TemOrigemDeclarada` | Torna a trait visível ao PHPStan |
| `Models\Contracts\UsaSoftDeletes` | Mesmo padrão, para soft delete |
| `Policies\Referencia\Concerns\ProtegeRegistroSincronizado` | Bloqueia escrita em linha `sync` |

## O que ainda falta

`AdminUser` e os quatro Concerns de Livewire (`ComAcoesCrud`, `ComFicha`,
`ComLixeira`, `EmiteNotificacoes`), mais os 11 componentes Blade do design
system. `AdminUser` sozinho aparece em 168 arquivos — vem numa fatia própria,
junto com `Empresa`, `Filial` e a configuração de autenticação.

Enquanto esses não vierem, uma extensão **ainda não instala fora do monorepo**.
