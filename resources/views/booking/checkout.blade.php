@extends('layouts.site')
@section('title', 'Checkout')
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-lg-7">
    <h1 class="section-title mb-4">Confirm your booking</h1>
    <div class="card shadow-sm mb-4"><div class="card-body p-4">@include('booking._summary')</div></div>
    <form method="post" action="{{ route('booking.pay', $booking->booking_number) }}" class="card shadow-sm"><div class="card-body p-4">
        @csrf
        <h2 class="h5 mb-3">How would you like to pay?</h2>
        <div class="d-grid gap-2 mb-4">
            @forelse ($methods as $method)
                <label class="border rounded-3 p-3 d-flex align-items-center gap-3" for="m{{ $method->payment_method_id }}" style="cursor:pointer">
                    <input class="form-check-input mt-0" type="radio" name="method" id="m{{ $method->payment_method_id }}" value="{{ $method->payment_method_id }}" @checked($loop->first) required>
                    <span class="fw-semibold">{{ $method->payment_method }}</span>
                    <span class="ms-auto small text-body-secondary">{{ in_array((int) $method->payment_method_id, $offline) ? 'Pay at the hotel / by transfer' : 'Pay online now' }}</span>
                </label>
            @empty
                <div class="alert alert-warning mb-0">No payment method is available right now. Please contact the hotel.</div>
            @endforelse
        </div>
        <button class="btn btn-primary btn-lg w-100" @disabled($methods->isEmpty())>Confirm booking</button>
    </div></form>
</div></div></div>
@endsection
