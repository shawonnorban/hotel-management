@extends('layouts.admin')
@section('title', 'Attendance dashboard')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3"><div class="me-auto"><h1 class="page-title">Attendance dashboard</h1><p class="page-sub">{{ $date->format('l, d F Y') }}</p></div>
    <form method="get" class="d-flex gap-2"><input name="date" class="form-control" data-date value="{{ $date->toDateString() }}"><button class="btn btn-outline-primary">Show</button></form>
    @can('hr-attendance.manage')<a href="{{ route('admin.hr.attendance', ['date' => $date->toDateString()]) }}" class="btn btn-primary">Record attendance</a>@endcan</div>
<div class="row g-3 mb-4">
@foreach ([['present', 'Present', 'success'], ['late', 'Late', 'warning'], ['half_day', 'Half day', 'info'], ['leave', 'On leave', 'secondary'], ['absent', 'Absent', 'danger'], ['unmarked', 'Not marked', 'dark']] as [$k, $label, $tone])
    <div class="col-6 col-md-4 col-xl-2"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $label }}</div><div class="fs-3 fw-bold text-{{ $tone }}">{{ $counts[$k] ?? 0 }}</div></div></div></div>
@endforeach
</div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">Rostered today</div><ul class="list-group list-group-flush">
        @forelse ($byShift as $name => $n)<li class="list-group-item d-flex justify-content-between">{{ $name }}<span class="badge text-bg-light">{{ $n }}</span></li>@empty<li class="list-group-item text-body-secondary">No roster for this day.</li>@endforelse
    </ul></div>
    @if ($missing->isNotEmpty())<div class="card mt-3 border-warning"><div class="card-header">Rostered but not checked in</div><ul class="list-group list-group-flush">@foreach ($missing as $e)<li class="list-group-item">{{ $e->full_name }} <span class="small text-body-secondary">{{ $rostered[$e->id]->shift->name }}</span></li>@endforeach</ul></div>@endif</div>
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead><tr><th>Employee</th><th>Shift</th><th>Status</th><th>In</th><th>Out</th></tr></thead><tbody>
        @foreach ($employees as $e)
            @php $r = $records->get($e->id); $ro = $rostered->get($e->id); @endphp
            <tr><td>{{ $e->full_name }}<div class="small text-body-secondary">{{ $e->department?->name }}</div></td>
                <td>@if ($ro?->shift)<span class="badge" style="background:{{ $ro->shift->color }}">{{ $ro->shift->name }}</span>@elseif ($ro)<span class="badge text-bg-secondary">Off</span>@endif</td>
                <td>{{ $r ? ucfirst(str_replace('_', ' ', $r->status)) : '—' }}</td><td>{{ $r?->check_in ? substr($r->check_in, 0, 5) : '' }}</td><td>{{ $r?->check_out ? substr($r->check_out, 0, 5) : '' }}</td></tr>
        @endforeach
        </tbody></table></div></div></div>
</div>
@endsection
