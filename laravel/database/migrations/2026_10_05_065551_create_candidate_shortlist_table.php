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
        Schema::create('candidate_shortlist', function (Blueprint $table) {
            $table->integer('can_short_id', true);
            $table->string('can_id', 30);
            $table->integer('job_adv_id');
            $table->string('date_of_shortlist', 50);
            $table->string('interview_date', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_shortlist');
    }
};
