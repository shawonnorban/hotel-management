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
        Schema::create('top_menu', function (Blueprint $table) {
            $table->integer('menuid', true);
            $table->text('menu_name');
            $table->string('menu_slug', 70);
            $table->integer('parentid');
            $table->date('entrydate');
            $table->integer('isactive')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('top_menu');
    }
};
