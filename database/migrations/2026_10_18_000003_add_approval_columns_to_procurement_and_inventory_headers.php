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
        $tables = ['purchase_order_header', 'stock_waste_header', 'stock_out_header'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'current_approver_id')) {
                        $table->unsignedBigInteger('current_approver_id')->nullable()->after('status');
                        $table->tinyInteger('approval_step')->default(1)->after('current_approver_id');
                        $table->unsignedBigInteger('supervisor_approved_by')->nullable()->after('approval_step');
                        $table->dateTime('supervisor_approved_at')->nullable()->after('supervisor_approved_by');
                        $table->unsignedBigInteger('dept_head_approved_by')->nullable()->after('supervisor_approved_at');
                        $table->dateTime('dept_head_approved_at')->nullable()->after('dept_head_approved_by');
                        $table->unsignedBigInteger('gm_approved_by')->nullable()->after('dept_head_approved_at');
                        $table->dateTime('gm_approved_at')->nullable()->after('gm_approved_by');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['purchase_order_header', 'stock_waste_header', 'stock_out_header'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $columns = [
                        'current_approver_id',
                        'approval_step',
                        'supervisor_approved_by',
                        'supervisor_approved_at',
                        'dept_head_approved_by',
                        'dept_head_approved_at',
                        'gm_approved_by',
                        'gm_approved_at',
                    ];
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $table->dropColumn($column);
                        }
                    }
                });
            }
        }
    }
};
