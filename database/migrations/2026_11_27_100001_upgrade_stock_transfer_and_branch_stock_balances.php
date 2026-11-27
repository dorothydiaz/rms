<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Materialized O(1) per-branch stock balances table
        if (!Schema::hasTable('branch_stock_balances')) {
            Schema::create('branch_stock_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->index();
                $table->unsignedBigInteger('inventory_item_id')->index();
                $table->string('sku', 100)->index();
                $table->decimal('on_hand_qty', 15, 4)->default(0.0000);
                $table->decimal('reserved_qty', 15, 4)->default(0.0000);
                $table->decimal('in_transit_in_qty', 15, 4)->default(0.0000);
                $table->decimal('in_transit_out_qty', 15, 4)->default(0.0000);
                $table->decimal('avg_unit_cost', 15, 4)->default(0.0000);
                $table->timestamp('last_movement_at')->nullable();
                $table->timestamps();

                $table->unique(['branch_id', 'inventory_item_id'], 'bsb_branch_item_unique');
                $table->index(['branch_id', 'sku'], 'bsb_branch_sku_idx');
            });
        }

        // 2. Upgrade stock_transfer_header with lifecycle, custody, receipt, variance & idempotency fields
        if (Schema::hasTable('stock_transfer_header')) {
            Schema::table('stock_transfer_header', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transfer_header', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->index();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'parent_transfer_id')) {
                    $table->unsignedBigInteger('parent_transfer_id')->nullable()->index();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'receipt_number')) {
                    $table->string('receipt_number', 60)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'approved_at')) {
                    $table->dateTime('approved_at')->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'rejected_by')) {
                    $table->string('rejected_by', 255)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'rejected_at')) {
                    $table->dateTime('rejected_at')->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'rejection_reason')) {
                    $table->string('rejection_reason', 500)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'cancelled_by')) {
                    $table->string('cancelled_by', 255)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'cancelled_at')) {
                    $table->dateTime('cancelled_at')->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'total_approved_qty')) {
                    $table->decimal('total_approved_qty', 12, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_header', 'total_variance_qty')) {
                    $table->decimal('total_variance_qty', 12, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_header', 'has_discrepancy')) {
                    $table->boolean('has_discrepancy')->default(false)->index();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'idempotency_key')) {
                    $table->string('idempotency_key', 120)->nullable();
                }
            });

            Schema::table('stock_transfer_header', function (Blueprint $table) {
                if (!Schema::hasIndex('stock_transfer_header', 'sth_receipt_number_unique')) {
                    $table->unique('receipt_number', 'sth_receipt_number_unique');
                }
                if (!Schema::hasIndex('stock_transfer_header', 'sth_idempotency_key_unique')) {
                    $table->unique('idempotency_key', 'sth_idempotency_key_unique');
                }
                if (!Schema::hasIndex('stock_transfer_header', 'sth_status_src_created_idx')) {
                    $table->index(['status', 'source_branch_id', 'created_at'], 'sth_status_src_created_idx');
                }
                if (!Schema::hasIndex('stock_transfer_header', 'sth_status_dst_created_idx')) {
                    $table->index(['status', 'destination_branch_id', 'created_at'], 'sth_status_dst_created_idx');
                }
                if (!Schema::hasIndex('stock_transfer_header', 'sth_type_status_idx')) {
                    $table->index(['transfer_type', 'status'], 'sth_type_status_idx');
                }
            });
        }

        // 3. Upgrade stock_transfer_lines with approval, reservation, UOM conversion & variance fields
        if (Schema::hasTable('stock_transfer_lines')) {
            Schema::table('stock_transfer_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transfer_lines', 'approved_qty')) {
                    $table->decimal('approved_qty', 12, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'reserved_qty')) {
                    $table->decimal('reserved_qty', 12, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'variance_qty')) {
                    $table->decimal('variance_qty', 12, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'variance_reason')) {
                    $table->string('variance_reason', 100)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'uom_multiplier')) {
                    $table->decimal('uom_multiplier', 12, 4)->default(1.0000);
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'base_uom')) {
                    $table->string('base_uom', 50)->nullable();
                }
            });

            Schema::table('stock_transfer_lines', function (Blueprint $table) {
                if (!Schema::hasIndex('stock_transfer_lines', 'stl_transfer_sku_idx')) {
                    $table->index(['internal_transfer_id', 'sku'], 'stl_transfer_sku_idx');
                }
            });
        }

        // 4. Add branch_id & composite index to inventory_ledger
        if (Schema::hasTable('inventory_ledger')) {
            Schema::table('inventory_ledger', function (Blueprint $table) {
                if (!Schema::hasColumn('inventory_ledger', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->index();
                }
            });

            Schema::table('inventory_ledger', function (Blueprint $table) {
                if (!Schema::hasIndex('inventory_ledger', 'il_branch_item_created_idx')) {
                    $table->index(['branch_id', 'inventory_item_id', 'created_at'], 'il_branch_item_created_idx');
                }
            });
        }

        // 5. Idempotent 1:1 Backfill of item_master.current_stock into Primary/Commissary Branch
        $this->backfillPrimaryBranchStock();
    }

    protected function backfillPrimaryBranchStock(): void
    {
        if (!Schema::hasTable('branch_stock_balances') || !Schema::hasTable('item_master')) {
            return;
        }

        if (DB::table('branch_stock_balances')->exists()) {
            return;
        }

        $primaryBranchId = null;
        $companyId = null;

        if (Schema::hasTable('branches')) {
            $primaryBranch = DB::table('branches')
                ->where('branch_is_active', true)
                ->orderByRaw("CASE WHEN LOWER(branch_name) LIKE '%commissary%' OR LOWER(branch_name) LIKE '%central%' OR LOWER(branch_name) LIKE '%main%' OR LOWER(branch_type) LIKE '%commissary%' THEN 0 ELSE 1 END")
                ->orderBy('branch_id')
                ->first();

            if ($primaryBranch) {
                $primaryBranchId = (int) $primaryBranch->branch_id;
                $companyId = $primaryBranch->company_id !== null ? (int) $primaryBranch->company_id : null;
            }
        }

        if ($primaryBranchId === null && Schema::hasTable('hr_branches')) {
            $hrBranch = DB::table('hr_branches')
                ->where('branch_is_active', true)
                ->orderBy('branch_id')
                ->first();

            if ($hrBranch) {
                $primaryBranchId = (int) $hrBranch->branch_id;
                $companyId = $hrBranch->branch_company_id !== null ? (int) $hrBranch->branch_company_id : null;
            }
        }

        if ($primaryBranchId === null) {
            return;
        }

        $now = now();
        DB::table('item_master')
            ->where('current_stock', '>', 0)
            ->orderBy('id')
            ->chunkById(500, function ($items) use ($primaryBranchId, $companyId, $now) {
                $rows = [];
                foreach ($items as $item) {
                    $rows[] = [
                        'company_id' => $companyId,
                        'branch_id' => $primaryBranchId,
                        'inventory_item_id' => $item->id,
                        'sku' => $item->sku,
                        'on_hand_qty' => (float) $item->current_stock,
                        'reserved_qty' => 0.0000,
                        'in_transit_in_qty' => 0.0000,
                        'in_transit_out_qty' => 0.0000,
                        'avg_unit_cost' => (float) $item->cost_price,
                        'last_movement_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('branch_stock_balances')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_ledger')) {
            Schema::table('inventory_ledger', function (Blueprint $table) {
                if (Schema::hasIndex('inventory_ledger', 'il_branch_item_created_idx')) {
                    $table->dropIndex('il_branch_item_created_idx');
                }
                if (Schema::hasColumn('inventory_ledger', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }

        if (Schema::hasTable('stock_transfer_lines')) {
            Schema::table('stock_transfer_lines', function (Blueprint $table) {
                if (Schema::hasIndex('stock_transfer_lines', 'stl_transfer_sku_idx')) {
                    $table->dropIndex('stl_transfer_sku_idx');
                }
                foreach (['approved_qty', 'reserved_qty', 'variance_qty', 'variance_reason', 'uom_multiplier', 'base_uom'] as $col) {
                    if (Schema::hasColumn('stock_transfer_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('stock_transfer_header')) {
            Schema::table('stock_transfer_header', function (Blueprint $table) {
                foreach (['sth_receipt_number_unique', 'sth_idempotency_key_unique'] as $uniq) {
                    if (Schema::hasIndex('stock_transfer_header', $uniq)) {
                        $table->dropUnique($uniq);
                    }
                }
                foreach (['sth_status_src_created_idx', 'sth_status_dst_created_idx', 'sth_type_status_idx'] as $idx) {
                    if (Schema::hasIndex('stock_transfer_header', $idx)) {
                        $table->dropIndex($idx);
                    }
                }
                foreach ([
                    'company_id', 'parent_transfer_id', 'receipt_number', 'approved_at',
                    'rejected_by', 'rejected_at', 'rejection_reason',
                    'cancelled_by', 'cancelled_at', 'total_approved_qty',
                    'total_variance_qty', 'has_discrepancy', 'idempotency_key',
                ] as $col) {
                    if (Schema::hasColumn('stock_transfer_header', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('branch_stock_balances');
    }
};
