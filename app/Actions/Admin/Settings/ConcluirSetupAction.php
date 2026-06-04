<?php

declare(strict_types=1);

namespace App\Actions\Admin\Settings;

use App\Actions\Admin\CreateAdminUserAction;
use App\DTOs\Admin\AdminUserDTO;
use App\DTOs\Admin\Settings\SetupDTO;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

/**
 * Conclui a instalação inicial: grava dados da empresa e marca, cria o primeiro
 * super-admin e marca o sistema como instalado — tudo de forma atômica.
 */
final class ConcluirSetupAction
{
    public function __construct(private readonly CreateAdminUserAction $criarAdmin) {}

    public function execute(SetupDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $general = app(GeneralSettings::class);
            $general->nome_cliente = $dto->nome_cliente;
            $general->razao_social = $dto->razao_social;
            $general->cnpj = $dto->cnpj;
            $general->instalado = true;
            $general->save();

            $branding = app(BrandingSettings::class);
            $branding->nome_sistema = $dto->nome_sistema;
            $branding->cor_primaria = $dto->cor_primaria;
            $branding->save();

            $this->criarAdmin->execute(new AdminUserDTO(
                nome: $dto->admin_nome,
                email: $dto->admin_email,
                ativo: true,
                roles: ['super-admin'],
                password: $dto->admin_senha,
            ));

            activity('configuracoes')
                ->withProperties(['nome_cliente' => $dto->nome_cliente])
                ->event('updated')
                ->log('Instalação concluída pelo Setup Wizard.');
        });
    }
}
