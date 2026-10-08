@extends('layouts.admin')
@section('title', 'Hallroom booking')
@section('content')
<div class="d-flex align-items-center mb-3"><div class="me-auto"><h1 class="page-title">Hallroom booking</h1><p class="page-sub">Events and functions in your halls.</p></div>
    @can('hall-bookings.manage')<a href="{{ route('admin.hall-bookings.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New booking</a>@endcan</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input name="q" class="form-control" placeholder="Booking, customer or event" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-2"><select name="hall" class="form-select"><option value="">All halls</option>@foreach ($halls as $id => $n)<option value="{{ $id }}" @selected((int) ($f['hall'] ?? 0) === $id)>{{ $n }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (\App\Models\HallBooking::STATUSES as $k => $l)<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Booking</th><th>Event</th><th>Hall</th><th>When</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead><tbody>
    @forelse ($bookings as $b)
        <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.hall-bookings.show', $b) }}">{{ $b->number }}</a></td><td>{{ $b->event_name }}<div class="small text-body-secondary">{{ $b->customer_name }}</div></td><td>{{ $b->hall->name }}</td>
            <td class="text-nowrap">{{ $b->event_date->format('d M Y') }}<div class="small text-body-secondary">{{ substr($b->starts_at, 0, 5) }}–{{ substr($b->ends_at, 0, 5) }}</div></td>
            <td><span class="badge text-bg-{{ ['tentative' => 'warning', 'confirmed' => 'primary', 'completed' => 'success', 'cancelled' => 'secondary'][$b->status] }}">{{ \App\Models\HallBooking::STATUSES[$b->status] }}</span></td>
            <td class="text-end">{{ \App\Support\Money::format($b->total) }}</td><td class="text-end {{ $b->status !== 'cancelled' && $b->due > 0 ? 'text-danger fw-semibold' : 'text-body-secondary' }}">{{ \App\Support\Money::format($b->status === 'cancelled' ? 0 : $b->due) }}</td></tr>
    @empty
        <tr><td colspan="7"><div class="empty"><i class="bi bi-calendar-event"></i>No hall bookings yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($bookings->hasPages())<div class="card-footer bg-transparent">{{ $bookings->links() }}</div>@endif
</div>
@endsection
