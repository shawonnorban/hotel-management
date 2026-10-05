@extends('layouts.admin')
@section('title', 'Booking '.$booking->booking_number)
@section('content')
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><div class="card-body">
        <h1 class="h4 mb-3">Booking {{ $booking->booking_number }}</h1>
        @include('booking._summary')
        <h2 class="h6 mt-4">Guest</h2>
        <p class="mb-0">{{ $booking->customer?->full_name }} · {{ $booking->customer?->email }} · {{ $booking->customer?->cust_phone }}</p>
    </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <h2 class="h6">Change status</h2>
        @forelse (\App\Models\BookedInfo::TRANSITIONS[(string) $booking->bookingstatus] ?? [] as $to)
            <form method="post" action="{{ route('admin.reservations.status', $booking->booking_number) }}" class="d-inline">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $to }}">
                <button class="btn btn-sm {{ $to === '1' ? 'btn-outline-danger' : 'btn-primary' }}">Mark {{ \App\Models\BookedInfo::STATUS_LABELS[$to] }}</button>
            </form>
        @empty
            <p class="text-muted mb-0">No further changes are possible.</p>
        @endforelse
    </div></div></div>
</div>
@endsection
