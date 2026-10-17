# DOLE-Aligned Employee Self-Service (ESS) Portal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete, professional, and fully functional DOLE-aligned Employee Self-Service (ESS) Portal for Stellar One ERP on `/ess` with dedicated standalone portal layout, statutory compliance rules engine, DTR, leaves, requests, payslips, 201 documents, and helpdesk.

**Architecture:** Modular monolith under `/ess` route prefix using a dedicated employee portal layout (`resources/views/layouts/ess.blade.php`) with modern white-surface aesthetic, glassmorphic cards, desktop top navigation, and mobile bottom navigation. Backend logic is driven by `App\Services\DoleLaborStandardsService` interacting with the shared ERP database without data duplication, guarded by `EssEmployeeMiddleware` for strict identity isolation.

**Tech Stack:** PHP 8.2+, Laravel 12, MySQL, Vanilla CSS (with CSS variables and glassmorphic tokens), Phosphor Icons, SweetAlert2.

**Spec:** `docs/superpowers/specs/2026-10-17-dole-ess-portal-design.md`

## Global Constraints

- Target Route Prefix: `/ess`
- Layout: Standalone `resources/views/layouts/ess.blade.php`
- Design Tokens: White background (`#ffffff` / `#f8fafc`), glass card surface (`rgba(255,255,255,0.92)`), primary violet/fuchsia accents (`#9333ea`, `#ec4899`), slate text (`#0f172a`, `#475569`)
- DOLE Overtime Multiplier: Ordinary 1.25x, Rest Day 1.30x, Special Non-Working 1.30x, Regular Holiday 2.00x, Rest Day + Regular Holiday 2.60x
- DOLE Night Shift Differential: 10:00 PM – 6:00 AM @ +10%
- Biometric Punch Invariant: Raw punch logs in `hr_attendance_records` are strictly immutable; corrections are managed via audit-trailed workflow
- Security Invariant: Employees can only view and mutate their own records (`employee_id = auth()->user()->employee->employee_id`)

## Review Focus

1. **Unassigned User/Employee Link:** User logs in but has no linked `hr_employees` record — should gracefully display friendly onboarding/unlinked notice instead of HTTP 500 error.
2. **Night Shift Differential Across Midnight:** Shift spans from 8:00 PM to 4:00 AM — hours between 10:00 PM and 4:00 AM (6 hours) must accurately receive the 10% premium.
3. **Statutory Leave Eligibility Edge Cases:** Employee with < 1 year tenure attempts to file SIL — system must flag ineligibility and prevent credit overdraft.
4. **ID Manipulation Attack:** Malicious user attempts to view another employee's payslip or cancel another employee's request via direct URL/API manipulation — system must return HTTP 403 Forbidden.
5. **Mobile Viewport Overflow:** Large tables (DTR, Requests ledger) must support horizontal scrolling on mobile viewports without breaking layout shell.

---

### Task 1: DOLE Labor Standards & Statutory Engine Service

**Files:**
- Create: `app/Services/DoleLaborStandardsService.php`
- Test: `tests/Unit/DoleLaborStandardsServiceTest.php`

**Interfaces:**
- Consumes: Employee salary, hourly rates, work date, shift start/end times, holiday types (`None`, `SpecialNonWorking`, `Regular`).
- Produces:
  - `calculateOvertimePay(float $hourlyRate, float $otHours, string $dayType = 'Ordinary', bool $isRestDay = false): float`
  - `calculateNightShiftDifferential(float $hourlyRate, string $timeIn, string $timeOut, string $dayType = 'Ordinary', bool $isRestDay = false): array`
  - `calculateHolidayPay(float $dailyRate, string $holidayType, bool $worked, bool $isRestDay): float`
  - `checkLeaveEligibility(\App\Models\Hr\Employee $employee, string $leaveTypeCode): array`
  - `calculateStatutoryDeductions(float $monthlyGross): array` (returns `['sss', 'philhealth', 'pagibig', 'withholding_tax']`)

- [ ] **Step 1: Write the failing unit tests for DOLE Labor Standards Service**

```php
// tests/Unit/DoleLaborStandardsServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DoleLaborStandardsService;

class DoleLaborStandardsServiceTest extends TestCase
{
    private DoleLaborStandardsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DoleLaborStandardsService();
    }

    public function test_overtime_pay_multipliers(): void
    {
        $hourlyRate = 100.00;
        // Ordinary OT (125%)
        $this->assertEquals(250.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Ordinary', false));
        // Rest Day OT (130% * 130% = 169%)
        $this->assertEquals(338.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Ordinary', true));
        // Regular Holiday OT (200% * 130% = 260%)
        $this->assertEquals(520.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Regular', false));
        // Regular Holiday on Rest Day OT (260% * 130% = 338%)
        $this->assertEquals(676.00, $this->service->calculateOvertimePay($hourlyRate, 2.0, 'Regular', true));
    }

    public function test_night_shift_differential_calculation(): void
    {
        $hourlyRate = 100.00;
        // 10:00 PM to 02:00 AM (4 hours in night window: 10pm - 6am)
        $res = $this->service->calculateNightShiftDifferential($hourlyRate, '22:00', '02:00', 'Ordinary');
        $this->assertEquals(4.0, $res['night_diff_hours']);
        $this->assertEquals(40.00, $res['night_diff_pay']); // 4 hrs * (100 * 0.10)
    }

    public function test_statutory_deductions_train_law(): void
    {
        // Monthly gross of 20,000 (below 20,833.33 taxable floor)
        $deductions = $this->service->calculateStatutoryDeductions(20000.00);
        $this->assertEquals(0.00, $deductions['withholding_tax']);
        $this->assertEquals(200.00, $deductions['pagibig']); // 200 cap
        $this->assertGreaterThan(0.00, $deductions['sss']);
        $this->assertEquals(500.00, $deductions['philhealth']); // 20,000 * 5% / 2 = 500
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/DoleLaborStandardsServiceTest.php`
Expected: FAIL with Class "App\Services\DoleLaborStandardsService" not found.

- [ ] **Step 3: Implement `App\Services\DoleLaborStandardsService`**

Implement computation logic with exact Philippine statutory rates:
- Regular day OT: 1.25x
- Rest day / Special holiday OT: 1.69x
- Special day on rest day OT: 1.95x
- Regular holiday OT: 2.60x
- Regular holiday on rest day OT: 3.38x
- Night diff: 10:00 PM to 6:00 AM @ +10%
- PhilHealth: 5% total split 50/50 (2.5% employee share)
- Pag-IBIG: 2% up to ₱10,000 max MSC (₱200 cap)
- SSS: 2025/2026 bracket schedule up to ₱30,000 MSC + WISP
- TRAIN Law tax brackets: ₱0-20,833 (0%), ₱20,833-33,333 (15% of excess), ₱33,333-66,667 (₱1,875 + 20% of excess), etc.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/DoleLaborStandardsServiceTest.php`
Expected: PASS with all 3 tests green.

- [ ] **Step 5: Commit**

```bash
git add app/Services/DoleLaborStandardsService.php tests/Unit/DoleLaborStandardsServiceTest.php
git commit -m "feat(ess): add DOLE Philippine labor standards and computation engine"
```

---

### Task 2: Database Migration & Models for ESS Requests and Helpdesk

**Files:**
- Create: `database/migrations/2026_10_17_000001_create_ess_portal_tables.php`
- Create: `app/Models/Ess/EssRequest.php`
- Create: `app/Models/Ess/EssHelpdeskTicket.php`
- Create: `app/Models/Ess/EssHelpdeskMessage.php`
- Create: `app/Models/Ess/EssAnnouncement.php`
- Test: `tests/Feature/EssDatabaseSchemaTest.php`

**Interfaces:**
- Produces:
  - Table `ess_requests`: `id`, `request_number`, `employee_id`, `request_type` (`OVERTIME`, `OFFICIAL_BUSINESS`, `ATTENDANCE_CORRECTION`, `SHIFT_SWAP`, `UNDERTIME`, `PROFILE_UPDATE`), `details` (json), `reason`, `attachment_url`, `status` (`DRAFT`, `PENDING`, `APPROVED`, `REJECTED`, `CANCELLED`), `approval_step` (int), `approver_id`, `approved_at`, `rejection_reason`, timestamps.
  - Table `ess_helpdesk_tickets`: `id`, `ticket_number`, `employee_id`, `category`, `subject`, `priority`, `status` (`OPEN`, `IN_PROGRESS`, `RESOLVED`, `CLOSED`), timestamps.
  - Table `ess_helpdesk_messages`: `id`, `ticket_id`, `user_id`, `message`, `attachment_url`, timestamps.
  - Table `ess_announcements`: `id`, `title`, `content`, `category`, `priority`, `published_at`, `expires_at`, `is_active`.

- [ ] **Step 1: Write the failing test for ESS database schema and models**

```php
// tests/Feature/EssDatabaseSchemaTest.php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Ess\EssRequest;
use App\Models\Ess\EssHelpdeskTicket;
use App\Models\Ess\EssAnnouncement;
use App\Models\Hr\Employee;

class EssDatabaseSchemaTest extends TestCase
{
    public function test_ess_request_can_be_created_and_queried(): void
    {
        $employee = Employee::first();
        $this->assertNotNull($employee, 'Seeded employee required');

        $req = EssRequest::create([
            'request_number' => 'REQ-2026-0001',
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'details' => ['ot_date' => '2026-10-18', 'hours' => 2.5, 'type' => 'PostShift'],
            'reason' => 'Quarter-end audit closing backlog',
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('ess_requests', [
            'request_number' => 'REQ-2026-0001',
            'status' => 'PENDING',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssDatabaseSchemaTest.php`
Expected: FAIL with table not found or class not found.

- [ ] **Step 3: Create migration and Eloquent models**

Implement `database/migrations/2026_10_17_000001_create_ess_portal_tables.php` and run migration:
```bash
php artisan migrate
```
Create `EssRequest.php`, `EssHelpdeskTicket.php`, `EssHelpdeskMessage.php`, `EssAnnouncement.php` with proper casts, UUID/auto-increments, and relationships to `Employee` and `User`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssDatabaseSchemaTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_10_17_000001_create_ess_portal_tables.php app/Models/Ess/ tests/Feature/EssDatabaseSchemaTest.php
git commit -m "feat(ess): create database tables and Eloquent models for ESS requests and helpdesk"
```

---

### Task 3: Security & Scoping Middleware (`EssEmployeeMiddleware`) and ESS Portal Shell Layout

**Files:**
- Create: `app/Http/Middleware/EssEmployeeMiddleware.php`
- Create: `resources/views/layouts/ess.blade.php`
- Create: `public/assets/css/ess-portal.css`
- Modify: `routes/web.php` (register `/ess` group with `auth` and `ess.employee` middleware)
- Test: `tests/Feature/EssAuthAndSecurityTest.php`

**Interfaces:**
- Consumes: Authenticated user session.
- Produces:
  - Injects resolved `$employee` into request attributes (`$request->attributes->set('current_employee', $employee)`).
  - Denies access with friendly message if user has no linked employee profile.
  - Layout with responsive desktop topbar + mobile bottom navigation, notification dropdown, and profile drawer.

- [ ] **Step 1: Write the failing security and layout test**

```php
// tests/Feature/EssAuthAndSecurityTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Hr\Employee;

class EssAuthAndSecurityTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/ess/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_employee_can_access_ess_dashboard(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->assertNotNull($employee);

        $response = $this->actingAs($employee->user)->get('/ess/dashboard');
        $response->assertStatus(200);
        $response->assertSee($employee->first_name);
        $response->assertSee('Employee Self-Service');
    }

    public function test_user_without_employee_profile_is_blocked_safely(): void
    {
        $user = User::factory()->create(['status' => 'Active']);
        $response = $this->actingAs($user)->get('/ess/dashboard');
        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssAuthAndSecurityTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `EssEmployeeMiddleware`, `layouts/ess.blade.php`, `public/assets/css/ess-portal.css`, and routes**

1. Create `EssEmployeeMiddleware`:
   - Checks `Auth::check()`.
   - Resolves `$user->employee`. If null, aborts with 403 or renders an informative unlinked account page.
2. Register `ess.employee` middleware alias in `bootstrap/app.php`.
3. Create `resources/views/layouts/ess.blade.php`:
   - Clean, white canvas layout with glassmorphic cards, Outfit/Poppins typography, Phosphor icons, top navigation bar, quick "+ Request" modal trigger, and mobile bottom bar.
4. Add route group `/ess` in `routes/web.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssAuthAndSecurityTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/EssEmployeeMiddleware.php bootstrap/app.php resources/views/layouts/ess.blade.php public/assets/css/ess-portal.css routes/web.php tests/Feature/EssAuthAndSecurityTest.php
git commit -m "feat(ess): add security scoping middleware and standalone ESS portal layout shell"
```

---

### Task 4: ESS Dashboard & Attendance / Daily Time Record (DTR) Module

**Files:**
- Create: `app/Http/Controllers/Ess/EssDashboardController.php`
- Create: `app/Http/Controllers/Ess/EssAttendanceController.php`
- Create: `resources/views/ess/dashboard.blade.php`
- Create: `resources/views/ess/attendance/dtr.blade.php`
- Test: `tests/Feature/EssAttendanceDtrTest.php`

**Interfaces:**
- Consumes: `hr_attendance_records`, `hr_employees`, `DoleLaborStandardsService`.
- Produces:
  - Route `GET /ess/dashboard`: renders dashboard with profile summary, shift status, leave balance, net pay teaser, recent requests.
  - Route `GET /ess/dtr`: renders interactive monthly/period DTR with overtime, night diff, tardiness, undertime calculations, and printable view.
  - Route `POST /ess/dtr/correction`: submits an attendance correction request (creates `EssRequest` and `hr_attendance_corrections` without altering raw record).
  - Route `GET /ess/dtr/export-pdf`: exports formal DTR PDF with employee signature block.

- [ ] **Step 1: Write the failing feature test for Dashboard and DTR**

```php
// tests/Feature/EssAttendanceDtrTest.php
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
        $rec = AttendanceRecord::where('employee_id', $employee->employee_id)->first();
        if (!$rec) {
            $rec = AttendanceRecord::create([
                'employee_id' => $employee->employee_id,
                'date' => now()->toDateString(),
                'time_in' => '09:15:00',
                'time_out' => '17:00:00',
                'status' => 'Present',
            ]);
        }

        $origTimeIn = $rec->time_in;

        $response = $this->actingAs($employee->user)->post('/ess/dtr/correction', [
            'attendance_record_id' => $rec->id,
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
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssAttendanceDtrTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement Dashboard, DTR Controllers, and Views**

1. `EssDashboardController`:
   - Gathers today's schedule, punch timeline, leave credit summary, active requests count, and latest payslip net pay.
2. `EssAttendanceController`:
   - `dtrIndex`: loads records for selected cutoff, calculates totals (regular hours, overtime, night differential, tardiness, undertime) via `DoleLaborStandardsService`.
   - `submitCorrection`: creates `EssRequest` and links to `AttendanceCorrection`.
   - `exportPdf`: renders clean printable HTML/PDF view.
3. Views:
   - `resources/views/ess/dashboard.blade.php`: hero card, today's shift card, KPI stat cards, holiday alerts, quick request modal.
   - `resources/views/ess/attendance/dtr.blade.php`: cutoff selector, filterable data table, correction modal with pre-filled inputs.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssAttendanceDtrTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssDashboardController.php app/Http/Controllers/Ess/EssAttendanceController.php resources/views/ess/dashboard.blade.php resources/views/ess/attendance/ routes/web.php tests/Feature/EssAttendanceDtrTest.php
git commit -m "feat(ess): implement employee dashboard and interactive DTR with non-destructive correction filing"
```

---

### Task 5: Work Schedule & Shift Swap Management Module

**Files:**
- Create: `app/Http/Controllers/Ess/EssScheduleController.php`
- Create: `resources/views/ess/schedule/index.blade.php`
- Test: `tests/Feature/EssScheduleTest.php`

**Interfaces:**
- Consumes: `hr_employee_schedules`, `hr_shift_templates`, `hr_holidays`.
- Produces:
  - Route `GET /ess/schedule`: renders monthly calendar and list view of employee shifts, rest days, and official holidays.
  - Route `POST /ess/schedule/swap`: files a shift swap request with another employee in the same branch/department.
  - Route `POST /ess/schedule/adjustment`: files a rest-day or shift adjustment request.

- [ ] **Step 1: Write the failing test for Work Schedule**

```php
// tests/Feature/EssScheduleTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;

class EssScheduleTest extends TestCase
{
    public function test_schedule_page_renders_calendar_and_shifts(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/schedule');
        $response->assertStatus(200);
        $response->assertSee('Work Schedule');
    }

    public function test_shift_swap_request_creation(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $coWorker = Employee::where('branch_id', $employee->branch_id)
            ->where('employee_id', '!=', $employee->employee_id)
            ->first();

        $this->assertNotNull($coWorker, 'Need co-worker in same branch');

        $response = $this->actingAs($employee->user)->post('/ess/schedule/swap', [
            'swap_with_employee_id' => $coWorker->employee_id,
            'my_shift_date' => now()->addDays(2)->toDateString(),
            'target_shift_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Family emergency on Friday shift',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'SHIFT_SWAP',
            'status' => 'PENDING',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssScheduleTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `EssScheduleController` and `resources/views/ess/schedule/index.blade.php`**

- Render month calendar with shift blocks, Rest Days (`RD`), Regular Holidays (`RH`), Special Holidays (`SH`).
- Provide interactive modal to request Shift Swap with eligible peers in the same branch/department or request Shift Template adjustments.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssScheduleTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssScheduleController.php resources/views/ess/schedule/ routes/web.php tests/Feature/EssScheduleTest.php
git commit -m "feat(ess): implement employee work schedule calendar and shift swap request system"
```

---

### Task 6: DOLE Statutory & Company Leaves Module

**Files:**
- Create: `app/Http/Controllers/Ess/EssLeaveController.php`
- Create: `resources/views/ess/leave/index.blade.php`
- Test: `tests/Feature/EssLeaveManagementTest.php`

**Interfaces:**
- Consumes: `hr_leave_types`, `hr_leave_balances`, `hr_leave_requests`, `DoleLaborStandardsService`.
- Produces:
  - Route `GET /ess/leave`: renders leave entitlement balances (SIL, VL, SL, Maternity, Paternity, Solo Parent, VAWC) and transaction history.
  - Route `POST /ess/leave/store`: validates statutory eligibility, duration (full-day / half-day), uploads supporting files, and creates leave request.
  - Route `POST /ess/leave/{id}/cancel`: allows cancellation of pending leave requests.

- [ ] **Step 1: Write the failing feature test for Leave Management**

```php
// tests/Feature/EssLeaveManagementTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveType;

class EssLeaveManagementTest extends TestCase
{
    public function test_leave_index_displays_statutory_and_company_balances(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/leave');
        $response->assertStatus(200);
        $response->assertSee('Leave Management');
        $response->assertSee('Service Incentive Leave');
    }

    public function test_filing_leave_creates_pending_request(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $leaveType = LeaveType::first();
        $this->assertNotNull($leaveType);

        $response = $this->actingAs($employee->user)->post('/ess/leave/store', [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'duration_type' => 'Full Day',
            'reason' => 'Annual medical checkup',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('hr_leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'Pending',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssLeaveManagementTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `EssLeaveController` and `resources/views/ess/leave/index.blade.php`**

- Fetch balances from `hr_leave_balances`. If employee has >= 1 year tenure and no SIL balance row exists, automatically calculate baseline 5 days SIL.
- Support half-day morning (0.5 day), half-day afternoon (0.5 day), and multi-day spans.
- Enforce mandatory attachment upload for Sick Leave > 2 days or VAWC/Solo Parent.
- Provide cancel action for `Pending` status.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssLeaveManagementTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssLeaveController.php resources/views/ess/leave/ routes/web.php tests/Feature/EssLeaveManagementTest.php
git commit -m "feat(ess): implement DOLE statutory and company leave portal with balance tracking"
```

---

### Task 7: Unified "My Requests" & Overtime / Requests Hub

**Files:**
- Create: `app/Http/Controllers/Ess/EssRequestController.php`
- Create: `resources/views/ess/requests/index.blade.php`
- Test: `tests/Feature/EssRequestWorkflowTest.php`

**Interfaces:**
- Consumes: `ess_requests`, `hr_employees`, supervisor hierarchy.
- Produces:
  - Route `GET /ess/requests`: centralized list of all submissions (Overtime, OB, Undertime, Rest-day work, Profile changes, Leaves, DTR Corrections) with status filters (`All`, `Pending`, `Approved`, `Rejected`).
  - Route `POST /ess/requests/overtime`: files pre-shift or post-shift overtime request with estimated hours and reason.
  - Route `POST /ess/requests/official-business`: files OB pass with client/location details.
  - Route `POST /ess/requests/undertime`: files early departure notification.
  - Route `POST /ess/requests/{id}/cancel`: cancels a pending request.
  - Route `GET /ess/requests/{id}/timeline`: returns JSON of approval workflow steps and history.

- [ ] **Step 1: Write the failing test for My Requests Hub**

```php
// tests/Feature/EssRequestWorkflowTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Ess\EssRequest;

class EssRequestWorkflowTest extends TestCase
{
    public function test_overtime_request_submission_and_tracking(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->post('/ess/requests/overtime', [
            'ot_date' => now()->addDays(1)->toDateString(),
            'start_time' => '17:00',
            'end_time' => '20:00',
            'hours' => 3.0,
            'reason' => 'Store inventory physical audit',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_requests', [
            'employee_id' => $employee->employee_id,
            'request_type' => 'OVERTIME',
            'status' => 'PENDING',
        ]);
    }

    public function test_employee_can_cancel_own_pending_request(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $req = EssRequest::create([
            'request_number' => 'REQ-TEST-CANCEL',
            'employee_id' => $employee->employee_id,
            'request_type' => 'OFFICIAL_BUSINESS',
            'details' => ['destination' => 'Makati City Hall'],
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($employee->user)->post("/ess/requests/{$req->id}/cancel");
        $response->assertRedirect();

        $req->refresh();
        $this->assertEquals('CANCELLED', $req->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssRequestWorkflowTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `EssRequestController` and `resources/views/ess/requests/index.blade.php`**

- Include modal forms for Overtime, Official Business (OB), Undertime, and Profile updates.
- Tabbed filters (`All`, `Pending`, `Approved`, `Rejected`, `Cancelled`).
- Step-by-step visual chain tracker displaying Level 1 (Supervisor), Level 2 (Manager), Level 3 (HR), timestamps, and remarks.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssRequestWorkflowTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssRequestController.php resources/views/ess/requests/ routes/web.php tests/Feature/EssRequestWorkflowTest.php
git commit -m "feat(ess): implement centralized My Requests hub with approval chain visualization"
```

---

### Task 8: Payroll, Itemized Payslip & Dispute Management Module

**Files:**
- Create: `app/Http/Controllers/Ess/EssPayrollController.php`
- Create: `resources/views/ess/payroll/index.blade.php`
- Test: `tests/Feature/EssPayrollPayslipTest.php`

**Interfaces:**
- Consumes: `hr_payroll_records`, `hr_payroll_periods`, `DoleLaborStandardsService`.
- Produces:
  - Route `GET /ess/payroll`: renders payslip viewer, period switcher, itemized earnings (Basic, OT, Night Diff, Premiums, Allowances), statutory deductions (SSS, PhilHealth, Pag-IBIG, BIR Tax), and net pay banner.
  - Route `GET /ess/payroll/{id}/pdf`: exports official DOLE-compliant PDF payslip.
  - Route `POST /ess/payroll/{id}/inquiry`: submits confidential payroll dispute inquiry to HR.

- [ ] **Step 1: Write the failing test for Payroll & Payslips**

```php
// tests/Feature/EssPayrollPayslipTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\Hr\PayrollRecord;

class EssPayrollPayslipTest extends TestCase
{
    public function test_payroll_page_shows_itemized_earnings_and_deductions(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->get('/ess/payroll');
        $response->assertStatus(200);
        $response->assertSee('Payroll & Payslips');
    }

    public function test_employee_cannot_access_another_employees_payslip(): void
    {
        $employee1 = Employee::whereNotNull('user_id')->first();
        $employee2 = Employee::whereNotNull('user_id')->where('employee_id', '!=', $employee1->employee_id)->first();
        $this->assertNotNull($employee2);

        $record2 = PayrollRecord::where('employee_id', $employee2->id)->first();
        if ($record2) {
            $response = $this->actingAs($employee1->user)->get("/ess/payroll/{$record2->id}/pdf");
            $response->assertStatus(403);
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssPayrollPayslipTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement `EssPayrollController` and `resources/views/ess/payroll/index.blade.php`**

- Transparent payslip presentation with side-by-side Gross Earnings vs. Statutory Deductions.
- Security watermark and YTD accumulator.
- Dispute inquiry modal creating a prioritized `EssHelpdeskTicket` linked to the specific payroll period.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssPayrollPayslipTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssPayrollController.php resources/views/ess/payroll/ routes/web.php tests/Feature/EssPayrollPayslipTest.php
git commit -m "feat(ess): implement payroll self-service, itemized statutory payslips, and dispute handling"
```

---

### Task 9: Profile, 201 Documents, COE Generator & HR Helpdesk Module

**Files:**
- Create: `app/Http/Controllers/Ess/EssProfileController.php`
- Create: `app/Http/Controllers/Ess/EssHelpdeskController.php`
- Create: `resources/views/ess/profile/index.blade.php`
- Create: `resources/views/ess/helpdesk/index.blade.php`
- Test: `tests/Feature/EssProfileAndHelpdeskTest.php`

**Interfaces:**
- Consumes: `hr_employees`, `hr_employee_documents`, `ess_helpdesk_tickets`, `ess_helpdesk_messages`.
- Produces:
  - Route `GET /ess/profile`: employee 201 details, editable contact info, document tracker.
  - Route `POST /ess/profile/update-contact`: self-service update of mobile number and address.
  - Route `POST /ess/profile/coe-request`: submits COE request with selected purpose.
  - Route `POST /ess/profile/upload-document`: uploads required 201 file.
  - Route `GET /ess/helpdesk`: ticket list and category selector.
  - Route `POST /ess/helpdesk/ticket`: opens new inquiry.
  - Route `POST /ess/helpdesk/ticket/{id}/reply`: sends message in open ticket thread.

- [ ] **Step 1: Write the failing test for Profile & Helpdesk**

```php
// tests/Feature/EssProfileAndHelpdeskTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;

class EssProfileAndHelpdeskTest extends TestCase
{
    public function test_profile_contact_update(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->post('/ess/profile/update-contact', [
            'mobile_number' => '09171234567',
            'address' => '123 Bonifacio St, Taguig City',
        ]);

        $response->assertRedirect();
        $employee->refresh();
        $this->assertEquals('09171234567', $employee->mobile_number);
    }

    public function test_helpdesk_ticket_creation_and_reply(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $response = $this->actingAs($employee->user)->post('/ess/helpdesk/ticket', [
            'category' => 'Government Benefits',
            'subject' => 'PhilHealth Member Data Record update',
            'message' => 'Please update my dependent list for newborn child.',
            'priority' => 'Normal',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ess_helpdesk_tickets', [
            'employee_id' => $employee->employee_id,
            'subject' => 'PhilHealth Member Data Record update',
            'status' => 'OPEN',
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EssProfileAndHelpdeskTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement Controllers and Views for Profile, COE, Documents, and Helpdesk**

- Create `EssProfileController` handling contact updates, COE request generation, and document uploads.
- Create `EssHelpdeskController` supporting category selection, threaded conversation views, and resolution status pills.
- Create Blade templates: `resources/views/ess/profile/index.blade.php` and `resources/views/ess/helpdesk/index.blade.php`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EssProfileAndHelpdeskTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Ess/EssProfileController.php app/Http/Controllers/Ess/EssHelpdeskController.php resources/views/ess/profile/ resources/views/ess/helpdesk/ routes/web.php tests/Feature/EssProfileAndHelpdeskTest.php
git commit -m "feat(ess): implement employee profile management, COE requests, document hub, and HR helpdesk"
```

---

### Task 10: End-to-End System Verification, Hierarchy Validation & Regression Suite

**Files:**
- Create: `tests/Feature/EssPortalIntegrationTest.php`
- Modify: `resources/views/components/sidebar.blade.php` (add "ESS Portal" launch link for quick switching)

**Interfaces:**
- Tests all 8 modules end-to-end with real database seeds, verifying DOLE compliance, supervisor assignment routing, and mobile responsiveness.

- [ ] **Step 1: Write complete end-to-end integration test suite**

```php
// tests/Feature/EssPortalIntegrationTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Hr\Employee;
use App\Models\User;

class EssPortalIntegrationTest extends TestCase
{
    public function test_full_ess_portal_navigation_for_employee(): void
    {
        $employee = Employee::whereNotNull('user_id')->first();
        $this->actingAs($employee->user);

        // 1. Dashboard
        $this->get('/ess/dashboard')->assertStatus(200);

        // 2. DTR
        $this->get('/ess/dtr')->assertStatus(200);

        // 3. Work Schedule
        $this->get('/ess/schedule')->assertStatus(200);

        // 4. Leaves
        $this->get('/ess/leave')->assertStatus(200);

        // 5. My Requests
        $this->get('/ess/requests')->assertStatus(200);

        // 6. Payroll
        $this->get('/ess/payroll')->assertStatus(200);

        // 7. Profile
        $this->get('/ess/profile')->assertStatus(200);

        // 8. Helpdesk
        $this->get('/ess/helpdesk')->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run all ESS tests and verify full suite passes**

Run: `php artisan test --filter=Ess`
Expected: PASS with 100% assertions green.

- [ ] **Step 3: Add seamless Portal Switcher in Sidebar & Header**

Add a quick "Employee Self-Service Portal" link in `resources/views/components/sidebar.blade.php` and the top header so authenticated employees/managers can easily jump between the ERP admin and their personal ESS portal.

- [ ] **Step 4: Final verification and commit**

```bash
git add resources/views/components/sidebar.blade.php tests/Feature/EssPortalIntegrationTest.php
git commit -m "feat(ess): complete end-to-end integration verification and portal navigation switcher"
```
