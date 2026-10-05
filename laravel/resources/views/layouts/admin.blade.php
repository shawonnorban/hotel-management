<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ $hotelName }} admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-md navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">{{ $hotelName }} · Admin</a>
        <ul class="navbar-nav me-auto flex-row gap-3">
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.reservations.index') }}">Reservations</a></li>
            <li class="nav-item"><a class="nav-link" href="{{ route('admin.rooms.index') }}">Rooms</a></li>
        </ul>
        <span class="navbar-text me-3">{{ auth('admin')->user()->full_name }}</span>
        <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-outline-light btn-sm">Sign out</button></form>
    </div>
</nav>
<main class="container-fluid py-4">
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
