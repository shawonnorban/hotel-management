<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\BookedInfo;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(BookedInfo::STATUS_LABELS))],
        ]);

        $bookings = BookedInfo::with('customer')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('booking_number', 'like', $like)
                    ->orWhere('full_guest_name', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('email', 'like', $like)->orWhere('cust_phone', 'like', $like)));
            })
            ->when(isset($filters['status']) && $filters['status'] !== null, fn ($q) => $q->where('bookingstatus', $filters['status']))
            ->orderByDesc('bookedid')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservations.index', ['bookings' => $bookings, 'filters' => $filters]);
    }

    public function show(BookedInfo $booking)
    {
        return view('admin.reservations.show', ['booking' => $booking->load('customer', 'details')]);
    }

    public function updateStatus(Request $request, BookedInfo $booking)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys(BookedInfo::STATUS_LABELS))]]);

        $allowed = BookedInfo::TRANSITIONS[(string) $booking->bookingstatus] ?? [];
        if (! in_array((string) $data['status'], $allowed, true)) {
            return back()->withErrors(['status' => 'That status change is not allowed from "'.$booking->status_label.'".']);
        }

        $booking->update(['bookingstatus' => (string) $data['status']]);

        return back()->with('status', 'Booking '.$booking->booking_number.' is now '.$booking->fresh()->status_label.'.');
    }
}
