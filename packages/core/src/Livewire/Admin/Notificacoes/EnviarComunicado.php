<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Notificacoes;

use Closure;
use HT2ML\Core\Actions\Admin\Notificacoes\EnviarComunicadoAction;
use HT2ML\Core\DTOs\Admin\ComunicadoDTO;
use HT2ML\Core\Enums\PublicoComunicado;
use HT2ML\Core\Enums\TipoNotificacao;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Composição e envio de comunicados in-app aos usuários. Restrito à permissão
 * notificacoes.enviar.
 *
 * @property-read Collection<int, string> $papeis
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Enviar comunicado')]
final class EnviarComunicado extends Component
{
    public string $tipo = TipoNotificacao::Info->value;

    public string $titulo = '';

    public string $mensagem = '';

    public string $publico = PublicoComunicado::Todos->value;

    public string $papel = '';

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('notificacoes.enviar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoNotificacao::class)],
            'titulo' => ['required', 'string', 'max:120'],
            // A mensagem é HTML (rich text). Mede obrigatoriedade e limite pelo
            // texto puro (strip_tags), não pelas tags.
            'mensagem' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                $texto = trim(strip_tags((string) $value));

                if ($texto === '') {
                    $fail('A mensagem é obrigatória.');

                    return;
                }

                if (mb_strlen($texto) > 1000) {
                    $fail('A mensagem deve ter no máximo 1000 caracteres.');
                }
            }],
            'publico' => ['required', Rule::enum(PublicoComunicado::class)],
            'papel' => ['nullable', 'required_if:publico,papel', Rule::in($this->papeis->all())],
        ];
    }

    public function enviar(EnviarComunicadoAction $action): void
    {
        $dados = $this->validate();

        $total = $action->execute(ComunicadoDTO::fromArray($dados));

        $this->reset(['titulo', 'mensagem']);

        $this->dispatch(
            'toast',
            variant: 'success',
            message: "Comunicado enviado para {$total} usuário(s).",
        );
    }

    /**
     * Papéis globais disponíveis para segmentação.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function papeis(): Collection
    {
        return Role::query()->where('guard_name', 'admin')->orderBy('name')->pluck('name');
    }

    public function render(): View
    {
        return view('livewire.admin.notificacoes.enviar-comunicado', [
            'tipos' => TipoNotificacao::options(),
            'publicos' => PublicoComunicado::options(),
        ]);
    }
}
