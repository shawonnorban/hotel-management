@extends('layouts.auth')
@section('title', 'Sign in')
@section('headline', 'Welcome back')
@section('tagline', 'Sign in to manage your bookings.')
@section('content')
<h1 class="h3 fw-bold mb-1">Sign in</h1>
<p class="text-body-secondary mb-4">New here? <a href="{{ route('register') }}">Create an account</a></p>
<form method="post" action="{{ route('login') }}">
    @csrf
    <div class="mb-3"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus autocomplete="username"></div>
    <div class="mb-2"><label class="form-label small fw-semibold d-flex justify-content-between">Password <a href="{{ route('password.request') }}" class="fw-normal">Forgot?</a></label><input type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-lg w-100 mt-3">Sign in</button>
</form>
<p class="small text-center mt-4"><a href="{{ route('home') }}"><i class="bi bi-arrow-left"></i> Back to the website</a></p>
@endsection
