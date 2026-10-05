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
        Schema::create('promocode', function (Blueprint $table) {
            $table->integer('promocodeid', true);
            $table->integer('roomid');
            $table->date('startdate');
            $table->date('enddate');
            $table->text('promocode');
            $table->integer('discount');
            $table->integer('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocode');
    }
};
