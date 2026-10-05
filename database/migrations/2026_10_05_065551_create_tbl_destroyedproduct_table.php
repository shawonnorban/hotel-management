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
        Schema::create('tbl_destroyedproduct', function (Blueprint $table) {
            $table->integer('destroy_id', true);
            $table->integer('product_id');
            $table->integer('quantity')->nullable()->default(0);
            $table->dateTime('rec_date')->nullable();
            $table->text('comment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_destroyedproduct');
    }
};
