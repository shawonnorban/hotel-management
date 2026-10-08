@php
    $roomNames = $roomNames ?? \App\Models\Roomdetails::pluck('roomtype', 'roomid');
    $actions = $actions ?? false;
    $tone = ['0' => 'warning', '1' => 'secondary', '2' => 'primary', '4' => 'info', '5' => 'success'];
@endphp
<div class="card"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
    <thead><tr><th>Booking</th><th>Guest</th><th>Stay</th><th>Rooms</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Balance</th>@if ($actions)<th class="text-end">Actions</th>@endif</tr></thead>
    <tbody>
    @forelse ($bookings as $booking)
        @php($st = (string) $booking->bookingstatus)
        <tr>
            <td><a class="fw-semibold text-decoration-none" href="{{ route('admin.reservations.show', $booking->booking_number) }}">#{{ $booking->booking_number }}</a><div class="small text-body-secondary text-capitalize">{{ $booking->source ?: 'website' }}</div></td>
            <td>{{ $booking->full_guest_name ?: $booking->customer?->full_name }}@if ($booking->customer?->is_vip) <span class="badge text-bg-warning">VIP</span>@endif<div class="small text-body-secondary">{{ $booking->customer?->cust_phone }}</div></td>
            <td class="text-nowrap">{{ $booking->checkindate->format('d M') }} → {{ $booking->checkoutdate->format('d M Y') }}
                <div class="small text-body-secondary">{{ $booking->nights }} night(s)@if (in_array($st, ['0', '2']) && $booking->checkindate->copy()->startOfDay()->gte(today())) · {{ $booking->checkindate->isToday() ? 'arrives today' : 'in '.today()->diffInDays($booking->checkindate->copy()->startOfDay()).' day(s)' }}@endif</div></td>
            <td style="min-width:150px">@foreach ($booking->roomLines() as $rl)<div class="small"><span class="fw-semibold">{{ $roomNames[$rl['room_id']] ?? 'Room' }}</span> <span class="text-body-secondary">× {{ $rl['rooms'] }} · {{ implode(', ', $rl['numbers']) }}</span></div>@endforeach</td>
            <td><span class="badge text-bg-{{ $tone[$st] ?? 'light' }}">{{ $booking->status_label }}</span></td>
            <td class="text-end text-nowrap">{{ \App\Support\Money::format($booking->total_price) }}<div class="small text-body-secondary">paid {{ \App\Support\Money::format($booking->paid_amount) }}</div></td>
            <td class="text-end text-nowrap {{ $booking->balance > 0 && $st !== '1' ? 'text-danger fw-semibold' : 'text-body-secondary' }}">{{ $st === '1' ? '—' : \App\Support\Money::format($booking->balance) }}</td>
            @if ($actions)
            <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.reservations.show', $booking->booking_number) }}" title="Open"><i class="bi bi-eye"></i></a>
                @can('reservations.status')
                    @if ($st === '0')<form method="post" action="{{ route('admin.reservations.confirm', $booking->booking_number) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-primary" title="Confirm"><i class="bi bi-check2-circle"></i></button></form>@endif
                    @if (in_array($st, ['0', '2']) && $booking->checkindate->copy()->startOfDay()->lte(today()))<form method="post" action="{{ route('admin.reservations.check-in', $booking->booking_number) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary" title="Check in"><i class="bi bi-box-arrow-in-right"></i></button></form>@endif
                @endcan
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.reservations.invoice', $booking->booking_number) }}" title="Invoice"><i class="bi bi-file-earmark-pdf"></i></a>
            </td>
            @endif
        </tr>
    @empty
        <tr><td colspan="{{ $actions ? 8 : 7 }}"><div class="empty"><i class="bi bi-calendar-x"></i>No reservations found.</div></td></tr>
    @endforelse
    </tbody>
</table></div></div>
