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
        Schema::create('tbl_reuseableproduct', function (Blueprint $table) {
            $table->integer('reuse_id', true);
            $table->integer('product_id')->nullable();
            $table->integer('in_use')->nullable()->default(0);
            $table->integer('in_laundry')->nullable()->default(0);
            $table->integer('ready')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_reuseableproduct');
    }
};
