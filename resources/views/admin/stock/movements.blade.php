@extends('layouts.admin')
@section('title', 'Stock movements')
@section('content')
<a href="{{ route('admin.stock.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Stock levels</a>
<h1 class="page-title mt-1 mb-4">Stock movements</h1>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><select name="item" class="form-select" data-search><option value="">All items</option>@foreach ($items as $id => $name)<option value="{{ $id }}" @selected((int) ($f['item'] ?? 0) === $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="type" class="form-select"><option value="">All types</option>@foreach (\App\Models\StockMovement::TYPES as $k => $l)<option value="{{ $k }}" @selected(($f['type'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>When</th><th>Item</th><th>Type</th><th class="text-end">Quantity</th><th class="text-end">Balance</th><th>Note</th><th>By</th></tr></thead><tbody>
    @forelse ($movements as $m)
        <tr><td class="text-nowrap">{{ $m->moved_at->format('d M Y H:i') }}</td><td>{{ $m->item->name }}</td><td>{{ \App\Models\StockMovement::TYPES[$m->type] }}</td>
            <td class="text-end {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($m->quantity, 3, '.', ''), '0'), '.') }} {{ $m->item->unit->short_code }}</td>
            <td class="text-end">{{ rtrim(rtrim(number_format($m->balance, 3, '.', ''), '0'), '.') ?: '0' }}</td><td class="text-body-secondary">{{ $m->note }}</td><td class="text-body-secondary">{{ $m->user?->full_name ?? 'System' }}</td></tr>
    @empty
        <tr><td colspan="7"><div class="empty"><i class="bi bi-arrow-left-right"></i>No movements yet.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($movements->hasPages())<div class="card-footer bg-transparent">{{ $movements->links() }}</div>@endif
</div>
@endsection
