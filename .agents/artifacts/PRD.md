# Product Requirements Document (PRD): Accelerated Shift Schedule Planner

## 1. Executive Summary
Provide an industrial-grade, responsive weekly shift roster matrix in `resources/views/hr/attendance/schedules.blade.php`, matching the speed and convenience demonstrated in `Reference/Schedule.html`. This eliminates the bottleneck of opening repetitive popups for every single day and employee.

## 2. Feature Slicing: MVP vs Phase 2

### MVP (In Scope for this execution)
1. **Interactive In-Cell Quick Plotter:**
   - Empty grid cells present 1-click preset shift buttons (`O`, `MD`, `LD`, `C`, etc. matching configured shift templates) + `OFF` (Rest Day).
   - Instant assignment with AJAX background sync and optimistic UI rendering.
2. **Assigned Shift Badge with Fast Unassign:**
   - Active cells display the template code, time range, and colored styling.
   - Hovering displays a quick clear (`✕`) button to wipe the schedule without opening a modal.
3. **Copy Previous Week Schedule:**
   - Top-bar button "Copy Prev Week" duplicates all schedules from `weekStart - 7 days` to the active week in 1 click.
4. **Row-Level Quick Fill:**
   - Dropdown or quick action on each employee row: "Apply Mon-Sat [Shift], Sun Off" or "Fill All Days with Shift".
5. **Fast Filtering & Live Search Toolbar:**
   - Client-side search by employee name, ID, branch, or position category without reloading the browser.
6. **Unified Shift Master Settings Modal:**
   - Inspect and configure shift start/end times directly from the schedule planner view.

### Phase 2 (Future Scope)
- Drag-and-drop shift assignment across cells.
- Biometric timecard discrepancy overlay (comparing scheduled shift vs actual biometric punch in/out).
- Export roster to Excel / PDF roster matrix.

---

## 3. Gherkin Acceptance Criteria

### Scenario 1: Fast One-Click Shift Assignment in Empty Cell
```gherkin
Scenario: Scheduler clicks a shift preset button on an empty cell
  Given the Weekly Shift Roster is loaded for the week of "2026-09-28"
  And employee ID 14 has no schedule on "2026-09-29"
  When the user clicks the "O" (Opening) preset button on the "2026-09-29" cell for employee 14
  Then an asynchronous request is dispatched to "/attendance/schedules/quick-assign"
  And the cell immediately transitions to an active scheduled card with Opening badge and times
  And an entry in "employee_schedules" is created with employee_id=14, schedule_date="2026-09-29", is_rest_day=false
```

### Scenario 2: Fast Rest Day Assignment
```gherkin
Scenario: Scheduler marks a day as Rest Day
  Given employee ID 14 on date "2026-10-04" (Sunday) has no schedule
  When the user clicks the "OFF" (Rest Day) preset button
  Then the cell transitions to a "Rest Day" badge
  And "employee_schedules" records is_rest_day=true and shift_template_id=null
```

### Scenario 3: Quick Clear / Unassign Shift
```gherkin
Scenario: Scheduler clears an existing shift
  Given employee ID 14 has an assigned shift on "2026-09-29"
  When the user hovers over the card and clicks the "✕" (Clear) button
  Then an asynchronous request is dispatched with clear=true
  And the record is removed or unassigned from "employee_schedules"
  And the cell returns to the empty state with quick preset buttons
```

### Scenario 4: Copy Previous Week Schedules
```gherkin
Scenario: Scheduler copies previous week roster into the current week
  Given the previous week "2026-09-21" to "2026-09-27" has 35 assigned shifts
  And the current week "2026-09-28" to "2026-10-04" has unassigned days
  When the user clicks "Copy Prev Week" and confirms
  Then the system copies the corresponding shift templates and rest days from the previous week
  And a toast notification confirms "Successfully copied X schedules from previous week"
  And the roster UI refreshes without full page disruption
```

### Scenario 5: Row-Level Quick Fill
```gherkin
Scenario: Scheduler fills an entire week for an employee in one click
  Given employee ID 14 has an empty or partial week
  When the user opens the employee row quick action menu and selects "Fill Mon-Sat Opening, Sun Off"
  Then 7 schedule records are upserted for that employee across the week
  And the entire row displays the updated shifts immediately
```

### Scenario 6: Live Employee Search Filter
```gherkin
Scenario: Planner searches for an employee in the roster
  Given the matrix contains 40 employee rows
  When the user types "Maria" in the quick search input
  Then only rows matching "Maria" remain visible
  And the table header and totals dynamically reflect the filtered count
```
