# DOLE-Aligned Employee Self-Service (ESS) Portal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a complete, DOLE-aligned, enterprise-grade Employee Self-Service (ESS) Portal for Stellar One Modular ERP System supporting profile management, DTR/attendance tracking, work schedule, statutory leave filing, universal request tracking, and confidential payslip inspection.

**Architecture:** A modular domain architecture under `/ess` powered by a dedicated `DoleComplianceService` (Philippine Labor Code Art. 86 Night Shift Diff, Art. 87 Overtime, Art. 91-94 Rest Days & Holidays, Art. 95 SIL & Statutory Leaves) and a universal `ess_requests` transaction lifecycle table, completely integrated into Stellar One's Blade component layout and sidebar rail.

**Tech Stack:** PHP 8.2+, Laravel 12.69.2, MySQL/MariaDB, Blade templating with Phosphor Icons & Vanilla CSS tokens, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-17-dole-ess-portal-design.md`

## Global Constraints
- Target platform: Windows / XAMPP, PHP 8.2+, MySQL / MariaDB (`rms_db`).
- All ESS endpoints must be mounted under the `/ess` URL prefix.
- All requests must strictly scope to `Auth::user()->employee->employee_id` with zero IDOR exposure.
- Preserves all existing models (`Employee`, `AttendanceRecord`, `LeaveRequest`, `PayrollRecord`, `Branch`, `Company`).
- Follows existing design aesthetics (League Spartan / Outfit / Poppins typography, Phosphor icons, clean glassmorphism cards).

## Review Focus
1. **Unlinked Users:** When a user logs in who has no linked `Employee` record, redirect gracefully with a helpful setup prompt rather than fatal null pointer errors.
2. **IDOR Attacks:** Attempting to view or cancel another employee's request or payslip by tampering with request IDs in URL/POST payload must return HTTP 403 Forbidden.
3. **DOLE Night Shift Differential Boundary:** Work rendered spanning across 10:00 PM to 6:00 AM must accurately calculate only the hours falling within that specific 8-hour window.
4. **Leave Balance Overdraft:** An employee cannot submit leave requests that exceed their available balance unless marked as Leave Without Pay (LWOP).
5. **Self-Approval Prevention:** An employee cannot approve their own submitted request even if they hold an approval role.

---

### Task 1: Database Migration & Models for Universal ESS Requests

**Files:**
- Create: `database/migrations/2026_10_17_000001_create_ess_requests_table.php`
- Create: `app/Models/Hr/EssRequest.php`
- Create: `app/Models/Hr/EssRequestLog.php`
- Test: `tests/Feature/EssRequestMigrationTest.php`

**Interfaces:**
- Consumes: `users`, `hr_employees`
- Produces: `EssRequest` model with scopes `forEmployee($employeeId)`, statuses (`DRAFT`, `SUBMITTED`, `PENDING_APPROVAL`, `APPROVED`, `REJECTED`, `CANCELLED`), and relationship `logs()`

- [ ] **Step 1: Write the failing test for ESS Request tables & models**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Create migration `database/migrations/2026_10_17_000001_create_ess_requests_table.php`**
- [ ] **Step 4: Create Eloquent models `app/Models/Hr/EssRequest.php` and `app/Models/Hr/EssRequestLog.php`**
- [ ] **Step 5: Run migration and verify test passes**
- [ ] **Step 6: Commit**

---

### Task 2: DOLE Labor Standards & Compliance Service (`DoleComplianceService`)

**Files:**
- Create: `app/Services/Hr/DoleComplianceService.php`
- Create: `tests/Unit/DoleComplianceServiceTest.php`

**Interfaces:**
- Produces:
  - `calculateOvertimeRate(string $dayType, bool $isRestDay, float $hourlyRate): float`
  - `calculateNightDiffHours(string|Carbon $timeIn, string|Carbon $timeOut): float`
  - `calculateHolidayPay(string $holidayType, bool $isRestDay, float $dailyRate, float $hoursWorked): array`
  - `checkStatutoryLeaveEligibility(string $leaveCode, Employee $employee): array`

- [ ] **Step 1: Write the failing unit tests for DOLE rates and calculations in `tests/Unit/DoleComplianceServiceTest.php`**
- [ ] **Step 2: Run test to verify it fails**
- [ ] **Step 3: Implement `app/Services/Hr/DoleComplianceService.php` covering Labor Code Art. 86, 87, 91-94, and 95**
- [ ] **Step 4: Run unit tests to verify 100% pass rate**
- [ ] **Step 5: Commit**

---

### Task 3: Backend ESS Controllers & Route Topology

**Files:**
- Create: `app/Http/Controllers/Ess/EssDashboardController.php`
- Create: `app/Http/Controllers/Ess/EssProfileController.php`
- Create: `app/Http/Controllers/Ess/EssAttendanceController.php`
- Create: `app/Http/Controllers/Ess/EssScheduleController.php`
- Create: `app/Http/Controllers/Ess/EssLeaveController.php`
- Create: `app/Http/Controllers/Ess/EssRequestController.php`
- Create: `app/Http/Controllers/Ess/EssPayrollController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/EssRoutingAndSecurityTest.php`

**Interfaces:**
- Consumes: `DoleComplianceService`, `EssRequest`, `Employee`, `AttendanceRecord`, `LeaveRequest`, `PayrollRecord`
- Produces: ESS Controller endpoints returning structured view payloads with active employee context

- [ ] **Step 1: Write failing security & routing feature test `tests/Feature/EssRoutingAndSecurityTest.php`**
- [ ] **Step 2: Run test to verify it fails (routes not defined)**
- [ ] **Step 3: Implement ESS Controllers (`EssDashboardController`, `EssProfileController`, `EssAttendanceController`, `EssScheduleController`, `EssLeaveController`, `EssRequestController`, `EssPayrollController`)**
- [ ] **Step 4: Register `/ess/*` routes in `routes/web.php`**
- [ ] **Step 5: Run tests to verify all routes load and IDOR protections pass**
- [ ] **Step 6: Commit**

---

### Task 4: ESS UI Views & Sidebar Navigation Rail

**Files:**
- Modify: `resources/views/components/sidebar.blade.php`
- Create: `resources/views/ess/dashboard.blade.php`
- Create: `resources/views/ess/profile.blade.php`
- Create: `resources/views/ess/attendance.blade.php`
- Create: `resources/views/ess/schedule.blade.php`
- Create: `resources/views/ess/leave.blade.php`
- Create: `resources/views/ess/requests.blade.php`
- Create: `resources/views/ess/payroll.blade.php`
- Create: `resources/views/ess/payslip-modal.blade.php`

**Interfaces:**
- Consumes: Blade template layout `resources/views/layouts/app.blade.php`
- Produces: Interactive, responsive UI with KPI cards, modals for request filing, printable DTR, and payslip viewer

- [ ] **Step 1: Add dedicated "Employee Self-Service" module section to `resources/views/components/sidebar.blade.php`**
- [ ] **Step 2: Implement `dashboard.blade.php` with personalized employee hero, shift status, punch card, and quick actions**
- [ ] **Step 3: Implement `profile.blade.php` with contact update modal, emergency contacts, and document checklist**
- [ ] **Step 4: Implement `attendance.blade.php` with DTR month/week view, DOLE premium badges, and PDF print stylesheet**
- [ ] **Step 5: Implement `schedule.blade.php` with shift calendar and swap request trigger**
- [ ] **Step 6: Implement `leave.blade.php` with statutory leave cards (SIL, Maternity, Paternity, Solo Parent), credit balances, and filing modal**
- [ ] **Step 7: Implement `requests.blade.php` with filterable tabs (`All`, `Pending`, `Approved`, `Rejected`), status timeline, and cancel actions**
- [ ] **Step 8: Implement `payroll.blade.php` & `payslip-modal.blade.php` with itemized DOLE earnings, statutory deductions, and YTD summaries**
- [ ] **Step 9: Commit**

---

### Task 5: End-to-End Automated Verification & Audit

**Files:**
- Create: `tests/Feature/EssPortalModuleTest.php`

**Interfaces:**
- Tests all 5 critical user scenarios:
  1. Employee dashboard rendering and live shift computation
  2. IDOR security barriers (prevent cross-employee access)
  3. Overtime, Night Differential, and Holiday rate calculations
  4. Leave filing and balance validation
  5. Universal request submission, supervisor assignment, and cancellation

- [ ] **Step 1: Write comprehensive test cases in `tests/Feature/EssPortalModuleTest.php`**
- [ ] **Step 2: Run test suite via `php artisan test --filter=EssPortalModuleTest`**
- [ ] **Step 3: Verify all tests pass with zero regressions across the RMS test suite**
- [ ] **Step 4: Commit**
