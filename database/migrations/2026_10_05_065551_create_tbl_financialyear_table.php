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
        Schema::create('tbl_financialyear', function (Blueprint $table) {
            $table->integer('fiyear_id', true);
            $table->string('title', 50);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->string('is_active', 3)->nullable()->comment('1=ended,0=inactive,2=active');
            $table->string('create_by', 3)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_financialyear');
    }
};
