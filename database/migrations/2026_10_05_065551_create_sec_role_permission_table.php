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
        Schema::create('sec_role_permission', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->integer('role_id');
            $table->integer('menu_id');
            $table->boolean('can_access');
            $table->boolean('can_create');
            $table->boolean('can_edit');
            $table->boolean('can_delete');
            $table->integer('createby');
            $table->dateTime('createdate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_role_permission');
    }
};
