<?php

namespace App\Services;

use App\Models\BookedInfo;
use App\Models\Roomdetails;
use App\Support\Money;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /** Invoice lines from the stored breakdown (falls back to the total for older bookings). */
    public function lines(BookedInfo $booking, bool $pdf = false): array
    {
        $money = fn ($v) => $pdf ? Money::pdf($v) : Money::format($v);

        if ($booking->subtotal === null) {
            return [['label' => 'Accommodation ('.$booking->nights.' night(s), '.$booking->total_room.' room(s))', 'amount' => $money($booking->total_price)]];
        }

        // One line per room type: "Deluxe Double × 2 room(s) × 3 night(s)".
        $names = Roomdetails::pluck('roomtype', 'roomid');
        $lines = [];
        foreach ($booking->roomLines() as $rl) {
            $lines[] = ['label' => ($names[$rl['room_id']] ?? 'Room').' × '.$rl['rooms'].' room(s) × '.$booking->nights.' night(s)', 'amount' => $money(round($rl['rate'] * $rl['rooms'] * $booking->nights, 2))];
        }
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
            'lines' => $this->lines($booking, true),
            'hotel' => Settings::hotelName(),
            'money' => fn ($v) => Money::pdf($v),
        ])->setPaper('a4');
    }
}
