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
        Schema::create('page_title', function (Blueprint $table) {
            $table->integer('pageid', true);
            $table->text('home');
            $table->text('aboutus');
            $table->text('contactus');
            $table->text('gallery');
            $table->text('roomlist');
            $table->text('roomdetails');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_title');
    }
};
