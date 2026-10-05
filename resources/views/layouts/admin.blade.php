<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    @include('partials.head')
    <title>@yield('title', 'Dashboard') · {{ $hotelName }}</title>
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            <span class="logo"><i class="bi bi-building"></i></span>
            <span class="text-truncate">{{ $hotelName }}</span>
        </a>
        @foreach ($adminMenu as $group => $items)
            <div class="group">{{ $group }}</div>
            <nav class="nav flex-column">
                @foreach ($items as $item)
                    <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}"><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
                @endforeach
            </nav>
        @endforeach
        <div class="mt-auto p-3 small text-center" style="color:#5f7584">v{{ config('hotel.version') }}</div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
            <div class="flex-grow-1"></div>
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex gap-1" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View website</a>
            <button class="btn btn-sm btn-outline-secondary" data-theme-toggle title="Toggle dark mode"><i class="bi bi-circle-half"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <span class="rounded-circle bg-brand-soft text-brand d-grid" style="width:28px;height:28px;place-items:center"><i class="bi bi-person-fill"></i></span>
                    <span class="d-none d-md-inline">{{ auth('admin')->user()->full_name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-body-secondary">{{ auth('admin')->user()->roles->pluck('name')->implode(', ') ?: 'No role' }}</span></li>
                    <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="bi bi-person-gear me-2"></i>My profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="post" action="{{ route('admin.logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                </ul>
            </div>
        </header>
        <main class="page">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@include('partials.scripts')
</body>
</html>
