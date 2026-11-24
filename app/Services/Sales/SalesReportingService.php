<?php

namespace App\Services\Sales;

use App\Models\Accounting\AcctCheckRegister;
use App\Models\Accounting\AcctCollectionReceipt;
use App\Models\Accounting\AcctCustomerMemo;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Inventory\StockOutOrder;
use App\Models\Sales\SalesOrder;
use Illuminate\Support\Facades\Schema;

class SalesReportingService
{
    public function getDailySalesReport(
        string $companyId,
        string $fromDate,
        string $toDate,
        ?string $branchId = null,
        ?string $salesperson = null
    ): array {
        $invoicesQuery = AcctSalesInvoice::with('lines')
            ->where('company_id', $companyId)
            ->whereIn('status', ['POSTED', 'PARTIAL', 'PAID'])
            ->whereDate('invoice_date', '>=', $fromDate)
            ->whereDate('invoice_date', '<=', $toDate);

        if (!empty($branchId)) {
            $invoicesQuery->where('branch_id', $branchId);
        }

        $invoices = $invoicesQuery->orderByDesc('invoice_date')->get();

        $salesOrderIds = $invoices->pluck('sales_order_id')->filter()->unique()->values()->all();
        $ordersById = !empty($salesOrderIds) && Schema::hasTable('sales_orders')
            ? SalesOrder::whereIn('sales_order_id', $salesOrderIds)->get()->keyBy('sales_order_id')
            : collect();

        if (!empty($salesperson)) {
            $invoices = $invoices->filter(function (AcctSalesInvoice $inv) use ($ordersById, $salesperson) {
                $so = $inv->sales_order_id ? $ordersById->get($inv->sales_order_id) : null;
                return $so && strcasecmp((string) $so->salesperson_name, $salesperson) === 0;
            })->values();
        }

        $stockOutRefIds = $invoices->pluck('stock_out_order_ref_id')->filter()->unique()->values()->all();
        $deliveriesByOrder = !empty($salesOrderIds)
            ? StockOutOrder::whereIn('sales_order_id', $salesOrderIds)->where('status', 'SHIPPED')->get()->groupBy('sales_order_id')
            : collect();
        $deliveriesById = !empty($stockOutRefIds)
            ? StockOutOrder::whereIn('id', $stockOutRefIds)->where('status', 'SHIPPED')->get()->keyBy('id')
            : collect();

        $netSales = round((float) $invoices->sum('subtotal_amount'), 2);
        $totalDiscounts = round((float) $invoices->sum('discount_amount'), 2);
        $grossSales = round($netSales + $totalDiscounts, 2);
        $outputVat = round((float) $invoices->sum('tax_amount'), 2);
        $totalBilled = round((float) $invoices->sum('total_amount'), 2);

        $totalCogs = 0.0;
        $countedDeliveries = [];
        foreach ($invoices as $inv) {
            if ($inv->stock_out_order_ref_id && $deliveriesById->has($inv->stock_out_order_ref_id)) {
                $del = $deliveriesById->get($inv->stock_out_order_ref_id);
                if (!isset($countedDeliveries[$del->id])) {
                    $totalCogs += (float) $del->total_cost_value;
                    $countedDeliveries[$del->id] = true;
                }
            } elseif ($inv->sales_order_id && $deliveriesByOrder->has($inv->sales_order_id)) {
                foreach ($deliveriesByOrder->get($inv->sales_order_id) as $del) {
                    if (!isset($countedDeliveries[$del->id])) {
                        $totalCogs += (float) $del->total_cost_value;
                        $countedDeliveries[$del->id] = true;
                    }
                }
            }
        }
        $totalCogs = round($totalCogs, 2);
        $grossProfit = round($netSales - $totalCogs, 2);
        $grossMarginPct = $netSales > 0.01 ? round(($grossProfit / $netSales) * 100, 1) : 0.0;

        $dailyRows = $invoices->groupBy(fn (AcctSalesInvoice $inv) => $inv->invoice_date->toDateString())
            ->map(function ($dayInvs, $date) use ($deliveriesById) {
                $net = (float) $dayInvs->sum('subtotal_amount');
                $disc = (float) $dayInvs->sum('discount_amount');
                $vat = (float) $dayInvs->sum('tax_amount');
                $cogs = 0.0;
                $seen = [];
                foreach ($dayInvs as $di) {
                    if ($di->stock_out_order_ref_id && $deliveriesById->has($di->stock_out_order_ref_id) && !isset($seen[$di->stock_out_order_ref_id])) {
                        $cogs += (float) $deliveriesById->get($di->stock_out_order_ref_id)->total_cost_value;
                        $seen[$di->stock_out_order_ref_id] = true;
                    }
                }
                return [
                    'date' => $date,
                    'invoices_count' => $dayInvs->count(),
                    'gross_sales' => round($net + $disc, 2),
                    'discounts' => round($disc, 2),
                    'net_sales' => round($net, 2),
                    'output_vat' => round($vat, 2),
                    'total_billed' => round((float) $dayInvs->sum('total_amount'), 2),
                    'cogs' => round($cogs, 2),
                    'gross_profit' => round($net - $cogs, 2),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $topCustomers = $invoices->groupBy('customer_name')
            ->map(fn ($rows, $name) => [
                'customer_name' => $name,
                'invoice_count' => $rows->count(),
                'net_sales' => round((float) $rows->sum('subtotal_amount'), 2),
                'total_billed' => round((float) $rows->sum('total_amount'), 2),
            ])
            ->sortByDesc('net_sales')
            ->take(8)
            ->values();

        $topSkus = $invoices->flatMap(fn (AcctSalesInvoice $inv) => $inv->lines)
            ->groupBy(fn ($l) => $l->item_code ?: $l->description)
            ->map(fn ($lines, $key) => [
                'item_code' => $lines->first()->item_code ?: 'CUSTOM',
                'description' => $lines->first()->description,
                'qty_sold' => round((float) $lines->sum('quantity'), 2),
                'net_sales' => round((float) $lines->sum('subtotal'), 2),
            ])
            ->sortByDesc('net_sales')
            ->take(10)
            ->values();

        return [
            'filters' => ['from' => $fromDate, 'to' => $toDate, 'branch_id' => $branchId, 'salesperson' => $salesperson],
            'kpis' => [
                'gross_sales' => $grossSales,
                'total_discounts' => $totalDiscounts,
                'net_sales' => $netSales,
                'output_vat' => $outputVat,
                'total_billed' => $totalBilled,
                'total_cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'gross_margin_pct' => $grossMarginPct,
                'invoice_count' => $invoices->count(),
                'avg_ticket' => $invoices->count() > 0 ? round($totalBilled / $invoices->count(), 2) : 0.0,
            ],
            'daily_rows' => $dailyRows,
            'top_customers' => $topCustomers,
            'top_skus' => $topSkus,
            'invoices' => $invoices->map(function (AcctSalesInvoice $inv) use ($ordersById) {
                $so = $inv->sales_order_id ? $ordersById->get($inv->sales_order_id) : null;
                return [
                    'invoice_id' => $inv->invoice_id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date->toDateString(),
                    'customer_name' => $inv->customer_name,
                    'sales_order_number' => $inv->sales_order_number,
                    'salesperson_name' => $so?->salesperson_name,
                    'subtotal_amount' => (float) $inv->subtotal_amount,
                    'discount_amount' => (float) $inv->discount_amount,
                    'tax_amount' => (float) $inv->tax_amount,
                    'total_amount' => (float) $inv->total_amount,
                    'status' => $inv->status,
                ];
            })->values(),
        ];
    }

    public function getPaymentReport(
        string $companyId,
        string $fromDate,
        string $toDate,
        ?string $method = null
    ): array {
        $receiptsQuery = AcctCollectionReceipt::with('lines.invoice')
            ->where('company_id', $companyId)
            ->whereDate('receipt_date', '>=', $fromDate)
            ->whereDate('receipt_date', '<=', $toDate);

        if (!empty($method)) {
            $receiptsQuery->where('payment_method', strtoupper(trim($method)));
        }

        $receipts = $receiptsQuery->orderByDesc('receipt_date')->get();

        $checks = AcctCheckRegister::where('company_id', $companyId)
            ->where('direction', 'INCOMING')
            ->orderByDesc('maturity_date')
            ->get();

        $pendingPdcAmount = round((float) $checks->whereIn('status', ['RECEIVED', 'DEPOSITED'])->sum('amount'), 2);
        $daifAmount = round((float) $checks->where('status', 'BOUNCED')->sum('amount'), 2);

        $memosCredit = round((float) AcctCustomerMemo::where('company_id', $companyId)
            ->where('memo_type', 'CREDIT')
            ->whereDate('memo_date', '>=', $fromDate)
            ->whereDate('memo_date', '<=', $toDate)
            ->sum('total_amount'), 2);

        $byMethod = $receipts->groupBy('payment_method')
            ->map(fn ($rows, $m) => [
                'method' => $m,
                'count' => $rows->count(),
                'gross_amount' => round((float) $rows->sum('gross_amount'), 2),
                'cwt_amount' => round((float) $rows->sum('cwt_amount'), 2),
                'net_amount' => round((float) $rows->sum('net_amount'), 2),
            ])
            ->values();

        return [
            'filters' => ['from' => $fromDate, 'to' => $toDate, 'method' => $method],
            'kpis' => [
                'total_gross_collected' => round((float) $receipts->sum('gross_amount'), 2),
                'total_cwt_2307' => round((float) $receipts->sum('cwt_amount'), 2),
                'total_net_cash' => round((float) $receipts->sum('net_amount'), 2),
                'receipt_count' => $receipts->count(),
                'pending_pdc_amount' => $pendingPdcAmount,
                'daif_bounced_amount' => $daifAmount,
                'credit_memos_issued' => $memosCredit,
            ],
            'by_method' => $byMethod,
            'receipts' => $receipts->map(function (AcctCollectionReceipt $rcp) {
                $soNums = $rcp->lines->map(fn ($l) => $l->invoice?->sales_order_number)->filter()->unique()->values()->all();
                return [
                    'receipt_id' => $rcp->receipt_id,
                    'receipt_number' => $rcp->receipt_number,
                    'receipt_date' => $rcp->receipt_date->toDateString(),
                    'customer_name' => $rcp->customer_name,
                    'payment_method' => $rcp->payment_method,
                    'reference_number' => $rcp->reference_number ?: ($rcp->check_number ?: '—'),
                    'gross_amount' => (float) $rcp->gross_amount,
                    'cwt_amount' => (float) $rcp->cwt_amount,
                    'net_amount' => (float) $rcp->net_amount,
                    'status' => $rcp->status,
                    'invoice_numbers' => $rcp->lines->pluck('invoice_number')->filter()->values()->all(),
                    'sales_order_numbers' => $soNums,
                ];
            })->values(),
            'checks' => $checks->take(20)->values(),
        ];
    }

    /**
     * Live 3-Way Order-to-Cash Reconciliation:
     * Sales Order <-> Inventory Delivery (Stock-Out) <-> Accounting AR Invoice <-> Collection Receipts.
     */
    public function getReconciliationReport(string $companyId): array
    {
        $orders = SalesOrder::with(['lines', 'deliveries', 'invoices'])
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['QUOTE_DRAFT', 'QUOTE_SENT', 'QUOTE_CONFIRMED', 'CANCELLED', 'ARCHIVED'])
            ->orderByDesc('order_date')
            ->get();

        $approvedUnshipped = [];
        $deliveredUnbilled = [];
        $billedUnpaid = [];
        $varianceAudit = [];

        foreach ($orders as $order) {
            $orderedQty = round((float) $order->lines->sum('qty_ordered'), 2);
            $deliveredQty = round((float) $order->lines->sum('qty_delivered'), 2);
            $invoicedQty = round((float) $order->lines->sum('qty_invoiced'), 2);

            $draftInvoices = $order->invoices->where('status', 'DRAFT');
            $postedInvoices = $order->invoices->whereIn('status', ['POSTED', 'PARTIAL', 'PAID']);

            // 1. Approved / Unshipped Backlog
            if (in_array(strtoupper((string) $order->status), ['APPROVED', 'PARTIALLY_DELIVERED'], true) && $deliveredQty < $orderedQty) {
                $approvedUnshipped[] = [
                    'sales_order_id' => $order->sales_order_id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'order_date' => optional($order->order_date)->toDateString(),
                    'ordered_qty' => $orderedQty,
                    'delivered_qty' => $deliveredQty,
                    'remaining_qty' => round($orderedQty - $deliveredQty, 2),
                    'gross_total' => (float) $order->gross_total,
                    'status' => $order->status,
                ];
            }

            // 2. Delivered but Unbilled Revenue Leakage (includes auto-drafted SI ready for 1-click post)
            $unbilledQty = round(max(0.0, $deliveredQty - $invoicedQty), 2);
            if ($unbilledQty > 0.01 || $draftInvoices->isNotEmpty()) {
                $firstDraft = $draftInvoices->first();
                $deliveredUnbilled[] = [
                    'sales_order_id' => $order->sales_order_id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'delivered_qty' => $deliveredQty,
                    'invoiced_qty' => $invoicedQty,
                    'unbilled_qty' => $unbilledQty,
                    'draft_invoice_id' => $firstDraft?->invoice_id,
                    'draft_invoice_number' => $firstDraft?->invoice_number,
                    'draft_amount' => $firstDraft ? (float) $firstDraft->total_amount : 0.0,
                ];
            }

            // 3. Billed Unpaid / Open AR
            $actualBalance = round((float) $postedInvoices->sum('balance_due'), 2);
            if ($actualBalance > 0.05) {
                $billedUnpaid[] = [
                    'sales_order_id' => $order->sales_order_id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'amount_billed' => round((float) $postedInvoices->sum('total_amount'), 2),
                    'amount_paid' => round((float) $postedInvoices->sum('paid_amount'), 2),
                    'balance_due' => $actualBalance,
                    'payment_status' => $order->payment_status,
                ];
            }

            // 4. Ledger / Quantity Variance Check
            $expectedBilled = round((float) $postedInvoices->sum('total_amount'), 2);
            $expectedPaid = round((float) $postedInvoices->sum('paid_amount'), 2);
            $soBilledDrift = abs(round((float) $order->amount_billed, 2) - $expectedBilled) > 0.05;
            $soPaidDrift = abs(round((float) $order->amount_paid, 2) - $expectedPaid) > 0.05;
            $overDelivered = $deliveredQty > ($orderedQty + 0.01);
            $overInvoiced = $invoicedQty > ($orderedQty + 0.01);

            if ($soBilledDrift || $soPaidDrift || $overDelivered || $overInvoiced) {
                $reasons = [];
                if ($soBilledDrift) $reasons[] = "SO billed (₱{$order->amount_billed}) != AR Posted (₱{$expectedBilled})";
                if ($soPaidDrift) $reasons[] = "SO paid (₱{$order->amount_paid}) != AR Paid (₱{$expectedPaid})";
                if ($overDelivered) $reasons[] = "Delivered ({$deliveredQty}) > Ordered ({$orderedQty})";
                if ($overInvoiced) $reasons[] = "Invoiced ({$invoicedQty}) > Ordered ({$orderedQty})";

                $varianceAudit[] = [
                    'sales_order_id' => $order->sales_order_id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'reasons' => implode(' | ', $reasons),
                ];
            }
        }

        return [
            'kpis' => [
                'total_orders_audited' => $orders->count(),
                'approved_unshipped_count' => count($approvedUnshipped),
                'delivered_unbilled_count' => count($deliveredUnbilled),
                'billed_unpaid_count' => count($billedUnpaid),
                'variance_count' => count($varianceAudit),
                'total_uncollected_ar' => round(collect($billedUnpaid)->sum('balance_due'), 2),
            ],
            'approved_unshipped' => $approvedUnshipped,
            'delivered_unbilled' => $deliveredUnbilled,
            'billed_unpaid' => $billedUnpaid,
            'variance_audit' => $varianceAudit,
        ];
    }
}
