<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    @include('partials.head')
    <title>@yield('title', 'Welcome') · {{ $hotelName }}</title>
    @hasSection('description')<meta name="description" content="@yield('description')">@endif
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg site-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}"><i class="bi bi-building me-2"></i>{{ $hotelName }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-label="Menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}">Rooms</a></li>
                @foreach ($sitePages ?? [] as $navPage)
                    <li class="nav-item"><a class="nav-link {{ request()->is('page/'.$navPage->slug) ? 'active' : '' }}" href="{{ route('pages.show', $navPage->slug) }}">{{ $navPage->title }}</a></li>
                @endforeach
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact*') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" data-theme-toggle title="Dark mode"><i class="bi bi-circle-half"></i></button>
                @auth('customer')
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle me-1"></i>{{ auth('customer')->user()->firstname }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('booking.index') }}"><i class="bi bi-calendar-check me-2"></i>My bookings</a></li>
                            <li><a class="dropdown-item" href="{{ route('account.edit') }}"><i class="bi bi-person-gear me-2"></i>My profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                        </ul>
                    </div>
                @else
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Sign in</a>
                    <a class="btn btn-sm btn-primary" href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
@yield('hero')
<main class="flex-grow-1 @yield('main-class', 'py-5')">
    <div class="container">@include('partials.flash')</div>
    @yield('content')
</main>
<footer class="site-footer mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="h5 text-white"><i class="bi bi-building me-2"></i>{{ $hotelName }}</div>
                <p class="small">{{ \App\Support\Settings::get('footer_text') ?: 'Comfortable rooms, warm service and an easy booking experience.' }}</p>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-white fw-semibold mb-2">Explore</div>
                <ul class="list-unstyled small mb-0">
                    <li><a href="{{ route('rooms.index') }}">Rooms</a></li>
                    @foreach ($sitePages ?? [] as $navPage)<li><a href="{{ route('pages.show', $navPage->slug) }}">{{ $navPage->title }}</a></li>@endforeach
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-white fw-semibold mb-2">Get in touch</div>
                <ul class="list-unstyled small mb-0">
                    @if ($v = \App\Support\Settings::get('address'))<li><i class="bi bi-geo-alt me-2"></i>{{ $v }}</li>@endif
                    @if ($v = \App\Support\Settings::get('phone'))<li><i class="bi bi-telephone me-2"></i>{{ $v }}</li>@endif
                    @if ($v = \App\Support\Settings::get('email'))<li><i class="bi bi-envelope me-2"></i>{{ $v }}</li>@endif
                </ul>
            </div>
        </div>
        <hr class="border-secondary-subtle my-4">
        <div class="small d-flex flex-wrap justify-content-between gap-2"><span>&copy; {{ date('Y') }} {{ $hotelName }}. All rights reserved.</span><span>@foreach ($footerPages ?? [] as $fp)<a class="me-3" href="{{ route('pages.show', $fp->slug) }}">{{ $fp->title }}</a>@endforeach</span></div>
    </div>
</footer>
@include('partials.scripts')
</body>
</html>
