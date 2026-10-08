@extends('layouts.admin')
@section('title', 'Room cleaning')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3"><div class="me-auto"><h1 class="page-title">Room cleaning</h1><p class="page-sub">{{ $date->format('l, d F Y') }} · {{ $tasks->count() }} task(s)</p></div>
    @can('hk-tasks.manage')<a href="{{ route('admin.housekeeping.assign') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Assign cleaning</a>@endcan</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-md-2"><input name="date" class="form-control" data-date value="{{ $date->toDateString() }}"></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (\App\Models\HkTask::STATUSES as $k => $l)<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="employee" class="form-select"><option value="">All housekeepers</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected((int) ($f['employee'] ?? 0) === $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
</form>
@error('task')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="row g-3">
@forelse ($tasks as $t)
    @php $tone = ['pending' => 'warning', 'in_progress' => 'primary', 'done' => 'success', 'inspected' => 'success', 'cancelled' => 'secondary'][$t->status]; @endphp
    <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-header d-flex align-items-center">Room {{ $t->room?->roomno }} <span class="small text-body-secondary ms-2">{{ $types[$t->room?->roomid] ?? '' }}</span>
        <span class="badge text-bg-{{ $tone }} ms-auto">{{ \App\Models\HkTask::STATUSES[$t->status] }}</span></div>
        <div class="card-body">
            <div class="small text-body-secondary mb-2"><i class="bi bi-person me-1"></i>{{ $t->employee?->full_name ?? 'Unassigned' }}@if ($t->source === 'qr') · <span class="badge text-bg-info">Guest request</span>@endif</div>
            @if ($t->notes)<p class="small">{{ $t->notes }}</p>@endif
            @foreach ($t->items as $i)
                <form method="post" action="{{ route('admin.housekeeping.tasks.item', [$t, $i->id]) }}">@csrf
                    <button class="btn btn-sm btn-link text-decoration-none p-0 text-start text-reset" @disabled(! auth('admin')->user()->can('hk-tasks.manage'))><i class="bi {{ $i->is_done ? 'bi-check-square-fill text-success' : 'bi-square' }} me-1"></i>{{ $i->name }}</button></form>
            @endforeach
        </div>
        @can('hk-tasks.manage')
        <div class="card-footer bg-transparent d-flex gap-2">
            @foreach ([['start', 'Start', 'primary', ['pending']], ['complete', 'Complete', 'success', ['pending', 'in_progress']], ['inspect', 'Inspect', 'outline-success', ['done']], ['cancel', 'Cancel', 'outline-danger', ['pending', 'in_progress']]] as [$a, $l, $c, $from])
                @if (in_array($t->status, $from))<form method="post" action="{{ route('admin.housekeeping.tasks.transition', [$t, $a]) }}">@csrf<button class="btn btn-sm btn-{{ $c }}">{{ $l }}</button></form>@endif
            @endforeach
        </div>@endcan
    </div></div>
@empty
    <div class="col-12"><div class="empty"><i class="bi bi-stars"></i>No cleaning tasks for this day.</div></div>
@endforelse
</div>
@endsection
