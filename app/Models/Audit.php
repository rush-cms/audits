<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $idempotency_key
 * @property string $url
 * @property string $strategy
 * @property string $lang
 * @property string $status
 * @property int|null $score
 * @property array<string, string>|null $metrics
 * @property string|null $pdf_path
 * @property string|null $error_message
 * @property array<string, mixed>|null $pagespeed_data
 * @property array<string, mixed>|null $screenshots_data
 * @property array<string, mixed>|null $processing_steps
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
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
        'pagespeed_data',
        'screenshots_data',
        'processing_steps',
        'last_attempt_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'pagespeed_data' => 'array',
            'screenshots_data' => 'array',
            'processing_steps' => 'array',
            'last_attempt_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'last_attempt_at' => now(),
        ]);
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
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordStep(string $stepName, string $status, ?array $data = null): void
    {
        $steps = $this->processing_steps ?? [];

        $step = [
            'name' => $stepName,
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($data) {
            $step['data'] = $data;
        }

        if ($status === 'failed' && isset($data['error'])) {
            $step['error'] = $data['error'];
        }

        $steps[] = $step;

        $this->update(['processing_steps' => $steps]);
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
        return hash('sha256', $url.$strategy.microtime(true).random_bytes(16));
    }
}
