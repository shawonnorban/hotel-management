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
        Schema::create('acc_bank', function (Blueprint $table) {
            $table->integer('bank_id', true);
            $table->string('bank_name', 200);
            $table->string('branch_name');
            $table->string('account_number', 50);
            $table->integer('opening_credit')->nullable();
            $table->boolean('status');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_bank');
    }
};
