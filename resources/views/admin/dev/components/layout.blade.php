<x-admin.layout
    title="Preview • x-admin.layout"
    subtitle="Layout e navegação"
    :breadcrumbs="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Dev Components', 'url' => route('admin.dev.components.index')],
        ['label' => 'Layout', 'current' => true],
    ]"
>
    <x-slot:actions>
        <a class="btn bg-light text-dark hover:text-primary" href="{{ route('admin.dev.components.index') }}">
            <i class="iconify tabler--arrow-left text-base"></i>
            Voltar ao índice
        </a>
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-shared.card title="Shell master" subtitle="Fonte da verdade do admin">
            <div class="text-default-400 space-y-3 text-sm">
                <p>O componente reúne topbar, sidebar, `content-page` e footer em um contrato único. `admin/layouts/app.blade.php` existe apenas como adapter de compatibilidade.</p>
                <p>O page header é renderizado automaticamente quando `title` está presente e aceita forwarding de `actions`, `subtitle` e `breadcrumbs`.</p>
            </div>
        </x-shared.card>

        <x-shared.card title="O que validar" subtitle="Checklist visual e funcional">
            <ul class="text-default-400 space-y-2 text-sm">
                <li>Topbar fixa com busca, toggle de tema, notificações e menu do usuário.</li>
                <li>Sidebar responsiva com `offcanvas` automático abaixo de 1140px.</li>
                <li>Content-page fluida com scroll próprio e footer ao final.</li>
                <li>Bootstrap de tema aplicado antes do Vite para evitar FOUC.</li>
            </ul>
        </x-shared.card>

        <x-shared.card title="Exemplo de domínio" subtitle="Conteúdo encaixado no shell real">
            <div class="border-default-300 bg-light/40 rounded-lg border p-4">
                <p class="text-body-color font-semibold">Dashboard administrativo</p>
                <p class="text-default-400 mt-2 text-sm">Use este mesmo shell para KPIs, relatórios, cadastros e fluxos operacionais do backoffice.</p>
            </div>
        </x-shared.card>
    </div>
</x-admin.layout>
