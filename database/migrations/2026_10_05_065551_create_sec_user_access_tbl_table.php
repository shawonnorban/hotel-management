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
        Schema::create('sec_user_access_tbl', function (Blueprint $table) {
            $table->integer('role_acc_id', true);
            $table->integer('fk_role_id');
            $table->integer('fk_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_user_access_tbl');
    }
};
