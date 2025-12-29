<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateRequestSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxSize = (int) config('audits.api.max_request_size', 1048576);
        $contentLength = (int) ($request->header('Content-Length') ?? 0);

        if ($contentLength > $maxSize) {
            return response()->json([
                'error' => 'Payload too large',
                'max_size' => number_format($maxSize / 1024 / 1024, 2).' MB',
                'received_size' => number_format($contentLength / 1024 / 1024, 2).' MB',
            ], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        return $next($request);
    }
}
