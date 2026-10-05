@extends('layouts.admin')
@section('title', 'My profile')
@section('content')
<h1 class="page-title mb-4">My profile</h1>
<form method="post" action="{{ route('admin.profile.update') }}" class="card" style="max-width:720px">
    @csrf @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label small fw-semibold">First name</label><input name="firstname" class="form-control" value="{{ old('firstname', $user->firstname) }}" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Last name</label><input name="lastname" class="form-control" value="{{ old('lastname', $user->lastname) }}"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="col-12"><hr class="my-1"><div class="small text-body-secondary">Change password (leave blank to keep the current one)</div></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Current password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">New password</label><input type="password" name="password" class="form-control" autocomplete="new-password"></div>
        <div class="col-md-4"><label class="form-label small fw-semibold">Confirm</label><input type="password" name="password_confirmation" class="form-control" autocomplete="new-password"></div>
    </div>
    <div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Save</button></div>
</form>
@endsection
