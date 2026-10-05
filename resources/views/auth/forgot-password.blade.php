@extends('layouts.auth')
@section('title', 'Forgot password')
@section('headline', 'Locked out? It happens.')
@section('tagline', 'We will e-mail you a link to choose a new password.')
@section('content')
<h1 class="h3 fw-bold mb-1">Forgot your password?</h1>
<p class="text-body-secondary mb-4">Enter the e-mail address of your account.</p>
<form method="post" action="{{ $broker === 'admins' ? route('admin.password.email') : route('password.email') }}">
    @csrf
    <div class="mb-4"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus></div>
    <button class="btn btn-primary btn-lg w-100">Send reset link</button>
</form>
<p class="small text-center mt-4"><a href="{{ $broker === 'admins' ? route('admin.login') : route('login') }}">Back to sign in</a></p>
@endsection
