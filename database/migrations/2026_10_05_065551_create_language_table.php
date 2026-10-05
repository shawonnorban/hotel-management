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
        Schema::create('language', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('phrase', 100);
            $table->string('english');
            $table->text('malay')->nullable();
            $table->text('french')->nullable();
            $table->text('german')->nullable();
            $table->text('spanish')->nullable();
            $table->text('turkish')->nullable();
            $table->text('hindi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language');
    }
};
