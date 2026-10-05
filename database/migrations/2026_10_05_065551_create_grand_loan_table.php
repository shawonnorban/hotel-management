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
        Schema::create('grand_loan', function (Blueprint $table) {
            $table->integer('loan_id', true);
            $table->string('employee_id', 50);
            $table->string('permission_by', 30);
            $table->string('loan_details', 30);
            $table->string('amount', 30);
            $table->string('interest_rate', 30);
            $table->string('installment', 30);
            $table->string('installment_period', 30);
            $table->string('repayment_amount', 30);
            $table->string('date_of_approve', 30);
            $table->string('repayment_start_date', 30);
            $table->string('created_by', 30);
            $table->string('updated_by', 30);
            $table->string('loan_status', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grand_loan');
    }
};
