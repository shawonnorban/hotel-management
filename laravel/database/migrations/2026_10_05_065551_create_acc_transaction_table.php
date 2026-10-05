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
        Schema::create('acc_transaction', function (Blueprint $table) {
            $table->integer('ID', true)->unique('id');
            $table->string('VNo', 50)->nullable();
            $table->string('Vtype', 50)->nullable();
            $table->date('VDate')->nullable();
            $table->string('COAID', 50)->index('coaid');
            $table->text('Narration')->nullable();
            $table->decimal('Debit', 18)->nullable();
            $table->decimal('Credit', 18)->nullable();
            $table->integer('StoreID');
            $table->char('IsPosted', 10)->nullable();
            $table->string('CreateBy', 50)->nullable();
            $table->dateTime('CreateDate')->nullable();
            $table->string('UpdateBy', 50)->nullable();
            $table->dateTime('UpdateDate')->nullable();
            $table->char('IsAppove', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_transaction');
    }
};
