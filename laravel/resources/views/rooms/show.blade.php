@extends('layouts.app')
@section('title', $room->roomtype)
@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <h1 class="h3">{{ $room->roomtype }}</h1>
        <div class="row g-2 mb-3">
            @forelse ($images as $image)
                <div class="col-6"><img src="{{ asset($image) }}" class="img-fluid rounded" alt=""></div>
            @empty
                <div class="col-6"><img src="{{ asset('assets/img/room_search.png') }}" class="img-fluid rounded" alt=""></div>
            @endforelse
        </div>
        <p>{{ $room->roomdescription }}</p>
        <ul class="list-unstyled text-muted">
            <li>Sleeps up to {{ $room->capacity }}</li>
            <li>Size: {{ $room->roomsize }} {{ $room->roomsizemesurement }}</li>
            <li>Rate: {{ number_format($room->rate, 2) }} per night</li>
        </ul>
        @if ($room->reservecondition)<div class="small text-muted">{!! nl2br(e($room->reservecondition)) !!}</div>@endif
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h5">Check availability</h2>
            <form method="get" class="row g-2 mb-3">
                <div class="col-6"><input type="date" name="checkin" class="form-control" min="{{ date('Y-m-d') }}" value="{{ $search['checkin'] ?? '' }}" required></div>
                <div class="col-6"><input type="date" name="checkout" class="form-control" min="{{ date('Y-m-d') }}" value="{{ $search['checkout'] ?? '' }}" required></div>
                <div class="col-4"><label class="small">Rooms</label><input type="number" name="rooms" class="form-control" min="1" max="10" value="{{ $search['rooms'] ?? 1 }}"></div>
                <div class="col-4"><label class="small">Adults</label><input type="number" name="adults" class="form-control" min="1" value="{{ $search['adults'] ?? 2 }}"></div>
                <div class="col-4"><label class="small">Children</label><input type="number" name="children" class="form-control" min="0" value="{{ $search['children'] ?? 0 }}"></div>
                <div class="col-12"><button class="btn btn-outline-primary w-100">Check</button></div>
            </form>
            @if ($quote)
                @if ($free < $quote['rooms'])
                    <div class="alert alert-warning mb-0">Only {{ $free }} room(s) free for these dates.</div>
                @else
                    <table class="table table-sm">
                        <tr><td>{{ number_format($quote['rate'], 2) }} × {{ $quote['nights'] }} night(s) × {{ $quote['rooms'] }} room(s)</td><td class="text-end">{{ number_format($quote['subtotal'], 2) }}</td></tr>
                        <tr><td>Taxes</td><td class="text-end">{{ number_format($quote['tax'], 2) }}</td></tr>
                        <tr><td>Service charge</td><td class="text-end">{{ number_format($quote['service'], 2) }}</td></tr>
                        <tr class="fw-bold"><td>Total</td><td class="text-end">{{ number_format($quote['total'], 2) }}</td></tr>
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
                            <div class="mb-2"><label class="form-label small">Guest name (optional)</label><input name="guest" class="form-control" maxlength="255"></div>
                            <div class="mb-2"><label class="form-label small">Special requests (optional)</label><textarea name="special" class="form-control" rows="2" maxlength="1000"></textarea></div>
                            <button class="btn btn-primary w-100">Book now</button>
                        </form>
                    @else
                        <a class="btn btn-primary w-100" href="{{ route('login') }}">Sign in to book</a>
                    @endauth
                @endif
            @endif
        </div></div>
    </div>
</div>
@endsection
