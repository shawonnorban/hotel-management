<?php

namespace App\Mail;

use App\Models\BookedInfo;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewBookingAlertMail extends Mailable
{
    public function __construct(public BookedInfo $booking, public string $heading)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading.' – #'.$this->booking->booking_number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-alert');
    }
}
