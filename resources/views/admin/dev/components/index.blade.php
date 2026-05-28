@extends ('admin.dev.components.shell', [
    'title' => 'Índice de Previews',
    'description' => 'Ponto único para validar visualmente os componentes concluídos da Fase 6 antes de começar a consumi-los em telas reais do admin.',
])

@section ('preview')
    <div class="grid gap-6 lg:grid-cols-2">
        <x-shared.card title="Componentes concluídos" subtitle="Fase 6 • Batches 1, 2, 3, 4, 5, 6, 7 e 8">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'layout' => ['Shell master do admin com topbar, sidebar, content-page e footer', 'tabler--app-window'],
                    'sidebar' => ['Navegação lateral fixa com seções hardcoded e gates de permissão', 'tabler--layout-sidebar-left'],
                    'topbar' => ['Header fixo com busca, tema, notificações e menu de usuário', 'tabler--layout-navbar'],
                    'page-header' => ['Título, breadcrumb e ações forwardadas pelo layout', 'tabler--heading'],
                    'footer' => ['Rodapé simples com versão e ambiente', 'tabler--baseline-density-medium'],
                    'pagination' => ['Tema oficial de paginação do Laravel para controllers e Livewire', 'tabler--chevrons-left-right'],
                    'spinner' => ['Loading inline para wire:loading, placeholders e estados leves', 'tabler--loader-2'],
                    'alert' => ['Alertas persistentes e dismissible', 'tabler--alert-triangle'],
                    'badge' => ['Badges de status, contagem e chip', 'tabler--tag'],
                    'button' => ['Ações primárias, outline, ghost e icon-only', 'tabler--pointer'],
                    'card' => ['Container base com header, ações e footer', 'tabler--layout-cards'],
                    'breadcrumb' => ['Breadcrumb standalone para drawer, modal e cards', 'tabler--route'],
                    'dropdown' => ['Menu de ações por linha e user menu', 'tabler--dots'],
                    'drawer' => ['Painel lateral admin-only com footer fixo', 'tabler--layout-sidebar-right'],
                    'collapse' => ['Seções expansíveis com trigger interno ou externo', 'tabler--chevrons-up-down'],
                    'toast' => ['Feedback efêmero com container fixo e helper JS leve', 'tabler--bell-ringing'],
                    'empty-state' => ['Estado vazio com CTA e tamanhos reutilizáveis', 'tabler--inbox-off'],
                    'loading-button' => ['Wrapper Livewire-native sobre a base de button', 'tabler--loader-2'],
                    'status-badge' => ['Wrapper enum-driven sobre badge para estados do sistema', 'tabler--rosette-discount-check'],
                    'tabs' => ['Família de abas com tab-nav, tab-trigger e tab-panel', 'tabler--layout-navbar-collapse'],
                    'confirm-dialog' => ['Helper SweetAlert2 para confirmações destrutivas e irreversíveis', 'tabler--shield-question'],
                    'input' => ['Campo base com validação, hint e ícone opcional', 'tabler--text-caption'],
                    'textarea' => ['Entrada multi-linha para descrições e observações', 'tabler--align-box-left-stretch'],
                    'select' => ['Select nativo para enums simples e listas curtas', 'tabler--list-details'],
                    'checkbox' => ['Seleção múltipla e flags independentes', 'tabler--checkbox'],
                    'radio' => ['Escolha exclusiva com apoio de radio-group', 'tabler--circle-dot'],
                    'toggle' => ['Switch booleano para regras e preferências', 'tabler--toggle-left'],
                    'password-input' => ['Senha com visibilidade e meter opcional', 'tabler--password'],
                    'date-picker' => ['Wrapper Flatpickr com locale pt-BR e helper JS leve', 'tabler--calendar-event'],
                    'select-search' => ['Choices.js para relacionamentos, múltiplos e agrupamentos', 'tabler--search'],
                    'tags-input' => ['Wrapper semântico para chips/tags sobre Choices.js', 'tabler--tags'],
                    'date-range-picker' => ['Intervalos de datas como componente irmão do date-picker', 'tabler--calendar-range'],
                    'cpf-input' => ['CPF com máscara BR sobre a base de input', 'tabler--id-badge-2'],
                    'cnpj-input' => ['CNPJ com máscara BR para instituições e configs', 'tabler--building-bank'],
                    'phone-input' => ['Telefone BR adaptativo para fixo e celular', 'tabler--phone'],
                    'money-input' => ['Valor monetário com Inputmask e persistência em centavos', 'tabler--currency-real'],
                    'cep-input' => ['CEP com ViaCEP e evento oficial de preenchimento', 'tabler--map-pin-search'],
                    'file-upload' => ['Upload único com modos livewire e dropzone', 'tabler--file-upload'],
                    'data-table' => ['Listagens administrativas com busca, export e seleção em massa', 'tabler--table'],
                    'list-group' => ['Composição vertical para dados estruturados e navegação lateral', 'tabler--list'],
                    'static-table' => ['Tabela HTML leve para listas curtas, cards e subtelas internas', 'tabler--table-options'],
                    'timeline-table' => ['Grid visual para programações com vigência, valor e ações rápidas', 'tabler--timeline-event'],
                    'kpi-card' => ['Métrica compacta para dashboard, relatórios e totais rápidos', 'tabler--chart-infographic'],
                    'chart-card' => ['Wrapper visual para charts com header, filtros e área reservada', 'tabler--chart-histogram'],
                    'chart-bar' => ['Wrapper ApexCharts horizontal para comparação entre contratos e categorias', 'tabler--chart-bar'],
                    'chart-line' => ['Wrapper ApexCharts dual para receita x inadimplência e séries temporais', 'tabler--chart-line'],
                    'chart-column' => ['Wrapper ApexCharts vertical para adesões por mês e relatórios', 'tabler--chart-histogram'],
                    'chart-pie' => ['Wrapper ApexCharts pie/donut para distribuição por modalidade e status', 'tabler--chart-donut-3'],
                    'progress-bar' => ['Barra de progresso reutilizável para metas, wizard e indicadores', 'tabler--progress-check'],
                    'accordion' => ['Seções coordenadas para formulários longos e FAQ do admin', 'tabler--layout-navbar-collapse'],
                    'modal' => ['Dialog centralizado para formulários rápidos e previews contextuais', 'tabler--window'],
                    'tooltip' => ['Ajuda textual simples sobre ícones, toggles e badges', 'tabler--message-circle-question'],
                    'sortable-list' => ['Drag-and-drop com SortableJS para termos e ordenação manual', 'tabler--drag-drop-2'],
                    'copy-button' => ['Botão nativo de copiar CPF, códigos e links com feedback visual', 'tabler--copy'],
                    'password-strength-meter' => ['Medidor visual de força com integração ao password-input', 'tabler--password-fingerprint'],
                ] as $route => $meta)
                    <a
                        href="{{ route('admin.dev.components.'.$route) }}"
                        class="border-default-300 bg-light/40 hover:border-primary/40 hover:bg-primary/5 flex items-start gap-3 rounded-lg border p-4 transition"
                    >
                        <span
                            class="bg-primary/15 text-primary inline-flex size-10 items-center justify-center rounded-full"
                        >
                            <i class="iconify {{ $meta[1] }} text-lg"></i>
                        </span>

                        <div class="min-w-0">
                            <p class="text-body-color font-semibold">{{ \Illuminate\Support\Str::headline($route) }}</p>
                            <p class="text-default-400 mt-1 text-sm">{{ $meta[0] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-shared.card>

        <x-shared.card title="Notas do lote" subtitle="Consolidação aplicada nas rodadas atuais">
            <div class="text-default-400 space-y-4 text-sm">
                <div class="border-default-300 bg-light/50 rounded-lg border p-4">
                    <p class="text-body-color font-semibold">API consolidada</p>
                    <p class="mt-1">Os componentes do shell admin e da UI base foram implementados com composição explícita, adapters de compatibilidade e mapas de classe previsíveis para evitar inconsistências entre docs e código.</p>
                </div>

                <div class="border-default-300 bg-light/50 rounded-lg border p-4">
                    <p class="text-body-color font-semibold">Compatibilidade prática</p>
                    <p class="mt-1">O shell admin agora existe em duas camadas: <code>&lt;x-admin.layout&gt;</code> como fonte da verdade e <code>admin/layouts/app.blade.php</code> como adapter para compatibilidade com <code>&#64;extends</code> e <code>#[Layout(...)]</code>.</p>
                </div>

                <div class="border-default-300 bg-light/50 rounded-lg border p-4">
                    <p class="text-body-color font-semibold">Dependências JS</p>
                    <p class="mt-1">O admin agora usa entrypoints próprios em <code>resources/css/admin.css</code> e <code>resources/js/admin.js</code>. O <code>mega-menu</code> foi removido do escopo, o sino de notificações permanece composição interna do topbar e os batches recentes foram fechados com helpers leves para <code>toast</code>, <code>confirm-dialog</code>, a família de forms base, os wrappers avançados de <code>Choices.js</code>, <code>Inputmask</code>, <code>Flatpickr</code>, <code>ViaCEP</code> e <code>Dropzone</code>, o bridge de <code>DataTables</code>, os wrappers de dashboard, a família especializada com <code>accordion</code>, <code>modal</code>, <code>tooltip</code> e <code>sortable-list</code>, os remanescentes base com paginação temática do Laravel, <code>spinner</code>, <code>static-table</code> e <code>timeline-table</code> e, agora, a rodada final com bridge de <code>ApexCharts</code>, wrappers <code>chart-bar/line/column/pie</code>, <code>copy-button</code> nativo e <code>password-strength-meter</code>.</p>
                </div>
            </div>
        </x-shared.card>
    </div>
@endsection
