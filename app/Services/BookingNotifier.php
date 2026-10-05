<?php

namespace App\Services;

use App\Mail\BookingConfirmationMail;
use App\Mail\NewBookingAlertMail;
use App\Models\BookedInfo;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;

/** E-mails around a booking. Mail problems are logged and never break the booking itself. */
class BookingNotifier
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    /** A booking was made on the website. */
    public function received(BookedInfo $booking): void
    {
        $this->toGuest($booking, 'received');
        $this->toHotel($booking, 'New website booking');
    }

    /** Money arrived for the booking. */
    public function paid(BookedInfo $booking, float $amount): void
    {
        $this->toGuest($booking, 'paid', $amount);
        $this->toHotel($booking, 'Online payment received');
    }

    private function toGuest(BookedInfo $booking, string $kind, ?float $amount = null): void
    {
        $guest = $booking->customer;
        if (! $guest?->email) {
            return;
        }
        rescue(fn () => Mail::to($guest->email)->send(new BookingConfirmationMail($booking, $kind, $amount, $this->invoices->pdf($booking)->output())), report: true);
    }

    private function toHotel(BookedInfo $booking, string $subject): void
    {
        if ($to = Settings::get('email')) {
            rescue(fn () => Mail::to($to)->send(new NewBookingAlertMail($booking, $subject)), report: true);
        }
    }
}
