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
        Schema::create('candidate_education_info', function (Blueprint $table) {
            $table->integer('can_edu_id', true);
            $table->string('can_id', 30);
            $table->string('degree_name', 30);
            $table->string('university_name', 50);
            $table->string('cgp', 30);
            $table->string('comments', 50)->nullable();
            $table->string('sequencee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_education_info');
    }
};
