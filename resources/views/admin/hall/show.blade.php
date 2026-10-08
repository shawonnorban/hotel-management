@extends('layouts.admin')
@section('title', $booking->number)
@section('content')
<a href="{{ route('admin.hall-bookings.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Hallroom booking</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-4"><div class="me-auto"><h1 class="page-title">{{ $booking->event_name }}</h1><p class="page-sub">{{ $booking->number }} · {{ $booking->hall->name }} · {{ $booking->event_date->format('d M Y') }} {{ substr($booking->starts_at, 0, 5) }}–{{ substr($booking->ends_at, 0, 5) }}</p></div>
    <span class="badge fs-6 text-bg-secondary">{{ \App\Models\HallBooking::STATUSES[$booking->status] }}</span></div>
@error('booking')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="row g-4"><div class="col-lg-8">
    <div class="card mb-4"><div class="card-body row">
        <div class="col-md-4 mb-3"><div class="small text-body-secondary">Customer</div><div class="fw-semibold">{{ $booking->customer_name }}</div><div class="small">{{ $booking->phone }} {{ $booking->email }}</div></div>
        <div class="col-md-4 mb-3"><div class="small text-body-secondary">Guests</div><div class="fw-semibold">{{ $booking->guests }} of {{ $booking->hall->capacity }}</div></div>
        <div class="col-md-4 mb-3"><div class="small text-body-secondary">Seat plan</div><div class="fw-semibold">{{ $booking->seatPlan ? $booking->seatPlan->name.' · '.\App\Models\HallSeatPlan::LAYOUTS[$booking->seatPlan->layout] : '—' }}</div></div>
        <div class="col-12 mb-2"><div class="small text-body-secondary">Facilities</div>@forelse ($booking->hall->facilities as $f)<span class="badge text-bg-light me-1">{{ $f->name }}</span>@empty — @endforelse</div>
        @if ($booking->notes)<div class="col-12"><div class="small text-body-secondary">Notes</div>{{ $booking->notes }}</div>@endif
    </div></div>
    <div class="card mb-4"><table class="table mb-0"><tbody>
        <tr><td>Hire ({{ rtrim(rtrim(number_format(\App\Services\HallService::hours($booking->starts_at, $booking->ends_at), 2), '0'), '.') }} h)</td><td class="text-end">{{ \App\Support\Money::format($booking->subtotal) }}</td></tr>
        <tr><td>Discount</td><td class="text-end">−{{ \App\Support\Money::format($booking->discount) }}</td></tr>
        <tr class="fw-bold"><td>Total</td><td class="text-end">{{ \App\Support\Money::format($booking->total) }}</td></tr>
        <tr><td>Paid</td><td class="text-end">{{ \App\Support\Money::format($booking->paid) }}</td></tr>
        <tr class="fw-bold"><td>Due</td><td class="text-end">{{ \App\Support\Money::format($booking->status === 'cancelled' ? 0 : $booking->due) }}</td></tr></tbody></table></div>
    <div class="card"><div class="card-header">Payments</div><table class="table table-sm mb-0"><tbody>
        @forelse ($booking->payments as $p)<tr><td>{{ $p->paid_on->format('d M Y') }}</td><td>{{ $p->account->name }}</td><td>{{ $p->reference }}</td><td class="text-end">{{ \App\Support\Money::format($p->amount) }}</td></tr>@empty<tr><td class="text-body-secondary p-3">No payments yet.</td></tr>@endforelse</tbody></table></div>
</div><div class="col-lg-4">
    @can('hall-bookings.manage')@if ($booking->status !== 'cancelled')
    <div class="card mb-3"><div class="card-header">Status</div><div class="card-body"><form method="post" action="{{ route('admin.hall-bookings.status', $booking) }}" class="d-flex gap-2">@csrf
        <select name="status" class="form-select">@foreach (\App\Models\HallBooking::STATUSES as $k => $l)<option value="{{ $k }}" @selected($booking->status === $k)>{{ $l }}</option>@endforeach</select><button class="btn btn-outline-primary">Save</button></form></div></div>
    @if ($booking->due > 0)
    <div class="card"><div class="card-header">Take payment</div><div class="card-body"><form method="post" action="{{ route('admin.hall-bookings.pay', $booking) }}" class="row g-2">@csrf
        <div class="col-12"><input type="number" step="0.01" name="amount" class="form-control" value="{{ $booking->due }}" required></div>
        <div class="col-12"><select name="account" class="form-select">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-6"><input name="paid_on" class="form-control" data-date value="{{ today()->toDateString() }}"></div><div class="col-6"><input name="reference" class="form-control" placeholder="Reference"></div>
        <div class="col-12"><button class="btn btn-primary w-100">Record payment</button></div></form></div></div>@endif
    @endif @endcan
</div></div>
@endsection
