<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Aba "Preferências" — idioma e fuso horário do próprio usuário. Nulo herda o
 * padrão da instância. O locale é aplicado por request (middleware) e o fuso só
 * na exibição de datas.
 */
class PreferenciasConta extends Component
{
    public ?string $locale = null;

    public ?string $timezone = null;

    public function mount(): void
    {
        $usuario = $this->usuario();
        $this->locale = $usuario->locale;
        $this->timezone = $usuario->timezone;
    }

    public function salvar(): void
    {
        $this->validate([
            'locale' => ['nullable', Rule::in(array_keys($this->locales()))],
            'timezone' => ['nullable', Rule::in(array_keys($this->timezones()))],
        ]);

        $this->usuario()->forceFill([
            'locale' => $this->locale ?: null,
            'timezone' => $this->timezone ?: null,
        ])->save();

        $this->dispatch('toast', variant: 'success', message: 'Preferências salvas.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.preferencias-conta', [
            'locales' => $this->locales(),
            'timezones' => $this->timezones(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function locales(): array
    {
        return [
            'pt_BR' => 'Português (Brasil)',
            'en' => 'English (US)',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function timezones(): array
    {
        return [
            'America/Sao_Paulo' => 'Brasília (São Paulo)',
            'America/Manaus' => 'Manaus',
            'America/Cuiaba' => 'Cuiabá',
            'America/Campo_Grande' => 'Campo Grande',
            'America/Belem' => 'Belém',
            'America/Fortaleza' => 'Fortaleza',
            'America/Recife' => 'Recife',
            'America/Bahia' => 'Salvador',
            'America/Rio_Branco' => 'Rio Branco',
            'America/Noronha' => 'Fernando de Noronha',
            'UTC' => 'UTC',
        ];
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
