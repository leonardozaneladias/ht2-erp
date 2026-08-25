<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Settings;

use HT2ML\Core\Actions\Admin\CreateAdminUserAction;
use HT2ML\Core\Actions\Admin\CreateEmpresaAction;
use HT2ML\Core\DTOs\Admin\AdminUserDTO;
use HT2ML\Core\DTOs\Admin\EmpresaDTO;
use HT2ML\Core\DTOs\Admin\Settings\SetupDTO;
use HT2ML\Core\Settings\BrandingSettings;
use HT2ML\Core\Settings\GeneralSettings;
use Illuminate\Support\Facades\DB;

/**
 * Conclui a instalação inicial: grava o nome do grupo/instalação e a marca, cria
 * a 1ª empresa (com filial Matriz) a partir dos dados do cliente, cria o primeiro
 * super-admin já vinculado a essa empresa, e marca o sistema como instalado —
 * tudo de forma atômica.
 */
final class ConcluirSetupAction
{
    public function __construct(
        private readonly CreateAdminUserAction $criarAdmin,
        private readonly CreateEmpresaAction $criarEmpresa,
    ) {}

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

            // 1ª empresa do ambiente (com filial Matriz), a partir dos dados do cliente.
            $empresa = $this->criarEmpresa->execute(EmpresaDTO::fromArray([
                'nome' => $dto->nome_cliente,
                'razao_social' => $dto->razao_social,
                'cnpj' => $dto->cnpj,
                'cor_primaria' => $dto->cor_primaria,
            ]));

            $admin = $this->criarAdmin->execute(new AdminUserDTO(
                nome: $dto->admin_nome,
                email: $dto->admin_email,
                ativo: true,
                roles: ['super-admin'],
                password: $dto->admin_senha,
            ));

            // Vincula o super-admin à empresa criada e a define como ativa.
            $admin->empresasAcessiveis()->syncWithoutDetaching([
                $empresa->id => ['todas_filiais' => true],
            ]);
            $admin->update(['empresa_ativa_id' => $empresa->id]);

            activity('configuracoes')
                ->withProperties(['nome_cliente' => $dto->nome_cliente])
                ->event('updated')
                ->log('Instalação concluída pelo Setup Wizard.');
        });
    }
}
