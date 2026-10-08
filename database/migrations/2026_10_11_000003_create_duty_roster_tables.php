<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('color', 9)->default('#0f766e');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // One row per employee per day; shift_id null means a rostered day off.
        Schema::create('hr_rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->foreignId('shift_id')->nullable()->constrained('hr_shifts')->nullOnDelete();
            $table->string('note', 150)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_rosters');
        Schema::dropIfExists('hr_shifts');
    }
};
