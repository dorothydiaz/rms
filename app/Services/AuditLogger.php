<?php

namespace App\Services;

use App\Models\Hr\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log an administrative or operational action.
     *
     * @param string $action (Create, Update, Delete, Approve, Reject, Finalize, Calculate, Adjust, Login, Logout)
     * @param string $module (Employees, Attendance, Leave, Payroll, Performance, Training, Settings, RBAC)
     * @param string|int|null $recordId
     * @param string|null $details
     * @param mixed $previousValue
     * @param mixed $newValue
     * @return AuditLog
     */
    public static function log(
        string $action,
        string $module,
        $recordId = null,
        ?string $details = null,
        $previousValue = null,
        $newValue = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId ? (string) $recordId : null,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'previous_value' => is_array($previousValue) ? $previousValue : ($previousValue ? (array) $previousValue : null),
            'new_value' => is_array($newValue) ? $newValue : ($newValue ? (array) $newValue : null),
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
