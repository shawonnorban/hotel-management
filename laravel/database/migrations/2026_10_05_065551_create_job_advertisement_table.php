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
        Schema::create('job_advertisement', function (Blueprint $table) {
            $table->unsignedInteger('job_adv_id');
            $table->string('pos_id', 30);
            $table->string('adv_circular_date', 30);
            $table->string('circular_dadeline', 30);
            $table->tinyText('adv_file');
            $table->string('adv_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_advertisement');
    }
};
