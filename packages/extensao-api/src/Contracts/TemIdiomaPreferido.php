<?php

declare(strict_types=1);

namespace HT2ML\Api\Contracts;

/**
 * Um usuário de API que guarda o idioma em que prefere ser atendido.
 *
 * O produto decide quem implementa (um responsável, um cliente, um
 * integrador). O DefinirIdioma usa a preferência quando a requisição não traz
 * `Accept-Language` — e o produto usa a mesma coluna para o que não tem
 * requisição: e-mail, push, prompt de IA.
 */
interface TemIdiomaPreferido
{
    /** O código como o cliente o manda (`pt`, `en`, `es`), ou null se não há preferência. */
    public function idiomaPreferido(): ?string;
}
