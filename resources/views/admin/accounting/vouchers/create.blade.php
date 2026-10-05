@extends('layouts.admin')
@section('title', 'New voucher')
@section('content')
@php($labels = ['receipt' => 'Receipt voucher', 'payment' => 'Payment voucher', 'contra' => 'Contra voucher', 'journal' => 'Journal voucher'])
<a href="{{ route('admin.vouchers.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Vouchers</a>
<h1 class="page-title mt-1 mb-4">{{ $labels[$type] }}</h1>
<ul class="nav nav-pills mb-3">@foreach ($labels as $k => $l)<li class="nav-item"><a class="nav-link {{ $k === $type ? 'active' : '' }}" href="{{ route('admin.vouchers.create', ['type' => $k]) }}">{{ explode(' ', $l)[0] }}</a></li>@endforeach</ul>
<form method="post" action="{{ route('admin.vouchers.store') }}" class="card"><div class="card-body row g-3">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="col-md-3"><label class="form-label small fw-semibold">Date</label><input type="text" name="entry_date" class="form-control" data-date value="{{ old('entry_date', now()->format('Y-m-d')) }}" required></div>
    <div class="col-md-9"><label class="form-label small fw-semibold">Narration</label><input name="narration" class="form-control" maxlength="500" value="{{ old('narration') }}"></div>
    @if ($type === 'journal')
        <div class="col-12">
            <table class="table table-sm align-middle" id="lines">
                <thead><tr><th style="width:38%">Account</th><th>Memo</th><th style="width:140px" class="text-end">Debit</th><th style="width:140px" class="text-end">Credit</th></tr></thead>
                <tbody>
                @for ($i = 0; $i < max(4, count(old('lines', []))); $i++)
                    <tr>
                        <td><select name="lines[{{ $i }}][account]" class="form-select form-select-sm" data-search><option value="">—</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected((string) old("lines.$i.account") === (string) $a->id)>{{ $a->label }}</option>@endforeach</select></td>
                        <td><input name="lines[{{ $i }}][memo]" class="form-control form-control-sm" value="{{ old("lines.$i.memo") }}"></td>
                        <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" class="form-control form-control-sm text-end js-debit" value="{{ old("lines.$i.debit") }}"></td>
                        <td><input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" class="form-control form-control-sm text-end js-credit" value="{{ old("lines.$i.credit") }}"></td>
                    </tr>
                @endfor
                </tbody>
                <tfoot><tr class="fw-semibold"><td colspan="2" class="text-end">Totals</td><td class="text-end" id="tDebit">0.00</td><td class="text-end" id="tCredit">0.00</td></tr></tfoot>
            </table>
            <div class="small" id="balanceNote"></div>
        </div>
        @push('scripts')
        <script>
        (function () {
            function sum(sel) { var t = 0; document.querySelectorAll(sel).forEach(function (i) { t += parseFloat(i.value) || 0; }); return t; }
            function refresh() {
                var d = sum('.js-debit'), c = sum('.js-credit');
                document.getElementById('tDebit').textContent = d.toFixed(2); document.getElementById('tCredit').textContent = c.toFixed(2);
                var n = document.getElementById('balanceNote');
                if (d === 0 && c === 0) { n.textContent = ''; } else if (Math.abs(d - c) < 0.005) { n.className = 'small text-success'; n.textContent = 'Balanced'; } else { n.className = 'small text-danger'; n.textContent = 'Out of balance by ' + Math.abs(d - c).toFixed(2); }
            }
            document.getElementById('lines').addEventListener('input', refresh); refresh();
        })();
        </script>
        @endpush
    @else
        <div class="col-md-5"><label class="form-label small fw-semibold">{{ $type === 'contra' ? 'Transfer from (credit)' : 'Cash / bank account' }}</label>
            <select name="cash_account" class="form-select" data-search required><option value="">— Select —</option>@foreach ($cashAccounts as $a)<option value="{{ $a->id }}" @selected((string) old('cash_account') === (string) $a->id)>{{ $a->label }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label small fw-semibold">{{ match ($type) { 'receipt' => 'Received from / income account', 'payment' => 'Paid to / expense account', default => 'Transfer to (debit)' } }}</label>
            <select name="other_account" class="form-select" data-search required><option value="">— Select —</option>@foreach (($type === 'contra' ? $cashAccounts : $accounts) as $a)<option value="{{ $a->id }}" @selected((string) old('other_account') === (string) $a->id)>{{ $a->label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small fw-semibold">Amount</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required></div>
    @endif
</div>
<div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4">Post voucher</button></div></form>
@endsection
