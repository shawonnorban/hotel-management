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
        Schema::create('tbl_taxmgt', function (Blueprint $table) {
            $table->integer('tax_id', true);
            $table->text('taxname')->nullable();
            $table->decimal('rate')->nullable()->default(2);
            $table->text('reg_no')->nullable();
            $table->integer('isactive')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_taxmgt');
    }
};
