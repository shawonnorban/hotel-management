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
        Schema::create('salary_setup_header', function (Blueprint $table) {
            $table->increments('s_s_h_id');
            $table->string('employee_id', 30);
            $table->string('salary_payable', 30)->nullable();
            $table->string('absent_deduct', 30);
            $table->string('tax_manager', 30);
            $table->string('status', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_setup_header');
    }
};
