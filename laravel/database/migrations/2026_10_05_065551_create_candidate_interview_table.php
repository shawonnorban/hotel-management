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
        Schema::create('candidate_interview', function (Blueprint $table) {
            $table->integer('can_int_id', true);
            $table->string('can_id', 30);
            $table->string('job_adv_id', 50);
            $table->string('interview_date', 30);
            $table->string('interviewer_id', 50);
            $table->string('interview_marks', 50);
            $table->string('written_total_marks', 50);
            $table->string('mcq_total_marks', 50);
            $table->string('total_marks', 30);
            $table->string('recommandation', 50);
            $table->string('selection', 50);
            $table->string('details', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_interview');
    }
};
