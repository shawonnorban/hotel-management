<?php

namespace App\Mail;

use App\Models\BookedInfo;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmationMail extends Mailable
{
    public function __construct(public BookedInfo $booking, public string $kind, public ?float $amount, private string $invoicePdf)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'paid' => 'Payment received – booking #'.$this->booking->booking_number,
            default => 'We received your booking #'.$this->booking->booking_number,
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-confirmation');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->invoicePdf, 'invoice-'.$this->booking->booking_number.'.pdf')->withMime('application/pdf')];
    }
}
