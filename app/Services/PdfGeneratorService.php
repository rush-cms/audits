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

        $filename = $data->auditId.'.pdf';
        $directory = 'reports';

        Storage::disk('public')->makeDirectory($directory);

        $path = Storage::disk('public')->path($directory.'/'.$filename);

        Browsershot::html($html)
            ->setNodeBinary(config('audits.browsershot.node_binary'))
            ->setNpmBinary(config('audits.browsershot.npm_binary'))
            ->setChromePath(config('audits.browsershot.chrome_path'))
            ->format('A4')
            ->margins(15, 15, 15, 15)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($path);

        return $path;
    }

    public function getPublicUrl(string $path): string
    {
        $filename = basename($path);

        return asset('storage/reports/'.$filename);
    }
}
