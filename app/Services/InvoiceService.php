<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Support\Money;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /** Invoice lines from the stored breakdown (falls back to the total for older bookings). */
    public function lines(BookedInfo $booking): array
    {
        $money = fn ($v) => Money::format($v);

        if ($booking->subtotal === null) {
            return [['label' => 'Accommodation ('.$booking->nights.' night(s), '.$booking->total_room.' room(s))', 'amount' => $money($booking->total_price)]];
        }

        $lines = [['label' => 'Accommodation ('.$booking->nights.' night(s), '.$booking->total_room.' room(s))', 'amount' => $money($booking->subtotal)]];
        if ((float) $booking->discount_amount > 0) {
            $lines[] = ['label' => 'Discount'.($booking->promocode ? ' (promo '.$booking->promocode.')' : ''), 'amount' => '−'.$money($booking->discount_amount)];
        }
        if ((float) $booking->tax_amount > 0) {
            $lines[] = ['label' => 'Taxes', 'amount' => $money($booking->tax_amount)];
        }
        if ((float) $booking->service_amount > 0) {
            $lines[] = ['label' => 'Service charge', 'amount' => $money($booking->service_amount)];
        }

        return $lines;
    }

    public function pdf(BookedInfo $booking): \Barryvdh\DomPDF\PDF
    {
        $booking->loadMissing('customer', 'payments');

        return Pdf::loadView('pdf.invoice', [
            'booking' => $booking,
            'lines' => $this->lines($booking),
            'hotel' => Settings::hotelName(),
            'money' => fn ($v) => Money::format($v),
        ])->setPaper('a4');
    }
}
