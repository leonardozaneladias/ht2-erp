<?php

declare(strict_types=1);

namespace HT2ML\Api\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base de todo Resource da API. Não impõe formato de campo: é o ponto único de
 * extensão comum a todos os Resources do produto.
 *
 * Nunca serializar um Model cru (`response()->json($model)`); sempre por um
 * Resource — é o que mantém o contrato estável quando uma coluna muda de nome.
 *
 * `toArray()` já é concreto em JsonResource; redeclará-lo `abstract` aqui
 * estoura "Cannot make non-abstract method ... abstract".
 */
abstract class ApiResource extends JsonResource {}
