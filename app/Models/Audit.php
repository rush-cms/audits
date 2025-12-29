<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class Audit extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'idempotency_key',
        'url',
        'strategy',
        'lang',
        'status',
        'score',
        'metrics',
        'pdf_path',
        'error_message',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function markAsCompleted(int $score, array $metrics, string $pdfPath): void
    {
        $this->update([
            'status' => 'completed',
            'score' => $score,
            'metrics' => $metrics,
            'pdf_path' => $pdfPath,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (! $this->pdf_path) {
            return null;
        }

        return asset('storage/reports/'.basename($this->pdf_path));
    }

    public static function generateIdempotencyKey(string $url, string $strategy): string
    {
        $windowMinutes = (int) config('audits.idempotency_window', 60);
        $windowStart = now()->floorMinutes($windowMinutes)->timestamp;

        return hash('sha256', $url.$strategy.$windowStart);
    }
}
