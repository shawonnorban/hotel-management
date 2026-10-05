<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    @include('partials.head')
    <title>@yield('title') · {{ $hotelName }}</title>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-art">
        <div class="mb-auto"><a href="{{ route('home') }}" class="text-white text-decoration-none fw-bold fs-5"><i class="bi bi-building me-2"></i>{{ $hotelName }}</a></div>
        <h2>@yield('headline', 'Welcome back')</h2>
        <p class="lead mb-0 opacity-75">@yield('tagline', 'Everything for your stay and your hotel, in one place.')</p>
    </div>
    <div class="auth-form">
        <div class="auth-card">
            @include('partials.flash')
            @yield('content')
        </div>
    </div>
</div>
@include('partials.scripts')
</body>
</html>
