@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
<div class="mb-4"><h1 class="page-title">Reports</h1><p class="page-sub">Print them or export to a spreadsheet.</p></div>
<div class="row g-3">
@foreach ([
    ['admin.reports.bookings', 'bi-calendar-check', 'Bookings', 'Every reservation arriving in a period, with totals and balances.'],
    ['admin.reports.receipts', 'bi-receipt', 'Guest receipts', 'Money received from guests, by payment method.'],
    ['admin.reports.occupancy', 'bi-door-open', 'Occupancy', 'Rooms sold per day and the occupancy rate.'],
    ['admin.reports.purchases', 'bi-cart', 'Purchases', 'What you bought, from whom, and what is still owed.'],
    ['admin.stock.index', 'bi-boxes', 'Stock valuation', 'Stock on hand and its value (Purchasing → Stock levels).'],
    ['admin.accounting.income-statement', 'bi-graph-up-arrow', 'Income statement', 'Profit and loss for a period.'],
    ['admin.accounting.balance-sheet', 'bi-bar-chart-steps', 'Balance sheet', 'Assets, liabilities and equity.'],
    ['admin.accounting.trial-balance', 'bi-calculator', 'Trial balance', 'Every account\'s balance on a date.'],
] as [$route, $icon, $title, $text])
    <div class="col-md-6 col-xl-3"><a href="{{ route($route) }}" class="card h-100 text-decoration-none text-reset"><div class="card-body"><div class="feature-icon mb-3"><i class="bi {{ $icon }}"></i></div><h2 class="h6">{{ $title }}</h2><p class="small text-body-secondary mb-0">{{ $text }}</p></div></a></div>
@endforeach
</div>
@endsection
