<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TblFloor;
use Illuminate\Database\Eloquent\Model;

class FloorResource extends Resource
{
    public static string $model = TblFloor::class;

    public static string $slug = 'floors';

    public static string $label = 'Floors';

    public static string $singular = 'Floor';

    public static string $icon = 'bi-layers';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('floorname', 'Floor name')->required()->rules('max:100')->unique()->listed(),
            Field::toggle('status', 'Active')->listed(),
        ];
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return \App\Models\TblRoomnofloorassign::where('floorid', $model->floorid)->exists() ? 'Rooms are assigned to this floor.' : null;
    }
}
