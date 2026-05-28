<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        $request->headers->set('X-Request-Id', $requestId);

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            $response = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)
                ->render($request, $e);
        }

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
