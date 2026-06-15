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

| Arquivo                | Fonte                                 | Contagem-âncora |
| ---------------------- | ------------------------------------- | --------------- |
| `paises.csv`           | IBGE (ISO 3166-1, ONU)                | 193             |
| `estados.csv`          | IBGE (fixo, 27 UF)                    | 27              |
| `municipios.csv`       | IBGE (API de localidades)             | 5.571           |
| `moedas.csv`           | ISO 4217 (curado)                     | 35              |
| `bancos.csv`           | BrasilAPI/BACEN (SPB)                 | 478             |
| `cargos.csv`           | MTE-CBO (starter, editável)           | 22              |
| `tipos_logradouro.csv` | lista pública (curado)                | 28              |
| `cnaes.csv`            | IBGE/Concla (subclasses)              | 1.332           |
| `cfops.csv`            | CONFAZ (comuns, curado)               | 30              |
| `ncms.csv`             | Siscomex via BrasilAPI (8 díg ativos) | 10.435          |

Todos UTF-8. `cargos`, `cfops` e `tipos_logradouro` são conjuntos curados/starter
(a expandir); os demais são listas autoritativas completas. Para adicionar/atualizar
um conjunto: novo CSV aqui + seeder + entrada em `DadosReferenciaSeeder::CONJUNTOS`

- contagem-âncora no teste. Para regerar a partir das fontes, refetch (IBGE/BrasilAPI)
  e normalize para UTF-8.
