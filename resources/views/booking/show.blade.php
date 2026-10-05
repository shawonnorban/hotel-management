@extends('layouts.app')
@section('title', 'Booking '.$booking->booking_number)
@section('content')
<div class="row justify-content-center"><div class="col-lg-7"><div class="card shadow-sm"><div class="card-body">
    <h1 class="h4 mb-3">Booking {{ $booking->booking_number }}</h1>
    @include('booking._summary')
    <a href="{{ route('booking.index') }}">← All my bookings</a>
</div></div></div></div>
@endsection
