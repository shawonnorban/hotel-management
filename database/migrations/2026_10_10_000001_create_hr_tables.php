<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** People management and payroll. The old employee/salary tables were not normalised enough to compute pay reliably. */
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('join_date');
            $table->date('leave_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->string('address', 255)->nullable();
            $table->string('national_id', 60)->nullable();
            $table->string('bank_account', 80)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recurring allowances and deductions on top of the basic salary.
        Schema::create('hr_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->enum('kind', ['allowance', 'deduction']);
            $table->string('name', 80);
            $table->decimal('amount', 12, 2);
            $table->boolean('is_percent')->default(false); // percent of basic salary
        });

        Schema::create('hr_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->enum('status', ['present', 'late', 'absent', 'leave', 'half_day']);
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('note')->nullable();
            $table->unique(['employee_id', 'work_date']);
            $table->index('work_date');
        });

        Schema::create('hr_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->date('holiday_date')->unique();
            $table->timestamps();
        });

        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->unsignedSmallInteger('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('hr_leave_types')->restrictOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('days', 5, 1);
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->unsignedInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('title', 150);
            $table->decimal('cash_amount', 12, 2)->default(0); // paid with that month's salary
            $table->date('awarded_on');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->unsignedSmallInteger('installments');
            $table->date('issued_on');
            $table->char('first_month', 7); // YYYY-MM of the first deduction
            $table->string('reason')->nullable();
            $table->enum('status', ['active', 'repaid'])->default('active');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('hr_loans')->cascadeOnDelete();
            $table->char('period', 7)->index(); // YYYY-MM
            $table->decimal('amount', 12, 2);
            $table->boolean('deducted')->default(false);
        });

        Schema::create('hr_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->char('period', 7)->unique(); // YYYY-MM
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->decimal('total_expense', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_loans', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('payment_entry_id')->nullable();
            $table->date('paid_on')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('hr_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->decimal('basic', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('net', 12, 2);
            $table->decimal('absent_days', 5, 1)->default(0);
            $table->unique(['run_id', 'employee_id']);
        });

        Schema::create('hr_candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->enum('stage', ['applied', 'shortlisted', 'interview', 'selected', 'rejected', 'hired'])->default('applied')->index();
            $table->date('interview_on')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hr_candidates', 'hr_payroll_items', 'hr_payroll_runs', 'hr_loan_installments', 'hr_loans', 'hr_awards', 'hr_leave_requests', 'hr_leave_types', 'hr_holidays', 'hr_attendance', 'hr_salary_components', 'hr_employees', 'hr_positions', 'hr_departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
