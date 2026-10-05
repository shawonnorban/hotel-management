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
        Schema::create('candidate_workexperience', function (Blueprint $table) {
            $table->integer('can_workexp_id', true);
            $table->string('can_id', 30);
            $table->string('company_name', 50);
            $table->string('working_period', 50);
            $table->string('duties', 30);
            $table->string('supervisor', 50);
            $table->string('sequencee', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_workexperience');
    }
};
