<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\Bedstype;
use App\Models\Roomdetails;
use App\Models\Roomsizemesurement;
use App\Models\Starclass;
use App\Models\RoomImage;
use App\Models\Roomfacilitydetails;
use App\Models\RoomfailityRefAccomodation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RoomTypeResource extends Resource
{
    public static string $model = Roomdetails::class;

    public static string $slug = 'room-types';

    public static string $label = 'Room types';

    public static string $singular = 'Room type';

    public static string $icon = 'bi-door-open';

    public static string $group = 'Hotel setup';

    public function fields(): array
    {
        return [
            Field::text('roomtype', 'Room type')->required()->rules('max:255')->listed(),
            Field::select('bedstype', 'Bed type', fn () => Bedstype::orderBy('bedstypetitle')->pluck('bedstypetitle', 'Bedstypeid')->all())->required()->listed(),
            Field::number('bedsno', 'Number of beds')->required()->rules('min:1|max:20')->default(1),
            Field::number('capacity', 'Guest capacity')->required()->rules('min:1|max:50')->default(2)->listed(),
            Field::number('child_limit', 'Child limit')->rules('min:0|max:50')->default(0),
            Field::number('exbedcapability', 'Extra beds allowed')->rules('min:0|max:10')->default(0),
            Field::number('roomsize', 'Room size')->required()->rules('min:1'),
            Field::select('roomsizemesurement', 'Size unit', fn () => Roomsizemesurement::orderBy('roommesurementitle')->pluck('roommesurementitle', 'mesurementid')->all())->required(),
            Field::select('number_of_star', 'Star class', fn () => Starclass::orderBy('starclassname')->pluck('starclassname', 'starcalssid')->all()),
            Field::money('rate', 'Rate per night')->required()->rules('min:0')->listed(),
            Field::money('bedcharge', 'Extra bed charge')->required()->rules('min:0')->default(0),
            Field::money('personcharge', 'Extra person charge')->required()->rules('min:0')->default(0),
            Field::textarea('roomdescription', 'Description')->required()->rules('max:255'),
            Field::textarea('reservecondition', 'Booking conditions'),
            Field::toggle('roomactive', 'Bookable online')->listed(),
            Field::heading('Facilities'),
            Field::multiselect('facility_ids', 'Room facilities', fn () => Roomfacilitydetails::query()->join('roomfacilitytype', 'roomfacilitytype.facilitytypeid', '=', 'roomfacilitydetails.facilitytypeid')
                ->orderBy('roomfacilitytype.facilitytypetitle')->orderBy('roomfacilitydetails.facilitytitle')->get(['roomfacilitydetails.facilityid', 'roomfacilitydetails.facilitytitle', 'roomfacilitytype.facilitytypetitle'])
                ->mapWithKeys(fn ($f) => [$f->facilityid => $f->facilitytypetitle.' — '.$f->facilitytitle])->all())
                ->virtual(fn ($room) => RoomfailityRefAccomodation::where('room_id', $room->roomid)->pluck('facilityid')->all())->col(12)
                ->help('Pick as many as the room has. Add new ones under Hotel setup → Facilities.'),
            Field::heading('Photos'),
            Field::images('gallery', 'Room images')->virtual(fn ($room) => RoomImage::where('room_id', $room->roomid)->ordered()->get()->map(fn ($i) => ['id' => $i->room_img_id, 'path' => $i->room_imagename])->all()),
        ];
    }

    public function afterSave(Model $model, array $data, bool $created): void
    {
        $roomId = $model->roomid;

        // Facilities: keep exactly the ticked ones.
        $wanted = array_map('intval', $data['facility_ids'] ?? []);
        RoomfailityRefAccomodation::where('room_id', $roomId)->whereNotIn('facilityid', $wanted ?: [0])->delete();
        $have = RoomfailityRefAccomodation::where('room_id', $roomId)->pluck('facilityid')->map(fn ($v) => (int) $v)->all();
        foreach (array_diff($wanted, $have) as $facilityId) {
            $type = Roomfacilitydetails::where('facilityid', $facilityId)->value('facilitytypeid');
            RoomfailityRefAccomodation::create(['room_id' => $roomId, 'facilityid' => $facilityId, 'facilititypeid' => $type ?? 0]);
        }

        // Photos: remove the ticked ones, add new uploads, then apply the cover choice.
        $g = $data['gallery'] ?? ['new' => [], 'remove' => [], 'cover' => null];
        foreach (RoomImage::where('room_id', $roomId)->whereIn('room_img_id', $g['remove'])->get() as $img) {
            if (str_starts_with($img->room_imagename, 'storage/uploads/')) {
                Storage::disk('public')->delete(substr($img->room_imagename, strlen('storage/')));
            }
            $img->delete();
        }
        foreach ($g['new'] as $path) {
            RoomImage::create(['room_id' => $roomId, 'room_imagename' => $path, 'sort_order' => 1]);
        }
        $images = RoomImage::where('room_id', $roomId)->ordered()->get();
        $cover = $images->firstWhere('room_img_id', $g['cover']) ?? $images->first();
        foreach ($images as $img) {
            $order = $cover && $img->room_img_id === $cover->room_img_id ? 0 : 1;
            if ((int) $img->sort_order !== $order) {
                $img->update(['sort_order' => $order]);
            }
        }
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['roomstatus'] = $model?->roomstatus ?? 0;
        $data['exbedcapability'] = $data['exbedcapability'] ?? 0;
        $data['child_limit'] = $data['child_limit'] ?? 0;
        $data['number_of_star'] = $data['number_of_star'] ?? 4;
        $data['roomactive'] = (int) $data['roomactive'];

        return $data;
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        return $model->roomNumbers()->exists() ? 'Remove this room type\'s room numbers first.' : null;
    }
}
