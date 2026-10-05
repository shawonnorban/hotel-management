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
        Schema::create('payroll_holiday', function (Blueprint $table) {
            $table->increments('payrl_holi_id');
            $table->string('holiday_name', 30);
            $table->string('start_date', 30);
            $table->string('end_date', 30);
            $table->string('no_of_days', 30);
            $table->string('created_by', 30);
            $table->string('updated_by', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_holiday');
    }
};
