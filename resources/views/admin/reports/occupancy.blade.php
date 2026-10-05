@extends('layouts.admin')
@section('title', 'Occupancy')
@section('content')
<h1 class="page-title mb-4">Occupancy</h1>
@include('admin.reports._filter', ['from' => $from, 'to' => $to])
<div class="row g-3 mb-4">
    @foreach ([['Rooms in the hotel', $summary['capacity']], ['Room nights sold', $summary['room_nights']], ['Average occupancy', $summary['occupancy'].'%']] as [$l, $v])
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="small text-body-secondary text-uppercase">{{ $l }}</div><div class="fs-4 fw-bold">{{ $v }}</div></div></div></div>
    @endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead><tr><th>Date</th>@foreach ($roomTypes as $t)<th class="text-end">{{ $t->roomtype }}</th>@endforeach<th class="text-end">Sold</th><th style="width:30%">Occupancy</th></tr></thead><tbody>
    @foreach ($days as $d)
        @php($pct = $summary['capacity'] ? round($d['total'] / $summary['capacity'] * 100) : 0)
        <tr><td>{{ $d['date']->format('D d M') }}</td>@foreach ($roomTypes as $t)<td class="text-end">{{ $d['sold'][$t->roomid] ?? 0 }}/{{ $inventory[$t->roomid] ?? 0 }}</td>@endforeach<td class="text-end fw-semibold">{{ $d['total'] }}</td>
            <td><div class="progress" style="height:10px"><div class="progress-bar {{ $pct >= 85 ? 'bg-success' : '' }}" style="width:{{ min(100, $pct) }}%"></div></div><span class="small text-body-secondary">{{ $pct }}%</span></td></tr>
    @endforeach
    </tbody></table></div></div>
@endsection
