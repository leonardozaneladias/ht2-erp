<?php

declare(strict_types=1);

namespace App\Contracts\Referencia;

/**
 * Fornece as unidades federativas para os formulários do core.
 *
 * A indireção não é sobre empacotamento — o ADR-0020 fixou que estes catálogos
 * ficam no core. Ela existe pela convenção da casa: componente Livewire não
 * conversa com Eloquent direto, conversa com um Service.
 *
 * Ligada em AppServiceProvider::registrarCatalogos(), sem condição: sempre há
 * uma implementação. Não escreva `app()->bound()` contra este contrato — a
 * checagem é sempre verdadeira, e o ramo do `else` nunca executa.
 */
interface FonteDeUnidadesFederativas
{
    /** @return array<string, string> sigla => nome, ordenado por nome */
    public function opcoes(): array;
}
