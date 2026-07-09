<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SaleInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Sale $sale;
    public ?string $attachmentPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Sale $sale, ?string $attachmentPath = null)
    {
        $this->sale = $sale;
        $this->attachmentPath = $attachmentPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hóa đơn bán hàng #' . $this->sale->code,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.sale-invoice',
            with: [
                'sale' => $this->sale,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->attachmentPath && file_exists(storage_path('app/public/' . $this->attachmentPath))) {
            return [
                Attachment::fromPath(storage_path('app/public/' . $this->attachmentPath))
                    ->as(basename($this->attachmentPath))
            ];
        }
        return [];
    }
}
