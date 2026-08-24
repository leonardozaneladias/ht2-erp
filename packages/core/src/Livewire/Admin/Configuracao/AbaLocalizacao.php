<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Configuracao;

use HT2ML\Core\Actions\Admin\Settings\SaveLocalizacaoSettingsAction;
use HT2ML\Core\DTOs\Admin\Settings\LocalizacaoSettingsDTO;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Settings\LocalizacaoSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Aba "Localização": idioma, fuso horário, formato de data e moeda.
 * Os valores são aplicados ao config() no boot pelo SettingsRuntimeApplier.
 */
class AbaLocalizacao extends Component
{
    use EmiteNotificacoes;

    public string $idioma = 'pt_BR';

    public string $timezone = 'America/Sao_Paulo';

    public string $formato_data = 'd/m/Y';

    public string $moeda_simbolo = 'R$';

    public int $casas_decimais = 2;

    public function mount(LocalizacaoSettings $settings): void
    {
        $this->idioma = $settings->idioma;
        $this->timezone = $settings->timezone;
        $this->formato_data = $settings->formato_data;
        $this->moeda_simbolo = $settings->moeda_simbolo;
        $this->casas_decimais = $settings->casas_decimais;
    }

    public function salvar(SaveLocalizacaoSettingsAction $action): void
    {
        $this->validate();

        $action->execute(new LocalizacaoSettingsDTO(
            idioma: $this->idioma,
            timezone: $this->timezone,
            formato_data: $this->formato_data,
            moeda_simbolo: $this->moeda_simbolo,
            casas_decimais: $this->casas_decimais,
        ));

        $this->notificarSucesso('Configurações de localização salvas.');
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-localizacao', [
            'idiomas' => [
                'pt_BR' => 'Português (Brasil)',
                'en' => 'English (US)',
            ],
            'timezones' => [
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
            ],
            'formatos' => [
                'd/m/Y' => 'dd/mm/aaaa',
                'd/m/Y H:i' => 'dd/mm/aaaa hh:mm',
                'Y-m-d' => 'aaaa-mm-dd',
                'm/d/Y' => 'mm/dd/aaaa',
            ],
            'opcoesCasas' => [
                ['value' => 0, 'label' => '0'],
                ['value' => 1, 'label' => '1'],
                ['value' => 2, 'label' => '2'],
                ['value' => 3, 'label' => '3'],
                ['value' => 4, 'label' => '4'],
            ],
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'idioma' => ['required', 'in:pt_BR,en'],
            'timezone' => ['required', 'timezone'],
            'formato_data' => ['required', 'string', 'max:30'],
            'moeda_simbolo' => ['required', 'string', 'max:6'],
            'casas_decimais' => ['required', 'integer', 'min:0', 'max:4'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'formato_data' => 'formato de data',
            'moeda_simbolo' => 'símbolo da moeda',
            'casas_decimais' => 'casas decimais',
        ];
    }
}
