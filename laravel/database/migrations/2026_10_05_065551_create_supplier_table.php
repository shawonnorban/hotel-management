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
        Schema::create('supplier', function (Blueprint $table) {
            $table->integer('supid', true);
            $table->string('suplier_code')->index('suplier_code');
            $table->string('supName', 100);
            $table->string('supEmail', 100);
            $table->string('supMobile', 50);
            $table->text('supAddress');
            $table->decimal('total_amount', 15)->nullable()->default(0);
            $table->decimal('paid_amount', 15)->nullable()->default(0);
            $table->decimal('due_amount', 15)->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier');
    }
};
