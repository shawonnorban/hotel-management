@extends('layouts.admin')
@section('title', 'Stock report')
@section('content')
<h1 class="page-title mb-4">Stock report</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
@php $q = fn ($v) => rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') ?: '0'; @endphp
<div class="row g-3 mb-4"><div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Stock value now</div><div class="fs-4 fw-bold">{{ \App\Support\Money::format($totalValue) }}</div></div></div></div></div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Item</th><th class="text-end">Purchased</th><th class="text-end">Returned</th><th class="text-end">Issued</th><th class="text-end">Wasted</th><th class="text-end">Adjusted</th><th class="text-end">On hand</th><th class="text-end">Value</th></tr></thead><tbody>
    @foreach ($rows as $r)
        <tr><td>{{ $r['item']->name }} <span class="small text-body-secondary">{{ $r['item']->unit->short_code }}</span></td><td class="text-end">{{ $q($r['purchased']) }}</td><td class="text-end">{{ $q($r['returned']) }}</td><td class="text-end">{{ $q($r['issued']) }}</td><td class="text-end">{{ $q($r['wasted']) }}</td><td class="text-end">{{ $q($r['adjusted']) }}</td><td class="text-end fw-semibold">{{ $q($r['on_hand']) }}</td><td class="text-end">{{ \App\Support\Money::format($r['value']) }}</td></tr>
    @endforeach
    </tbody></table></div></div>
@endsection
