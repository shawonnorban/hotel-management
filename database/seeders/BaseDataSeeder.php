<?php

namespace Database\Seeders;

use App\Models\Bedstype;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Roomsizemesurement;
use App\Models\Setting;
use App\Models\Starclass;
use App\Models\TblFloor;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/** Reference data a new hotel needs before the first booking. Never overwrites existing rows. */
class BaseDataSeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::firstOrCreate(['currencyname' => 'USD'], ['curr_icon' => '$', 'position' => 1, 'curr_rate' => 1]);

        if (! Setting::find(Settings::ROW_ID)) {
            Setting::create([
                'id' => Settings::ROW_ID,
                'title' => config('app.name'),
                'storename' => config('app.name'),
                'servicecharge' => 0,
                'vat' => 0,
                'currency' => $usd->currencyid,
                'splash_logo' => '',
                'timezone' => config('app.timezone'),
                'checkintime' => '14:00',
                'checkouttime' => '12:00',
                'dateformat' => 'd M Y',
            ]);
        }

        foreach ([1 => 'Card Payment', 3 => 'Paypal', 4 => 'Cash Payment', 5 => 'SSLCommerz', 6 => 'Bank Payment', 7 => 'Stripe'] as $id => $name) {
            PaymentMethod::firstOrCreate(['payment_method_id' => $id], ['payment_method' => $name, 'is_active' => in_array($id, [4, 6]) ? 1 : 0]);
        }

        foreach (['Single', 'Double', 'Queen', 'King', 'Twin'] as $bed) {
            Bedstype::firstOrCreate(['bedstypetitle' => $bed]);
        }
        foreach (['sqft', 'm²'] as $unit) {
            Roomsizemesurement::firstOrCreate(['roommesurementitle' => $unit]);
        }
        foreach (['1 Star', '2 Star', '3 Star', '4 Star', '5 Star'] as $star) {
            Starclass::firstOrCreate(['starclassname' => $star]);
        }
        TblFloor::firstOrCreate(['floorname' => 'Ground floor'], ['status' => 1]);
    }
}
