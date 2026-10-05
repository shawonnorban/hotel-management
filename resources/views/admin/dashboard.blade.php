@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="mb-4"><h1 class="page-title">Dashboard</h1><p class="page-sub">{{ now()->format('l, d F Y') }}</p></div>
<div class="row g-3 mb-4">
    @foreach ($stats as $stat)
        <div class="col-6 col-md-4 col-xl-3"><div class="card stat {{ $stat['class'] }}"><span class="icon"><i class="bi {{ $stat['icon'] }}"></i></span><div><div class="label">{{ $stat['label'] }}</div><div class="value">{{ $stat['value'] }}</div></div></div></div>
    @endforeach
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-8"><div class="card h-100"><div class="card-header">Bookings &amp; revenue · last 14 days</div><div class="card-body"><canvas id="trend" height="110"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header">Reservations by status</div><div class="card-body d-flex align-items-center"><canvas id="status" height="200"></canvas></div></div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Latest reservations</h2>@can('reservations.view')<a href="{{ route('admin.reservations.index') }}" class="small">View all</a>@endcan</div>
@include('admin.reservations._table', ['bookings' => $latest])
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(function () {
    var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(15,23,42,.06)';
    var text = dark ? '#9fb0c6' : '#64748b';
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = text;
    var c = @json($chart);
    new Chart(document.getElementById('trend'), {
        data: { labels: c.labels, datasets: [
            { type: 'bar', label: 'Bookings', data: c.bookings, backgroundColor: 'rgba(15,118,110,.75)', borderRadius: 6, yAxisID: 'y' },
            { type: 'line', label: 'Revenue', data: c.revenue, borderColor: '#b8893b', backgroundColor: '#b8893b', tension: .35, yAxisID: 'y1' }
        ]},
        options: { maintainAspectRatio: true, interaction: { mode: 'index', intersect: false }, scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: grid } },
            y1: { beginAtZero: true, position: 'right', grid: { display: false } }
        }, plugins: { legend: { position: 'bottom' } } }
    });
    var s = @json($statusChart);
    new Chart(document.getElementById('status'), {
        type: 'doughnut',
        data: { labels: s.map(function (x) { return x.label; }), datasets: [{ data: s.map(function (x) { return x.count; }), backgroundColor: ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#94a3b8'], borderWidth: 0 }] },
        options: { cutout: '68%', plugins: { legend: { position: 'bottom' } } }
    });
})();
</script>
@endpush
@endsection
