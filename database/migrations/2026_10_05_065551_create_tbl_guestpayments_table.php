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
        Schema::create('tbl_guestpayments', function (Blueprint $table) {
            $table->integer('payid', true);
            $table->string('bookedid');
            $table->string('invoice');
            $table->dateTime('paydate');
            $table->string('paymenttype', 100);
            $table->decimal('paymentamount', 10)->default(0);
            $table->string('details', 100)->nullable();
            $table->integer('book_type')->nullable()->default(0)->comment('0=room, 1=hall room');

            $table->index(['bookedid', 'invoice'], 'bookedid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_guestpayments');
    }
};
