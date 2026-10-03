<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public ?string $messageText = null,
        public ?string $emailSubject = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject ?: 'Báo giá ' . $this->quotation->code . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation');
    }

    public function attachments(): array
    {
        return [];
    }
}
