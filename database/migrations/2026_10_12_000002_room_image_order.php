<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** 0 = cover photo (shown on room cards); the rest follow in upload order. */
    public function up(): void
    {
        Schema::table('room_image', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('room_image', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
