<div class="d-flex justify-content-between align-items-start mb-3">
    <div><div class="text-body-secondary small">Booking number</div><div class="h4 mb-0">#{{ $booking->booking_number }}</div></div>
    <span class="pill {{ $booking->status_pill }}">{{ $booking->status_label }}</span>
</div>
<div class="row g-3 mb-3">
    <div class="col-6"><div class="small text-body-secondary">Check-in</div><div class="fw-semibold">{{ $booking->checkindate->format('D, d M Y') }}</div></div>
    <div class="col-6"><div class="small text-body-secondary">Check-out</div><div class="fw-semibold">{{ $booking->checkoutdate->format('D, d M Y') }}</div></div>
    <div class="col-6"><div class="small text-body-secondary">Guest</div><div class="fw-semibold">{{ $booking->full_guest_name }}</div></div>
    <div class="col-6"><div class="small text-body-secondary">Rooms</div><div class="fw-semibold">{{ $booking->total_room }} · {{ $booking->nights }} night(s) · room {{ $booking->room_no }}</div></div>
</div>
<table class="table table-sm mb-0">
    <tr><td>Total</td><td class="text-end fw-bold">{{ \App\Support\Money::format($booking->total_price) }}</td></tr>
    <tr><td>Paid</td><td class="text-end">{{ \App\Support\Money::format($booking->paid_amount) }}</td></tr>
    <tr class="fw-semibold"><td>Balance due</td><td class="text-end">{{ \App\Support\Money::format(max(0, $booking->total_price - $booking->paid_amount)) }}</td></tr>
</table>
@if ($booking->special_request)<p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-chat-left-text me-1"></i>{{ $booking->special_request }}</p>@endif
