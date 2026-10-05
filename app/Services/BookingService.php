<?php

namespace App\Services;

use App\Models\BookedDetails;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\Roomdetails;
use App\Models\Promocode;
use App\Models\Setting;
use App\Models\TblRoomOffer;
use App\Models\TblRoomnofloorassign;
use App\Models\TblTaxmgt;
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

    /** The active room offer (percent) for this room type, 0 when none. */
    public function offerPercent(Roomdetails $room, ?Carbon $on = null): int
    {
        $on ??= now();

        return (int) TblRoomOffer::query()
            ->where('roomid', $room->roomid)
            ->whereDate('offer_date', '>=', $on->toDateString())
            ->max('offer');
    }

    /**
     * Resolve a promo code for this room and stay; null when it is unknown, expired, inactive,
     * for another room type, or already used (codes are single-use).
     */
    public function findPromo(?string $code, Roomdetails $room, Carbon $checkin): ?Promocode
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        $promo = Promocode::query()
            ->whereRaw('UPPER(promocode) = ?', [$code])
            ->where('status', 1)
            ->whereDate('startdate', '<=', now()->toDateString())
            ->whereDate('enddate', '>=', now()->toDateString())
            ->whereIn('roomid', [0, $room->roomid])
            ->first();

        if (! $promo) {
            return null;
        }

        $used = BookedInfo::query()->whereRaw('UPPER(promocode) = ?', [$code])->whereNotIn('bookingstatus', ['1'])->exists();

        return $used ? null : $promo;
    }

    /** @return array{nights:int,rate:float,rooms:int,subtotal:float,discount:float,tax:float,service:float,total:float,offer:int,promo:int} */
    public function quote(Roomdetails $room, Carbon $checkin, Carbon $checkout, int $rooms, ?Promocode $promo = null): array
    {
        $nights = $this->nights($checkin, $checkout);
        $subtotal = round((float) $room->rate * $nights * $rooms, 2);

        $offer = $this->offerPercent($room);
        $afterOffer = $subtotal - round($subtotal * $offer / 100, 2);
        $promoPercent = $promo ? (int) $promo->discount : 0;
        $net = round($afterOffer - round($afterOffer * $promoPercent / 100, 2), 2);
        $discount = round($subtotal - $net, 2);

        $taxRate = (float) TblTaxmgt::where('isactive', 1)->sum('rate');
        $serviceRate = (float) (Setting::query()->value('servicecharge') ?? 0);

        $tax = round($net * $taxRate / 100, 2);
        $service = round($net * $serviceRate / 100, 2);

        return [
            'nights' => $nights,
            'rate' => (float) $room->rate,
            'rooms' => $rooms,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'service' => $service,
            'total' => round($net + $tax + $service, 2),
            'offer' => $offer,
            'promo' => $promoPercent,
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
        ?Promocode $promo = null,
    ): BookedInfo {
        if ($checkout->lte($checkin)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }
        if ($adults + $children > max(1, (int) $room->capacity) * $rooms) {
            throw new InvalidArgumentException('The selected rooms cannot accommodate this many guests.');
        }

        return DB::transaction(function () use ($guest, $room, $checkin, $checkout, $rooms, $adults, $children, $guestName, $specialRequest, $promo) {
            // Serialise concurrent bookings of the same hotel so two guests cannot get the same room number.
            BookedInfo::query()->lockForUpdate()->orderByDesc('bookedid')->first();

            $free = $this->availableRoomNumbers((int) $room->roomid, $checkin, $checkout);
            if (count($free) < $rooms) {
                throw new RuntimeException('Sorry, the requested number of rooms is no longer available.');
            }

            if ($promo && ! $this->findPromo($promo->promocode, $room, $checkin)) {
                throw new InvalidArgumentException('That promo code can no longer be used.');
            }

            $quote = $this->quote($room, $checkin, $checkout, $rooms, $promo);
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
                'subtotal' => $quote['subtotal'],
                'discount_amount' => $quote['discount'],
                'tax_amount' => $quote['tax'],
                'service_amount' => $quote['service'],
                'paid_amount' => 0,
                'offer_discount' => $perRoom((int) round($quote['discount'])),
                'promocode' => $promo?->promocode,
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
