<table class="table table-sm">
    <tr><th style="width:35%">Booking number</th><td>{{ $booking->booking_number }}</td></tr>
    <tr><th>Status</th><td><span class="badge text-bg-secondary">{{ $booking->status_label }}</span></td></tr>
    <tr><th>Guest</th><td>{{ $booking->full_guest_name }}</td></tr>
    <tr><th>Stay</th><td>{{ $booking->checkindate->format('d M Y') }} → {{ $booking->checkoutdate->format('d M Y') }} ({{ $booking->nights }} night(s))</td></tr>
    <tr><th>Rooms</th><td>{{ $booking->total_room }} · room no. {{ $booking->room_no }}</td></tr>
    <tr><th>Total</th><td>{{ number_format($booking->total_price, 2) }}</td></tr>
    <tr><th>Paid</th><td>{{ number_format($booking->paid_amount, 2) }}</td></tr>
    @if ($booking->special_request)<tr><th>Requests</th><td>{{ $booking->special_request }}</td></tr>@endif
</table>
