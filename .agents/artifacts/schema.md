# API & Data Contracts: Accelerated Shift Schedule Planner

## 1. Database Schema

### Table: `employee_schedules`
| Column | Type | Nullable | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | Primary Key |
| `employee_id` | BIGINT UNSIGNED | NO | Foreign Key -> `employees.id` (cascade delete) |
| `branch_id` | BIGINT UNSIGNED | YES | Foreign Key -> `branches.id` (null on delete) |
| `shift_template_id` | BIGINT UNSIGNED | YES | Foreign Key -> `shift_templates.id` (null on delete) |
| `schedule_date` | DATE | NO | Target schedule date (`YYYY-MM-DD`) |
| `custom_start_time`| TIME | YES | Optional override start time |
| `custom_end_time` | TIME | YES | Optional override end time |
| `is_rest_day` | BOOLEAN | NO | Default `false`. True if scheduled as rest day |
| `notes` | TEXT | YES | Planners' notes / tags |
| `created_at` | TIMESTAMP | YES | Creation timestamp |
| `updated_at` | TIMESTAMP | YES | Update timestamp |
| **Unique Constraint** | (`employee_id`, `schedule_date`) | - | 1 schedule row per employee per date |

### Table: `shift_templates`
| Column | Type | Nullable | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | Primary Key |
| `name` | VARCHAR(60) | NO | E.g. "Opening", "Mid Day", "Late Day", "Closing" |
| `code` | VARCHAR(20) | NO | Unique code, e.g. "0800", "O", "MD", "LD", "C" |
| `start_time` | TIME | NO | E.g. "08:00:00" |
| `end_time` | TIME | NO | E.g. "17:00:00" |
| `is_overnight` | BOOLEAN | NO | Default `false` |
| `break_minutes` | UNSIGNED INT | NO | Default `60` |
| `color` | VARCHAR(20) | NO | E.g. `#10b981`, `#3b82f6`, `#f59e0b`, `#8b5cf6` |
| `description` | TEXT | YES | Template description |

---

## 2. API Endpoints Contract

### Endpoint 1: Quick Cell Upsert
- **Method:** `POST`
- **Route:** `hr.attendance.schedules.quick-assign` (`/attendance/schedules/quick-assign`)
- **Headers:** `Content-Type: application/json`, `X-CSRF-TOKEN: <csrf_token>`, `Accept: application/json`
- **Request Payload:**
```json
{
  "employee_id": 14,
  "date": "2026-09-28",
  "shift_template_id": 2, // or null if rest day or clear
  "is_rest_day": false,   // true if setting Rest Day
  "clear": false          // true if clearing/unassigning
}
```
- **Response Success (200 OK):**
```json
{
  "success": true,
  "message": "Shift updated successfully",
  "data": {
    "employee_id": 14,
    "date": "2026-09-28",
    "is_rest_day": false,
    "shift": {
      "id": 2,
      "name": "Mid Day",
      "code": "1000",
      "time_range": "10:00 AM - 7:00 PM",
      "color": "#3b82f6"
    }
  }
}
```

### Endpoint 2: Copy Previous Week Schedules
- **Method:** `POST`
- **Route:** `hr.attendance.schedules.copy-week` (`/attendance/schedules/copy-week`)
- **Headers:** `Content-Type: application/json`, `X-CSRF-TOKEN: <csrf_token>`, `Accept: application/json`
- **Request Payload:**
```json
{
  "current_week_start": "2026-09-28",
  "branch_id": null // optional filter
}
```
- **Response Success (200 OK):**
```json
{
  "success": true,
  "message": "Successfully copied 42 shift schedules from previous week.",
  "copied_count": 42
}
```

### Endpoint 3: Batch Save Schedule Matrix
- **Method:** `POST`
- **Route:** `hr.attendance.schedules.batch` (`/attendance/schedules/batch`)
- **Headers:** `Content-Type: application/json`, `X-CSRF-TOKEN: <csrf_token>`, `Accept: application/json`
- **Request Payload:**
```json
{
  "schedules": [
    {
      "employee_id": 14,
      "date": "2026-09-28",
      "shift_template_id": 1,
      "is_rest_day": false
    },
    {
      "employee_id": 14,
      "date": "2026-10-04",
      "shift_template_id": null,
      "is_rest_day": true
    }
  ]
}
```
- **Response Success (200 OK):**
```json
{
  "success": true,
  "message": "Saved 2 schedule entries successfully.",
  "total_updated": 2
}
```

### Endpoint 4: Quick Fill Row (Employee's Week)
- **Method:** `POST`
- **Route:** `hr.attendance.schedules.quick-fill-row` (`/attendance/schedules/quick-fill-row`)
- **Request Payload:**
```json
{
  "employee_id": 14,
  "week_start": "2026-09-28",
  "shift_template_id": 1,
  "rest_days": ["Sun"]
}
```
- **Response Success (200 OK):**
```json
{
  "success": true,
  "message": "Populated 7 days schedule for employee.",
  "updated_count": 7
}
```
