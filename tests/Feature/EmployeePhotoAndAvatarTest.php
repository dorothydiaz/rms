<?php

namespace Tests\Feature;

use App\Models\Hr\Branch;
use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\Hr\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeePhotoAndAvatarTest extends TestCase
{
    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'peter')->first() ?? User::where('role', 'hr_admin')->first() ?? User::first();
        $this->branch = Branch::first();
    }

    public function test_employee_without_photo_uses_avatar_initials(): void
    {
        $employee = Employee::create([
            'employee_id' => 'EMP-NO-PHOTO-' . rand(1000, 9999),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $this->branch->id,
            'date_hired' => '2026-01-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'photo' => null,
        ]);

        $this->assertNull($employee->photo);
        $this->assertNull($employee->photo_url);
        $this->assertEquals('MS', $employee->initials);

        $response = $this->actingAs($this->admin)->get(route('hr.people.employees'));
        $response->assertStatus(200);
        $response->assertSee('MS');
        $response->assertSee('Maria Santos');
    }

    public function test_can_upload_employee_photo_on_creation(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('avatar.jpg', 200, 'image/jpeg');

        $empId = 'EMP-WITH-PHOTO-' . rand(1000, 9999);
        $response = $this->actingAs($this->admin)->post(route('hr.people.employees.store'), [
            'employee_id' => $empId,
            'first_name' => 'UniqueJuan',
            'last_name' => 'Dela Cruz',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $this->branch->id,
            'date_hired' => '2026-02-01',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'photo' => $file,
        ]);

        $response->assertRedirect(route('hr.people.employees'));
        $response->assertSessionHas('success');

        $employee = Employee::where('employee_id', $empId)->first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->photo);

        Storage::disk('public')->assertExists($employee->photo);
        $this->assertStringContainsString('storage/employees/photos', $employee->photo_url);
    }

    public function test_can_update_and_remove_employee_photo(): void
    {
        Storage::fake('public');

        $initialFile = UploadedFile::fake()->create('profile1.png', 100, 'image/png');
        $path = $initialFile->store('employees/photos', 'public');

        $employee = Employee::create([
            'employee_id' => 'EMP-UPDATE-PHOTO-' . rand(1000, 9999),
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $this->branch->id,
            'date_hired' => '2026-01-15',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'photo' => $path,
        ]);

        // 1. Remove photo
        $res = $this->actingAs($this->admin)->put(route('hr.people.employees.update', $employee->id), [
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $this->branch->id,
            'date_hired' => '2026-01-15',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'remove_photo' => 1,
        ]);

        $res->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertNull($employee->photo);
        $this->assertEquals('ER', $employee->initials);

        // 2. Upload replacement photo
        $newFile = UploadedFile::fake()->create('profile2.png', 100, 'image/png');
        $res2 = $this->actingAs($this->admin)->put(route('hr.people.employees.update', $employee->id), [
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'branch_id' => $this->branch->id,
            'date_hired' => '2026-01-15',
            'employment_status' => 'Active',
            'employment_type' => 'Regular',
            'salary_type' => 'Monthly',
            'pay_frequency' => 'Semi-Monthly',
            'photo' => $newFile,
        ]);

        $res2->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertNotNull($employee->photo);
        Storage::disk('public')->assertExists($employee->photo);
    }
}
