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
        Schema::create('products', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('product_name', 250);
            $table->integer('category_id')->default(1);
            $table->integer('uom_id');
            $table->integer('stock')->nullable()->default(0);
            $table->integer('used')->nullable()->default(0);
            $table->integer('destroyed')->default(0);
            $table->integer('reuseable')->default(0)->comment('0=No,1=Yes');
            $table->tinyInteger('is_active');

            $table->index(['category_id', 'is_active'], 'category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
