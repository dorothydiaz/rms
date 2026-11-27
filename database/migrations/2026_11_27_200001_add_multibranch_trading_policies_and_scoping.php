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
        // 1. Multi-branch operational & internal trading policies on canonical branches
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (!Schema::hasColumn('branches', 'branch_role')) {
                    $table->string('branch_role', 50)->default('RETAIL_STORE');
                }
                if (!Schema::hasColumn('branches', 'default_source_branch_id')) {
                    $table->unsignedBigInteger('default_source_branch_id')->nullable()->index();
                }
                if (!Schema::hasColumn('branches', 'can_supply_branches')) {
                    $table->boolean('can_supply_branches')->default(true);
                }
                if (!Schema::hasColumn('branches', 'allow_peer_transfers')) {
                    $table->boolean('allow_peer_transfers')->default(true);
                }
                if (!Schema::hasColumn('branches', 'can_direct_procure')) {
                    $table->boolean('can_direct_procure')->default(true);
                }
                if (!Schema::hasColumn('branches', 'internal_pricing_mode')) {
                    $table->string('internal_pricing_mode', 40)->default('AT_COST');
                }
                if (!Schema::hasColumn('branches', 'internal_markup_pct')) {
                    $table->decimal('internal_markup_pct', 8, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('branches', 'auto_post_interbranch_gl')) {
                    $table->boolean('auto_post_interbranch_gl')->default(true);
                }
            });

            // Backfill sensible roles & default commissary routing
            $commissary = DB::table('branches')
                ->where('branch_is_active', true)
                ->where(function ($q) {
                    $q->whereRaw("LOWER(branch_name) LIKE '%commissary%'")
                      ->orWhereRaw("LOWER(branch_name) LIKE '%central%'")
                      ->orWhereRaw("LOWER(branch_type) LIKE '%commissary%'")
                      ->orWhereRaw("LOWER(branch_type) LIKE '%warehouse%'");
                })
                ->orderBy('branch_id')
                ->first();

            if ($commissary) {
                DB::table('branches')
                    ->where('branch_id', $commissary->branch_id)
                    ->update(['branch_role' => 'CENTRAL_COMMISSARY']);

                DB::table('branches')
                    ->where('branch_id', '!=', $commissary->branch_id)
                    ->whereNull('default_source_branch_id')
                    ->update(['default_source_branch_id' => $commissary->branch_id]);
            }
        }

        // 2. Internal billing & GL settlement tracking on stock_transfer_header
        if (Schema::hasTable('stock_transfer_header')) {
            Schema::table('stock_transfer_header', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transfer_header', 'pricing_mode')) {
                    $table->string('pricing_mode', 40)->default('AT_COST');
                }
                if (!Schema::hasColumn('stock_transfer_header', 'markup_pct')) {
                    $table->decimal('markup_pct', 8, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('stock_transfer_header', 'total_internal_billed')) {
                    $table->decimal('total_internal_billed', 15, 2)->default(0.00);
                }
                if (!Schema::hasColumn('stock_transfer_header', 'settlement_status')) {
                    $table->string('settlement_status', 40)->default('UNBILLED')->index();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'dispatch_journal_id')) {
                    $table->string('dispatch_journal_id', 60)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'receipt_journal_id')) {
                    $table->string('receipt_journal_id', 60)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'settlement_journal_id')) {
                    $table->string('settlement_journal_id', 60)->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'settled_at')) {
                    $table->dateTime('settled_at')->nullable();
                }
                if (!Schema::hasColumn('stock_transfer_header', 'settled_by')) {
                    $table->string('settled_by', 255)->nullable();
                }
            });
        }

        // 3. Internal transfer pricing per line item
        if (Schema::hasTable('stock_transfer_lines')) {
            Schema::table('stock_transfer_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transfer_lines', 'internal_unit_price')) {
                    $table->decimal('internal_unit_price', 15, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('stock_transfer_lines', 'internal_line_total')) {
                    $table->decimal('internal_line_total', 15, 2)->default(0.00);
                }
            });
        }

        // 4. Branch & Company scoping columns across operational inventory & procurement headers
        foreach (['stock_in_header', 'stock_out_header', 'production_orders', 'purchase_order_header', 'rfq_header'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'company_id')) {
                        $table->unsignedBigInteger('company_id')->nullable()->index();
                    }
                    if (!Schema::hasColumn($tableName, 'branch_id')) {
                        $table->unsignedBigInteger('branch_id')->nullable()->index();
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['stock_in_header', 'stock_out_header', 'production_orders', 'purchase_order_header', 'rfq_header'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    foreach (['company_id', 'branch_id'] as $col) {
                        if (Schema::hasColumn($tableName, $col)) {
                            $table->dropColumn($col);
                        }
                    }
                });
            }
        }

        if (Schema::hasTable('stock_transfer_lines')) {
            Schema::table('stock_transfer_lines', function (Blueprint $table) {
                foreach (['internal_unit_price', 'internal_line_total'] as $col) {
                    if (Schema::hasColumn('stock_transfer_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('stock_transfer_header')) {
            Schema::table('stock_transfer_header', function (Blueprint $table) {
                foreach ([
                    'pricing_mode', 'markup_pct', 'total_internal_billed', 'settlement_status',
                    'dispatch_journal_id', 'receipt_journal_id', 'settlement_journal_id',
                    'settled_at', 'settled_by',
                ] as $col) {
                    if (Schema::hasColumn('stock_transfer_header', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                foreach ([
                    'branch_role', 'default_source_branch_id', 'can_supply_branches',
                    'allow_peer_transfers', 'can_direct_procure', 'internal_pricing_mode',
                    'internal_markup_pct', 'auto_post_interbranch_gl',
                ] as $col) {
                    if (Schema::hasColumn('branches', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
