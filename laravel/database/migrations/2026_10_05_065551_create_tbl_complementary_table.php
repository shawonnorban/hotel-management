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
        Schema::create('tbl_complementary', function (Blueprint $table) {
            $table->integer('complementary_id', true);
            $table->text('roomtype')->nullable();
            $table->text('complementaryname')->nullable();
            $table->decimal('rate', 10)->nullable();
            $table->integer('status')->comment('0=inactive,1=active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_complementary');
    }
};
