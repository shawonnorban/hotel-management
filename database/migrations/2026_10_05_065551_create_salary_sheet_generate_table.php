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
        Schema::create('salary_sheet_generate', function (Blueprint $table) {
            $table->increments('ssg_id');
            $table->string('employee_id', 20);
            $table->string('name', 30);
            $table->string('gdate', 20)->nullable();
            $table->string('start_date', 30);
            $table->string('end_date', 30);
            $table->string('generate_by', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_sheet_generate');
    }
};
