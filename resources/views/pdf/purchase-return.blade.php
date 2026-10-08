<!doctype html>
<html><head><meta charset="utf-8"><title>Return {{ $return->number }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
    h1 { font-size: 18px; margin: 0; color: #0f766e; }
    .muted { color: #64748b; }
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    th { background: #f1f5f9; }
    .r { text-align: right; }
    .total td { font-weight: bold; font-size: 13px; border-top: 2px solid #0f172a; border-bottom: 0; }
</style></head>
<body>
<h1>{{ $hotel }}</h1>
<div class="muted" style="margin-bottom:14px">Purchase return invoice · {{ $return->number }}</div>
<table style="margin-bottom:12px"><tr>
    <td style="border:0"><strong>{{ $return->purchase->supplier->name }}</strong><br><span class="muted">{{ $return->purchase->supplier->address }}<br>{{ $return->purchase->supplier->phone }}</span></td>
    <td style="border:0" class="r">Date: {{ $return->return_date->format('d M Y') }}<br>Against purchase: {{ $return->purchase->number }}@if ($return->purchase->reference)<br>Supplier invoice: {{ $return->purchase->reference }}@endif</td>
</tr></table>
<table>
    <thead><tr><th>Item</th><th class="r">Quantity</th><th class="r">Unit cost</th><th class="r">Amount</th></tr></thead>
    <tbody>
    @php $lines = $return->purchase->items->keyBy('item_id'); $factor = (float) $return->purchase->subtotal > 0 ? (float) $return->purchase->total / (float) $return->purchase->subtotal : 0; @endphp
    @foreach ($return->movements as $m)
        @php $qty = abs((float) $m->quantity); $unit = (float) ($lines[$m->item_id]->unit_cost ?? 0) * $factor; @endphp
        <tr><td>{{ $m->item->name }}</td><td class="r">{{ rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') }} {{ $m->item->unit->short_code }}</td><td class="r">{{ $money($unit) }}</td><td class="r">{{ $money($qty * $unit) }}</td></tr>
    @endforeach
    <tr class="total"><td colspan="3">Credit from supplier</td><td class="r">{{ $money($return->total) }}</td></tr>
    </tbody>
</table>
@if ($return->reason)<p class="muted" style="margin-top:16px">Reason: {{ $return->reason }}</p>@endif
</body></html>
