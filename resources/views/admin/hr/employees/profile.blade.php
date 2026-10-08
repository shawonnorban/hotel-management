@extends('layouts.admin')
@section('title', $employee->full_name)
@section('content')
@php
    $e = $employee;
    $img = fn ($p) => $p ? asset($p) : null;
    $row = fn ($label, $value) => '<div class="col-md-4 mb-3"><div class="small text-body-secondary">'.e($label).'</div><div class="fw-semibold">'.(filled($value) ? e($value) : '—').'</div></div>';
    $fmt = fn ($d) => $d?->format('d M Y');
@endphp
<a href="{{ route('admin.resource.index', 'hr-employees') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Employees</a>
<div class="card my-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center">
    @if ($e->photo)<img src="{{ asset($e->photo) }}" class="rounded-circle" width="88" height="88" style="object-fit:cover" alt="">@else<div class="rounded-circle bg-body-secondary d-flex align-items-center justify-content-center" style="width:88px;height:88px"><i class="bi bi-person fs-1"></i></div>@endif
    <div><h1 class="page-title mb-0">{{ $e->full_name }}</h1>
        <div class="text-body-secondary">{{ $e->position?->title }}{{ $e->department ? ' · '.$e->department->name : '' }} · {{ $e->code }}</div>
        <span class="badge {{ $e->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $e->is_active ? 'Employed' : 'Left' }}</span></div>
    <div class="ms-auto no-print">
        @can('hr-employees.edit')<a class="btn btn-outline-primary" href="{{ route('admin.resource.edit', ['hr-employees', $e->id]) }}"><i class="bi bi-pencil me-1"></i>Edit</a>@endcan
        @can('hr-payroll.view')<a class="btn btn-outline-secondary" href="{{ route('admin.hr.employees.salary', $e) }}"><i class="bi bi-cash-stack me-1"></i>Salary</a>@endcan
    </div>
</div></div>

<ul class="nav nav-tabs mb-3" role="tablist">
    @foreach (['personal' => 'Personal', 'employment' => 'Employment', 'documents' => 'Documents', 'education' => 'Education', 'experience' => 'Experience'] as $id => $label)
        <li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $id }}" type="button">{{ $label }}</button></li>
    @endforeach
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active" id="personal"><div class="card"><div class="card-body"><div class="row">
        {!! $row('Gender', $e->gender) !!}{!! $row('Date of birth', $fmt($e->birth_date)) !!}{!! $row('Blood group', $e->blood_group) !!}
        {!! $row("Father's name", $e->father_name) !!}{!! $row("Mother's name", $e->mother_name) !!}{!! $row('Marital status', $e->marital_status) !!}
        {!! $row('Spouse', $e->spouse_name) !!}{!! $row('Religion', $e->religion) !!}{!! $row('Nationality', $e->nationality) !!}
        {!! $row('Email', $e->email) !!}{!! $row('Phone', $e->phone) !!}{!! $row('Alternative phone', $e->alt_phone) !!}
        {!! $row('Present address', $e->present_address) !!}{!! $row('Permanent address', $e->address) !!}
        {!! $row('Emergency contact', trim($e->emergency_name.' '.($e->emergency_relation ? '('.$e->emergency_relation.')' : '').' '.$e->emergency_phone)) !!}
        {!! $row('NID', $e->national_id) !!}{!! $row('Passport', $e->passport_no) !!}{!! $row('TIN', $e->tin_no) !!}
        <div class="col-12 d-flex gap-3">
            @foreach (['id_front' => 'NID front', 'id_back' => 'NID back'] as $c => $l)
                @if ($e->$c)<a href="{{ asset($e->$c) }}" target="_blank"><img src="{{ asset($e->$c) }}" height="120" class="rounded border" alt="{{ $l }}"><div class="small text-center">{{ $l }}</div></a>@endif
            @endforeach
        </div>
    </div></div></div></div>

    <div class="tab-pane fade" id="employment"><div class="card"><div class="card-body"><div class="row">
        {!! $row('Employee no.', $e->code) !!}{!! $row('Department', $e->department?->name) !!}{!! $row('Position', $e->position?->title) !!}
        {!! $row('Employment type', $e->employment_type) !!}{!! $row('Work location', $e->work_location) !!}{!! $row('Joined on', $fmt($e->join_date)) !!}
        {!! $row('Probation ends', $fmt($e->probation_end)) !!}{!! $row('Left on', $fmt($e->leave_date)) !!}{!! $row('Basic salary', \App\Support\Money::format($e->basic_salary)) !!}
        {!! $row('Bank', $e->bank_name) !!}{!! $row('Branch', $e->bank_branch) !!}{!! $row('Account no.', $e->bank_account) !!}
        <div class="col-12">{!! $e->notes ? '<div class="small text-body-secondary">Notes</div><div>'.nl2br(e($e->notes)).'</div>' : '' !!}</div>
    </div></div></div></div>

    <div class="tab-pane fade" id="documents"><div class="card"><div class="card-body">
        <table class="table align-middle"><thead><tr><th>Title</th><th>Type</th><th>Expires</th><th></th></tr></thead><tbody>
            @forelse ($e->documents as $d)
                <tr><td><a href="{{ asset($d->file) }}" target="_blank">{{ $d->title }}</a></td><td>{{ $d->type }}</td><td>{{ $d->expires_on ? \Illuminate\Support\Carbon::parse($d->expires_on)->format('d M Y') : '—' }}</td>
                    <td class="text-end">@can('hr-employees.edit')<form method="post" action="{{ route('admin.hr.employees.records.destroy', [$e, 'documents', $d->id]) }}" data-confirm="Remove this document?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>@endcan</td></tr>
            @empty<tr><td colspan="4" class="text-body-secondary">No documents uploaded.</td></tr>@endforelse
        </tbody></table>
        @can('hr-employees.edit')
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.hr.employees.records.store', [$e, 'documents']) }}" class="row g-2 no-print">@csrf
            <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
            <div class="col-md-2"><input name="type" class="form-control" placeholder="Type (CV, contract…)"></div>
            <div class="col-md-2"><input name="expires_on" class="form-control" data-date placeholder="Expires" autocomplete="off"></div>
            <div class="col-md-3"><input type="file" name="file" class="form-control" required></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Upload</button></div>
        </form>@endcan
    </div></div></div>

    <div class="tab-pane fade" id="education"><div class="card"><div class="card-body">
        <table class="table align-middle"><thead><tr><th>Degree</th><th>Institute</th><th>Field</th><th>Result</th><th>Year</th><th></th></tr></thead><tbody>
            @forelse ($e->education as $r)
                <tr><td>{{ $r->degree }}</td><td>{{ $r->institute }}</td><td>{{ $r->field }}</td><td>{{ $r->result }}</td><td>{{ $r->passing_year }}</td>
                    <td class="text-end">@can('hr-employees.edit')<form method="post" action="{{ route('admin.hr.employees.records.destroy', [$e, 'education', $r->id]) }}" data-confirm="Remove this entry?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>@endcan</td></tr>
            @empty<tr><td colspan="6" class="text-body-secondary">No education recorded.</td></tr>@endforelse
        </tbody></table>
        @can('hr-employees.edit')
        <form method="post" action="{{ route('admin.hr.employees.records.store', [$e, 'education']) }}" class="row g-2 no-print">@csrf
            <div class="col-md-3"><input name="degree" class="form-control" placeholder="Degree" required></div>
            <div class="col-md-3"><input name="institute" class="form-control" placeholder="Institute"></div>
            <div class="col-md-2"><input name="field" class="form-control" placeholder="Field"></div>
            <div class="col-md-1"><input name="result" class="form-control" placeholder="GPA"></div>
            <div class="col-md-1"><input name="passing_year" class="form-control" placeholder="Year"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
        </form>@endcan
    </div></div></div>

    <div class="tab-pane fade" id="experience"><div class="card"><div class="card-body">
        <table class="table align-middle"><thead><tr><th>Company</th><th>Title</th><th>From</th><th>To</th><th></th></tr></thead><tbody>
            @forelse ($e->experience as $r)
                <tr><td>{{ $r->company }}<div class="small text-body-secondary">{{ $r->responsibilities }}</div></td><td>{{ $r->title }}</td><td>{{ $r->from_date ? \Illuminate\Support\Carbon::parse($r->from_date)->format('M Y') : '' }}</td><td>{{ $r->to_date ? \Illuminate\Support\Carbon::parse($r->to_date)->format('M Y') : '' }}</td>
                    <td class="text-end">@can('hr-employees.edit')<form method="post" action="{{ route('admin.hr.employees.records.destroy', [$e, 'experience', $r->id]) }}" data-confirm="Remove this entry?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>@endcan</td></tr>
            @empty<tr><td colspan="5" class="text-body-secondary">No experience recorded.</td></tr>@endforelse
        </tbody></table>
        @can('hr-employees.edit')
        <form method="post" action="{{ route('admin.hr.employees.records.store', [$e, 'experience']) }}" class="row g-2 no-print">@csrf
            <div class="col-md-3"><input name="company" class="form-control" placeholder="Company" required></div>
            <div class="col-md-2"><input name="title" class="form-control" placeholder="Title"></div>
            <div class="col-md-2"><input name="from_date" class="form-control" data-date placeholder="From" autocomplete="off"></div>
            <div class="col-md-2"><input name="to_date" class="form-control" data-date placeholder="To" autocomplete="off"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Add</button></div>
            <div class="col-12"><textarea name="responsibilities" class="form-control" rows="2" placeholder="Responsibilities"></textarea></div>
        </form>@endcan
    </div></div></div>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var h=location.hash;if(h){var b=document.querySelector('[data-bs-target="'+h+'"]');if(b)bootstrap.Tab.getOrCreateInstance(b).show();}});</script>
@endsection
