@extends('layouts.site')
@section('title', 'Booking '.$booking->booking_number)
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-lg-7">
    <div class="card shadow-sm"><div class="card-body p-4">
        @include('booking._summary')
        <div class="d-flex gap-2 mt-4 no-print">
            <a href="{{ route('booking.invoice', $booking->booking_number) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download invoice</a>
            <a href="{{ route('booking.index') }}" class="btn btn-light ms-auto">All my bookings</a>
        </div>
    </div></div>
</div></div></div>
@endsection
