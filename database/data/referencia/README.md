# Dados de Referência — CSVs versionados

Catálogos **globais** (sem `empresa_id`) consumidos pelos seeders em
`database/seeders/Referencia/` via a base `CsvReferenceSeeder` (stream + upsert
idempotente). Popular/atualizar:

```bash
php artisan referencia:sync            # todos os conjuntos
php artisan referencia:sync estados    # só um conjunto
php artisan referencia:sync --dry-run  # sem gravar (rollback)
```

`referencia:sync` é o passo de **deploy** (após `migrate --force`); produção não
roda `db:seed`. Em dev, `migrate:fresh --seed` já popula (o `DatabaseSeeder`
chama o agregador antes dos seeders de demo).

## Convenções dos arquivos

- **Encoding:** UTF-8 (normalizar no commit; nunca ISO-8859-1).
- **Separador:** `,` por padrão (sobrescreva `$separador` no seeder se a fonte usar `;`).
- **Cabeçalho:** 1ª linha (pulada pelo seeder).
- **Chave natural** = código (idempotência via `upsert ON CONFLICT`).

## Conjuntos

| Arquivo          | Fonte                     | Encoding | Contagem-âncora |
| ---------------- | ------------------------- | -------- | --------------- |
| `estados.csv`    | IBGE (fixo, 27 UF)        | UTF-8    | 27              |
| `paises.csv`     | ISO 3166-1                | UTF-8    | ~249            |
| `municipios.csv` | IBGE (API de localidades) | UTF-8    | ~5.570          |

> Demais conjuntos (moedas, bancos, cargos/CBO, tipos de logradouro, CNAE, CFOP,
> NCM) entram conforme as fases B2–B4. Cada adição: novo CSV aqui + seeder +
> entrada no mapa `DadosReferenciaSeeder::CONJUNTOS` + contagem-âncora no teste.
