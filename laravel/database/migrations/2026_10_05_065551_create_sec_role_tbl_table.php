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
        Schema::create('sec_role_tbl', function (Blueprint $table) {
            $table->integer('role_id', true);
            $table->text('role_name');
            $table->text('role_description');
            $table->integer('create_by')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->integer('role_status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_role_tbl');
    }
};
