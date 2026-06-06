<x-shared.card title="Histórico de logins" subtitle="Seus 10 acessos mais recentes.">
    @if ($registros->isEmpty())
        <x-shared.empty-state
            size="sm"
            icon="tabler--login-2"
            title="Sem registros"
            description="Seus próximos acessos aparecerão aqui."
        />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-default-500 border-default-200 border-b text-left">
                        <th class="py-2 pe-4 font-medium">Data</th>
                        <th class="py-2 pe-4 font-medium">IP</th>
                        <th class="py-2 font-medium">Navegador</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $registro)
                        <tr class="border-default-100 border-b">
                            <td class="text-default-700 py-2 pe-4">
                                {{ $registro->created_at?->timezone($usuario->timezone ?? config('app.timezone'))->translatedFormat('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-default-700 py-2 pe-4">{{ $registro->ip_address ?? '—' }}</td>
                            <td class="text-default-500 py-2">
                                {{ \Illuminate\Support\Str::limit($registro->user_agent ?? '—', 60) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-shared.card>
