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
        Schema::create('tbl_note', function (Blueprint $table) {
            $table->integer('note_id', true);
            $table->text('note')->nullable();
            $table->text('roomno')->nullable();
            $table->text('bookedid')->nullable();
            $table->integer('status')->nullable()->default(0)->comment('0=pending,1=solved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_note');
    }
};
