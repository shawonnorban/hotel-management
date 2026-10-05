@extends('layouts.admin')
@section('title', $purchase->number)
@section('content')
@php($money = fn ($v) => \App\Support\Money::format($v))
<a href="{{ route('admin.purchases.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Purchases</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-4">
    <h1 class="page-title me-auto">{{ $purchase->number }}</h1>
    @can('purchases.pay')@if ($purchase->due > 0)<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash-coin me-1"></i>Pay supplier</button>@endif @endcan
    @can('purchases.create')<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#returnModal">Return goods</button>@endcan
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4"><div class="card-body row g-3">
            <div class="col-md-4"><div class="small text-body-secondary">Supplier</div><div class="fw-semibold">{{ $purchase->supplier->name }}</div></div>
            <div class="col-md-4"><div class="small text-body-secondary">Date</div><div class="fw-semibold">{{ $purchase->purchase_date->format('d M Y') }}</div></div>
            <div class="col-md-4"><div class="small text-body-secondary">Supplier invoice</div><div class="fw-semibold">{{ $purchase->reference ?: '—' }}</div></div>
            @if ($purchase->notes)<div class="col-12"><div class="small text-body-secondary">Notes</div>{{ $purchase->notes }}</div>@endif
        </div></div>
        <div class="card mb-4"><div class="card-header">Items</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Returned</th><th class="text-end">Unit cost</th><th class="text-end">Total</th></tr></thead><tbody>
            @foreach ($purchase->items as $line)<tr><td>{{ $line->item->name }}</td><td class="text-end">{{ rtrim(rtrim($line->quantity, '0'), '.') }} {{ $line->item->unit->short_code }}</td><td class="text-end">{{ (float) $line->returned > 0 ? rtrim(rtrim($line->returned, '0'), '.') : '—' }}</td><td class="text-end">{{ $money($line->unit_cost) }}</td><td class="text-end">{{ $money($line->line_total) }}</td></tr>@endforeach
            </tbody><tfoot>
                <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">{{ $money($purchase->subtotal) }}</td></tr>
                @if ((float) $purchase->discount > 0)<tr><td colspan="4" class="text-end">Discount</td><td class="text-end">−{{ $money($purchase->discount) }}</td></tr>@endif
                <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">{{ $money($purchase->total) }}</td></tr>
                @if ($purchase->returns->isNotEmpty())<tr><td colspan="4" class="text-end">Returned to supplier</td><td class="text-end">−{{ $money($purchase->returns->sum('total')) }}</td></tr>@endif
                <tr><td colspan="4" class="text-end">Paid</td><td class="text-end">{{ $money($purchase->paid) }}</td></tr>
                <tr class="fw-bold {{ $purchase->due > 0 ? 'table-warning' : '' }}"><td colspan="4" class="text-end">Balance due</td><td class="text-end">{{ $money($purchase->due) }}</td></tr>
            </tfoot></table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4"><div class="card-header">Payments</div><ul class="list-group list-group-flush">
            @forelse ($purchase->payments as $pay)<li class="list-group-item d-flex justify-content-between"><div>{{ $pay->paid_on->format('d M Y') }}<div class="small text-body-secondary">{{ $pay->account->name }}{{ $pay->reference ? ' · '.$pay->reference : '' }}</div></div><strong>{{ $money($pay->amount) }}</strong></li>
            @empty<li class="list-group-item text-body-secondary small">No payments yet.</li>@endforelse
        </ul></div>
        @if ($purchase->returns->isNotEmpty())
        <div class="card"><div class="card-header">Returns</div><ul class="list-group list-group-flush">
            @foreach ($purchase->returns as $r)<li class="list-group-item d-flex justify-content-between"><div>{{ $r->number }} · {{ $r->return_date->format('d M Y') }}<div class="small text-body-secondary">{{ $r->reason }}</div></div><strong>{{ $money($r->total) }}</strong></li>@endforeach
        </ul></div>
        @endif
    </div>
</div>
@can('purchases.pay')
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><form method="post" action="{{ route('admin.purchases.pay', $purchase) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Pay supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-6"><label class="form-label small fw-semibold">Amount (due {{ $money($purchase->due) }})</label><input type="number" step="0.01" min="0.01" max="{{ $purchase->due }}" name="amount" value="{{ $purchase->due }}" class="form-control" required></div>
        <div class="col-6"><label class="form-label small fw-semibold">Date</label><input name="paid_on" class="form-control" data-date value="{{ now()->format('Y-m-d') }}" required></div>
        <div class="col-6"><label class="form-label small fw-semibold">Pay from</label><select name="account" class="form-select" required>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-6"><label class="form-label small fw-semibold">Reference</label><input name="reference" class="form-control" maxlength="80"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Record payment</button></div></form></div></div>
@endcan
@can('purchases.create')
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="post" action="{{ route('admin.purchases.return', $purchase) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Return goods to the supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <table class="table table-sm align-middle"><thead><tr><th>Item</th><th class="text-end">Can return</th><th style="width:150px" class="text-end">Return qty</th></tr></thead><tbody>
            @foreach ($purchase->items as $line)<tr><td>{{ $line->item->name }}</td><td class="text-end">{{ rtrim(rtrim(number_format($line->returnable, 3, '.', ''), '0'), '.') }}</td><td><input type="number" step="0.001" min="0" max="{{ $line->returnable }}" name="qty[{{ $line->id }}]" class="form-control form-control-sm text-end" @disabled($line->returnable <= 0)></td></tr>@endforeach
        </tbody></table>
        <div class="row g-3"><div class="col-md-4"><label class="form-label small fw-semibold">Date</label><input name="return_date" class="form-control" data-date value="{{ now()->format('Y-m-d') }}" required></div>
            <div class="col-md-8"><label class="form-label small fw-semibold">Reason</label><input name="reason" class="form-control" maxlength="250"></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Record return</button></div></form></div></div>
@endcan
@endsection
