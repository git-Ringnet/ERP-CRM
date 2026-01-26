<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseRequest $purchaseRequest;
    public Supplier $supplier;

    public function __construct(PurchaseRequest $purchaseRequest, Supplier $supplier)
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->supplier = $supplier;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu báo giá #' . $this->purchaseRequest->code . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-request',
            with: [
                'request' => $this->purchaseRequest,
                'supplier' => $this->supplier,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
