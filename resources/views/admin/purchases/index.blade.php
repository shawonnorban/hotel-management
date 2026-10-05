@extends('layouts.admin')
@section('title', 'Purchases')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Purchases</h1><p class="page-sub">Owed to suppliers: <strong>{{ \App\Support\Money::format($owed) }}</strong></p></div>
    @can('purchases.create')<a href="{{ route('admin.purchases.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New purchase</a>@endcan
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Number, supplier or invoice no." value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-3"><select name="supplier" class="form-select"><option value="">All suppliers</option>@foreach ($suppliers as $id => $name)<option value="{{ $id }}" @selected((int) ($f['supplier'] ?? 0) === $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-auto form-check form-switch pt-2 ms-2"><input class="form-check-input" type="checkbox" role="switch" name="unpaid" value="1" id="unpaid" @checked(request()->boolean('unpaid'))><label class="form-check-label" for="unpaid">Unpaid only</label></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Purchase</th><th>Date</th><th>Supplier</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th></tr></thead>
    <tbody>
    @forelse ($purchases as $p)
        <tr><td><a class="fw-semibold text-decoration-none" href="{{ route('admin.purchases.show', $p) }}">{{ $p->number }}</a>@if ($p->reference)<div class="small text-body-secondary">{{ $p->reference }}</div>@endif</td>
            <td>{{ $p->purchase_date->format('d M Y') }}</td><td>{{ $p->supplier->name }}</td>
            <td class="text-end">{{ \App\Support\Money::format($p->total) }}</td><td class="text-end">{{ \App\Support\Money::format($p->paid) }}</td>
            <td class="text-end {{ $p->due > 0 ? 'text-danger fw-semibold' : 'text-body-secondary' }}">{{ \App\Support\Money::format($p->due) }}</td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-cart"></i>No purchases yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($purchases->hasPages())<div class="card-footer bg-transparent">{{ $purchases->links() }}</div>@endif
</div>
@endsection
