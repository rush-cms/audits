<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Audit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WebhookFailedNotification extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Audit $audit,
        public readonly int $attempts
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Webhook Failed] Audit {$this->audit->id}",
        );
    }

    public function content(): Content
    {
        $message = <<<TEXT
Webhook delivery permanently failed after {$this->attempts} attempts.

Audit Details:
- ID: {$this->audit->id}
- URL: {$this->audit->url}
- Strategy: {$this->audit->strategy}
- Score: {$this->audit->score}
- Status: {$this->audit->status}
- Created: {$this->audit->created_at}
- Webhook Attempts: {$this->audit->webhook_attempts}

PDF URL: {$this->audit->pdf_url}

You can manually retry the webhook using:
php artisan webhook:retry {$this->audit->id}

---
This is an automated notification from Rush CMS Audits.
TEXT;

        return new Content(
            text: 'emails.webhook-failed',
            with: [
                'message' => $message,
            ]
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
