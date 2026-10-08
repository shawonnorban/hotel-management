@extends('layouts.admin')
@section('title', 'Roster assign')
@section('content')
<a href="{{ route('admin.hr.roster.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Roster list</a>
<h1 class="page-title mt-1 mb-4">Roster assign</h1>
@if ($shifts->isEmpty())<div class="alert alert-warning">Create at least one shift first in <a href="{{ route('admin.resource.index', 'hr-shifts') }}">Shift list</a>.</div>@endif
<form method="post" action="{{ route('admin.hr.roster.assign.store') }}" class="card"><div class="card-body row g-3">@csrf
    <div class="col-12"><label class="form-label small fw-semibold">Employees</label>
        <select name="employees[]" class="form-select" multiple data-search required>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(in_array($e->id, (array) old('employees', [])))>{{ $e->full_name }} · {{ $e->department?->name }}</option>@endforeach</select>
        @error('employees')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label small fw-semibold">From</label><input name="from" class="form-control" data-date value="{{ old('from', today()->toDateString()) }}" required>@error('from')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label small fw-semibold">To</label><input name="to" class="form-control" data-date value="{{ old('to', today()->addDays(6)->toDateString()) }}" required>@error('to')<div class="text-danger small">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label small fw-semibold">Shift</label>
        <select name="shift" class="form-select" required>@foreach ($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift') == $s->id)>{{ $s->name }} ({{ $s->hours_label }})</option>@endforeach<option value="off" @selected(old('shift') === 'off')>Day off</option><option value="clear" @selected(old('shift') === 'clear')>Clear existing roster</option></select></div>
    <div class="col-12"><label class="form-label small fw-semibold">Only on these weekdays <span class="text-body-secondary fw-normal">(leave empty for every day)</span></label><div class="d-flex flex-wrap gap-3">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $label)<div class="form-check"><input class="form-check-input" type="checkbox" name="weekdays[]" value="{{ $i }}" id="wd{{ $i }}" @checked(in_array($i, (array) old('weekdays', [])))><label class="form-check-label" for="wd{{ $i }}">{{ $label }}</label></div>@endforeach</div></div>
    <div class="col-12"><label class="form-label small fw-semibold">Note</label><input name="note" class="form-control" maxlength="150" value="{{ old('note') }}"></div>
</div><div class="card-footer bg-transparent"><button class="btn btn-primary">Save roster</button></div></form>
@endsection
