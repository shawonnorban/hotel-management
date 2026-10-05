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
        Schema::create('message', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('sender_id');
            $table->integer('receiver_id');
            $table->string('subject');
            $table->text('message');
            $table->dateTime('datetime');
            $table->boolean('sender_status')->default(false)->comment('0=unseen, 1=seen, 2=delete');
            $table->boolean('receiver_status')->default(false)->comment('0=unseen, 1=seen, 2=delete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message');
    }
};
