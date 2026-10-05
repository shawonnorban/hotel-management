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
        Schema::create('loan_installment', function (Blueprint $table) {
            $table->integer('loan_inst_id', true);
            $table->string('employee_id', 21);
            $table->string('loan_id', 21);
            $table->string('installment_amount', 20);
            $table->string('payment', 20);
            $table->string('date', 20);
            $table->string('received_by', 20);
            $table->string('installment_no', 20)->default('1');
            $table->string('notes', 80);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_installment');
    }
};
