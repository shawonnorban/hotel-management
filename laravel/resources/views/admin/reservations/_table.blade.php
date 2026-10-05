<div class="table-responsive bg-white border rounded">
<table class="table table-hover mb-0">
    <thead><tr><th>Number</th><th>Guest</th><th>Stay</th><th>Rooms</th><th class="text-end">Total</th><th class="text-end">Paid</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($bookings as $booking)
        <tr>
            <td><a href="{{ route('admin.reservations.show', $booking->booking_number) }}">{{ $booking->booking_number }}</a></td>
            <td>{{ $booking->full_guest_name ?: $booking->customer?->full_name }}</td>
            <td>{{ $booking->checkindate->format('d M Y') }} → {{ $booking->checkoutdate->format('d M Y') }}</td>
            <td>{{ $booking->room_no }}</td>
            <td class="text-end">{{ number_format($booking->total_price, 2) }}</td>
            <td class="text-end">{{ number_format($booking->paid_amount, 2) }}</td>
            <td>{{ $booking->status_label }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">No reservations.</td></tr>
    @endforelse
    </tbody>
</table></div>
