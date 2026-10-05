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
        Schema::create('booked_info', function (Blueprint $table) {
            $table->integer('bookedid', true);
            $table->string('booking_number', 100);
            $table->dateTime('date_time');
            $table->text('roomid');
            $table->string('nuofpeople', 100)->nullable()->default('0');
            $table->text('children')->nullable();
            $table->integer('total_room')->default(0);
            $table->string('room_no', 45);
            $table->text('roomrate')->nullable();
            $table->text('promocode')->nullable();
            $table->decimal('total_price', 10)->default(0);
            $table->decimal('paid_amount', 10)->default(0);
            $table->string('offer_discount', 100)->default('0.00');
            $table->text('full_guest_name')->nullable();
            $table->text('special_request')->nullable();
            $table->text('coments')->nullable();
            $table->dateTime('checkindate');
            $table->dateTime('checkoutdate');
            $table->integer('cutomerid');
            $table->string('bookingstatus')->comment('0=pending,1=cancel,2=success,3=finish,4=checkin,5=checkout');
            $table->integer('isSeen')->nullable()->default(0);

            $table->index(['cutomerid', 'bookingstatus'], 'cutomerid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booked_info');
    }
};
