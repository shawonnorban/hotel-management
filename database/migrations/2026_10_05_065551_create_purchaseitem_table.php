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
        Schema::create('purchaseitem', function (Blueprint $table) {
            $table->integer('purID', true);
            $table->string('invoiceid', 50)->nullable();
            $table->integer('suplierID');
            $table->decimal('total_price', 10)->default(0);
            $table->text('details')->nullable();
            $table->date('purchasedate');
            $table->date('purchaseexpiredate');
            $table->integer('savedby');
            $table->string('status', 3)->nullable()->default('0')->comment('0=unpaid,1=paid');

            $table->index(['invoiceid', 'suplierID', 'status'], 'invoiceid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchaseitem');
    }
};
