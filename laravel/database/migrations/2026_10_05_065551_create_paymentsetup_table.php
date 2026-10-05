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
        Schema::create('paymentsetup', function (Blueprint $table) {
            $table->integer('setupid', true);
            $table->integer('paymentid');
            $table->string('marchantid')->nullable();
            $table->string('password', 120);
            $table->string('email', 100);
            $table->string('currency', 20);
            $table->integer('Islive');
            $table->integer('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentsetup');
    }
};
