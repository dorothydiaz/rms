<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Leave Requests
        $leaveTable = Schema::hasTable('hr_leave_requests') ? 'hr_leave_requests' : (Schema::hasTable('leave_requests') ? 'leave_requests' : null);
        if ($leaveTable) {
            Schema::table($leaveTable, function (Blueprint $table) use ($leaveTable) {
                $prefix = str_starts_with($leaveTable, 'hr_') ? 'leave_request_' : '';
                if (!Schema::hasColumn($leaveTable, $prefix . 'current_approver_id')) {
                    $table->unsignedBigInteger($prefix . 'current_approver_id')->nullable();
                    $table->tinyInteger($prefix . 'approval_step')->default(1);
                    $table->unsignedBigInteger($prefix . 'supervisor_approved_by')->nullable();
                    $table->dateTime($prefix . 'supervisor_approved_at')->nullable();
                    $table->unsignedBigInteger($prefix . 'dept_head_approved_by')->nullable();
                    $table->dateTime($prefix . 'dept_head_approved_at')->nullable();
                    $table->unsignedBigInteger($prefix . 'gm_approved_by')->nullable();
                    $table->dateTime($prefix . 'gm_approved_at')->nullable();
                }
            });
        }

        // 2. Purchase Orders
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_orders', 'current_approver_id')) {
                    $table->unsignedBigInteger('current_approver_id')->nullable();
                    $table->tinyInteger('approval_step')->default(1);
                    $table->unsignedBigInteger('supervisor_approved_by')->nullable();
                    $table->dateTime('supervisor_approved_at')->nullable();
                    $table->unsignedBigInteger('dept_head_approved_by')->nullable();
                    $table->dateTime('dept_head_approved_at')->nullable();
                    $table->unsignedBigInteger('gm_approved_by')->nullable();
                    $table->dateTime('gm_approved_at')->nullable();
                }
            });
        }

        // 3. Waste Records
        if (Schema::hasTable('waste_records')) {
            Schema::table('waste_records', function (Blueprint $table) {
                if (!Schema::hasColumn('waste_records', 'current_approver_id')) {
                    $table->unsignedBigInteger('current_approver_id')->nullable();
                    $table->tinyInteger('approval_step')->default(1);
                    $table->unsignedBigInteger('supervisor_approved_by')->nullable();
                    $table->dateTime('supervisor_approved_at')->nullable();
                    $table->unsignedBigInteger('dept_head_approved_by')->nullable();
                    $table->dateTime('dept_head_approved_at')->nullable();
                    $table->unsignedBigInteger('gm_approved_by')->nullable();
                    $table->dateTime('gm_approved_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $leaveTable = Schema::hasTable('hr_leave_requests') ? 'hr_leave_requests' : (Schema::hasTable('leave_requests') ? 'leave_requests' : null);
        if ($leaveTable) {
            $prefix = str_starts_with($leaveTable, 'hr_') ? 'leave_request_' : '';
            if (Schema::hasColumn($leaveTable, $prefix . 'current_approver_id')) {
                Schema::table($leaveTable, function (Blueprint $table) use ($prefix) {
                    $table->dropColumn([
                        $prefix . 'current_approver_id',
                        $prefix . 'approval_step',
                        $prefix . 'supervisor_approved_by',
                        $prefix . 'supervisor_approved_at',
                        $prefix . 'dept_head_approved_by',
                        $prefix . 'dept_head_approved_at',
                        $prefix . 'gm_approved_by',
                        $prefix . 'gm_approved_at',
                    ]);
                });
            }
        }

        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'current_approver_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn([
                    'current_approver_id',
                    'approval_step',
                    'supervisor_approved_by',
                    'supervisor_approved_at',
                    'dept_head_approved_by',
                    'dept_head_approved_at',
                    'gm_approved_by',
                    'gm_approved_at',
                ]);
            });
        }

        if (Schema::hasTable('waste_records') && Schema::hasColumn('waste_records', 'current_approver_id')) {
            Schema::table('waste_records', function (Blueprint $table) {
                $table->dropColumn([
                    'current_approver_id',
                    'approval_step',
                    'supervisor_approved_by',
                    'supervisor_approved_at',
                    'dept_head_approved_by',
                    'dept_head_approved_at',
                    'gm_approved_by',
                    'gm_approved_at',
                ]);
            });
        }
    }
};
