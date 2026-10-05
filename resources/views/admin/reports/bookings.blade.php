@extends('layouts.admin')
@section('title', 'Bookings report')
@section('content')
<h1 class="page-title mb-4">Bookings report</h1>
@component('admin.reports._filter', ['from' => $from, 'to' => $to])
    <div class="col-md-3"><label class="form-label small fw-semibold">Status</label><select name="status" class="form-select"><option value="">Any</option>@foreach (\App\Models\BookedInfo::STATUS_LABELS as $k => $l)<option value="{{ $k }}" @selected((string) $status === (string) $k && $status !== null && $status !== '')>{{ $l }}</option>@endforeach</select></div>
@endcomponent
<div class="row g-3 mb-4">
    @foreach ([['Bookings', $totals['count']], ['Room nights', $totals['nights']], ['Booked value', \App\Support\Money::format($totals['total'])], ['Collected', \App\Support\Money::format($totals['paid'])]] as [$l, $v])
        <div class="col-6 col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $l }}</div><div class="fs-4 fw-bold">{{ $v }}</div></div></div></div>
    @endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Booking</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Rooms</th><th>Source</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th></tr></thead><tbody>
    @forelse ($rows as $b)
        <tr><td><a class="text-decoration-none" href="{{ route('admin.reservations.show', $b->booking_number) }}">#{{ $b->booking_number }}</a></td><td>{{ $b->full_guest_name ?: $b->customer?->full_name }}</td><td>{{ $b->checkindate->format('d M Y') }}</td><td>{{ $b->checkoutdate->format('d M Y') }}</td><td>{{ $b->room_no }}</td><td class="text-capitalize">{{ $b->source }}</td><td><span class="pill {{ $b->status_pill }}">{{ $b->status_label }}</span></td><td class="text-end">{{ \App\Support\Money::format($b->total_price) }}</td><td class="text-end">{{ \App\Support\Money::format($b->paid_amount) }}</td></tr>
    @empty
        <tr><td colspan="9" class="text-center text-body-secondary py-4">No bookings in this period.</td></tr>
    @endforelse
    </tbody></table></div></div>
@endsection
