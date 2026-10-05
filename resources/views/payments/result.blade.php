@extends('layouts.site')
@section('title', $ok ? 'Payment successful' : 'Payment')
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">
    <div class="card shadow-sm text-center"><div class="card-body p-5">
        <div class="feature-icon mx-auto mb-3" style="width:72px;height:72px;font-size:2rem;{{ $ok ? '' : 'background:#fde8e8;color:#dc2626' }}"><i class="bi {{ $ok ? 'bi-check-lg' : 'bi-x-lg' }}"></i></div>
        <h1 class="h4">{{ $ok ? 'Payment successful' : 'Payment not completed' }}</h1>
        <p class="text-body-secondary">{{ $message }}</p>
        @if (! empty($booking))
            <p class="small mb-4">Booking <strong>#{{ $booking->booking_number }}</strong></p>
            <a href="{{ route('booking.show', $booking->booking_number) }}" class="btn btn-primary">View my booking</a>
            @unless ($ok)<a href="{{ route('booking.checkout', $booking->booking_number) }}" class="btn btn-outline-primary ms-2">Try again</a>@endunless
        @else
            <a href="{{ route('home') }}" class="btn btn-primary">Back to the website</a>
        @endif
    </div></div>
</div></div></div>
@endsection
