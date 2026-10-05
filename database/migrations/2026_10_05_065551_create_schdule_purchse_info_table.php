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
        Schema::create('schdule_purchse_info', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('purchase_key', 100)->nullable();
            $table->string('domain', 200)->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->string('port', 11)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schdule_purchse_info');
    }
};
