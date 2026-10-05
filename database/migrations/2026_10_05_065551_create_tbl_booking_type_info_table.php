<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_booking_type_info', function (Blueprint $table) {
            $table->integer('btypeinfoid', true);
            $table->string('booking_type', 200);
            $table->string('booking_sourse', 200);
            $table->decimal('commissionrate', 10, 0)->default(2);
            $table->float('balance')->default(0);
            $table->decimal('paid_amount', 10, 0)->default(0);
            $table->decimal('due_amount', 10, 0)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_booking_type_info');
    }
};
