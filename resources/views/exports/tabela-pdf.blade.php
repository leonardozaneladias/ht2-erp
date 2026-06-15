@php
    /** @var \App\DTOs\Admin\Export\ExportavelDTO $dados */
    $geradoEm = now()->format('d/m/Y H:i');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <style>
        * {
            font-family:
                DejaVu Sans,
                sans-serif;
        }
        body {
            margin: 0;
            color: #1f2937;
            font-size: 11px;
        }
        .cabecalho {
            border-bottom: 2px solid #111827;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .titulo {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .meta {
            color: #6b7280;
            font-size: 10px;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background: #f3f4f6;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #d1d5db;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .vazio {
            padding: 16px;
            text-align: center;
            color: #9ca3af;
        }
        .rodape {
            margin-top: 12px;
            color: #9ca3af;
            font-size: 9px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="cabecalho">
        @if ($dados->logoUrl)
            <img
                src="{{ $dados->logoUrl }}"
                alt="Logo"
                style="max-height: 40px; max-width: 160px; margin-bottom: 6px"
            />
        @endif
        <h1 class="titulo">{{ $dados->titulo }}</h1>
        <p class="meta">Gerado em {{ $geradoEm }} · {{ count($dados->linhas) }} registro(s)</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($dados->colunas as $coluna)
                    <th>{{ $coluna }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($dados->linhas as $linha)
                <tr>
                    @foreach ($linha as $celula)
                        <td>{{ $celula }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="vazio" colspan="{{ max(count($dados->colunas), 1) }}">Nenhum registro.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="rodape">{{ config('app.name') }}</p>
</body>
</html>
