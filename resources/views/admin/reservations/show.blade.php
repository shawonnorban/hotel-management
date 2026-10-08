@extends('layouts.admin')
@section('title', 'Booking #'.$booking->booking_number)
@section('content')
@php($status = (string) $booking->bookingstatus)
@php($money = fn ($v) => \App\Support\Money::format($v))
<a href="{{ route('admin.reservations.index') }}" class="small text-decoration-none no-print"><i class="bi bi-arrow-left"></i> Reservations</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-4">
    <h1 class="page-title me-2">Booking #{{ $booking->booking_number }}</h1>
    <span class="pill {{ $booking->status_pill }} fs-6">{{ $booking->status_label }}</span>
    <div class="ms-auto d-flex flex-wrap gap-2 no-print">
        @can('reservations.status')
            @if ($status === '0')<form method="post" action="{{ route('admin.reservations.confirm', $booking->booking_number) }}">@csrf<button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Confirm</button></form>@endif
            @if (in_array($status, ['0', '2']))<form method="post" action="{{ route('admin.reservations.check-in', $booking->booking_number) }}">@csrf<button class="btn btn-success"><i class="bi bi-box-arrow-in-right me-1"></i>Check in</button></form>@endif
            @if ($status === '4')<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#checkoutModal"><i class="bi bi-box-arrow-right me-1"></i>Check out</button>@endif
        @endcan
        @can('reservations.edit')@if (in_array($status, ['0', '2']))<a class="btn btn-outline-primary" href="{{ route('admin.reservations.edit', $booking->booking_number) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endif @endcan
        <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.invoice', $booking->booking_number) }}" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Invoice</a>
        @can('reservations.status')@if (in_array($status, ['0', '2']))<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-1"></i>Cancel</button>@endif @endcan
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4"><div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Check-in</div><div class="fw-semibold">{{ $booking->checkindate->format('D, d M Y') }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Check-out</div><div class="fw-semibold">{{ $booking->checkoutdate->format('D, d M Y') }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Nights</div><div class="fw-semibold">{{ $booking->nights }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Source</div><div class="fw-semibold text-capitalize">{{ $booking->source ?: 'website' }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Room type</div><div class="fw-semibold">{{ $roomType?->roomtype ?? '—' }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Rooms</div><div class="fw-semibold">{{ $booking->total_room }} · No. {{ $booking->room_no }}</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Guests</div><div class="fw-semibold">{{ array_sum(explode(',', $booking->nuofpeople)) }} adult(s), {{ array_sum(explode(',', (string) $booking->children)) }} child(ren)</div></div>
                <div class="col-sm-6 col-md-3"><div class="small text-body-secondary">Booked on</div><div class="fw-semibold">{{ $booking->date_time->format('d M Y H:i') }}</div></div>
            </div>
            @if ($booking->special_request)<hr><div class="small text-body-secondary">Special requests</div><div>{{ $booking->special_request }}</div>@endif
        </div></div>

        <div class="card mb-4"><div class="card-header d-flex align-items-center">Guests
                        @if (! empty($wa))<a class="btn btn-sm btn-outline-success ms-auto no-print" href="{{ $wa }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>@endif
            @can('reservations.edit')<button class="btn btn-sm btn-outline-primary {{ ! empty($wa) ? 'ms-2' : 'ms-auto' }} no-print" data-bs-toggle="modal" data-bs-target="#guestModal"><i class="bi bi-person-plus me-1"></i>Add guest</button>@endcan</div>
            <div class="table-responsive"><table class="table mb-0 align-middle"><tbody>
                <tr><td>@if ($booking->customer?->imgguest)<img src="{{ asset($booking->customer->imgguest) }}" class="rounded" width="40" height="40" alt="">@endif</td><td>{{ trim(($booking->customer->firstname ?? '').' '.($booking->customer->lastname ?? '')) }} <span class="badge text-bg-secondary">Primary</span></td><td>{{ $booking->customer->cust_phone ?? '' }}</td><td>{{ $booking->customer->pitype ?? '' }} {{ $booking->customer->pid ?? '' }}</td><td class="text-end">@foreach (['imgfront' => 'Front', 'imgback' => 'Back'] as $c => $l)@if ($booking->customer?->$c)<a href="{{ asset($booking->customer->$c) }}" target="_blank" class="small me-2">{{ $l }}</a>@endif @endforeach</td></tr>
                @foreach ($booking->guests as $g)
                <tr><td>@if ($g->occupant_image)<img src="{{ asset($g->occupant_image) }}" class="rounded" width="40" height="40" alt="">@endif</td><td>{{ $g->guestname }} <span class="small text-body-secondary">{{ $g->gender }}</span></td><td>{{ $g->mobile }}</td><td>{{ $g->photo_id_type }} {{ $g->photo_id }}</td>
                    <td class="text-end">@if ($g->front_image)<a href="{{ asset($g->front_image) }}" target="_blank" class="small me-2">Front</a>@endif @if ($g->back_image)<a href="{{ asset($g->back_image) }}" target="_blank" class="small me-2">Back</a>@endif
                        @can('reservations.edit')<form method="post" action="{{ route('admin.reservations.guests.destroy', [$booking->booking_number, $g]) }}" class="d-inline no-print" data-confirm="Remove this guest?">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button></form>@endcan</td></tr>
                @endforeach
            </tbody></table></div></div>

        <div class="card mb-4"><div class="card-header d-flex align-items-center">Bill
            @can('reservations.edit')@if (in_array($status, ['0', '2', '4']))<button class="btn btn-sm btn-outline-primary ms-auto no-print" data-bs-toggle="modal" data-bs-target="#chargeModal"><i class="bi bi-plus-lg me-1"></i>Add charge</button>@endif @endcan</div>
            <div class="table-responsive"><table class="table mb-0">
                <tbody>
                @foreach ($lines as $line)<tr><td>{{ $line['label'] }}</td><td class="text-end">{{ $line['amount'] }}</td></tr>@endforeach
                @foreach ($booking->charges as $charge)
                    <tr><td>{{ $charge->description }} <span class="small text-body-secondary">· {{ $charge->charged_on->format('d M') }}</span></td>
                        <td class="text-end">{{ $money($charge->amount) }}
                            @can('reservations.edit')@if (in_array($status, ['0', '2', '4']))<form method="post" action="{{ route('admin.reservations.charges.destroy', [$booking->booking_number, $charge]) }}" class="d-inline no-print" data-confirm="Remove this charge?">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0 ms-2"><i class="bi bi-x-lg"></i></button></form>@endif @endcan</td></tr>
                @endforeach
                <tr class="fw-bold"><td>Total</td><td class="text-end">{{ $money($booking->total_price) }}</td></tr>
                <tr><td>Paid</td><td class="text-end">{{ $money($booking->paid_amount) }}</td></tr>
                <tr class="fw-bold {{ $booking->balance > 0 && $status !== '1' ? 'table-warning' : '' }}"><td>Balance due</td><td class="text-end">{{ $money($booking->balance) }}</td></tr>
                </tbody></table></div></div>

        <div class="card mb-4"><div class="card-header d-flex align-items-center">Payments
            @can('reservations.payments')<span class="ms-auto d-flex gap-2 no-print">
                @if ($booking->balance > 0 && $status !== '1')<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin me-1"></i>Take payment</button>@endif
                @if ($booking->paid_amount > 0)<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#refundModal">Refund</button>@endif</span>@endcan</div>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th>Details</th><th class="text-end">Amount</th></tr></thead><tbody>
                @forelse ($booking->payments as $p)<tr><td>{{ $p->invoice }}</td><td>{{ $p->paydate->format('d M Y H:i') }}</td><td>{{ $p->paymenttype }}</td><td class="text-body-secondary">{{ $p->details }}</td><td class="text-end {{ $p->paymentamount < 0 ? 'text-danger' : '' }}">{{ $money($p->paymentamount) }}</td></tr>
                @empty<tr><td colspan="5" class="text-center text-body-secondary py-3">No payments yet.</td></tr>@endforelse
                </tbody></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4"><div class="card-header">Guest</div><div class="card-body">
            <div class="fw-semibold">{{ $booking->customer?->full_name }}</div>
            @if ($booking->full_guest_name && $booking->full_guest_name !== $booking->customer?->full_name)<div class="small text-body-secondary">Staying: {{ $booking->full_guest_name }}</div>@endif
            <div class="small mt-2"><i class="bi bi-telephone me-2"></i>{{ $booking->customer?->cust_phone ?: '—' }}</div>
            <div class="small"><i class="bi bi-envelope me-2"></i>{{ $booking->customer?->email ?: '—' }}</div>
            @can('customers.view')@if ($booking->customer)<a class="small d-inline-block mt-2" href="{{ route('admin.resource.edit', ['customers', $booking->customer->customerid]) }}">Open guest record</a>@endif @endcan
        </div></div>
        <div class="card"><div class="card-header">Activity</div><ul class="list-group list-group-flush">
            @forelse ($booking->events as $e)
                <li class="list-group-item"><div class="d-flex justify-content-between"><strong class="text-capitalize">{{ str_replace('_', ' ', $e->event) }}</strong><span class="small text-body-secondary">{{ $e->created_at->format('d M H:i') }}</span></div>
                    @if ($e->detail)<div class="small text-body-secondary">{{ $e->detail }}</div>@endif<div class="small text-body-tertiary">{{ $e->user?->full_name ?? 'System' }}</div></li>
            @empty<li class="list-group-item text-body-secondary small">No activity recorded.</li>@endforelse
        </ul></div>
    </div>
</div>

@can('reservations.payments')
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.reservations.payments.store', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Take payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Amount (balance {{ $money($booking->balance) }})</label><input type="number" step="0.01" min="0.01" max="{{ $booking->balance }}" name="amount" value="{{ $booking->balance }}" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Method</label><select name="method" class="form-select" required>@foreach ($methods as $m)<option value="{{ $m->payment_method_id }}">{{ $m->payment_method }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Reference</label><input name="reference" class="form-control" maxlength="60" placeholder="Card slip / transfer no."></div>
        <div class="col-12"><label class="form-label small fw-semibold">Note</label><input name="details" class="form-control" maxlength="100"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Record payment</button></div></form></div></div>
<div class="modal fade" id="refundModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.reservations.refunds.store', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Amount (paid {{ $money($booking->paid_amount) }})</label><input type="number" step="0.01" min="0.01" max="{{ $booking->paid_amount }}" name="amount" class="form-control" required></div>
        <div class="col-12"><label class="form-label small fw-semibold">Paid back by</label><select name="method" class="form-select" required>@foreach ($allMethods as $m)<option value="{{ $m->payment_method_id }}">{{ $m->payment_method }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label small fw-semibold">Reason</label><input name="reason" class="form-control" maxlength="150"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Refund</button></div></form></div></div>
@endcan
@can('reservations.edit')
<div class="modal fade" id="guestModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" enctype="multipart/form-data" action="{{ route('admin.reservations.guests.store', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Add guest</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-2">
        <div class="col-md-6"><input name="guest[name]" class="form-control" placeholder="Full name" required></div>
        <div class="col-md-3"><select name="guest[gender]" class="form-select"><option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option></select></div>
        <div class="col-md-3"><input name="guest[mobile]" class="form-control" placeholder="Mobile"></div>
        <div class="col-md-6"><input type="email" name="guest[email]" class="form-control" placeholder="Email"></div>
        <div class="col-md-3"><select name="guest[id_type]" class="form-select"><option value="">ID type</option><option>NID</option><option>Passport</option><option>Driving licence</option><option>Other</option></select></div>
        <div class="col-md-3"><input name="guest[id_no]" class="form-control" placeholder="ID number"></div>
        <div class="col-md-4"><label class="small">ID front</label><input type="file" name="guest[front]" class="form-control" accept="image/*"></div>
        <div class="col-md-4"><label class="small">ID back</label><input type="file" name="guest[back]" class="form-control" accept="image/*"></div>
        <div class="col-md-4"><label class="small">Guest photo</label><input type="file" name="guest[photo]" class="form-control" accept="image/*"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Add</button></div></form></div></div>
@endcan
@can('reservations.edit')
<div class="modal fade" id="chargeModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.reservations.charges.store', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Add charge to the bill</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Description</label><input name="description" class="form-control" maxlength="150" required placeholder="Laundry, minibar, airport transfer…"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Add charge</button></div></form></div></div>
@endcan
@can('reservations.status')
<div class="modal fade" id="checkoutModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.reservations.check-out', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Check out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p>Total {{ $money($booking->total_price) }} · paid {{ $money($booking->paid_amount) }} · <strong>balance {{ $money($booking->balance) }}</strong></p>
        @if ($booking->balance > 0)<div class="form-check"><input class="form-check-input" type="checkbox" name="on_account" value="1" id="onAcct"><label class="form-check-label" for="onAcct">Leave the unpaid balance on the guest's account</label></div>
        <div class="form-text">Otherwise take the payment first.</div>@else<div class="text-success"><i class="bi bi-check-circle me-1"></i>Everything is settled.</div>@endif
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-success">Check out</button></div></form></div></div>
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.reservations.cancel', $booking->booking_number) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Cancel booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Reason</label><input name="reason" class="form-control" maxlength="200"></div>
        @if ($booking->paid_amount > 0)
            <div class="col-12 small text-body-secondary">{{ $money($booking->paid_amount) }} has been paid. Whatever you do not refund is kept as cancellation income.</div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Refund amount</label><input type="number" step="0.01" min="0" max="{{ $booking->paid_amount }}" name="refund" value="{{ $booking->paid_amount }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Paid back by</label><select name="method" class="form-select">@foreach ($allMethods as $m)<option value="{{ $m->payment_method_id }}">{{ $m->payment_method }}</option>@endforeach</select></div>
        @endif
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep booking</button><button class="btn btn-danger">Cancel booking</button></div></form></div></div>
@endcan
@endsection
