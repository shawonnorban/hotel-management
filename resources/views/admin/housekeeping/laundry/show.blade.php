@extends('layouts.admin')
@section('title', $order->number)
@section('content')
<a href="{{ route('admin.laundry.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Laundry list</a>
<div class="d-flex flex-wrap align-items-center gap-2 mt-1 mb-4"><div class="me-auto"><h1 class="page-title">{{ $order->number }}</h1><p class="page-sub">{{ $order->guest_name }}{{ $order->room_no ? ' · Room '.$order->room_no : '' }} · {{ $order->order_date->format('d M Y') }}</p></div>
    <span class="badge fs-6 text-bg-secondary">{{ \App\Models\HkLaundryOrder::STATUSES[$order->status] }}</span></div>
@error('order')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="row g-4"><div class="col-lg-8">
    <div class="card mb-4"><table class="table mb-0"><thead><tr><th>Item</th><th>Service</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead><tbody>
        @foreach ($order->lines as $l)<tr><td>{{ $l->product->name }}</td><td>{{ $services[$l->service] ?? $l->service }}</td><td class="text-end">{{ $l->quantity }}</td><td class="text-end">{{ \App\Support\Money::format($l->unit_cost) }}</td><td class="text-end">{{ \App\Support\Money::format($l->line_total) }}</td></tr>@endforeach
        <tr class="fw-bold"><td colspan="4">Total</td><td class="text-end">{{ \App\Support\Money::format($order->total) }}</td></tr>
        <tr><td colspan="4">Paid</td><td class="text-end">{{ \App\Support\Money::format($order->paid) }}</td></tr>
        <tr class="fw-bold"><td colspan="4">Due</td><td class="text-end">{{ \App\Support\Money::format($order->status === 'cancelled' ? 0 : $order->due) }}</td></tr></tbody></table></div>
    <div class="card"><div class="card-header">Payments</div><table class="table table-sm mb-0"><tbody>
        @forelse ($order->payments as $p)<tr><td>{{ $p->paid_on->format('d M Y') }}</td><td>{{ $p->account->name }}</td><td>{{ $p->reference }}</td><td class="text-end">{{ \App\Support\Money::format($p->amount) }}</td></tr>@empty<tr><td class="text-body-secondary p-3">No payments yet.</td></tr>@endforelse</tbody></table></div>
</div>
<div class="col-lg-4">
    @can('hk-laundry.manage')@if ($order->status !== 'cancelled')
    <div class="card mb-3"><div class="card-header">Progress</div><div class="card-body"><form method="post" action="{{ route('admin.laundry.status', $order) }}" class="d-flex gap-2">@csrf
        <select name="status" class="form-select">@foreach (['received', 'washing', 'ready', 'delivered'] as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ \App\Models\HkLaundryOrder::STATUSES[$s] }}</option>@endforeach</select><button class="btn btn-outline-primary">Save</button></form></div></div>
    @if ($order->due > 0)
    <div class="card mb-3"><div class="card-header">Take payment</div><div class="card-body"><form method="post" action="{{ route('admin.laundry.pay', $order) }}" class="row g-2">@csrf
        <div class="col-12"><input type="number" step="0.01" name="amount" class="form-control" value="{{ $order->due }}" required></div>
        <div class="col-12"><select name="account" class="form-select">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        <div class="col-6"><input name="paid_on" class="form-control" data-date value="{{ today()->toDateString() }}"></div><div class="col-6"><input name="reference" class="form-control" placeholder="Reference"></div>
        <div class="col-12"><button class="btn btn-primary w-100">Record payment</button></div></form></div></div>@endif
    @if ((float) $order->paid == 0)<form method="post" action="{{ route('admin.laundry.cancel', $order) }}" data-confirm="Cancel this order?">@csrf<button class="btn btn-outline-danger w-100">Cancel order</button></form>@endif
    @endif @endcan
</div></div>
@endsection
