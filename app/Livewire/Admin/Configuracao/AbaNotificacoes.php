<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Actions\Admin\Settings\SaveNotificacaoSettingsAction;
use App\DTOs\Admin\Settings\NotificacaoSettingsDTO;
use App\Enums\Admin\Notificacao\ConfirmacaoPosicao;
use App\Enums\Admin\Notificacao\ToastDuracao;
use App\Enums\Admin\Notificacao\ToastEstilo;
use App\Enums\Admin\Notificacao\ToastPosicao;
use App\Settings\NotificacaoSettings;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Aba "Notificações": posição, duração, estilo e quantidade dos toasts, além da
 * posição das confirmações. Os valores alimentam NotificacaoSettings e são
 * aplicados no frontend (toast.js / confirm.js) via NotificacaoService.
 */
class AbaNotificacoes extends Component
{
    use EmiteNotificacoes;

    public string $toast_posicao = 'top-center';

    public string $toast_duracao = 'media';

    public string $toast_estilo = 'pilula';

    public int $toast_maximo = 3;

    public string $confirmacao_posicao = 'center';

    public function mount(NotificacaoSettings $settings): void
    {
        $this->toast_posicao = $settings->toast_posicao;
        $this->toast_duracao = $settings->toast_duracao;
        $this->toast_estilo = $settings->toast_estilo;
        $this->toast_maximo = $settings->toast_maximo;
        $this->confirmacao_posicao = $settings->confirmacao_posicao;
    }

    public function salvar(SaveNotificacaoSettingsAction $action): void
    {
        $this->validate();

        $action->execute(new NotificacaoSettingsDTO(
            toast_posicao: $this->toast_posicao,
            toast_duracao: $this->toast_duracao,
            toast_estilo: $this->toast_estilo,
            toast_maximo: $this->toast_maximo,
            confirmacao_posicao: $this->confirmacao_posicao,
        ));

        $this->notificarSucesso('Configurações de notificações salvas.');
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-notificacoes', [
            'posicoes' => ToastPosicao::options(),
            'duracoes' => ToastDuracao::options(),
            'estilos' => ToastEstilo::options(),
            'posicoesConfirmacao' => ConfirmacaoPosicao::options(),
            'opcoesMaximo' => array_map(
                static fn (int $n): array => ['value' => $n, 'label' => (string) $n],
                range(1, 5),
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'toast_posicao' => ['required', Rule::in(ToastPosicao::valores())],
            'toast_duracao' => ['required', Rule::in(ToastDuracao::valores())],
            'toast_estilo' => ['required', Rule::in(ToastEstilo::valores())],
            'toast_maximo' => ['required', 'integer', 'min:1', 'max:5'],
            'confirmacao_posicao' => ['required', Rule::in(ConfirmacaoPosicao::valores())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'toast_posicao' => 'posição das notificações',
            'toast_duracao' => 'duração',
            'toast_estilo' => 'estilo',
            'toast_maximo' => 'quantidade máxima',
            'confirmacao_posicao' => 'posição das confirmações',
        ];
    }
}
