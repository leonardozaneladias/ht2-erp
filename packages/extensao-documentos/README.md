# ht2ml/extensao-documentos

Extensão-biblioteca **Documentos**: numeração sequencial de documentos por empresa,
tipo e ano, sem buracos nem duplicatas.

Não tem tela, rota, menu nem permissão. É código consumido por quem numera
documentos (um módulo ou o próprio produto). Saiu do core porque o mecanismo é
genérico, mas o vocabulário (matrícula, contrato, protocolo, recibo) é de domínio
([ADR-0019](../../docs/architecture/adrs/ADR-0019-plataforma-abstrata-sem-produto.md)).

## Instalar

O pacote é privado e vem por VCS do repositório
`leonardozaneladias/ht2ml-extensao-documentos`. Depende de `ht2ml/core` (a tabela
referencia `empresas`).

```bash
composer require ht2ml/extensao-documentos
php artisan migrate --force   # cria document_sequences
```

## O que entrega

| Símbolo | Papel |
| --- | --- |
| `HT2ML\Documentos\Support\GeradorNumeroDocumento` | `gerar(int $empresaId, TipoDocumento $tipo, int $ano, ?string $template = null): string`. Registrado como singleton |
| `HT2ML\Documentos\Enums\TipoDocumento` | `Matricula`, `Contrato`, `Protocolo`, `Recibo`, cada um com `label()` e `template()` padrão |
| `HT2ML\Documentos\Models\DocumentSequence` | O contador: uma linha por empresa + tipo + ano (`document_sequences`, índice único nos três) |

```php
use HT2ML\Documentos\Enums\TipoDocumento;
use HT2ML\Documentos\Support\GeradorNumeroDocumento;

$numero = app(GeradorNumeroDocumento::class)->gerar($empresaId, TipoDocumento::Matricula, 2026);
// "MAT-2026-000001"

app(GeradorNumeroDocumento::class)->gerar($empresaId, TipoDocumento::Recibo, 2026, 'R{SEQ:4}/{ANO}');
// "R0001/2026"
```

Placeholders do template: `{ANO}` (ano com 4 dígitos) e `{SEQ:N}` (sequencial com N
dígitos, completado com zeros).

## Por que é assim

- **Transação + `lockForUpdate`.** Duas requisições simultâneas não recebem o mesmo
  número, e um número não é pulado.
- **`DocumentSequence` não usa `BelongsToEmpresa`.** O global scope por empresa ativa
  quebraria o `lockForUpdate` em transações concorrentes; por isso `gerar()` recebe
  `$empresaId` explícito.
- **Sem auditoria.** É contador técnico, não entidade de negócio.
- **Os tipos são um enum fechado.** Um tipo novo exige versão nova do pacote.

## Desenvolvimento

No monorepo da plataforma o pacote vive em `packages/extensao-documentos`. Release:
`make release-modulo pacote=extensao-documentos versao=vX.Y.Z` (ver
[`docs/devops/release.md`](../../docs/devops/release.md)).
