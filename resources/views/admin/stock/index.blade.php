@extends('layouts.admin')
@section('title', 'Stock levels')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Stock levels</h1><p class="page-sub">{{ $totals->n }} items · stock value <strong>{{ \App\Support\Money::format($totals->value) }}</strong>@if ($low) · <span class="text-danger">{{ $low }} low</span>@endif</p></div>
    <a href="{{ route('admin.stock.movements') }}" class="btn btn-outline-secondary">Movements</a>
    @can('stock.adjust')<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#opModal" data-op="issue">Issue</button><button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#opModal" data-op="waste">Write off</button><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#opModal" data-op="adjust">Stock count</button>@endcan
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" class="form-control" placeholder="Search item or SKU" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-3"><select name="category" class="form-select"><option value="">All categories</option>@foreach ($categories as $id => $name)<option value="{{ $id }}" @selected((int) ($f['category'] ?? 0) === $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-auto form-check form-switch pt-2 ms-2"><input class="form-check-input" type="checkbox" role="switch" name="low" value="1" id="low" @checked(request()->boolean('low'))><label class="form-check-label" for="low">Low stock only</label></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Item</th><th>Category</th><th class="text-end">On hand</th><th class="text-end">Reorder at</th><th class="text-end">Avg. cost</th><th class="text-end">Value</th></tr></thead><tbody>
    @forelse ($items as $item)
        <tr><td><div class="fw-semibold">{{ $item->name }}</div><div class="small text-body-secondary">{{ $item->sku }}</div></td><td>{{ $item->category?->name ?? '—' }}</td>
            <td class="text-end {{ $item->is_low ? 'text-danger fw-semibold' : '' }}">{{ rtrim(rtrim(number_format($item->stock, 3, '.', ''), '0'), '.') ?: '0' }} {{ $item->unit->short_code }}@if ($item->is_low) <i class="bi bi-exclamation-triangle-fill ms-1" title="Low stock"></i>@endif</td>
            <td class="text-end">{{ (float) $item->reorder_level > 0 ? rtrim(rtrim(number_format($item->reorder_level, 3, '.', ''), '0'), '.') : '—' }}</td>
            <td class="text-end">{{ \App\Support\Money::format($item->avg_cost) }}</td><td class="text-end">{{ \App\Support\Money::format($item->value) }}</td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-box-seam"></i>No items found.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($items->hasPages())<div class="card-footer bg-transparent">{{ $items->links() }}</div>@endif
</div>
@can('stock.adjust')
<div class="modal fade" id="opModal" tabindex="-1"><div class="modal-dialog"><form method="post" id="opForm" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title" id="opTitle">Issue stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label small fw-semibold">Item</label><select name="item" class="form-select" required>@foreach (\App\Models\InventoryItem::with('unit')->where('is_active', true)->orderBy('name')->get() as $i)<option value="{{ $i->id }}">{{ $i->name }} — {{ rtrim(rtrim(number_format($i->stock, 3, '.', ''), '0'), '.') ?: '0' }} {{ $i->unit->short_code }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label small fw-semibold" id="qtyLabel">Quantity</label><input type="number" step="0.001" min="0" name="quantity" id="qtyInput" class="form-control" required></div>
        <div class="col-12"><label class="form-label small fw-semibold">Reason / where it went</label><input name="reason" class="form-control" maxlength="200" required placeholder="e.g. Housekeeping, kitchen, expired"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary">Save</button></div></form></div></div>
@push('scripts')
<script>
document.getElementById('opModal').addEventListener('show.bs.modal', function (e) {
    var op = e.relatedTarget.dataset.op, form = document.getElementById('opForm'), q = document.getElementById('qtyInput');
    var cfg = { issue: ['Issue stock', @json(route('admin.stock.issue')), 'Quantity to issue', 'quantity'], waste: ['Write off stock', @json(route('admin.stock.waste')), 'Quantity lost', 'quantity'], adjust: ['Stock count', @json(route('admin.stock.adjust')), 'Counted quantity on the shelf', 'counted'] }[op];
    document.getElementById('opTitle').textContent = cfg[0]; form.action = cfg[1]; document.getElementById('qtyLabel').textContent = cfg[2]; q.name = cfg[3];
});
</script>
@endpush
@endcan
@endsection
