@extends('layouts.admin')
@section('title', 'Hotel settings')
@section('content')
<div class="mb-4"><h1 class="page-title">Hotel settings</h1><p class="page-sub">Your hotel's profile, pricing defaults and e-mail.</p></div>
<div class="row g-4">
    <div class="col-xl-7">
        <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="card">
            @csrf @method('PUT')
            <div class="card-header">Hotel profile</div>
            <div class="card-body row g-3">
                <div class="col-md-8"><label class="form-label small fw-semibold">Hotel name</label><input name="title" class="form-control" value="{{ old('title', $row->title) }}" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Logo</label><input type="file" name="logo" accept="image/*" class="form-control">@if ($row->logo)<div class="mt-2"><img src="{{ asset($row->logo) }}" alt="" style="height:36px"></div>@endif</div>
                <div class="col-12"><label class="form-label small fw-semibold">Address</label><input name="address" class="form-control" value="{{ old('address', $row->address) }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $row->phone) }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">E-mail (receives booking alerts and website messages)</label><input type="email" name="email" class="form-control" value="{{ old('email', $row->email) }}"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Footer text</label><input name="footer_text" class="form-control" value="{{ old('footer_text', $row->footer_text) }}"></div>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Currency</label><select name="currency" class="form-select">@foreach ($currencies as $c)<option value="{{ $c->currencyid }}" @selected((int) old('currency', $row->currency) === $c->currencyid)>{{ $c->currencyname }} ({{ $c->curr_icon }})</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Service charge (%)</label><input type="number" step="0.01" min="0" max="100" name="servicecharge" class="form-control" value="{{ old('servicecharge', $row->servicecharge) }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Check-in</label><input name="checkintime" class="form-control" data-time value="{{ old('checkintime', substr((string) $row->checkintime, 0, 5)) }}"></div>
                <div class="col-md-2"><label class="form-label small fw-semibold">Check-out</label><input name="checkouttime" class="form-control" data-time value="{{ old('checkouttime', substr((string) $row->checkouttime, 0, 5)) }}"></div>
                <div class="col-12 small text-body-secondary">Taxes are managed under <a href="{{ route('admin.resource.index', 'taxes') }}">Taxes</a>.</div>
            </div>
            <div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Save settings</button></div>
        </form>
    </div>
    <div class="col-xl-5">
        <form method="post" action="{{ route('admin.settings.mail') }}" class="card mb-4">
            @csrf @method('PUT')
            <div class="card-header">E-mail server (SMTP)</div>
            <div class="card-body row g-3">
                <div class="col-md-8"><label class="form-label small fw-semibold">Host</label><input name="host" class="form-control" value="{{ old('host', $mail['host']) }}" placeholder="smtp.example.com"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Port</label><input type="number" name="port" class="form-control" value="{{ old('port', $mail['port']) }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Username</label><input name="username" class="form-control" autocomplete="off" value="{{ old('username', $mail['username']) }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Password</label><input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="{{ $mail['has_password'] ? '•••••••• (saved)' : '' }}"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Security</label><select name="encryption" class="form-select">@foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', 'none' => 'None'] as $k => $l)<option value="{{ $k }}" @selected(old('encryption', $mail['encryption']) === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-8"><label class="form-label small fw-semibold">From address</label><input type="email" name="from_address" class="form-control" value="{{ old('from_address', $mail['from_address']) }}"></div>
                <div class="col-12"><label class="form-label small fw-semibold">From name</label><input name="from_name" class="form-control" value="{{ old('from_name', $mail['from_name']) }}"></div>
            </div>
            <div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Save mail settings</button></div>
        </form>
        <form method="post" action="{{ route('admin.settings.mail-test') }}" class="card">
            @csrf
            <div class="card-header">Send a test e-mail</div>
            <div class="card-body d-flex gap-2"><input type="email" name="to" class="form-control" placeholder="you@example.com" required value="{{ auth('admin')->user()->email }}"><button class="btn btn-outline-primary text-nowrap" data-allow-multi>Send test</button></div>
        </form>
    </div>
</div>
@endsection
