@extends('layouts.site')
@section('title', 'Book your stay')
@section('main-class', 'pb-5')
@section('hero')
<section class="hero">
    <div class="container">
        <span class="badge text-bg-light text-brand mb-3 px-3 py-2"><i class="bi bi-stars me-1"></i>Direct booking · best rate</span>
        <h1>Your comfortable stay starts here</h1>
        <p class="lead mt-3">Choose your dates, pick a room and confirm in minutes — no middleman, no hidden fees.</p>
    </div>
</section>
<div class="container">
    <div class="card search-card"><div class="card-body p-3 p-md-4">@include('rooms._search', ['search' => []])</div></div>
</div>
@endsection
@section('content')
<div class="container">
    <div class="row g-4 my-4 text-center">
        @foreach ([['bi-shield-check', 'Secure booking', 'Your details are protected and prices are confirmed before you pay.'], ['bi-cash-coin', 'Clear pricing', 'Taxes and service charges are shown up front — what you see is what you pay.'], ['bi-headset', 'Friendly service', 'Our front desk is a message away for special requests.']] as [$icon, $title, $text])
            <div class="col-md-4"><div class="feature-icon mx-auto mb-3"><i class="bi {{ $icon }}"></i></div><h3 class="h5">{{ $title }}</h3><p class="text-body-secondary mb-0">{{ $text }}</p></div>
        @endforeach
    </div>
    <div class="d-flex align-items-end justify-content-between mt-5 mb-4">
        <div><h2 class="section-title h1 mb-1">Our rooms</h2><p class="text-body-secondary mb-0">Find the room that fits your stay.</p></div>
        <a href="{{ route('rooms.index') }}" class="btn btn-outline-primary d-none d-sm-inline-flex">See all rooms <i class="bi bi-arrow-right ms-2"></i></a>
    </div>
    <div class="row g-4">
        @forelse ($rooms as $room)
            @include('rooms._card', ['room' => $room, 'image' => $images[$room->roomid] ?? null, 'search' => []])
        @empty
            <div class="col-12"><div class="empty card"><i class="bi bi-door-closed"></i>Rooms will appear here as soon as they are added.</div></div>
        @endforelse
    </div>
</div>
@endsection
