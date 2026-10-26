# Lixeira (soft-delete) — excluir, restaurar e excluir definitivamente

Dados de manutenção deixam de ser apagáveis de forma irreversível: ganham uma
**lixeira** com restauração e exclusão definitiva controlada por permissão.

## `ativo` × `deleted_at` — conceitos independentes

| Campo                        | Significado                              | Efeito na listagem                       |
| ---------------------------- | ---------------------------------------- | ---------------------------------------- |
| `ativo` (boolean)            | liga/desliga **operacional** do registro | segue **visível** (filtrável por status) |
| `deleted_at` (`SoftDeletes`) | **lixeira**                              | **some** da listagem; restaurável        |

Os dois coexistem e não se confundem: desativar ≠ excluir. O `deleted_at` é o
soft-delete do Eloquent; o `ativo` é regra de negócio.

## Arquitetura genérica

- **Trait `HT2ML\Core\Livewire\Concerns\ComLixeira`** — dá a qualquer tabela PowerGrid de
  um model com `SoftDeletes` o fluxo completo: alternar ativos/lixeira
  (`verLixeira` + `alternarLixeira`), e os handlers `excluir` / `restaurar` /
  `excluirDefinitivo` (com `solicitar*` montando o bridge `confirm` do
  SweetAlert2). Os eventos `#[On]` são namespaced por `$tableName` para não
  colidir entre grids. Compõe **por fora** do escopo multi-empresa:
  `aplicarLixeira($this->aplicarEscopoMultiEmpresa(Model::query()))` — os global
  scopes `empresa` e `SoftDeletingScope` são independentes.
- **Contrato `HT2ML\Core\Models\Contracts\UsaSoftDeletes`** — marca um model que usa
  `SoftDeletes`, expondo `restore()`/`trashed()` ao type-system para a
  manipulação genérica do trait. Todo model soft-deletável o implementa.
- **`Auditavel`** já loga `deleted`/`restored`/`forceDeleted`.

### Hooks de extensão (opcionais, default sem efeito)

| Hook                                    | Quando                  | Exemplo                                           |
| --------------------------------------- | ----------------------- | ------------------------------------------------- |
| `bloqueioExclusao(Model): ?string`      | antes do soft-delete    | Empresa ativa/última; anti-self-delete de usuário |
| `bloqueioRestauracao(Model): ?string`   | antes do restore        | colisão de e-mail de usuário (D3)                 |
| `textoExcluirDefinitivo(Model): string` | confirm do force-delete | aviso de cascata física (Empresa)                 |

## Permissões — 3 níveis por recurso

A base da permissão é `{modulo}.{recurso}` para recurso de módulo (ex.: `rh.departamentos`),
derivada pelo `RecursoBuilder`. Os recursos do core e os legados de extensão usam só
`{recurso}` (ex.: `empresas`, `cargos`, `cnaes`, `exemplos`).

- `{base}.deletar` → mover para a lixeira (soft-delete);
- `{base}.restaurar` → restaurar da lixeira;
- `{base}.excluir_permanente` → exclusão definitiva (force-delete).

Um recurso declarado com `'sem_lixeira' => true` (ou gerado com `--sem-soft-delete`) tem só
`deletar`, sem as outras duas.

`excluir_permanente` **não é atribuída a nenhum perfil por padrão**; como o
super-admin tem bypass no `Gate::before`, o force-delete fica de fato só para
super-admin sem hard-code de papel. As policies mapeiam `restore` →
`{base}.restaurar` e `forceDelete` → `{base}.excluir_permanente`.

## Entidades com lixeira

Medido em 2026-10-25: todo model que implementa `UsaSoftDeletes` em `packages/*/src/Models`
(17 models).

| Entidade                                                                                      | Observações                                                                                                                                                                                                  |
| --------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Empresa                                                                                       | guardas: não exclui a **ativa** nem a **última**; force-delete cascateia fisicamente para filiais/acessos/vínculos (aviso no confirm)                                                                        |
| Filial                                                                                        | lixeira gerida **dentro de Empresas** (no `FormEmpresa`), reusando `empresas.*`; a Matriz não é excluível                                                                                                    |
| AdminUser                                                                                     | login do excluído bloqueado pelo `SoftDeletingScope`; anti-self-delete + hierarquia; e-mail liberado enquanto na lixeira (índice unique **parcial** `WHERE deleted_at IS NULL`); a restauração checa colisão |
| Anexo                                                                                         | soft-delete **técnico** (retenção/auditoria): mantém o arquivo físico; o binário só some no force-delete (evento `forceDeleted`)                                                                             |
| Catálogos de referência do core: Banco, Cargo, Estado, Moeda, Município, País, TipoLogradouro | `HT2ML\Core\Models\Referencia\*`; globais (sem empresa). Linhas de origem `sync` são protegidas contra escrita pela policy (`ProtegeRegistroSincronizado`)                                                   |
| Fiscal: Cnae, Cfop, Ncm                                                                       | no pacote `ht2ml/extensao-fiscal-br`; mesmo padrão dos catálogos do core                                                                                                                                     |
| RH: Departamento, Funcionario                                                                 | no pacote `ht2ml/extensao-rh`                                                                                                                                                                                |
| Exemplo                                                                                       | piloto/gabarito de referência, no pacote `ht2ml/extensao-exemplo-demo`                                                                                                                                       |

**Fora da lixeira:** ACL (papéis/permissões) e Menu.

## Gerador (`make:recurso`)

Todo recurso novo nasce com a lixeira quando o soft-delete está ativo (padrão).
`--sem-soft-delete` produz a saída antiga (sem lixeira). Ver
[`criar-recurso.md`](criar-recurso.md).

> **Models de pacote:** o resolver de factory do Laravel assume `App\`; um model
> em `HT2ML\…\Models` precisa de `protected static function newFactory()`
> apontando a factory do pacote. O `make:recurso --modulo=…` já gera esse método
> (ver os models do `extensao-rh`).
