<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TrVehicle;
use Illuminate\Database\Eloquent\Model;

class TrVehicleResource extends Resource
{
    public static string $model = TrVehicle::class;

    public static string $slug = 'tr-vehicles';

    public static string $label = 'Vehicle details list';

    public static string $singular = 'Vehicle';

    public static string $icon = 'bi-truck-front';

    public static string $group = 'Transport';

    public function searchable(): array
    {
        return ['reg_no', 'type', 'make_model', 'driver_name'];
    }

    public function fields(): array
    {
        return [
            Field::text('reg_no', 'Registration no.')->required()->rules('max:40')->unique()->listed(),
            Field::select('type', 'Type', array_combine(['Car', 'Microbus', 'Van', 'Bus', 'SUV', 'Other'], ['Car', 'Microbus', 'Van', 'Bus', 'SUV', 'Other']))->required()->listed(),
            Field::text('make_model', 'Make & model')->rules('max:120')->listed(),
            Field::number('seats', 'Seats')->required()->default(4)->rules('integer|min:1|max:100')->listed(),
            Field::text('driver_name', 'Driver')->rules('max:120')->listed(),
            Field::text('driver_phone', 'Driver phone')->rules('max:40'),
            Field::money('base_fare', 'Base fare')->default(0)->rules('min:0'),
            Field::money('rate_per_km', 'Rate per km')->default(0)->rules('min:0'),
            Field::toggle('is_active', 'Available')->listed(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['base_fare'] = $data['base_fare'] ?? 0;
        $data['rate_per_km'] = $data['rate_per_km'] ?? 0;

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return \App\Models\TrVehicleBooking::where('vehicle_id', $model->id)->exists() ? 'This vehicle has bookings. Mark it unavailable instead.' : null;
    }
}
