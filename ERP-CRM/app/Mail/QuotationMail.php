<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
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
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
        public ?string $senderName = null,
        public bool $attachExcel = true,
        public ?string $excelBinary = null,
        public ?string $excelFilename = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];
        if (!empty($this->replyToEmail)) {
            $replyTo[] = new Address($this->replyToEmail, $this->replyToName ?? $this->replyToEmail);
        }

        $from = null;
        if (!empty($this->senderName)) {
            $currentFromAddress = config('mail.from.address') ?: 'noreply@minierp.com';
            $from = new Address($currentFromAddress, $this->senderName . ' via ' . config('app.name', 'ERP-CRM'));
        }

        return new Envelope(
            from: $from,
            replyTo: $replyTo,
            subject: $this->emailSubject ?: 'Báo giá ' . $this->quotation->code . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation',
            with: [
                'quotation' => $this->quotation,
                'messageText' => $this->messageText,
                'salesName' => $this->replyToName,
                'salesEmail' => $this->replyToEmail,
                'hasAttachment' => ($this->attachExcel && !empty($this->excelBinary)),
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        if ($this->attachExcel && !empty($this->excelBinary) && !empty($this->excelFilename)) {
            $attachments[] = Attachment::fromData(
                fn () => $this->excelBinary,
                $this->excelFilename
            )->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }

        return $attachments;
    }
}
