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
        Schema::create('tbl_openingbalance', function (Blueprint $table) {
            $table->integer('opbalance_id', true);
            $table->integer('fiyear_id')->index('fiyear_id');
            $table->text('headcode')->nullable();
            $table->decimal('opening_balance', 10)->nullable();
            $table->decimal('current_balance', 10)->nullable();
            $table->text('remark')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_openingbalance');
    }
};
