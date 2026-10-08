<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Make Bangladeshi Taka available and switch hotels still on the installer's USD default to it. */
    public function up(): void
    {
        $bdt = DB::table('currency')->where('currencyname', 'BDT')->first();
        $bdtId = $bdt?->currencyid ?? DB::table('currency')->insertGetId(['currencyname' => 'BDT', 'curr_icon' => '৳', 'position' => 1, 'curr_rate' => 1]);

        $usd = DB::table('currency')->where('currencyname', 'USD')->first();
        if ($usd) {
            DB::table('setting')->where('currency', $usd->currencyid)->update(['currency' => $bdtId]);
        }
    }

    public function down(): void {}
};
