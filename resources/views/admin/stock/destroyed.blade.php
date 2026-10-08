@extends('layouts.admin')
@section('title', 'Destroyed list')
@section('content')
<a href="{{ route('admin.stock.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Stock levels</a>
<div class="mt-1 mb-4"><h1 class="page-title">Destroyed list</h1><p class="page-sub">Stock written off as damaged, expired or lost · total loss <strong class="text-danger">{{ \App\Support\Money::format($loss) }}</strong></p></div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><select name="item" class="form-select" data-search><option value="">All items</option>@foreach ($items as $id => $name)<option value="{{ $id }}" @selected((int) ($f['item'] ?? 0) === $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Date</th><th>Item</th><th class="text-end">Quantity</th><th class="text-end">Loss</th><th>Reason</th><th>By</th></tr></thead><tbody>
    @forelse ($movements as $m)
        <tr><td class="text-nowrap">{{ $m->moved_at->format('d M Y') }}</td><td>{{ $m->item->name }}</td>
            <td class="text-end">{{ rtrim(rtrim(number_format(abs($m->quantity), 3, '.', ''), '0'), '.') }} {{ $m->item->unit->short_code }}</td>
            <td class="text-end text-danger">{{ \App\Support\Money::format(abs($m->quantity) * $m->unit_cost) }}</td><td class="text-body-secondary">{{ $m->note }}</td><td class="text-body-secondary">{{ $m->user?->full_name ?? 'System' }}</td></tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-trash3"></i>Nothing has been written off.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($movements->hasPages())<div class="card-footer bg-transparent">{{ $movements->links() }}</div>@endif
</div>
@endsection
