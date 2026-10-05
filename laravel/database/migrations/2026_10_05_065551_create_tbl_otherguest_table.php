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
        Schema::create('tbl_otherguest', function (Blueprint $table) {
            $table->integer('otherguest_id', true);
            $table->text('bookedid')->nullable();
            $table->integer('customerid')->nullable()->index('customerid');
            $table->text('guestname')->nullable();
            $table->text('gender')->nullable();
            $table->text('mobile')->nullable();
            $table->text('email')->nullable();
            $table->text('photo_id_type')->nullable();
            $table->text('photo_id')->nullable();
            $table->string('front_image', 100)->nullable();
            $table->string('back_image', 100)->nullable();
            $table->string('occupant_image', 100)->nullable();
            $table->integer('type')->nullable()->default(0)->comment('0=room, 1=hall room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_otherguest');
    }
};
