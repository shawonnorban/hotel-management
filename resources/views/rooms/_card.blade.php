@php($stay = collect($search ?? [])->only(['checkin', 'checkout', 'adults', 'children'])->filter()->all())
<div class="col-md-6 col-lg-4">
    <div class="card room-card h-100">
        <a href="{{ route('rooms.show', ['room' => $room->roomid] + $stay) }}"><img src="{{ asset($image ?: 'assets/img/room_search.png') }}" class="thumb" alt="{{ $room->roomtype }}" loading="lazy"></a>
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <h3 class="h5 mb-1">{{ $room->roomtype }}</h3>
                <div class="text-end"><span class="price">{{ \App\Support\Money::format($room->rate) }}</span><div class="small text-body-secondary">per night</div></div>
            </div>
            <div class="small text-body-secondary mb-3 d-flex flex-wrap gap-3">
                <span><i class="bi bi-people me-1"></i>{{ $room->capacity }} guests</span>
                <span><i class="bi bi-aspect-ratio me-1"></i>{{ $room->size_label }}</span>
                @if ($room->bedType)<span><i class="bi bi-moon-stars me-1"></i>{{ $room->bedsno }} {{ $room->bedType->bedstypetitle }}</span>@endif
            </div>
            @isset($availableCount)
                <div class="small mb-3 {{ $availableCount > 0 ? 'text-success' : 'text-danger' }}"><i class="bi {{ $availableCount > 0 ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>{{ $availableCount > 0 ? $availableCount.' room(s) available for your dates' : 'Not available for your dates' }}</div>
            @endisset
            <a class="btn btn-primary mt-auto" href="{{ route('rooms.show', ['room' => $room->roomid] + $stay) }}">View &amp; book</a>
        </div>
    </div>
</div>
