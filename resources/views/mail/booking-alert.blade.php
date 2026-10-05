<x-mail::message>
# {{ $heading }}

Booking **#{{ $booking->booking_number }}** for {{ $booking->full_guest_name }} ({{ $booking->customer?->email }}, {{ $booking->customer?->cust_phone }}).

{{ $booking->checkindate->format('d M Y') }} → {{ $booking->checkoutdate->format('d M Y') }} · {{ $booking->total_room }} room(s) · total {{ \App\Support\Money::format($booking->total_price) }} · paid {{ \App\Support\Money::format($booking->paid_amount) }}

<x-mail::button :url="route('admin.reservations.show', $booking->booking_number)">
Open reservation
</x-mail::button>
</x-mail::message>
