<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Company;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultipleAssignmentsAndScheduleArrowsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_can_be_assigned_multiple_branches_departments_and_positions_on_create_and_update(): void
    {
        $admin = User::where('username', 'peter')->first();
        $this->actingAs($admin);

        $branches = Branch::take(2)->get();
        $departments = Department::take(2)->get();
        $positions = Position::take(2)->get();

        $this->assertCount(2, $branches);
        $this->assertCount(2, $departments);
        $this->assertCount(2, $positions);

        // 1. Create employee with multiple assignments
        $response = $this->post(route('hr.people.employees.store'), [
            'employee_id' => 'EMP-TEST-MA01',
            'first_name' => 'Testing',
            'last_name' => 'MultiAssign',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branches[0]->id,
            'assigned_branch_ids' => [$branches[0]->id, $branches[1]->id],
            'department_id' => $departments[0]->id,
            'assigned_department_ids' => [$departments[0]->id, $departments[1]->id],
            'position_id' => $positions[0]->id,
            'assigned_position_ids' => [$positions[0]->id, $positions[1]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'employment_source' => 'Company',
            'company_name' => 'Bistro Hospitality Group Inc.',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'basic_salary' => 30000,
        ]);

        $response->assertSessionHasNoErrors();

        $emp = Employee::where('first_name', 'Testing')->where('last_name', 'MultiAssign')->firstOrFail();
        $this->assertEquals($branches[0]->id, $emp->branch_id);
        $this->assertEquals([$branches[0]->id, $branches[1]->id], $emp->assigned_branch_ids);
        $this->assertEquals($departments[0]->id, $emp->department_id);
        $this->assertEquals([$departments[0]->id, $departments[1]->id], $emp->assigned_department_ids);
        $this->assertEquals($positions[0]->id, $emp->position_id);
        $this->assertEquals([$positions[0]->id, $positions[1]->id], $emp->assigned_position_ids);
        $this->assertTrue($emp->has_multiple_departments);

        // 2. Update employee assignments
        $response = $this->put(route('hr.people.employees.update', $emp->id), [
            'first_name' => 'Testing',
            'last_name' => 'MultiAssign',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branches[1]->id,
            'assigned_branch_ids' => [$branches[1]->id],
            'department_id' => $departments[1]->id,
            'assigned_department_ids' => [$departments[0]->id, $departments[1]->id],
            'position_id' => $positions[1]->id,
            'assigned_position_ids' => [$positions[1]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'employment_source' => 'Company',
            'company_name' => 'Bistro Hospitality Group Inc.',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'basic_salary' => 32000,
        ]);

        $response->assertSessionHasNoErrors();
        $emp->refresh();

        $this->assertEquals($branches[1]->id, $emp->branch_id);
        $this->assertEquals([$branches[1]->id], $emp->assigned_branch_ids);
        $this->assertEquals($departments[1]->id, $emp->department_id);
        $this->assertTrue(in_array($departments[1]->id, $emp->all_department_ids));
        $this->assertEquals($positions[1]->id, $emp->position_id);
    }

    public function test_employee_profile_displays_multiple_assigned_branches_departments_and_positions(): void
    {
        $admin = User::where('username', 'peter')->first();
        $this->actingAs($admin);

        $branches = Branch::take(2)->get();
        $departments = Department::take(2)->get();
        $positions = Position::take(2)->get();

        $emp = Employee::create([
            'employee_id' => 'EMP-TEST-MULTI',
            'first_name' => 'Clara',
            'last_name' => 'Oswald',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branches[0]->id,
            'assigned_branch_ids' => [$branches[0]->id, $branches[1]->id],
            'department_id' => $departments[0]->id,
            'assigned_department_ids' => [$departments[0]->id, $departments[1]->id],
            'position_id' => $positions[0]->id,
            'assigned_position_ids' => [$positions[0]->id, $positions[1]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'employment_source' => 'Company',
            'company_name' => 'Bistro Hospitality Group Inc.',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'basic_salary' => 35000,
        ]);

        $response = $this->get(route('hr.people.employees.show', $emp->id));
        $response->assertStatus(200);

        // Verify both branches are displayed
        $response->assertSee($branches[0]->name);
        $response->assertSee($branches[1]->name);

        // Verify both departments are displayed
        $response->assertSee($departments[0]->name);
        $response->assertSee($departments[1]->name);

        // Verify both positions are displayed
        $response->assertSee($positions[0]->name);
        $response->assertSee($positions[1]->name);

        // Verify primary badge is present
        $response->assertSee('Primary');
    }

    public function test_schedule_planner_only_shows_arrows_for_employees_with_multiple_departments(): void
    {
        $admin = User::where('username', 'peter')->first();
        $this->actingAs($admin);

        $departments = Department::take(2)->get();
        $branch = Branch::first();

        // 1. Single Department Employee
        $singleDeptEmp = Employee::create([
            'employee_id' => 'EMP-SINGLE-DEPT',
            'first_name' => 'Solo',
            'last_name' => 'DepartmentUser',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branch->id,
            'department_id' => $departments[0]->id,
            'assigned_department_ids' => [$departments[0]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
        ]);

        // 2. Multi Department Employee
        $multiDeptEmp = Employee::create([
            'employee_id' => 'EMP-MULTI-DEPT',
            'first_name' => 'Dual',
            'last_name' => 'DepartmentUser',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branch->id,
            'department_id' => $departments[0]->id,
            'assigned_department_ids' => [$departments[0]->id, $departments[1]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
        ]);

        $response = $this->get(route('hr.attendance.schedules', ['week_start' => '2026-09-28']));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Multi dept employee has arrows:
        $this->assertStringContainsString("cycleEmployeeDepartment({$multiDeptEmp->id}, -1, this)", $content);
        $this->assertStringContainsString("cycleEmployeeDepartment({$multiDeptEmp->id}, 1, this)", $content);

        // Single dept employee does NOT have arrows:
        $this->assertStringNotContainsString("cycleEmployeeDepartment({$singleDeptEmp->id}, -1, this)", $content);
    }

    public function test_department_cycler_ajax_preserves_assigned_departments(): void
    {
        $admin = User::where('username', 'peter')->first();
        $this->actingAs($admin);

        $departments = Department::take(2)->get();
        $branch = Branch::first();

        $emp = Employee::create([
            'employee_id' => 'EMP-CYCLER-TEST',
            'first_name' => 'Cycle',
            'last_name' => 'Tester',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $branch->id,
            'department_id' => $departments[0]->id,
            'assigned_department_ids' => [$departments[0]->id, $departments[1]->id],
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
        ]);

        $response = $this->postJson(route('hr.attendance.schedules.update-employee-department'), [
            'employee_id' => $emp->id,
            'department_id' => $departments[1]->id,
            'department_name' => $departments[1]->name,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'department_id' => $departments[1]->id,
            'department_name' => $departments[1]->name,
        ]);

        $emp->refresh();
        $this->assertEquals($departments[1]->id, $emp->department_id);
        $this->assertTrue(in_array($departments[0]->id, $emp->assigned_department_ids));
        $this->assertTrue(in_array($departments[1]->id, $emp->assigned_department_ids));
        $this->assertTrue($emp->has_multiple_departments);
    }
}
