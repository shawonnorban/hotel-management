@extends('layouts.app')
@section('title', 'Checkout')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7"><div class="card shadow-sm"><div class="card-body">
    <h1 class="h4 mb-3">Confirm your booking</h1>
    @include('booking._summary')
    <form method="post" action="{{ route('booking.pay', $booking->booking_number) }}">
        @csrf
        <h2 class="h6">Payment method</h2>
        @foreach ($methods as $method)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="method" id="m{{ $method->payment_method_id }}" value="{{ $method->payment_method_id }}" @checked($loop->first) required>
                <label class="form-check-label" for="m{{ $method->payment_method_id }}">
                    {{ $method->payment_method }}
                    @unless (in_array($method->payment_method_id, $offline)) <span class="text-muted small">(coming soon)</span> @endunless
                </label>
            </div>
        @endforeach
        <button class="btn btn-primary mt-3">Confirm booking</button>
    </form>
</div></div></div></div>
@endsection
