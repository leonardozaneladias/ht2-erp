<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use HT2ML\Core\DTOs\Admin\Settings\GeneralSettingsDTO;
use HT2ML\Core\Settings\GeneralSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class SaveGeneralSettingsAction
{
    public function execute(GeneralSettingsDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $settings = app(GeneralSettings::class);

            $settings->nome_cliente = $dto->nome_cliente;
            $settings->razao_social = $dto->razao_social;
            $settings->cnpj = $dto->cnpj;
            $settings->inscricao_estadual = $dto->inscricao_estadual;
            $settings->telefone = $dto->telefone;
            $settings->email_contato = $dto->email_contato;
            $settings->endereco = $dto->endereco;
            $settings->numero = $dto->numero;
            $settings->bairro = $dto->bairro;
            $settings->cidade = $dto->cidade;
            $settings->estado = $dto->estado;
            $settings->cep = $dto->cep;
            $settings->site_url = $dto->site_url;
            $settings->save();

            activity('configuracoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'nome_cliente' => $dto->nome_cliente,
                    'razao_social' => $dto->razao_social,
                    'cnpj' => $dto->cnpj,
                ])
                ->event('updated')
                ->log('Configurações da empresa atualizadas');
        });
    }
}
