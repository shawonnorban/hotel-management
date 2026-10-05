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
        Schema::create('employee_benifit', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('bnf_cl_code', 100);
            $table->string('bnf_cl_code_des', 250);
            $table->date('bnff_acural_date');
            $table->tinyInteger('bnf_status');
            $table->string('employee_id', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_benifit');
    }
};
