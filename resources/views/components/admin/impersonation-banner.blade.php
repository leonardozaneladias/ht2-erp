@php
    $impersonation = app(\App\Support\Impersonation\ImpersonationContext::class);
@endphp

@if ($impersonation->ativo())
    @php
        $alvo = auth('admin')->user();
        $timeout = app(\App\Settings\SegurancaSettings::class)->impersonation_timeout_minutos;
        $expiraEm = ($impersonation->iniciadoEm() ?? time()) + $timeout * 60;
    @endphp
    <div
        class="bg-warning/15 text-warning-700 border-warning/30 flex flex-wrap items-center justify-between gap-3 border-b px-4 py-2 text-sm"
        x-data="{
            expiraEm: {{ $expiraEm }},
            restante: '',
            tick() {
                const s = Math.max(0, this.expiraEm - Math.floor(Date.now() / 1000));
                const m = Math.floor(s / 60).toString().padStart(2, '0');
                const r = (s % 60).toString().padStart(2, '0');
                this.restante = `${m}:${r}`;
            },
        }"
        x-init="
            tick();
            setInterval(() => tick(), 1000);
        "
    >
        <div class="flex items-center gap-2">
            <span class="iconify tabler--user-shield" aria-hidden="true"></span>
            <span>
                Você está personificando <strong>{{ $alvo?->nome }}</strong>
                @if ($impersonation->motivo())
                    · {{ $impersonation->motivo() }}
                @endif
                · expira em <span x-text="restante" class="font-mono"></span>
            </span>
        </div>

        <form method="POST" action="{{ route('admin.impersonation.sair') }}">
            @csrf
            <button
                type="submit"
                class="btn btn-sm bg-warning/25 hover:bg-warning/35 text-warning-800 inline-flex items-center gap-x-1.5"
            >
                <span class="iconify tabler--logout" aria-hidden="true"></span>
                Sair da personificação
            </button>
        </form>
    </div>
@endif
