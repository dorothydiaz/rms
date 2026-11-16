<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. acct_vendor_payments
        if (Schema::hasTable('acct_vendor_payments')) {
            Schema::table('acct_vendor_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_vendor_payments', 'total_amount')) {
                    $table->decimal('total_amount', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('acct_vendor_payments', 'total_ewt_amount')) {
                    $table->decimal('total_ewt_amount', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('acct_vendor_payments', 'total_net_amount')) {
                    $table->decimal('total_net_amount', 18, 4)->default(0.0000);
                }
            });

            DB::statement('UPDATE acct_vendor_payments SET total_amount = gross_amount, total_ewt_amount = ewt_amount, total_net_amount = net_amount WHERE (total_amount = 0 OR total_amount IS NULL) AND gross_amount > 0');
        }

        // 2. acct_bir_2307_certificates
        if (Schema::hasTable('acct_bir_2307_certificates')) {
            Schema::table('acct_bir_2307_certificates', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_bir_2307_certificates', 'payee_tin')) {
                    $table->string('payee_tin', 50)->nullable();
                }
                if (!Schema::hasColumn('acct_bir_2307_certificates', 'vendor_payment_id')) {
                    $table->uuid('vendor_payment_id')->nullable()->index();
                }
                if (!Schema::hasColumn('acct_bir_2307_certificates', 'gross_income_payment')) {
                    $table->decimal('gross_income_payment', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('acct_bir_2307_certificates', 'amount_withheld')) {
                    $table->decimal('amount_withheld', 18, 4)->default(0.0000);
                }
            });

            DB::statement('UPDATE acct_bir_2307_certificates SET payee_tin = party_tin WHERE payee_tin IS NULL AND party_tin IS NOT NULL');
            DB::statement('UPDATE acct_bir_2307_certificates SET gross_income_payment = income_payment_amount, amount_withheld = tax_withheld_amount WHERE (gross_income_payment = 0 OR gross_income_payment IS NULL) AND income_payment_amount > 0');
        }

        // 3. acct_collection_receipts
        if (Schema::hasTable('acct_collection_receipts')) {
            Schema::table('acct_collection_receipts', function (Blueprint $table) {
                if (!Schema::hasColumn('acct_collection_receipts', 'total_amount')) {
                    $table->decimal('total_amount', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('acct_collection_receipts', 'total_cwt_amount')) {
                    $table->decimal('total_cwt_amount', 18, 4)->default(0.0000);
                }
                if (!Schema::hasColumn('acct_collection_receipts', 'total_net_amount')) {
                    $table->decimal('total_net_amount', 18, 4)->default(0.0000);
                }
            });

            DB::statement('UPDATE acct_collection_receipts SET total_amount = gross_amount, total_cwt_amount = cwt_amount, total_net_amount = net_amount WHERE (total_amount = 0 OR total_amount IS NULL) AND gross_amount > 0');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Columns remain harmlessly in place
    }
};

