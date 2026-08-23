<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\DTOs\Admin\PermissionDefinitionDTO;
use HT2ML\Core\Enums\ModuloAcesso;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class PermissionRegistry
{
    /**
     * Todas as permissões declaradas no catálogo (config/access.php), achatadas.
     *
     * @return Collection<int, PermissionDefinitionDTO>
     */
    public function todas(): Collection
    {
        /** @var Collection<int, PermissionDefinitionDTO> $definicoes */
        $definicoes = collect();

        foreach ($this->modulos() as $modulo => $permissoes) {
            foreach ($permissoes as $nome => $meta) {
                $definicoes->push(PermissionDefinitionDTO::fromArray([
                    'nome' => $nome,
                    'modulo' => $modulo,
                    'label' => $meta['label'],
                    'descricao' => $meta['descricao'] ?? null,
                ]));
            }
        }

        return $definicoes;
    }

    /**
     * Permissões agrupadas por módulo (valor do enum ModuloAcesso).
     *
     * @return Collection<string, Collection<int, PermissionDefinitionDTO>>
     */
    public function porModulo(): Collection
    {
        return $this->todas()->groupBy(
            fn (PermissionDefinitionDTO $definicao): string => $definicao->modulo->value,
        );
    }

    /**
     * Nomes das permissões declaradas (para validação e seeds).
     *
     * @return list<string>
     */
    public function nomes(): array
    {
        return $this->todas()
            ->map(fn (PermissionDefinitionDTO $definicao): string => $definicao->nome)
            ->values()
            ->all();
    }

    public function existe(string $permissao): bool
    {
        return in_array($permissao, $this->nomes(), true);
    }

    public function moduloDe(string $permissao): ?ModuloAcesso
    {
        return $this->todas()
            ->first(fn (PermissionDefinitionDTO $definicao): bool => $definicao->nome === $permissao)
            ?->modulo;
    }

    /**
     * Permissões presentes na tabela mas ausentes do catálogo (órfãs).
     *
     * @return list<string>
     */
    public function orfas(): array
    {
        /** @var list<string> $existentes */
        $existentes = Permission::query()
            ->where('guard_name', 'admin')
            ->pluck('name')
            ->all();

        return array_values(array_diff($existentes, $this->nomes()));
    }

    /**
     * @return array<string, array<string, array{label: string, descricao?: ?string}>>
     */
    private function modulos(): array
    {
        /** @var array<string, array<string, array{label: string, descricao?: ?string}>> $modulos */
        $modulos = config('access.modules', []);

        return $modulos;
    }
}
