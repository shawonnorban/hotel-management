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
        Schema::create('employee_performance', function (Blueprint $table) {
            $table->increments('emp_per_id');
            $table->string('employee_id', 50);
            $table->string('note', 50);
            $table->string('date', 50);
            $table->string('note_by', 50);
            $table->string('number_of_star', 50);
            $table->string('status', 50);
            $table->string('updated_by', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_performance');
    }
};
