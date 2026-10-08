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
<form method="post" action="{{ $editing ? route('admin.reservations.update', $booking->booking_number) : route('admin.reservations.store') }}" id="resForm" enctype="multipart/form-data">
    @csrf @if ($editing) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            @unless ($editing)
            <div class="card mb-4"><div class="card-header">Guest</div><div class="card-body">
                <div class="mb-3"><label class="form-label small fw-semibold">Existing guest</label>
                    <select name="guest_id" id="guest_id" class="form-select" data-search><option value="">— New guest —</option>@foreach ($guests as $g)<option value="{{ $g->customerid }}" @selected((string) $val('guest_id') === (string) $g->customerid)>{{ trim($g->firstname.' '.$g->lastname) }} · {{ $g->cust_phone }}{{ $g->email ? ' · '.$g->email : '' }}</option>@endforeach</select></div>
                <div class="row g-3" id="newGuest">
                    <div class="col-md-2"><label class="form-label small fw-semibold">Title</label><select name="new_title" class="form-select"><option value="">—</option><option>Mr</option><option>Mrs</option><option>Ms</option><option>Dr</option></select></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold">First name</label><input type="text" name="new_firstname" class="form-control" value="{{ old('new_firstname') }}" ></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold">Last name</label><input type="text" name="new_lastname" class="form-control" value="{{ old('new_lastname') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Phone</label><input type="text" name="new_phone" class="form-control" value="{{ old('new_phone') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Email (optional)</label><input type="email" name="new_email" class="form-control" value="{{ old('new_email') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Gender</label><select name="new_gender" class="form-select"><option value="">—</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Father / spouse name</label><input type="text" name="new_fathername" class="form-control" value="{{ old('new_fathername') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Occupation</label><input type="text" name="new_profession" class="form-control" value="{{ old('new_profession') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Nationality</label><input type="text" name="new_nationality" class="form-control" value="{{ old('new_nationality') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Date of birth</label><input type="text" name="new_dob" class="form-control" value="{{ old('new_dob') }}" data-date autocomplete="off"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Anniversary</label><input type="text" name="new_anniversary" class="form-control" value="{{ old('new_anniversary') }}" data-date autocomplete="off"></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="new_is_vip" value="1" id="new_is_vip"><label class="form-check-label" for="new_is_vip">VIP guest</label></div></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Country</label><input type="text" name="new_country" class="form-control" value="{{ old('new_country') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">State</label><input type="text" name="new_state" class="form-control" value="{{ old('new_state') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">City</label><input type="text" name="new_city" class="form-control" value="{{ old('new_city') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Zip code</label><input type="text" name="new_zipcode" class="form-control" value="{{ old('new_zipcode') }}" ></div>
                    <div class="col-md-8"><label class="form-label small fw-semibold">Address</label><input type="text" name="new_address" class="form-control" value="{{ old('new_address') }}" ></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">ID type</label><select name="new_id_type" class="form-select"><option value="">—</option><option>NID</option><option>Passport</option><option>Driving licence</option><option>Other</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">ID number</label><input type="text" name="new_id_no" class="form-control" value="{{ old('new_id_no') }}" ></div>
                    <div class="col-md-4"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">ID photo — front</label><input type="file" name="new_front" class="form-control" accept="image/*"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">ID photo — back</label><input type="file" name="new_back" class="form-control" accept="image/*"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Guest photo</label><input type="file" name="new_photo" class="form-control" accept="image/*"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Notes</label><textarea name="new_comments" class="form-control" rows="2">{{ old('new_comments') }}</textarea></div>
                </div>
            </div></div>
            @endunless
            @unless ($editing)
            <div class="card mb-4"><div class="card-header d-flex align-items-center">Additional guests <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="addGuestRow"><i class="bi bi-plus-lg me-1"></i>Add guest</button></div>
                <div class="card-body" id="guestRows"><p class="small text-body-secondary mb-0" id="guestHint">Add everyone staying besides the main guest, with their ID and photo.</p></div></div>
            <template id="guestTpl"><div class="border rounded p-3 mb-3 guest-row"><div class="row g-2">
                <div class="col-md-5"><input name="guests[__i__][name]" class="form-control" placeholder="Full name"></div>
                <div class="col-md-3"><select name="guests[__i__][gender]" class="form-select"><option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option></select></div>
                <div class="col-md-3"><input name="guests[__i__][mobile]" class="form-control" placeholder="Mobile"></div>
                <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger rm-guest"><i class="bi bi-trash"></i></button></div>
                <div class="col-md-5"><input type="email" name="guests[__i__][email]" class="form-control" placeholder="Email"></div>
                <div class="col-md-3"><select name="guests[__i__][id_type]" class="form-select"><option value="">ID type</option><option>NID</option><option>Passport</option><option>Driving licence</option><option>Other</option></select></div>
                <div class="col-md-4"><input name="guests[__i__][id_no]" class="form-control" placeholder="ID number"></div>
                <div class="col-md-4"><label class="small text-body-secondary">ID front</label><input type="file" name="guests[__i__][front]" class="form-control" accept="image/*"></div>
                <div class="col-md-4"><label class="small text-body-secondary">ID back</label><input type="file" name="guests[__i__][back]" class="form-control" accept="image/*"></div>
                <div class="col-md-4"><label class="small text-body-secondary">Guest photo</label><input type="file" name="guests[__i__][photo]" class="form-control" accept="image/*"></div>
            </div></div></template>
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
    var rows = document.getElementById('guestRows'), tpl = document.getElementById('guestTpl'), gi = 0;
    if (rows && tpl) {
        document.getElementById('addGuestRow').addEventListener('click', function () {
            var h = document.getElementById('guestHint'); if (h) h.remove();
            rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, gi++));
        });
        rows.addEventListener('click', function (e) { var b = e.target.closest('.rm-guest'); if (b) b.closest('.guest-row').remove(); });
    }
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
