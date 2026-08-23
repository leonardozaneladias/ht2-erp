<?php

declare(strict_types=1);

namespace App\Settings;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Spatie\LaravelSettings\Settings;

/**
 * Preferências do sistema de notificações (toasts) e confirmações.
 *
 * Definem aparência/comportamento do feedback visual da instância. Aplicadas no
 * frontend via NotificacaoService::paraJsConfig() (injetado pelo partial
 * x-admin.partials.notification-config) e lidas pelo motor resources/js/admin/toast.js.
 */
final class NotificacaoSettings extends Settings
{
    /** Posição dos toasts na tela (ver App\Enums\Admin\Notificacao\ToastPosicao). */
    public string $toast_posicao;

    /** Tempo até sumir: curta|media|longa (ver ToastDuracao). */
    public string $toast_duracao;

    /** Estilo visual: pilula|card (ver ToastEstilo). */
    public string $toast_estilo;

    /** Máximo de toasts visíveis simultaneamente (1–5). */
    public int $toast_maximo;

    /** Posição do diálogo de confirmação: center|top (ver ConfirmacaoPosicao). */
    public string $confirmacao_posicao;

    public static function group(): string
    {
        return SettingsGroup::NOTIFICACOES->value;
    }
}
