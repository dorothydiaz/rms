# Prompt Specification: Accelerated Shift Schedule Plotter

## 1. Core Intent
Transform the Weekly Shift Roster in `resources/views/hr/attendance/schedules.blade.php` from a slow, modal-dependent single-cell workflow into a high-speed, interactive matrix plotter inspired by `Reference/Schedule.html`. Planners must be able to:
- Instantly plot shifts into any empty cell via 1-click preset buttons (e.g., Opening, Mid Day, Late Day, Closing, Rest Day / Templates).
- Click or hover to remove or switch shift assignments without navigating away.
- Copy the previous week's schedule forward to the active week in 1 click.
- Batch fill an entire row or group (e.g. standard Mon-Sat shift with Sunday Rest Day).
- Save batch changes with visual confirmation and real-time state synchronization.
- Filter and search the roster quickly by staff name, branch, or department.

## 2. Explicit Constraints
- **Framework & Runtime:** Laravel 12 on PHP 8.2+, MySQL.
- **Frontend Architecture:** Laravel Blade templating with Phosphor Icons (`<i class="ph ph-...">`), custom CSS design tokens from `assets/css/styles.css`, and modular Vanilla JavaScript.
- **Security:** Strict CSRF validation on all asynchronous POST/PUT endpoints.
- **Data Persistence:** Persist to `employee_schedules` table with valid `shift_template_id`, `schedule_date`, `is_rest_day`, and `branch_id`.
- **Backward Compatibility:** Keep existing manual modal dialogs functional while introducing the direct-in-cell quick-plotters.

## 3. Excluded Scope (Non-Goals)
- No dependency on Google Apps Script / Google Sheets (`serverCode`, `PMC Attendance` references from the legacy reference file).
- No biometric physical sync modifications (punches display reference only if linked).
- No payroll recalculation triggers outside normal schedule saves.

## 4. Target Tech Stack
- **Backend:** PHP 8.2+, Laravel 12, Eloquent ORM (`EmployeeSchedule`, `ShiftTemplate`, `Employee`, `Branch`).
- **Endpoints:**
  - `POST /attendance/schedules/batch` (batch upsert schedule matrix payload).
  - `POST /attendance/schedules/copy-week` (copy schedule from prior week).
  - `POST /attendance/schedules/quick-set` (inline cell update).
- **Frontend:** Blade, Vanilla JS, CSS3 Flexbox/Grid, Phosphor Icons Web.
