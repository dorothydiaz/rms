@once
    @push('styles')
        @vite('resources/css/modules/accounting.css')
    @endpush

    @push('blade-data')
        <script>
            window.AccountingConfig = {
                csrfToken: @json(csrf_token()),
                activePage: @json($accountingPayload['activePage'] ?? 'dashboard'),
                routes: {
                    coaSave: @json(route('accounting.api.coa.save')),
                    coaDelete: @json(route('accounting.api.coa.delete', ['accountId' => '__ID__'])),
                    journalSave: @json(route('accounting.api.journals.save')),
                    journalTransition: @json(route('accounting.api.journals.transition', ['journalId' => '__ID__'])),
                    journalReverse: @json(route('accounting.api.journals.reverse', ['journalId' => '__ID__'])),
                    journalDelete: @json(route('accounting.api.journals.delete', ['journalId' => '__ID__'])),
                    recurringExecute: @json(route('accounting.api.recurring.execute', ['templateId' => '__ID__'])),
                    periodToggleLock: @json(route('accounting.api.periods.toggle-lock', ['periodId' => '__ID__'])),
                    configSave: @json(route('accounting.api.config.save')),
                    approvalAct: @json(route('accounting.api.approvals.act', ['requestId' => '__ID__'])),
                    attachmentStore: @json(route('accounting.api.attachments.store')),
                    // Wave 2 AP / AR / Cash & Bank Routes
                    apBillSave: @json(route('accounting.api.ap.bills.save')),
                    apPaymentSave: @json(route('accounting.api.ap.payments.save')),
                    apMemoSave: @json(route('accounting.api.ap.memos.save')),
                    apVendorLedger: @json(route('accounting.api.ap.vendor-ledger')),
                    arCustomerSave: @json(route('accounting.api.ar.customers.save')),
                    arInvoiceSave: @json(route('accounting.api.ar.invoices.save')),
                    arReceiptSave: @json(route('accounting.api.ar.receipts.save')),
                    arMemoSave: @json(route('accounting.api.ar.memos.save')),
                    arCheckBounce: @json(route('accounting.api.ar.checks.bounce', ['checkId' => '__ID__'])),
                    arCustomerLedger: @json(route('accounting.api.ar.customer-ledger')),
                    cashBankSave: @json(route('accounting.api.cash.banks.save')),
                    cashTransferSave: @json(route('accounting.api.cash.transfers.save')),
                    cashAdvanceSave: @json(route('accounting.api.cash.advances.save')),
                    cashAdvanceLiquidate: @json(route('accounting.api.cash.advances.liquidate')),
                    cashCheckClear: @json(route('accounting.api.cash.checks.clear', ['checkId' => '__ID__'])),
                    cashReconSave: @json(route('accounting.api.cash.recon.save')),
                    // Wave 3 Fixed Assets & Loans Routes
                    fixedAssetSave: @json(route('accounting.api.fixed-assets.save')),
                    fixedAssetDepreciate: @json(route('accounting.api.fixed-assets.depreciate')),
                    fixedAssetDispose: @json(route('accounting.api.fixed-assets.dispose', ['assetId' => '__ID__'])),
                    loanSave: @json(route('accounting.api.loans.save')),
                    loanPayment: @json(route('accounting.api.loans.payment', ['loanId' => '__ID__'])),
                    glInquiry: @json(route('accounting.api.gl.inquiry')),
                    recurringSave: @json(route('accounting.api.gl.recurring.save')),
                    recurringRun: @json(route('accounting.api.gl.recurring.run')),
                    periodChecklist: @json(route('accounting.api.gl.period-close.checklist')),
                    yearEndClose: @json(route('accounting.api.gl.year-end-close')),
                    // Wave 4 Tax & Financial Reports Routes
                    reportsTax: @json(route('accounting.api.reports.tax')),
                    reportsFinancial: @json(route('accounting.api.reports.financial')),
                    landedCostAllocate: @json(route('accounting.api.landed-cost.allocate')),
                },
                data: @json($accountingPayload),
            };
        </script>
    @endpush

    @push('scripts')
        @vite('resources/js/modules/accounting.js')
    @endpush
@endonce

@php
    $pendingCount = count($accountingPayload['pendingApprovals'] ?? []);
    $auditCount = count($accountingPayload['auditLogs'] ?? []);
    $attachmentCount = count($accountingPayload['attachments'] ?? []);
    $openPeriodsCount = $accountingPayload['kpis']['openPeriods'] ?? 0;
@endphp

<div class="hr-parent-header">
    <div class="acct-header-bar">
        <div>
            <h1 class="hr-parent-title">
                <i class="ph {{ $pageIcon ?? 'ph-calculator' }}"></i>
                <span>{{ $pageTitle ?? 'Accounting & Finance' }}</span>
            </h1>
            <p class="hr-parent-subtitle">
                {{ $pageSubtitle ?? 'Enterprise Financial Accounting Workspace' }}
                &nbsp;&bull;&nbsp;
                <strong>{{ $accountingPayload['companyName'] ?? 'Company' }}</strong>
                ({{ $accountingPayload['baseCurrency'] ?? 'PHP' }})
            </p>
        </div>

        <div class="acct-header-actions">
            <a href="{{ route('accounting.gl', ['tab' => 'period-close']) }}" class="acct-sys-pill" title="Open Fiscal Periods">
                <i class="ph ph-calendar-check"></i>
                <span>Open Periods</span>
                <span class="acct-sys-count">{{ $openPeriodsCount }}</span>
            </a>

            <button type="button" class="acct-sys-pill" id="btnOpenApprovalDrawer" data-acct-open="approval-drawer">
                <i class="ph ph-check-square-offset"></i>
                <span>Approval Inbox</span>
                <span class="acct-sys-count{{ $pendingCount > 0 ? ' is-alert' : '' }}" id="acctApprovalCountBadge">{{ $pendingCount }}</span>
            </button>

            <button type="button" class="acct-sys-pill" id="btnOpenAuditDrawer" data-acct-open="audit-drawer">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Audit Log</span>
                <span class="acct-sys-count" id="acctAuditCountBadge">{{ $auditCount }}</span>
            </button>

            <button type="button" class="acct-sys-pill" id="btnOpenAttachmentModal" data-acct-open="attachment-modal">
                <i class="ph ph-paperclip"></i>
                <span>Attachments</span>
                <span class="acct-sys-count" id="acctAttachmentCountBadge">{{ $attachmentCount }}</span>
            </button>
        </div>
    </div>
</div>

<!-- System-Wide Approval Inbox Drawer -->
<div class="acct-backdrop is-drawer" id="acctApprovalBackdrop" aria-hidden="true">
    <div class="acct-drawer" role="dialog" aria-label="Approval Inbox">
        <div class="acct-modal-head">
            <h3><i class="ph ph-check-square-offset"></i> Approval Inbox</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctApprovalBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <div class="acct-modal-body">
            <p class="hr-parent-subtitle" style="margin-bottom: 12px;">Pending financial documents awaiting Maker-Checker review.</p>
            <div id="acctApprovalList"></div>
        </div>
    </div>
</div>

<!-- System-Wide Audit Log Drawer -->
<div class="acct-backdrop is-drawer" id="acctAuditBackdrop" aria-hidden="true">
    <div class="acct-drawer" role="dialog" aria-label="Accounting Audit Log">
        <div class="acct-modal-head">
            <h3><i class="ph ph-clock-counter-clockwise"></i> System Audit Log</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctAuditBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <div class="acct-modal-body">
            <div class="acct-field" style="margin-bottom: 12px;">
                <input type="text" class="acct-input" id="acctAuditSearch" placeholder="Filter by doc #, action, actor, or module...">
            </div>
            <div id="acctAuditList"></div>
        </div>
    </div>
</div>

<!-- System-Wide Document Attachments Modal -->
<div class="acct-backdrop" id="acctAttachmentBackdrop" aria-hidden="true">
    <div class="acct-modal" role="dialog" aria-label="Document Attachments">
        <div class="acct-modal-head">
            <h3><i class="ph ph-paperclip"></i> Document Attachments</h3>
            <button type="button" class="acct-row-btn" data-acct-close="acctAttachmentBackdrop"><i class="ph ph-x"></i> Close</button>
        </div>
        <div class="acct-modal-body">
            <form id="acctAttachmentForm" class="acct-header-fields" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 14px;">
                <div class="acct-field">
                    <label class="acct-label">Document Type</label>
                    <select class="acct-select" name="document_type" id="attDocType">
                        <option value="GL_JOURNAL">GL Journal</option>
                        <option value="VENDOR_BILL">Vendor Bill</option>
                        <option value="SALES_INVOICE">Sales Invoice</option>
                        <option value="BANK_STATEMENT">Bank Statement</option>
                    </select>
                </div>
                <div class="acct-field">
                    <label class="acct-label">Document Ref #</label>
                    <input type="text" class="acct-input" name="document_number" id="attDocNumber" placeholder="e.g. JE-000001" required>
                </div>
                <div class="acct-field">
                    <label class="acct-label">Document ID</label>
                    <input type="text" class="acct-input" name="document_id" id="attDocId" placeholder="UUID or Reference ID" required>
                </div>
                <div class="acct-field">
                    <label class="acct-label">File Name</label>
                    <input type="text" class="acct-input" name="file_name" id="attFileName" placeholder="supporting-voucher.pdf" required>
                </div>
                <div style="grid-column: span 2; display: flex; justify-content: flex-end;">
                    <button type="submit" class="rfq-btn rfq-btn-primary">
                        <i class="ph ph-upload-simple"></i> Record Attachment
                    </button>
                </div>
            </form>

            <div class="acct-table-wrap">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>File Name</th>
                            <th>Uploaded By</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody id="acctAttachmentTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

