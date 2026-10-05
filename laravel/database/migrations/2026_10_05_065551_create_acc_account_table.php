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
        Schema::create('acc_account', function (Blueprint $table) {
            $table->integer('account_id', true);
            $table->string('sector_name');
            $table->string('sector_type', 120);
            $table->boolean('status');
            $table->date('date')->nullable()->default('1970-01-02');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_account');
    }
};
