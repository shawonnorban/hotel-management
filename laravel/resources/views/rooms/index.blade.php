@extends('layouts.app')
@section('title', 'Rooms')
@section('content')
<div class="bg-white border rounded-3 p-4 mb-4">@include('rooms._search')</div>
<div class="row g-4">
    @forelse ($rooms as $room)
        @include('rooms._card', ['image' => $images[$room->roomid] ?? null, 'availableCount' => $searched ? ($available[$room->roomid] ?? 0) : null])
    @empty
        <p class="text-muted">No rooms found.</p>
    @endforelse
</div>
@endsection
