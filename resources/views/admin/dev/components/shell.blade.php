<x-admin.layout :title="$title ?? 'Preview de Componentes'" :with-livewire="false">
    <x-slot:scripts>
        @yield ('previewScripts')
    </x-slot:scripts>

    @php
        $navItems = [
            'index' => 'Visão Geral',
            'layout' => 'Layout',
            'sidebar' => 'Sidebar',
            'topbar' => 'Topbar',
            'page-header' => 'Page Header',
            'footer' => 'Footer',
            'pagination' => 'Pagination',
            'spinner' => 'Spinner',
            'alert' => 'Alert',
            'badge' => 'Badge',
            'button' => 'Button',
            'card' => 'Card',
            'breadcrumb' => 'Breadcrumb',
            'dropdown' => 'Dropdown',
            'drawer' => 'Drawer',
            'collapse' => 'Collapse',
            'toast' => 'Toast',
            'empty-state' => 'Empty State',
            'loading-button' => 'Loading Button',
            'status-badge' => 'Status Badge',
            'tabs' => 'Tabs',
            'confirm-dialog' => 'Confirm Dialog',
            'input' => 'Input',
            'textarea' => 'Textarea',
            'select' => 'Select',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio',
            'toggle' => 'Toggle',
            'password-input' => 'Password Input',
            'date-picker' => 'Date Picker',
            'select-search' => 'Select Search',
            'tags-input' => 'Tags Input',
            'date-range-picker' => 'Date Range Picker',
            'cpf-input' => 'CPF Input',
            'cnpj-input' => 'CNPJ Input',
            'phone-input' => 'Phone Input',
            'money-input' => 'Money Input',
            'cep-input' => 'CEP Input',
            'file-upload' => 'File Upload',
            'data-table' => 'Data Table',
            'list-group' => 'List Group',
            'static-table' => 'Static Table',
            'timeline-table' => 'Timeline Table',
            'kpi-card' => 'KPI Card',
            'chart-card' => 'Chart Card',
            'chart-bar' => 'Chart Bar',
            'chart-line' => 'Chart Line',
            'chart-column' => 'Chart Column',
            'chart-pie' => 'Chart Pie',
            'progress-bar' => 'Progress Bar',
            'accordion' => 'Accordion',
            'modal' => 'Modal',
            'tooltip' => 'Tooltip',
            'sortable-list' => 'Sortable List',
            'copy-button' => 'Copy Button',
            'password-strength-meter' => 'Password Strength Meter',
        ];
    @endphp
    <div class="bg-default-100 min-h-screen py-8">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="border-default-300 bg-card mb-6 rounded-xl border p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <p class="text-2xs text-primary font-semibold tracking-[0.28em] uppercase">Sistema Admin</p>
                        <h1 class="text-body-color text-2xl font-semibold">{{ $title ?? 'Preview de Componentes' }}</h1>
                        <p class="text-default-400 max-w-3xl text-sm">
                            {{ $description ?? 'Previews visuais dos componentes consolidados a partir do catálogo Inspinia.' }}
                        </p>
                    </div>

                    @unless (request()->routeIs('admin.dev.components.index'))
                        <a
                            class="btn bg-light text-dark hover:text-primary"
                            href="{{ route('admin.dev.components.index') }}"
                        >
                            <i class="iconify tabler--arrow-left text-base"></i>
                            Voltar ao índice
                        </a>
                    @endunless
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($navItems as $route => $label)
                        @php
                            $active = request()->routeIs('admin.dev.components.'.$route);
                        @endphp
                        <a
                            href="{{ route('admin.dev.components.'.$route) }}"
                            class="btn btn-sm {{ $active ? 'bg-primary text-white hover:bg-primary-hover' : 'bg-light text-dark hover:text-primary' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            @yield ('preview')
        </div>
    </div>
</x-admin.layout>
