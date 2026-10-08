@extends('layouts.admin')
@section('title', 'Assign room cleaning')
@section('content')
<h1 class="page-title mb-1">Assign room cleaning</h1><p class="page-sub mb-4">Pick the rooms and who cleans them. The checklist is copied onto every task.</p>
<form method="post" action="{{ route('admin.housekeeping.assign.store') }}">@csrf
<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-4"><label class="form-label small fw-semibold">Date</label><input name="task_date" class="form-control" data-date value="{{ old('task_date', today()->toDateString()) }}" required></div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Housekeeper</label><select name="employee" class="form-select"><option value="">— Unassigned —</option>@foreach ($employees as $e)<option value="{{ $e->id }}" @selected(old('employee') == $e->id)>{{ $e->full_name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label small fw-semibold">Note</label><input name="notes" class="form-control" maxlength="500" value="{{ old('notes') }}"></div>
</div></div>
@error('rooms')<div class="alert alert-danger">{{ $message }}</div>@enderror
<div class="card"><div class="card-header d-flex align-items-center">Rooms <button type="button" class="btn btn-sm btn-link ms-auto" onclick="document.querySelectorAll('.room-pick').forEach(c=>c.checked=!c.checked)">Toggle all</button></div>
<div class="card-body row g-2">
    @foreach ($rooms as $r)
        <div class="col-6 col-md-3 col-xl-2"><label class="border rounded p-2 d-block h-100 {{ in_array($r->status, [6]) ? 'border-danger' : '' }}" style="cursor:pointer">
            <input type="checkbox" class="form-check-input room-pick me-1" name="rooms[]" value="{{ $r->roomassignid }}" @checked(in_array($r->roomassignid, (array) old('rooms', [])))>
            <strong>{{ $r->roomno }}</strong><div class="small text-body-secondary">{{ $types[$r->roomid] ?? '' }}</div><div class="small">{{ $statuses[$r->status] ?? '' }}</div></label></div>
    @endforeach
</div><div class="card-footer bg-transparent"><button class="btn btn-primary">Assign cleaning</button></div></div>
</form>
@endsection
