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
        Schema::create('custom_table', function (Blueprint $table) {
            $table->integer('custom_id', true);
            $table->string('custom_field', 100);
            $table->integer('custom_data_type');
            $table->text('custom_data');
            $table->string('employee_id', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_table');
    }
};
