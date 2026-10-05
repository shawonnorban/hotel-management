<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Booking</th><th>Guest</th><th>Stay</th><th>Rooms</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($bookings as $booking)
        <tr>
            <td><a class="fw-semibold text-decoration-none" href="{{ route('admin.reservations.show', $booking->booking_number) }}">#{{ $booking->booking_number }}</a></td>
            <td>{{ $booking->full_guest_name ?: $booking->customer?->full_name }}<div class="small text-body-secondary">{{ $booking->customer?->cust_phone }}</div></td>
            <td class="text-nowrap">{{ $booking->checkindate->format('d M') }} → {{ $booking->checkoutdate->format('d M Y') }}</td>
            <td>{{ $booking->room_no }}</td>
            <td class="text-end">{{ \App\Support\Money::format($booking->total_price) }}</td>
            <td class="text-end {{ $booking->balance > 0 && (string) $booking->bookingstatus !== '1' ? 'text-danger fw-semibold' : 'text-body-secondary' }}">{{ (string) $booking->bookingstatus === '1' ? '—' : \App\Support\Money::format($booking->balance) }}</td>
            <td><span class="pill {{ $booking->status_pill }}">{{ $booking->status_label }}</span></td>
        </tr>
    @empty
        <tr><td colspan="7"><div class="empty"><i class="bi bi-calendar-x"></i>No reservations found.</div></td></tr>
    @endforelse
    </tbody>
</table></div></div>
