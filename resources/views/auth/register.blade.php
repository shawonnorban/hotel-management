@extends('layouts.auth')
@section('title', 'Create account')
@section('headline', 'Book faster next time')
@section('tagline', 'An account keeps your bookings and details in one place.')
@section('content')
<h1 class="h3 fw-bold mb-1">Create your account</h1>
<p class="text-body-secondary mb-4">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
<form method="post" action="{{ route('register') }}" class="row g-3">
    @csrf
    <div class="col-6"><label class="form-label small fw-semibold">First name</label><input name="firstname" value="{{ old('firstname') }}" class="form-control" required></div>
    <div class="col-6"><label class="form-label small fw-semibold">Last name</label><input name="lastname" value="{{ old('lastname') }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required autocomplete="username"></div>
    <div class="col-12"><label class="form-label small fw-semibold">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
    <div class="col-6"><label class="form-label small fw-semibold">Password</label><input type="password" name="password" class="form-control" required autocomplete="new-password"></div>
    <div class="col-6"><label class="form-label small fw-semibold">Confirm</label><input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password"></div>
    <div class="col-12 form-text mt-0">At least 8 characters with letters and numbers.</div>
    <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="terms" value="1" id="terms" required><label class="form-check-label small" for="terms">I agree to the terms of service and privacy policy</label></div>
    <div class="col-12"><button class="btn btn-primary btn-lg w-100">Create account</button></div>
</form>
@endsection
