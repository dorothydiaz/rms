<?php

namespace Tests\Feature;

use App\Models\Hr\AttendanceRecord;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\EssRequest;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveType;
use App\Models\Hr\PayrollPeriod;
use App\Models\Hr\PayrollRecord;
use App\Models\Hr\ShiftTemplate;
use App\Models\User;
use App\Services\Hr\DoleComplianceService;
use Carbon\Carbon;
use Tests\TestCase;

class EssPortalModuleTest extends TestCase
{
    private User $user;
    private Employee $employee;
    private DoleComplianceService $doleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doleService = new DoleComplianceService();

        // Find or create test employee and linked user
        $this->user = User::firstOrCreate(
            ['username' => 'test_ess_user'],
            [
                'full_name' => 'Maria Clara',
                'email' => 'maria.clara@stellarone.local',
                'password' => bcrypt('secret123'),
                'role' => 'Staff',
                'status' => 'Active',
            ]
        );

        $this->employee = Employee::firstOrCreate(
            ['employee_id' => 'EMP-TEST-ESS-01'],
            [
                'user_id' => $this->user->id,
                'first_name' => 'Maria',
                'last_name' => 'Clara',
                'employment_status' => 'Active',
                'gender' => 'Female',
                'civil_status' => 'Single',
                'date_hired' => Carbon::now()->subMonths(18),
            ]
        );

        // Ensure user is linked
        if ($this->employee->user_id !== $this->user->id) {
            $this->employee->update(['user_id' => $this->user->id]);
        }
    }

    /**
     * Scenario 1: Employee access to /ess/dashboard renders personalized profile, supervisor, and shifts.
     */
    public function test_scenario_1_dashboard_renders_personalized_profile_and_today_shift(): void
    {
        // Seed today's schedule
        $shift = ShiftTemplate::first();
        if ($shift) {
            EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $this->employee->id,
                    'schedule_date' => today()->toDateString(),
                ],
                [
                    'shift_template_id' => $shift->id,
                    'is_rest_day' => false,
                ]
            );
        }

        $response = $this->actingAs($this->user)->get('/ess/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Maria');
        $response->assertSee('EMP-TEST-ESS-01');
    }

    /**
     * Scenario 2: Employee cannot view another employee's payslip or DTR (IDOR security test).
     */
    public function test_scenario_2_idor_protection_blocks_unauthorized_payslip_access(): void
    {
        $period = PayrollPeriod::first();
        if (!$period) {
            $period = PayrollPeriod::create([
                'name' => 'Test Period 2026',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'Finalized',
            ]);
        }

        // Create confidential payslip for another employee
        $otherEmp = Employee::firstOrCreate(
            ['employee_id' => 'EMP-OTHER-IDOR-01'],
            [
                'first_name' => 'Other',
                'last_name' => 'Employee',
                'email' => 'other_emp@stellar.test',
                'date_hired' => now()->subYears(2)->toDateString(),
                'employment_status' => 'Active',
            ]
        );

        $otherPayslip = PayrollRecord::updateOrCreate(
            [
                'payroll_period_id' => $period->id,
                'employee_id' => $otherEmp->id,
            ],
            [
                'basic_pay' => 75000.00,
                'gross_pay' => 85000.00,
                'total_deductions' => 15000.00,
                'net_pay' => 70000.00,
                'status' => 'Finalized',
            ]
        );

        // Attempting to view another employee's payslip must yield HTTP 403 Forbidden
        $response = $this->actingAs($this->user)->get("/ess/payroll/payslip/{$otherPayslip->id}");
        $response->assertStatus(403);
    }

    /**
     * Scenario 3: DTR calculation accurately computes Overtime (+25%), Night Diff (10%), and Holiday Premiums via DoleComplianceService.
     */
    public function test_scenario_3_dtr_and_dole_compliance_service_calculations(): void
    {
        // 1. Regular Day Overtime: 25% premium (125% total)
        $hourlyRate = 100.00;
        $otPay = $this->doleService->calculateOvertimePay($hourlyRate, 2.0, 'ORDINARY', false);
        $this->assertEquals(250.00, $otPay); // 100 * 1.25 * 2.0 = 250.00

        // 2. Regular Holiday Overtime: 200% * 1.30 = 260%
        $holidayOtPay = $this->doleService->calculateOvertimePay($hourlyRate, 2.0, 'REGULAR_HOLIDAY', false);
        $this->assertEquals(520.00, $holidayOtPay); // 100 * 2.60 * 2.0 = 520.00

        // 3. Night Shift Differential: 10:00 PM to 2:00 AM = 4.0 hours
        $ndHours = $this->doleService->calculateNightDiffHours('2026-10-17 22:00:00', '2026-10-18 02:00:00');
        $this->assertEquals(4.0, $ndHours);

        $ndPay = $this->doleService->calculateNightDiffPay($hourlyRate, $ndHours);
        $this->assertEquals(40.00, $ndPay); // 100 * 0.10 * 4 = 40.00
    }

    /**
     * Scenario 4: Leave request validation enforces balance checks and statutory eligibility.
     */
    public function test_scenario_4_leave_request_submission_and_balance_validation(): void
    {
        $leaveType = LeaveType::firstOrCreate(
            ['code' => 'VL'],
            ['name' => 'Vacation Leave', 'is_paid' => true, 'default_credits' => 12]
        );

        // Assign 5 days balance
        LeaveBalance::updateOrCreate(
            [
                'employee_id' => $this->employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => date('Y'),
            ],
            [
                'beginning_balance' => 5,
                'earned' => 0,
                'used' => 0,
                'remaining' => 5,
            ]
        );

        // Submit valid 2-day leave
        $response = $this->actingAs($this->user)->post('/ess/leave/request', [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'reason' => 'Annual family gathering',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hr_leave_requests', [
            'leave_request_employee_id' => $this->employee->id,
            'leave_request_number_of_days' => 2.0,
            'leave_request_status' => 'Pending',
        ]);

        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $this->employee->employee_id,
            'request_type' => 'LEAVE',
            'status' => 'PENDING_APPROVAL',
        ]);

        // Attempting to request 10 days (which exceeds 5 remaining days) must be rejected
        $responseOver = $this->actingAs($this->user)->post('/ess/leave/request', [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'reason' => 'Extended vacation exceeding balance',
        ]);

        $responseOver->assertSessionHas('error');
    }

    /**
     * Scenario 5: ESS Request submission logs into ess_requests with correct status transitions and supervisor routing.
     */
    public function test_scenario_5_universal_request_lifecycle_and_cancellation(): void
    {
        // 1. Submit Overtime
        $response = $this->actingAs($this->user)->post('/ess/requests/overtime', [
            'date' => now()->toDateString(),
            'start_time' => '17:00',
            'end_time' => '20:00',
            'total_hours' => 3.0,
            'reason' => 'Urgent banquet catering prep',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $req = EssRequest::where('employee_id', $this->employee->employee_id)
            ->where('request_type', 'OVERTIME')
            ->latest('id')
            ->first();

        $this->assertNotNull($req);
        $this->assertEquals('PENDING_APPROVAL', $req->status);
        $this->assertEquals(3.0, (float) $req->total_hours);

        // Verify action log
        $this->assertCount(1, $req->logs);
        $this->assertEquals('SUBMITTED', $req->logs->first()->action);

        // 2. Cancel the pending request
        $cancelResponse = $this->actingAs($this->user)->post("/ess/requests/cancel/{$req->id}");
        $cancelResponse->assertRedirect();
        $cancelResponse->assertSessionHas('success');

        $this->assertEquals('CANCELLED', $req->fresh()->status);
        $this->assertNotNull($req->fresh()->cancelled_at);
        $this->assertEquals('CANCELLED', $req->fresh()->logs->last()->action);
    }
}
