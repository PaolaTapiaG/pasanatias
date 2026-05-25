<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Factura $factura,
        private string $pdfContent
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura ' . $this->factura->numero_factura,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-pdf',
            with: [
                'factura' => $this->factura,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                $this->factura->numero_factura . '.pdf'
            )->withMime('application/pdf'),
        ];
    }

    public function apiAttachments(): array
    {
        return [
            [
                'filename' => $this->factura->numero_factura . '.pdf',
                'content' => $this->pdfContent,
            ],
        ];
    }
}
