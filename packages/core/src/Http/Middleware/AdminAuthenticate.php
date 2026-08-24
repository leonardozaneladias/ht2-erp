<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;

final class AdminAuthenticate extends Authenticate
{
    /** @param array<string> $guards */
    protected function authenticate($request, array $guards): void
    {
        parent::authenticate($request, empty($guards) ? ['admin'] : $guards);
    }

    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('admin.login');
    }
}
