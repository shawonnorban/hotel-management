@extends('layouts.site')
@section('title', 'My profile')
@section('content')
<div class="container"><div class="row justify-content-center"><div class="col-lg-7">
    <h1 class="section-title mb-4">My profile</h1>
    <form method="post" action="{{ route('account.update') }}" class="card shadow-sm">
        @csrf @method('PUT')
        <div class="card-body p-4 row g-3">
            <div class="col-md-6"><label class="form-label small fw-semibold">First name</label><input name="firstname" class="form-control" value="{{ old('firstname', $guest->firstname) }}" required></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Last name</label><input name="lastname" class="form-control" value="{{ old('lastname', $guest->lastname) }}" required></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $guest->email) }}" required></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="cust_phone" class="form-control" value="{{ old('cust_phone', $guest->cust_phone) }}" required></div>
            <div class="col-12"><label class="form-label small fw-semibold">Address</label><input name="address" class="form-control" value="{{ old('address', $guest->address) }}"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">City</label><input name="city" class="form-control" value="{{ old('city', $guest->city) }}"></div>
            <div class="col-md-6"><label class="form-label small fw-semibold">Country</label><input name="country" class="form-control" value="{{ old('country', $guest->country) }}"></div>
            <div class="col-12"><hr class="my-1"><div class="small text-body-secondary">Change password (leave blank to keep the current one)</div></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Current password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">New password</label><input type="password" name="password" class="form-control" autocomplete="new-password"></div>
            <div class="col-md-4"><label class="form-label small fw-semibold">Confirm</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
        </div>
        <div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Save changes</button></div>
    </form>
</div></div></div>
@endsection
