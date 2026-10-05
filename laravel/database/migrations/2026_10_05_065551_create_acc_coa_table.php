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
        Schema::create('acc_coa', function (Blueprint $table) {
            $table->string('HeadCode', 50)->index('headcode');
            $table->string('HeadName', 100)->primary();
            $table->string('PHeadName', 50);
            $table->integer('HeadLevel');
            $table->boolean('IsActive');
            $table->boolean('IsTransaction');
            $table->boolean('IsGL');
            $table->char('HeadType', 1);
            $table->boolean('IsBudget');
            $table->boolean('IsDepreciation');
            $table->decimal('DepreciationRate', 18);
            $table->string('CreateBy', 50);
            $table->dateTime('CreateDate');
            $table->string('UpdateBy', 50);
            $table->dateTime('UpdateDate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_coa');
    }
};
