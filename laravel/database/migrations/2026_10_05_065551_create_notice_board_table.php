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
        Schema::create('notice_board', function (Blueprint $table) {
            $table->integer('notice_id', true);
            $table->text('notice_descriptiion');
            $table->date('notice_date');
            $table->string('notice_type', 50);
            $table->string('notice_by', 50);
            $table->text('notice_attachment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_board');
    }
};
