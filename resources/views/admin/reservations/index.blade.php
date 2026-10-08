@extends('layouts.admin')
@section('title', 'Reservations')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <div class="me-auto"><h1 class="page-title">Reservations</h1><p class="page-sub">{{ number_format($bookings->total()) }} bookings</p></div>
    @can('reservations.create')<a href="{{ route('admin.reservations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New reservation</a>@endcan
</div>
@php
    $tiles = [
        ['arrivals', 'Arrivals today', $stats['arrivals'], 'bi-box-arrow-in-right', 'primary'],
        ['inhouse', 'In house', $stats['inhouse'], 'bi-door-open', 'info'],
        ['departures', 'Departures today', $stats['departures'], 'bi-box-arrow-right', 'warning'],
        ['upcoming', 'Upcoming', $stats['upcoming'], 'bi-calendar-event', 'success'],
        ['pending', 'Awaiting advance', $stats['pending'], 'bi-hourglass-split', 'secondary'],
    ];
@endphp
<div class="row row-cols-2 row-cols-xl-6 g-3 mb-3">
    @foreach ($tiles as [$key, $label, $count, $icon, $tone])
        <div class="col"><a href="{{ route('admin.reservations.index', ['view' => $key]) }}" class="card text-decoration-none text-reset h-100 {{ ($f['view'] ?? '') === $key ? 'border-primary' : '' }}"><div class="card-body d-flex align-items-center gap-3 py-3">
            <span class="stat"><span class="icon text-{{ $tone }}"><i class="bi {{ $icon }}"></i></span></span>
            <div><div class="fs-4 fw-bold lh-1">{{ $count }}</div><div class="small text-body-secondary">{{ $label }}</div></div></div></a></div>
    @endforeach
    <div class="col"><a href="{{ route('admin.reservations.index', ['view' => 'unpaid']) }}" class="card text-decoration-none text-reset h-100 {{ ($f['view'] ?? '') === 'unpaid' ? 'border-primary' : '' }}"><div class="card-body py-3"><div class="fs-5 fw-bold lh-1 {{ $stats['unpaid'] > 0 ? 'text-danger' : '' }}">{{ \App\Support\Money::format($stats['unpaid']) }}</div><div class="small text-body-secondary mt-1">Balance due</div></div></a></div>
</div>
<form method="get" class="row g-2 mb-3">
    @if (! empty($f['view']))<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
    <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input name="q" class="form-control" placeholder="Booking no., guest, phone, e-mail, room" value="{{ $f['q'] ?? '' }}"></div></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">Any status</option>@foreach (\App\Models\BookedInfo::STATUS_LABELS as $code => $label)<option value="{{ $code }}" @selected(($f['status'] ?? '') !== '' && (string) $f['status'] === (string) $code)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="Arrival from" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="Arrival to" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-md-2"><select name="sort" class="form-select"><option value="newest" @selected(($f['sort'] ?? 'newest') === 'newest')>Newest first</option><option value="arrival" @selected(($f['sort'] ?? '') === 'arrival')>By arrival date</option></select></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('admin.reservations.index', array_merge(request()->query(), ['export' => 'csv'])) }}"><i class="bi bi-download me-1"></i>CSV</a> @if ($f)<a class="btn btn-link" href="{{ route('admin.reservations.index') }}">Reset</a>@endif</div>
</form>
@include('admin.reservations._table', ['actions' => true])
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
