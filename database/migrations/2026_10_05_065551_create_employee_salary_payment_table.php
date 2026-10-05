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
        Schema::create('employee_salary_payment', function (Blueprint $table) {
            $table->increments('emp_sal_pay_id');
            $table->string('employee_id', 50);
            $table->string('total_salary', 50);
            $table->string('total_working_minutes', 50);
            $table->string('working_period', 50);
            $table->string('payment_due', 50);
            $table->string('payment_date', 50);
            $table->string('paid_by', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary_payment');
    }
};
