@extends('layouts.admin')
@section('title', $booking ? 'Edit reservation' : 'New reservation')
@section('content')
@php
    $editing = (bool) $booking;
    $map = $editing ? [
        'checkin' => $booking->checkindate->format('Y-m-d'), 'checkout' => $booking->checkoutdate->format('Y-m-d'),
        'guest_name' => $booking->full_guest_name, 'special' => $booking->special_request, 'promo' => $booking->promocode,
    ] : [];
    $val = fn ($key, $default = null) => old($key, $editing ? ($map[$key] ?? $default) : ($prefill[$key] ?? $default));
    // One row per room type: from the failed submission, the booking being edited, a prefilled room, or an empty first row.
    if (old('lines')) {
        $lineRows = array_values(old('lines'));
    } elseif ($editing) {
        $lineRows = array_map(fn ($l) => ['room' => $l['room_id'], 'rooms' => $l['rooms'], 'adults' => $l['adults'], 'children' => $l['children'], 'numbers' => $l['numbers']], $booking->roomLines());
    } else {
        $lineRows = [['room' => $prefill['room'] ?? '', 'rooms' => 1, 'adults' => 2, 'children' => 0, 'numbers' => []]];
    }
@endphp
<a href="{{ $editing ? route('admin.reservations.show', $booking->booking_number) : route('admin.reservations.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $editing ? '#'.$booking->booking_number : 'Reservations' }}</a>
<h1 class="page-title mt-1 mb-4">{{ $editing ? 'Edit reservation #'.$booking->booking_number : 'New reservation' }}</h1>
<form method="post" action="{{ $editing ? route('admin.reservations.update', $booking->booking_number) : route('admin.reservations.store') }}" id="resForm" enctype="multipart/form-data">
    @csrf @if ($editing) @method('PUT') @endif
    @error('booking')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @unless ($editing)
    <div class="card mb-4"><div class="card-header fw-semibold"><span class="px-2 py-1 rounded text-white" style="background:var(--brand,#0f766e)">Reservation Details</span>
        <a href="{{ route('admin.reservations.index') }}" class="btn btn-sm btn-primary float-end"><i class="bi bi-list-ul me-1"></i>Check In List</a></div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label small fw-semibold">Check In <span class="text-danger">*</span></label><input name="checkin" id="checkin" class="form-control" data-date value="{{ $val('checkin', $editing ? null : today()->toDateString()) }}" required autocomplete="off"><div class="form-text" id="inTime"></div></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Check Out <span class="text-danger">*</span></label><input name="checkout" id="checkout" class="form-control" data-date value="{{ $val('checkout', $editing ? null : today()->addDay()->toDateString()) }}" required autocomplete="off"><div class="form-text" id="outTime"></div></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Arrival From</label><input name="arrival_from" class="form-control" value="{{ old('arrival_from') }}" placeholder="Arrival from"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Booking Type</label><select name="source" class="form-select">@foreach ($sources as $k => $l)<option value="{{ $k }}" @selected(old('source', 'phone') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Choose Booking Reference</label><select name="booking_source" class="form-select"><option value="">— Choose —</option>@foreach ($references as $r)<option @selected(old('booking_source') === $r)>{{ $r }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Booking Reference No</label><input name="booking_source_no" class="form-control" value="{{ old('booking_source_no') }}" placeholder="Booking reference no."></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Purpose of Visit</label><input name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="Purpose of visit"></div>
            <div class="col-md-3"><label class="form-label small fw-semibold">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="Remarks"></div>
        </div></div>
    @endunless

    <div class="card mb-4"><div class="card-header fw-semibold">{{ $editing ? 'Stay' : 'Room Details' }}</div><div class="card-body">
        <div id="roomRows">
            @foreach ($lineRows as $i => $row)
                @include('admin.reservations._room_row', ['i' => $i, 'row' => $row, 'rooms' => $rooms])
            @endforeach
        </div>
        <template id="roomTpl">@include('admin.reservations._room_row', ['i' => '__i__', 'row' => ['room' => '', 'rooms' => 1, 'adults' => 2, 'children' => 0, 'numbers' => []], 'rooms' => $rooms])</template>
        <div class="mb-3"><button type="button" class="btn btn-outline-primary btn-sm" id="addRoom"><i class="bi bi-plus-lg me-1"></i>Add another room type</button> <span class="small text-body-secondary ms-2">A guest can book several room types in one reservation.</span></div>
        <div class="border rounded mb-3"><div class="p-3 row g-3">
            <div class="col-md-3"><label class="form-label small fw-semibold">Promo code</label><input name="promo" id="promo" class="form-control text-uppercase" value="{{ $val('promo') }}"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Guest name on the booking</label><input name="guest_name" class="form-control" value="{{ $val('guest_name') }}"></div>
            <div class="col-md-5"><label class="form-label small fw-semibold">Special requests</label><input name="special" class="form-control" value="{{ $val('special') }}"></div>
            @error('booking')<div class="col-12 text-danger small">{{ $message }}</div>@enderror
        </div></div>

        <div class="row g-3">
            @unless ($editing)
            <div class="col-lg-6"><div class="border rounded h-100"><div class="px-3 py-2 border-bottom d-flex align-items-center"><span class="small fw-semibold">Customer Info</span>
                <div class="dropdown ms-auto"><button type="button" class="btn btn-primary btn-sm" data-bs-toggle="dropdown" aria-label="Add customer"><i class="bi bi-plus-lg"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end"><li><button type="button" class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#newCustomerModal">New Customer</button></li><li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#oldCustomerModal">Old Customer</button></li></ul></div></div>
                <table class="table table-sm mb-0"><thead><tr><th style="width:50px">SL</th><th>Name</th><th>Mobile No.</th><th class="text-end">Action</th></tr></thead><tbody id="custRows"><tr id="custEmpty"><td colspan="4" class="text-body-secondary p-3">Add the main guest with the + button.</td></tr></tbody></table>
                <input type="hidden" name="guest_id" id="guest_id" value="{{ old('guest_id', $prefill['guest'] ?? '') }}">
                @error('new_firstname')<div class="text-danger small px-3 pb-2">Add the main guest (new or old customer).</div>@enderror @error('new_phone')<div class="text-danger small px-3 pb-2">{{ $message }}</div>@enderror
            </div></div>
            @endunless
            <div class="col-lg-{{ $editing ? 12 : 6 }}"><div class="border rounded h-100"><div class="px-3 py-2 border-bottom small fw-semibold">Rent Info</div>
                <div class="p-3 row g-3"><div class="col-md-4"><label class="form-label small">Check In</label><input class="form-control" id="rentIn" readonly></div><div class="col-md-4"><label class="form-label small">Check Out</label><input class="form-control" id="rentOut" readonly></div><div class="col-md-4"><label class="form-label small">Rent</label><input class="form-control" id="rentAmt" readonly></div>
                @unless ($editing)<div class="col-12"><label class="form-label small fw-semibold">Complementary</label><select name="complementary[]" class="form-select" multiple data-search>@foreach ($complementary as $c)<option @selected(in_array($c, (array) old('complementary', [])))>{{ $c }}</option>@endforeach</select></div>@endunless</div></div></div>
        </div>
    </div></div>

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

    <div class="row g-4 mb-4">
        @unless ($editing)
        <div class="col-lg-4"><div class="card h-100"><div class="card-header">Payments Details</div><div class="card-body row g-3">
            <div class="col-12"><label class="form-label small fw-semibold">Discount Reason</label><input name="discount_reason" class="form-control" value="{{ old('discount_reason') }}" placeholder="Discount reason">@error('discount_reason')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-6"><label class="form-label small fw-semibold">Discount (Max-100%)</label><input type="number" step="0.01" min="0" max="100" name="discount_percent" id="discount_percent" class="form-control" value="{{ old('discount_percent') }}"></div>
            <div class="col-6"><label class="form-label small fw-semibold">Discount amount</label><input id="discountAmt" class="form-control" readonly></div>
            <div class="col-6"><label class="form-label small fw-semibold">Commission (%)</label><input type="number" step="0.01" min="0" max="100" name="commission_percent" id="commission_percent" class="form-control" value="{{ old('commission_percent') }}"></div>
            <div class="col-6"><label class="form-label small fw-semibold">Commission Amount</label><input id="commissionAmt" class="form-control" readonly></div>
        </div></div></div>
        @endunless
        <div class="col-lg-{{ $editing ? 12 : 4 }}"><div class="card h-100"><div class="card-header">Billing Details</div><div class="card-body" id="quoteBox"><div class="text-body-secondary small">Choose a room type and dates to see the price and availability.</div></div></div></div>
        @unless ($editing)
        <div class="col-lg-4"><div class="card h-100"><div class="card-header">Advance Details</div><div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label small fw-semibold">Payment Mode</label><select name="deposit_method" class="form-select"><option value="">Choose Payment Mode</option>@foreach ($methods as $m)<option value="{{ $m->payment_method_id }}" @selected((string) old('deposit_method') === (string) $m->payment_method_id)>{{ $m->payment_method }}</option>@endforeach</select>@error('deposit_method')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Advance Amount</label><input type="number" step="0.01" min="0" name="deposit" id="deposit" class="form-control" value="{{ old('deposit') }}">@error('deposit')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label small fw-semibold">Advance Remarks</label><input name="advance_remarks" class="form-control" value="{{ old('advance_remarks') }}" placeholder="Advance remarks"></div>
            <div class="col-12 small" id="advanceHint">@if ($advancePercent > 0)<span class="text-body-secondary">This hotel confirms a booking once {{ rtrim(rtrim(number_format($advancePercent, 2), '0'), '.') }}% is paid in advance.</span>@endif</div>
        </div></div></div>
        @endunless
    </div>
    <div class="text-end mb-5"><button class="btn btn-primary btn-lg px-5">{{ $editing ? 'Save changes' : 'Save reservation' }}</button></div>
    @unless ($editing)
<div class="modal fade" id="newCustomerModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">New customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3" id="newGuest">
        <div class="col-md-2"><label class="form-label small fw-semibold">Title</label><select name="new_title" class="form-select"><option value="">—</option><option>Mr</option><option>Mrs</option><option>Ms</option><option>Dr</option></select></div><div class="col-md-5"><label class="form-label small fw-semibold">First name</label><input type="text" name="new_firstname" class="form-control" value="{{ old('new_firstname') }}" data-ng="first"></div><div class="col-md-5"><label class="form-label small fw-semibold">Last name</label><input type="text" name="new_lastname" class="form-control" value="{{ old('new_lastname') }}" ></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Mobile no.</label><input type="text" name="new_phone" class="form-control" value="{{ old('new_phone') }}" data-ng="phone"></div><div class="col-md-4"><label class="form-label small fw-semibold">Email (optional)</label><input type="email" name="new_email" class="form-control" value="{{ old('new_email') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">Gender</label><select name="new_gender" class="form-select"><option value="">—</option><option>Male</option><option>Female</option><option>Other</option></select></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Father / spouse name</label><input type="text" name="new_fathername" class="form-control" value="{{ old('new_fathername') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">Occupation</label><input type="text" name="new_profession" class="form-control" value="{{ old('new_profession') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">Nationality</label><input type="text" name="new_nationality" class="form-control" value="{{ old('new_nationality') }}" ></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Date of birth</label><input type="text" name="new_dob" class="form-control" value="{{ old('new_dob') }}" data-date autocomplete="off"></div><div class="col-md-4"><label class="form-label small fw-semibold">Anniversary</label><input type="text" name="new_anniversary" class="form-control" value="{{ old('new_anniversary') }}" data-date autocomplete="off"></div><div class="col-md-4 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="new_is_vip" value="1" id="new_is_vip"><label class="form-check-label" for="new_is_vip">VIP guest</label></div></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Country</label><input type="text" name="new_country" class="form-control" value="{{ old('new_country') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">State</label><input type="text" name="new_state" class="form-control" value="{{ old('new_state') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">City</label><input type="text" name="new_city" class="form-control" value="{{ old('new_city') }}" ></div><div class="col-md-4"><label class="form-label small fw-semibold">Zip code</label><input type="text" name="new_zipcode" class="form-control" value="{{ old('new_zipcode') }}" ></div><div class="col-md-8"><label class="form-label small fw-semibold">Address</label><input type="text" name="new_address" class="form-control" value="{{ old('new_address') }}" ></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">ID type</label><select name="new_id_type" class="form-select"><option value="">—</option><option>NID</option><option>Passport</option><option>Driving licence</option><option>Other</option></select></div><div class="col-md-4"><label class="form-label small fw-semibold">ID number</label><input type="text" name="new_id_no" class="form-control" value="{{ old('new_id_no') }}" ></div><div class="col-md-4"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">ID photo — front</label><input type="file" name="new_front" class="form-control" accept="image/*"></div><div class="col-md-4"><label class="form-label small fw-semibold">ID photo — back</label><input type="file" name="new_back" class="form-control" accept="image/*"></div><div class="col-md-4"><label class="form-label small fw-semibold">Guest photo</label><input type="file" name="new_photo" class="form-control" accept="image/*"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Notes</label><textarea name="new_comments" class="form-control" rows="2">{{ old('new_comments') }}</textarea></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" id="newCustomerAdd">Add</button></div>
</div></div></div>
<div class="modal fade" id="oldCustomerModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Old customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label small fw-semibold">Mobile no., name or email</label><input id="oldSearch" class="form-control" autocomplete="off" placeholder="Type at least 2 characters">
        <div class="list-group mt-3" id="oldResults"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
    @endunless
</form>
@push('scripts')
<script>
(function () {
    function $(id) { return document.getElementById(id); }
    var money = window.fmtMoney;
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

    // Additional guests
    var rows = $('guestRows'), tpl = $('guestTpl'), gi = 0;
    if (rows && tpl) {
        $('addGuestRow').addEventListener('click', function () { var h = $('guestHint'); if (h) h.remove(); rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, gi++)); });
        rows.addEventListener('click', function (e) { var b = e.target.closest('.rm-guest'); if (b) b.closest('.guest-row').remove(); });
    }

    // Room rows ------------------------------------------------------------
    var roomRows = $('roomRows'), roomTpl = $('roomTpl'), rowSeq = roomRows.querySelectorAll('.room-row').length + 100;
    var timer, box = $('quoteBox');
    var base = ['checkin', 'checkout', 'promo', 'discount_percent', 'commission_percent'].filter(function (f) { return $(f); });
    function rowEls() { return Array.prototype.slice.call(roomRows.querySelectorAll('.room-row')); }
    function ticked(row) { return Array.prototype.slice.call(row.querySelectorAll('.room-numbers input:checked')); }
    function syncTypeOptions() {
        var used = rowEls().map(function (r) { return r.querySelector('.room-type').value; }).filter(Boolean);
        rowEls().forEach(function (r) {
            var mine = r.querySelector('.room-type').value;
            Array.prototype.forEach.call(r.querySelectorAll('.room-type option'), function (o) { o.disabled = o.value !== '' && o.value !== mine && used.indexOf(o.value) >= 0; });
        });
        $('addRoom').disabled = used.length >= roomRows.querySelector('.room-type').options.length - 1 && rowEls().every(function (r) { return r.querySelector('.room-type').value; });
    }
    function query() {
        var q = new URLSearchParams(), n = 0;
        base.forEach(function (f) { q.set(f, $(f).value); });
        rowEls().forEach(function (r) {
            var type = r.querySelector('.room-type').value; if (!type) { return; }
            q.set('lines[' + n + '][room]', type);
            q.set('lines[' + n + '][rooms]', r.querySelector('.room-count').value || 1);
            q.set('lines[' + n + '][adults]', r.querySelector('.room-adults').value || 1);
            q.set('lines[' + n + '][children]', r.querySelector('.room-children').value || 0);
            ticked(r).forEach(function (c, k) { q.set('lines[' + n + '][numbers][' + k + ']', c.value); });
            n++;
        });
        @if ($editing) q.set('booking', @json($booking->booking_number)); @endif
        return n ? q : null;
    }
    function renderNumbers(row, info) {
        var box2 = row.querySelector('.room-numbers'), keep = ticked(row).map(function (c) { return c.value; });
        var idx = row.dataset.i;
        box2.innerHTML = info.numbers.length ? info.numbers.map(function (n) { return '<label class="btn btn-sm btn-outline-secondary mb-0"><input type="checkbox" class="form-check-input me-1" name="lines[' + idx + '][numbers][]" value="' + esc(n) + '"' + (keep.indexOf(String(n)) >= 0 ? ' checked' : '') + '>' + esc(n) + '</label>'; }).join('') : '<span class="small text-danger">No free rooms of this type for those dates.</span>';
        var count = parseInt(row.querySelector('.room-count').value || 1, 10), av = row.querySelector('.room-avail'), party = parseInt(row.querySelector('.room-adults').value || 0, 10) + parseInt(row.querySelector('.room-children').value || 0, 10);
        var msgs = [];
        msgs.push(info.available >= count ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + info.available + ' room(s) free</span>' : '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Only ' + info.available + ' room(s) free, you asked for ' + count + '</span>');
        if (party > info.capacity) { msgs.push('<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' + party + ' guests do not fit ' + count + ' room(s) (max ' + info.capacity + ')</span>'); }
        av.innerHTML = msgs.join(' · ');
    }
    function refresh() {
        var q = query();
        if (!q || !$('checkin').value || !$('checkout').value || $('checkout').value <= $('checkin').value) { box.innerHTML = '<div class="text-body-secondary small">Choose a room type and dates to see the price and availability.</div>'; return; }
        fetch(@json(route('admin.reservations.quote')) + '?' + q.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) {
                var h = '<table class="table table-sm mb-0">';
                d.lines.forEach(function (l) { h += '<tr><td>' + esc(l.room) + '<div class="small text-body-secondary">' + money(l.rate) + ' × ' + l.nights + ' night(s) × ' + l.rooms + ' room(s)</div></td><td class="text-end">' + money(l.subtotal) + '</td></tr>'; });
                if (d.lines.length > 1) { h += '<tr class="fw-semibold"><td>Booking Charge</td><td class="text-end">' + money(d.subtotal) + '</td></tr>'; }
                if (d.discount > 0) { h += '<tr class="text-success"><td>Discount</td><td class="text-end">−' + money(d.discount) + '</td></tr>'; }
                h += '<tr><td>Tax <span class="small text-body-secondary">(' + d.tax_rate + '%)</span></td><td class="text-end">' + money(d.tax) + '</td></tr><tr><td>Service Charge <span class="small text-body-secondary">(' + d.service_rate + '%)</span></td><td class="text-end">' + money(d.service) + '</td></tr><tr class="fw-bold"><td>Total</td><td class="text-end">' + money(d.total) + '</td></tr></table>';
                if (d.promo_valid === false) { h += '<div class="small text-danger mt-2">Promo code not valid for these rooms.</div>'; }
                if (d.promo_valid === true) { h += '<div class="small text-success mt-2">Promo code applied (' + d.promo + '%).</div>'; }
                box.innerHTML = h;
                rowEls().forEach(function (r) { var info = d.types[r.querySelector('.room-type').value]; if (info) { renderNumbers(r, info); } });
                if ($('rentIn')) { $('rentIn').value = $('checkin').value + ' ' + String(d.checkin_time).slice(0, 5); $('rentOut').value = $('checkout').value + ' ' + String(d.checkout_time).slice(0, 5); $('rentAmt').value = money(d.subtotal); }
                if ($('inTime')) { $('inTime').textContent = 'from ' + String(d.checkin_time).slice(0, 5); $('outTime').textContent = 'until ' + String(d.checkout_time).slice(0, 5); }
                if ($('discountAmt')) { $('discountAmt').value = money(d.manual_discount); $('commissionAmt').value = money(d.commission); }
                if ($('advanceHint') && d.advance_required > 0) { $('advanceHint').innerHTML = 'Required advance: <strong>' + money(d.advance_required) + '</strong> <button type="button" class="btn btn-sm btn-link p-0 ms-1" id="useAdv">use it</button>'; var u = $('useAdv'); if (u) { u.onclick = function () { $('deposit').value = d.advance_required.toFixed(2); }; } }
            }).catch(function () { box.innerHTML = '<div class="small text-danger">Could not load the price.</div>'; });
    }
    function schedule() { clearTimeout(timer); timer = setTimeout(refresh, 250); }
    function onRowChange(e) {
        var row = e.target.closest('.room-row'); if (!row) { return; }
        var count = row.querySelector('.room-count');
        if (e.target.matches('.room-numbers input')) {
            // Ticking rooms sets how many rooms are booked; unticked rooms are simply picked for you.
            var n = ticked(row).length; if (n > parseInt(count.value || 0, 10)) { count.value = n; }
        } else if (e.target.matches('.room-count')) {
            var picks = ticked(row), want = parseInt(count.value || 1, 10);
            while (picks.length > want) { picks.pop().checked = false; }
        } else if (e.target.matches('.room-type')) {
            row.querySelector('.room-numbers').innerHTML = ''; row.querySelector('.room-avail').innerHTML = '';
            var opt = e.target.selectedOptions[0]; if (opt && opt.dataset.cap) { row.querySelector('.room-adults').max = opt.dataset.cap * 10; }
            syncTypeOptions();
        }
        schedule();
    }
    roomRows.addEventListener('input', onRowChange); roomRows.addEventListener('change', onRowChange);
    roomRows.addEventListener('click', function (e) {
        var b = e.target.closest('.rm-room'); if (!b) { return; }
        if (rowEls().length > 1) { b.closest('.room-row').remove(); syncTypeOptions(); schedule(); } else { alert('A reservation needs at least one room.'); }
    });
    $('addRoom').addEventListener('click', function () {
        roomRows.insertAdjacentHTML('beforeend', roomTpl.innerHTML.replace(/__i__/g, rowSeq++));
        var rows = rowEls(), last = rows[rows.length - 1], sel = last.querySelector('.room-type');
        syncTypeOptions();
        var free = Array.prototype.find.call(sel.options, function (o) { return o.value && !o.disabled; });
        if (free) { sel.value = free.value; }
        syncTypeOptions(); schedule();
    });
    base.forEach(function (f) { $(f).addEventListener('input', schedule); $(f).addEventListener('change', schedule); });

    // Customers: one main guest, either new (modal fields) or an existing record
    var custRows = $('custRows'), gid = $('guest_id');
    function setCustomer(name, phone, badge) {
        if (!custRows) { return; }
        custRows.innerHTML = '<tr><td>1</td><td>' + esc(name) + ' <span class="badge text-bg-secondary">' + badge + '</span></td><td>' + esc(phone) + '</td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" id="custRemove"><i class="bi bi-trash"></i></button></td></tr>';
        $('custRemove').onclick = clearCustomer;
    }
    function clearCustomer() {
        gid.value = '';
        document.querySelectorAll('#newGuest input, #newGuest select, #newGuest textarea').forEach(function (e) { if (e.type === 'checkbox') { e.checked = false; } else { e.value = ''; } });
        custRows.innerHTML = '<tr id="custEmpty"><td colspan="4" class="text-body-secondary p-3">Add the main guest with the + button.</td></tr>';
    }
    if ($('newCustomerAdd')) {
        $('newCustomerAdd').addEventListener('click', function () {
            var f = document.querySelector('[data-ng=first]'), p = document.querySelector('[data-ng=phone]');
            if (!f.value.trim() || !p.value.trim()) { alert('Enter at least the first name and mobile number.'); return; }
            gid.value = '';
            setCustomer((document.querySelector('[name=new_title]').value + ' ' + f.value + ' ' + document.querySelector('[name=new_lastname]').value).trim(), p.value, 'New');
            bootstrap.Modal.getInstance($('newCustomerModal')).hide();
        });
        var st, so = $('oldSearch'), res = $('oldResults');
        so.addEventListener('input', function () {
            clearTimeout(st); var q = so.value.trim();
            if (q.length < 2) { res.innerHTML = ''; return; }
            st = setTimeout(function () {
                fetch(@json(route('admin.reservations.customers')) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (list) {
                    res.innerHTML = list.length ? list.map(function (c) { return '<button type="button" class="list-group-item list-group-item-action" data-id="' + c.id + '" data-name="' + esc(c.name) + '" data-phone="' + esc(c.phone) + '">' + esc(c.phone) + ' — ' + esc(c.name) + '</button>'; }).join('') : '<div class="text-body-secondary small">No customer found. Use New Customer instead.</div>';
                });
            }, 250);
        });
        res.addEventListener('click', function (e) {
            var b = e.target.closest('[data-id]'); if (!b) { return; }
            document.querySelectorAll('#newGuest input, #newGuest select, #newGuest textarea').forEach(function (x) { if (x.type === 'checkbox') { x.checked = false; } else { x.value = ''; } });
            gid.value = b.dataset.id; setCustomer(b.dataset.name, b.dataset.phone, 'Existing');
            bootstrap.Modal.getInstance($('oldCustomerModal')).hide();
        });
        @php $preGuest = old('guest_id', $prefill['guest'] ?? null) ? \App\Models\Customerinfo::find(old('guest_id', $prefill['guest'] ?? null)) : null; @endphp
        @if ($preGuest) setCustomer(@json(trim($preGuest->firstname.' '.$preGuest->lastname)), @json($preGuest->cust_phone), 'Existing');
        @elseif (old('new_firstname')) setCustomer(@json(trim(old('new_title').' '.old('new_firstname').' '.old('new_lastname'))), @json(old('new_phone')), 'New'); @endif
    }
    window.addEventListener('DOMContentLoaded', function () {
        ['checkin', 'checkout'].forEach(function (f) { var el = $(f); if (el && el._flatpickr) { el._flatpickr.config.onChange.push(schedule); } });
        // Check-out can only be after check-in: follow the arrival date and offer the next day.
        var ci = $('checkin'), co = $('checkout');
        if (ci && co && ci._flatpickr && co._flatpickr) {
            var sync = function () {
                var d = ci._flatpickr.selectedDates[0]; if (!d) { return; }
                var min = new Date(d.getTime()); min.setDate(min.getDate() + 1);
                co._flatpickr.set('minDate', min);
                var c = co._flatpickr.selectedDates[0];
                if (!c || c <= d) { co._flatpickr.setDate(min, true); }
            };
            ci._flatpickr.config.onChange.push(sync); sync();
        }
        syncTypeOptions(); refresh();
    });
})();
</script>
@endpush
@endsection
