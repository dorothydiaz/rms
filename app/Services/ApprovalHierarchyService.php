<?php

namespace App\Services;

use App\Models\Hr\Department;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalHierarchyService
{
    /**
     * Get the top leader of the organization (Summit Leader).
     * Prevents unassigned staff with supervisor_id = null from acting as CEO.
     */
    public function getTopLeader(): ?Employee
    {
        // 1. First look for root employee with subordinates
        $leader = Employee::whereNull('supervisor_id')
            ->has('subordinates')
            ->first();

        if ($leader) {
            return $leader;
        }

        // 2. Fallback to GM / Executive by title or ID 1
        return Employee::where(function ($q) {
            $q->where('id', 1)
              ->orWhere('employee_id', 1);
        })->first() ?? Employee::first();
    }

    /**
     * Get the immediate line manager for an employee.
     */
    public function getImmediateManager(Employee $employee): ?Employee
    {
        return $employee->supervisor;
    }

    /**
     * Get the full sequential approval ladder for a request without skipping managers.
     * 
     * Sequential Chain of Command (Tree Climbing):
     * Step 1: Immediate Line Supervisor ($employee->supervisor) - MANDATORY
     * Step 2: Next Manager Up the Tree ($supervisor->supervisor) / Department Head
     * Step 3: Top Executive / General Manager
     * 
     * Edge Case Fixes:
     * - Gap 1: If Immediate Supervisor IS the Department Head, auto-advance; never add the same person twice.
     * - Gap 2 & Gap 5: Multi-Branch & Chain-of-command climbing works across Makati, Taguig, and QC seamlessly.
     * - Gap 3: Apex is strictly verified via getTopLeader().
     */
    public function getSequentialApprovalChain(Employee $employee, string $module, float $amount = 0, float $durationDays = 0): array
    {
        $chain = [];
        $step = 1;
        $seenEmployeeIds = [$employee->id];

        // Step 1: Immediate Direct Manager (Never Skipped)
        $directManager = $employee->supervisor;
        if ($directManager && !in_array($directManager->id, $seenEmployeeIds)) {
            $chain[] = [
                'step' => $step++,
                'level_name' => 'Immediate Supervisor',
                'employee_id' => $directManager->id,
                'employee_name' => $directManager->full_name,
                'position' => $directManager->position?->name ?? 'Supervisor',
                'department' => $directManager->department?->name ?? 'Operations',
            ];
            $seenEmployeeIds[] = $directManager->id;
        }

        // Step 2: Department Head / Next Manager in Chain (Escalation)
        $requiresStep2 = false;
        if ($module === 'leave' && $durationDays >= 5) {
            $requiresStep2 = true;
        } elseif (in_array($module, ['purchase_order', 'purchase_request']) && $amount >= 10000) {
            $requiresStep2 = true;
        } elseif (in_array($module, ['waste', 'stock_out']) && $amount >= 5000) {
            $requiresStep2 = true;
        }

        if ($requiresStep2) {
            // First check tree climbing: manager's manager
            $nextManager = $directManager?->supervisor;

            // If no manager's manager or direct manager is already top, check department head
            if (!$nextManager) {
                $deptHead = $employee->department?->head;
                if ($deptHead && !in_array($deptHead->id, $seenEmployeeIds)) {
                    $nextManager = $deptHead;
                }
            }

            if ($nextManager && !in_array($nextManager->id, $seenEmployeeIds)) {
                $chain[] = [
                    'step' => $step++,
                    'level_name' => 'Department Head / Operations Manager',
                    'employee_id' => $nextManager->id,
                    'employee_name' => $nextManager->full_name,
                    'position' => $nextManager->position?->name ?? 'Manager',
                    'department' => $nextManager->department?->name ?? ($employee->department?->name ?? 'Department'),
                ];
                $seenEmployeeIds[] = $nextManager->id;
            }
        }

        // Step 3: General Manager / Top of Organization (High Value / Executive Policy)
        $requiresStep3 = false;
        if (in_array($module, ['purchase_order', 'purchase_request']) && $amount >= 50000) {
            $requiresStep3 = true;
        } elseif (in_array($module, ['waste', 'stock_out']) && $amount >= 20000) {
            $requiresStep3 = true;
        }

        if ($requiresStep3) {
            $topLeader = $this->getTopLeader();
            if ($topLeader && !in_array($topLeader->id, $seenEmployeeIds)) {
                $chain[] = [
                    'step' => $step++,
                    'level_name' => 'General Manager',
                    'employee_id' => $topLeader->id,
                    'employee_name' => $topLeader->full_name,
                    'position' => $topLeader->position?->name ?? 'General Manager',
                    'department' => 'Executive Management',
                ];
                $seenEmployeeIds[] = $topLeader->id;
            }
        }

        return $chain;
    }

    /**
     * Determine the NEXT active approver for a specific record.
     * Approvals strictly follow Step 1 -> Step 2 -> Step 3.
     */
    public function getNextApprover(Model $record, string $module): ?Employee
    {
        $employee = $record->employee ?? ($record->requestor ?? null);
        if (!$employee) {
            return null;
        }

        $amount = (float) ($record->total_amount ?? $record->cost ?? $record->total_cost ?? $record->gross_total ?? 0);
        $durationDays = (float) ($record->number_of_days ?? 0);
        $chain = $this->getSequentialApprovalChain($employee, $module, $amount, $durationDays);

        if (empty($chain)) {
            return null;
        }

        // Check multi-step state on record
        $hasSupervisorApproved = !empty($record->supervisor_approved_at) || !empty($record->leave_request_supervisor_approved_at);
        $hasDeptHeadApproved = !empty($record->dept_head_approved_at) || !empty($record->leave_request_dept_head_approved_at);
        $hasGmApproved = !empty($record->gm_approved_at) || !empty($record->leave_request_gm_approved_at);

        foreach ($chain as $item) {
            if ($item['level_name'] === 'Immediate Supervisor' && !$hasSupervisorApproved) {
                return Employee::find($item['employee_id']);
            }
            if (str_contains($item['level_name'], 'Department Head') && !$hasDeptHeadApproved) {
                return Employee::find($item['employee_id']);
            }
            if ($item['level_name'] === 'General Manager' && !$hasGmApproved) {
                return Employee::find($item['employee_id']);
            }
        }

        // If legacy/pending single-step record
        $status = $record->status ?? $record->leave_request_status ?? '';
        if ($status === 'Pending' && !empty($chain[0])) {
            return Employee::find($chain[0]['employee_id']);
        }

        return null;
    }

    /**
     * Check if a given User has permission to approve the current step.
     */
    public function canUserApprove(User $user, Model $record, string $module): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $nextApprover = $this->getNextApprover($record, $module);
        if (!$nextApprover) {
            return false;
        }

        return (int) $nextApprover->user_id === (int) $user->id;
    }

    /**
     * Process an approval action on a record, advancing the sequential step or completing it.
     */
    public function processApproval(Model $record, string $module, User $approverUser, string $action = 'Approve', ?string $remarks = null): array
    {
        $approverEmployee = $approverUser->employee ?? Employee::where('user_id', $approverUser->id)->first();
        $isSuperAdmin = $approverUser->isSuperAdmin();

        $nextApprover = $this->getNextApprover($record, $module);
        if (!$isSuperAdmin && (!$nextApprover || (int) $nextApprover->user_id !== (int) $approverUser->id)) {
            return [
                'success' => false,
                'message' => 'Unauthorized: You are not the required sequential approver for this step.',
            ];
        }

        $now = now();
        $prefix = str_starts_with($record->getTable(), 'hr_') ? 'leave_request_' : '';

        if ($action === 'Reject') {
            $record->update([
                $prefix . 'status' => 'Rejected',
                $prefix . 'rejection_reason' => $remarks,
            ]);
            return [
                'success' => true,
                'status' => 'Rejected',
                'message' => 'Request rejected.',
            ];
        }

        // Determine current approval step
        $currentStepApproverId = $nextApprover ? $nextApprover->id : ($approverEmployee?->id ?? 1);
        $amount = (float) ($record->total_amount ?? $record->cost ?? $record->total_cost ?? $record->gross_total ?? 0);
        $durationDays = (float) ($record->number_of_days ?? 0);
        $employee = $record->employee ?? ($record->requestor ?? null);
        $chain = $this->getSequentialApprovalChain($employee, $module, $amount, $durationDays);

        $hasSupervisorApproved = !empty($record->{$prefix . 'supervisor_approved_at'});
        $hasDeptHeadApproved = !empty($record->{$prefix . 'dept_head_approved_at'});

        $updates = [];

        if (!$hasSupervisorApproved) {
            $updates[$prefix . 'supervisor_approved_by'] = $currentStepApproverId;
            $updates[$prefix . 'supervisor_approved_at'] = $now;

            // Gap 1 Fix: Check if this supervisor is also the Department Head or top leader in the chain!
            // If they are, auto-stamp Step 2 as well!
            $deptHead = $employee->department?->head;
            if ($deptHead && $deptHead->id === $currentStepApproverId) {
                $updates[$prefix . 'dept_head_approved_by'] = $currentStepApproverId;
                $updates[$prefix . 'dept_head_approved_at'] = $now;
            }
        } elseif (!$hasDeptHeadApproved) {
            $updates[$prefix . 'dept_head_approved_by'] = $currentStepApproverId;
            $updates[$prefix . 'dept_head_approved_at'] = $now;
        } else {
            $updates[$prefix . 'gm_approved_by'] = $currentStepApproverId;
            $updates[$prefix . 'gm_approved_at'] = $now;
        }

        // Check if more steps remain in the chain
        $record->fill($updates);
        $remainingApprover = $this->getNextApprover($record, $module);

        if ($remainingApprover) {
            $updates[$prefix . 'current_approver_id'] = $remainingApprover->id;
            $updates[$prefix . 'status'] = 'Pending';
            $record->update($updates);

            return [
                'success' => true,
                'status' => 'Pending',
                'next_approver' => $remainingApprover->full_name,
                'message' => "Approved by {$approverUser->full_name}. Request escalated to {$remainingApprover->full_name}.",
            ];
        }

        // Fully approved
        $updates[$prefix . 'status'] = 'Approved';
        $updates[$prefix . 'current_approver_id'] = null;
        if (SchemaHasColumn($record->getTable(), $prefix . 'approval_date')) {
            $updates[$prefix . 'approval_date'] = $now;
        }
        if (SchemaHasColumn($record->getTable(), $prefix . 'approver_id')) {
            $updates[$prefix . 'approver_id'] = $approverUser->id;
        }

        $record->update($updates);

        return [
            'success' => true,
            'status' => 'Approved',
            'message' => 'Request fully approved.',
        ];
    }
}

if (!function_exists('SchemaHasColumn')) {
    function SchemaHasColumn(string $table, string $column): bool {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }
}
