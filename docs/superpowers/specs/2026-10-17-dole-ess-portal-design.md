# DOLE-Aligned Employee Self-Service (ESS) Portal — Architectural Specification
**System:** Stellar One Modular ERP System (RMS)  
**Module:** Employee Self-Service (ESS) Portal  
**Target Route Prefix:** `/ess`  
**Date:** 2026-10-17  
**Status:** Under Review  

---

## 1. Executive Summary & Objective

The **Stellar One Employee Self-Service (ESS) Portal** is a dedicated, responsive, enterprise-grade web application built within the Stellar One ERP system. Designed to feel like an **independent employee portal website** while maintaining seamless integration with the core ERP database, it empowers regular and supervisory employees across all companies, branches, and departments to independently access their employment details, monitor daily attendance, file statutory and company requests, inspect payslips with itemized Philippine statutory deductions, and manage HR communications.

All computations, entitlement rules, premiums, and policies are strictly anchored to **Philippine Labor Laws (Labor Code of the Philippines)**, applicable **Department of Labor and Employment (DOLE) Department Orders**, **Republic Acts**, and relevant social legislation (SSS, PhilHealth, Pag-IBIG, and BIR TRAIN Law).

---

## 2. Architecture & Design Principles

### 2.1 "Entirely Different Website" Look & Feel
The ESS portal operates with its own distinct presentation layer:
- **Dedicated Layout Shell (`resources/views/layouts/ess.blade.php`):** Unlike the administrative back-office ERP sidebar with multi-branch switchers and inventory controls, the ESS layout provides an employee-first experience.
- **Visual Aesthetic:** Clean white base canvas (`#ffffff` / `#f8fafc`), subtle glassmorphism (`backdrop-filter: blur(16px)`), crisp slate typography (`Outfit`, `Poppins`, `League Spartan`), restrained gradient accents (violet to fuchsia), and organized card containers.
- **Dual Form Factor Navigation:**
  - **Desktop:** Elegant sticky top navigation bar with quick links (Dashboard, DTR, Schedule, Leaves, Requests, Payslips, Documents, Helpdesk), user avatar, live notification bell, and quick action "+ Request" button.
  - **Mobile / Tablet:** Responsive floating bottom navigation bar with active indicators, slide-up action drawer for rapid request filing, and responsive data tables.
- **Zero Raw Overwrite Invariant:** Raw biometric attendance logs are immutable. Corrections, adjustments, and shift swaps are recorded as audit-trailed workflow requests that alter effective values only upon approved state transitions.

### 2.2 System Architecture Diagram
```
+-----------------------------------------------------------------------------------+
|                           EMPLOYEE SELF-SERVICE (ESS)                             |
|                                    (/ess/*)                                       |
+-----------------------------------------------------------------------------------+
|  [Dashboard]  [My DTR]  [Schedule]  [Leaves]  [Requests]  [Payslips]  [Helpdesk]  |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                            ESS CONTROLLER SUITE                                   |
|  - EssDashboardController          - EssAttendanceController                      |
|  - EssLeaveController              - EssOvertimeRequestController                 |
|  - EssPayrollController            - EssProfileDocumentController                 |
|  - EssHelpdeskController           - EssScheduleController                        |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                       DOLE & STATUTORY RULES ENGINE                               |
|                     (App\Services\DoleLaborStandardsService)                      |
|  - Minimum Wage & Cutoff Rules     - Overtime & Night Shift Differential (10%)    |
|  - Holiday Multipliers (200%/130%) - Statutory Leaves (SIL, Maternity, VAWC, etc.)|
|  - Tax / SSS / PhilHealth Tables   - Immutable Audit Logging                      |
+-----------------------------------------------------------------------------------+
                                         |
                                         v
+-----------------------------------------------------------------------------------+
|                       SHARED STELLAR ONE ERP DATABASE                             |
|  - hr_employees                    - hr_attendance_records                        |
|  - hr_leave_requests               - payroll_records                              |
|  - approval_requests               - hr_departments / hr_branches / hr_companies  |
+-----------------------------------------------------------------------------------+
```

---

## 3. DOLE Philippine Labor Standards Engine

The backend implements `App\Services\DoleLaborStandardsService` to govern all computations dynamically without hardcoded magic numbers.

### 3.1 Overtime & Premium Calculation Matrix
| Work Category | Regular Hourly Rate Multiplier | Overtime Multiplier (>8 hrs) | Night Shift Diff (10:00 PM – 6:00 AM) |
| :--- | :--- | :--- | :--- |
| **Ordinary Working Day** | 100% | 125% (1.25x) | Rate × 1.10 (+10%) |
| **Scheduled Rest Day (RD)** | 130% (1.30x) | 169% (1.30 × 1.30 = 1.69x) | (Rate × 1.30) × 1.10 |
| **Special Non-Working Holiday (SH)** | 130% (1.30x) | 169% (1.69x) | (Rate × 1.30) × 1.10 |
| **Special Holiday on Rest Day** | 150% (1.50x) | 195% (1.50 × 1.30 = 1.95x) | (Rate × 1.50) × 1.10 |
| **Regular Holiday (RH)** | 200% (2.00x) | 260% (2.00 × 1.30 = 2.60x) | (Rate × 2.00) × 1.10 |
| **Regular Holiday on Rest Day** | 260% (2.60x) | 338% (2.60 × 1.30 = 3.38x) | (Rate × 2.60) × 1.10 |
| **Double Holiday (Rare)** | 300% (3.00x) | 390% (3.00 × 1.30 = 3.90x) | (Rate × 3.00) × 1.10 |

### 3.2 Statutory Leave Entitlements
1. **Service Incentive Leave (SIL - Art. 95, Labor Code):**
   - Eligibility: Every employee who has rendered at least 1 year of service.
   - Entitlement: At least 5 days with pay per year. Commutable to cash if unused at year-end.
2. **Maternity Leave (RA 11210 / 105-Day Expanded Maternity Leave Act):**
   - Eligibility: Female employee regardless of civil status or child legitimacy.
   - Entitlement: 105 days paid for live birth (+15 additional days for qualified solo parent), 60 days paid for miscarriage/emergency termination.
3. **Paternity Leave (RA 8187):**
   - Eligibility: Married male employee cohabiting with spouse who delivers a child or suffers miscarriage.
   - Entitlement: 7 working days with full pay for the first 4 deliveries.
4. **Solo Parent Leave (RA 8972 / RA 11861):**
   - Eligibility: Solo parent employee with at least 6 months of service presenting a valid Solo Parent ID.
   - Entitlement: 7 working days with pay annually.
5. **VAWC Leave (RA 9262 / Anti-Violence Against Women and Their Children):**
   - Eligibility: Female employee victim of physical, sexual, or psychological violence.
   - Entitlement: 10 days paid leave upon certification from Punong Barangay/Kagawad or Court.
6. **Special Leave for Women (RA 9710 / Magna Carta of Women):**
   - Eligibility: Female employee who has rendered continuous aggregate service of 6 months for the last 12 months undergoing surgery due to gynecological disorders.
   - Entitlement: Up to 2 months full pay based on gross monthly compensation.
7. **Company Discretionary Leaves:**
   - Vacation Leave (VL), Sick Leave (SL), Emergency Leave (EL), Bereavement Leave.

### 3.3 Statutory Contributions & Deductions
- **SSS (Republic Act No. 11199):** Current contribution schedule applying Regular Social Security, Employee Compensation (EC), and Mandatory Provident Fund (WISP) brackets up to the maximum Monthly Salary Credit (MSC).
- **PhilHealth (Universal Health Care Act RA 11223):** 5.0% premium rate with monthly salary floor and ceiling, split 50/50 between employer and employee.
- **Pag-IBIG / HDMF (Circular No. 460):** Maximum mandatory employee share of ₱200 (2% of ₱10,000 max MSC) with matching ₱200 employer counterpart.
- **BIR Withholding Tax (TRAIN Law / RA 10963):** Graduated withholding tax tables; compensation ≤ ₱20,833.33/month is exempt from income tax. Form 2316 annualization.

---

## 4. Module & Screen Specifications

### 4.1 ESS Portal Shell & Responsive Layout
- **Path:** `resources/views/layouts/ess.blade.php`
- **Header:**
  - Stellar One ESS Logo & Portal Badge
  - Navigation links with active pills: Dashboard, Attendance/DTR, Schedule, Leaves, Requests, Payslips, Documents, Helpdesk
  - Current Date & Active Shift ticker badge
  - Notifications Center (dropdown with unread badge)
  - Quick Action Button: `+ New Request` (triggers modal: Leave, Overtime, Correction, OB, Helpdesk)
  - Profile Menu: Employee Name, Position, Branch, Switch to Admin ERP (if user has admin role), Logout
- **Mobile Bottom Navigation Bar:**
  - 5 core icon tabs: `Dashboard`, `DTR`, `+ Request` (accent center button), `Payslips`, `Profile`

### 4.2 Module 1: Employee Dashboard (`/ess/dashboard`)
- **Personal Hero Card:** Photo/Initials, Full Name, Employee Code (`EMP-YYYY-XXX`), Department, Position, Branch, Company Affiliation, Immediate Supervisor pill.
- **Shift & Timekeeping Card:**
  - Today's schedule (e.g. `08:00 AM - 05:00 PM (Regular Day)`).
  - Live clock with today's punch timeline: In 1, Out 1 (Lunch), In 2, Out 2. Current status badge: `ON DUTY`, `ON BREAK`, `COMPLETED`, `REST DAY`.
- **4 Key Stat Cards:**
  1. *Leave Credits Remaining:* (e.g. `8.5 Days Available`).
  2. *Period Hours Rendered:* (e.g. `76.5 Regular / 4.0 OT`).
  3. *Latest Net Pay:* (masked with toggle eye icon `₱ ••••••`).
  4. *Active Requests:* (count of pending items).
- **Recent Requests & Timeline:** Status tracker showing last 5 submitted requests with approver steps.
- **Announcements & Holiday Ticker:** Upcoming Philippine regular and special non-working holidays with DOLE premium indicators.

### 4.3 Module 2: Attendance & Daily Time Record (`/ess/dtr`)
- **Filter Bar:** Pay Period selector (1st Half / 2nd Half of month), custom date range picker, search.
- **Interactive DTR Table:**
  - Date, Day, Scheduled Shift, Time-In, Time-Out, Regular Hours, Overtime, Night Differential, Undertime (mins), Late (mins), Status Badge (`Present`, `Late`, `Undertime`, `Absent`, `Rest Day`, `Holiday`).
- **Correction Request Trigger:** Clicking any row or exception badge opens the **Attendance Correction Modal**:
  - Pre-fills date and original punch values.
  - Requested Time-In / Time-Out fields.
  - Reason category: `Biometric Machine Error`, `Official Business / Field Work`, `Forgot to Punch`, `Power Outage`.
  - Explanation textarea + attachment file upload.
  - Submission creates pending approval request; does NOT mutate original attendance record directly.
- **Export Actions:** Download PDF DTR (formatted according to DOLE inspection standards with employee signature block) and CSV export.

### 4.4 Module 3: Work Schedule (`/ess/schedule`)
- **Calendar & Table Views:** Monthly calendar grid with color-coded shift blocks, scheduled rest days (`RD`), and holidays (`RH`/`SH`).
- **Shift Details Popover:** Working hours, meal break duration, work location/branch.
- **Shift Swap / Schedule Adjustment Request:**
  - Form to request swap with a co-employee or adjust assigned shift template for a specified date range.
  - Validates department and branch matching.

### 4.5 Module 4: DOLE Statutory & Company Leaves (`/ess/leave`)
- **Leave Balance Overview Cards:**
  - Service Incentive Leave (SIL): Accrued, Used, Remaining.
  - Vacation Leave (VL), Sick Leave (SL), Emergency Leave (EL).
  - Special Leaves: Maternity, Paternity, Solo Parent, VAWC with statutory eligibility badges.
- **File Leave Request Modal:**
  - Leave Type dropdown (dynamically loads eligible types based on employee tenure, gender, and civil status).
  - Start Date, End Date, Duration Type: `Full Day (8 hrs)`, `Half Day - Morning (4 hrs)`, `Half Day - Afternoon (4 hrs)`.
  - Calculated total days deducted.
  - Reason textarea + Medical Certificate / Supporting Document upload (mandatory for Sick Leave > 2 days or VAWC/Solo Parent).
- **Leave History & Approval Ledger:** Full audit log of all leaves filed, dates, approver remarks, and balance adjustments.

### 4.6 Module 5: Centralized "My Requests" Center (`/ess/requests`)
- **Multi-Type Request Management:**
  - Overtime (Pre-shift OT, Post-shift OT, Rest Day OT).
  - Official Business (OB) / Fieldwork pass.
  - Attendance Correction.
  - Shift Swap / Schedule Adjustment.
  - Undertime / Early Departure notification.
  - Profile Update Request (change of address, contact, emergency contact).
- **Tabbed Filter:** `All Requests`, `Pending Approval`, `Approved`, `Rejected`, `Cancelled`.
- **Live Approval Chain Visualizer:** Visual step diagram showing Level 1 (Supervisor), Level 2 (Department Head / Manager), Level 3 (HR), current active node, timestamps, and approver remarks.
- **Cancellation & Resubmission:** Ability to cancel requests that are still `PENDING`, or edit and resubmit `REJECTED` requests.

### 4.7 Module 6: Payroll & Payslips (`/ess/payroll`)
- **Cutoff / Period Selector:** Browse past finalized pay runs.
- **Itemized DOLE Payslip Display:**
  - **Earnings:** Basic Pay, Overtime Pay, Night Shift Differential Pay, Rest Day & Holiday Premiums, De Minimis & Taxable Allowances, 13th Month Pay Accrual.
  - **Deductions:** Late & Undertime deductions, SSS Contribution, PhilHealth Contribution, Pag-IBIG Contribution, Withholding Tax, Company Loans / Cash Advances.
  - **Net Pay Banner:** Distinct highlighted Net Take-Home Pay.
- **Downloadable PDF Payslip:** Official payslip PDF generation with security watermark.
- **Confidential Payroll Inquiry Button:** Modal to submit question directly to HR/Payroll officer regarding calculation discrepancies.

### 4.8 Module 7: Employee Profile & 201 Documents (`/ess/profile`, `/ess/documents`)
- **Profile View:** Personal information, Government Statutory IDs (SSS, PhilHealth, Pag-IBIG, TIN, PhilSys), Job details, Work location, Immediate supervisor.
- **Update Contact Info:** Self-service edit of mobile number, address, and emergency contacts.
- **Document Hub:** Upload and view 201 file documents (Valid IDs, NBI Clearance, Medical Exam, Contracts).
- **COE Request Generator:** Form to request Certificate of Employment (COE) with selection of purpose (Loan application, Visa application, Employment reference).

### 4.9 Module 8: HR Helpdesk (`/ess/helpdesk`)
- **Ticket Submission:** Category dropdown (`Payroll & Wages`, `Attendance & Biometrics`, `Leave Credits`, `Government Benefits`, `Company Policy / General`).
- **Ticket Thread:** Secure message exchange between employee and HR support team, file attachments, and resolution status (`Open`, `In Progress`, `Resolved`, `Closed`).

---

## 5. Security, Authorization & Data Isolation

1. **Authentication:** Uses the existing `User` model linked to `hr_employees`. If an employee does not have a user account, automatic provisioning with secure temporary credentials is provided.
2. **Strict Identity Isolation Middleware (`EssEmployeeMiddleware`):**
   - Automatically resolves `auth()->user()->employee`.
   - If user has no employee record, denies access with clear instructional message.
   - Every database query for attendance, payroll, leaves, and requests is strictly scoped by `$employee->employee_id`. It is architecturally impossible to view or mutate another employee's records.
3. **Immutable Biometrics:**
   - Raw logs in `hr_attendance_records` retain original `time_in` and `time_out`.
   - Approved corrections update `corrected_time_in`, `corrected_time_out`, `correction_status = 'Approved'`, with approver ID and timestamp preserved in `hr_attendance_corrections`.
4. **Data Privacy Act (RA 10173):**
   - Sensitive financial and medical information is masked and strictly accessible only by the subject employee and authorized HR executives.

---

## 6. Implementation Strategy & Deliverables

### File Architecture
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Ess/
│   │       ├── EssDashboardController.php
│   │       ├── EssAttendanceController.php
│   │       ├── EssScheduleController.php
│   │       ├── EssLeaveController.php
│   │       ├── EssRequestController.php
│   │       ├── EssPayrollController.php
│   │       ├── EssProfileController.php
│   │       └── EssHelpdeskController.php
│   └── Middleware/
│       └── EssEmployeeMiddleware.php
├── Services/
│   └── DoleLaborStandardsService.php
├── Models/
│   └── Ess/
│       ├── EssRequest.php
│       └── EssHelpdeskTicket.php
resources/
├── views/
│   ├── layouts/
│   │   └── ess.blade.php
│   └── ess/
│       ├── dashboard.blade.php
│       ├── attendance/
│       │   └── dtr.blade.php
│       ├── schedule/
│       │   └── index.blade.php
│       ├── leave/
│       │   └── index.blade.php
│       ├── requests/
│       │   └── index.blade.php
│       ├── payroll/
│       │   └── index.blade.php
│       ├── profile/
│       │   └── index.blade.php
│       └── helpdesk/
│           └── index.blade.php
public/
└── assets/
    └── css/
        └── ess-portal.css
routes/
└── web.php (or dedicated routes/ess.php included in bootstrap)
tests/
└── Feature/
    └── EssPortalIntegrationTest.php
```

---

## 7. Verification & Automated Testing Plan

1. **Hierarchy & Identity Verification:**
   - Test login with regular employee (e.g. `staff`, `cashier`, `kitchen`). Verify redirect to ESS dashboard and correct profile data.
2. **Attendance & DTR Calculation Test:**
   - Verify regular hours, night shift differential (10:00 PM – 6:00 AM), overtime multiplier (125%), and rest day multiplier (130%).
   - Verify that attendance correction creates an audit trail without overwriting raw punches.
3. **DOLE Statutory Leave Test:**
   - Test filing Service Incentive Leave (SIL), Maternity, Paternity, Solo Parent, and VAWC leaves. Verify credit validation and approval queue assignment.
4. **Payroll & Itemized Payslip Test:**
   - Verify payslip calculations match DOLE standards, with transparent deductions for SSS, PhilHealth, Pag-IBIG, and withholding tax.
5. **Security & Boundary Test:**
   - Attempt to access another employee's request or payslip using direct ID manipulation; verify HTTP 403 Forbidden.
   - Verify mobile viewport responsiveness across all screens.
