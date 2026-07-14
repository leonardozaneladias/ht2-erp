# field-display — exibição read-only de um campo

Rótulo em caps pequenas + valor: o tijolo de composição das telas de consulta
(ficha "Ver" em drawer, telas de detalhe). Vive no core, junto com a infra do
"Ver" — ver `docs/visualizacao.md`. Um módulo-pacote que precise de um
equivalente próprio deve delegar aqui, para não divergir do padrão.

## API

```blade
<x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
```

| Prop    | Tipo   | Default | Descrição        |
| ------- | ------ | ------- | ---------------- |
| `label` | string | —       | Rótulo do campo. |

O valor vai no slot — texto, badge (`x-shared.badge`), link, o que a ficha
precisar. Valor vazio: renderize `—` no call site (`{{ $x ?: '—' }}`).

## Padrão de composição (ficha)

```blade
<section>
    <h4 class="text-body-color mb-3 text-sm font-semibold">Dados gerais</h4>
    <div class="grid grid-cols-2 gap-x-4 gap-y-4 md:grid-cols-3">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Status">
            <x-shared.badge :variant="$registro->status->variant()" pill size="sm">
                {{ $registro->status->label() }}</x-shared.badge
            >
        </x-shared.field-display>
    </div>
</section>
```

Formatação por tipo (convenção das fichas): dinheiro `Money::fromCentavos(...)->formatado()`,
enum via badge, boolean `Sim`/`Não`, datas `d/m/Y`, vazio `—`.

Preview: `/admin/dev/components/field-display`.
