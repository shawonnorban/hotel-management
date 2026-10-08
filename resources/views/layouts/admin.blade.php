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
            @php($gid = 'g_'.\Illuminate\Support\Str::slug($group, '_'))
            @php($hasActive = collect($items)->contains('active', true))
            @if (count($items) === 1 && $group === 'Dashboard')
                <nav class="nav flex-column mt-2"><a class="nav-link {{ $items[0]['active'] ? 'active' : '' }}" href="{{ $items[0]['url'] }}"><i class="bi {{ $items[0]['icon'] }}"></i><span>{{ $items[0]['label'] }}</span></a></nav>
            @else
                <button type="button" class="group group-toggle {{ $hasActive ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#{{ $gid }}" data-group="{{ $gid }}" aria-expanded="{{ $hasActive ? 'true' : 'false' }}">
                    <span>{{ $group }}</span><i class="bi bi-chevron-down caret"></i>
                </button>
                <div id="{{ $gid }}" class="collapse {{ $hasActive ? 'show' : '' }}">
                    <nav class="nav flex-column">
                        @foreach ($items as $item)
                            <a class="nav-link {{ $item['active'] ? 'active' : '' }}" href="{{ $item['url'] }}"><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
                        @endforeach
                    </nav>
                </div>
            @endif
        @endforeach
        <div class="mt-auto p-3 small text-center" style="color:var(--sb-muted)">v{{ config('hotel.version') }}</div>
    </aside>
    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" data-sidebar-toggle aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
            @if (collect($quickLinks)->contains(fn ($l) => $l[0] === 'Arrivals today'))
                <form method="get" action="{{ route('admin.reservations.index') }}" class="d-none d-xl-block" role="search"><div class="input-group input-group-sm"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" class="form-control" style="width:200px" placeholder="Find booking, guest, phone…" aria-label="Find a reservation"></div></form>
            @endif
            <div class="d-none d-lg-flex gap-1 quick-links">
                @foreach ($quickLinks as [$label, $icon, $url, $class])<a href="{{ $url }}" class="btn btn-sm {{ $class }}" title="{{ $label }}" data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="{{ $label }}"><i class="bi {{ $icon }}"></i></a>@endforeach
            </div>
            @if ($quickLinks)
            <div class="dropdown d-lg-none"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown" aria-label="Quick links"><i class="bi bi-lightning-charge"></i></button>
                <ul class="dropdown-menu">@foreach ($quickLinks as [$label, $icon, $url])<li><a class="dropdown-item" href="{{ $url }}"><i class="bi {{ $icon }} me-2"></i>{{ $label }}</a></li>@endforeach</ul></div>
            @endif
            <div class="flex-grow-1"></div>
            <span class="d-none d-xl-inline small text-body-secondary fw-semibold text-nowrap" id="topClock" aria-label="Current time"></span>
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
