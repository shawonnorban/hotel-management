<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->string('id_front', 191)->nullable();
            $table->string('id_back', 191)->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('mother_name', 150)->nullable();
            $table->string('spouse_name', 150)->nullable();
            $table->string('marital_status', 20)->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('alt_phone', 40)->nullable();
            $table->string('present_address', 255)->nullable();
            $table->string('emergency_name', 120)->nullable();
            $table->string('emergency_relation', 60)->nullable();
            $table->string('emergency_phone', 40)->nullable();
            $table->string('passport_no', 60)->nullable();
            $table->string('tin_no', 60)->nullable();
            $table->string('employment_type', 30)->nullable();
            $table->date('probation_end')->nullable();
            $table->string('work_location', 120)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_branch', 120)->nullable();
            $table->text('notes')->nullable();
        });

        Schema::create('hr_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('type', 50)->nullable();
            $table->string('file', 191);
            $table->date('expires_on')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_employee_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('degree', 150);
            $table->string('institute', 191)->nullable();
            $table->string('field', 150)->nullable();
            $table->string('result', 60)->nullable();
            $table->unsignedSmallInteger('passing_year')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_employee_experience', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('company', 191);
            $table->string('title', 150)->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->text('responsibilities')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_experience');
        Schema::dropIfExists('hr_employee_education');
        Schema::dropIfExists('hr_employee_documents');
    }
};
