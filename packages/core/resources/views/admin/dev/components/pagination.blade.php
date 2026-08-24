@extends ('admin.dev.components.shell', [
    'title' => 'Preview • Pagination',
    'description' => 'Tema oficial de paginação do Laravel registrado via AppServiceProvider, com suporte a paginação completa e simplePaginate.',
])

@section ('preview')
    @php
        $fullPageName = 'demo_page';
        $fullCurrentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage($fullPageName);
        $fullItems = collect(range(1, 96))
            ->slice(($fullCurrentPage - 1) * 8, 8)
            ->values()
            ->map(fn (int $item) => "Contrato {$item}");

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $fullItems,
            96,
            8,
            $fullCurrentPage,
            ['path' => request()->url(), 'pageName' => $fullPageName]
        );

        $simplePageName = 'simple_page';
        $simpleCurrentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage($simplePageName);
        $simpleItems = collect(range(1, 42))
            ->slice(($simpleCurrentPage - 1) * 6, 6)
            ->values()
            ->map(fn (int $item) => "Relatório {$item}");

        $simplePaginator = new \Illuminate\Pagination\Paginator(
            $simpleItems,
            6,
            $simpleCurrentPage,
            ['path' => request()->url(), 'pageName' => $simplePageName]
        );
    @endphp
    <div class="grid gap-6 xl:grid-cols-2">
        <x-shared.card
            title="Paginação padrão"
            subtitle="LengthAwarePaginator usando a view vendor.pagination.inspinia registrada globalmente"
        >
            <div class="space-y-4">
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($paginator as $item)
                        <div class="border-default-300 bg-light/40 text-body-color rounded-xl border px-4 py-3 text-sm">
                            {{ $item }}
                        </div>
                    @endforeach
                </div>

                <div class="pt-2">{{ $paginator->links() }}</div>
            </div>
        </x-shared.card>

        <x-shared.card title="Paginação simples" subtitle="Simple paginator para fluxos com apenas anterior e próxima">
            <div class="space-y-4">
                <div class="space-y-2">
                    @foreach ($simplePaginator as $item)
                        <div class="border-default-300 bg-light/40 text-body-color rounded-xl border px-4 py-3 text-sm">
                            {{ $item }}
                        </div>
                    @endforeach
                </div>

                <div class="pt-2">{{ $simplePaginator->links() }}</div>
            </div>
        </x-shared.card>
    </div>
@endsection
