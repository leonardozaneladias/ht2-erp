{{--
    Sistema de notificações (toasts) — FONTE ÚNICA DE APARÊNCIA.

    Há dois estilos (escolhidos nas Configurações → Notificações): `pilula`
    (compacta) e `card` (com título). Edite os <template> abaixo para mudar a
    aparência; o COMPORTAMENTO (posição, duração, máximo, estilo ativo) vem das
    Configurações e é aplicado por resources/js/admin/toast.js, que clona o
    template do estilo ativo e preenche [data-toast-icon]/[data-toast-title]/
    [data-toast-message]. A posição (top/bottom/left/right/center) é aplicada via
    inline-style pelo JS; as classes abaixo são o estado inicial (topo central).

    ACESSIBILIDADE: os <template> declaram role="status"/aria-live="polite"
    (padrão). Para erros e avisos (variantes danger/warning), o toast.js eleva o
    item clonado para role="alert"/aria-live="assertive", interrompendo o leitor
    de tela — mesmo critério do x-shared.alert (WAI-ARIA APG).
--}}
<div
    {{
$attributes->class([
            'pointer-events-none fixed inset-x-0 top-20 z-[1100] flex flex-col items-center gap-2 px-4',
        ])
}}
    data-toast-container
>
    {{-- Estilo "pílula": compacto, uma linha (ícone + mensagem). --}}
    <template data-toast-template="pilula">
        <div
            data-toast-item
            role="status"
            aria-live="polite"
            class="group bg-card/95 border-default-200/80 dark:border-default-700/70 dark:bg-default-900/95 pointer-events-auto flex max-w-md items-center gap-2.5 rounded-full border py-2 ps-3.5 pe-2.5 shadow-lg backdrop-blur transition-all ease-out"
        >
            <i data-toast-icon class="iconify shrink-0 text-lg" aria-hidden="true"></i>

            <div class="min-w-0 py-0.5">
                <p data-toast-title class="text-default-800 dark:text-default-100 hidden text-sm font-semibold"></p>
                <p
                    data-toast-message
                    class="text-default-700 dark:text-default-200 text-sm leading-snug font-medium"
                ></p>
            </div>

            <button
                type="button"
                data-toast-close
                aria-label="Fechar"
                class="text-default-400 hover:bg-default-200/60 hover:text-default-700 dark:hover:text-default-100 ms-1 inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full transition hover:dark:bg-white/10"
            >
                <i class="iconify tabler--x text-base" aria-hidden="true"></i>
            </button>
        </div>
    </template>

    {{-- Estilo "card": maior, com título em negrito + mensagem secundária. --}}
    <template data-toast-template="card">
        <div
            data-toast-item
            role="status"
            aria-live="polite"
            class="group bg-card/95 border-default-200/80 dark:border-default-700/70 dark:bg-default-900/95 pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border p-4 shadow-lg backdrop-blur transition-all ease-out"
        >
            <i data-toast-icon class="iconify mt-0.5 shrink-0 text-xl" aria-hidden="true"></i>

            <div class="min-w-0 flex-1">
                <p data-toast-title class="text-default-800 dark:text-default-100 hidden text-sm font-semibold"></p>
                <p data-toast-message class="text-default-600 dark:text-default-300 text-sm leading-snug"></p>
            </div>

            <button
                type="button"
                data-toast-close
                aria-label="Fechar"
                class="text-default-400 hover:bg-default-200/60 hover:text-default-700 dark:hover:text-default-100 -me-1 -mt-1 inline-flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full transition hover:dark:bg-white/10"
            >
                <i class="iconify tabler--x text-base" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</div>
