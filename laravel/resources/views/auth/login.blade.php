@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div class="row justify-content-center"><div class="col-md-5"><div class="card shadow-sm"><div class="card-body">
    <h1 class="h4 mb-3">Sign in</h1>
    <form method="post" action="{{ route('login') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-primary w-100">Sign in</button>
    </form>
    <p class="small mt-3 mb-0">No account? <a href="{{ route('register') }}">Register</a></p>
</div></div></div></div>
@endsection
