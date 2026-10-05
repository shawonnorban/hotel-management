@extends('layouts.app')
@section('title', 'My bookings')
@section('content')
<h1 class="h4 mb-3">My bookings</h1>
<div class="table-responsive bg-white border rounded">
<table class="table table-hover mb-0">
    <thead><tr><th>Number</th><th>Stay</th><th>Rooms</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($bookings as $booking)
        <tr>
            <td><a href="{{ route('booking.show', $booking->booking_number) }}">{{ $booking->booking_number }}</a></td>
            <td>{{ $booking->checkindate->format('d M Y') }} → {{ $booking->checkoutdate->format('d M Y') }}</td>
            <td>{{ $booking->total_room }}</td>
            <td>{{ number_format($booking->total_price, 2) }}</td>
            <td>{{ $booking->status_label }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted text-center py-4">You have no bookings yet.</td></tr>
    @endforelse
    </tbody>
</table></div>
<div class="mt-3">{{ $bookings->links() }}</div>
@endsection
