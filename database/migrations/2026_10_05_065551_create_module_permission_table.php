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
        Schema::create('module_permission', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('fk_module_id')->index('fk_module_id');
            $table->integer('fk_user_id')->index('fk_user_id');
            $table->boolean('create')->nullable();
            $table->boolean('read')->nullable();
            $table->boolean('update')->nullable();
            $table->boolean('delete')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_permission');
    }
};
