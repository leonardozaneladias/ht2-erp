@props ([
    'headers' => null,
    'bordered' => false,
    'striped' => false,
    'hover' => true,
    'compact' => false,
    'emptyMessage' => 'Nenhum registro encontrado.',
])

@php
    $hasRows = \Illuminate\Support\Str::of(strip_tags((string) $slot))->squish()->isNotEmpty();

    // Colspan da linha de vazio: com `:headers` é o count do array; com `x-slot:head`
    // conta os <th> do markup — sem isso a mensagem ficava ancorada na 1ª coluna.
    $emptyColspan = match (true) {
        is_array($headers) => count($headers),
        isset($head) => max(1, substr_count((string) $head, '<th')),
        default => 1,
    };
@endphp

<div {{ $attributes->only('class')->class(['overflow-x-auto rounded-xl']) }}>
    <table
        {{
$attributes->except('class')->class([
            'table w-full align-middle',
            'table-bordered' => $bordered,
            'table-striped' => $striped,
            'table-hover' => $hover,
            'table-sm' => $compact,
        ])
}}
    >
        @if ($headers)
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        {{-- scope="col" liga as células de dados ao nome da coluna (WCAG 1.3.1),
                             igual ao x-admin.table.th-sort. Sem ele, leitores de tela não
                             associam cada célula ao seu cabeçalho. --}}
                        <th scope="col">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @elseif (isset($head))
            <thead>
                {{ $head }}
            </thead>
        @endif

        <tbody>
            @if ($hasRows)
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ $emptyColspan }}" class="text-default-400 px-4 py-10 text-center text-sm">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endif
        </tbody>

        @isset ($foot)
            <tfoot>
                {{ $foot }}
            </tfoot>
        @endisset
    </table>
</div>
