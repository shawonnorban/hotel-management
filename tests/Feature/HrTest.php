<?php

namespace Tests\Feature;

use App\Models\HrAttendance;
use App\Models\HrAward;
use App\Models\HrCandidate;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrHoliday;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Models\HrLoan;
use App\Models\HrPayrollRun;
use App\Models\HrPosition;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Hr\PayrollService;
use App\Services\LedgerService;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Hash;

class HrTest extends HotelTestCase
{
    private LedgerService $ledger;

    private HrEmployee $anna;

    private HrEmployee $ben;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
        AppSettings::set('hr.weekly_off', 'sat,sun');
        $dept = HrDepartment::create(['name' => 'Front office']);
        $pos = HrPosition::create(['title' => 'Receptionist', 'department_id' => $dept->id]);
        $this->anna = HrEmployee::create(['code' => 'E1', 'first_name' => 'Anna', 'last_name' => 'Lee', 'join_date' => '2025-01-01', 'basic_salary' => 3000, 'department_id' => $dept->id, 'position_id' => $pos->id]);
        $this->ben = HrEmployee::create(['code' => 'E2', 'first_name' => 'Ben', 'last_name' => 'Ng', 'join_date' => '2025-01-01', 'basic_salary' => 1500]);
        $this->actingAs($this->staff, 'admin');
    }

    private function payroll(string $period = '2026-03'): HrPayrollRun
    {
        return app(PayrollService::class)->generate($period, $this->staff->id);
    }

    private function item(HrPayrollRun $run, HrEmployee $e)
    {
        return $run->items()->get()->firstWhere('employee_id', $e->id);
    }

    public function test_basic_payroll_run(): void
    {
        $run = $this->payroll();

        $this->assertSame('draft', $run->status);
        $this->assertSame('4500.00', $run->total_net);
        $this->assertSame('3000.00', $this->item($run, $this->anna)->net);
    }

    public function test_allowances_deductions_and_percentages(): void
    {
        $this->anna->components()->createMany([
            ['kind' => 'allowance', 'name' => 'Transport', 'amount' => 200, 'is_percent' => false],
            ['kind' => 'allowance', 'name' => 'Housing', 'amount' => 10, 'is_percent' => true],   // 300
            ['kind' => 'deduction', 'name' => 'Tax', 'amount' => 5, 'is_percent' => true],       // 150
            ['kind' => 'deduction', 'name' => 'Insurance', 'amount' => 50, 'is_percent' => false],
        ]);

        $item = $this->item($this->payroll(), $this->anna);

        $this->assertSame('500.00', $item->allowances);
        $this->assertSame('200.00', $item->deductions);
        $this->assertSame('3300.00', $item->net);
    }

    public function test_absence_unpaid_leave_and_half_days_reduce_pay(): void
    {
        // March 2026 has 31 days: 3000 / 31 per day.
        foreach (['2026-03-02', '2026-03-03'] as $day) {
            HrAttendance::create(['employee_id' => $this->anna->id, 'work_date' => $day, 'status' => 'absent']);
        }
        HrAttendance::create(['employee_id' => $this->anna->id, 'work_date' => '2026-03-04', 'status' => 'half_day']);
        HrAttendance::create(['employee_id' => $this->anna->id, 'work_date' => '2026-03-05', 'status' => 'present']);
        $unpaid = HrLeaveType::create(['name' => 'Unpaid', 'days_per_year' => 0, 'is_paid' => false]);
        HrLeaveRequest::create(['employee_id' => $this->anna->id, 'leave_type_id' => $unpaid->id, 'from_date' => '2026-03-09', 'to_date' => '2026-03-10', 'days' => 2, 'status' => 'approved']);

        $item = $this->item($this->payroll(), $this->anna);

        $this->assertSame('4.5', $item->absent_days);
        $this->assertSame(round(3000 / 31 * 4.5, 2), (float) $item->absence_deduction);
        $this->assertSame(round(3000 - round(3000 / 31 * 4.5, 2), 2), (float) $item->net);
    }

    public function test_paid_leave_does_not_reduce_pay(): void
    {
        $annual = HrLeaveType::create(['name' => 'Annual', 'days_per_year' => 20, 'is_paid' => true]);
        HrLeaveRequest::create(['employee_id' => $this->anna->id, 'leave_type_id' => $annual->id, 'from_date' => '2026-03-09', 'to_date' => '2026-03-13', 'days' => 5, 'status' => 'approved']);

        $this->assertSame('3000.00', $this->item($this->payroll(), $this->anna)->net);
    }

    public function test_bonus_awards_in_the_month_are_added(): void
    {
        HrAward::create(['employee_id' => $this->anna->id, 'title' => 'Employee of the month', 'cash_amount' => 250, 'awarded_on' => '2026-03-15']);
        HrAward::create(['employee_id' => $this->anna->id, 'title' => 'Old award', 'cash_amount' => 999, 'awarded_on' => '2026-02-15']);

        $item = $this->item($this->payroll(), $this->anna);

        $this->assertSame('250.00', $item->bonus);
        $this->assertSame('3250.00', $item->net);
    }

    public function test_joiners_and_leavers_are_prorated_and_outsiders_skipped(): void
    {
        $joiner = HrEmployee::create(['code' => 'E3', 'first_name' => 'New', 'join_date' => '2026-03-16', 'basic_salary' => 3100]); // 16 of 31 days
        HrEmployee::create(['code' => 'E4', 'first_name' => 'Future', 'join_date' => '2026-04-01', 'basic_salary' => 999]);
        HrEmployee::create(['code' => 'E5', 'first_name' => 'Gone', 'join_date' => '2025-01-01', 'leave_date' => '2026-02-28', 'basic_salary' => 999, 'is_active' => false]);
        $leaver = HrEmployee::create(['code' => 'E6', 'first_name' => 'Leaver', 'join_date' => '2025-01-01', 'leave_date' => '2026-03-10', 'basic_salary' => 3100]); // 10 of 31

        $run = $this->payroll();

        $this->assertSame(4, $run->items()->count());
        $this->assertSame('1600.00', $this->item($run, $joiner)->basic);
        $this->assertSame('1000.00', $this->item($run, $leaver)->basic);
    }

    public function test_finalize_books_the_expense_and_pay_settles_it(): void
    {
        $this->anna->components()->create(['kind' => 'deduction', 'name' => 'Tax', 'amount' => 300, 'is_percent' => false]);
        HrAward::create(['employee_id' => $this->ben->id, 'title' => 'Bonus', 'cash_amount' => 100, 'awarded_on' => '2026-03-20']);
        $run = $this->payroll();
        $cash = LedgerAccount::where('system_key', 'cash')->first();

        $this->post("/admin/hr/payroll/{$run->id}/finalize")->assertSessionHasNoErrors();

        // cost = 3000 + 1500 + 100 = 4600; tax 300 withheld; net 4300.
        $this->assertSame(4600.0, $this->ledger->balance('salary_expense'));
        $this->assertSame(300.0, $this->ledger->balance('payroll_tax_payable'));
        $this->assertSame(4300.0, $this->ledger->balance('salary_payable'));
        $this->assertSame('finalized', $run->fresh()->status);
        $tb = $this->ledger->trialBalance(today());
        $this->assertSame($tb['debit'], $tb['credit']);

        // A finalized payroll cannot be regenerated or deleted.
        $this->post('/admin/hr/payroll', ['period' => '2026-03'])->assertSessionHasErrors('payroll');
        $this->delete("/admin/hr/payroll/{$run->id}")->assertSessionHasErrors('payroll');

        $this->post("/admin/hr/payroll/{$run->id}/pay", ['account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('paid', $run->fresh()->status);
        $this->assertSame(0.0, $this->ledger->balance('salary_payable'));
        $this->assertSame(-4300.0, $this->ledger->balance('cash'));
        $this->post("/admin/hr/payroll/{$run->id}/pay", ['account' => $cash->id, 'paid_on' => today()->toDateString()])->assertSessionHasErrors('payroll');
    }

    public function test_reopening_reverses_the_booking(): void
    {
        $run = $this->payroll();
        $this->post("/admin/hr/payroll/{$run->id}/finalize");
        $this->assertSame(4500.0, $this->ledger->balance('salary_expense'));

        $this->post("/admin/hr/payroll/{$run->id}/reopen")->assertSessionHasNoErrors();

        $this->assertSame('draft', $run->fresh()->status);
        $this->assertSame(0.0, $this->ledger->balance('salary_expense'));
        $this->post("/admin/hr/payroll/{$run->id}/finalize")->assertSessionHasNoErrors();
        $this->assertSame(4500.0, $this->ledger->balance('salary_expense'));
    }

    public function test_loan_is_repaid_through_payroll_instalments(): void
    {
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $this->post('/admin/hr/loans', ['employee_id' => $this->anna->id, 'amount' => 1000, 'installments' => 3, 'issued_on' => '2026-02-20', 'first_month' => '2026-03', 'account' => $cash->id, 'reason' => 'Rent'])->assertSessionHasNoErrors();

        $loan = HrLoan::firstOrFail();
        $this->assertSame(1000.0, $this->ledger->balance('staff_loans'));
        $this->assertEqualsCanonicalizing(['333.33', '333.33', '333.34'], $loan->schedule->pluck('amount')->all());

        $march = $this->payroll('2026-03');
        $this->assertSame('333.33', $this->item($march, $this->anna)->loan_deduction);
        $this->assertSame('2666.67', $this->item($march, $this->anna)->net);
        $this->post("/admin/hr/payroll/{$march->id}/finalize")->assertSessionHasNoErrors();
        $this->assertSame(666.67, $this->ledger->balance('staff_loans'));
        $this->assertSame(666.67, $loan->fresh()->load('schedule')->outstanding);

        foreach (['2026-04', '2026-05'] as $period) {
            $run = $this->payroll($period);
            $this->post("/admin/hr/payroll/{$run->id}/finalize")->assertSessionHasNoErrors();
        }
        $this->assertSame(0.0, $this->ledger->balance('staff_loans'));
        $this->assertSame('repaid', $loan->fresh()->status);
        $this->assertSame('3000.00', $this->item($this->payroll('2026-06'), $this->anna)->net);
    }

    public function test_loan_instalments_the_pay_cannot_cover_wait_for_next_month(): void
    {
        $cash = LedgerAccount::where('system_key', 'cash')->first();
        $this->ben->update(['basic_salary' => 300]);
        $this->post('/admin/hr/loans', ['employee_id' => $this->ben->id, 'amount' => 800, 'installments' => 2, 'issued_on' => '2026-02-20', 'first_month' => '2026-03', 'account' => $cash->id])->assertSessionHasNoErrors();

        $march = $this->payroll('2026-03');

        // 400 per instalment does not fit in 300 of pay: nothing is taken and net stays positive.
        $this->assertSame('0.00', $this->item($march, $this->ben)->loan_deduction);
        $this->assertSame('300.00', $this->item($march, $this->ben)->net);
    }

    public function test_loan_validation(): void
    {
        $expense = LedgerAccount::where('system_key', 'consumables')->first();
        $this->post('/admin/hr/loans', ['employee_id' => $this->anna->id, 'amount' => 100, 'installments' => 2, 'issued_on' => '2026-02-20', 'first_month' => '2026-03', 'account' => $expense->id])->assertSessionHasErrors('loan');
        $this->post('/admin/hr/loans', ['employee_id' => $this->anna->id, 'amount' => 100, 'installments' => 0, 'issued_on' => '2026-02-20', 'first_month' => '2026-13', 'account' => $expense->id])->assertSessionHasErrors(['installments', 'first_month']);
        $this->assertSame(0, HrLoan::count());
    }

    public function test_attendance_sheet_saves_and_updates(): void
    {
        $date = '2026-03-03';
        $this->get("/admin/hr/attendance?date=$date")->assertOk()->assertSee('Anna Lee');

        $this->post('/admin/hr/attendance', ['date' => $date, 'rows' => [
            ['employee' => $this->anna->id, 'status' => 'present', 'check_in' => '08:55', 'check_out' => '17:05'],
            ['employee' => $this->ben->id, 'status' => 'absent', 'check_in' => '', 'check_out' => ''],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(2, HrAttendance::count());
        $this->assertSame('08:55:00', HrAttendance::where('employee_id', $this->anna->id)->first()->check_in);

        // Saving again updates instead of duplicating; blank status clears the row.
        $this->post('/admin/hr/attendance', ['date' => $date, 'rows' => [
            ['employee' => $this->anna->id, 'status' => 'late', 'check_in' => '09:30', 'check_out' => ''],
            ['employee' => $this->ben->id, 'status' => '', 'check_in' => '', 'check_out' => ''],
        ]]);
        $this->assertSame(1, HrAttendance::count());
        $this->assertSame('late', HrAttendance::first()->status);
        $this->post('/admin/hr/attendance', ['date' => $date, 'rows' => [['employee' => $this->anna->id, 'status' => 'present', 'check_in' => '10:00', 'check_out' => '09:00']]])->assertSessionHasErrors('rows.0.check_out');

        $this->get('/admin/hr/attendance/report?month=2026-03')->assertOk()->assertSee('Anna Lee');
    }

    public function test_weekly_days_off_are_saved(): void
    {
        $this->put('/admin/hr/attendance/weekly-off', ['off' => ['fri', 'sat']])->assertSessionHasNoErrors();
        AppSettings::flush();
        $this->assertSame(['fri', 'sat'], app(\App\Services\Hr\WorkCalendar::class)->weeklyOff());
        $this->put('/admin/hr/attendance/weekly-off', ['off' => ['funday']])->assertSessionHasErrors('off.0');
    }

    public function test_leave_workflow_excludes_days_off_and_marks_attendance(): void
    {
        HrHoliday::create(['name' => 'Founders day', 'holiday_date' => '2026-03-11']);
        $annual = HrLeaveType::create(['name' => 'Annual', 'days_per_year' => 10, 'is_paid' => true]);

        // Mon 9 – Fri 13 March has 5 weekdays, one of which is a holiday.
        $this->post('/admin/hr/leave', ['employee_id' => $this->anna->id, 'leave_type_id' => $annual->id, 'from_date' => '2026-03-09', 'to_date' => '2026-03-13', 'reason' => 'Trip'])->assertSessionHasNoErrors();
        $request = HrLeaveRequest::firstOrFail();
        $this->assertSame('4.0', $request->days);

        $this->post("/admin/hr/leave/{$request->id}/decide", ['decision' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(4, HrAttendance::where('employee_id', $this->anna->id)->where('status', 'leave')->count());
        $this->assertNull(HrAttendance::whereDate('work_date', '2026-03-11')->first());
        $this->post("/admin/hr/leave/{$request->id}/decide", ['decision' => 'reject'])->assertSessionHasErrors('leave');
    }

    public function test_leave_rules(): void
    {
        $annual = HrLeaveType::create(['name' => 'Annual', 'days_per_year' => 5, 'is_paid' => true]);
        $base = ['employee_id' => $this->anna->id, 'leave_type_id' => $annual->id];

        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-09', 'to_date' => '2026-03-20'])->assertSessionHasErrors('leave'); // 10 days > 5
        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-14', 'to_date' => '2026-03-15'])->assertSessionHasErrors('leave'); // weekend only
        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-09', 'to_date' => '2026-03-10'])->assertSessionHasNoErrors();
        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-10', 'to_date' => '2026-03-11'])->assertSessionHasErrors('leave'); // overlaps
        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-16', 'to_date' => '2026-03-16', 'half_day' => 1])->assertSessionHasNoErrors();
        $this->assertSame('0.5', HrLeaveRequest::orderByDesc('id')->first()->days);
        $this->post('/admin/hr/leave', $base + ['from_date' => '2026-03-20', 'to_date' => '2026-03-10'])->assertSessionHasErrors('to_date');
    }

    public function test_candidate_can_be_hired(): void
    {
        $dept = HrDepartment::first();
        $pos = HrPosition::first();
        $candidate = HrCandidate::create(['name' => 'Carol White', 'email' => 'carol@example.com', 'position_id' => $pos->id, 'stage' => 'selected']);

        $this->get('/admin/hr-candidates')->assertOk()->assertSee('Hire');
        $this->post("/admin/hr/candidates/{$candidate->id}/hire")->assertRedirect();

        $employee = HrEmployee::where('email', 'carol@example.com')->firstOrFail();
        $this->assertSame('Carol', $employee->first_name);
        $this->assertSame('White', $employee->last_name);
        $this->assertSame($dept->id, $employee->department_id);
        $this->assertSame('hired', $candidate->fresh()->stage);

        // Hiring twice does not duplicate.
        $this->post("/admin/hr/candidates/{$candidate->id}/hire")->assertRedirect();
        $this->assertSame(1, HrEmployee::where('email', 'carol@example.com')->count());
    }

    public function test_salary_setup_screen(): void
    {
        $this->get("/admin/hr/employees/{$this->anna->id}/salary")->assertOk()->assertSee('Anna Lee');
        $this->put("/admin/hr/employees/{$this->anna->id}/salary", ['basic_salary' => 3500, 'components' => [
            ['name' => 'Transport', 'kind' => 'allowance', 'amount' => 100, 'is_percent' => 0],
            ['name' => 'Tax', 'kind' => 'deduction', 'amount' => 5, 'is_percent' => 1],
            ['name' => '', 'kind' => 'allowance', 'amount' => '', 'is_percent' => 0],
        ]])->assertSessionHasNoErrors();

        $this->assertSame('3500.00', $this->anna->fresh()->basic_salary);
        $this->assertSame(2, $this->anna->components()->count());
        $this->put("/admin/hr/employees/{$this->anna->id}/salary", ['basic_salary' => 3500, 'components' => []]);
        $this->assertSame(0, $this->anna->components()->count());
    }

    public function test_payslip_csv_and_screens(): void
    {
        $run = $this->payroll();
        $item = $run->items()->first();

        $this->get('/admin/hr/payroll')->assertOk()->assertSee('March 2026');
        $this->get("/admin/hr/payroll/{$run->id}")->assertOk()->assertSee('Finalize');
        $this->get("/admin/hr/payroll/{$run->id}/export")->assertOk();
        // Drafts have no payslips yet.
        $this->get("/admin/hr/payroll/{$run->id}/payslip/{$item->id}")->assertNotFound();

        $this->post("/admin/hr/payroll/{$run->id}/finalize");
        $r = $this->get("/admin/hr/payroll/{$run->id}/payslip/{$item->id}")->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('content-type'));
        $this->get('/admin/hr/leave')->assertOk();
        $this->get('/admin/hr/loans')->assertOk();
        $this->get('/admin/hr/attendance')->assertOk();
    }

    public function test_master_data_screens_and_protections(): void
    {
        $this->post('/admin/hr-employees', ['first_name' => 'Dan', 'basic_salary' => 2000, 'join_date' => '2026-01-01', 'is_active' => 1])->assertRedirect('/admin/hr-employees');
        $this->assertMatchesRegularExpression('/^EMP-\d{4}$/', HrEmployee::where('first_name', 'Dan')->value('code'));
        $this->post('/admin/hr-employees', ['first_name' => 'Eve', 'basic_salary' => 1, 'join_date' => '2026-01-01', 'leave_date' => '2025-01-01'])->assertSessionHasErrors('leave_date');
        $this->post('/admin/hr-holidays', ['name' => 'New year', 'holiday_date' => '2026-01-01'])->assertRedirect();
        $this->post('/admin/hr-holidays', ['name' => 'Dup', 'holiday_date' => '2026-01-01'])->assertSessionHasErrors('holiday_date');
        $this->post('/admin/hr-awards', ['employee_id' => $this->anna->id, 'title' => 'Star', 'awarded_on' => '2026-03-01', 'cash_amount' => 50])->assertRedirect();

        $this->delete('/admin/hr-departments/'.$this->anna->department_id)->assertSessionHasErrors('delete');
        $this->delete('/admin/hr-positions/'.$this->anna->position_id)->assertSessionHasErrors('delete');
        $run = $this->payroll();
        $this->delete('/admin/hr-employees/'.$this->anna->id)->assertSessionHasErrors('delete');
    }

    public function test_hr_manager_can_work_but_not_the_ledger_and_front_desk_cannot_see_salaries(): void
    {
        $hr = User::create(['firstname' => 'H', 'lastname' => 'R', 'email' => 'hr@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $hr->assignRole('HR Manager');
        $this->actingAs($hr, 'admin');
        $this->get('/admin/hr/payroll')->assertOk();
        $this->get('/admin/hr/attendance')->assertOk();
        $this->get('/admin/hr-employees')->assertOk();
        $this->post('/admin/hr/payroll', ['period' => '2026-03'])->assertRedirect();
        $this->get('/admin/accounting/trial-balance')->assertForbidden();
        $this->get('/admin/reservations')->assertForbidden();

        $desk = User::create(['firstname' => 'F', 'lastname' => 'D', 'email' => 'fd9@example.com', 'password' => Hash::make('Passw0rd!'), 'status' => 1, 'usertype' => 1, 'is_admin' => 0]);
        $desk->assignRole('Front Desk');
        $this->actingAs($desk, 'admin');
        $this->get('/admin/hr/payroll')->assertForbidden();
        $this->get('/admin/hr/loans')->assertForbidden();
        $this->get("/admin/hr/employees/{$this->anna->id}/salary")->assertForbidden();
        $this->post('/admin/hr/payroll', ['period' => '2026-03'])->assertForbidden();
    }
}
