<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Admin\ExpirarAcessosVencidosAction;
use Illuminate\Console\Command;

final class AccessExpirarCommand extends Command
{
    protected $signature = 'access:expirar';

    protected $description = 'Marca como revogadas as concessões de acesso direto que já venceram.';

    public function handle(ExpirarAcessosVencidosAction $action): int
    {
        $total = $action->execute();

        $this->info("Acessos expirados: {$total}.");

        return self::SUCCESS;
    }
}
