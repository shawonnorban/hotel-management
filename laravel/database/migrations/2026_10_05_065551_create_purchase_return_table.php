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
        Schema::create('purchase_return', function (Blueprint $table) {
            $table->integer('preturn_id', true);
            $table->integer('supplier_id');
            $table->string('po_no', 120);
            $table->date('return_date');
            $table->float('totalamount');
            $table->float('totaldiscount');
            $table->string('return_reason', 250);
            $table->integer('createby');
            $table->dateTime('createdate');
            $table->integer('updateby');
            $table->dateTime('updatedate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return');
    }
};
