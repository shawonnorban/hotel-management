@extends('layouts.site')
@section('title', $room->roomtype)
@section('description', \Illuminate\Support\Str::limit($room->roomdescription, 150))
@section('content')
<div class="container">
    <nav class="small mb-3"><a href="{{ route('rooms.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> All rooms</a></nav>
    <div class="row g-4">
        <div class="col-lg-7">
            <h1 class="section-title">{{ $room->roomtype }}</h1>
            <div class="d-flex flex-wrap gap-3 text-body-secondary mb-3">
                <span><i class="bi bi-people me-1"></i>Sleeps {{ $room->capacity }}</span>
                <span><i class="bi bi-aspect-ratio me-1"></i>{{ $room->size_label }}</span>
                @if ($room->bedType)<span><i class="bi bi-moon-stars me-1"></i>{{ $room->bedsno }} {{ $room->bedType->bedstypetitle }} bed(s)</span>@endif
            </div>
            @php($gallery = $images->isEmpty() ? collect(['assets/img/room_search.png']) : $images)
            <div id="gallery" class="carousel slide mb-4 rounded-3 overflow-hidden shadow-sm" data-bs-ride="false">
                <div class="carousel-inner">
                    @foreach ($gallery as $image)<div class="carousel-item {{ $loop->first ? 'active' : '' }}"><img src="{{ asset($image) }}" class="d-block w-100" style="height:420px;object-fit:cover" alt="{{ $room->roomtype }}"></div>@endforeach
                </div>
                @if ($gallery->count() > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#gallery" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                    <button class="carousel-control-next" type="button" data-bs-target="#gallery" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                @endif
            </div>
            <p class="lead fs-6">{{ $room->roomdescription }}</p>
            @if ($facilities->isNotEmpty())
                <h2 class="h5 mt-4">Facilities</h2>
                <div class="row g-2">@foreach ($facilities as $facility)<div class="col-6 col-md-4"><i class="bi bi-check2-circle text-brand me-2"></i>{{ $facility }}</div>@endforeach</div>
            @endif
            @if ($room->reservecondition)<h2 class="h5 mt-4">Booking conditions</h2><div class="text-body-secondary small">{!! nl2br(e($room->reservecondition)) !!}</div>@endif
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm position-sticky" style="top:90px"><div class="card-body p-4">
                <div class="d-flex align-items-baseline gap-2 mb-3"><span class="price fs-3">{{ \App\Support\Money::format($room->rate) }}</span><span class="text-body-secondary">per night</span></div>
                <form method="get" class="row g-2 mb-3">
                    <div class="col-6"><label class="small fw-semibold">Check-in</label><input type="text" name="checkin" class="form-control" data-date data-min="today" value="{{ $search['checkin'] ?? '' }}" required autocomplete="off"></div>
                    <div class="col-6"><label class="small fw-semibold">Check-out</label><input type="text" name="checkout" class="form-control" data-date data-min="today" value="{{ $search['checkout'] ?? '' }}" required autocomplete="off"></div>
                    <div class="col-4"><label class="small fw-semibold">Rooms</label><input type="number" name="rooms" class="form-control" min="1" max="10" value="{{ $search['rooms'] ?? 1 }}"></div>
                    <div class="col-4"><label class="small fw-semibold">Adults</label><input type="number" name="adults" class="form-control" min="1" value="{{ $search['adults'] ?? 2 }}"></div>
                    <div class="col-4"><label class="small fw-semibold">Children</label><input type="number" name="children" class="form-control" min="0" value="{{ $search['children'] ?? 0 }}"></div>
                    <div class="col-12"><button class="btn btn-outline-primary w-100">Check availability</button></div>
                </form>
                @if ($quote)
                    @if ($free < $quote['rooms'])
                        <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Only {{ $free }} room(s) free for these dates. Try other dates or fewer rooms.</div>
                    @else
                        <table class="table table-sm mb-3">
                            <tr><td>{{ \App\Support\Money::format($quote['rate']) }} × {{ $quote['nights'] }} night(s) × {{ $quote['rooms'] }} room(s)</td><td class="text-end">{{ \App\Support\Money::format($quote['subtotal']) }}</td></tr>
                            @if ($quote['discount'] > 0)<tr class="text-success"><td>Offer discount</td><td class="text-end">−{{ \App\Support\Money::format($quote['discount']) }}</td></tr>@endif
                            <tr><td>Taxes</td><td class="text-end">{{ \App\Support\Money::format($quote['tax']) }}</td></tr>
                            <tr><td>Service charge</td><td class="text-end">{{ \App\Support\Money::format($quote['service']) }}</td></tr>
                            <tr class="fw-bold fs-5"><td>Total</td><td class="text-end">{{ \App\Support\Money::format($quote['total']) }}</td></tr>
                        </table>
                        @auth('customer')
                            <form method="post" action="{{ route('booking.store') }}">
                                @csrf
                                <input type="hidden" name="room" value="{{ $room->roomid }}">
                                <input type="hidden" name="checkin" value="{{ $search['checkin'] }}">
                                <input type="hidden" name="checkout" value="{{ $search['checkout'] }}">
                                <input type="hidden" name="rooms" value="{{ $quote['rooms'] }}">
                                <input type="hidden" name="adults" value="{{ $search['adults'] ?? 2 }}">
                                <input type="hidden" name="children" value="{{ $search['children'] ?? 0 }}">
                                <div class="mb-2"><label class="small fw-semibold">Promo code</label><input name="promo" class="form-control text-uppercase" maxlength="50" placeholder="Optional"></div>
                                <div class="mb-2"><label class="small fw-semibold">Guest name</label><input name="guest" class="form-control" maxlength="255" value="{{ auth('customer')->user()->full_name }}"></div>
                                <div class="mb-3"><label class="small fw-semibold">Special requests</label><textarea name="special" class="form-control" rows="2" maxlength="1000"></textarea></div>
                                <button class="btn btn-primary btn-lg w-100">Book now</button>
                            </form>
                        @else
                            <a class="btn btn-primary btn-lg w-100" href="{{ route('login') }}">Sign in to book</a>
                        @endauth
                    @endif
                @endif
            </div></div>
        </div>
    </div>
</div>
@endsection
