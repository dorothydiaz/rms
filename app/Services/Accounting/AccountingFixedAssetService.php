<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctFixedAsset;
use App\Models\Accounting\AcctFixedAssetDepreciation;
use App\Models\Accounting\AcctFixedAssetDisposal;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\AcctFiscalPeriod;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingFixedAssetService
{
    public function __construct(
        protected AccountingPostingService $postingService,
        protected AccountingNumberingService $numberingService
    ) {
    }

    /**
     * Capitalize or create a new Fixed Asset (standalone or linked to a Vendor Bill line item).
     */
    public function createOrCapitalizeAsset(array $data, ?string $vendorBillId = null, ?string $vendorBillLineId = null): AcctFixedAsset
    {
        $companyId = (string) ($data['company_id'] ?? '1');
        $branchId = (string) ($data['branch_id'] ?? 'BR-01');
        $actor = $data['acted_by'] ?? (Auth::user()->name ?? 'Asset Controller');

        if (empty($data['asset_name'])) {
            throw new InvalidArgumentException('Asset name is required.');
        }

        $cost = max(0.01, (float) ($data['acquisition_cost'] ?? 0.0));
        $salvage = max(0.0, (float) ($data['salvage_value'] ?? 0.0));
        $usefulLife = max(1, (int) ($data['useful_life_months'] ?? 60));

        return DB::transaction(function () use ($data, $vendorBillId, $vendorBillLineId, $companyId, $branchId, $cost, $salvage, $usefulLife, $actor) {
            $assetCode = !empty($data['asset_code'])
                ? (string) $data['asset_code']
                : $this->numberingService->generateNextNumber($companyId, 'FA', 'FA');

            // Resolve default GL accounts if not explicitly passed
            $assetGlId = $data['asset_gl_account_id'] ?? null;
            if (!$assetGlId) {
                try {
                    $assetAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'FIXED_ASSET_CLEARING');
                    $assetGlId = $assetAcc->account_id;
                } catch (\Throwable $e) {
                    $assetAcc = ChartOfAccount::where('company_id', $companyId)->where('account_type', 'ASSET')->firstOrFail();
                    $assetGlId = $assetAcc->account_id;
                }
            }

            $accumGlId = $data['accum_deprec_gl_account_id'] ?? null;
            if (!$accumGlId) {
                $accumAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'ACCUMULATED_DEPRECIATION');
                $accumGlId = $accumAcc->account_id;
            }

            $expenseGlId = $data['deprec_expense_gl_account_id'] ?? null;
            if (!$expenseGlId) {
                $expenseAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'DEPRECIATION_EXPENSE');
                $expenseGlId = $expenseAcc->account_id;
            }

            $depreciableBase = round(max(0.0, $cost - $salvage), 4);

            $asset = AcctFixedAsset::create([
                'asset_code' => $assetCode,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'department_id' => $data['department_id'] ?? null,
                'asset_name' => $data['asset_name'],
                'asset_category' => $data['asset_category'] ?? 'EQUIPMENT',
                'acquisition_date' => $data['acquisition_date'] ?? now()->toDateString(),
                'acquisition_cost' => $cost,
                'salvage_value' => $salvage,
                'depreciable_base' => $depreciableBase,
                'useful_life_months' => $usefulLife,
                'depreciation_method' => $data['depreciation_method'] ?? 'STRAIGHT_LINE',
                'asset_gl_account_id' => $assetGlId,
                'accum_deprec_gl_account_id' => $accumGlId,
                'deprec_expense_gl_account_id' => $expenseGlId,
                'vendor_bill_id' => $vendorBillId,
                'vendor_bill_line_id' => $vendorBillLineId,
                'total_depreciated_amount' => 0.0000,
                'net_book_value' => $cost,
                'status' => 'ACTIVE',
                'serial_number' => $data['serial_number'] ?? null,
                'location' => $data['location'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor,
            ]);

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $branchId,
                module: 'FIXED_ASSETS',
                documentType: 'FIXED_ASSET',
                documentId: $asset->asset_id,
                documentNumber: $asset->asset_code,
                action: 'CREATED',
                summary: "Fixed Asset {$asset->asset_code} ({$asset->asset_name}) capitalized — ₱" . number_format($cost, 2),
                actedBy: $actor
            );

            return $asset;
        });
    }

    /**
     * Run monthly straight-line depreciation batch and post dual-entry General Ledger journals.
     *
     * Journal:
     * DR DEPRECIATION_EXPENSE (asset's branch & department)
     * CR ACCUMULATED_DEPRECIATION
     */
    public function postMonthlyDepreciation(string $companyId, string $fiscalPeriodId, ?string $assetId = null, ?string $depreciationDate = null): array
    {
        $actor = Auth::user()->name ?? 'Asset Controller';

        return DB::transaction(function () use ($companyId, $fiscalPeriodId, $assetId, $depreciationDate, $actor) {
            $period = AcctFiscalPeriod::where('company_id', $companyId)->where('period_id', $fiscalPeriodId)->firstOrFail();
            if (in_array(strtoupper((string) $period->status), ['LOCKED', 'CLOSED'], true)) {
                throw new DomainException("Cannot post depreciation into closed or locked period [{$period->period_name}].");
            }

            $date = $depreciationDate ?: ($period->end_date ? $period->end_date->toDateString() : now()->toDateString());

            $query = AcctFixedAsset::where('company_id', $companyId)
                ->where('status', 'ACTIVE')
                ->where('net_book_value', '>', 0.0);

            if ($assetId) {
                $query->where('asset_id', $assetId);
            }

            $assets = $query->lockForUpdate()->get();
            $results = [];

            foreach ($assets as $asset) {
                $salvage = (float) $asset->salvage_value;
                $currentNbv = (float) $asset->net_book_value;
                $maxDepreciable = round($currentNbv - $salvage, 4);

                if ($maxDepreciable <= 0.0001) {
                    $asset->status = 'FULLY_DEPRECIATED';
                    $asset->save();
                    continue;
                }

                $monthlyAmount = round((float) $asset->depreciable_base / (int) $asset->useful_life_months, 4);
                $actualDepreciation = min($monthlyAmount, $maxDepreciable);

                if ($actualDepreciation <= 0.0001) {
                    continue;
                }

                $deprecNumber = $this->numberingService->generateNextNumber($companyId, 'DEP', 'DEP');
                $newNbv = round($currentNbv - $actualDepreciation, 4);

                // Post GL Journal
                $journal = $this->postingService->saveDraftOrPostJournal([
                    'company_id' => $companyId,
                    'branch_id' => $asset->branch_id,
                    'department_id' => $asset->department_id,
                    'journal_date' => $date,
                    'journal_type' => 'SYSTEM_DEPRECIATION',
                    'source_module' => 'FIXED_ASSETS',
                    'source_document_type' => 'FIXED_ASSET_DEPRECIATION',
                    'source_document_id' => $asset->asset_id,
                    'source_document_number' => $deprecNumber,
                    'currency_code' => 'PHP',
                    'exchange_rate' => 1.0,
                    'is_system_generated' => true,
                    'description' => "Monthly Depreciation for Asset {$asset->asset_code} ({$asset->asset_name})",
                    'acted_by' => $actor,
                ], [
                    [
                        'account_id' => $asset->deprec_expense_gl_account_id,
                        'branch_id' => $asset->branch_id,
                        'department_id' => $asset->department_id,
                        'transaction_debit' => $actualDepreciation,
                        'transaction_credit' => 0.0,
                        'description' => "Depreciation expense on {$asset->asset_code}",
                    ],
                    [
                        'account_id' => $asset->accum_deprec_gl_account_id,
                        'branch_id' => $asset->branch_id,
                        'department_id' => $asset->department_id,
                        'transaction_debit' => 0.0,
                        'transaction_credit' => $actualDepreciation,
                        'description' => "Accumulated depreciation on {$asset->asset_code}",
                    ],
                ], 'POSTED');

                // Create depreciation record
                $record = AcctFixedAssetDepreciation::create([
                    'depreciation_number' => $deprecNumber,
                    'asset_id' => $asset->asset_id,
                    'company_id' => $companyId,
                    'branch_id' => $asset->branch_id,
                    'fiscal_period_id' => $fiscalPeriodId,
                    'depreciation_date' => $date,
                    'depreciation_amount' => $actualDepreciation,
                    'book_value_before' => $currentNbv,
                    'book_value_after' => $newNbv,
                    'journal_id' => $journal->journal_id,
                    'status' => 'POSTED',
                    'posted_by' => $actor,
                    'posted_at' => now(),
                ]);

                // Update asset balances
                $asset->total_depreciated_amount = round((float) $asset->total_depreciated_amount + $actualDepreciation, 4);
                $asset->net_book_value = $newNbv;
                if ($newNbv <= $salvage + 0.01) {
                    $asset->status = 'FULLY_DEPRECIATED';
                }
                $asset->save();

                $results[] = $record;
            }

            return $results;
        });
    }

    /**
     * Dispose of an asset (Sale, Scrap, or Casualty Loss) and post Gain/Loss on Disposal.
     *
     * Journal:
     * DR CASH_IN_BANK (Proceeds, if > 0)
     * DR ACCUMULATED_DEPRECIATION (Full accumulated depreciation)
     * CR FIXED_ASSET_CLEARING / ASSET (Full historical acquisition cost)
     * DR/CR GAIN_LOSS_DISPOSAL (Net gain or loss)
     */
    public function disposeAsset(string $assetId, array $disposalData): AcctFixedAssetDisposal
    {
        $actor = $disposalData['acted_by'] ?? (Auth::user()->name ?? 'Asset Controller');

        return DB::transaction(function () use ($assetId, $disposalData, $actor) {
            $asset = AcctFixedAsset::where('asset_id', $assetId)->lockForUpdate()->firstOrFail();

            if ($asset->status === 'DISPOSED') {
                throw new DomainException("Asset [{$asset->asset_code}] has already been disposed.");
            }

            $companyId = (string) $asset->company_id;
            $branchId = (string) $asset->branch_id;
            $disposalDate = $disposalData['disposal_date'] ?? now()->toDateString();
            $disposalType = strtoupper((string) ($disposalData['disposal_type'] ?? 'SALE'));
            $proceeds = max(0.0, (float) ($disposalData['proceeds_amount'] ?? 0.0));

            $cost = (float) $asset->acquisition_cost;
            $accum = (float) $asset->total_depreciated_amount;
            $nbv = (float) $asset->net_book_value;
            $gainLoss = round($proceeds - $nbv, 4);

            $disposalNumber = $this->numberingService->generateNextNumber($companyId, 'DSP', 'DSP');

            // Resolve receipt account (Cash/Bank)
            $receiptAccId = $disposalData['receipt_account_id'] ?? null;
            if ($proceeds > 0 && !$receiptAccId) {
                $bankAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'CASH_IN_BANK');
                $receiptAccId = $bankAcc->account_id;
            }

            // Resolve Gain/Loss account
            $gainLossAccId = $disposalData['gain_loss_gl_account_id'] ?? null;
            if (!$gainLossAccId) {
                try {
                    $glAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'GAIN_LOSS_DISPOSAL');
                    $gainLossAccId = $glAcc->account_id;
                } catch (\Throwable $e) {
                    $glAcc = $this->postingService->resolveSystemRoleAccount($companyId, 'COGS');
                    $gainLossAccId = $glAcc->account_id;
                }
            }

            // Build GL Journal lines
            $glLines = [];

            // 1. Debit Cash/Bank if proceeds received
            if ($proceeds > 0.0001) {
                $glLines[] = [
                    'account_id' => $receiptAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => $proceeds,
                    'transaction_credit' => 0.0,
                    'description' => "Proceeds from disposal of {$asset->asset_code}",
                ];
            }

            // 2. Debit Accumulated Depreciation to clear historical depreciation
            if ($accum > 0.0001) {
                $glLines[] = [
                    'account_id' => $asset->accum_deprec_gl_account_id,
                    'branch_id' => $branchId,
                    'transaction_debit' => $accum,
                    'transaction_credit' => 0.0,
                    'description' => "Clear accumulated depreciation on {$asset->asset_code}",
                ];
            }

            // 3. Credit Asset Account to derecognize asset
            $glLines[] = [
                'account_id' => $asset->asset_gl_account_id,
                'branch_id' => $branchId,
                'transaction_debit' => 0.0,
                'transaction_credit' => $cost,
                'description' => "Derecognize asset cost for {$asset->asset_code}",
            ];

            // 4. Gain or Loss line
            if ($gainLoss > 0.0001) {
                // Gain -> Credit
                $glLines[] = [
                    'account_id' => $gainLossAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => 0.0,
                    'transaction_credit' => $gainLoss,
                    'description' => "Gain on disposal of {$asset->asset_code}",
                ];
            } elseif ($gainLoss < -0.0001) {
                // Loss -> Debit
                $glLines[] = [
                    'account_id' => $gainLossAccId,
                    'branch_id' => $branchId,
                    'transaction_debit' => abs($gainLoss),
                    'transaction_credit' => 0.0,
                    'description' => "Loss on disposal of {$asset->asset_code}",
                ];
            }

            $journal = $this->postingService->saveDraftOrPostJournal([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'department_id' => $asset->department_id,
                'journal_date' => $disposalDate,
                'journal_type' => 'SYSTEM_DEPRECIATION',
                'source_module' => 'FIXED_ASSETS',
                'source_document_type' => 'FIXED_ASSET_DISPOSAL',
                'source_document_id' => $asset->asset_id,
                'source_document_number' => $disposalNumber,
                'currency_code' => 'PHP',
                'exchange_rate' => 1.0,
                'is_system_generated' => true,
                'description' => "Asset Disposal {$disposalNumber}: {$asset->asset_name} ({$disposalType})",
                'acted_by' => $actor,
            ], $glLines, 'POSTED');

            $disposal = AcctFixedAssetDisposal::create([
                'disposal_number' => $disposalNumber,
                'asset_id' => $asset->asset_id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'disposal_date' => $disposalDate,
                'disposal_type' => $disposalType,
                'proceeds_amount' => $proceeds,
                'cost_at_disposal' => $cost,
                'accum_deprec_at_disposal' => $accum,
                'book_value_at_disposal' => $nbv,
                'gain_loss_amount' => $gainLoss,
                'buyer_name' => $disposalData['buyer_name'] ?? null,
                'receipt_account_id' => $receiptAccId,
                'gain_loss_gl_account_id' => $gainLossAccId,
                'journal_id' => $journal->journal_id,
                'status' => 'POSTED',
                'notes' => $disposalData['notes'] ?? null,
                'created_by' => $actor,
            ]);

            // Update asset status
            $asset->status = 'DISPOSED';
            $asset->net_book_value = 0.0000;
            $asset->save();

            $this->postingService->recordAudit(
                companyId: $companyId,
                branchId: $branchId,
                module: 'FIXED_ASSETS',
                documentType: 'FIXED_ASSET_DISPOSAL',
                documentId: $disposal->disposal_id,
                documentNumber: $disposal->disposal_number,
                action: 'DISPOSED',
                summary: "Disposed asset {$asset->asset_code} ({$disposalType}) — Gain/Loss: ₱" . number_format($gainLoss, 2),
                actedBy: $actor
            );

            return $disposal;
        });
    }
}
