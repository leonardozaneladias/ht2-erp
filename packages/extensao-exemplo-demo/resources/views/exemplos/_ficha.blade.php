@php
    /** @var \HT2ML\ExemploDemo\Models\Exemplo $registro */
@endphp

{{-- Ficha de visualização do Exemplo (corpo do x-admin.ficha-drawer). Padrão:
     x-admin.ficha-section com grade de x-shared.field-display; formatação por tipo —
     money ->formatado(), enum via badge, boolean Sim/Não, datas d/m/Y, vazio "—".
     Campos que não cabem na grade (texto longo, tags) vão no slot `footer`. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Dados gerais">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Slug">{{ $registro->slug ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Status">
            <x-shared.badge :variant="$registro->status->variant()" pill size="sm">
                {{ $registro->status->label() }}
            </x-shared.badge>
        </x-shared.field-display>
        <x-shared.field-display label="Categoria">{{ $registro->categoria ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Filial">{{ $registro->filial?->nome ?? '—' }}</x-shared.field-display>
        <x-shared.field-display label="Destaque">{{ $registro->destaque ? 'Sim' : 'Não' }}</x-shared.field-display>

        @if ($registro->descricao)
            <x-slot:footer>
                <x-shared.field-display label="Descrição">{{ $registro->descricao }}</x-shared.field-display>
            </x-slot:footer>
        @endif
    </x-admin.ficha-section>

    <x-admin.ficha-section title="Valores e vigência">
        <x-shared.field-display label="Preço">
            {{ $registro->preco !== null ? \HT2ML\Core\Support\Money\Money::fromCentavos((int) $registro->preco)->formatado() : '—' }}
        </x-shared.field-display>
        <x-shared.field-display label="Custo">
            {{ $registro->custo !== null ? 'R$ ' . number_format((float) $registro->custo, 2, ',', '.') : '—' }}
        </x-shared.field-display>
        <x-shared.field-display label="Quantidade">{{ $registro->quantidade ?? '—' }}</x-shared.field-display>
        <x-shared.field-display label="Início">
            {{ $registro->data_inicio?->format('d/m/Y') ?? '—' }}</x-shared.field-display
        >
        <x-shared.field-display label="Publicado em">
            {{ $registro->publicado_em?->format('d/m/Y H:i') ?? '—' }}
        </x-shared.field-display>
    </x-admin.ficha-section>

    <x-admin.ficha-section title="Contato e documentos">
        <x-shared.field-display label="E-mail">{{ $registro->email ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Telefone">{{ $registro->telefone ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Site">{{ $registro->site ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="CEP">{{ $registro->cep ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="CNPJ">{{ $registro->cnpj ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="CPF">{{ $registro->cpf ?: '—' }}</x-shared.field-display>

        @if (filled($registro->tags))
            <x-slot:footer>
                <x-shared.field-display label="Tags">
                    <span class="flex flex-wrap gap-1.5">
                        @foreach ((array) $registro->tags as $tag)
                            <x-shared.badge variant="default" size="sm" pill>{{ $tag }}</x-shared.badge>
                        @endforeach
                    </span>
                </x-shared.field-display>
            </x-slot:footer>
        @endif
    </x-admin.ficha-section>
</div>
