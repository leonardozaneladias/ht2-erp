@php
    /** @var \HT2ML\Core\Models\Empresa $registro */
@endphp

{{-- Ficha de visualização ("Ver") — corpo do x-admin.ficha-drawer. --}}
<div class="space-y-6">
    <x-admin.ficha-section title="Identificação">
        <x-shared.field-display label="Nome">{{ $registro->nome }}</x-shared.field-display>
        <x-shared.field-display label="Razão social">{{ $registro->razao_social ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="CNPJ">{{ $registro->cnpj ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Inscrição estadual">
            {{ $registro->inscricao_estadual ?: '—' }}</x-shared.field-display
        >
        <x-shared.field-display label="Situação">
            <x-shared.badge :variant="$registro->ativo ? 'success' : 'default'" pill size="sm">
                {{ $registro->ativo ? 'Ativo' : 'Inativo' }}
            </x-shared.badge>
        </x-shared.field-display>
    </x-admin.ficha-section>
    <x-admin.ficha-section title="Contato e endereço">
        <x-shared.field-display label="E-mail">{{ $registro->email ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Telefone">{{ $registro->telefone ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Site">{{ $registro->site_url ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Endereço">
            {{ trim(($registro->endereco ?? '') . ($registro->numero ? ', ' . $registro->numero : '')) ?: '—' }}</x-shared.field-display
        >
        <x-shared.field-display label="Bairro">{{ $registro->bairro ?: '—' }}</x-shared.field-display>
        <x-shared.field-display label="Cidade/UF">
            {{ $registro->cidade ? $registro->cidade . ($registro->estado ? '/' . $registro->estado : '') : '—' }}</x-shared.field-display
        >
        <x-shared.field-display label="CEP">{{ $registro->cep ?: '—' }}</x-shared.field-display>
    </x-admin.ficha-section>
    <x-admin.ficha-section title="Identidade visual e filiais">
        <x-shared.field-display label="Cor primária">
            <span class="inline-flex items-center gap-2">
                <span
                    class="border-default-300 size-4 rounded border"
                    style="background: {{ $registro->cor_primaria }}"
                ></span>
                {{ $registro->cor_primaria ?: '—' }}
            </span>
        </x-shared.field-display>
        <x-shared.field-display label="Filiais">
            <span class="flex flex-wrap gap-1.5">
                @forelse ($registro->filiais as $filial)
                    <x-shared.badge variant="default" size="sm" pill>{{ $filial->nome }}</x-shared.badge>
                @empty
                    —
                @endforelse
            </span>
        </x-shared.field-display>
    </x-admin.ficha-section>
</div>
