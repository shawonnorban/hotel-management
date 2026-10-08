@extends('layouts.admin')
@section('title', 'Hall report')
@section('content')
<h1 class="page-title mb-4">Hall report</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Bookings</div><div class="fs-4 fw-bold">{{ $rows->count() }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Billed</div><div class="fs-4 fw-bold">{{ \App\Support\Money::format($rows->sum('total')) }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">Received</div><div class="fs-4 fw-bold">{{ \App\Support\Money::format($received) }}</div></div></div></div>
</div>
<div class="row g-4"><div class="col-lg-4"><div class="card"><div class="card-header">By hall</div><table class="table table-sm mb-0">@forelse ($byHall as $name => $r)<tr><td>{{ $name }}<div class="small text-body-secondary">{{ $r['count'] }} booking(s) · {{ $r['hours'] }} h</div></td><td class="text-end">{{ \App\Support\Money::format($r['total']) }}</td></tr>@empty<tr><td class="text-body-secondary p-3">Nothing booked.</td></tr>@endforelse</table></div></div>
<div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Booking</th><th>Date</th><th>Hall</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Paid</th></tr></thead><tbody>
    @foreach ($rows as $b)<tr><td><a class="text-decoration-none" href="{{ route('admin.hall-bookings.show', $b) }}">{{ $b->number }}</a></td><td>{{ $b->event_date->format('d M Y') }}</td><td>{{ $b->hall->name }}</td><td>{{ $b->customer_name }}</td><td class="text-end">{{ \App\Support\Money::format($b->total) }}</td><td class="text-end">{{ \App\Support\Money::format($b->paid) }}</td></tr>@endforeach
    </tbody></table></div></div></div></div>
@endsection
