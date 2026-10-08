@extends('layouts.admin')
@section('title', 'Room QR list')
@section('content')
<div class="d-flex align-items-center mb-3"><div class="me-auto"><h1 class="page-title">Room QR list</h1><p class="page-sub">Print and place in each room. Guests scan to request cleaning.</p></div>
    <button class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
<div class="row g-3">
@foreach ($rooms as $r)
    <div class="col-6 col-md-4 col-xl-3"><div class="card text-center h-100"><div class="card-body">
        <div class="mx-auto" style="width:160px">{!! $r->qr !!}</div>
        <div class="fw-bold fs-5 mt-2">Room {{ $r->roomno }}</div><div class="small text-body-secondary">{{ $types[$r->roomid] ?? '' }}</div>
        <a class="small no-print" href="{{ route('room.show', $r->roomassignid) }}" target="_blank">Open page</a>
    </div></div></div>
@endforeach
</div>
@endsection
