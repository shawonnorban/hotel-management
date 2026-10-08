@extends('layouts.admin')
@section('title', 'Purchase returns')
@section('content')
<div class="mb-4"><h1 class="page-title">Purchase returns</h1><p class="page-sub">Goods sent back to suppliers. Start a return from the purchase itself.</p></div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Return, purchase or supplier" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Return</th><th>Date</th><th>Purchase</th><th>Supplier</th><th class="text-end">Credit</th><th>Reason</th><th></th></tr></thead><tbody>
    @forelse ($returns as $r)
        <tr><td class="fw-semibold">{{ $r->number }}</td><td>{{ $r->return_date->format('d M Y') }}</td>
            <td><a class="text-decoration-none" href="{{ route('admin.purchases.show', $r->purchase) }}">{{ $r->purchase->number }}</a></td><td>{{ $r->purchase->supplier->name }}</td>
            <td class="text-end">{{ \App\Support\Money::format($r->total) }}</td><td class="text-body-secondary">{{ $r->reason }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.returns.invoice', $r) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Invoice</a></td></tr>
    @empty
        <tr><td colspan="7"><div class="empty"><i class="bi bi-arrow-return-left"></i>No returns yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($returns->hasPages())<div class="card-footer bg-transparent">{{ $returns->links() }}</div>@endif
</div>
@endsection
