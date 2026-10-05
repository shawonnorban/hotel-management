@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="row justify-content-center"><div class="col-md-6"><div class="card shadow-sm"><div class="card-body">
    <h1 class="h4 mb-3">Create an account</h1>
    <form method="post" action="{{ route('register') }}" class="row g-3">
        @csrf
        <div class="col-6"><label class="form-label">First name</label><input name="firstname" value="{{ old('firstname') }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Last name</label><input name="lastname" value="{{ old('lastname') }}" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Phone</label><input name="phone" value="{{ old('phone') }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="6" required></div>
        <div class="col-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
        <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="terms" value="1" id="terms" required><label class="form-check-label" for="terms">I agree to the terms of service</label></div>
        <div class="col-12"><button class="btn btn-primary w-100">Register</button></div>
    </form>
</div></div></div></div>
@endsection
