<?php

namespace App\Services;

use App\Models\Legacy\BookedDetails;
use App\Models\Legacy\BookedInfo;
use App\Models\Legacy\Customerinfo;
use App\Models\Legacy\Roomdetails;
use App\Models\Legacy\Setting;
use App\Models\Legacy\TblRoomnofloorassign;
use App\Models\Legacy\TblTaxmgt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Room availability, pricing and booking creation.
 *
 * Prices are always calculated on the server from the room rate, the active taxes
 * and the service charge; nothing monetary is read from the request.
 */
class BookingService
{
    /** Booking statuses that no longer occupy a room (1 = cancelled, 5 = checked out). */
    private const FREED_STATUSES = ['1', '5'];

    public function nights(Carbon $checkin, Carbon $checkout): int
    {
        return max(1, (int) $checkin->copy()->startOfDay()->diffInDays($checkout->copy()->startOfDay()));
    }

    /** @return list<string> Room numbers of this room type that are free for the period. */
    public function availableRoomNumbers(int $roomId, Carbon $checkin, Carbon $checkout): array
    {
        $assigned = TblRoomnofloorassign::where('roomid', $roomId)->pluck('roomno')->map(fn ($n) => (string) $n)->all();

        $taken = BookedInfo::query()
            ->whereNotIn('bookingstatus', self::FREED_STATUSES)
            ->where('checkindate', '<', $checkout)
            ->where('checkoutdate', '>', $checkin)
            ->pluck('room_no')
            ->flatMap(fn ($list) => explode(',', (string) $list))
            ->map(fn ($n) => trim($n))
            ->filter()
            ->all();

        return array_values(array_diff($assigned, $taken));
    }

    /** @return array{nights:int,rate:float,rooms:int,subtotal:float,tax:float,service:float,total:float} */
    public function quote(Roomdetails $room, Carbon $checkin, Carbon $checkout, int $rooms): array
    {
        $nights = $this->nights($checkin, $checkout);
        $subtotal = round((float) $room->rate * $nights * $rooms, 2);

        $taxRate = (float) TblTaxmgt::where('isactive', 1)->sum('rate');
        $serviceRate = (float) (Setting::query()->value('servicecharge') ?? 0);

        $tax = round($subtotal * $taxRate / 100, 2);
        $service = round($subtotal * $serviceRate / 100, 2);

        return [
            'nights' => $nights,
            'rate' => (float) $room->rate,
            'rooms' => $rooms,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'service' => $service,
            'total' => round($subtotal + $tax + $service, 2),
        ];
    }

    /**
     * Create a pending booking (status 0) for the guest.
     *
     * @throws RuntimeException when not enough rooms are free any more
     * @throws InvalidArgumentException when the party does not fit the rooms
     */
    public function createBooking(
        Customerinfo $guest,
        Roomdetails $room,
        Carbon $checkin,
        Carbon $checkout,
        int $rooms,
        int $adults,
        int $children,
        ?string $guestName = null,
        ?string $specialRequest = null,
    ): BookedInfo {
        if ($checkout->lte($checkin)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }
        if ($adults + $children > max(1, (int) $room->capacity) * $rooms) {
            throw new InvalidArgumentException('The selected rooms cannot accommodate this many guests.');
        }

        return DB::transaction(function () use ($guest, $room, $checkin, $checkout, $rooms, $adults, $children, $guestName, $specialRequest) {
            // Serialise concurrent bookings of the same hotel so two guests cannot get the same room number.
            BookedInfo::query()->lockForUpdate()->orderByDesc('bookedid')->first();

            $free = $this->availableRoomNumbers((int) $room->roomid, $checkin, $checkout);
            if (count($free) < $rooms) {
                throw new RuntimeException('Sorry, the requested number of rooms is no longer available.');
            }

            $quote = $this->quote($room, $checkin, $checkout, $rooms);
            $roomNumbers = array_slice($free, 0, $rooms);
            $perRoom = fn (int $total) => implode(',', array_map(fn ($i) => intdiv($total, $rooms) + ($i < $total % $rooms ? 1 : 0), range(0, $rooms - 1)));
            $repeat = fn ($value) => implode(',', array_fill(0, $rooms, $value));

            $nextId = ((int) BookedInfo::query()->max('bookedid')) + 1;
            $booking = BookedInfo::create([
                'booking_number' => str_pad((string) $nextId, 8, '0', STR_PAD_LEFT),
                'date_time' => now(),
                'roomid' => $repeat($room->roomid),
                'nuofpeople' => $perRoom($adults),
                'children' => $perRoom($children),
                'total_room' => $rooms,
                'room_no' => implode(',', $roomNumbers),
                'roomrate' => $repeat($quote['rate']),
                'total_price' => $quote['total'],
                'paid_amount' => 0,
                'offer_discount' => $repeat(0),
                'full_guest_name' => $guestName ?: $guest->full_name,
                'special_request' => $specialRequest,
                'checkindate' => $checkin,
                'checkoutdate' => $checkout,
                'cutomerid' => $guest->customerid,
                'bookingstatus' => '0',
            ]);

            $zeros = $repeat(0);
            BookedDetails::create([
                'bookedid' => $booking->bookedid,
                'booking_type' => '', 'booking_source' => '', 'booking_source_no' => '',
                'extracheckin' => $repeat($checkin->toDateString()),
                'extracheckout' => $repeat($checkin->toDateString()),
                'arival_from' => '', 'purpose' => '',
                'extra_facility_days' => $zeros, 'extrabed' => $zeros, 'extraperson' => $zeros, 'extrachild' => $zeros,
                'complementary' => 'no', 'complementaryprice' => $zeros,
                'discountreason' => '', 'discountamount' => 0,
                'commissionpersent' => 0, 'commissionamount' => 0,
                'payment_method' => '', 'advance_amount' => 0, 'advance_remarks' => '', 'remarks' => '',
                'booked_from' => 1,
            ]);

            return $booking;
        });
    }
}
