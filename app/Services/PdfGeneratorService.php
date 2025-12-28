<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuditData;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

final class PdfGeneratorService
{
    public function generate(AuditData $data, string $lang = 'en'): string
    {
        app()->setLocale($lang);

        $tailwindCss = $this->getTailwindCss();

        $html = view('reports.audit', [
            'audit' => $data,
            'tailwindCss' => $tailwindCss,
        ])->render();

        $filename = $data->auditId.'.pdf';
        $htmlFilename = $data->auditId.'.html';
        $directory = 'reports';

        Storage::disk('public')->makeDirectory($directory);

        $pdfPath = Storage::disk('public')->path($directory.'/'.$filename);
        $htmlPath = Storage::disk('public')->path($directory.'/'.$htmlFilename);

        file_put_contents($htmlPath, $html);

        Browsershot::htmlFromFilePath($htmlPath)
            ->setNodeBinary(config('audits.browsershot.node_binary'))
            ->setNpmBinary(config('audits.browsershot.npm_binary'))
            ->setChromePath(config('audits.browsershot.chrome_path'))
            ->noSandbox()
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($pdfPath);

        unlink($htmlPath);

        return $pdfPath;
    }

    public function getPublicUrl(string $path): string
    {
        $filename = basename($path);

        return asset('storage/reports/'.$filename);
    }

    private function getTailwindCss(): string
    {
        $manifestPath = public_path('build/.vite/manifest.json');

        if (! file_exists($manifestPath)) {
            return $this->getFallbackCss();
        }

        $manifestContent = file_get_contents($manifestPath);

        if ($manifestContent === false) {
            return $this->getFallbackCss();
        }

        $manifest = json_decode($manifestContent, true);
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;

        if (! $cssFile) {
            return $this->getFallbackCss();
        }

        $cssPath = public_path('build/'.$cssFile);

        if (! file_exists($cssPath)) {
            return $this->getFallbackCss();
        }

        $cssContent = file_get_contents($cssPath);

        return $cssContent !== false ? $cssContent : $this->getFallbackCss();
    }

    private function getFallbackCss(): string
    {
        return <<<'CSS'
*, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; }
.bg-white { background-color: #fff; }
.bg-slate-50 { background-color: #f8fafc; }
.bg-slate-100 { background-color: #f1f5f9; }
.bg-slate-900 { background-color: #0f172a; }
.text-white { color: #fff; }
.text-slate-900 { color: #0f172a; }
.text-slate-500 { color: #64748b; }
.text-blue-600 { color: #2563eb; }
.rounded-2xl { border-radius: 1rem; }
.shadow-xl { box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
.p-8 { padding: 2rem; }
.max-w-4xl { max-width: 56rem; }
.mx-auto { margin-left: auto; margin-right: auto; }
CSS;
    }
}
