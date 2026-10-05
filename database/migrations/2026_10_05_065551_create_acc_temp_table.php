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
        Schema::create('acc_temp', function (Blueprint $table) {
            $table->string('COAID', 50);
            $table->string('Name', 50);
            $table->decimal('Debit', 18);
            $table->decimal('Credit', 18);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_temp');
    }
};
