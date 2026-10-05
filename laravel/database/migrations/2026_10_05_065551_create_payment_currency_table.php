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
        Schema::create('payment_currency', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('currency_name', 100);
            $table->string('currency_details', 100)->nullable();
            $table->boolean('is_active')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_currency');
    }
};
