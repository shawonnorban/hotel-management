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
        Schema::create('acc_customer_income', function (Blueprint $table) {
            $table->integer('ID', true)->unique('id');
            $table->string('Customer_Id', 50);
            $table->string('VNo', 50);
            $table->date('Date');
            $table->decimal('Amount', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_customer_income');
    }
};
