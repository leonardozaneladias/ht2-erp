<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\OrigemAcesso;
use Illuminate\Support\Carbon;

final readonly class AcessoEfetivoDTO
{
    public function __construct(
        public string $ability,
        public bool $permitido,
        public OrigemAcesso $origem,
        public ?string $roleDeOrigem = null,
        public ?int $grantId = null,
        public ?Carbon $expiraEm = null,
        public ?string $motivo = null,
    ) {}
}
