<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <style>
        body {
            font-family:
                DejaVu Sans,
                sans-serif;
            font-size: 11px;
            color: #222;
        }
        h1 {
            font-size: 16px;
        }
        h2 {
            font-size: 13px;
            margin-top: 18px;
            border-bottom: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        td,
        th {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <h1>Dados pessoais — LGPD</h1>

    <h2>Perfil</h2>
    <table>
        @foreach ($dados['perfil'] as $campo => $valor)
            <tr>
                <th>{{ $campo }}</th>
                <td>{{ is_bool($valor) ? ($valor ? 'sim' : 'não') : ($valor ?? '—') }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Acessos</h2>
    <table>
        <tr>
            <th>Papéis globais</th>
            <td>{{ implode(', ', $dados['acessos']['papeis_globais']) ?: '—' }}</td>
        </tr>
        <tr>
            <th>Empresas</th>
            <td>{{ implode(', ', $dados['acessos']['empresas']) ?: '—' }}</td>
        </tr>
        <tr>
            <th>Filiais</th>
            <td>{{ implode(', ', $dados['acessos']['filiais']) ?: '—' }}</td>
        </tr>
    </table>

    <h2>Atividades (até 1000 mais recentes)</h2>
    <table>
        <tr>
            <th>Data</th>
            <th>Log</th>
            <th>Evento</th>
            <th>Descrição</th>
        </tr>
        @foreach ($dados['atividades'] as $a)
            <tr>
                <td>{{ $a['data'] }}</td>
                <td>{{ $a['log'] }}</td>
                <td>{{ $a['evento'] }}</td>
                <td>{{ $a['descricao'] }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
