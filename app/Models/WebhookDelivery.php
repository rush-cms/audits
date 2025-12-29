<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $audit_id
 * @property int $attempt_number
 * @property string $url
 * @property array<string, mixed> $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int|null $response_time_ms
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property Audit $audit
 */
final class WebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'attempt_number',
        'url',
        'payload',
        'response_status',
        'response_body',
        'response_time_ms',
        'error_message',
        'delivered_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function wasSuccessful(): bool
    {
        return $this->response_status !== null
            && $this->response_status >= 200
            && $this->response_status < 300;
    }
}
