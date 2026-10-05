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
        Schema::create('tbl_roomnofloorassign', function (Blueprint $table) {
            $table->integer('roomassignid', true);
            $table->integer('roomid');
            $table->integer('floorid');
            $table->integer('roomno');
            $table->integer('status')->nullable()->default(1)->comment('1=ready,2=booked,3=assigned to clean,4=booked and assigned to clean, 5=under maintenance,6=dirty,7=blocked,8=do not reserve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_roomnofloorassign');
    }
};
