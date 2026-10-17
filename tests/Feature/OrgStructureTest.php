<?php

namespace Tests\Feature;

use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\User;
use App\Services\ApprovalHierarchyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrgStructureTest extends TestCase
{
    use DatabaseTransactions;

    protected function getAdminUser(): User
    {
        return User::where('username', 'peter')->first() ?? User::first();
    }

    /**
     * Test 1: Org Structure page loads successfully with HTTP 200
     */
    public function test_org_structure_page_renders_successfully(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('hr.people.organization'));

        $response->assertStatus(200);
        $response->assertViewHas('companies');
        $response->assertSee('Total Workforce');
        $response->assertSee('Summit Leader');
        $response->assertSee('Tree View');
        $response->assertSee('Department Grid');
        $response->assertSee('Table Directory');
        $response->assertSee('id="orgCompanyFilter"', false);
        $response->assertSee('id="orgCanvasViewport"', false);
        $response->assertSee('id="orgZoomControls"', false);
    }

    /**
     * Test 2: Org Structure Data API returns nested tree JSON with Summit Leader
     */
    public function test_org_structure_data_returns_nested_tree(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin)->get(route('hr.people.organization.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tree',
            'unassigned',
            'departments',
            'branches',
            'companies',
            'stats' => [
                'total',
                'top_executives',
                'department_heads',
                'unassigned',
            ],
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['tree']);

        // First tree root should be Dorothy Diaz (Summit Leader)
        $root = $data['tree'][0];
        $this->assertEquals('Dorothy Diaz', $root['full_name']);
        $this->assertTrue($root['is_top_leader']);
        $this->assertGreaterThan(0, count($root['children']));
    }

    /**
     * Test 3: Reassign Manager updates employee's supervisor_id
     */
    public function test_assign_supervisor_updates_manager(): void
    {
        $admin = $this->getAdminUser();
        $staff = Employee::find(7); // John Cashier

        $response = $this->actingAs($admin)->postJson(route('hr.people.organization.assign-supervisor'), [
            'employee_id' => $staff->id,
            'supervisor_id' => 4, // Carlos Mendoza
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $staff->refresh();
        $this->assertEquals(4, $staff->supervisor_id);
    }

    /**
     * Test 4: Cycle Prevention blocks assigning employee to report to their subordinate
     */
    public function test_cycle_prevention_rejects_subordinate_assignment(): void
    {
        $admin = $this->getAdminUser();
        $dorothy = Employee::find(1); // Dorothy Diaz (Top Leader)
        $cashier = Employee::find(7); // John Cashier (Subordinate down the tree)

        // Attempting to make Dorothy report to John Cashier must be blocked
        $response = $this->actingAs($admin)->postJson(route('hr.people.organization.assign-supervisor'), [
            'employee_id' => $dorothy->id,
            'supervisor_id' => $cashier->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    /**
     * Test 5: Appoint Department Head updates department_head_employee_id
     */
    public function test_appoint_department_head(): void
    {
        $admin = $this->getAdminUser();
        $dept = Department::where('department_name', 'Back of House')->first();
        $this->assertNotNull($dept);

        $response = $this->actingAs($admin)->postJson(route('hr.people.departments.assign-head', $dept->id), [
            'head_employee_id' => 5, // Rodrigo Santos
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $dept->refresh();
        $this->assertEquals(5, $dept->head_employee_id);
        $this->assertEquals('Rodrigo Santos', $dept->head?->full_name);
    }

    /**
     * Test 6: ApprovalHierarchyService enforces sequential approvals without skipping
     */
    public function test_approval_service_sequential_chain(): void
    {
        $service = new ApprovalHierarchyService();
        $johnCashier = Employee::find(7); // Reports to Maria Garcia (ID: 6)

        $this->assertNotNull($johnCashier);
        $this->assertEquals(6, $johnCashier->supervisor_id);

        // Standard Leave Request (under 5 days) -> Must be immediate supervisor ONLY
        $chain = $service->getSequentialApprovalChain($johnCashier, 'leave', 0, 2);
        $this->assertCount(1, $chain);
        $this->assertEquals('Immediate Supervisor', $chain[0]['level_name']);
        $this->assertEquals(6, $chain[0]['employee_id']); // Maria Garcia

        // Extended Leave Request (>= 5 days) -> Sequential escalation up the tree
        $extendedChain = $service->getSequentialApprovalChain($johnCashier, 'leave', 0, 7);
        $this->assertGreaterThanOrEqual(2, count($extendedChain));
        $this->assertEquals('Immediate Supervisor', $extendedChain[0]['level_name']);
        $this->assertEquals(6, $extendedChain[0]['employee_id']); // Maria Garcia
        $this->assertEquals(4, $extendedChain[1]['employee_id']); // Carlos Mendoza (Maria's manager)
    }

    /**
     * Test 7: ApprovalHierarchyService auto-advances when Immediate Supervisor IS Department Head (Gap 1)
     */
    public function test_approval_service_auto_advance_when_supervisor_is_dept_head(): void
    {
        $service = new ApprovalHierarchyService();
        $cook = Employee::find(9); // Marco Rossi reports to Rodrigo Santos (ID: 5)
        $this->assertNotNull($cook);
        $this->assertEquals(5, $cook->supervisor_id);

        // Rodrigo Santos is also Back of House Department Head (ID: 5)
        $dept = Department::where('department_name', 'Back of House')->first();
        $this->assertEquals(5, $dept->head_employee_id);

        // Extended leave for cook: Rodrigo is supervisor AND dept head
        // The chain must not list Rodrigo twice!
        $chain = $service->getSequentialApprovalChain($cook, 'leave', 0, 7);
        $approverIds = collect($chain)->pluck('employee_id')->toArray();

        // Rodrigo Santos must only appear ONCE in the chain
        $rodrigoOccurrences = array_count_values($approverIds)[5] ?? 0;
        $this->assertEquals(1, $rodrigoOccurrences, 'Immediate supervisor who is also Dept Head should not appear twice');
    }

    /**
     * Test 8: Unassigned employee with supervisor_id = null is not treated as Summit Leader (Gap 3)
     */
    public function test_unassigned_employee_is_not_summit_leader(): void
    {
        // Create or find an employee with supervisor_id = null and NO subordinates
        $unassigned = Employee::where('employee_first_name', 'Miguel')->first();
        if (!$unassigned) {
            $unassigned = Employee::create([
                'employee_id' => 'EMP-TEST-' . rand(1000, 9999),
                'first_name' => 'Test',
                'last_name' => 'Floating',
                'date_hired' => now()->toDateString(),
                'employment_status' => 'Active',
                'employment_type' => 'Regular',
                'branch_id' => \App\Models\Hr\Branch::first()?->id ?? 8,
                'supervisor_id' => null,
            ]);
        }

        $this->assertTrue($unassigned->isUnassigned());
        $this->assertFalse($unassigned->isTopLevelLeader());

        $service = new ApprovalHierarchyService();
        $topLeader = $service->getTopLeader();
        $this->assertEquals(1, $topLeader->id, 'Top Leader must be Dorothy Diaz, not an unassigned employee');
    }
}
