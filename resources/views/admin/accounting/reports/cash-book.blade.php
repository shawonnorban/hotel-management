@extends('layouts.admin')
@section('title', 'Cash & bank book')
@section('content')
<h1 class="page-title mb-4">Cash &amp; bank book</h1>
@include('admin.accounting.reports._filter', ['from' => $from, 'to' => $to])
@forelse ($books as $book)
<div class="card mb-4"><div class="card-header">{{ $book['account']->label }}</div>
<div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Date</th><th>Voucher</th><th>Narration</th><th class="text-end">Receipts</th><th class="text-end">Payments</th><th class="text-end">Balance</th></tr></thead>
    <tbody>
        <tr class="table-light"><td colspan="5">Opening balance</td><td class="text-end">{{ number_format($book['data']['opening'], 2) }}</td></tr>
        @foreach ($book['data']['rows'] as $r)
            <tr><td>{{ $r['entry']->entry_date->format('d M Y') }}</td><td><a href="{{ route('admin.vouchers.show', $r['entry']) }}" class="text-decoration-none">{{ $r['entry']->number }}</a></td><td>{{ $r['entry']->narration }}</td>
                <td class="text-end">{{ $r['debit'] ? number_format($r['debit'], 2) : '' }}</td><td class="text-end">{{ $r['credit'] ? number_format($r['credit'], 2) : '' }}</td><td class="text-end">{{ number_format($r['balance'], 2) }}</td></tr>
        @endforeach
        <tr class="table-light fw-semibold"><td colspan="5">Closing balance</td><td class="text-end">{{ number_format($book['data']['closing'], 2) }}</td></tr>
    </tbody></table></div></div>
@empty
    <div class="empty card"><i class="bi bi-wallet2"></i>No cash or bank accounts are set up.</div>
@endforelse
@endsection
