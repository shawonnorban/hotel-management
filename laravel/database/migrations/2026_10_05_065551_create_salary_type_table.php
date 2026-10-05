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
        Schema::create('salary_type', function (Blueprint $table) {
            $table->increments('salary_type_id');
            $table->string('sal_name', 50);
            $table->string('emp_sal_type', 50);
            $table->string('default_amount', 30);
            $table->string('status', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_type');
    }
};
