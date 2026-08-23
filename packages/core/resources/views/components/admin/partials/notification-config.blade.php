@php
    // Preferências de notificação/confirmação da instância (NotificacaoSettings),
    // consumidas pelo motor resources/js/admin/toast.js e por confirm.js.
    // O service é tolerante a falhas (cai nos defaults sem banco).
    $notificacaoConfig = app(\HT2ML\Core\Services\Admin\Settings\NotificacaoService::class)->paraJsConfig();
@endphp

<script>
    window.__notificacaoConfig = @json ($notificacaoConfig);
</script>
