<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class PageSpeedService
{
    private const string API_URL = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * @return array<string, mixed>
     */
    public function fetchLighthouseResult(string $url, string $strategy = 'mobile'): array
    {
        $params = [
            'url' => $url,
            'strategy' => $strategy,
            'category' => 'performance',
        ];

        $apiKey = config('audits.pagespeed.api_key');
        if ($apiKey) {
            $params['key'] = $apiKey;
        }

        $response = Http::timeout(60)->get(self::API_URL, $params);

        $response->throw();

        /** @var array<string, mixed> $data */
        $data = $response->json();

        /** @var array<string, mixed> */
        return $data['lighthouseResult'];
    }
}
