<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TrFlight;
use Illuminate\Database\Eloquent\Model;

class TrFlightResource extends Resource
{
    public static string $model = TrFlight::class;

    public static string $slug = 'tr-flights';

    public static string $label = 'Flight details list';

    public static string $singular = 'Flight';

    public static string $icon = 'bi-airplane';

    public static string $group = 'Transport';

    public static ?string $orderBy = 'flight_at';

    public function searchable(): array
    {
        return ['guest_name', 'flight_no', 'airline', 'booking_number'];
    }

    public function fields(): array
    {
        return [
            Field::text('guest_name', 'Guest')->required()->rules('max:150')->listed(),
            Field::text('booking_number', 'Booking no.')->rules('max:30')->listed(),
            Field::select('direction', 'Direction', ['arrival' => 'Arrival', 'departure' => 'Departure'])->required()->listed(),
            Field::text('flight_no', 'Flight no.')->required()->rules('max:20')->listed(),
            Field::text('airline', 'Airline')->rules('max:80'),
            Field::text('airport', 'Airport')->rules('max:120'),
            Field::datetime('flight_at', 'Flight time')->required()->listed(),
            Field::number('passengers', 'Passengers')->default(1)->rules('integer|min:1|max:99'),
            Field::textarea('notes', 'Notes')->col(12),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['passengers'] = $data['passengers'] ?? 1;

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return \App\Models\TrVehicleBooking::where('flight_id', $model->id)->exists() ? 'A vehicle booking is linked to this flight.' : null;
    }
}
