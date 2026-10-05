@extends('layouts.site')
@section('title', 'Booking '.$booking->booking_number)
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-lg-7">
    <div class="card shadow-sm"><div class="card-body p-4">
        @include('booking._summary')
        <div class="d-flex gap-2 mt-4 no-print">
            @if ($booking->is_open && $booking->balance > 0)<a href="{{ route('booking.checkout', $booking->booking_number) }}" class="btn btn-primary"><i class="bi bi-credit-card me-1"></i>Pay balance</a>@endif
            <a href="{{ route('booking.invoice', $booking->booking_number) }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf me-1"></i>Download invoice</a>
            @if ((string) $booking->bookingstatus === '0' && (float) $booking->paid_amount == 0)<form method="post" action="{{ route('booking.cancel', $booking->booking_number) }}" data-confirm="Cancel this booking?">@csrf<button class="btn btn-outline-danger">Cancel booking</button></form>@endif
            <a href="{{ route('booking.index') }}" class="btn btn-light ms-auto">All my bookings</a>
        </div>
    </div></div>
</div></div></div>
@endsection
