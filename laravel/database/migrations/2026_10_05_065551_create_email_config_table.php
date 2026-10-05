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
        Schema::create('email_config', function (Blueprint $table) {
            $table->integer('email_config_id', true);
            $table->string('smtp_host', 200)->nullable();
            $table->string('secure_image', 50)->nullable();
            $table->string('smtp_port', 200)->nullable();
            $table->string('smtp_password', 200)->nullable();
            $table->text('protocol');
            $table->text('mailpath');
            $table->text('mailtype');
            $table->text('sender');
            $table->string('api_key', 250)->nullable();
            $table->integer('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_config');
    }
};
