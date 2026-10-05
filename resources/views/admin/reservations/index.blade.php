@extends('layouts.admin')
@section('title', 'Reservations')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <div class="me-auto"><h1 class="page-title">Reservations</h1><p class="page-sub">{{ number_format($bookings->total()) }} bookings</p></div>
    @can('reservations.create')<a href="{{ route('admin.reservations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New reservation</a>@endcan
</div>
@php($views = ['' => 'All', 'arrivals' => 'Arrivals today', 'inhouse' => 'In house', 'departures' => 'Departures today', 'unpaid' => 'Unpaid'])
<ul class="nav nav-pills mb-3 flex-wrap gap-1">
    @foreach ($views as $key => $label)
        <li class="nav-item"><a class="nav-link py-1 {{ ($f['view'] ?? '') === (string) $key ? 'active' : '' }}" href="{{ route('admin.reservations.index', array_filter(['view' => $key, 'q' => $f['q'] ?? null])) }}">{{ $label }}</a></li>
    @endforeach
</ul>
<form method="get" class="row g-2 mb-3">
    @if (! empty($f['view']))<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
    <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input name="q" class="form-control" placeholder="Booking no., guest, phone, e-mail, room" value="{{ $f['q'] ?? '' }}"></div></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">Any status</option>@foreach (\App\Models\BookedInfo::STATUS_LABELS as $code => $label)<option value="{{ $code }}" @selected(($f['status'] ?? '') !== '' && (string) $f['status'] === (string) $code)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="Arrival from" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="Arrival to" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
@include('admin.reservations._table')
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
