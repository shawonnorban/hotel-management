@extends('layouts.site')
@section('title', 'Rooms')
@section('content')
<div class="container">
    <h1 class="section-title mb-4">Rooms &amp; rates</h1>
    <div class="card mb-4"><div class="card-body p-3 p-md-4">@include('rooms._search')</div></div>
    <div class="row g-4">
        @forelse ($rooms as $room)
            @include('rooms._card', ['image' => $images[$room->roomid] ?? null, 'availableCount' => $searched ? ($available[$room->roomid] ?? 0) : null, 'search' => $search])
        @empty
            <div class="col-12"><div class="empty card"><i class="bi bi-door-closed"></i>No rooms found.</div></div>
        @endforelse
    </div>
</div>
@endsection
