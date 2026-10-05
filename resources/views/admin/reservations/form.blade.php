@extends('layouts.admin')
@section('title', $booking ? 'Edit reservation' : 'New reservation')
@section('content')
@php
    $editing = (bool) $booking;
    $map = $editing ? [
        'room' => (int) explode(',', $booking->roomid)[0], 'checkin' => $booking->checkindate->format('Y-m-d'), 'checkout' => $booking->checkoutdate->format('Y-m-d'),
        'rooms' => $booking->total_room, 'adults' => array_sum(explode(',', $booking->nuofpeople)), 'children' => array_sum(explode(',', (string) $booking->children)),
        'guest_name' => $booking->full_guest_name, 'special' => $booking->special_request, 'promo' => $booking->promocode,
    ] : [];
    $val = fn ($key, $default = null) => old($key, $editing ? ($map[$key] ?? $default) : ($prefill[$key] ?? $default));
@endphp
<a href="{{ $editing ? route('admin.reservations.show', $booking->booking_number) : route('admin.reservations.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $editing ? '#'.$booking->booking_number : 'Reservations' }}</a>
<h1 class="page-title mt-1 mb-4">{{ $editing ? 'Edit reservation #'.$booking->booking_number : 'New reservation' }}</h1>
<form method="post" action="{{ $editing ? route('admin.reservations.update', $booking->booking_number) : route('admin.reservations.store') }}" id="resForm">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            @unless ($editing)
            <div class="card mb-4"><div class="card-header">Guest</div><div class="card-body">
                <div class="mb-3"><label class="form-label small fw-semibold">Existing guest</label>
                    <select name="guest_id" id="guest_id" class="form-select" data-search><option value="">— New guest —</option>@foreach ($guests as $g)<option value="{{ $g->customerid }}" @selected((string) $val('guest_id') === (string) $g->customerid)>{{ trim($g->firstname.' '.$g->lastname) }} · {{ $g->cust_phone }}{{ $g->email ? ' · '.$g->email : '' }}</option>@endforeach</select></div>
                <div class="row g-3" id="newGuest">
                    <div class="col-md-6"><label class="form-label small fw-semibold">First name</label><input name="new_firstname" class="form-control" value="{{ old('new_firstname') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Last name</label><input name="new_lastname" class="form-control" value="{{ old('new_lastname') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="new_phone" class="form-control" value="{{ old('new_phone') }}"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email (optional)</label><input type="email" name="new_email" class="form-control" value="{{ old('new_email') }}"></div>
                </div>
            </div></div>
            @endunless
            <div class="card mb-4"><div class="card-header">Stay</div><div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Room type</label>
                    <select name="room" id="room" class="form-select" required><option value="">— Select —</option>@foreach ($rooms as $r)<option value="{{ $r->roomid }}" @selected((string) $val('room') === (string) $r->roomid)>{{ $r->roomtype }} · {{ \App\Support\Money::format($r->rate) }} · sleeps {{ $r->capacity }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Check-in</label><input name="checkin" id="checkin" class="form-control" data-date value="{{ $val('checkin') }}" required autocomplete="off"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Check-out</label><input name="checkout" id="checkout" class="form-control" data-date value="{{ $val('checkout') }}" required autocomplete="off"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Rooms</label><input type="number" name="rooms" id="rooms" min="1" max="20" class="form-control" value="{{ $val('rooms', 1) }}" required></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Adults</label><input type="number" name="adults" min="1" class="form-control" value="{{ $val('adults', 2) }}" required></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Children</label><input type="number" name="children" min="0" class="form-control" value="{{ $val('children', 0) }}"></div>
                <div class="col-md-3"><label class="form-label small fw-semibold">Promo code</label><input name="promo" id="promo" class="form-control text-uppercase" value="{{ $val('promo') }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Guest name on the booking</label><input name="guest_name" class="form-control" value="{{ $val('guest_name') }}"></div>
                @unless ($editing)<div class="col-md-6"><label class="form-label small fw-semibold">Booking source</label><select name="source" class="form-select">@foreach ($sources as $k => $l)<option value="{{ $k }}" @selected(old('source', 'phone') === $k)>{{ $l }}</option>@endforeach</select></div>@endunless
                <div class="col-12"><label class="form-label small fw-semibold">Special requests</label><textarea name="special" rows="2" class="form-control">{{ $val('special') }}</textarea></div>
            </div></div>
            @unless ($editing)
            <div class="card"><div class="card-header">Deposit (optional)</div><div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Amount received now</label><input type="number" step="0.01" min="0" name="deposit" class="form-control" value="{{ old('deposit') }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Method</label><select name="deposit_method" class="form-select"><option value="">—</option>@foreach ($methods as $m)<option value="{{ $m->payment_method_id }}" @selected((string) old('deposit_method') === (string) $m->payment_method_id)>{{ $m->payment_method }}</option>@endforeach</select></div>
            </div></div>
            @endunless
        </div>
        <div class="col-lg-4">
            <div class="card position-sticky" style="top:90px"><div class="card-header">Price</div><div class="card-body" id="quoteBox"><div class="text-body-secondary small">Choose a room type and dates to see the price and availability.</div></div>
                <div class="card-footer bg-transparent"><button class="btn btn-primary w-100">{{ $editing ? 'Save changes' : 'Create reservation' }}</button></div></div>
        </div>
    </div>
</form>
@push('scripts')
<script>
(function () {
    var box = document.getElementById('quoteBox'), guest = document.getElementById('guest_id'), timer;
    var fields = ['room', 'checkin', 'checkout', 'rooms', 'promo'];
    function money(v) { return Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function refresh() {
        var q = {}; fields.forEach(function (f) { q[f] = document.getElementById(f).value; });
        if (!q.room || !q.checkin || !q.checkout || q.checkout <= q.checkin) { return; }
        @if ($editing) q.booking = @json($booking->booking_number); @endif
        fetch(@json(route('admin.reservations.quote')) + '?' + new URLSearchParams(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) {
                var h = '<table class="table table-sm mb-2"><tr><td>' + money(d.rate) + ' × ' + d.nights + ' night(s) × ' + d.rooms + ' room(s)</td><td class="text-end">' + money(d.subtotal) + '</td></tr>';
                if (d.discount > 0) { h += '<tr class="text-success"><td>Discount</td><td class="text-end">−' + money(d.discount) + '</td></tr>'; }
                h += '<tr><td>Taxes</td><td class="text-end">' + money(d.tax) + '</td></tr><tr><td>Service charge</td><td class="text-end">' + money(d.service) + '</td></tr><tr class="fw-bold"><td>Total</td><td class="text-end">' + money(d.total) + '</td></tr></table>';
                h += d.available >= d.rooms ? '<div class="small text-success"><i class="bi bi-check-circle me-1"></i>' + d.available + ' room(s) available</div>' : '<div class="small text-danger"><i class="bi bi-x-circle me-1"></i>Only ' + d.available + ' room(s) available</div>';
                if (d.promo_valid === false) { h += '<div class="small text-danger mt-1">Promo code not valid.</div>'; }
                if (d.promo_valid === true) { h += '<div class="small text-success mt-1">Promo code applied (' + d.promo + '%).</div>'; }
                box.innerHTML = h;
            }).catch(function () { box.innerHTML = '<div class="small text-danger">Could not load the price.</div>'; });
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 250); }
    fields.forEach(function (f) { var el = document.getElementById(f); el.addEventListener('input', schedule); el.addEventListener('change', schedule); });
    window.addEventListener('DOMContentLoaded', function () {
        ['checkin', 'checkout'].forEach(function (f) { var el = document.getElementById(f); if (el._flatpickr) { el._flatpickr.config.onChange.push(schedule); } });
        refresh();
        if (guest) {
            var toggle = function () { document.getElementById('newGuest').style.display = guest.value ? 'none' : ''; };
            guest.addEventListener('change', toggle); toggle();
        }
    });
})();
</script>
@endpush
@endsection
