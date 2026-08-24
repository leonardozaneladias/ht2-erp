# 11 — Importação e Exportação (Excel)

> Como entram e saem funcionários **em lote** via planilha Excel — para carga inicial (migração de outro sistema), edição em massa e relatório. Reaproveita a infra de import/export **já existente no core** (`HT2ML\Core\Imports\BaseImport`, `HT2ML\Core\Exports\TabelaExport`, fila `exports`, PowerGrid `Exportable`) e estende para o **agregado multi-aba** (funcionário + filhas). O **schema é definido em [01](01-modelo-de-dominio.md)** (fonte de verdade); o log opcional de importação é `importacoes` ([01 §F](01-modelo-de-dominio.md)).
>
> Pacote: `ht2ml/extensao-rh` · namespace `HT2ML\Rh\` · views `rh::` · **PostgreSQL 16** · multi-tenant por `empresa_id`. **Faseamento:** pós-Fase 1 (depende de B2 — cadastro + filhas, [02](02-fase-1-blueprint.md)); o export simples da listagem já vem com B2 (PowerGrid).

Relacionados: [01](01-modelo-de-dominio.md) · [03](03-cadastro-pessoa-documentos.md) · [10](10-campos-personalizados.md) · [04](04-catalogos-configuraveis.md)

---

## 1. Reuso do core (não reinventar)

A base de import/export **já existe** no boilerplate e é reaproveitada — o RH só **estende**:

| Peça do core                                                                                | O que faz                                                                                                                                                                                                       | Uso no RH                                                                           |
| ------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| `HT2ML\Core\Imports\BaseImport`                                                                    | base de importação Excel: `SkipsOnFailure` + `WithValidation` + `WithHeadingRow` + `ToCollection`; valida por linha, acumula falhas, expõe `getErros(): list<{linha,campo,mensagem}>` e `getLinhasImportadas()` | base das sub-importações de cada aba (§4)                                           |
| `HT2ML\Core\Exports\TabelaExport` + `HT2ML\Core\DTOs\Admin\Export\ExportavelDTO`                          | export tabular **mono-aba** (colunas + linhas, `WithTitle`) para Excel/CSV                                                                                                                                      | export simples/da listagem (§6.1); o round-trip multi-aba usa export própria (§6.2) |
| `HT2ML\Core\Livewire\Concerns\ExportaExcel` · `HT2ML\Core\Actions\Admin\Export\ExportarTabelaExcelAction` | concern + Action que geram o arquivo                                                                                                                                                                            | reaproveitados pela tela de RH                                                      |
| PowerGrid `Exportable` (`TYPE_XLS`, `TYPE_CSV`)                                             | export da **listagem filtrada** direto da grade (já usado em `UsuariosTable`/`EmpresasTable`)                                                                                                                   | `FuncionarioTable` exporta a listagem corrente (§6.1)                               |
| Fila **`exports`** (Horizon)                                                                | processamento assíncrono de relatórios pesados                                                                                                                                                                  | importação/exportação em lote rodam aqui (§7)                                       |
| `maatwebsite/excel` (`^3.1`)                                                                | leitura/escrita de `.xlsx`/`.csv`                                                                                                                                                                               | `WithMultipleSheets` para o layout multi-aba (§4)                                   |

> A `BaseImport` é **mono-coleção** (`ToCollection`). Para o agregado multi-aba o RH usa `Maatwebsite\Excel\Concerns\WithMultipleSheets`, com **uma sub-import por aba** estendendo `BaseImport` — cada uma traz suas `regrasPorColuna()`/`importarLinha()`. O relatório de erros agrega o `getErros()` de todas as abas.

---

## 2. Planilha multi-aba (o agregado em uma pasta de trabalho)

O funcionário é um **agregado** (núcleo + filhas — [01 §3 Bloco B](01-modelo-de-dominio.md)). A planilha espelha isso em **abas**:

| Aba              | Tabela ([01](01-modelo-de-dominio.md)) | Cardinalidade  | Chave de ligação ao funcionário           |
| ---------------- | -------------------------------------- | -------------- | ----------------------------------------- |
| **Funcionarios** | `funcionarios` (núcleo + contratação)  | 1 linha/pessoa | — (é a âncora)                            |
| Contatos         | `funcionario_contatos`                 | N linhas       | `matricula` (ou `cpf`)                    |
| Enderecos        | `funcionario_enderecos`                | N linhas       | `matricula` (ou `cpf`)                    |
| DadosBancarios   | `funcionario_dados_bancarios`          | N linhas       | `matricula` (ou `cpf`)                    |
| Dependentes      | `funcionario_dependentes`              | N linhas       | `matricula` (ou `cpf`)                    |
| Documentos       | `funcionario_documentos` (metadados)   | N linhas       | `matricula` (ou `cpf`) + `tipo_documento` |

- A aba **Funcionarios** é processada **primeiro**; as abas-filhas referenciam a pessoa pela **chave de negócio** (§3), nunca por `id` interno (que o cliente não conhece).
- **Documentos** importa só **metadados** (tipo, número, validade…); o **binário** (`Anexo`) **não** vem por planilha — anexos seguem o upload seguro ([03 §8.3](03-cadastro-pessoa-documentos.md)). Multi-upload/ZIP de arquivos é o tema de [03 §8.5/§8.6](03-cadastro-pessoa-documentos.md), não desta importação.
- **PCD / `cid`** (dado de saúde — [01 §8](01-modelo-de-dominio.md)) **não** entram no layout padrão; carga de dados sensíveis é exceção tratada à parte (não em planilha trafegada por e-mail).

---

## 3. Chave de identificação e detecção insert vs update

A importação é **idempotente por chave de negócio** — reimportar a mesma planilha atualiza, não duplica.

- **`matricula` é a chave primária de negócio** (única por empresa — [01 §3 B1](01-modelo-de-dominio.md)). Tem **precedência**: se a linha traz `matricula`, ela manda.
- **`cpf` é a chave natural alternativa** (também único por empresa). Usado quando a linha **não** traz `matricula` (comum em carga vinda de outro sistema).
- **Precedência resolvida assim** (por linha da aba Funcionarios):
    1. tem `matricula` existente na empresa → **update** desse funcionário;
    2. sem `matricula`, mas `cpf` existe na empresa → **update** (e preenche/gera `matricula` se faltava);
    3. nenhuma das duas casa → **insert** (cria; se `matricula` veio vazia, **gera** pela regra de [03 §3.1](03-cadastro-pessoa-documentos.md) — `SugerirMatricula`, sequencial por empresa, zero-pad configurável).
- **Abas-filhas** ligam-se ao funcionário pela mesma chave (matrícula com precedência, CPF como alternativa). Uma linha-filha cuja chave **não** corresponde a nenhum funcionário (nem na planilha nem no banco) **vira erro de linha** (não cria filha órfã).

> A detecção usa o índice único parcial `(empresa_id, matricula)`/`(empresa_id, cpf)` `WHERE deleted_at IS NULL` ([01 §3 B1](01-modelo-de-dominio.md)). Registros na **lixeira** não são alvo de update silencioso pela importação (um CPF na lixeira não é "reaproveitado" sem ação explícita) — decisão alinhada ao comportamento de unicidade parcial. A **geração de matrícula em lote** (caso 3) **serializa** a sequência por empresa (lock/contador na transação) — não recalcula `max+1` ingenuamente por linha, senão duas linhas (ou dois jobs) colidiriam no índice único.

---

## 4. Validação e integridade de relacionamentos

Cada aba é uma sub-import estendendo `HT2ML\Core\Imports\BaseImport` — validação **por linha** com `regrasPorColuna()` (reaproveita as `Rules` do cadastro: `HT2ML\Core\Rules\Cpf`, `HT2ML\Rh\Rules\PisPasep`, PIX por tipo — [03 §3/§5](03-cadastro-pessoa-documentos.md)).

**Catálogos e referências são citados por código/nome, não por id:**

| Coluna na planilha           | Referência resolvida ([01](01-modelo-de-dominio.md)) | Como resolve                                              |
| ---------------------------- | ---------------------------------------------------- | --------------------------------------------------------- |
| `departamento` (nome/código) | `departamentos` (tenant)                             | `firstWhere(nome\|codigo)` na empresa → `departamento_id` |
| `cargo` (código CBO/nome)    | `cargos` (referência global CBO)                     | resolve → `cargo_id` (+ `cargo_nivel` derivado)           |
| `tipo_documento` (código)    | `tipos_documento` (tenant)                           | resolve → `tipo_documento_id`                             |
| `banco` (código/nome)        | `bancos` (referência)                                | resolve → `banco_id`                                      |
| `municipio`/`uf`/`pais`      | `municipios`/`estados`/`paises` (referência)         | resolve → FKs de endereço                                 |
| enums (`tipo_vinculo`, …)    | enums de [01 §4](01-modelo-de-dominio.md)            | aceita o **valor** do enum ou o **label** (mapeado)       |

- Uma referência **inválida** (departamento inexistente na empresa, CBO desconhecido) **vira erro de linha** com mensagem clara — **nunca** cria FK órfã nem catálogo "on the fly" (catálogo é criado pelo cliente — [04](04-catalogos-configuraveis.md)).
- **Transação por funcionário**: o núcleo + suas filhas são persistidos numa `DB::transaction` por pessoa. Se uma filha falha, **aquele** funcionário é revertido e marcado com erro — o lote continua nos demais (não grava "pela metade"). Isso difere do `SkipsOnFailure` linha-a-linha do `BaseImport`: a falha de validação pula a linha; a falha de **persistência do agregado** reverte o bloco do funcionário.
- `empresa_id` é **sempre** o da empresa ativa (`BelongsToEmpresa` auto-fill) — a planilha **não** carrega `empresa_id` (anti-injeção cross-tenant).

---

## 5. Campos personalizados na planilha (cruza com [10](10-campos-personalizados.md))

Colunas extras na aba **Funcionarios**, prefixadas (ex.: `cp_tamanho_camiseta`, `cp_matricula_legada`), são mapeadas para `funcionarios.dados_personalizados` ([01 §B1](01-modelo-de-dominio.md)):

- A importação lê as definições ativas (`campos_personalizados` da empresa — [10 §2.1](10-campos-personalizados.md)) e casa cada coluna `cp_<chave>` à sua definição.
- A validação reusa `regrasPersonalizadas()` ([10 §4](10-campos-personalizados.md)) — tipo, obrigatoriedade e opções valem na importação como valem na tela.
- Coluna `cp_<chave>` sem definição → **ignorada com aviso** no relatório (não falha o lote); campo obrigatório ausente → **erro de linha**.
- O **template** (§6.2) já emite as colunas `cp_*` da empresa e documenta os valores válidos na aba "Instruções".
- **Identificação na importação/atualização:** cada coluna `cp_<chave>` é casada à definição pela `chave` (estável); o **round-trip** (§6.2) reexporta os `cp_*` e reimporta atualizando pelos mesmos. Desativar/renomear uma definição muda as colunas emitidas — ver preservação de dados em [10 §2.5](10-campos-personalizados.md).
- **Listagens dinâmicas, filtros (JSONB/GIN), relatórios, dashboards e API** sobre campos personalizados: a visão completa (e o faseamento MVP × evolução) está em [10 §8/§8.1](10-campos-personalizados.md) — esta seção cobre só o recorte de **planilha**.

---

## 6. Exportação e round-trip

### 6.1 Export da listagem (já na Fase 1, via PowerGrid)

`FuncionarioTable` ([03 §1](03-cadastro-pessoa-documentos.md)) usa o `Exportable` do PowerGrid (`TYPE_XLS`, `TYPE_CSV`) — exporta **a listagem como está filtrada/ordenada na tela** (colunas da grade), respeitando a ACL hierárquica ([05](05-organograma-acl-hierarquica.md)) e a PII mascarada ([03 §10](03-cadastro-pessoa-documentos.md)). Bom para relatório rápido; **não** é o layout de reimportação.

### 6.2 Export round-trip (multi-aba, reimportável)

A exportação "round-trip" gera a **mesma estrutura multi-aba** que a importação aceita (§2), incluindo as colunas `cp_*`. Como `TabelaExport`/`ExportavelDTO` são **mono-aba**, o round-trip usa uma **export própria** (`FuncionarioRoundTripExport` com `WithMultipleSheets` — uma `FromArray`+`WithTitle` por aba); `TabelaExport` fica para o export simples (§6.1):

1. **Exportar** com os **filtros atuais** do `FuncionarioTable` (a seleção da tela define o conjunto exportado) → `.xlsx` multi-aba.
2. **Editar** no Excel (corrigir/atualizar em massa).
3. **Reimportar** → as linhas casam pela **matrícula** (§3) e **atualizam** os existentes; linhas novas viram inserts.

### 6.3 Template / modelo

Uma Action/comando (`GerarTemplateImportacaoFuncionarios`) emite um `.xlsx` **modelo**:

- todas as abas (§2) com seus **cabeçalhos** (nomes de coluna que o `WithHeadingRow` espera);
- uma **linha de exemplo** preenchida (descartada na importação real);
- uma aba **"Instruções / Legenda"** listando, **por empresa ativa**, os valores válidos: códigos/nomes de `departamentos`, `cargos` (CBO), `tipos_documento`, `tipos_afastamento`, os valores de cada enum ([01 §4](01-modelo-de-dominio.md)) e as colunas `cp_*` com seus tipos.

---

## 7. Execução assíncrona e relatório de erros

Importação de volume roda **assíncrona** (fila `exports`), não trava a request:

- A tela **envia o arquivo** (disco privado temporário) e despacha um **Job** na fila `exports`; o usuário acompanha o progresso e é **notificado ao concluir** (notificação do core + toast).
- O **resultado** é uma tela com: **total** de linhas, **criados**, **atualizados**, **com erro** e **download do relatório de erros** (`.xlsx` com a linha original + o motivo). O relatório **une duas fontes**: as _failures_ de validação do `BaseImport` (`getErros()`) **e** os erros de **persistência por funcionário** (transação revertida, §4) — capturados pela camada do RH, pois `getErros()` cobre só a validação, não a falha de gravação do agregado.
- O **log** opcional `importacoes` ([01 §F](01-modelo-de-dominio.md)) persiste status/contadores/`relatorio_erros` (JSONB) — alimenta a tela de resultado e a auditoria do que foi importado, e permite reconsultar uma importação passada.
- Exportações grandes seguem o mesmo caminho (fila + notificação), reaproveitando `ExportarTabelaExcelAction`/`GerarExportacao*Job` do core.

---

## 8. LGPD

Exportar funcionários **é exportar PII** (CPF, nome da mãe, dados bancários — [01 §8](01-modelo-de-dominio.md)). Tratamento:

- **Aviso explícito** na UI antes de exportar ("o arquivo contém dados pessoais; trate conforme a LGPD").
- **Auditoria da exportação**: registra quem exportou, quando, com quais filtros e quantas linhas (a operação é auditável mesmo não alterando dados).
- **Mascaramento opcional**: export "mascarado" (CPF/conta parciais) para perfis sem direito ao dado completo; export "completo" exige a permissão de ver o dado (alinhado a [03 §10](03-cadastro-pessoa-documentos.md)). **`cid`/saúde nunca** entram no export padrão.
- **Disco privado + expiração**: o arquivo gerado fica em disco privado, baixado por URL assinada temporária, e é **expurgado** após a janela de download (não acumula PII no servidor).

---

## 9. Permissões

| Permissão ([01 §10](01-modelo-de-dominio.md)) | O que cobre                                                                               |
| --------------------------------------------- | ----------------------------------------------------------------------------------------- |
| `rh.funcionarios.exportar`                    | exportar a listagem (§6.1) e o round-trip (§6.2) — **especial**, à mão em `config/rh.php` |
| `rh.funcionarios.importar`                    | importar a planilha multi-aba (§2) — **especial**                                         |

Ambas são **add-ons de `rh.funcionarios.*`** (recorte do recurso `funcionarios`, não recurso próprio — [01 §10](01-modelo-de-dominio.md)) e **especiais** (fora das âncoras CRUD), conferidas no servidor (Policy). O log `importacoes` é consultado dentro do fluxo de `importar` (sem permissão própria na Fase 1). A importação cria/edita funcionários **sob a mesma ACL** ([05](05-organograma-acl-hierarquica.md)): quem importa precisa poder criar/editar os registros resultantes na empresa ativa.

---

## 10. Faseamento

- **Já na Fase 1 (B2):** export da listagem filtrada via PowerGrid (§6.1) — vem "de graça" com `FuncionarioTable`.
- **Pós-Fase 1 (incremento):** importação multi-aba (§2–§5), template (§6.3), round-trip (§6.2), execução assíncrona + relatório de erros (§7), log `importacoes` ([01 §F](01-modelo-de-dominio.md)). Depende de **B2 completo** (núcleo + 5 filhas) e cruza com [10](10-campos-personalizados.md) (colunas `cp_*`).
- **Evolução:** importação de outras entidades (afastamentos/atestados em lote — [12](12-ausencias-faltas-atestados-afastamentos.md)), agendamento de importação recorrente, conector direto (sem planilha).

---

## 11. Checklist de implementação (incremento pós-Fase 1)

- [ ] `FuncionarioImport` (`WithMultipleSheets`) + uma sub-import por aba estendendo `HT2ML\Core\Imports\BaseImport` (`regrasPorColuna()`/`mensagensValidacao()`/`importarLinha()`); agregação de `getErros()`.
- [ ] Resolução de chave (matrícula→CPF→gera, §3) e de referências por código/nome → id (§4); transação por funcionário; `empresa_id` por auto-fill (nunca da planilha).
- [ ] Colunas `cp_*` → `dados_personalizados` reusando `regrasPersonalizadas()` ([10](10-campos-personalizados.md)).
- [ ] Export round-trip multi-aba via export própria `WithMultipleSheets` (não `TabelaExport`, que é mono-aba) com os filtros do `FuncionarioTable`; export da listagem (PowerGrid) já cobre §6.1.
- [ ] `GerarTemplateImportacaoFuncionarios` (cabeçalhos + exemplo + aba "Instruções" com catálogos/enums/`cp_*` da empresa).
- [ ] Job na fila `exports`; tela de resultado (total/criados/atualizados/erros + download); notificação ao concluir; log `importacoes` ([01 §F](01-modelo-de-dominio.md)).
- [ ] LGPD: aviso de PII, auditoria da exportação, mascaramento opcional, disco privado + expiração; `cid`/saúde fora do export padrão.
- [ ] Permissões especiais `rh.funcionarios.{importar,exportar}` ([01 §10](01-modelo-de-dominio.md)); Policy; ACL na criação/edição resultante.
- [ ] Testes Pest: insert vs update por matrícula/CPF; referência inválida = erro de linha (sem FK órfã); transação por funcionário (falha de filha reverte só a pessoa); round-trip (export→reimport atualiza); `cp_*` validado; tenant scope.
- [ ] Pós-tarefa: `pint`, `prettier`, `phpstan`, `php artisan test`.
