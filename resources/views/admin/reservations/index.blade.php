@extends('layouts.admin')
@section('title', 'Reservations')
@section('content')
<h1 class="h4 mb-3">Reservations</h1>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Number, guest, email or phone" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">Any status</option>
            @foreach (\App\Models\Legacy\BookedInfo::STATUS_LABELS as $code => $label)
                <option value="{{ $code }}" @selected(($filters['status'] ?? '') !== '' && (string) ($filters['status'] ?? '') === (string) $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
</form>
@include('admin.reservations._table')
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
