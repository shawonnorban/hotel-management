@extends('layouts.app')
@section('content')
<div class="p-5 mb-4 bg-white rounded-3 border">
    <h1 class="display-6">Welcome to {{ $hotelName }}</h1>
    <p class="lead">Find a room and book it in a few steps.</p>
    @include('rooms._search', ['search' => []])
</div>
<h2 class="h4 mb-3">Our rooms</h2>
<div class="row g-4">
    @forelse ($rooms as $room)
        @include('rooms._card', ['room' => $room, 'image' => $images[$room->roomid] ?? null])
    @empty
        <p class="text-muted">No rooms are available to book yet.</p>
    @endforelse
</div>
@endsection
