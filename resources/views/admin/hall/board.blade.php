@extends('layouts.admin')
@section('title', 'Hall status')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3"><div class="me-auto"><h1 class="page-title">Hall status</h1><p class="page-sub">{{ $date->format('l, d F Y') }}</p></div>
    <form method="get" class="d-flex gap-2"><input name="date" class="form-control" data-date value="{{ $date->toDateString() }}"><button class="btn btn-outline-primary">Show</button></form></div>
<div class="row g-3">
@forelse ($halls as $h)
    @php $list = $bookings->get($h->id, collect()); @endphp
    <div class="col-md-6 col-xl-4"><div class="card h-100 {{ ! $h->is_active ? 'opacity-50' : '' }}"><div class="card-header d-flex align-items-center">{{ $h->name }} <span class="small text-body-secondary ms-2">{{ $h->type->name }} · {{ $h->capacity }}</span>
        <span class="badge ms-auto text-bg-{{ ! $h->is_active ? 'secondary' : ($list->isEmpty() ? 'success' : 'danger') }}">{{ ! $h->is_active ? 'Unavailable' : ($list->isEmpty() ? 'Free' : 'Booked') }}</span></div>
        <ul class="list-group list-group-flush">@forelse ($list as $b)<li class="list-group-item"><a class="text-decoration-none" href="{{ route('admin.hall-bookings.show', $b) }}">{{ substr($b->starts_at, 0, 5) }}–{{ substr($b->ends_at, 0, 5) }} · {{ $b->event_name }}</a><div class="small text-body-secondary">{{ $b->customer_name }} · {{ \App\Models\HallBooking::STATUSES[$b->status] }}</div></li>@empty<li class="list-group-item text-body-secondary">Nothing booked.</li>@endforelse</ul></div></div>
@empty
    <div class="col-12"><div class="empty"><i class="bi bi-grid"></i>No halls yet.</div></div>
@endforelse
</div>
@endsection
