@extends('layouts.admin')
@section('title', 'Trial balance')
@section('content')
<h1 class="page-title mb-4">Trial balance</h1>
@include('admin.accounting.reports._filter', ['asOf' => $asOf, 'csv' => true])
<div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Code</th><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
    <tbody>
    @forelse ($tb['rows'] as $r)
        <tr><td>{{ $r['account']->code }}</td><td><a class="text-decoration-none" href="{{ route('admin.accounting.ledger', ['account' => $r['account']->id, 'from' => $asOf->copy()->startOfYear()->format('Y-m-d'), 'to' => $asOf->format('Y-m-d')]) }}">{{ $r['account']->name }}</a></td><td class="text-end">{{ $r['debit'] ? number_format($r['debit'], 2) : '' }}</td><td class="text-end">{{ $r['credit'] ? number_format($r['credit'], 2) : '' }}</td></tr>
    @empty
        <tr><td colspan="4" class="text-center text-body-secondary py-4">No balances yet.</td></tr>
    @endforelse
    </tbody>
    <tfoot><tr class="fw-bold table-light"><td colspan="2">Total</td><td class="text-end">{{ number_format($tb['debit'], 2) }}</td><td class="text-end">{{ number_format($tb['credit'], 2) }}</td></tr></tfoot>
</table></div></div>
@if (abs($tb['debit'] - $tb['credit']) >= 0.01)<div class="alert alert-danger mt-3">The trial balance is out of balance by {{ number_format(abs($tb['debit'] - $tb['credit']), 2) }}. Check the opening balances.</div>@endif
@endsection
