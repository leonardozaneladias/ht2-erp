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
                        <th>{{ $header }}</th>
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
                    <td
                        colspan="{{ is_array($headers) ? count($headers) : 1 }}"
                        class="text-default-400 px-4 py-10 text-center text-sm"
                    >
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
