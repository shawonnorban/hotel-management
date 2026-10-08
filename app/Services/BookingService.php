<?php

namespace App\Services;

use App\Models\BookedDetails;
use App\Models\BookedInfo;
use App\Models\Customerinfo;
use App\Models\Promocode;
use App\Models\Roomdetails;
use App\Models\Setting;
use App\Models\TblRoomnofloorassign;
use App\Models\TblRoomOffer;
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
    public function availableRoomNumbers(int $roomId, Carbon $checkin, Carbon $checkout, ?int $ignoreBookingId = null): array
    {
        $assigned = TblRoomnofloorassign::where('roomid', $roomId)->pluck('roomno')->map(fn ($n) => (string) $n)->all();

        $taken = BookedInfo::query()
            ->whereNotIn('bookingstatus', self::FREED_STATUSES)
            ->when($ignoreBookingId, fn ($q) => $q->where('bookedid', '!=', $ignoreBookingId))
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
        return $this->findPromoForRooms($code, [(int) $room->roomid], $checkin);
    }

    /** Like {@see findPromo()} for a booking with several room types: the code must fit at least one of them. */
    public function findPromoForRooms(?string $code, array $roomIds, Carbon $checkin): ?Promocode
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
            ->whereIn('roomid', array_merge([0], array_map('intval', $roomIds)))
            ->first();

        if (! $promo) {
            return null;
        }

        $used = BookedInfo::query()->whereRaw('UPPER(promocode) = ?', [$code])->whereNotIn('bookingstatus', ['1'])->exists();

        return $used ? null : $promo;
    }

    /**
     * Merge repeated room types: [['room' => Roomdetails, 'rooms' => int, 'adults' => int, 'children' => int, 'numbers' => list<string>]].
     *
     * @param  list<array{room:Roomdetails,rooms:int,adults?:int,children?:int,numbers?:list<string>}>  $lines
     * @return list<array{room:Roomdetails,rooms:int,adults:int,children:int,numbers:list<string>}>
     */
    public function mergeLines(array $lines): array
    {
        $merged = [];
        foreach ($lines as $line) {
            $id = (int) $line['room']->roomid;
            $merged[$id] ??= ['room' => $line['room'], 'rooms' => 0, 'adults' => 0, 'children' => 0, 'numbers' => []];
            $merged[$id]['rooms'] += max(0, (int) $line['rooms']);
            $merged[$id]['adults'] += max(0, (int) ($line['adults'] ?? 0));
            $merged[$id]['children'] += max(0, (int) ($line['children'] ?? 0));
            $merged[$id]['numbers'] = array_values(array_unique(array_merge($merged[$id]['numbers'], array_map('strval', $line['numbers'] ?? []))));
        }
        // Ticking more room numbers than the "rooms" box says means the clerk wants that many rooms.
        foreach ($merged as &$m) {
            $m['rooms'] = max($m['rooms'], count($m['numbers']));
        }

        return array_values(array_filter($merged, fn ($m) => $m['rooms'] > 0));
    }

    /** @return array{nights:int,rate:float,rooms:int,subtotal:float,discount:float,tax:float,service:float,total:float,offer:int,promo:int,manual_discount:float,lines:list<array>} */
    public function quote(Roomdetails $room, Carbon $checkin, Carbon $checkout, int $rooms, ?Promocode $promo = null, float $manualPercent = 0): array
    {
        return $this->quoteLines([['room' => $room, 'rooms' => $rooms]], $checkin, $checkout, $promo, $manualPercent);
    }

    /**
     * Price a stay made of one or more room types. Each type gets its own offer and promo discount; a hand-given
     * discount percentage then applies to what is left, and tax and service charge are charged on the net amount.
     */
    public function quoteLines(array $lines, Carbon $checkin, Carbon $checkout, ?Promocode $promo = null, float $manualPercent = 0): array
    {
        $lines = $this->mergeLines($lines);
        $nights = $this->nights($checkin, $checkout);

        $subtotal = 0.0;
        $afterPromoTotal = 0.0;
        $rows = [];
        foreach ($lines as $line) {
            /** @var Roomdetails $room */
            $room = $line['room'];
            $sub = round((float) $room->rate * $nights * $line['rooms'], 2);
            $offer = $this->offerPercent($room);
            $afterOffer = $sub - round($sub * $offer / 100, 2);
            $promoPercent = $promo && in_array((int) $promo->roomid, [0, (int) $room->roomid], true) ? (int) $promo->discount : 0;
            $afterPromo = round($afterOffer - round($afterOffer * $promoPercent / 100, 2), 2);

            $subtotal += $sub;
            $afterPromoTotal += $afterPromo;
            $rows[] = ['room_id' => (int) $room->roomid, 'room' => $room->roomtype, 'rooms' => $line['rooms'], 'rate' => (float) $room->rate, 'nights' => $nights, 'subtotal' => $sub, 'offer' => $offer, 'promo' => $promoPercent, 'net' => $afterPromo];
        }
        $subtotal = round($subtotal, 2);
        $afterPromoTotal = round($afterPromoTotal, 2);

        // Discount a clerk grants by hand, on top of the offer and promo code.
        $manualPercent = max(0, min(100, $manualPercent));
        $manual = round($afterPromoTotal * $manualPercent / 100, 2);
        $net = round($afterPromoTotal - $manual, 2);
        $discount = round($subtotal - $net, 2);

        $taxRate = (float) TblTaxmgt::where('isactive', 1)->sum('rate');
        $serviceRate = (float) (Setting::query()->value('servicecharge') ?? 0);
        $tax = round($net * $taxRate / 100, 2);
        $service = round($net * $serviceRate / 100, 2);

        return [
            'nights' => $nights,
            'rate' => (float) ($rows[0]['rate'] ?? 0),
            'rooms' => array_sum(array_column($rows, 'rooms')),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'service' => $service,
            'total' => round($net + $tax + $service, 2),
            'offer' => (int) max([0, ...array_column($rows, 'offer')]),
            'promo' => (int) max([0, ...array_column($rows, 'promo')]),
            'manual_discount' => $manual,
            'tax_rate' => $taxRate,
            'service_rate' => $serviceRate,
            'lines' => $rows,
        ];
    }

    /** Create a pending booking for one room type (status 0 unless told otherwise). See {@see createBookingLines()}. */
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
        string $status = '0',
        string $source = 'website',
        array $extras = [],
        array $wantedRooms = [],
    ): BookedInfo {
        if ($wantedRooms && count($wantedRooms) > $rooms) {
            throw new InvalidArgumentException('You chose more room numbers than rooms.');
        }

        return $this->createBookingLines($guest, [['room' => $room, 'rooms' => $rooms, 'adults' => $adults, 'children' => $children, 'numbers' => $wantedRooms]], $checkin, $checkout, $guestName, $specialRequest, $promo, $status, $source, $extras);
    }

    /**
     * Create a booking for one or more room types.
     *
     * @throws RuntimeException when not enough rooms are free any more
     * @throws InvalidArgumentException when the party does not fit the rooms
     */
    public function createBookingLines(
        Customerinfo $guest,
        array $lines,
        Carbon $checkin,
        Carbon $checkout,
        ?string $guestName = null,
        ?string $specialRequest = null,
        ?Promocode $promo = null,
        string $status = '0',
        string $source = 'website',
        array $extras = [],
    ): BookedInfo {
        if ($checkout->lte($checkin)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }
        $lines = $this->mergeLines($lines);
        if (! $lines) {
            throw new InvalidArgumentException('Choose at least one room.');
        }
        $this->assertCapacity($lines);

        return DB::transaction(function () use ($guest, $lines, $checkin, $checkout, $guestName, $specialRequest, $promo, $status, $source, $extras) {
            // Serialise concurrent bookings of the same hotel so two guests cannot get the same room number.
            BookedInfo::query()->lockForUpdate()->orderByDesc('bookedid')->first();

            $allocated = $this->allocate($lines, $checkin, $checkout, null, []);

            if ($promo && ! $this->findPromoForRooms($promo->promocode, array_map(fn ($l) => (int) $l['room']->roomid, $lines), $checkin)) {
                throw new InvalidArgumentException('That promo code can no longer be used.');
            }

            $quote = $this->quoteLines($lines, $checkin, $checkout, $promo, (float) ($extras['discount_percent'] ?? 0));
            $columns = $this->roomColumns($lines, $allocated, $quote);

            $nextId = ((int) BookedInfo::query()->max('bookedid')) + 1;
            $booking = BookedInfo::create($columns + [
                'booking_number' => str_pad((string) $nextId, 8, '0', STR_PAD_LEFT),
                'date_time' => now(),
                'paid_amount' => 0,
                'promocode' => $promo?->promocode,
                'full_guest_name' => $guestName ?: $guest->full_name,
                'special_request' => $specialRequest,
                'checkindate' => $checkin,
                'checkoutdate' => $checkout,
                'cutomerid' => $guest->customerid,
                'bookingstatus' => $status,
                'source' => $source,
            ]);

            $total = (int) $columns['total_room'];
            $repeat = fn ($value) => implode(',', array_fill(0, $total, $value));
            $zeros = $repeat(0);
            BookedDetails::create([
                'bookedid' => $booking->bookedid,
                'booking_type' => (string) ($extras['booking_type'] ?? ''), 'booking_source' => (string) ($extras['booking_source'] ?? ''), 'booking_source_no' => (string) ($extras['booking_source_no'] ?? ''),
                'extracheckin' => $repeat($checkin->toDateString()),
                'extracheckout' => $repeat($checkin->toDateString()),
                'arival_from' => (string) ($extras['arrival_from'] ?? ''), 'purpose' => (string) ($extras['purpose'] ?? ''),
                'extra_facility_days' => $zeros, 'extrabed' => $zeros, 'extraperson' => $zeros, 'extrachild' => $zeros,
                'complementary' => ! empty($extras['complementary']) ? (string) $extras['complementary'] : 'no', 'complementaryprice' => $zeros,
                'discountreason' => (string) ($extras['discount_reason'] ?? ''), 'discountamount' => $quote['manual_discount'],
                'commissionpersent' => (float) ($extras['commission_percent'] ?? 0), 'commissionamount' => round($quote['total'] * (float) ($extras['commission_percent'] ?? 0) / 100, 2),
                'payment_method' => '', 'advance_amount' => 0, 'advance_remarks' => (string) ($extras['advance_remarks'] ?? ''), 'remarks' => (string) ($extras['remarks'] ?? ''),
                'booked_from' => 1,
            ]);

            return $booking;
        });
    }

    /** Change the stay of a booking for one room type. See {@see modifyLines()}. */
    public function modify(
        BookedInfo $booking,
        Roomdetails $room,
        Carbon $checkin,
        Carbon $checkout,
        int $rooms,
        int $adults,
        int $children,
        ?Promocode $promo = null,
    ): BookedInfo {
        return $this->modifyLines($booking, [['room' => $room, 'rooms' => $rooms, 'adults' => $adults, 'children' => $children]], $checkin, $checkout, $promo);
    }

    /**
     * Change the stay of a booking that has not been checked in yet (dates, room types, number of rooms, party size).
     * Room numbers are re-allocated, keeping the booking's current rooms of the same type where they are still free.
     */
    public function modifyLines(BookedInfo $booking, array $lines, Carbon $checkin, Carbon $checkout, ?Promocode $promo = null): BookedInfo
    {
        if (! in_array((string) $booking->bookingstatus, ['0', '2'], true)) {
            throw new InvalidArgumentException('Only pending or confirmed bookings can be changed.');
        }
        if ($checkout->lte($checkin)) {
            throw new InvalidArgumentException('Check-out must be after check-in.');
        }
        $lines = $this->mergeLines($lines);
        if (! $lines) {
            throw new InvalidArgumentException('Choose at least one room.');
        }
        $this->assertCapacity($lines);

        return DB::transaction(function () use ($booking, $lines, $checkin, $checkout, $promo) {
            BookedInfo::query()->lockForUpdate()->orderByDesc('bookedid')->first();
            $booking = BookedInfo::findOrFail($booking->bookedid);

            $keep = [];
            foreach ($booking->roomLines() as $current) {
                $keep[$current['room_id']] = $current['numbers'];
            }
            $allocated = $this->allocate($lines, $checkin, $checkout, (int) $booking->bookedid, $keep);

            $quote = $this->quoteLines($lines, $checkin, $checkout, $promo);
            $columns = $this->roomColumns($lines, $allocated, $quote);

            $booking->update($columns + [
                'total_price' => round($quote['total'] + (float) $booking->extras_amount, 2),
                'promocode' => $promo?->promocode ?? $booking->promocode,
                'checkindate' => $checkin,
                'checkoutdate' => $checkout,
            ]);

            return $booking->fresh();
        });
    }

    private function assertCapacity(array $lines): void
    {
        foreach ($lines as $line) {
            if ($line['adults'] + $line['children'] > max(1, (int) $line['room']->capacity) * $line['rooms']) {
                throw new InvalidArgumentException('The '.$line['room']->roomtype.' rooms cannot accommodate this many guests.');
            }
        }
    }

    /**
     * Pick room numbers for every line: first the ones the clerk chose, then the ones the booking already holds
     * (when editing), then any other free room of that type.
     *
     * @param  array<int,list<string>>  $keep  room type id => numbers currently held
     * @return array<int,list<string>> room type id => numbers
     */
    private function allocate(array $lines, Carbon $checkin, Carbon $checkout, ?int $ignoreBookingId, array $keep): array
    {
        $out = [];
        foreach ($lines as $line) {
            $id = (int) $line['room']->roomid;
            $free = $this->availableRoomNumbers($id, $checkin, $checkout, $ignoreBookingId);
            if (count($free) < $line['rooms']) {
                throw new RuntimeException('Sorry, only '.count($free).' '.$line['room']->roomtype.' room(s) are available for those dates.');
            }
            $chosen = array_map('strval', $line['numbers']);
            if (array_diff($chosen, $free)) {
                throw new RuntimeException('One of the chosen '.$line['room']->roomtype.' rooms is not available for those dates.');
            }
            $held = array_values(array_intersect(array_map('strval', $keep[$id] ?? []), $free));
            $out[$id] = array_slice(array_values(array_unique(array_merge($chosen, $held, $free))), 0, $line['rooms']);
        }

        return $out;
    }

    /** The per-room comma lists the legacy table stores, plus the price breakdown. */
    private function roomColumns(array $lines, array $allocated, array $quote): array
    {
        $ids = $rates = $adults = $children = $numbers = [];
        foreach ($lines as $line) {
            $id = (int) $line['room']->roomid;
            $n = $line['rooms'];
            for ($i = 0; $i < $n; $i++) {
                $ids[] = $id;
                $rates[] = (float) $line['room']->rate;
                $adults[] = intdiv($line['adults'], $n) + ($i < $line['adults'] % $n ? 1 : 0);
                $children[] = intdiv($line['children'], $n) + ($i < $line['children'] % $n ? 1 : 0);
                $numbers[] = $allocated[$id][$i];
            }
        }
        $total = count($ids);
        $discount = (int) round($quote['discount']);
        $perRoomDiscount = array_map(fn ($i) => intdiv($discount, $total) + ($i < $discount % $total ? 1 : 0), range(0, $total - 1));

        return [
            'roomid' => implode(',', $ids),
            'nuofpeople' => implode(',', $adults),
            'children' => implode(',', $children),
            'total_room' => $total,
            'room_no' => implode(',', $numbers),
            'roomrate' => implode(',', $rates),
            'total_price' => $quote['total'],
            'subtotal' => $quote['subtotal'],
            'discount_amount' => $quote['discount'],
            'tax_amount' => $quote['tax'],
            'service_amount' => $quote['service'],
            'offer_discount' => implode(',', $perRoomDiscount),
        ];
    }
}
