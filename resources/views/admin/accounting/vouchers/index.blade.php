@extends('layouts.admin')
@section('title', 'Vouchers')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Vouchers</h1><p class="page-sub">{{ number_format($entries->total()) }} journal entries</p></div>
    @can('accounts.manage')
    <div class="dropdown"><button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-plus-lg me-1"></i>New voucher</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', ['type' => 'receipt']) }}">Receipt (money in)</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', ['type' => 'payment']) }}">Payment (money out)</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', ['type' => 'contra']) }}">Contra (cash ⇄ bank)</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.vouchers.create', ['type' => 'journal']) }}">Journal</a></li>
        </ul></div>
    @endcan
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-3"><input name="q" class="form-control" placeholder="Number or narration" value="{{ $f['q'] ?? '' }}"></div>
    <div class="col-md-2"><select name="type" class="form-select"><option value="">All types</option>@foreach (\App\Models\JournalEntry::TYPES as $k => $l)<option value="{{ $k }}" @selected(($f['type'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="from" class="form-control" data-date placeholder="From" value="{{ $f['from'] ?? '' }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date placeholder="To" value="{{ $f['to'] ?? '' }}"></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>Voucher</th><th>Date</th><th>Type</th><th>Narration</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($entries as $e)
        <tr class="{{ $e->status === 'void' ? 'text-decoration-line-through text-body-tertiary' : '' }}">
            <td><a class="fw-semibold text-decoration-none" href="{{ route('admin.vouchers.show', $e) }}">{{ $e->number }}</a></td>
            <td>{{ $e->entry_date->format('d M Y') }}</td><td>{{ \App\Models\JournalEntry::TYPES[$e->type] ?? ucfirst($e->type) }}</td>
            <td>{{ \Illuminate\Support\Str::limit($e->narration, 70) }}</td><td class="text-end">{{ number_format($e->total, 2) }}</td>
            <td><span class="badge text-bg-{{ $e->status === 'posted' ? 'success' : 'secondary' }}">{{ ucfirst($e->status) }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6"><div class="empty"><i class="bi bi-journal-text"></i>No vouchers found.</div></td></tr>
    @endforelse
    </tbody></table></div>
    @if ($entries->hasPages())<div class="card-footer bg-transparent">{{ $entries->links() }}</div>@endif
</div>
@endsection
