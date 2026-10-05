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
        Schema::create('tbl_wakeup_call', function (Blueprint $table) {
            $table->integer('wapupid', true);
            $table->integer('custid');
            $table->string('wakeupcall_time', 100);
            $table->timestamp('insert_time')->useCurrent();
            $table->text('remarks')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_wakeup_call');
    }
};
