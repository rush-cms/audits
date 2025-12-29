<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PageSpeedService
{
    private const string API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * @return array<string, mixed>
     */
    public function fetchLighthouseResult(string $url, string $strategy = 'mobile'): array
    {
        $this->checkQuota();

        $queryParams = http_build_query([
            'url' => $url,
            'strategy' => $strategy,
        ]);

        $queryParams .= '&category=performance&category=seo&category=accessibility';

        $apiKey = config('audits.pagespeed.api_key');
        if ($apiKey) {
            $queryParams .= '&key='.$apiKey;
        }

        $this->incrementQuota();

        $response = Http::timeout(60)->get(self::API_URL.'?'.$queryParams);

        $response->throw();

        /** @var array<string, mixed> $data */
        $data = $response->json();

        /** @var array<string, mixed> */
        return $data['lighthouseResult'];
    }

    private function checkQuota(): void
    {
        $perMinuteLimit = (int) config('audits.pagespeed.rate_limit_per_minute', 6);
        $perDayLimit = (int) config('audits.pagespeed.rate_limit_per_day', 25000);

        $minuteKey = 'pagespeed:quota:minute:'.now()->format('YmdHi');
        $dayKey = 'pagespeed:quota:day:'.now()->format('Ymd');

        $minuteCalls = (int) Cache::get($minuteKey, 0);
        $dayCalls = (int) Cache::get($dayKey, 0);

        if ($minuteCalls >= $perMinuteLimit) {
            Log::channel('audits')->warning('PageSpeed API quota exceeded (minute)', [
                'calls' => $minuteCalls,
                'limit' => $perMinuteLimit,
            ]);

            throw new \RuntimeException('PageSpeed API minute quota exceeded. Please try again later.');
        }

        if ($dayCalls >= $perDayLimit) {
            Log::channel('audits')->warning('PageSpeed API quota exceeded (day)', [
                'calls' => $dayCalls,
                'limit' => $perDayLimit,
            ]);

            throw new \RuntimeException('PageSpeed API daily quota exceeded. Please try again tomorrow.');
        }

        $minuteUsage = round(($minuteCalls / $perMinuteLimit) * 100);
        $dayUsage = round(($dayCalls / $perDayLimit) * 100);

        if ($minuteUsage >= 80 || $dayUsage >= 80) {
            Log::channel('audits')->info('PageSpeed API quota usage high', [
                'minute_usage' => $minuteUsage.'%',
                'day_usage' => $dayUsage.'%',
                'minute_calls' => $minuteCalls,
                'day_calls' => $dayCalls,
            ]);
        }
    }

    private function incrementQuota(): void
    {
        $minuteKey = 'pagespeed:quota:minute:'.now()->format('YmdHi');
        $dayKey = 'pagespeed:quota:day:'.now()->format('Ymd');

        Cache::increment($minuteKey);
        Cache::increment($dayKey);

        Cache::put($minuteKey, Cache::get($minuteKey, 0), now()->addMinutes(2));
        Cache::put($dayKey, Cache::get($dayKey, 0), now()->addDay());
    }
}
