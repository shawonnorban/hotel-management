@extends('layouts.admin')
@section('title', 'Purchases report')
@section('content')
<h1 class="page-title mb-4">Purchases report</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Purchased</div><div class="fs-4 fw-bold">{{ \App\Support\Money::format($total) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Still owed</div><div class="fs-4 fw-bold text-danger">{{ \App\Support\Money::format($due) }}</div></div></div></div>
</div>
<div class="row g-4"><div class="col-lg-4"><div class="card"><div class="card-header">By supplier</div><table class="table table-sm mb-0">@forelse ($bySupplier as $name => $s)<tr><td>{{ $name }}<div class="small text-body-secondary">{{ $s['count'] }} purchase(s)</div></td><td class="text-end">{{ \App\Support\Money::format($s['total']) }}<div class="small text-danger">{{ $s['due'] > 0 ? 'owed '.\App\Support\Money::format($s['due']) : '' }}</div></td></tr>@empty<tr><td class="text-body-secondary p-3">Nothing purchased.</td></tr>@endforelse</table></div></div>
<div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Purchase</th><th>Date</th><th>Supplier</th><th class="text-end">Total</th><th class="text-end">Due</th></tr></thead><tbody>
    @foreach ($rows as $p)<tr><td><a class="text-decoration-none" href="{{ route('admin.purchases.show', $p) }}">{{ $p->number }}</a></td><td>{{ $p->purchase_date->format('d M Y') }}</td><td>{{ $p->supplier->name }}</td><td class="text-end">{{ \App\Support\Money::format($p->total) }}</td><td class="text-end">{{ \App\Support\Money::format($p->due) }}</td></tr>@endforeach
    </tbody></table></div></div></div></div>
@endsection
