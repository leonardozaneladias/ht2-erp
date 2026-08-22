<x-shared.card title="Histórico de logins" subtitle="Seus 10 acessos mais recentes.">
    @if ($registros->isEmpty())
        <x-shared.empty-state
            size="sm"
            icon="tabler--login-2"
            title="Sem registros"
            description="Seus próximos acessos aparecerão aqui."
        />
    @else
        <x-shared.static-table :headers="['Data', 'IP', 'Navegador']">
            @foreach ($registros as $registro)
                <tr>
                    <td>
                        {{ $registro->created_at?->timezone($usuario->timezone ?? config('app.timezone'))->translatedFormat('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td>{{ $registro->ip_address ?? '—' }}</td>
                    <td class="text-default-500">
                        {{ \Illuminate\Support\Str::limit($registro->user_agent ?? '—', 60) }}
                    </td>
                </tr>
            @endforeach
        </x-shared.static-table>
    @endif
</x-shared.card>
