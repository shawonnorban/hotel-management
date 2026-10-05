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
        Schema::create('tbl_module_purchasekey', function (Blueprint $table) {
            $table->integer('mpid', true);
            $table->string('module', 25)->nullable();
            $table->string('purchasekey', 55)->nullable();
            $table->dateTime('downloaddate')->default('1970-01-01 01:01:01');
            $table->dateTime('updatedate')->default('1970-01-01 01:01:01');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_module_purchasekey');
    }
};
