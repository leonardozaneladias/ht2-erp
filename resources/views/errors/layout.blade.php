{{-- Layout standalone das páginas de erro: sem sessão, banco ou layout autenticado,
     para funcionar mesmo quando a aplicação está degradada (500/503). --}}
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        @yield ('codigo')
        —
        @yield ('titulo')
        | {{ config('app.name') }}
    </title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" />

    <x-admin.partials.theme-bootstrap />

    @vite (['resources/css/admin.css'])
</head>
<body>
    <div class="flex min-h-screen items-center">
        <div class="container">
            <div class="flex justify-center p-12">
                <div class="text-center md:w-1/2">
                    <p class="text-primary text-7xl font-bold tracking-tight md:text-8xl">@yield ('codigo')</p>
                    <h1 class="mt-4 mb-2 text-2xl font-bold uppercase">@yield ('titulo')</h1>
                    <p class="text-default-400 mb-8">@yield ('mensagem')</p>
                    <div class="flex items-center justify-center gap-2">
                        @hasSection ('acoes')
                            @yield ('acoes')
                        @else
                            <a class="btn bg-primary text-white" href="{{ url('/') }}">
                                <i class="iconify tabler--home"></i>
                                Voltar ao painel
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
