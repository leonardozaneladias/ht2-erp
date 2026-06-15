<?php

declare(strict_types=1);

namespace App\Services\Admin\Settings;

use App\Enums\Admin\Notificacao\ConfirmacaoPosicao;
use App\Enums\Admin\Notificacao\ToastDuracao;
use App\Enums\Admin\Notificacao\ToastEstilo;
use App\Enums\Admin\Notificacao\ToastPosicao;
use App\Settings\NotificacaoSettings;
use Throwable;

/**
 * Resolve as preferências de notificação/confirmação para o frontend.
 *
 * Cada valor é validado contra o enum correspondente, com fallback ao padrão
 * (defesa contra valores inválidos no banco). Tolerante a falhas (sem banco,
 * tabela ausente) como o AppearanceService — assim páginas de erro/instalação
 * caem nos defaults sem quebrar.
 */
final class NotificacaoService
{
    public function __construct(private readonly NotificacaoSettings $settings) {}

    public function posicaoToast(): ToastPosicao
    {
        return ToastPosicao::tryFrom($this->texto('toast_posicao')) ?? ToastPosicao::padrao();
    }

    public function duracaoToast(): ToastDuracao
    {
        return ToastDuracao::tryFrom($this->texto('toast_duracao')) ?? ToastDuracao::padrao();
    }

    public function estiloToast(): ToastEstilo
    {
        return ToastEstilo::tryFrom($this->texto('toast_estilo')) ?? ToastEstilo::padrao();
    }

    public function maximoToast(): int
    {
        return max(1, min(5, $this->inteiro('toast_maximo', 3)));
    }

    public function posicaoConfirmacao(): ConfirmacaoPosicao
    {
        return ConfirmacaoPosicao::tryFrom($this->texto('confirmacao_posicao')) ?? ConfirmacaoPosicao::padrao();
    }

    /**
     * Config consumida pelo motor JS (window.__notificacaoConfig).
     *
     * @return array{toast: array{position: string, duration: int, style: string, max: int}, confirm: array{position: string}}
     */
    public function paraJsConfig(): array
    {
        return [
            'toast' => [
                'position' => $this->posicaoToast()->value,
                'duration' => $this->duracaoToast()->ms(),
                'style' => $this->estiloToast()->value,
                'max' => $this->maximoToast(),
            ],
            'confirm' => [
                'position' => $this->posicaoConfirmacao()->swal(),
            ],
        ];
    }

    private function texto(string $propriedade): string
    {
        try {
            $valor = $this->settings->{$propriedade};

            return is_string($valor) ? $valor : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function inteiro(string $propriedade, int $padrao): int
    {
        try {
            return (int) $this->settings->{$propriedade};
        } catch (Throwable) {
            return $padrao;
        }
    }
}
