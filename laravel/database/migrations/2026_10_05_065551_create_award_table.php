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
        Schema::create('award', function (Blueprint $table) {
            $table->integer('award_id', true);
            $table->string('award_name', 50);
            $table->string('aw_description', 200);
            $table->string('awr_gift_item', 50);
            $table->date('date');
            $table->string('employee_id', 30);
            $table->string('awarded_by', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('award');
    }
};
