@extends('layouts.admin')
@section('title', 'Rooms')
@section('content')
<h1 class="h4 mb-3">Rooms</h1>
<div class="table-responsive bg-white border rounded">
<table class="table mb-0">
    <thead><tr><th>Type</th><th>Capacity</th><th class="text-end">Rate</th><th>Active</th><th>Room numbers</th></tr></thead>
    <tbody>
    @forelse ($rooms as $room)
        <tr>
            <td>{{ $room->roomtype }}</td><td>{{ $room->capacity }}</td>
            <td class="text-end">{{ number_format($room->rate, 2) }}</td>
            <td>{{ $room->roomactive ? 'Yes' : 'No' }}</td>
            <td>{{ ($numbers[$room->roomid] ?? collect())->pluck('roomno')->implode(', ') ?: '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted text-center py-4">No rooms yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
@endsection
