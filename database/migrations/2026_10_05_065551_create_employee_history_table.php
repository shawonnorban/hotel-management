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
        Schema::create('employee_history', function (Blueprint $table) {
            $table->integer('emp_his_id', true);
            $table->string('employee_id', 30)->index('employee_id');
            $table->string('pos_id', 30);
            $table->string('first_name', 30);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 30);
            $table->string('email', 32);
            $table->string('phone', 30);
            $table->string('alter_phone', 30);
            $table->string('present_address', 100)->nullable();
            $table->string('parmanent_address', 100)->nullable();
            $table->text('picture')->nullable();
            $table->string('degree_name', 30)->nullable();
            $table->string('university_name', 50)->nullable();
            $table->string('cgp', 30)->nullable();
            $table->string('passing_year', 30)->nullable();
            $table->string('company_name', 30)->nullable();
            $table->string('working_period', 30)->nullable();
            $table->string('duties', 30)->nullable();
            $table->string('supervisor', 30)->nullable();
            $table->text('signature')->nullable();
            $table->integer('is_admin')->default(0);
            $table->integer('dept_id')->nullable();
            $table->integer('division_id');
            $table->string('maiden_name', 50);
            $table->string('state', 30);
            $table->string('city', 30);
            $table->integer('zip');
            $table->integer('citizenship');
            $table->integer('duty_type');
            $table->date('hire_date');
            $table->date('original_hire_date');
            $table->date('termination_date');
            $table->text('termination_reason');
            $table->integer('voluntary_termination');
            $table->date('rehire_date');
            $table->integer('rate_type');
            $table->float('rate');
            $table->integer('pay_frequency');
            $table->string('pay_frequency_txt', 50);
            $table->float('hourly_rate2');
            $table->float('hourly_rate3');
            $table->string('home_department', 100);
            $table->string('department_text', 100);
            $table->string('class_code', 50);
            $table->string('class_code_desc', 100);
            $table->date('class_acc_date');
            $table->tinyInteger('class_status');
            $table->integer('is_super_visor')->nullable();
            $table->string('super_visor_id', 30);
            $table->text('supervisor_report');
            $table->date('dob');
            $table->integer('gender');
            $table->string('country', 120)->nullable();
            $table->integer('marital_status');
            $table->string('ethnic_group', 100);
            $table->string('eeo_class_gp', 100);
            $table->string('ssn', 50);
            $table->integer('work_in_state');
            $table->integer('live_in_state');
            $table->string('home_email', 50);
            $table->string('business_email', 50);
            $table->string('home_phone', 30);
            $table->string('business_phone', 30);
            $table->string('cell_phone', 30);
            $table->string('emerg_contct', 30);
            $table->string('emrg_h_phone', 30);
            $table->string('emrg_w_phone', 30);
            $table->string('emgr_contct_relation', 50);
            $table->string('alt_em_contct', 30);
            $table->string('alt_emg_h_phone', 30);
            $table->string('alt_emg_w_phone', 30);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_history');
    }
};
