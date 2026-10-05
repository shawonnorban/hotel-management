@extends('layouts.admin')
@section('content')
<h1 class="h4 mb-3">Dashboard</h1>
<div class="row g-3 mb-4">
    @foreach ($stats as $label => $value)
        <div class="col-6 col-md-3 col-xl"><div class="card"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div></div></div></div>
    @endforeach
</div>
<h2 class="h5">Latest reservations</h2>
@include('admin.reservations._table', ['bookings' => $latest])
@endsection
