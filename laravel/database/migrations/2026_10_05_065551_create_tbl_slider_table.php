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
        Schema::create('tbl_slider', function (Blueprint $table) {
            $table->integer('slid', true);
            $table->integer('Sltypeid');
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image');
            $table->text('link1')->nullable();
            $table->text('link2')->nullable();
            $table->text('link3')->nullable();
            $table->text('slink')->nullable();
            $table->integer('status')->default(1);
            $table->integer('delation_status')->default(0);
            $table->integer('width')->default(0);
            $table->integer('height')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_slider');
    }
};
