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
        Schema::create('user', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('firstname', 50)->nullable();
            $table->string('lastname', 50)->nullable();
            $table->text('about')->nullable();
            $table->string('email', 100);
            $table->text('device_token')->nullable();
            $table->string('password', 32);
            $table->string('password_reset_token', 20)->nullable();
            $table->string('image', 100)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->dateTime('last_logout')->nullable();
            $table->string('ip_address', 14)->nullable();
            $table->boolean('status')->default(true);
            $table->integer('usertype')->default(1)->comment('1=user,2=employee');
            $table->tinyInteger('is_admin')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
