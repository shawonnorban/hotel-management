@extends('layouts.admin')
@section('title', 'Ledger')
@section('content')
<h1 class="page-title mb-4">Account ledger</h1>
@component('admin.accounting.reports._filter', ['from' => $from, 'to' => $to, 'csv' => (bool) $account])
    <div class="col-md-4"><label class="form-label small fw-semibold">Account</label>
        <select name="account" class="form-select" data-search required><option value="">— Select —</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($account?->id === $a->id)>{{ $a->label }}</option>@endforeach</select></div>
@endcomponent
@if ($data)
<div class="card"><div class="card-header d-flex justify-content-between"><span>{{ $account->label }}</span><span class="text-body-secondary small">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span></div>
<div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Date</th><th>Voucher</th><th>Narration</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
    <tbody>
        <tr class="table-light"><td colspan="5" class="fw-semibold">Opening balance</td><td class="text-end fw-semibold">{{ number_format($data['opening'], 2) }}</td></tr>
        @forelse ($data['rows'] as $r)
            <tr><td>{{ $r['entry']->entry_date->format('d M Y') }}</td><td><a href="{{ route('admin.vouchers.show', $r['entry']) }}" class="text-decoration-none">{{ $r['entry']->number }}</a></td><td>{{ $r['entry']->narration }}</td>
                <td class="text-end">{{ $r['debit'] ? number_format($r['debit'], 2) : '' }}</td><td class="text-end">{{ $r['credit'] ? number_format($r['credit'], 2) : '' }}</td><td class="text-end">{{ number_format($r['balance'], 2) }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-body-secondary py-4">No transactions in this period.</td></tr>
        @endforelse
        <tr class="table-light"><td colspan="5" class="fw-semibold">Closing balance</td><td class="text-end fw-semibold">{{ number_format($data['closing'], 2) }}</td></tr>
    </tbody></table></div></div>
@endif
@endsection
