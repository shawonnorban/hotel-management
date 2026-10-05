@extends('layouts.admin')
@section('title', 'Guest receipts')
@section('content')
<h1 class="page-title mb-4">Guest receipts</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Net received</div><div class="fs-4 fw-bold">{{ \App\Support\Money::format($total) }}</div></div></div></div>
    @foreach ($byMethod as $method => $m)<div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $method }} · {{ $m['count'] }}</div><div class="fs-5 fw-bold">{{ \App\Support\Money::format($m['amount']) }}</div></div></div></div>@endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Receipt</th><th>Date</th><th>Booking</th><th>Guest</th><th>Method</th><th class="text-end">Amount</th></tr></thead><tbody>
    @forelse ($payments as $p)
        <tr><td>{{ $p->invoice }}</td><td>{{ $p->paydate->format('d M Y H:i') }}</td><td>@if ($p->booking)<a class="text-decoration-none" href="{{ route('admin.reservations.show', $p->booking->booking_number) }}">#{{ $p->booking->booking_number }}</a>@endif</td><td>{{ $p->booking?->customer?->full_name }}</td><td>{{ $p->paymenttype }}</td><td class="text-end {{ $p->paymentamount < 0 ? 'text-danger' : '' }}">{{ \App\Support\Money::format($p->paymentamount) }}</td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-body-secondary py-4">No payments in this period.</td></tr>
    @endforelse
    </tbody></table></div></div>
@endsection
