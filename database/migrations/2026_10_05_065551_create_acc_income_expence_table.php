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
        Schema::create('acc_income_expence', function (Blueprint $table) {
            $table->integer('ID', true)->unique('id');
            $table->string('VNo', 50);
            $table->string('Vtype', 50)->nullable();
            $table->date('Date');
            $table->string('Paymode', 50);
            $table->string('Perpose', 50)->nullable();
            $table->text('Narration')->nullable();
            $table->integer('StoreID');
            $table->string('COAID', 50);
            $table->decimal('Amount', 10);
            $table->tinyInteger('IsApprove');
            $table->string('CreateBy', 50);
            $table->dateTime('CreateDate');

            $table->index(['VNo', 'IsApprove'], 'vno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_income_expence');
    }
};
