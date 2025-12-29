<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = $user->currentAccessToken();
        $tokenId = $token !== null ? (string) $token->id : 'unknown';
        $tokenKey = "token:{$tokenId}";

        $globalExceeded = $this->checkGlobalRateLimit();
        if ($globalExceeded) {
            return $this->buildRateLimitResponse(
                'Global rate limit exceeded. Please try again later.',
                $this->getRetryAfter('global', config('audits.rate_limit.global_per_minute'))
            );
        }

        $perMinuteExceeded = $this->checkRateLimit(
            $tokenKey,
            'minute',
            config('audits.rate_limit.per_minute'),
            60
        );

        if ($perMinuteExceeded) {
            return $this->buildRateLimitResponse(
                'Too many requests. Please try again later.',
                $this->getRetryAfter("{$tokenKey}:minute", config('audits.rate_limit.per_minute'))
            );
        }

        $perHourExceeded = $this->checkRateLimit(
            $tokenKey,
            'hour',
            config('audits.rate_limit.per_hour'),
            3600
        );

        if ($perHourExceeded) {
            return $this->buildRateLimitResponse(
                'Hourly rate limit exceeded. Please try again later.',
                $this->getRetryAfter("{$tokenKey}:hour", config('audits.rate_limit.per_hour'))
            );
        }

        $perDayExceeded = $this->checkRateLimit(
            $tokenKey,
            'day',
            config('audits.rate_limit.per_day'),
            86400
        );

        if ($perDayExceeded) {
            return $this->buildRateLimitResponse(
                'Daily rate limit exceeded. Please try again tomorrow.',
                $this->getRetryAfter("{$tokenKey}:day", config('audits.rate_limit.per_day'))
            );
        }

        $this->incrementCounters($tokenKey);

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $tokenKey);
    }

    private function checkGlobalRateLimit(): bool
    {
        $key = 'global';
        $limit = config('audits.rate_limit.global_per_minute');
        $window = 60;

        return $this->checkRateLimit($key, 'minute', $limit, $window);
    }

    private function checkRateLimit(string $key, string $period, int $limit, int $window): bool
    {
        $cacheKey = "rate_limit:{$key}:{$period}";
        $current = (int) Cache::get($cacheKey, 0);

        return $current >= $limit;
    }

    private function incrementCounters(string $tokenKey): void
    {
        $this->increment('global', 'minute', 60);
        $this->increment($tokenKey, 'minute', 60);
        $this->increment($tokenKey, 'hour', 3600);
        $this->increment($tokenKey, 'day', 86400);
    }

    private function increment(string $key, string $period, int $ttl): void
    {
        $cacheKey = "rate_limit:{$key}:{$period}";

        if (Cache::has($cacheKey)) {
            Cache::increment($cacheKey);
        } else {
            Cache::put($cacheKey, 1, $ttl);
        }
    }

    private function getRetryAfter(string $key, int $limit): int
    {
        $cacheKey = "rate_limit:{$key}";

        return 60;
    }

    private function buildRateLimitResponse(string $message, int $retryAfter): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'retry_after' => $retryAfter,
        ], 429)
            ->header('Retry-After', (string) $retryAfter)
            ->header('X-RateLimit-Limit', (string) config('audits.rate_limit.per_minute'))
            ->header('X-RateLimit-Remaining', '0')
            ->header('X-RateLimit-Reset', (string) (time() + $retryAfter));
    }

    private function addRateLimitHeaders(Response $response, string $tokenKey): Response
    {
        $limit = config('audits.rate_limit.per_minute');
        $cacheKey = "rate_limit:{$tokenKey}:minute";
        $current = (int) Cache::get($cacheKey, 0);
        $remaining = max($limit - $current, 0);
        $reset = time() + 60;

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $reset);

        return $response;
    }
}
