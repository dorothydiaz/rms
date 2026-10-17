<?php

namespace Tests\Feature;

use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\User;
use App\Services\ApprovalHierarchyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApprovalHierarchyServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected ApprovalHierarchyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApprovalHierarchyService::class);
    }

    /**
     * Gap 1: Duplicate Approval Bug Fix
     * When immediate supervisor is also the Department Head, auto-stamp Step 2
     * so the manager never approves the exact same ticket twice.
     */
    public function test_gap_1_auto_advance_when_supervisor_is_dept_head()
    {
        // Marco Rossi (ID: 9) reports to Rodrigo Santos (ID: 5)
        // Rodrigo Santos is also Back of House Department Head (head_employee_id = 5)
        $cook = Employee::find(9);
        $this->assertNotNull($cook);
        $this->assertEquals(5, $cook->supervisor_id);

        $dept = Department::where('department_name', 'Back of House')->first();
        $this->assertNotNull($dept);
        $this->assertEquals(5, $dept->head_employee_id);

        $chain = $this->service->getSequentialApprovalChain($cook, 'leave', 0, 7);

        // Chain should contain Rodrigo Santos exactly once, not duplicated
        $rodrigoOccurrences = array_filter($chain, fn($item) => $item['employee_id'] === 5);
        $this->assertCount(1, $rodrigoOccurrences);
    }

    /**
     * Gap 2: Sequential Tree-Climbing (John Cashier -> Maria Garcia -> Carlos Mendoza -> Dorothy Diaz)
     */
    public function test_gap_2_chain_of_command_tree_climbing()
    {
        // John Cashier (ID: 7) reports to Maria Garcia (ID: 6)
        // Maria Garcia reports to Carlos Mendoza (ID: 4)
        // Carlos Mendoza reports to Dorothy Diaz (ID: 1)
        $john = Employee::find(7);
        $this->assertNotNull($john);

        $reportingLine = $john->getLineOfReporting()->pluck('id')->toArray();
        $this->assertEquals([6, 4, 1], $reportingLine);

        // Multi-tier leave chain (>= 5 days) climbs sequentially: John -> Maria (Step 1) -> Carlos (Step 2)
        $chain = $this->service->getSequentialApprovalChain($john, 'leave', 0, 7);
        $this->assertGreaterThanOrEqual(2, count($chain));
        $this->assertEquals(6, $chain[0]['employee_id']); // Maria Garcia
        $this->assertEquals(4, $chain[1]['employee_id']); // Carlos Mendoza
    }

    /**
     * Gap 3: Unassigned employees (supervisor_id = NULL) do not become CEOs
     */
    public function test_gap_3_unassigned_employee_not_treated_as_summit_leader()
    {
        $newHire = Employee::create([
            'employee_id' => 'EMP-TEST-FLOATING',
            'first_name' => 'Floating',
            'last_name' => 'Dishwasher',
            'date_hired' => now()->toDateString(),
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'supervisor_id' => null,
        ]);

        $this->assertTrue($newHire->isUnassigned());
        $this->assertFalse($newHire->isTopLevelLeader());

        $topLeader = $this->service->getTopLeader();
        $this->assertNotNull($topLeader);
        $this->assertEquals(1, $topLeader->id, 'Top Leader must remain Dorothy Diaz (ID: 1), never unassigned staff');
    }

    /**
     * Gap 4: Transactional approval tracking columns exist on all target tables
     */
    public function test_gap_4_transactional_tables_have_hierarchical_approval_columns()
    {
        $tables = [
            'hr_leave_requests' => 'leave_request_',
            'purchase_order_header' => '',
            'stock_waste_header' => '',
            'stock_out_header' => '',
        ];

        foreach ($tables as $table => $prefix) {
            $this->assertTrue(Schema::hasColumn($table, $prefix . 'current_approver_id'), "Table {$table} missing current_approver_id");
            $this->assertTrue(Schema::hasColumn($table, $prefix . 'approval_step'), "Table {$table} missing approval_step");
            $this->assertTrue(Schema::hasColumn($table, $prefix . 'supervisor_approved_at'), "Table {$table} missing supervisor_approved_at");
            $this->assertTrue(Schema::hasColumn($table, $prefix . 'dept_head_approved_at'), "Table {$table} missing dept_head_approved_at");
            $this->assertTrue(Schema::hasColumn($table, $prefix . 'gm_approved_at'), "Table {$table} missing gm_approved_at");
        }
    }

    /**
     * Gap 5: Multi-Branch Reality (Taguig vs Makati)
     * Demonstrates that tree climbing isolates branch reporting lines
     */
    public function test_gap_5_multi_branch_reporting_isolation()
    {
        // Beatriz Valdez is Restaurant Manager - Taguig (ID: 11) reporting to Dorothy Diaz (ID: 1)
        $beatrizTaguig = Employee::find(11);
        $this->assertNotNull($beatrizTaguig);
        $this->assertEquals(1, $beatrizTaguig->supervisor_id);

        // Create a temporary Taguig staff member reporting to Beatriz
        $taguigStaff = Employee::create([
            'employee_id' => 'EMP-TEST-TAGUIG',
            'first_name' => 'Taguig',
            'last_name' => 'Server',
            'date_hired' => now()->toDateString(),
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'supervisor_id' => $beatrizTaguig->id,
        ]);

        // Immediate supervisor is Beatriz Valdez (Taguig Manager), NOT Makati Head
        $chain = $this->service->getSequentialApprovalChain($taguigStaff, 'leave', 0, 2);
        $this->assertCount(1, $chain);
        $this->assertEquals($beatrizTaguig->id, $chain[0]['employee_id']);
    }
}
