<!doctype html>
<html><head><meta charset="utf-8"><title>Invoice {{ $booking->booking_number }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
    h1 { font-size: 22px; margin: 0; color: #0f766e; }
    .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
    td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; }
    .right { text-align: right; }
    .total td { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; border-bottom: 0; }
    .box { background: #f1f5f9; padding: 10px 12px; border-radius: 4px; }
</style></head>
<body>
<table style="margin-bottom:18px"><tr>
    <td style="border:0;width:60%"><h1>{{ $hotel }}</h1><div class="muted">{{ \App\Support\Settings::get('address') }}<br>{{ \App\Support\Settings::get('phone') }} · {{ \App\Support\Settings::get('email') }}</div></td>
    <td style="border:0" class="right"><div style="font-size:20px;font-weight:bold">INVOICE</div><div class="muted">#{{ $booking->booking_number }}<br>{{ now()->format('d M Y') }}</div></td>
</tr></table>
<table style="margin-bottom:18px"><tr>
    <td style="border:0;width:50%"><div class="muted">Billed to</div><strong>{{ $booking->customer?->full_name }}</strong><br>{{ $booking->customer?->email }}<br>{{ $booking->customer?->cust_phone }}</td>
    <td style="border:0"><div class="muted">Stay</div>{{ $booking->checkindate->format('D, d M Y') }} → {{ $booking->checkoutdate->format('D, d M Y') }}<br>{{ $booking->nights }} night(s) · {{ $booking->total_room }} room(s) · room {{ $booking->room_no }}<br>Guest: {{ $booking->full_guest_name }}</td>
</tr></table>
<table>
    <thead><tr><th>Description</th><th class="right">Amount</th></tr></thead>
    <tbody>
        @foreach ($lines as $line)<tr><td>{{ $line['label'] }}</td><td class="right">{{ $line['amount'] }}</td></tr>@endforeach
        <tr class="total"><td>Total</td><td class="right">{{ $money($booking->total_price) }}</td></tr>
    </tbody>
</table>
@if ($booking->payments->isNotEmpty())
    <h3 style="margin:22px 0 6px">Payments received</h3>
    <table><thead><tr><th>Date</th><th>Receipt</th><th>Method</th><th class="right">Amount</th></tr></thead><tbody>
        @foreach ($booking->payments as $payment)<tr><td>{{ \Illuminate\Support\Carbon::parse($payment->paydate)->format('d M Y') }}</td><td>{{ $payment->invoice }}</td><td>{{ $payment->paymenttype }}</td><td class="right">{{ $money($payment->paymentamount) }}</td></tr>@endforeach
    </tbody></table>
@endif
<div class="box" style="margin-top:18px"><table><tr><td style="border:0">Paid</td><td style="border:0" class="right">{{ $money($booking->paid_amount) }}</td></tr>
<tr><td style="border:0"><strong>Balance due</strong></td><td style="border:0" class="right"><strong>{{ $money($booking->balance) }}</strong></td></tr></table></div>
<p class="muted" style="margin-top:24px">Thank you for staying with us.</p>
</body></html>
