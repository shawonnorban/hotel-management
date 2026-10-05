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
        Schema::create('candidate_basic_info', function (Blueprint $table) {
            $table->string('can_id', 20)->primary();
            $table->string('first_name', 11);
            $table->string('last_name', 30);
            $table->string('email', 30);
            $table->string('phone', 20);
            $table->string('alter_phone', 20);
            $table->string('present_address', 100);
            $table->string('parmanent_address', 100);
            $table->text('picture')->nullable();
            $table->string('ssn', 50);
            $table->string('state', 30);
            $table->string('city', 30);
            $table->integer('zip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_basic_info');
    }
};
