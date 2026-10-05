@extends('layouts.admin')
@section('title', 'Attendance')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Attendance</h1><p class="page-sub">{{ $date->format('l, d F Y') }}@if ($offDay) · <span class="text-warning">day off / holiday</span>@endif</p></div>
    <form method="get" class="d-flex gap-2"><input name="date" class="form-control" data-date value="{{ $date->format('Y-m-d') }}" style="width:160px"><button class="btn btn-outline-primary">Go</button></form>
    <a href="{{ route('admin.hr.attendance.report') }}" class="btn btn-outline-secondary">Monthly report</a>
</div>
<form method="post" action="{{ route('admin.hr.attendance.save') }}" class="card mb-4">
    @csrf <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
    <div class="card-header d-flex align-items-center">Mark attendance <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="allPresent">Mark everyone present</button></div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>Employee</th><th style="width:200px">Status</th><th style="width:140px">In</th><th style="width:140px">Out</th></tr></thead><tbody>
        @forelse ($employees as $i => $e)
            @php($r = $records[$e->id] ?? null)
            <tr><td><input type="hidden" name="rows[{{ $i }}][employee]" value="{{ $e->id }}"><div class="fw-semibold">{{ $e->full_name }}</div><div class="small text-body-secondary">{{ $e->code }} · {{ $e->department?->name }}</div></td>
                <td><select name="rows[{{ $i }}][status]" class="form-select form-select-sm js-status"><option value="">—</option>@foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected($r?->status === $k)>{{ $l }}</option>@endforeach</select></td>
                <td><input name="rows[{{ $i }}][check_in]" class="form-control form-control-sm" data-time value="{{ $r?->check_in ? substr($r->check_in, 0, 5) : '' }}"></td>
                <td><input name="rows[{{ $i }}][check_out]" class="form-control form-control-sm" data-time value="{{ $r?->check_out ? substr($r->check_out, 0, 5) : '' }}"></td></tr>
        @empty
            <tr><td colspan="4"><div class="empty"><i class="bi bi-people"></i>No active employees yet.</div></td></tr>
        @endforelse
        </tbody></table></div>
    <div class="card-footer bg-transparent text-end"><button class="btn btn-primary px-4" @disabled($employees->isEmpty())>Save attendance</button></div>
</form>
@can('hr-attendance.manage')
<form method="post" action="{{ route('admin.hr.attendance.weekly-off') }}" class="card"><div class="card-body d-flex flex-wrap align-items-center gap-3">
    @csrf @method('PUT')
    <strong>Weekly days off:</strong>
    @foreach ($days as $k => $label)<div class="form-check form-check-inline mb-0"><input class="form-check-input" type="checkbox" name="off[]" value="{{ $k }}" id="off_{{ $k }}" @checked(in_array($k, $weeklyOff))><label class="form-check-label" for="off_{{ $k }}">{{ substr($label, 0, 3) }}</label></div>@endforeach
    <button class="btn btn-sm btn-outline-primary ms-auto">Save</button>
</div></form>
@endcan
@push('scripts')<script>document.getElementById('allPresent').addEventListener('click', function () { document.querySelectorAll('.js-status').forEach(function (s) { if (!s.value) { s.value = 'present'; } }); });</script>@endpush
@endsection
