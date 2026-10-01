<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDocument;
use App\Models\Hr\EmergencyContact;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementModuleTest extends TestCase
{
    protected User $adminUser;
    protected Branch $branch;
    protected Department $department;
    protected Position $position;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::firstOrCreate(['code' => 'TEST_BR'], [
            'name' => 'Test Branch',
            'is_active' => true,
        ]);

        $this->department = Department::firstOrCreate(['code' => 'TEST_DEPT'], [
            'name' => 'Test Department',
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $this->position = Position::firstOrCreate(['code' => 'TEST_POS'], [
            'name' => 'Test Crew',
            'department_id' => $this->department->id,
            'job_grade' => 'JG-1',
            'base_rate' => 610.00,
            'is_active' => true,
        ]);

        $this->adminUser = User::where('username', 'peter')->first() ?? User::where('role', 'hr_admin')->first() ?? User::first();

        $existingIds = Employee::withTrashed()->where('employee_id', 'like', 'EMP-TEST-%')->pluck('id');
        if ($existingIds->isNotEmpty()) {
            \App\Models\Hr\EmergencyContact::whereIn('employee_id', $existingIds)->delete();
            \App\Models\Hr\EmploymentHistory::whereIn('employee_id', $existingIds)->delete();
            \App\Models\Hr\EmployeeDocument::whereIn('employee_id', $existingIds)->delete();
            Employee::withTrashed()->whereIn('id', $existingIds)->forceDelete();
        }

        $testEmpId = 'EMP-TEST-' . rand(10000, 99999);

        $this->employee = Employee::create([
            'employee_id' => $testEmpId,
            'first_name' => 'Juan',
            'middle_name' => 'Protacio',
            'last_name' => 'Dela Cruz',
            'suffix' => 'Jr.',
            'preferred_name' => 'Johnny',
            'gender' => 'Male',
            'civil_status' => 'Married',
            'nationality' => 'Filipino',
            'date_of_birth' => '1992-05-15',
            'birth_place' => 'Manila',
            'email' => 'juan.delacruz@example.com',
            'company_email' => 'juan.c@company.ph',
            'personal_email' => 'juan.p@gmail.com',
            'mobile_number' => '09171234567',
            'telephone_number' => '0281234567',
            'address' => 'Unit 12B, Rizal St, Brgy Poblacion, Makati City',
            'permanent_address' => 'Unit 12B, Rizal St, Brgy Poblacion, Makati City',
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'employment_type' => 'Regular',
            'employment_status' => 'Active',
            'employment_source' => 'Company',
            'company_or_agency' => 'Restaurant Management Inc',
            'date_hired' => '2023-01-10',
            'date_of_regularization' => '2023-07-10',
            'basic_salary' => 25000.00,
            'pay_frequency' => 'Semi-monthly',
            'pay_type' => 'Salaried Monthly',
            'sss_number' => '34-1234567-8',
            'sss_verified' => true,
            'philhealth_number' => '12-345678901-2',
            'philhealth_verified' => true,
            'pagibig_number' => '1234-5678-9012',
            'pagibig_verified' => true,
            'tin_number' => '123-456-789-000',
            'tin_verified' => true,
            'rdo_code' => 'RDO 044',
            'philsys_id' => '1234-5678-9012-3456',
            'passport_number' => 'P1234567A',
            'driver_license' => 'N01-12-345678',
            'work_location' => 'Makati Flagship Store',
            'work_schedule' => 'Morning Shift 7AM-4PM',
            'job_level' => 'Senior Crew Lead',
        ]);
    }

    public function test_employee_management_index_renders_with_cards_filters_and_directory_table(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('hr.people.employees'));

        $response->assertStatus(200);
        $response->assertSee('Employee Management');
        $response->assertSee('Total Employees');
        $response->assertSee('Active');
        $response->assertSee('Probationary');
        $response->assertSee('On Leave');
        $response->assertSee('Separated');
        $response->assertSee($this->employee->employee_id);
        $response->assertSee($this->employee->full_name);
        $response->assertSee('Generate COE');
        $response->assertSee('Print 201 File');
    }

    public function test_employee_profile_detail_renders_eight_tabs_and_strong_header(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('hr.people.employees.show', $this->employee->id));

        $response->assertStatus(200);
        $response->assertSee($this->employee->full_name);
        $response->assertSee($this->employee->employee_id);
        $response->assertSee('Overview');
        $response->assertSee('Personal');
        $response->assertSee('Employment');
        $response->assertSee('Family & Education', false);
        $response->assertSee('Government IDs');
        $response->assertSee('Documents');
        $response->assertSee('Attendance & Leave', false);
        $response->assertSee('Payroll & Performance', false);
        $response->assertSee('Leave Balance');
        $response->assertSee('Attendance Rate');
        $response->assertSee('Show ID Numbers');
    }

    public function test_employee_lifecycle_actions_work_and_record_history(): void
    {
        $newPosition = Position::firstOrCreate(
            ['code' => 'TEST_SUPV_LIFE'],
            [
                'name' => 'Floor Supervisor',
                'department_id' => $this->department->id,
                'job_grade' => 'JG-2',
                'base_rate' => 750.00,
                'is_active' => true,
            ]
        );

        // 1. Change position
        $response = $this->actingAs($this->adminUser)->post(route('hr.people.employees.change-position', $this->employee->id), [
            'position_id' => $newPosition->id,
            'effective_date' => '2026-10-01',
            'reason' => 'Promotion to supervisor',
        ]);
        $response->assertRedirect();
        $this->assertEquals($newPosition->id, $this->employee->fresh()->position_id);
        $this->assertDatabaseHas('hr_employment_histories', [
            'employee_id' => $this->employee->id,
            'action_type' => 'Position Change',
            'new_value' => $newPosition->name,
            'remarks' => 'Promotion to supervisor',
        ]);

        // 2. Adjust salary
        $responseSalary = $this->actingAs($this->adminUser)->post(route('hr.people.employees.change-salary', $this->employee->id), [
            'new_salary' => 32000.00,
            'adjustment_type' => 'Merit Increase',
            'effective_date' => '2026-10-01',
            'reason' => 'Supervisory promotion increment',
        ]);
        $responseSalary->assertRedirect();
        $this->assertEquals(32000.00, (float)$this->employee->fresh()->basic_salary);

        // 3. Change status
        $responseStatus = $this->actingAs($this->adminUser)->post(route('hr.people.employees.change-status', $this->employee->id), [
            'employment_status' => 'On Leave',
            'effective_date' => '2026-10-01',
            'reason' => 'Filing study leave',
        ]);
        $responseStatus->assertRedirect();
        $this->assertEquals('On Leave', $this->employee->fresh()->employment_status);
    }

    public function test_family_emergency_contacts_and_education_crud(): void
    {
        // 1. Add Family
        $respFam = $this->actingAs($this->adminUser)->post(route('hr.people.employees.family.store', $this->employee->id), [
            'name' => 'Maria Dela Cruz',
            'relationship' => 'Spouse',
            'birth_date' => '1994-08-20',
            'occupation' => 'Accountant',
            'contact_number' => '09181112233',
            'is_dependent' => '1',
            'is_beneficiary' => '1',
        ]);
        $respFam->assertRedirect();
        $fam = $this->employee->fresh()->family_dependents;
        $this->assertCount(1, $fam);
        $this->assertEquals('Maria Dela Cruz', $fam[0]['name']);

        // 2. Add Emergency Contact
        $respEmer = $this->actingAs($this->adminUser)->post(route('hr.people.employees.emergency.store', $this->employee->id), [
            'contact_name' => 'Pedro Dela Cruz',
            'relationship' => 'Father',
            'mobile_number' => '09192223344',
            'contact_number' => '09192223344',
            'address' => 'Quezon City',
            'is_primary' => '1',
        ]);
        $respEmer->assertRedirect();
        $this->assertDatabaseHas('hr_emergency_contacts', [
            'employee_id' => $this->employee->id,
            'contact_name' => 'Pedro Dela Cruz',
            'is_primary' => true,
        ]);

        // 3. Add Education
        $respEdu = $this->actingAs($this->adminUser)->post(route('hr.people.employees.education.store', $this->employee->id), [
            'level' => 'College / Degree',
            'school' => 'University of the Philippines',
            'course' => 'BS Hotel and Restaurant Administration',
            'year_started' => '2010',
            'year_completed' => '2014',
            'status' => 'Graduated',
            'honors' => 'Cum Laude',
        ]);
        $respEdu->assertRedirect();
        $edu = $this->employee->fresh()->education_history;
        $this->assertCount(1, $edu);
        $this->assertEquals('University of the Philippines', $edu[0]['school']);
    }

    public function test_coe_and_201_file_printable_views_render_successfully(): void
    {
        $respCoe = $this->actingAs($this->adminUser)->get(route('hr.people.employees.coe', [
            'id' => $this->employee->id,
            'purpose' => 'Bank Credit Card Application',
        ]));
        $respCoe->assertStatus(200);
        $respCoe->assertSee('Certificate of Employment');
        $respCoe->assertSee($this->employee->full_name);
        $respCoe->assertSee('Bank Credit Card Application');

        $resp201 = $this->actingAs($this->adminUser)->get(route('hr.people.employees.print-201', $this->employee->id));
        $resp201->assertStatus(200);
        $resp201->assertSee('Employee 201 File Master Record');
        $resp201->assertSee($this->employee->full_name);
        $resp201->assertSee($this->employee->employee_id);
    }

    public function test_document_verify_and_replace(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('contract.pdf', 100);

        $doc = EmployeeDocument::create([
            'employee_id' => $this->employee->id,
            'document_type' => 'Contract',
            'document_name' => 'Initial Employment Contract',
            'category' => 'Employment',
            'file_path' => 'hr_documents/test_contract.pdf',
            'status' => 'Pending',
            'is_verified' => false,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $respVerify = $this->actingAs($this->adminUser)->post(route('hr.people.documents.verify', $doc->id));
        $respVerify->assertRedirect();
        $this->assertEquals('Verified', $doc->fresh()->status);
        $this->assertTrue((bool)$doc->fresh()->is_verified);

        $newFile = UploadedFile::fake()->create('updated_contract.pdf', 150);
        $respReplace = $this->actingAs($this->adminUser)->post(route('hr.people.documents.replace', $doc->id), [
            'file' => $newFile,
            'expiry_date' => '2028-12-31',
        ]);
        $respReplace->assertRedirect();
        $this->assertEquals('2028-12-31', $doc->fresh()->expiry_date->format('Y-m-d'));
    }

    protected function tearDown(): void
    {
        $existingIds = Employee::withTrashed()->where('employee_id', 'like', 'EMP-TEST-%')->pluck('id');
        if ($existingIds->isNotEmpty()) {
            \App\Models\Hr\EmergencyContact::whereIn('employee_id', $existingIds)->delete();
            \App\Models\Hr\EmploymentHistory::whereIn('employee_id', $existingIds)->delete();
            \App\Models\Hr\EmployeeDocument::whereIn('employee_id', $existingIds)->delete();
            Employee::withTrashed()->whereIn('id', $existingIds)->forceDelete();
        }
        parent::tearDown();
    }
}
