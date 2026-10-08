@extends('layouts.admin')
@section('title', 'Roster list')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3"><div class="me-auto"><h1 class="page-title">Roster list</h1><p class="page-sub">Who works which shift, day by day.</p></div>
    @can('hr-roster.manage')<a href="{{ route('admin.hr.roster.assign') }}" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i>Assign roster</a>@endcan</div>
<form method="get" class="row g-2 mb-3 no-print">
    <div class="col-md-2"><input name="from" class="form-control" data-date value="{{ $from->toDateString() }}"></div>
    <div class="col-md-2"><input name="to" class="form-control" data-date value="{{ $to->toDateString() }}"></div>
    <div class="col-md-3"><select name="department" class="form-select"><option value="">All departments</option>@foreach ($departments as $id => $name)<option value="{{ $id }}" @selected((int) $department === $id)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Show</button> <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i></button></div>
</form>
<div class="mb-2 small">@foreach ($shifts as $s)<span class="badge me-1" style="background:{{ $s->color }}">{{ $s->name }} {{ $s->hours_label }}</span>@endforeach<span class="badge text-bg-secondary">Day off</span></div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0 text-center">
    <thead><tr><th class="text-start">Employee</th>@foreach ($days as $d)<th class="{{ $d->isToday() ? 'table-primary' : '' }}"><div class="small text-body-secondary">{{ $d->format('D') }}</div>{{ $d->format('d M') }}</th>@endforeach</tr></thead><tbody>
    @forelse ($employees as $e)
        <tr><td class="text-start text-nowrap"><a class="text-decoration-none" href="{{ route('admin.hr.employees.profile', $e) }}">{{ $e->full_name }}</a><div class="small text-body-secondary">{{ $e->department?->name }}</div></td>
        @foreach ($days as $d)
            @php $c = $cells->get($e->id)?->get($d->toDateString()); @endphp
            <td>@if ($c && $c->shift)<span class="badge" style="background:{{ $c->shift->color }}" title="{{ $c->shift->hours_label }}">{{ $c->shift->name }}</span>@elseif ($c)<span class="badge text-bg-secondary">Off</span>@else<span class="text-body-tertiary">·</span>@endif</td>
        @endforeach</tr>
    @empty
        <tr><td colspan="{{ $days->count() + 1 }}"><div class="empty"><i class="bi bi-calendar-week"></i>No employees.</div></td></tr>
    @endforelse
    </tbody></table></div></div>
@endsection
