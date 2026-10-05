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
        Schema::create('sms_template', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('template_name');
            $table->string('message');
            $table->string('type');
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('default_status');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_template');
    }
};
