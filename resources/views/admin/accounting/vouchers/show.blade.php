@extends('layouts.admin')
@section('title', $entry->number)
@section('content')
<a href="{{ route('admin.vouchers.index') }}" class="small text-decoration-none no-print"><i class="bi bi-arrow-left"></i> Vouchers</a>
<div class="d-flex align-items-center gap-3 mt-1 mb-4">
    <h1 class="page-title me-auto">{{ $entry->number }} <span class="badge text-bg-{{ $entry->status === 'posted' ? 'success' : 'secondary' }} fs-6 align-middle">{{ ucfirst($entry->status) }}</span></h1>
    <button class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    @can('accounts.manage')
        @if ($entry->status === 'posted')
            <form method="post" action="{{ route('admin.vouchers.void', $entry) }}" data-confirm="Void this voucher? It will no longer count in any balance.">@csrf<button class="btn btn-outline-danger no-print">Void</button></form>
        @endif
    @endcan
</div>
<div class="card"><div class="card-body">
    <div class="row mb-3">
        <div class="col-md-3"><div class="small text-body-secondary">Date</div>{{ $entry->entry_date->format('d M Y') }}</div>
        <div class="col-md-3"><div class="small text-body-secondary">Type</div>{{ \App\Models\JournalEntry::TYPES[$entry->type] ?? ucfirst($entry->type) }}</div>
        <div class="col-md-3"><div class="small text-body-secondary">Posted by</div>{{ $entry->creator?->full_name ?? 'System' }}</div>
        <div class="col-md-3"><div class="small text-body-secondary">Created</div>{{ $entry->created_at->format('d M Y H:i') }}</div>
    </div>
    @if ($entry->narration)<p class="mb-3">{{ $entry->narration }}</p>@endif
    <table class="table table-sm"><thead><tr><th>Account</th><th>Memo</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead><tbody>
        @foreach ($entry->lines as $line)<tr><td>{{ $line->account->label }}</td><td>{{ $line->memo }}</td><td class="text-end">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td><td class="text-end">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td></tr>@endforeach
    </tbody><tfoot><tr class="fw-bold"><td colspan="2">Total</td><td class="text-end">{{ number_format($entry->lines->sum('debit'), 2) }}</td><td class="text-end">{{ number_format($entry->lines->sum('credit'), 2) }}</td></tr></tfoot></table>
</div></div>
@endsection
