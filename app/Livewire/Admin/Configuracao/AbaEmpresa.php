<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Actions\Admin\Settings\SaveGeneralSettingsAction;
use App\DTOs\Admin\Settings\GeneralSettingsDTO;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Settings\GeneralSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Aba "Empresa": dados do cliente dono desta instalação.
 */
class AbaEmpresa extends Component
{
    use EmiteNotificacoes;

    public string $nome_cliente = '';

    public string $razao_social = '';

    public string $cnpj = '';

    public string $inscricao_estadual = '';

    public string $telefone = '';

    public ?string $email_contato = null;

    public string $endereco = '';

    public string $numero = '';

    public string $bairro = '';

    public string $cidade = '';

    public string $estado = '';

    public string $cep = '';

    public ?string $site_url = null;

    public function mount(GeneralSettings $settings): void
    {
        $this->nome_cliente = $settings->nome_cliente;
        $this->razao_social = $settings->razao_social;
        $this->cnpj = $settings->cnpj;
        $this->inscricao_estadual = $settings->inscricao_estadual;
        $this->telefone = $settings->telefone;
        $this->email_contato = $settings->email_contato ?: null;
        $this->endereco = $settings->endereco;
        $this->numero = $settings->numero;
        $this->bairro = $settings->bairro;
        $this->cidade = $settings->cidade;
        $this->estado = $settings->estado;
        $this->cep = $settings->cep;
        $this->site_url = $settings->site_url ?: null;
    }

    public function salvar(SaveGeneralSettingsAction $action): void
    {
        // Livewire envia '' (não null) para campos vazios; normalizamos para que
        // as regras 'nullable|email|url' não rejeitem strings vazias.
        $this->email_contato = blank($this->email_contato) ? null : $this->email_contato;
        $this->site_url = blank($this->site_url) ? null : $this->site_url;

        $dados = $this->validate();

        $action->execute(GeneralSettingsDTO::fromArray($dados));

        $this->notificarSucesso('Dados da empresa salvos.');
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-empresa');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'nome_cliente' => ['required', 'string', 'max:160'],
            'razao_social' => ['nullable', 'string', 'max:160'],
            'cnpj' => ['nullable', 'string', 'max:18'],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email_contato' => ['nullable', 'email:rfc', 'max:160'],
            'endereco' => ['nullable', 'string', 'max:160'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:9'],
            'site_url' => ['nullable', 'url', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome_cliente' => 'nome do cliente',
            'razao_social' => 'razão social',
            'inscricao_estadual' => 'inscrição estadual',
            'email_contato' => 'e-mail de contato',
            'site_url' => 'site',
            'estado' => 'UF',
        ];
    }
}
