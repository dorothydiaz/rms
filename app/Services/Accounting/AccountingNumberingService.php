<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctNumberingGap;
use App\Models\Accounting\AcctNumberingSeries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingNumberingService
{
    public const DEFAULT_SERIES = [
        'JE' => ['name' => 'General Journal Entries', 'prefix' => 'JE'],
        'BILL' => ['name' => 'Vendor Bills', 'prefix' => 'BILL'],
        'PV' => ['name' => 'Vendor Payment Vouchers', 'prefix' => 'PV'],
        'VMEMO' => ['name' => 'Vendor Credit / Debit Memos', 'prefix' => 'VCM'],
        'SINV' => ['name' => 'Sales Invoices', 'prefix' => 'SI'],
        'CR' => ['name' => 'Collection Receipts', 'prefix' => 'CR'],
        'CMEMO' => ['name' => 'Customer Credit Memos', 'prefix' => 'CCM'],
        'FT' => ['name' => 'Fund Transfers', 'prefix' => 'FT'],
        'ADV' => ['name' => 'Employee Advances', 'prefix' => 'ADV'],
        'FA' => ['name' => 'Fixed Assets', 'prefix' => 'FA'],
        'LOAN' => ['name' => 'Loans Register', 'prefix' => 'LN'],
        'LCV' => ['name' => 'Landed Cost Vouchers', 'prefix' => 'LCV'],
    ];

    /**
     * Generate the next document number with row-level lock and gap reuse.
     */
    public function generateNextNumber(string $companyId, string $moduleCode, ?string $defaultPrefix = null): string
    {
        $moduleKey = strtoupper(trim($moduleCode));

        return DB::transaction(function () use ($companyId, $moduleKey, $defaultPrefix) {
            $series = AcctNumberingSeries::where('company_id', $companyId)
                ->where('module_code', $moduleKey)
                ->lockForUpdate()
                ->first();

            if (!$series) {
                $meta = self::DEFAULT_SERIES[$moduleKey] ?? ['name' => $moduleKey . ' Documents', 'prefix' => $defaultPrefix ?: $moduleKey];
                $series = AcctNumberingSeries::create([
                    'series_id' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'module_code' => $moduleKey,
                    'module_name' => $meta['name'],
                    'prefix' => $defaultPrefix ?: $meta['prefix'],
                    'next_number' => 1,
                    'padding' => 4,
                    'reset_frequency' => 'YEARLY',
                    'is_active' => true,
                ]);
                $series = AcctNumberingSeries::where('series_id', $series->series_id)->lockForUpdate()->first();
            }

            // Reuse earliest released gap number before advancing sequence
            $gap = AcctNumberingGap::where('series_id', $series->series_id)
                ->whereNull('reused_at')
                ->orderBy('released_at')
                ->lockForUpdate()
                ->first();

            if ($gap) {
                $gap->reused_at = now();
                $gap->save();
                return $gap->reserved_number;
            }

            $seq = max(1, (int) $series->next_number);
            $pad = max(3, min(8, (int) $series->padding));
            $year = date('Y');
            $formatted = sprintf('%s-%s-%0' . $pad . 'd', $series->prefix, $year, $seq);

            $series->next_number = $seq + 1;
            $series->save();

            return $formatted;
        });
    }

    /**
     * Release an unused draft document number into the gap pool for reuse.
     */
    public function releaseUnusedNumber(string $companyId, string $moduleCode, string $reservedNumber): void
    {
        $moduleKey = strtoupper(trim($moduleCode));
        if (trim($reservedNumber) === '') {
            return;
        }

        DB::transaction(function () use ($companyId, $moduleKey, $reservedNumber) {
            $series = AcctNumberingSeries::where('company_id', $companyId)
                ->where('module_code', $moduleKey)
                ->lockForUpdate()
                ->first();

            if (!$series) {
                return;
            }

            $alreadyQueued = AcctNumberingGap::where('series_id', $series->series_id)
                ->where('reserved_number', $reservedNumber)
                ->whereNull('reused_at')
                ->exists();

            if (!$alreadyQueued) {
                AcctNumberingGap::create([
                    'gap_id' => (string) Str::uuid(),
                    'series_id' => $series->series_id,
                    'company_id' => $companyId,
                    'module_code' => $moduleKey,
                    'reserved_number' => $reservedNumber,
                    'released_at' => now(),
                ]);
            }
        });
    }
}

