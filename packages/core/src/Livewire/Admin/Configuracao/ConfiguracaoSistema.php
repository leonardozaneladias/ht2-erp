<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Configuracao;

use HT2ML\Core\Enums\Admin\SettingsGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tela de Configurações do Sistema (shell).
 *
 * Navegação lateral (vertical) com busca de seções; renderiza apenas a aba
 * ativa (troca server-side). Cada aba é um componente Livewire isolado.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Configurações')]
class ConfiguracaoSistema extends Component
{
    #[Url(as: 'aba')]
    public string $abaAtiva = 'general';

    public string $busca = '';

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('configuracoes.editar')) {
            throw new AuthorizationException('Acesso negado.');
        }

        if (! in_array($this->abaAtiva, $this->valoresValidos(), true)) {
            $this->abaAtiva = 'general';
        }
    }

    public function irPara(string $aba): void
    {
        if (in_array($aba, $this->valoresValidos(), true)) {
            $this->abaAtiva = $aba;
        }
    }

    public function render(): View
    {
        $abas = SettingsGroup::abas();
        $termo = trim($this->busca);

        $filtradas = $termo === ''
            ? $abas
            : array_values(array_filter($abas, fn (SettingsGroup $aba): bool => $aba->correspondeBusca($termo)));

        return view('livewire.admin.configuracao.configuracao-sistema', [
            'abasFiltradas' => $filtradas,
            'abaAtiva' => $this->abaAtiva,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function valoresValidos(): array
    {
        return [
            ...array_map(static fn (SettingsGroup $aba): string => $aba->value, SettingsGroup::abas()),
            'danger',
        ];
    }
}
