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
        Schema::create('sms_configuration', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('link');
            $table->string('gateway', 200);
            $table->string('user_name', 200);
            $table->string('password');
            $table->string('sms_from', 200);
            $table->string('userid', 100);
            $table->integer('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_configuration');
    }
};
