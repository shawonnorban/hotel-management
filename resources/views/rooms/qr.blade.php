@extends('layouts.site')
@section('title', 'Room '.$room->roomno)
@section('content')
<div class="container py-5" style="max-width:520px">
    <div class="card text-center"><div class="card-body p-4">
        <div class="text-body-secondary small text-uppercase">{{ $type }}</div>
        <h1 class="display-5 fw-bold">Room {{ $room->roomno }}</h1>
        <p class="text-body-secondary">Need your room cleaned? Let housekeeping know with one tap.</p>
        @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        <form method="post" action="{{ route('room.cleaning', $room->roomassignid) }}">@csrf<button class="btn btn-primary btn-lg w-100"><i class="bi bi-stars me-2"></i>Request room cleaning</button></form>
    </div></div>
</div>
@endsection
