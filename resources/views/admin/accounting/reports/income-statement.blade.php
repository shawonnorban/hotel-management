@extends('layouts.admin')
@section('title', 'Income statement')
@section('content')
<h1 class="page-title mb-4">Income statement</h1>
@include('admin.accounting.reports._filter', ['from' => $from, 'to' => $to, 'csv' => true])
<div class="card"><div class="card-body">
    <h2 class="h6 text-uppercase text-body-secondary">Income</h2>
    <table class="table table-sm">@foreach ($is['income'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
        <tr class="fw-semibold"><td>Total income</td><td class="text-end">{{ number_format($is['total_income'], 2) }}</td></tr></table>
    <h2 class="h6 text-uppercase text-body-secondary mt-4">Expenses</h2>
    <table class="table table-sm">@foreach ($is['expenses'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
        <tr class="fw-semibold"><td>Total expenses</td><td class="text-end">{{ number_format($is['total_expenses'], 2) }}</td></tr></table>
    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3"><span class="h5 mb-0">{{ $is['net'] >= 0 ? 'Net profit' : 'Net loss' }}</span><span class="h4 mb-0 {{ $is['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($is['net'], 2) }}</span></div>
</div></div>
@endsection
