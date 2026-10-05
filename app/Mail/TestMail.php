<?php

namespace App\Mail;

use App\Support\Settings;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Test e-mail from '.Settings::hotelName());
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Your mail settings work. You can now send booking confirmations to your guests.</p>');
    }
}
