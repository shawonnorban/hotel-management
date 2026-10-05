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
        Schema::create('acc_glsummarybalance', function (Blueprint $table) {
            $table->integer('ID', true)->unique('id');
            $table->string('COAID', 50)->nullable();
            $table->decimal('Debit', 18)->nullable();
            $table->decimal('Credit', 18)->nullable();
            $table->integer('FYear')->nullable();
            $table->string('CreateBy', 50)->nullable();
            $table->dateTime('CreateDate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_glsummarybalance');
    }
};
