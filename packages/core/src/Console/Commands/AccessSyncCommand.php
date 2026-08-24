<?php

declare(strict_types=1);

namespace HT2ML\Core\Console\Commands;

use HT2ML\Core\Support\Access\PermissionRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class AccessSyncCommand extends Command
{
    protected $signature = 'access:sync
        {--prune : Remove permissões órfãs (ausentes do catálogo) que não estão em uso}
        {--dry-run : Apenas exibe o que seria feito, sem gravar}';

    protected $description = 'Sincroniza o catálogo de permissões (config/access.php) com a tabela permissions.';

    public function handle(PermissionRegistry $registry, PermissionRegistrar $registrar): int
    {
        $registrar->forgetCachedPermissions();

        $dryRun = (bool) $this->option('dry-run');
        $criadas = 0;
        $atualizadas = 0;

        foreach ($registry->todas() as $definicao) {
            $metadados = [
                'modulo' => $definicao->modulo->value,
                'label' => $definicao->label,
                'descricao' => $definicao->descricao,
            ];

            $existente = Permission::query()
                ->where('name', $definicao->nome)
                ->where('guard_name', 'admin')
                ->first();

            if ($existente === null) {
                if (! $dryRun) {
                    $permissao = Permission::findOrCreate($definicao->nome, 'admin');
                    DB::table('permissions')->where('id', $permissao->getKey())->update($metadados);
                }
                $criadas++;
                $this->line("  <fg=green>criar</>     {$definicao->nome}");

                continue;
            }

            $desatualizado = $existente->getAttribute('modulo') !== $metadados['modulo']
                || $existente->getAttribute('label') !== $metadados['label']
                || $existente->getAttribute('descricao') !== $metadados['descricao'];

            if ($desatualizado) {
                if (! $dryRun) {
                    DB::table('permissions')->where('id', $existente->getKey())->update($metadados);
                }
                $atualizadas++;
                $this->line("  <fg=yellow>atualizar</> {$definicao->nome}");
            }
        }

        if ($this->option('prune')) {
            $this->prune($registry, $dryRun);
        }

        $registrar->forgetCachedPermissions();

        $this->info(sprintf(
            '%sPermissões sincronizadas: %d criada(s), %d atualizada(s).',
            $dryRun ? '[dry-run] ' : '',
            $criadas,
            $atualizadas,
        ));

        if (! $dryRun) {
            activity('acessos')
                ->event('permissoes_sincronizadas')
                ->withProperties(['criadas' => $criadas, 'atualizadas' => $atualizadas])
                ->log('Catálogo de permissões sincronizado');
        }

        return self::SUCCESS;
    }

    private function prune(PermissionRegistry $registry, bool $dryRun): void
    {
        foreach ($registry->orfas() as $nome) {
            $permissao = Permission::query()
                ->where('name', $nome)
                ->where('guard_name', 'admin')
                ->first();

            if ($permissao === null) {
                continue;
            }

            $emUso = $permissao->roles()->exists()
                || DB::table('model_has_permissions')
                    ->where('permission_id', $permissao->getKey())
                    ->exists();

            if ($emUso) {
                $this->warn("  em uso, mantida: {$nome}");

                continue;
            }

            if (! $dryRun) {
                $permissao->delete();
            }
            $this->line("  <fg=red>remover</>   {$nome}");
        }
    }
}
