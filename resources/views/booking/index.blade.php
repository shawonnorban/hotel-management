@extends('layouts.site')
@section('title', 'My bookings')
@section('content')
<div class="container">
    <h1 class="section-title mb-4">My bookings</h1>
    <div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Booking</th><th>Stay</th><th>Rooms</th><th class="text-end">Total</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($bookings as $booking)
            <tr>
                <td><a class="fw-semibold text-decoration-none" href="{{ route('booking.show', $booking->booking_number) }}">#{{ $booking->booking_number }}</a></td>
                <td>{{ $booking->checkindate->format('d M Y') }} → {{ $booking->checkoutdate->format('d M Y') }}</td>
                <td>{{ $booking->total_room }}</td>
                <td class="text-end">{{ \App\Support\Money::format($booking->total_price) }}</td>
                <td><span class="pill {{ $booking->status_pill }}">{{ $booking->status_label }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><i class="bi bi-calendar-x"></i>You have no bookings yet. <a href="{{ route('rooms.index') }}">Find a room</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($bookings->hasPages())<div class="card-footer bg-transparent">{{ $bookings->links() }}</div>@endif
    </div>
</div>
@endsection
