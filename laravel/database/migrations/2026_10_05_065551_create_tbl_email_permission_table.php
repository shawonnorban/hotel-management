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
        Schema::create('tbl_email_permission', function (Blueprint $table) {
            $table->integer('permission_id', true);
            $table->text('permission')->nullable();
            $table->integer('status')->nullable()->default(0)->comment('0=no,1=yes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_email_permission');
    }
};
