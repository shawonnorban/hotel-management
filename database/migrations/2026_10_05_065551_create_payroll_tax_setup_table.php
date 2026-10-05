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
        Schema::create('payroll_tax_setup', function (Blueprint $table) {
            $table->increments('tax_setup_id');
            $table->string('start_amount', 30);
            $table->string('end_amount', 30);
            $table->string('rate', 30);
            $table->string('status', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_setup');
    }
};
