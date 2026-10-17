# DOLE-Aligned Employee Self-Service (ESS) Portal Specification

- **System:** Stellar One Modular ERP (Restaurant Management System)
- **Status:** APPROVED DESIGN SPEC
- **Author:** Antigravity AEC Council & Full Stack Architecture
- **Date:** 2026-10-17
- **Target Release:** Phase 1 (Core ESS)

---

## 1. Executive Summary & Core Intent

The **Employee Self-Service (ESS) Portal** provides all employees across companies, branches, and departments with a centralized, mobile-responsive, and secure portal to:
1. Review personal profiles, employment details, and emergency contacts.
2. Monitor real-time daily time records (DTR), clock punch history, tardiness, undertime, overtime, night shift differential, rest day, and holiday classifications.
3. Access active work schedules, shift templates, and request shift swaps or schedule adjustments.
4. Manage leave entitlements under Philippine Labor Standards (Service Incentive Leave - SIL, Maternity, Paternity, Solo Parent, VAWC, Special Leave for Women, Vacation/Sick Leave) with a live credit balance ledger.
5. Submit, track, and manage employee requests (Overtime, Official Business, Attendance Corrections, Schedule Swaps, Profile Updates) with transparent multi-level approval history and audit logs.
6. Inspect confidential payslips with comprehensive gross-to-net breakdowns, statutory contributions (SSS, PhilHealth, Pag-IBIG), withholding tax, and accrued 13th-month pay.

---

## 2. DOLE Philippine Labor Standards Engine (`DoleComplianceService`)

The core business logic complies strictly with the Labor Code of the Philippines (Presidential Decree No. 442, as amended) and relevant Republic Acts:

### 2.1 Overtime & Premium Pay (Articles 87, 91–94)
- **Ordinary Work Day OT:** +25% on the regular hourly wage for hours rendered beyond 8 hours.
- **Rest Day / Special Non-Working Day (First 8 Hours):** +30% premium on daily wage (Rate: 130%).
- **Rest Day / Special Non-Working Day OT:** Additional +30% on the hourly rest day rate (130% × 1.30 = 169%).
- **Special Day Falling on a Scheduled Rest Day (First 8 Hours):** +50% premium (Rate: 150%).
- **Special Day + Rest Day OT:** 150% × 1.30 = 195%.
- **Regular Holiday (First 8 Hours):** +100% premium (total 200% daily rate).
- **Regular Holiday OT:** 200% × 1.30 = 260%.
- **Regular Holiday Falling on a Scheduled Rest Day (First 8 Hours):** Total 260% daily wage.
- **Regular Holiday + Rest Day OT:** 260% × 1.30 = 338%.

### 2.2 Night Shift Differential (Article 86)
- Additional 10% of regular hourly wage for each hour of work performed between **10:00 PM and 6:00 AM**.

### 2.3 Statutory Leaves & Entitlements
- **Service Incentive Leave (Art. 95):** 5 days paid leave for employees with at least 1 year of continuous service.
- **Maternity Leave (RA 11210):** 105 days with full pay for female employees regardless of civil status, with 15 additional days for solo parents.
- **Paternity Leave (RA 8187):** 7 days paid leave for married male employees for up to 4 deliveries.
- **Solo Parent Leave (RA 8972 as amended by RA 11861):** 7 days paid parental leave for qualified solo parents with at least 1 year of service.
- **Special Leave for Women (RA 9710 Magna Carta of Women):** Up to 2 months with full pay following surgery caused by gynecological disorders.
- **VAWC Leave (RA 9262):** 10 days paid leave for victims of violence against women and their children.
- **Company Discretionary Leaves:** Vacation Leave (VL), Sick Leave (SL), Emergency Leave (EL), Bereavement Leave.

---

## 3. Architecture & Controller Topology

### 3.1 Namespace & Route Structure
All ESS endpoints are mounted under `/ess`:
```
GET   /ess/dashboard                     -> EssDashboardController@index
GET   /ess/profile                       -> EssProfileController@show
POST  /ess/profile/update-contact        -> EssProfileController@updateContact
POST  /ess/profile/emergency-contact     -> EssProfileController@storeEmergencyContact
DELETE/ess/profile/emergency-contact/{id}-> EssProfileController@destroyEmergencyContact

GET   /ess/attendance                    -> EssAttendanceController@index
GET   /ess/attendance/dtr-export         -> EssAttendanceController@exportDtrPdf
POST  /ess/attendance/correction-request -> EssAttendanceController@storeCorrectionRequest

GET   /ess/schedule                      -> EssScheduleController@index
POST  /ess/schedule/adjustment-request   -> EssScheduleController@storeAdjustmentRequest

GET   /ess/leave                         -> EssLeaveController@index
POST  /ess/leave/request                 -> EssLeaveController@storeLeaveRequest
POST  /ess/leave/cancel/{id}             -> EssLeaveController@cancelLeaveRequest

GET   /ess/requests                      -> EssRequestController@index
POST  /ess/requests/overtime             -> EssRequestController@storeOvertimeRequest
POST  /ess/requests/official-business    -> EssRequestController@storeOfficialBusinessRequest
POST  /ess/requests/cancel/{id}          -> EssRequestController@cancelRequest

GET   /ess/payroll                       -> EssPayrollController@index
GET   /ess/payroll/payslip/{id}          -> EssPayrollController@showPayslip
GET   /ess/payroll/payslip/{id}/pdf      -> EssPayrollController@downloadPayslipPdf
```

### 3.2 Security, Scoping & Privacy (Data Privacy Act of 2012)
- All controllers query the logged-in user's linked employee record via `Auth::user()->employee`.
- If a user has no linked employee, they are redirected with a friendly setup notice.
- ID-based tampering is strictly blocked: Employees can only view and mutate records where `employee_id === Auth::user()->employee->employee_id`.

---

## 4. Database Schema & Data Models

### 4.1 Universal ESS Request System (`ess_requests`)
Table: `ess_requests`
- `id` (bigint unsigned, primary key)
- `request_number` (string 50, unique: e.g. `REQ-2026-0001`)
- `employee_id` (string 50, indexed)
- `request_type` (enum: `OVERTIME`, `OFFICIAL_BUSINESS`, `ATTENDANCE_CORRECTION`, `SHIFT_SWAP`, `SCHEDULE_ADJUSTMENT`, `UNDERTIME`, `PROFILE_UPDATE`, `COE_REQUEST`)
- `status` (enum: `DRAFT`, `SUBMITTED`, `PENDING_APPROVAL`, `APPROVED`, `REJECTED`, `CANCELLED`)
- `start_date` (date)
- `end_date` (date, nullable)
- `start_time` (time, nullable)
- `end_time` (time, nullable)
- `total_hours` (decimal 8,2, nullable)
- `reason` (text)
- `metadata` (json, nullable - for extra structured fields like punch correction in/out, target swap shift, etc.)
- `attachment_path` (string 255, nullable)
- `current_approver_id` (bigint unsigned, nullable -> `users.id`)
- `approved_by` (bigint unsigned, nullable -> `users.id`)
- `approved_at` (timestamp, nullable)
- `rejection_reason` (text, nullable)
- `cancelled_at` (timestamp, nullable)
- `timestamps` (`created_at`, `updated_at`)

### 4.2 ESS Request Audit & Action History (`ess_request_logs`)
- `id` (bigint unsigned, primary key)
- `ess_request_id` (foreign key)
- `action` (string: `SUBMITTED`, `FORWARDED`, `APPROVED`, `REJECTED`, `CANCELLED`)
- `acted_by` (bigint unsigned -> `users.id`)
- `remarks` (text, nullable)
- `created_at` (timestamp)

---

## 5. UI/UX & Component System

Follows Stellar One's existing aesthetic tokens:
- **Typography:** Outfit / League Spartan / Poppins
- **Color Tokens:**
  - Primary: `#1e3a8a` / `#2563eb` (Stellar Royal Blue)
  - Surface: `#ffffff` / subtle glassmorphism (`backdrop-filter: blur(12px)`)
  - Accent / Status: Emerald `#10b981` (Approved), Amber `#f59e0b` (Pending), Crimson `#ef4444` (Rejected), Violet `#8b5cf6` (Holiday/Premium)
- **Responsive Layout:**
  - Sidebar integration with dedicated `Employee Self-Service` rail and sub-tabs.
  - Floating Quick-Action Modal on Mobile.
  - Printable DTR and Payslip view using standard `@media print`.

---

## 6. Verification & Automated Test Suite

Test file: `tests/Feature/EssPortalModuleTest.php`
- Scenario 1: Employee access to `/ess/dashboard` renders personalized profile, supervisor, and shifts.
- Scenario 2: Employee cannot view another employee's payslip or DTR (IDOR security test).
- Scenario 3: DTR calculation accurately computes Overtime (+25%), Night Diff (10%), and Holiday Premiums via `DoleComplianceService`.
- Scenario 4: Leave request validation enforces balance checks and statutory eligibility.
- Scenario 5: ESS Request submission logs into `ess_requests` with correct status transitions and supervisor routing.
