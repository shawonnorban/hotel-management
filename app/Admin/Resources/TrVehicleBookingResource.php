<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TrFlight;
use App\Models\TrVehicle;
use App\Models\TrVehicleBooking;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;

class TrVehicleBookingResource extends Resource
{
    public static string $model = TrVehicleBooking::class;

    public static string $slug = 'tr-vehicle-bookings';

    public static string $label = 'Vehicle booking list';

    public static string $singular = 'Vehicle booking';

    public static string $icon = 'bi-calendar2-check';

    public static string $group = 'Transport';

    public static ?string $orderBy = 'pickup_at';

    public function with(): array
    {
        return ['vehicle'];
    }

    public function searchable(): array
    {
        return ['guest_name', 'booking_number', 'pickup_location', 'drop_location'];
    }

    public function fields(): array
    {
        return [
            Field::select('vehicle_id', 'Vehicle', fn () => TrVehicle::where('is_active', true)->orderBy('reg_no')->get()->mapWithKeys(fn ($v) => [$v->id => $v->reg_no.' · '.$v->type])->all())->required()->listed(),
            Field::text('guest_name', 'Guest')->required()->rules('max:150')->listed(),
            Field::text('booking_number', 'Booking no.')->rules('max:30'),
            Field::select('flight_id', 'Flight', fn () => TrFlight::orderByDesc('flight_at')->limit(200)->get()->mapWithKeys(fn ($f) => [$f->id => $f->flight_no.' · '.$f->guest_name.' · '.$f->flight_at->format('d M H:i')])->all()),
            Field::datetime('pickup_at', 'Pick-up time')->required()->listed(),
            Field::datetime('return_at', 'Expected return')->rules('after:pickup_at'),
            Field::text('pickup_location', 'Pick-up location')->required()->rules('max:191')->listed(),
            Field::text('drop_location', 'Drop location')->required()->rules('max:191')->listed(),
            Field::number('distance_km', 'Distance (km)')->default(0)->rules('numeric|min:0'),
            Field::money('amount', 'Charge')->default(0)->rules('min:0')->help('Leave 0 to price it from the vehicle: base fare + rate per km × distance.')->listed(),
            Field::select('status', 'Status', TrVehicleBooking::STATUSES)->required()->default('booked')->listed(),
            Field::textarea('notes', 'Notes')->col(12),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['distance_km'] = $data['distance_km'] ?? 0;
        $vehicle = TrVehicle::find($data['vehicle_id']);
        if ((float) ($data['amount'] ?? 0) <= 0 && $vehicle) {
            $data['amount'] = round((float) $vehicle->base_fare + (float) $vehicle->rate_per_km * (float) ($data['distance_km'] ?? 0), 2);
        }

        // A vehicle can't be in two places: block overlapping trips (a trip without a return time is assumed to take 4 hours).
        if (($data['status'] ?? 'booked') !== 'cancelled') {
            $start = Carbon::parse($data['pickup_at']);
            $end = ! empty($data['return_at']) ? Carbon::parse($data['return_at']) : $start->copy()->addHours(4);
            $clash = TrVehicleBooking::where('vehicle_id', $data['vehicle_id'])->where('status', '!=', 'cancelled')
                ->when($model, fn ($q) => $q->where('id', '!=', $model->id))
                ->get()->first(function ($b) use ($start, $end) {
                    $bEnd = $b->return_at ?? $b->pickup_at->copy()->addHours(4);

                    return $b->pickup_at->lt($end) && $bEnd->gt($start);
                });
            if ($clash) {
                throw ValidationException::withMessages(['vehicle_id' => 'This vehicle is already booked from '.$clash->pickup_at->format('d M H:i').' for '.$clash->guest_name.'.']);
            }
        }

        $data['amount'] = $data['amount'] ?? 0;

        return $data;
    }
}
