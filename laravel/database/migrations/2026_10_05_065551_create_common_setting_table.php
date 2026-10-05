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
        Schema::create('common_setting', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('address')->nullable();
            $table->string('email', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('logo', 50)->nullable();
            $table->string('login_logo', 50)->nullable();
            $table->string('footer_logo', 50)->nullable();
            $table->string('invoice_logo', 50)->nullable();
            $table->text('powerbytxt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('common_setting');
    }
};
