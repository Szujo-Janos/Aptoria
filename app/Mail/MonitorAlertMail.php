<?php

namespace App\Mail;

use App\Models\MonitorAlertEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitorAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly MonitorAlertEvent $event,
        public readonly array $payload,
    ) {
    }

    public function envelope(): Envelope
    {
        $project = (string) ($this->payload['project'] ?? 'Project');
        $monitor = (string) ($this->payload['monitor'] ?? 'Monitor');
        $status = (string) ($this->payload['status'] ?? 'unknown');

        return new Envelope(
            subject: '[Aptoria] '.$project.' / '.$monitor.' — '.$status,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monitors.alert',
            text: 'emails.monitors.alert-text',
            with: [
                'event' => $this->event,
                'payload' => $this->payload,
            ],
        );
    }
}
