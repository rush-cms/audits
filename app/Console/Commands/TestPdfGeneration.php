<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\AccessibilityData;
use App\Data\AuditData;
use App\Data\SeoData;
use App\Services\PdfGeneratorService;
use App\ValueObjects\AuditScore;
use App\ValueObjects\MetricValue;
use App\ValueObjects\Url;
use Illuminate\Console\Command;

class TestPdfGeneration extends Command
{
    protected $signature = 'test:pdf {--lang=pt_BR : Language for the report}';

    protected $description = 'Generate a test PDF using fixture data (no API calls)';

    public function handle(PdfGeneratorService $pdfService): int
    {
        $this->info('Generating test PDF...');

        $auditData = new AuditData(
            targetUrl: new Url('https://exemplo.com.br/'),
            score: new AuditScore(0.36),
            lcp: MetricValue::fromDisplayValue('4.2 s'),
            fcp: MetricValue::fromDisplayValue('2.8 s'),
            cls: MetricValue::fromDisplayValue('0.15'),
            auditId: 'test-'.now()->timestamp,
            seo: new SeoData(
                score: new AuditScore(0.85),
                failedAudits: [
                    ['id' => 'robots-txt', 'title' => 'robots.txt is not valid', 'description' => ''],
                    ['id' => 'meta-description', 'title' => 'Document does not have a meta description', 'description' => ''],
                ],
            ),
            accessibility: new AccessibilityData(
                score: new AuditScore(0.78),
                failedAudits: [
                    ['id' => 'link-name', 'title' => 'Links do not have a discernible name', 'description' => ''],
                    ['id' => 'color-contrast', 'title' => 'Background and foreground colors do not have a sufficient contrast ratio', 'description' => ''],
                ],
            ),
            desktopScreenshot: 'https://placehold.co/600x400/1e293b/94a3b8?text=Desktop+Preview',
            mobileScreenshot: 'https://placehold.co/300x600/1e293b/94a3b8?text=Mobile',
        );

        $lang = (string) $this->option('lang');
        $path = $pdfService->generate($auditData, $lang);

        $this->info('PDF generated: '.$pdfService->getPublicUrl($path));

        return Command::SUCCESS;
    }
}
