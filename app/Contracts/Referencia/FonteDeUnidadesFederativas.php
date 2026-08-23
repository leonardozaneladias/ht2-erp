<?php

declare(strict_types=1);

namespace App\Contracts\Referencia;

/**
 * Fornece as unidades federativas para os formulários do core.
 *
 * O core **não** implementa: quem implementa é a extensão de localização do país
 * (ht2ml/localizacao-br e afins). Sem nenhuma ligada, o formulário degrada para
 * campo de texto livre — que é o que a coluna sempre foi no banco (`size:2`,
 * string, sem FK).
 *
 * É esta indireção que permite ao core ficar abstrato sem perder o select: ele
 * pergunta ao container se existe uma fonte, em vez de importar um model.
 */
interface FonteDeUnidadesFederativas
{
    /** @return array<string, string> sigla => nome, ordenado por nome */
    public function opcoes(): array;
}
