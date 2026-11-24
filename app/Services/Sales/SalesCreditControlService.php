<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctPaymentTerm;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalesCreditControlService
{
    public const CASH_PREPAID_TERMS = ['COD', 'CASH', 'ADVANCE', 'PREPAID', '100%_ADVANCE', 'CASH_ON_DELIVERY'];

    public function __construct(
        protected AccountingSeedService $seedService,
        protected AccountingPostingService $postingService,
        protected SalesInventoryService $inventoryService
    ) {
    }

    public function isCashOrPrepaidTerms(?string $termsCode): bool
    {
        $normalized = strtoupper(str_replace([' ', '-'], '_', trim((string) $termsCode)));

        return in_array($normalized, self::CASH_PREPAID_TERMS, true);
    }

    /**
     * Resolve payment term due days from Accounting AcctPaymentTerm catalog with clean fallbacks.
     */
    public function resolveDueDays(string $companyId, ?string $termsCode): int
    {
        $raw = strtoupper(trim((string) $termsCode));
        if ($raw === '' || $this->isCashOrPrepaidTerms($raw)) {
            return 0;
        }

        if (Schema::hasTable('acct_payment_terms')) {
            $compact = str_replace(['_', '-', ' '], '', $raw);
            $term = AcctPaymentTerm::where('company_id', $companyId)
                ->where('is_active', true)
                ->get(['term_code', 'due_days'])
                ->first(function (AcctPaymentTerm $t) use ($raw, $compact) {
                    $tc = strtoupper(trim((string) $t->term_code));
                    return $tc === $raw || str_replace(['_', '-', ' '], '', $tc) === $compact;
                });

            if ($term) {
                return max(0, (int) $term->due_days);
            }
        }

        if (preg_match('/NET[_ ]?(\d+)/', $raw, $m)) {
            return max(0, (int) $m[1]);
        }

        return 30;
    }

    /**
     * Evaluate customer credit exposure including open unbilled/undelivered Sales Orders,
     * AR open balance, overdue invoices, and explicit credit_hold flags.
     *
     * Policy (Strict Manual Review): EVERY credit-term Sales Order enters Accounting Credit Review,
     * while COD/Advance orders auto-clear unless the customer has an active credit_hold flag.
     */
    public function evaluateCustomerCredit(
        AcctCustomer $customer,
        float $orderGrossTotal,
        string $paymentTermsCode,
        ?string $excludeSalesOrderId = null
    ): array {
        $isCashTerms = $this->isCashOrPrepaidTerms($paymentTermsCode);
        $creditLimit = max(0.0, (float) $customer->credit_limit);
        $currentArBalance = max(0.0, (float) $customer->current_balance);

        $openOrdersQuery = SalesOrder::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['APPROVED', 'PARTIALLY_DELIVERED', 'DELIVERED']);

        if ($excludeSalesOrderId) {
            $openOrdersQuery->where('sales_order_id', '!=', $excludeSalesOrderId);
        }

        $openOrdersExposure = (float) $openOrdersQuery->get()->sum(function (SalesOrder $so) {
            return max(0.0, round((float) $so->gross_total - (float) $so->amount_billed, 4));
        });

        $projectedExposure = round($currentArBalance + $openOrdersExposure + $orderGrossTotal, 4);
        $availableCredit = max(0.0, round($creditLimit - $currentArBalance - $openOrdersExposure, 4));

        $graceDays = max(0, (int) ($customer->credit_grace_days ?? 15));
        $overdueCutoff = now()->startOfDay()->subDays($graceDays)->toDateString();

        $overdueInvoices = AcctSalesInvoice::where('customer_id', $customer->customer_id)
            ->whereIn('status', ['POSTED', 'PARTIAL'])
            ->where('balance_due', '>', 0.01)
            ->whereDate('due_date', '<', $overdueCutoff)
            ->get(['invoice_id', 'invoice_number', 'due_date', 'balance_due']);

        $overdueCount = $overdueInvoices->count();
        $overdueAmount = round((float) $overdueInvoices->sum('balance_due'), 4);

        $flags = [];
        $reasons = [];

        if ((bool) $customer->credit_hold) {
            $flags[] = 'CUSTOMER_ON_CREDIT_HOLD';
            $reasons[] = 'Customer account is currently flagged on Credit Hold.';
        }

        if ($overdueCount > 0) {
            $flags[] = 'OVERDUE_INVOICES';
            $reasons[] = sprintf(
                'Customer has %d overdue invoice(s) totaling ₱%s past %d-day grace period.',
                $overdueCount,
                number_format($overdueAmount, 2),
                $graceDays
            );
        }

        if (!$isCashTerms && $creditLimit <= 0.0001) {
            $flags[] = 'NO_CREDIT_LIMIT_ASSIGNED';
            $reasons[] = 'Customer has ₱0.00 credit limit configured for credit-term orders.';
        } elseif (!$isCashTerms && $projectedExposure > ($creditLimit + 0.01)) {
            $flags[] = 'EXCEEDS_CREDIT_LIMIT';
            $reasons[] = sprintf(
                'Projected exposure ₱%s exceeds credit limit ₱%s by ₱%s.',
                number_format($projectedExposure, 2),
                number_format($creditLimit, 2),
                number_format($projectedExposure - $creditLimit, 2)
            );
        }

        $breachAmount = 0.0;
        if (!$isCashTerms) {
            if ($creditLimit <= 0.0001) {
                $breachAmount = round($orderGrossTotal, 4);
            } elseif ($projectedExposure > $creditLimit) {
                $breachAmount = round($projectedExposure - $creditLimit, 4);
            }
        }

        $requiresReview = !$isCashTerms || (bool) $customer->credit_hold;
        $withinLimit = empty($flags);

        return [
            'is_cash_terms' => $isCashTerms,
            'requires_review' => $requiresReview,
            'within_limit' => $withinLimit,
            'credit_limit' => $creditLimit,
            'current_ar_balance' => $currentArBalance,
            'open_orders_exposure' => round($openOrdersExposure, 4),
            'order_gross_total' => round($orderGrossTotal, 4),
            'projected_exposure' => $projectedExposure,
            'available_credit' => $availableCredit,
            'breach_amount' => $breachAmount,
            'overdue_invoice_count' => $overdueCount,
            'overdue_amount' => $overdueAmount,
            'flags' => $flags,
            'reasons' => $reasons,
        ];
    }

    /**
     * Create an Accounting Approval Request ticket for a Sales Order on Credit Review Hold.
     */
    public function createCreditReviewTicket(SalesOrder $order, array $evaluation, string $actor): ?string
    {
        if (!Schema::hasTable('approval_requests') || !Schema::hasTable('approval_workflows')) {
            return null;
        }

        $this->seedService->ensureSeeded((string) $order->company_id);

        $wf = DB::table('approval_workflows')
            ->where('approval_workflow_code', 'ACCT_SALES_ORDER_CREDIT')
            ->first();

        if (!$wf) {
            return null;
        }

        $reqId = (string) Str::uuid();
        DB::table('approval_requests')->insert([
            'approval_request_id' => $reqId,
            'approval_workflow_id' => $wf->approval_workflow_id,
            'approval_document_type' => 'SALES_ORDER_CREDIT',
            'approval_document_id' => $order->sales_order_id,
            'approval_document_number' => $order->order_number,
            'approval_amount' => (float) $order->gross_total,
            'approval_currency_code' => $order->currency_code ?: 'PHP',
            'approval_submission_number' => 1,
            'approval_current_step_number' => 1,
            'approval_request_status' => 'PENDING',
            'approval_request_created_at' => now(),
            'approval_request_updated_at' => now(),
        ]);

        $order->approval_request_id = $reqId;
        $order->save();

        $this->postingService->recordAudit(
            companyId: (string) $order->company_id,
            branchId: (string) $order->branch_id,
            module: 'AR',
            documentType: 'SALES_ORDER_CREDIT',
            documentId: $order->sales_order_id,
            documentNumber: $order->order_number,
            action: 'CREDIT_REVIEW_QUEUED',
            summary: sprintf(
                'Sales Order %s (₱%s) queued for Accounting Credit Review (%s).',
                $order->order_number,
                number_format((float) $order->gross_total, 2),
                $evaluation['within_limit'] ? 'Within Credit Limit' : implode(', ', $evaluation['flags'])
            ),
            actedBy: $actor
        );

        return $reqId;
    }

    /**
     * Resolve an Accounting Credit Review hold on a Sales Order.
     */
    public function resolveCreditReview(
        string $salesOrderId,
        string $decision,
        string $actor,
        ?string $remarks = null,
        ?float $newCreditLimit = null,
        bool $clearCustomerHold = false
    ): SalesOrder {
        $decision = strtoupper(trim($decision));
        if (!in_array($decision, ['APPROVE', 'APPROVE_OVERRIDE', 'RETURN_TO_DRAFT', 'REJECT'], true)) {
            throw new DomainException("Unsupported credit review decision [{$decision}].");
        }

        return DB::transaction(function () use ($salesOrderId, $decision, $actor, $remarks, $newCreditLimit, $clearCustomerHold) {
            $order = SalesOrder::with('lines')
                ->where('sales_order_id', $salesOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtoupper((string) $order->status) !== 'CREDIT_HOLD') {
                throw new DomainException("Sales Order [{$order->order_number}] is not in CREDIT_HOLD status (current: {$order->status}).");
            }

            $customer = AcctCustomer::where('customer_id', $order->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($decision, ['APPROVE', 'APPROVE_OVERRIDE'], true)) {
                if ($newCreditLimit !== null && $newCreditLimit > 0) {
                    $customer->credit_limit = round($newCreditLimit, 4);
                }
                if ($clearCustomerHold && $customer->credit_hold) {
                    $customer->credit_hold = false;
                }
                $customer->save();

                $eval = $this->evaluateCustomerCredit(
                    $customer,
                    (float) $order->gross_total,
                    (string) $order->payment_terms_code,
                    $order->sales_order_id
                );

                $isOverrideRequired = !$eval['within_limit'];
                if ($isOverrideRequired && trim((string) $remarks) === '') {
                    throw new DomainException('Override remarks are mandatory when approving an order that exceeds credit limit, has overdue invoices, or is on credit hold.');
                }

                $order->credit_limit_snapshot = $eval['credit_limit'];
                $order->credit_exposure_snapshot = $eval['projected_exposure'];
                $order->credit_breach_amount = $eval['breach_amount'];
                $order->credit_review_flags_json = $eval['flags'];
                $order->credit_review_status = $isOverrideRequired ? 'APPROVED_OVERRIDE' : 'APPROVED';
                $order->credit_reviewed_by = $actor;
                $order->credit_reviewed_at = now();
                $order->credit_review_remarks = $remarks;
                $order->status = 'APPROVED';
                $order->updated_by = $actor;
                $order->save();

                $this->inventoryService->reserveOrderStock($order);

                $approvalAction = 'APPROVED';
            } elseif ($decision === 'RETURN_TO_DRAFT') {
                $order->credit_review_status = 'RETURNED';
                $order->credit_reviewed_by = $actor;
                $order->credit_reviewed_at = now();
                $order->credit_review_remarks = $remarks;
                $order->status = 'DRAFT';
                $order->updated_by = $actor;
                $order->save();

                $approvalAction = 'RETURNED';
            } else {
                $order->credit_review_status = 'REJECTED';
                $order->credit_reviewed_by = $actor;
                $order->credit_reviewed_at = now();
                $order->credit_review_remarks = $remarks;
                $order->cancellation_reason = $remarks ?: 'Rejected during Accounting Credit Review.';
                $order->status = 'CANCELLED';
                $order->updated_by = $actor;
                $order->save();

                $approvalAction = 'REJECTED';
            }

            if (!empty($order->approval_request_id) && Schema::hasTable('approval_requests')) {
                DB::table('approval_requests')
                    ->where('approval_request_id', $order->approval_request_id)
                    ->update([
                        'approval_request_status' => $approvalAction,
                        'approval_completed_at' => now(),
                        'approval_request_updated_at' => now(),
                    ]);

                if (Schema::hasTable('approval_actions')) {
                    DB::table('approval_actions')->insert([
                        'approval_action_id' => (string) Str::uuid(),
                        'approval_request_id' => $order->approval_request_id,
                        'acted_by_user_id' => null,
                        'approval_action_type' => $approvalAction,
                        'approval_action_at' => now(),
                        'approval_action_comments' => $remarks,
                    ]);
                }
            }

            $this->postingService->recordAudit(
                companyId: (string) $order->company_id,
                branchId: (string) $order->branch_id,
                module: 'AR',
                documentType: 'SALES_ORDER_CREDIT',
                documentId: $order->sales_order_id,
                documentNumber: $order->order_number,
                action: 'CREDIT_' . $approvalAction,
                summary: "Sales Order {$order->order_number} credit review resolved as {$order->credit_review_status}" . ($remarks ? " ({$remarks})" : ''),
                actedBy: $actor
            );

            return $order->fresh(['lines', 'customer', 'reservations']);
        });
    }
}
