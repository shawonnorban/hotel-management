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
        Schema::create('synchronizer_setting', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('hostname', 100);
            $table->string('username', 100);
            $table->string('password', 100);
            $table->string('port', 10);
            $table->string('debug', 10);
            $table->string('project_root', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synchronizer_setting');
    }
};
