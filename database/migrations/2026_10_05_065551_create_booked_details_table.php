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
        Schema::create('booked_details', function (Blueprint $table) {
            $table->integer('book_detailsid', true);
            $table->integer('bookedid')->index('bookedid');
            $table->string('booking_type', 100)->nullable();
            $table->string('booking_source', 100)->nullable();
            $table->string('booking_source_no', 100)->nullable();
            $table->text('extracheckin')->nullable();
            $table->text('extracheckout')->nullable();
            $table->string('arival_from', 100)->nullable();
            $table->string('purpose', 100)->nullable();
            $table->text('extra_facility_days')->nullable();
            $table->text('extrabed')->nullable();
            $table->text('extraperson')->nullable();
            $table->text('extrachild')->nullable();
            $table->text('complementary')->nullable();
            $table->text('complementaryprice')->nullable();
            $table->text('discountreason')->nullable();
            $table->decimal('discountamount', 10)->nullable();
            $table->integer('commissionpersent')->nullable();
            $table->decimal('commissionamount', 10)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->decimal('advance_amount', 10)->nullable();
            $table->string('advance_remarks', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->integer('booked_from')->nullable()->default(0)->comment('0=admin,1=user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booked_details');
    }
};
