<div class="col-md-4">
    <div class="card h-100 shadow-sm">
        <img src="{{ asset($image ?: 'assets/img/room_search.png') }}" class="card-img-top" alt="{{ $room->roomtype }}" style="height:200px;object-fit:cover">
        <div class="card-body d-flex flex-column">
            <h3 class="h5 card-title">{{ $room->roomtype }}</h3>
            <p class="card-text text-muted small mb-1">Sleeps {{ $room->capacity }} · {{ $room->roomsize }} {{ $room->roomsizemesurement }}</p>
            <p class="card-text fw-bold">{{ number_format($room->rate, 2) }} <span class="text-muted fw-normal">/ night</span></p>
            @isset($availableCount)
                <p class="small {{ $availableCount > 0 ? 'text-success' : 'text-danger' }}">{{ $availableCount > 0 ? $availableCount.' room(s) available' : 'Not available for these dates' }}</p>
            @endisset
            <a class="btn btn-outline-primary mt-auto" href="{{ route('rooms.show', array_filter(['room' => $room->roomid] + ($search ?? []))) }}">View &amp; book</a>
        </div>
    </div>
</div>
