@extends('layouts.admin')
@section('title', 'Balance sheet')
@section('content')
<h1 class="page-title mb-4">Balance sheet</h1>
@include('admin.accounting.reports._filter', ['asOf' => $asOf])
<div class="row g-4">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Assets</div><div class="card-body">
        <table class="table table-sm">@foreach ($bs['assets'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
            <tr class="fw-bold"><td>Total assets</td><td class="text-end">{{ number_format($bs['total_assets'], 2) }}</td></tr></table>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Liabilities &amp; equity</div><div class="card-body">
        <table class="table table-sm">
            @foreach ($bs['liabilities'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
            <tr class="fw-semibold"><td>Total liabilities</td><td class="text-end">{{ number_format($bs['total_liabilities'], 2) }}</td></tr>
            @foreach ($bs['equity'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
            <tr><td>Current period result</td><td class="text-end">{{ number_format($bs['result'], 2) }}</td></tr>
            <tr class="fw-semibold"><td>Total equity</td><td class="text-end">{{ number_format($bs['total_equity'], 2) }}</td></tr>
            <tr class="fw-bold border-top"><td>Total liabilities &amp; equity</td><td class="text-end">{{ number_format($bs['total_liabilities'] + $bs['total_equity'], 2) }}</td></tr>
        </table>
    </div></div></div>
</div>
@if (abs($bs['total_assets'] - ($bs['total_liabilities'] + $bs['total_equity'])) >= 0.01)<div class="alert alert-danger mt-3">The balance sheet does not balance. Check the opening balances of your accounts.</div>@endif
@endsection
