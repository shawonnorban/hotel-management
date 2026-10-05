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
        Schema::create('roomfacilitydetails', function (Blueprint $table) {
            $table->integer('facilityid', true);
            $table->integer('facilitytypeid')->index('facilitytypeid');
            $table->string('facilitytitle');
            $table->string('image', 100)->nullable();

            $table->index(['facilitytypeid'], 'facilitytypeid_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roomfacilitydetails');
    }
};
