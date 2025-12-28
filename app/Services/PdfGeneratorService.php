<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AuditData;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

final class PdfGeneratorService
{
    public function generate(AuditData $data): string
    {
        $html = view('reports.audit', ['audit' => $data])->render();

        $filename = $data->auditId . '.pdf';
        $htmlFilename = $data->auditId . '.html';
        $directory = 'reports';

        Storage::disk('public')->makeDirectory($directory);

        $pdfPath = Storage::disk('public')->path($directory . '/' . $filename);
        $htmlPath = Storage::disk('public')->path($directory . '/' . $htmlFilename);

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
}
