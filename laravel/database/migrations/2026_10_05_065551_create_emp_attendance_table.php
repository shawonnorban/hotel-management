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
        Schema::create('emp_attendance', function (Blueprint $table) {
            $table->increments('att_id');
            $table->string('employee_id', 50);
            $table->string('date', 30);
            $table->string('sign_in', 30)->nullable();
            $table->string('sign_out', 30)->nullable();
            $table->time('staytime')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_attendance');
    }
};
