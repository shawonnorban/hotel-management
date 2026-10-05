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
        Schema::create('leave_apply', function (Blueprint $table) {
            $table->integer('leave_appl_id', true);
            $table->string('employee_id', 20);
            $table->integer('leave_type_id');
            $table->string('apply_strt_date', 20);
            $table->string('apply_end_date', 20);
            $table->integer('apply_day');
            $table->string('leave_aprv_strt_date', 20);
            $table->string('leave_aprv_end_date', 20);
            $table->string('num_aprv_day', 15);
            $table->string('reason', 100);
            $table->text('apply_hard_copy')->nullable();
            $table->string('apply_date', 20);
            $table->string('approve_date', 20);
            $table->string('approved_by', 30);
            $table->string('leave_type', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_apply');
    }
};
