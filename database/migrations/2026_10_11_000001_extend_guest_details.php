<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customerinfo', function (Blueprint $table) {
            $table->string('title', 20)->nullable()->after('customernumber');
            $table->string('country_code', 10)->nullable();
            $table->string('state', 100)->nullable();
            $table->boolean('is_vip')->default(false);
            $table->text('comments')->nullable();
        });

        Schema::table('tbl_otherguest', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_otherguest', fn (Blueprint $t) => $t->dropColumn('booking_id'));
        Schema::table('customerinfo', fn (Blueprint $t) => $t->dropColumn(['title', 'country_code', 'state', 'is_vip', 'comments']));
    }
};
