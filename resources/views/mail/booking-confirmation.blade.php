<x-mail::message>
# Hello {{ $booking->customer?->firstname ?? 'guest' }},

@if ($kind === 'paid')
We have received your payment of **{{ \App\Support\Money::format($amount) }}** for booking **#{{ $booking->booking_number }}**. Your booking is confirmed.
@else
Thank you for booking with {{ \App\Support\Settings::hotelName() }}. We have received your booking **#{{ $booking->booking_number }}**.
@endif

<x-mail::table>
| | |
|:--|:--|
| Check-in | {{ $booking->checkindate->format('D, d M Y') }} |
| Check-out | {{ $booking->checkoutdate->format('D, d M Y') }} |
| Nights | {{ $booking->nights }} |
| Rooms | {{ $booking->total_room }} (room {{ $booking->room_no }}) |
| Total | {{ \App\Support\Money::format($booking->total_price) }} |
| Paid | {{ \App\Support\Money::format($booking->paid_amount) }} |
| Balance due | {{ \App\Support\Money::format($booking->balance) }} |
</x-mail::table>

<x-mail::button :url="route('booking.show', $booking->booking_number)">
View your booking
</x-mail::button>

Your invoice is attached. If you have any questions, just reply to this e-mail.

Thanks,<br>
{{ \App\Support\Settings::hotelName() }}
</x-mail::message>
