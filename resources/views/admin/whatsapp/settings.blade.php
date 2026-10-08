@extends('layouts.admin')
@section('title', 'WhatsApp setting')
@section('content')
<div class="mb-4"><h1 class="page-title">WhatsApp setting</h1><p class="page-sub">Click-to-chat links work without any set-up. To send messages from the system, connect the WhatsApp Cloud API.</p></div>
<form method="post" action="{{ route('admin.whatsapp.update') }}" class="card" style="max-width:720px"><div class="card-body row g-3">@csrf @method('PUT')
    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="en" @checked(old('enabled', $enabled))><label class="form-check-label" for="en">Send messages from the system (Cloud API)</label></div>@error('enabled')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label small fw-semibold">Phone-number ID</label><input name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $phoneNumberId) }}" inputmode="numeric">@error('phone_number_id')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label small fw-semibold">Access token</label><input type="password" name="token" class="form-control" autocomplete="new-password" placeholder="{{ $hasToken ? '•••••••• (saved — leave blank to keep)' : '' }}">@error('token')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Default country code</label><input name="country_code" class="form-control" value="{{ old('country_code', $countryCode) }}" placeholder="880" inputmode="numeric"><div class="form-text">Used when a number starts with 0.</div>@error('country_code')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-12"><label class="form-label small fw-semibold">Greeting for click-to-chat</label><input name="greeting" class="form-control" value="{{ old('greeting', $greeting) }}"><div class="form-text">Placeholders: {guest}, {hotel}, {booking}.</div></div>
</div><div class="card-footer bg-transparent"><button class="btn btn-primary">Save</button></div></form>
@endsection
