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
        Schema::create('sec_menu_item', function (Blueprint $table) {
            $table->integer('menu_id', true);
            $table->string('menu_title', 200)->nullable();
            $table->string('page_url', 250)->nullable();
            $table->string('module', 200)->nullable();
            $table->integer('parent_menu')->nullable();
            $table->boolean('is_report')->nullable();
            $table->integer('createby');
            $table->dateTime('createdate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_menu_item');
    }
};
