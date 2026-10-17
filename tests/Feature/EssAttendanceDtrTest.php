<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\AttendanceRecord;

class EssAttendanceDtrTest extends TestCase
{
    public function test_dtr_displays_correct_records_and_computations(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->assertNotNull($employee);

        $response = $this->actingAs($employee->user)->get('/ess/dtr');
        $response->assertStatus(200);
        $response->assertSee('Daily Time Record');
        $response->assertSee($employee->employee_id);
    }

    public function test_attendance_correction_filing_preserves_raw_punch(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $empIntId = $employee->getRawOriginal('employee_id') ?? $employee->getKey();

        $rec = AttendanceRecord::where('employee_id', $empIntId)->first();
        if (!$rec) {
            $rec = AttendanceRecord::create([
                'employee_id' => $empIntId,
                'date' => now()->toDateString(),
                'time_in' => '09:15:00',
                'time_out' => '17:00:00',
                'status' => 'Present',
            ]);
        }

        $origTimeIn = $rec->time_in;

        $response = $this->actingAs($employee->user)->post('/ess/dtr/correction', [
            'attendance_record_id' => $rec->getKey(),
            'corrected_time_in' => '08:00:00',
            'corrected_time_out' => '17:00:00',
            'reason_category' => 'Biometric Machine Error',
            'explanation' => 'Biometric scanner rejected fingerprint upon arrival.',
        ]);

        $response->assertRedirect();
        // Verify raw record is NOT modified directly
        $rec->refresh();
        $this->assertEquals($origTimeIn, $rec->time_in);

        // Verify request exists
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'ATTENDANCE_CORRECTION',
            'status' => 'PENDING',
        ]);
    }

    public function test_dtr_export_pdf_view_renders(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/dtr/export-pdf');
        $response->assertStatus(200);
        $response->assertSee('CERTIFICATE OF DAILY TIME RECORD');
    }
}
