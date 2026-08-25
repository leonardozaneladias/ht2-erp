<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Access;

use HT2ML\Core\DTOs\Admin\PermissionDefinitionDTO;
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

        foreach ($this->areas() as $area => $permissoes) {
            foreach ($permissoes as $nome => $meta) {
                $definicoes->push(PermissionDefinitionDTO::fromArray([
                    'nome' => $nome,
                    'area' => $area,
                    'label' => $meta['label'],
                    'descricao' => $meta['descricao'] ?? null,
                ]));
            }
        }

        return $definicoes;
    }

    /**
     * Permissões agrupadas pela área do catálogo.
     *
     * @return Collection<string, Collection<int, PermissionDefinitionDTO>>
     */
    public function porArea(): Collection
    {
        return $this->todas()->groupBy(
            fn (PermissionDefinitionDTO $definicao): string => $definicao->area->chave,
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

    public function areaDe(string $permissao): ?AreaDeAcesso
    {
        return $this->todas()
            ->first(fn (PermissionDefinitionDTO $definicao): bool => $definicao->nome === $permissao)
            ?->area;
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
     * O catálogo, indexado pela chave da área.
     *
     * @return array<string, array<string, array{label: string, descricao?: ?string}>>
     */
    private function areas(): array
    {
        /** @var array<string, array<string, array{label: string, descricao?: ?string}>> $areas */
        $areas = config('access.modules', []);

        return $areas;
    }
}
