@extends('layouts.admin')
@section('title', 'Attendance report')
@section('content')
@php($codes = ['present' => ['P', 'success'], 'late' => ['L', 'warning'], 'absent' => ['A', 'danger'], 'leave' => ['V', 'info'], 'half_day' => ['½', 'secondary']])
<div class="d-flex flex-wrap align-items-center gap-3 mb-4">
    <div class="me-auto"><h1 class="page-title">Attendance report</h1><p class="page-sub">{{ $start->format('F Y') }}</p></div>
    <form method="get" class="d-flex gap-2"><input type="month" name="month" class="form-control" value="{{ $month }}"><button class="btn btn-outline-primary">Show</button></form>
    <button class="btn btn-outline-secondary no-print" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0 text-center" style="font-size:.78rem">
    <thead><tr><th class="text-start">Employee</th>@foreach ($days as $d)<th class="{{ $calendar->isWorkingDay($d) ? '' : 'table-secondary' }}">{{ $d->day }}<div class="fw-normal text-body-secondary">{{ substr($d->format('D'), 0, 1) }}</div></th>@endforeach<th>P</th><th>A</th><th>V</th></tr></thead>
    <tbody>
    @forelse ($rows as $row)
        <tr><td class="text-start text-nowrap">{{ $row['employee']->full_name }}</td>
            @foreach ($days as $d)
                @php($rec = $row['byDay'][$d->day] ?? null)
                <td class="{{ $calendar->isWorkingDay($d) ? '' : 'table-secondary' }}">@if ($rec)<span class="badge text-bg-{{ $codes[$rec->status][1] }}">{{ $codes[$rec->status][0] }}</span>@endif</td>
            @endforeach
            <td class="fw-semibold">{{ $row['present'] }}</td><td class="fw-semibold">{{ $row['absent'] }}</td><td class="fw-semibold">{{ $row['leave'] }}</td></tr>
    @empty
        <tr><td colspan="{{ $days->count() + 4 }}" class="text-body-secondary py-4">No employees.</td></tr>
    @endforelse
    </tbody></table></div></div>
<p class="small text-body-secondary mt-2">P present · L late · A absent · V leave · ½ half day. Grey columns are days off or holidays.</p>
@endsection
