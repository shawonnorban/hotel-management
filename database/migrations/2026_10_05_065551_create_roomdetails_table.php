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
        Schema::create('roomdetails', function (Blueprint $table) {
            $table->integer('roomid', true);
            $table->string('roomtype')->index('roomtype');
            $table->integer('roomsize');
            $table->string('roomsizemesurement');
            $table->integer('roomactive');
            $table->integer('bedsno');
            $table->integer('bedstype');
            $table->integer('number_of_star')->nullable()->default(4);
            $table->string('roomdescription');
            $table->text('reservecondition')->nullable();
            $table->integer('roomstatus')->default(0);
            $table->integer('capacity');
            $table->integer('exbedcapability')->default(1);
            $table->integer('child_limit')->nullable()->default(0);
            $table->decimal('rate', 10)->default(0);
            $table->decimal('bedcharge', 10, 0);
            $table->decimal('personcharge', 10, 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roomdetails');
    }
};
