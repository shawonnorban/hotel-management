@extends('layouts.admin')
@section('title', 'Housekeeping report')
@section('content')
<h1 class="page-title mb-4">Housekeeping report</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
<div class="row g-3 mb-4">
    @foreach (\App\Models\HkTask::STATUSES as $k => $l)<div class="col-6 col-md-2"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $l }}</div><div class="fs-4 fw-bold">{{ $byStatus[$k] ?? 0 }}</div></div></div></div>@endforeach
</div>
<div class="row g-4"><div class="col-lg-7"><div class="card"><div class="card-header">By housekeeper</div><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Name</th><th class="text-end">Tasks</th><th class="text-end">Finished</th><th class="text-end">Avg. minutes</th></tr></thead><tbody>
    @forelse ($byEmployee as $name => $r)<tr><td>{{ $name }}</td><td class="text-end">{{ $r['total'] }}</td><td class="text-end">{{ $r['done'] }}</td><td class="text-end">{{ $r['avg_minutes'] ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-body-secondary p-3">No tasks in this period.</td></tr>@endforelse
    </tbody></table></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Laundry</div><ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between">Orders<strong>{{ $laundryCount }}</strong></li>
    <li class="list-group-item d-flex justify-content-between">Billed<strong>{{ \App\Support\Money::format($laundryTotal) }}</strong></li>
    <li class="list-group-item d-flex justify-content-between">Received<strong>{{ \App\Support\Money::format($laundryReceived) }}</strong></li></ul></div></div></div>
@endsection
