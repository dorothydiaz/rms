<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AcctBir2307Certificate;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\VendorBillHeader;
use App\Models\Purchase\ProcurementVendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingTaxReportService
{
    /**
     * Resolve Quarter Date Range.
     *
     * @return array{0: string, 1: string} [startDate, endDate]
     */
    public function getQuarterDateRange(int $year, int $quarter): array
    {
        $quarter = max(1, min(4, $quarter));
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;

        $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();

        return [$startDate, $endDate];
    }

    /**
     * Generate BIR Form 2550Q (Quarterly Value-Added Tax Return) payload.
     */
    public function generate2550Q(string $companyId, int $year, int $quarter): array
    {
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);

        // 1. Sales & Output Tax from Posted AR Sales Invoices
        $invoices = AcctSalesInvoice::with('lines')
            ->where('company_id', $companyId)
            ->whereIn('status', ['POSTED', 'PAID', 'PARTIAL'])
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->get();

        $vatableSales = 0.0;
        $outputVat = 0.0;
        $zeroRatedSales = 0.0;
        $exemptSales = 0.0;
        $governmentSales = 0.0;

        foreach ($invoices as $inv) {
            foreach ($inv->lines as $line) {
                $taxCode = strtoupper((string) ($line->tax_code ?? 'VAT_12'));
                $lineSubtotal = (float) $line->subtotal;
                $lineTax = (float) $line->tax_amount;

                if (str_contains($taxCode, 'ZERO') || $taxCode === 'ZERO_RATED') {
                    $zeroRatedSales += $lineSubtotal;
                } elseif (str_contains($taxCode, 'EXEMPT')) {
                    $exemptSales += $lineSubtotal;
                } elseif (str_contains($taxCode, 'GOV')) {
                    $governmentSales += $lineSubtotal;
                } else {
                    $vatableSales += $lineSubtotal;
                    $outputVat += $lineTax;
                }
            }
        }

        // 2. Purchases & Input Tax from Posted AP Vendor Bills
        $bills = VendorBillHeader::with('lines')
            ->where('company_id', $companyId)
            ->whereIn('vendor_bill_status', ['POSTED', 'PAID', 'PARTIALLY_PAID'])
            ->whereDate('vendor_invoice_date', '>=', $startDate)
            ->whereDate('vendor_invoice_date', '<=', $endDate)
            ->get();

        $inputTaxCapitalGoods = 0.0;
        $inputTaxGoods = 0.0;
        $inputTaxServices = 0.0;
        $purchasesCapitalGoods = 0.0;
        $purchasesGoods = 0.0;
        $purchasesServices = 0.0;

        // Find Capital Asset Clearing Account ID if any
        $faClearingId = ChartOfAccount::where('company_id', $companyId)
            ->where('system_role', 'FIXED_ASSET_CLEARING')
            ->value('account_id');

        foreach ($bills as $bill) {
            $billTax = (float) $bill->bill_tax_amount;
            $billSubtotal = (float) $bill->bill_subtotal_amount;

            // Classify based on bill lines
            $isCapital = false;
            $isService = false;

            foreach ($bill->lines as $bLine) {
                if ($faClearingId && $bLine->gl_expense_account_id === $faClearingId) {
                    $isCapital = true;
                    break;
                }
                $desc = strtolower((string) ($bLine->bill_line_description ?? ''));
                if (str_contains($desc, 'service') || str_contains($desc, 'repair') || str_contains($desc, 'consult') || str_contains($desc, 'rent') || str_contains($desc, 'utility')) {
                    $isService = true;
                }
            }

            if ($isCapital) {
                $purchasesCapitalGoods += $billSubtotal;
                $inputTaxCapitalGoods += $billTax;
            } elseif ($isService) {
                $purchasesServices += $billSubtotal;
                $inputTaxServices += $billTax;
            } else {
                $purchasesGoods += $billSubtotal;
                $inputTaxGoods += $billTax;
            }
        }

        $totalAllowableInputTax = round($inputTaxCapitalGoods + $inputTaxGoods + $inputTaxServices, 4);
        $totalSales = round($vatableSales + $zeroRatedSales + $exemptSales + $governmentSales, 4);
        $netVatPayable = round($outputVat - $totalAllowableInputTax, 4);

        // 3. Creditable Withholding VAT (2307 received from customers)
        $cwtReceived = (float) AcctBir2307Certificate::where('company_id', $companyId)
            ->where('party_type', 'CUSTOMER')
            ->whereDate('period_to', '>=', $startDate)
            ->whereDate('period_to', '<=', $endDate)
            ->sum('amount_withheld');

        $totalTaxStillPayable = round($netVatPayable - $cwtReceived, 4);

        return [
            'return_period' => [
                'year' => $year,
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'part2_computation' => [
                'line_15a_vatable_sales' => round($vatableSales, 2),
                'line_15a_output_tax' => round($outputVat, 2),
                'line_15b_government_sales' => round($governmentSales, 2),
                'line_15c_zero_rated_sales' => round($zeroRatedSales, 2),
                'line_15d_exempt_sales' => round($exemptSales, 2),
                'line_15e_total_sales' => round($totalSales, 2),
                'line_15e_total_output_tax' => round($outputVat, 2),
                'line_16a_capital_goods_purchases' => round($purchasesCapitalGoods, 2),
                'line_16a_capital_goods_input_tax' => round($inputTaxCapitalGoods, 2),
                'line_16b_goods_purchases' => round($purchasesGoods, 2),
                'line_16b_goods_input_tax' => round($inputTaxGoods, 2),
                'line_16c_services_purchases' => round($purchasesServices, 2),
                'line_16c_services_input_tax' => round($inputTaxServices, 2),
                'line_16i_total_allowable_input_tax' => round($totalAllowableInputTax, 2),
                'line_19_net_vat_payable' => round($netVatPayable, 2),
                'line_20b_creditable_vat_withheld' => round($cwtReceived, 2),
                'line_22_total_tax_still_payable' => round($totalTaxStillPayable, 2),
            ],
        ];
    }

    /**
     * Generate Summary List of Sales & Purchases (SLSP).
     */
    public function generateSLSP(string $companyId, int $year, int $quarter): array
    {
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);

        // 1. SLS (Summary List of Sales)
        $invoices = AcctSalesInvoice::with(['customer', 'lines'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['POSTED', 'PAID', 'PARTIAL'])
            ->whereDate('invoice_date', '>=', $startDate)
            ->whereDate('invoice_date', '<=', $endDate)
            ->get();

        $slsGroups = [];
        foreach ($invoices as $inv) {
            $custId = $inv->customer_id ?? 'CUST-GEN';
            if (!isset($slsGroups[$custId])) {
                $slsGroups[$custId] = [
                    'tin' => $inv->customer?->tin ?? '000-000-000-000',
                    'registered_name' => $inv->customer?->customer_name ?? $inv->customer_name,
                    'trade_name' => $inv->customer?->trade_name ?? $inv->customer_name,
                    'address' => $inv->customer?->address ?? 'Metro Manila, Philippines',
                    'gross_sales' => 0.0,
                    'exempt_sales' => 0.0,
                    'zero_rated_sales' => 0.0,
                    'taxable_sales' => 0.0,
                    'output_tax' => 0.0,
                ];
            }

            foreach ($inv->lines as $line) {
                $taxCode = strtoupper((string) ($line->tax_code ?? 'VAT_12'));
                $subtotal = (float) $line->subtotal;
                $tax = (float) $line->tax_amount;
                $gross = (float) $line->total;

                $slsGroups[$custId]['gross_sales'] += $gross;
                if (str_contains($taxCode, 'EXEMPT')) {
                    $slsGroups[$custId]['exempt_sales'] += $subtotal;
                } elseif (str_contains($taxCode, 'ZERO') || $taxCode === 'ZERO_RATED') {
                    $slsGroups[$custId]['zero_rated_sales'] += $subtotal;
                } else {
                    $slsGroups[$custId]['taxable_sales'] += $subtotal;
                    $slsGroups[$custId]['output_tax'] += $tax;
                }
            }
        }

        $slsRows = array_values(array_map(function ($g) {
            return [
                'tin' => $g['tin'],
                'registered_name' => $g['registered_name'],
                'trade_name' => $g['trade_name'],
                'address' => $g['address'],
                'gross_sales' => round($g['gross_sales'], 2),
                'exempt_sales' => round($g['exempt_sales'], 2),
                'zero_rated_sales' => round($g['zero_rated_sales'], 2),
                'taxable_sales' => round($g['taxable_sales'], 2),
                'output_tax' => round($g['output_tax'], 2),
            ];
        }, $slsGroups));

        $slsTotals = [
            'gross_sales' => round(array_sum(array_column($slsRows, 'gross_sales')), 2),
            'exempt_sales' => round(array_sum(array_column($slsRows, 'exempt_sales')), 2),
            'zero_rated_sales' => round(array_sum(array_column($slsRows, 'zero_rated_sales')), 2),
            'taxable_sales' => round(array_sum(array_column($slsRows, 'taxable_sales')), 2),
            'output_tax' => round(array_sum(array_column($slsRows, 'output_tax')), 2),
        ];

        // 2. SLP (Summary List of Purchases)
        $bills = VendorBillHeader::with('lines')
            ->where('company_id', $companyId)
            ->whereIn('vendor_bill_status', ['POSTED', 'PAID', 'PARTIALLY_PAID'])
            ->whereDate('vendor_invoice_date', '>=', $startDate)
            ->whereDate('vendor_invoice_date', '<=', $endDate)
            ->get();

        $slpGroups = [];
        $faClearingId = ChartOfAccount::where('company_id', $companyId)
            ->where('system_role', 'FIXED_ASSET_CLEARING')
            ->value('account_id');

        foreach ($bills as $bill) {
            $venId = $bill->vendor_id ?? 'VEN-GEN';
            if (!isset($slpGroups[$venId])) {
                $vendorRec = null;
                if (class_exists(ProcurementVendor::class)) {
                    $vendorRec = ProcurementVendor::find($bill->vendor_id);
                }

                $slpGroups[$venId] = [
                    'tin' => $vendorRec?->tax_id ?? '000-000-000-000',
                    'registered_name' => $vendorRec?->legal_name ?? $bill->vendor_name,
                    'trade_name' => $vendorRec?->trade_name ?? $bill->vendor_name,
                    'address' => $vendorRec?->address_line1 ?? 'Metro Manila, Philippines',
                    'gross_purchases' => 0.0,
                    'exempt_purchases' => 0.0,
                    'zero_rated_purchases' => 0.0,
                    'taxable_goods' => 0.0,
                    'taxable_services' => 0.0,
                    'capital_goods' => 0.0,
                    'input_tax' => 0.0,
                ];
            }

            $bGross = (float) $bill->bill_total_amount;
            $bTax = (float) $bill->bill_tax_amount;
            $bSubtotal = (float) $bill->bill_subtotal_amount;

            $slpGroups[$venId]['gross_purchases'] += $bGross;
            $slpGroups[$venId]['input_tax'] += $bTax;

            $isCapital = false;
            $isService = false;
            foreach ($bill->lines as $bl) {
                if ($faClearingId && $bl->gl_expense_account_id === $faClearingId) {
                    $isCapital = true;
                }
                $desc = strtolower((string) ($bl->bill_line_description ?? ''));
                if (str_contains($desc, 'service') || str_contains($desc, 'repair') || str_contains($desc, 'consult') || str_contains($desc, 'rent') || str_contains($desc, 'utility')) {
                    $isService = true;
                }
            }

            if ($isCapital) {
                $slpGroups[$venId]['capital_goods'] += $bSubtotal;
            } elseif ($isService) {
                $slpGroups[$venId]['taxable_services'] += $bSubtotal;
            } else {
                $slpGroups[$venId]['taxable_goods'] += $bSubtotal;
            }
        }

        $slpRows = array_values(array_map(function ($g) {
            return [
                'tin' => $g['tin'],
                'registered_name' => $g['registered_name'],
                'trade_name' => $g['trade_name'],
                'address' => $g['address'],
                'gross_purchases' => round($g['gross_purchases'], 2),
                'exempt_purchases' => round($g['exempt_purchases'], 2),
                'zero_rated_purchases' => round($g['zero_rated_purchases'], 2),
                'taxable_goods' => round($g['taxable_goods'], 2),
                'taxable_services' => round($g['taxable_services'], 2),
                'capital_goods' => round($g['capital_goods'], 2),
                'input_tax' => round($g['input_tax'], 2),
            ];
        }, $slpGroups));

        $slpTotals = [
            'gross_purchases' => round(array_sum(array_column($slpRows, 'gross_purchases')), 2),
            'exempt_purchases' => round(array_sum(array_column($slpRows, 'exempt_purchases')), 2),
            'zero_rated_purchases' => round(array_sum(array_column($slpRows, 'zero_rated_purchases')), 2),
            'taxable_goods' => round(array_sum(array_column($slpRows, 'taxable_goods')), 2),
            'taxable_services' => round(array_sum(array_column($slpRows, 'taxable_services')), 2),
            'capital_goods' => round(array_sum(array_column($slpRows, 'capital_goods')), 2),
            'input_tax' => round(array_sum(array_column($slpRows, 'input_tax')), 2),
        ];

        return [
            'return_period' => [
                'year' => $year,
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'sls' => [
                'rows' => $slsRows,
                'totals' => $slsTotals,
            ],
            'slp' => [
                'rows' => $slpRows,
                'totals' => $slpTotals,
            ],
        ];
    }

    /**
     * Generate BIR Form 1601-EQ & QAP schedule.
     */
    public function generate1601EQ(string $companyId, int $year, int $quarter): array
    {
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);

        // Fetch all BIR 2307 certificates issued to vendors within quarter
        $certs = AcctBir2307Certificate::where('company_id', $companyId)
            ->where('party_type', 'VENDOR')
            ->whereDate('period_to', '>=', $startDate)
            ->whereDate('period_to', '<=', $endDate)
            ->get();

        $atcBreakdown = [];
        $totalTaxBase = 0.0;
        $totalTaxWithheld = 0.0;

        foreach ($certs as $cert) {
            $atc = $cert->atc_code ?: 'WC158';
            $taxBase = (float) ($cert->gross_income_payment ?: $cert->income_payment_amount);
            $withheld = (float) ($cert->amount_withheld ?: $cert->tax_withheld_amount);
            $ratePct = $taxBase > 0 ? round(($withheld / $taxBase) * 100, 2) : 1.0;

            if (!isset($atcBreakdown[$atc])) {
                $atcBreakdown[$atc] = [
                    'atc_code' => $atc,
                    'nature_of_payment' => $cert->tax_code ?: 'Income Payment to Suppliers',
                    'tax_base' => 0.0,
                    'tax_rate_pct' => $ratePct,
                    'tax_withheld' => 0.0,
                ];
            }

            $atcBreakdown[$atc]['tax_base'] += $taxBase;
            $atcBreakdown[$atc]['tax_withheld'] += $withheld;
            $totalTaxBase += $taxBase;
            $totalTaxWithheld += $withheld;
        }

        return [
            'return_period' => [
                'year' => $year,
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'schedule_1' => array_values(array_map(function ($row) {
                return [
                    'atc_code' => $row['atc_code'],
                    'nature_of_payment' => $row['nature_of_payment'],
                    'tax_base' => round($row['tax_base'], 2),
                    'tax_rate_pct' => $row['tax_rate_pct'],
                    'tax_withheld' => round($row['tax_withheld'], 2),
                ];
            }, $atcBreakdown)),
            'total_remittance_due' => round($totalTaxWithheld, 2),
            'total_tax_base' => round($totalTaxBase, 2),
        ];
    }

    /**
     * Generate QAP (Quarterly Alphanumeric Tax Code List of Payees).
     */
    public function generateQAP(string $companyId, int $year, int $quarter): array
    {
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);

        $certs = AcctBir2307Certificate::where('company_id', $companyId)
            ->where('party_type', 'VENDOR')
            ->whereDate('period_to', '>=', $startDate)
            ->whereDate('period_to', '<=', $endDate)
            ->orderBy('party_name')
            ->get();

        $rows = [];
        $seq = 1;
        foreach ($certs as $cert) {
            $taxBase = (float) ($cert->gross_income_payment ?: $cert->income_payment_amount);
            $withheld = (float) ($cert->amount_withheld ?: $cert->tax_withheld_amount);
            $ratePct = $taxBase > 0 ? round(($withheld / $taxBase) * 100, 2) : 1.0;

            $rows[] = [
                'seq_no' => $seq++,
                'tin' => $cert->payee_tin ?: ($cert->party_tin ?: '000-000-000-000'),
                'payee_name' => $cert->party_name,
                'atc_code' => $cert->atc_code ?: 'WC158',
                'nature_of_payment' => $cert->tax_code ?: 'Income payment to supplier',
                'gross_amount' => round($taxBase, 2),
                'tax_rate' => $ratePct,
                'tax_withheld' => round($withheld, 2),
                'certificate_number' => $cert->certificate_number,
            ];
        }

        return [
            'return_period' => [
                'year' => $year,
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'payees' => $rows,
            'total_gross' => round(array_sum(array_column($rows, 'gross_amount')), 2),
            'total_withheld' => round(array_sum(array_column($rows, 'tax_withheld')), 2),
        ];
    }

    /**
     * Generate SAWT (Summary Alphanumeric Withholding Tax statement for CWT received from clients).
     */
    public function generateSAWT(string $companyId, int $year, int $quarter): array
    {
        [$startDate, $endDate] = $this->getQuarterDateRange($year, $quarter);

        $certs = AcctBir2307Certificate::where('company_id', $companyId)
            ->where('party_type', 'CUSTOMER')
            ->whereDate('period_to', '>=', $startDate)
            ->whereDate('period_to', '<=', $endDate)
            ->orderBy('party_name')
            ->get();

        $rows = [];
        $seq = 1;
        foreach ($certs as $cert) {
            $taxBase = (float) ($cert->gross_income_payment ?: $cert->income_payment_amount);
            $withheld = (float) ($cert->amount_withheld ?: $cert->tax_withheld_amount);
            $ratePct = $taxBase > 0 ? round(($withheld / $taxBase) * 100, 2) : 1.0;

            $rows[] = [
                'seq_no' => $seq++,
                'tin' => $cert->party_tin ?: '000-000-000-000',
                'customer_name' => $cert->party_name,
                'atc_code' => $cert->atc_code ?: 'WC158',
                'nature_of_income' => $cert->tax_code ?: 'Sales of Goods / Services',
                'gross_sales' => round($taxBase, 2),
                'tax_rate' => $ratePct,
                'tax_withheld' => round($withheld, 2),
                'certificate_number' => $cert->certificate_number,
            ];
        }

        return [
            'return_period' => [
                'year' => $year,
                'quarter' => $quarter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'customers' => $rows,
            'total_gross' => round(array_sum(array_column($rows, 'gross_sales')), 2),
            'total_tax_withheld' => round(array_sum(array_column($rows, 'tax_withheld')), 2),
        ];
    }
}
