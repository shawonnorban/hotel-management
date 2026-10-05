@extends('layouts.site')
@section('title', 'Contact us')
@section('content')
<div class="container">
    <h1 class="section-title mb-4">Contact us</h1>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card h-100"><div class="card-body p-4">
                <h2 class="h5 mb-3">Find us</h2>
                @php($contacts = [['bi-geo-alt', \App\Support\Settings::get('address')], ['bi-telephone', \App\Support\Settings::get('phone')], ['bi-envelope', \App\Support\Settings::get('email')], ['bi-clock', \App\Support\Settings::get('checkintime') ? 'Check-in from '.\App\Support\Settings::get('checkintime').' · Check-out until '.\App\Support\Settings::get('checkouttime') : null]])
                @foreach ($contacts as [$icon, $value])
                    @if ($value)<p class="d-flex gap-3"><span class="feature-icon" style="width:40px;height:40px;font-size:1.1rem"><i class="bi {{ $icon }}"></i></span><span class="align-self-center">{{ $value }}</span></p>@endif
                @endforeach
            </div></div>
        </div>
        <div class="col-lg-7">
            <form method="post" action="{{ route('contact.send') }}" class="card shadow-sm"><div class="card-body p-4 row g-3">
                @csrf
                <div class="d-none"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Your name</label><input name="name" class="form-control" value="{{ old('name', auth('customer')->user()?->full_name) }}" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', auth('customer')->user()?->email) }}" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Subject</label><input name="subject" class="form-control" value="{{ old('subject') }}"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Message</label><textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea></div>
                <div class="col-12"><button class="btn btn-primary px-4">Send message</button></div>
            </div></form>
        </div>
    </div>
</div>
@endsection
