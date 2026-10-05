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
        Schema::create('employee_salary_setup', function (Blueprint $table) {
            $table->increments('e_s_s_id');
            $table->string('employee_id', 30);
            $table->string('sal_type', 30);
            $table->string('salary_type_id', 30);
            $table->string('amount', 30);
            $table->date('create_date')->nullable();
            $table->dateTime('update_date', 6)->nullable();
            $table->string('update_id', 30);
            $table->float('gross_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary_setup');
    }
};
