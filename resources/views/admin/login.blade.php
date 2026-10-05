@extends('layouts.auth')
@section('title', 'Staff sign in')
@section('headline', 'Run your hotel with confidence')
@section('tagline', 'Reservations, front desk, accounts and people — together.')
@section('content')
<h1 class="h3 fw-bold mb-1">Staff sign in</h1>
<p class="text-body-secondary mb-4">Use your staff account to open the back office.</p>
<form method="post" action="{{ route('admin.login') }}">
    @csrf
    <div class="mb-3"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus autocomplete="username"></div>
    <div class="mb-4"><label class="form-label small fw-semibold">Password</label><input type="password" name="password" class="form-control form-control-lg" required autocomplete="current-password"></div>
    <button class="btn btn-primary btn-lg w-100">Sign in</button>
</form>
@endsection
