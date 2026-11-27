<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Accounting\GlJournalHeader;
use App\Models\Branch;
use App\Models\Inventory\InternalTransfer;
use App\Models\User;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InterBranchAccountingService
{
    public function __construct(
        protected AccountingSeedService $seedService,
        protected AccountingPostingService $postingService
    ) {
    }

    /**
     * Compute internal transfer pricing based on source branch's trading policy.
     *
     * @return array{pricing_mode: string, markup_pct: float, internal_unit_price: float, internal_line_total: float}
     */
    public function computeLinePricing(?Branch $sourceBranch, float $unitCost, float $baseQty): array
    {
        $mode = $sourceBranch && $sourceBranch->internal_pricing_mode === 'COST_PLUS_PCT'
            ? 'COST_PLUS_PCT'
            : 'AT_COST';

        $markupPct = ($mode === 'COST_PLUS_PCT' && $sourceBranch)
            ? max(0.0, (float) $sourceBranch->internal_markup_pct)
            : 0.0;

        $internalUnitPrice = round($unitCost * (1.0 + ($markupPct / 100.0)), 4);
        $internalLineTotal = round($baseQty * $internalUnitPrice, 2);

        return [
            'pricing_mode' => $mode,
            'markup_pct' => round($markupPct, 4),
            'internal_unit_price' => $internalUnitPrice,
            'internal_line_total' => $internalLineTotal,
        ];
    }

    /**
     * Post balanced IB-OUT journal on Source Dispatch:
     * DR 1230 Inventory In-Transit (Source) / CR 1210 Inventory Asset (Source)
     */
    public function postDispatchJournal(InternalTransfer $transfer, string $actorName): ?GlJournalHeader
    {
        $baseCost = round((float) $transfer->total_valuation, 2);
        if ($baseCost <= 0 || !$transfer->source_branch_id) {
            return null;
        }

        $sourceBranch = Branch::find((int) $transfer->source_branch_id);
        if ($sourceBranch && $sourceBranch->auto_post_interbranch_gl === false) {
            return null;
        }

        $companyId = (string) ($transfer->company_id ?: ($sourceBranch?->company_id ?: '1'));
        $this->seedService->ensureSeeded($companyId);

        $srcBranchId = (string) $transfer->source_branch_id;
        $desc = "Inter-Branch Dispatch {$transfer->transfer_number}: {$transfer->source_location} -> {$transfer->destination_location}";

        $journal = $this->postingService->saveDraftOrPostJournal([
            'company_id' => $companyId,
            'branch_id' => $srcBranchId,
            'journal_date' => now()->toDateString(),
            'journal_type' => 'INVENTORY',
            'source_module' => 'STOCK_TRANSFER',
            'source_document_type' => 'INTERBRANCH_DISPATCH',
            'source_document_id' => (string) $transfer->id,
            'source_document_number' => $transfer->transfer_number,
            'is_system_generated' => true,
            'description' => $desc,
            'acted_by' => $actorName,
        ], [
            [
                'system_role' => 'INVENTORY_IN_TRANSIT',
                'branch_id' => $srcBranchId,
                'transaction_debit' => $baseCost,
                'transaction_credit' => 0,
                'description' => "In-transit inventory dispatched ({$transfer->transfer_number})",
            ],
            [
                'system_role' => 'INVENTORY_ASSET',
                'branch_id' => $srcBranchId,
                'transaction_debit' => 0,
                'transaction_credit' => $baseCost,
                'description' => "Inventory released from {$transfer->source_location}",
            ],
        ], 'POSTED');

        $transfer->dispatch_journal_id = $journal->journal_id;
        $transfer->save();

        return $journal;
    }

    /**
     * Post balanced IB-REC multi-branch journal on Destination Receipt:
     * Destination: DR 1210 Inventory Asset (internal_billed) [+ DR 5010 COGS if shortage] / CR 2050 Due To Branches
     * Source:      DR 1170 Due From Branches / CR 1230 Inventory In-Transit (base) [+ CR 4030 Inter-Branch Recovery (markup)]
     */
    public function postReceiptJournal(
        InternalTransfer $transfer,
        float $receivedBaseCost,
        float $receivedInternalBilled,
        float $varianceBaseCost,
        string $actorName
    ): ?GlJournalHeader {
        $receivedBaseCost = round($receivedBaseCost, 2);
        $receivedInternalBilled = round(max($receivedBaseCost, $receivedInternalBilled), 2);
        $varianceBaseCost = round($varianceBaseCost, 2);
        $totalSettlementDue = round($receivedInternalBilled + $varianceBaseCost, 2);

        if ($totalSettlementDue <= 0 || !$transfer->source_branch_id || !$transfer->destination_branch_id) {
            return null;
        }

        $sourceBranch = Branch::find((int) $transfer->source_branch_id);
        if ($sourceBranch && $sourceBranch->auto_post_interbranch_gl === false) {
            return null;
        }

        $companyId = (string) ($transfer->company_id ?: ($sourceBranch?->company_id ?: '1'));
        $this->seedService->ensureSeeded($companyId);

        $srcBranchId = (string) $transfer->source_branch_id;
        $dstBranchId = (string) $transfer->destination_branch_id;
        $voucher = $transfer->receipt_number ?: $transfer->transfer_number;
        $markupGain = round($receivedInternalBilled - $receivedBaseCost, 2);
        $transitCleared = round($receivedBaseCost + $varianceBaseCost, 2);

        $lines = [];

        // Destination Branch accounting lines
        if ($receivedInternalBilled > 0) {
            $lines[] = [
                'system_role' => 'INVENTORY_ASSET',
                'branch_id' => $dstBranchId,
                'transaction_debit' => $receivedInternalBilled,
                'transaction_credit' => 0,
                'description' => "Accepted transfer stock [{$voucher}] from {$transfer->source_location}",
            ];
        }
        if ($varianceBaseCost > 0) {
            $lines[] = [
                'system_role' => 'COGS',
                'branch_id' => $dstBranchId,
                'transaction_debit' => $varianceBaseCost,
                'transaction_credit' => 0,
                'description' => "Transit shortage/damage write-off [{$voucher}]",
            ];
        }
        $lines[] = [
            'system_role' => 'INTERBRANCH_PAYABLE',
            'branch_id' => $dstBranchId,
            'partner_type' => 'BRANCH',
            'partner_id' => $srcBranchId,
            'partner_name' => $transfer->source_location,
            'transaction_debit' => 0,
            'transaction_credit' => $totalSettlementDue,
            'description' => "Due to {$transfer->source_location} ({$transfer->transfer_number})",
        ];

        // Source Branch accounting lines
        $lines[] = [
            'system_role' => 'INTERBRANCH_RECEIVABLE',
            'branch_id' => $srcBranchId,
            'partner_type' => 'BRANCH',
            'partner_id' => $dstBranchId,
            'partner_name' => $transfer->destination_location,
            'transaction_debit' => $totalSettlementDue,
            'transaction_credit' => 0,
            'description' => "Due from {$transfer->destination_location} ({$transfer->transfer_number})",
        ];
        if ($transitCleared > 0) {
            $lines[] = [
                'system_role' => 'INVENTORY_IN_TRANSIT',
                'branch_id' => $srcBranchId,
                'transaction_debit' => 0,
                'transaction_credit' => $transitCleared,
                'description' => "Clear in-transit inventory on receipt [{$voucher}]",
            ];
        }
        if ($markupGain > 0.005) {
            $lines[] = [
                'system_role' => 'INTERBRANCH_RECOVERY',
                'branch_id' => $srcBranchId,
                'transaction_debit' => 0,
                'transaction_credit' => $markupGain,
                'description' => "Internal transfer markup recovery ({$transfer->markup_pct}%)",
            ];
        }

        $journal = $this->postingService->saveDraftOrPostJournal([
            'company_id' => $companyId,
            'branch_id' => $dstBranchId,
            'journal_date' => now()->toDateString(),
            'journal_type' => 'INVENTORY',
            'source_module' => 'STOCK_TRANSFER',
            'source_document_type' => 'INTERBRANCH_RECEIPT',
            'source_document_id' => (string) $transfer->id,
            'source_document_number' => $voucher,
            'is_system_generated' => true,
            'description' => "Inter-Branch Receipt {$voucher} ({$transfer->transfer_number}): {$transfer->source_location} -> {$transfer->destination_location}",
            'acted_by' => $actorName,
        ], $lines, 'POSTED');

        $transfer->receipt_journal_id = $journal->journal_id;
        if ($transfer->settlement_status !== 'SETTLED') {
            $transfer->settlement_status = 'POSTED_TO_GL';
        }
        $transfer->save();

        return $journal;
    }

    /**
     * Live Due-To / Due-From Settlement Matrix aggregated in a single SQL GROUP BY query.
     *
     * @return array<string, mixed>
     */
    public function getSettlementMatrix(?int $companyId, ?int $activeBranchId, bool $isConsolidated): array
    {
        $query = InternalTransfer::query()
            ->whereIn('status', ['COMPLETED', 'DISCREPANCY', 'PARTIALLY_RECEIVED'])
            ->whereNotNull('source_branch_id')
            ->whereNotNull('destination_branch_id');

        if ($companyId !== null) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        if (!$isConsolidated && $activeBranchId !== null) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('source_branch_id', $activeBranchId)
                  ->orWhere('destination_branch_id', $activeBranchId);
            });
        }

        $rows = (clone $query)
            ->selectRaw("
                source_branch_id,
                source_location,
                destination_branch_id,
                destination_location,
                settlement_status,
                COUNT(*) as transfer_count,
                COALESCE(SUM(CASE WHEN total_internal_billed > 0 THEN total_internal_billed ELSE total_valuation END), 0) as billed_sum
            ")
            ->groupBy('source_branch_id', 'source_location', 'destination_branch_id', 'destination_location', 'settlement_status')
            ->get();

        $unsettledTransfers = (clone $query)
            ->where('settlement_status', '!=', 'SETTLED')
            ->orderByDesc('id')
            ->limit(100)
            ->get([
                'id',
                'transfer_number',
                'receipt_number',
                'source_branch_id',
                'source_location',
                'destination_branch_id',
                'destination_location',
                'pricing_mode',
                'markup_pct',
                'total_valuation',
                'total_internal_billed',
                'settlement_status',
                'receipt_journal_id',
                'received_at',
            ]);

        $pairs = [];
        $totalUnsettled = 0.0;
        $totalSettled = 0.0;

        foreach ($rows as $row) {
            $key = "{$row->source_branch_id}_{$row->destination_branch_id}";
            if (!isset($pairs[$key])) {
                $pairs[$key] = [
                    'source_branch_id' => (int) $row->source_branch_id,
                    'source_location' => $row->source_location,
                    'destination_branch_id' => (int) $row->destination_branch_id,
                    'destination_location' => $row->destination_location,
                    'unsettled_amount' => 0.0,
                    'settled_amount' => 0.0,
                    'transfer_count' => 0,
                ];
            }

            $amt = round((float) $row->billed_sum, 2);
            $pairs[$key]['transfer_count'] += (int) $row->transfer_count;
            if ($row->settlement_status === 'SETTLED') {
                $pairs[$key]['settled_amount'] = round($pairs[$key]['settled_amount'] + $amt, 2);
                $totalSettled += $amt;
            } else {
                $pairs[$key]['unsettled_amount'] = round($pairs[$key]['unsettled_amount'] + $amt, 2);
                $totalUnsettled += $amt;
            }
        }

        return [
            'pairs' => array_values($pairs),
            'unsettled_transfers' => $unsettledTransfers,
            'totals' => [
                'unsettled_amount' => round($totalUnsettled, 2),
                'settled_amount' => round($totalSettled, 2),
            ],
        ];
    }

    /**
     * 1-Click Inter-Branch Settlement: clears 2050 Due To Branch vs 1170 Due From Branch.
     *
     * @param  array<int, int>  $transferIds
     * @return array{settled_count: int, settled_amount: float, journal_id: ?string}
     */
    public function settleTransfers(array $transferIds, ?User $actor): array
    {
        $cleanIds = array_values(array_unique(array_map('intval', $transferIds)));
        if (empty($cleanIds)) {
            throw ValidationException::withMessages([
                'transfer_ids' => ['Select at least one received transfer to settle.'],
            ]);
        }

        $actorName = $actor ? (string) ($actor->full_name ?? $actor->name ?? $actor->username ?? 'Finance Controller') : 'Finance Controller';

        return DB::transaction(function () use ($cleanIds, $actorName) {
            $transfers = InternalTransfer::query()
                ->whereIn('id', $cleanIds)
                ->whereIn('status', ['COMPLETED', 'DISCREPANCY', 'PARTIALLY_RECEIVED'])
                ->where('settlement_status', '!=', 'SETTLED')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($transfers->isEmpty()) {
                throw ValidationException::withMessages([
                    'transfer_ids' => ['No unsettled received transfers found for the selected IDs.'],
                ]);
            }

            $totalSettled = 0.0;
            $lines = [];
            $companyId = (string) ($transfers->first()->company_id ?: '1');
            $this->seedService->ensureSeeded($companyId);

            foreach ($transfers as $trf) {
                $amt = round((float) ($trf->total_internal_billed > 0 ? $trf->total_internal_billed : $trf->total_valuation), 2);
                if ($amt <= 0 || !$trf->source_branch_id || !$trf->destination_branch_id) {
                    continue;
                }

                $totalSettled += $amt;
                $srcId = (string) $trf->source_branch_id;
                $dstId = (string) $trf->destination_branch_id;

                $lines[] = [
                    'system_role' => 'INTERBRANCH_PAYABLE',
                    'branch_id' => $dstId,
                    'partner_type' => 'BRANCH',
                    'partner_id' => $srcId,
                    'partner_name' => $trf->source_location,
                    'transaction_debit' => $amt,
                    'transaction_credit' => 0,
                    'description' => "Settle payable for {$trf->transfer_number}",
                ];
                $lines[] = [
                    'system_role' => 'INTERBRANCH_RECEIVABLE',
                    'branch_id' => $srcId,
                    'partner_type' => 'BRANCH',
                    'partner_id' => $dstId,
                    'partner_name' => $trf->destination_location,
                    'transaction_debit' => 0,
                    'transaction_credit' => $amt,
                    'description' => "Settle receivable for {$trf->transfer_number}",
                ];
            }

            $journalId = null;
            if (count($lines) >= 2 && $totalSettled > 0) {
                $refList = $transfers->pluck('transfer_number')->take(3)->implode(', ');
                $journal = $this->postingService->saveDraftOrPostJournal([
                    'company_id' => $companyId,
                    'branch_id' => (string) $transfers->first()->destination_branch_id,
                    'journal_date' => now()->toDateString(),
                    'journal_type' => 'GENERAL',
                    'source_module' => 'STOCK_TRANSFER',
                    'source_document_type' => 'INTERBRANCH_SETTLEMENT',
                    'source_document_id' => (string) $transfers->first()->id,
                    'source_document_number' => $transfers->first()->transfer_number,
                    'is_system_generated' => true,
                    'description' => "Inter-Branch Settlement Clearing ({$refList})",
                    'acted_by' => $actorName,
                ], $lines, 'POSTED');

                $journalId = $journal->journal_id;
            }

            $now = now();
            foreach ($transfers as $trf) {
                $trf->update([
                    'settlement_status' => 'SETTLED',
                    'settlement_journal_id' => $journalId,
                    'settled_at' => $now,
                    'settled_by' => $actorName,
                ]);
            }

            return [
                'settled_count' => $transfers->count(),
                'settled_amount' => round($totalSettled, 2),
                'journal_id' => $journalId,
            ];
        });
    }
}
