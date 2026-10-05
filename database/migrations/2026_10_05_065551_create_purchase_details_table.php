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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->integer('detailsid', true);
            $table->integer('purchaseid');
            $table->integer('proid');
            $table->decimal('quantity', 10)->default(0);
            $table->string('unitname', 80);
            $table->decimal('price', 10)->default(0);
            $table->decimal('totalprice', 10)->default(0);
            $table->integer('purchaseby');
            $table->date('purchasedate');
            $table->date('purchaseexpiredate')->nullable();

            $table->index(['purchaseid', 'proid'], 'purchaseid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_details');
    }
};
