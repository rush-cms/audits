<?php

declare(strict_types=1);

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;

final class ScreenshotService
{
    private const int DESKTOP_WIDTH = 1920;

    private const int DESKTOP_HEIGHT = 1080;

    private const int MOBILE_WIDTH = 375;

    private const int MOBILE_HEIGHT = 812;

    private const int OUTPUT_WIDTH = 600;

    /**
     * @return array{desktop: string|null, mobile: string|null, failed: bool, error: string|null}
     */
    public function capture(string $url, string $auditId): array
    {
        $storagePath = storage_path('app/public/screenshots');
        if (! is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        $result = [
            'desktop' => null,
            'mobile' => null,
            'failed' => false,
            'error' => null,
        ];

        try {
            $desktopPath = $this->captureDesktop($url, $auditId, $storagePath);
            $result['desktop'] = $desktopPath;
        } catch (\Throwable $e) {
            $result['failed'] = true;
            $result['error'] = 'Desktop: '.$e->getMessage();
        }

        try {
            $mobilePath = $this->captureMobile($url, $auditId, $storagePath);
            $result['mobile'] = $mobilePath;
        } catch (\Throwable $e) {
            $result['failed'] = true;
            $result['error'] = ($result['error'] ? $result['error'].' | ' : '').'Mobile: '.$e->getMessage();
        }

        return $result;
    }

    private function captureDesktop(string $url, string $auditId, string $storagePath): string
    {
        $tempPath = $storagePath.'/'.$auditId.'_desktop_temp.png';
        $finalPath = $storagePath.'/'.$auditId.'_desktop.webp';

        $this->createBrowsershot($url)
            ->windowSize(self::DESKTOP_WIDTH, self::DESKTOP_HEIGHT)
            ->save($tempPath);

        $this->convertToWebp($tempPath, $finalPath);

        return $finalPath;
    }

    private function captureMobile(string $url, string $auditId, string $storagePath): string
    {
        $tempPath = $storagePath.'/'.$auditId.'_mobile_temp.png';
        $finalPath = $storagePath.'/'.$auditId.'_mobile.webp';

        $this->createBrowsershot($url)
            ->windowSize(self::MOBILE_WIDTH, self::MOBILE_HEIGHT)
            ->mobile()
            ->save($tempPath);

        $this->convertToWebp($tempPath, $finalPath);

        return $finalPath;
    }

    private function createBrowsershot(string $url): Browsershot
    {
        return Browsershot::url($url)
            ->setNodeBinary(config('audits.browsershot.node_binary'))
            ->setNpmBinary(config('audits.browsershot.npm_binary'))
            ->setChromePath(config('audits.browsershot.chrome_path'))
            ->noSandbox()
            ->dismissDialogs()
            ->waitUntilNetworkIdle()
            ->timeout(30);
    }

    private function convertToWebp(string $sourcePath, string $destinationPath): void
    {
        Image::useImageDriver(ImageDriver::Gd)
            ->loadFile($sourcePath)
            ->width(self::OUTPUT_WIDTH)
            ->save($destinationPath);

        if (file_exists($sourcePath)) {
            unlink($sourcePath);
        }
    }

    public function getPublicUrl(string $path): string
    {
        $filename = basename($path);

        return url('storage/screenshots/'.$filename);
    }
}
