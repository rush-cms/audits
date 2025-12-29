<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthCheckService
{
    /**
     * @return array{status: string, checks: array<string, string>, metrics: array<string, int>}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDisk(),
            'chromium' => $this->checkChromium(),
        ];

        $metrics = [
            'queue_depth' => $this->getQueueDepth(),
            'failed_jobs_last_hour' => $this->getFailedJobsCount(),
            'disk_usage_percent' => $this->getDiskUsagePercent(),
            'audits_last_hour' => $this->getAuditsLastHour(),
        ];

        $allHealthy = ! in_array('fail', $checks, true);
        $status = $allHealthy ? 'healthy' : 'unhealthy';

        return [
            'status' => $status,
            'checks' => $checks,
            'metrics' => $metrics,
        ];
    }

    private function checkDatabase(): string
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            DB::table('audits')->limit(1)->count();
            $duration = (microtime(true) - $startTime) * 1000;

            return $duration < 100 ? 'ok' : 'slow';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkRedis(): string
    {
        try {
            $startTime = microtime(true);
            $key = 'health_check_'.time();
            Redis::set($key, 'test', 'EX', 5);
            $value = Redis::get($key);
            Redis::del($key);
            $duration = (microtime(true) - $startTime) * 1000;

            if ($value !== 'test') {
                return 'fail';
            }

            return $duration < 50 ? 'ok' : 'slow';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkQueue(): string
    {
        try {
            $depth = $this->getQueueDepth();
            $failedCount = $this->getFailedJobsCount();

            if ($depth > 100 || $failedCount > 10) {
                return 'warning';
            }

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkDisk(): string
    {
        try {
            $usagePercent = $this->getDiskUsagePercent();

            if ($usagePercent >= 90) {
                return 'critical';
            }

            if ($usagePercent >= 80) {
                return 'warning';
            }

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkChromium(): string
    {
        try {
            $chromePath = config('browsershot.chrome_path', '/usr/bin/google-chrome');

            if (! file_exists($chromePath)) {
                return 'fail';
            }

            if (! is_executable($chromePath)) {
                return 'fail';
            }

            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function getQueueDepth(): int
    {
        try {
            return (int) Queue::size();
        } catch (Throwable) {
            return 0;
        }
    }

    private function getFailedJobsCount(): int
    {
        try {
            return (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHour())
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function getDiskUsagePercent(): int
    {
        try {
            $path = storage_path();
            $total = disk_total_space($path);
            $free = disk_free_space($path);

            if ($total === false || $free === false || $total === 0) {
                return 0;
            }

            return (int) round((($total - $free) / $total) * 100);
        } catch (Throwable) {
            return 0;
        }
    }

    private function getAuditsLastHour(): int
    {
        try {
            return Audit::where('created_at', '>=', now()->subHour())->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
