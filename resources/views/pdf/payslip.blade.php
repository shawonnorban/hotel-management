<!doctype html>
<html><head><meta charset="utf-8"><title>Payslip</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    h1 { font-size: 18px; margin: 0; color: #0f766e; }
    .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    .r { text-align: right; }
    .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #0f172a; border-bottom: 0; }
</style></head>
<body>
<h1>{{ $hotel }}</h1>
<div class="muted" style="margin-bottom:14px">Payslip · {{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $run->period.'-01')->format('F Y') }}</div>
<table style="margin-bottom:12px"><tr><td style="border:0"><strong>{{ $item->employee->full_name }}</strong><br><span class="muted">{{ $item->employee->code }} · {{ $item->employee->position?->title }} · {{ $item->employee->department?->name }}</span></td></tr></table>
<table>
    <tr><td>Basic salary</td><td class="r">{{ $money($item->basic) }}</td></tr>
    <tr><td>Allowances</td><td class="r">{{ $money($item->allowances) }}</td></tr>
    <tr><td>Bonus</td><td class="r">{{ $money($item->bonus) }}</td></tr>
    <tr><td>Absence ({{ rtrim(rtrim($item->absent_days, '0'), '.') ?: '0' }} day(s))</td><td class="r">−{{ $money($item->absence_deduction) }}</td></tr>
    <tr><td>Deductions</td><td class="r">−{{ $money($item->deductions) }}</td></tr>
    <tr><td>Loan instalment</td><td class="r">−{{ $money($item->loan_deduction) }}</td></tr>
    <tr class="total"><td>Net pay</td><td class="r">{{ $money($item->net) }}</td></tr>
</table>
<p class="muted" style="margin-top:20px">{{ $run->status === 'paid' ? 'Paid on '.$run->paid_on->format('d M Y').'.' : 'Not paid yet.' }}</p>
</body></html>
