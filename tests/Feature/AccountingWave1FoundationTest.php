<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctFiscalPeriod;
use App\Models\Accounting\AcctNumberingGap;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\GlAccountPeriodBalance;
use App\Models\Accounting\GlJournalHeader;
use App\Models\Accounting\GlJournalLine;
use App\Models\User;
use App\Services\Accounting\AccountingNumberingService;
use App\Services\Accounting\AccountingPostingService;
use App\Services\Accounting\AccountingSeedService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountingWave1FoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_all_ten_accounting_routes_load_and_vendor_bills_redirects(): void
    {
        $routes = [
            '/accounting/dashboard',
            '/accounting/ap',
            '/accounting/ar',
            '/accounting/cash-bank',
            '/accounting/general-ledger',
            '/accounting/fixed-assets',
            '/accounting/loans',
            '/accounting/tax',
            '/accounting/financial-reports',
            '/accounting/configuration',
        ];

        foreach ($routes as $uri) {
            $res = $this->actingAs($this->user)->get($uri);
            $res->assertStatus(200);
            $res->assertSee('id="btnOpenApprovalDrawer"', false);
            $res->assertSee('id="btnOpenAuditDrawer"', false);
            $res->assertSee('id="btnOpenAttachmentModal"', false);
        }

        // Unified Purchase Vendor Bills route redirects to Accounting AP
        $redirectRes = $this->actingAs($this->user)->get('/purchase/vendor-bills');
        $redirectRes->assertRedirect(route('accounting.ap', ['tab' => 'bills']));
    }

    public function test_coa_is_company_scoped_with_all_30_protected_system_roles(): void
    {
        $this->assertFalse(
            Schema::hasColumn('chart_of_accounts', 'branch_id'),
            'chart_of_accounts must be scoped to company_id only; branch_id belongs on journal lines.'
        );

        app(AccountingSeedService::class)->ensureSeeded('1');

        // Verify every required system_role exists and is active
        $seededRoles = ChartOfAccount::where('company_id', '1')
            ->whereNotNull('system_role')
            ->pluck('system_role')
            ->all();

        foreach (ChartOfAccount::SYSTEM_ROLES as $role) {
            $this->assertContains($role, $seededRoles, "Missing required ChartOfAccount system_role: {$role}");
        }

        // Duplicate active system_role in same company must be blocked
        $this->expectException(\InvalidArgumentException::class);
        ChartOfAccount::create([
            'company_id' => '1',
            'account_code' => '1029-DUP',
            'account_name' => 'Duplicate Cash in Bank Role',
            'account_type' => 'ASSET',
            'normal_balance' => 'DEBIT',
            'cash_flow_category' => 'CASH_EQUIVALENT',
            'system_role' => 'CASH_IN_BANK',
            'is_active' => true,
        ]);
    }

    public function test_system_role_accounts_cannot_be_deleted_deactivated_or_retyped_via_api(): void
    {
        app(AccountingSeedService::class)->ensureSeeded('1');

        $apAccount = ChartOfAccount::where('company_id', '1')
            ->where('system_role', 'ACCOUNTS_PAYABLE')
            ->firstOrFail();

        // Deleting a system_role account must fail (422)
        $delRes = $this->actingAs($this->user)->deleteJson("/accounting/api/coa/{$apAccount->account_id}");
        $delRes->assertStatus(422);

        // Deactivating a system_role account must fail (422)
        $deactRes = $this->actingAs($this->user)->postJson('/accounting/api/coa/save', [
            'account_id' => $apAccount->account_id,
            'account_code' => $apAccount->account_code,
            'account_name' => $apAccount->account_name,
            'account_type' => $apAccount->account_type,
            'normal_balance' => $apAccount->normal_balance,
            'system_role' => 'ACCOUNTS_PAYABLE',
            'is_active' => false,
        ]);
        $deactRes->assertStatus(422);

        // Mutating account_type of a system_role account must fail (422)
        $retypeRes = $this->actingAs($this->user)->postJson('/accounting/api/coa/save', [
            'account_id' => $apAccount->account_id,
            'account_code' => $apAccount->account_code,
            'account_name' => $apAccount->account_name,
            'account_type' => 'EXPENSE',
            'normal_balance' => 'DEBIT',
            'system_role' => 'ACCOUNTS_PAYABLE',
            'is_active' => true,
        ]);
        $retypeRes->assertStatus(422);

        // Creating and deleting an unused non-system account succeeds
        $createRes = $this->actingAs($this->user)->postJson('/accounting/api/coa/save', [
            'account_code' => '6999',
            'account_name' => 'Custom Temp Expense',
            'account_type' => 'EXPENSE',
            'normal_balance' => 'DEBIT',
            'cash_flow_category' => 'OPERATING',
        ]);
        $createRes->assertStatus(200);
        $customId = $createRes->json('data.account_id');
        $this->assertNotEmpty($customId);

        $customDel = $this->actingAs($this->user)->deleteJson("/accounting/api/coa/{$customId}");
        $customDel->assertStatus(200);
    }

    public function test_multi_dimension_posting_fx_rounding_line_and_period_balances(): void
    {
        app(AccountingSeedService::class)->ensureSeeded('1');
        $posting = app(AccountingPostingService::class);

        $expenseAcc = $posting->resolveSystemRoleAccount('1', 'BANK_PENALTY_EXPENSE');
        $bankAcc = $posting->resolveSystemRoleAccount('1', 'CASH_IN_BANK');

        // 0.33 * 1.50 = 0.495 -> 0.50 (x2 = 1.00 base Dr) vs 0.66 * 1.50 = 0.99 base Cr => 0.01 rounding drift
        $journal = $posting->saveDraftOrPostJournal([
            'company_id' => '1',
            'branch_id' => 'BR-MAIN',
            'journal_date' => now()->toDateString(),
            'journal_type' => 'MANUAL',
            'currency_code' => 'USD',
            'exchange_rate' => 1.50,
            'description' => 'Multi-dimension FX conversion rounding test',
        ], [
            [
                'account_id' => $expenseAcc->account_id,
                'branch_id' => 'BR-NORTH',
                'department_id' => 'DEPT-KIT',
                'project_code' => 'PROJ-ALPHA',
                'transaction_debit' => 0.33,
                'description' => 'Split A',
            ],
            [
                'account_id' => $expenseAcc->account_id,
                'branch_id' => 'BR-SOUTH',
                'department_id' => 'DEPT-FOH',
                'project_code' => 'PROJ-BETA',
                'transaction_debit' => 0.33,
                'description' => 'Split B',
            ],
            [
                'account_id' => $bankAcc->account_id,
                'branch_id' => 'BR-MAIN',
                'department_id' => 'DEPT-FIN',
                'project_code' => 'PROJ-ALPHA',
                'transaction_credit' => 0.66,
                'description' => 'FX USD payout',
            ],
        ], 'POSTED');

        $journal->refresh()->load('lines');

        // Auto-appended FX rounding line must exist and bring base Dr === base Cr
        $roundingLines = $journal->lines->where('is_fx_rounding_line', true);
        $this->assertCount(1, $roundingLines);
        $this->assertEquals(
            round((float) $journal->lines->sum('base_debit'), 2),
            round((float) $journal->lines->sum('base_credit'), 2)
        );
        $this->assertEqualsWithDelta(1.00, (float) $journal->total_debit, 0.001);
        $this->assertEqualsWithDelta(1.00, (float) $journal->total_credit, 0.001);

        // Verify per-line dimensions persisted
        $northLine = $journal->lines->firstWhere('branch_id', 'BR-NORTH');
        $this->assertNotNull($northLine);
        $this->assertSame('DEPT-KIT', $northLine->department_id);
        $this->assertSame('PROJ-ALPHA', $northLine->project_code);

        // Verify gl_account_period_balances materialized rollup
        $rollup = GlAccountPeriodBalance::where('company_id', '1')
            ->where('fiscal_period_id', $journal->fiscal_period_id)
            ->where('account_id', $expenseAcc->account_id)
            ->first();
        $this->assertNotNull($rollup);
        $this->assertGreaterThanOrEqual(1.00, (float) $rollup->period_debit);
    }

    public function test_two_layer_immutability_and_canonical_reversal(): void
    {
        app(AccountingSeedService::class)->ensureSeeded('1');

        $bankAcc = ChartOfAccount::where('company_id', '1')->where('system_role', 'CASH_IN_BANK')->firstOrFail();
        $revAcc = ChartOfAccount::where('company_id', '1')->where('system_role', 'SALES_REVENUE')->firstOrFail();

        $postRes = $this->actingAs($this->user)->postJson('/accounting/api/journals/save', [
            'journal_date' => now()->toDateString(),
            'journal_type' => 'MANUAL',
            'currency_code' => 'PHP',
            'exchange_rate' => 1.0,
            'description' => 'Immutability test posted journal',
            'target_status' => 'POSTED',
            'lines' => [
                ['account_id' => $bankAcc->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 2500.00, 'description' => 'Cash In'],
                ['account_id' => $revAcc->account_id, 'branch_id' => 'BR-01', 'transaction_credit' => 2500.00, 'description' => 'Sales'],
            ],
        ]);
        $postRes->assertStatus(200);
        $journalId = $postRes->json('data.journal_id');

        // Layer 1a: API delete must return 422
        $this->actingAs($this->user)->deleteJson("/accounting/api/journals/{$journalId}")->assertStatus(422);

        // Layer 1b: Eloquent update must throw DomainException
        $header = GlJournalHeader::findOrFail($journalId);
        $eloquentBlocked = false;
        try {
            $header->description = 'Tampered narration';
            $header->save();
        } catch (DomainException $e) {
            $eloquentBlocked = true;
        }
        $this->assertTrue($eloquentBlocked, 'Eloquent updating guard must block modifying a POSTED journal.');

        // Layer 2a: Direct DB::table() line update bypassing Eloquent must fail via Database Trigger
        $lineTriggerBlocked = false;
        try {
            DB::table('gl_journal_lines')->where('journal_id', $journalId)->update(['base_debit' => 99999]);
        } catch (QueryException $e) {
            $lineTriggerBlocked = true;
        }
        $this->assertTrue($lineTriggerBlocked, 'Database trigger must block raw SQL update on POSTED gl_journal_lines.');

        // Layer 2b: Direct DB::table() header delete bypassing Eloquent must fail via Database Trigger
        $headerDeleteTriggerBlocked = false;
        try {
            DB::table('gl_journal_headers')->where('journal_id', $journalId)->delete();
        } catch (QueryException $e) {
            $headerDeleteTriggerBlocked = true;
        }
        $this->assertTrue($headerDeleteTriggerBlocked, 'Database trigger must block raw SQL delete on POSTED gl_journal_headers.');

        // Canonical Reversal flow
        $revRes = $this->actingAs($this->user)->postJson("/accounting/api/journals/{$journalId}/reverse", [
            'reason' => 'Misclassified branch entry',
            'reversal_date' => now()->toDateString(),
        ]);
        $revRes->assertStatus(200);
        $this->assertSame('REVERSAL', $revRes->json('data.journal_type'));
        $this->assertSame('POSTED', $revRes->json('data.status'));

        $header->refresh();
        $this->assertSame('REVERSED', $header->status);
        $this->assertSame($revRes->json('data.journal_id'), $header->reversal_journal_id);

        // Swapped debit/credit check on reversal lines
        $revLines = GlJournalLine::where('journal_id', $revRes->json('data.journal_id'))->get();
        $revBankLine = $revLines->firstWhere('account_id', $bankAcc->account_id);
        $this->assertEqualsWithDelta(2500.00, (float) $revBankLine->base_credit, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $revBankLine->base_debit, 0.01);
    }

    public function test_numbering_service_recycles_deleted_draft_gap_and_locked_period_blocks_posting(): void
    {
        app(AccountingSeedService::class)->ensureSeeded('1');

        $bankAcc = ChartOfAccount::where('company_id', '1')->where('system_role', 'CASH_IN_BANK')->firstOrFail();
        $expAcc = ChartOfAccount::where('company_id', '1')->where('system_role', 'BANK_PENALTY_EXPENSE')->firstOrFail();

        // Create Draft #1
        $draft1 = $this->actingAs($this->user)->postJson('/accounting/api/journals/save', [
            'journal_date' => now()->toDateString(),
            'journal_type' => 'MANUAL',
            'description' => 'Draft to test gap release',
            'target_status' => 'DRAFT',
            'lines' => [
                ['account_id' => $expAcc->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 100.00],
                ['account_id' => $bankAcc->account_id, 'branch_id' => 'BR-01', 'transaction_credit' => 100.00],
            ],
        ])->assertStatus(200);

        $firstId = $draft1->json('data.journal_id');
        $firstNum = $draft1->json('data.journal_number');

        // Delete Draft #1 -> pushes number into acct_numbering_gaps
        $this->actingAs($this->user)->deleteJson("/accounting/api/journals/{$firstId}")->assertStatus(200);
        $this->assertDatabaseHas('acct_numbering_gaps', ['reserved_number' => $firstNum, 'reused_at' => null]);

        // Next Draft reuses exact gap number
        $draft2 = $this->actingAs($this->user)->postJson('/accounting/api/journals/save', [
            'journal_date' => now()->toDateString(),
            'journal_type' => 'MANUAL',
            'description' => 'Draft reusing gap',
            'target_status' => 'DRAFT',
            'lines' => [
                ['account_id' => $expAcc->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 100.00],
                ['account_id' => $bankAcc->account_id, 'branch_id' => 'BR-01', 'transaction_credit' => 100.00],
            ],
        ])->assertStatus(200);
        $this->assertSame($firstNum, $draft2->json('data.journal_number'));

        // Lock current fiscal period and verify posting is rejected (422)
        $currentPeriod = AcctFiscalPeriod::where('company_id', '1')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->firstOrFail();

        $this->actingAs($this->user)->postJson("/accounting/api/periods/{$currentPeriod->period_id}/toggle-lock", [
            'target_status' => 'LOCKED',
        ])->assertStatus(200);

        $this->actingAs($this->user)->postJson('/accounting/api/journals/save', [
            'journal_date' => now()->toDateString(),
            'journal_type' => 'MANUAL',
            'description' => 'Should fail in locked period',
            'target_status' => 'POSTED',
            'lines' => [
                ['account_id' => $expAcc->account_id, 'branch_id' => 'BR-01', 'transaction_debit' => 50.00],
                ['account_id' => $bankAcc->account_id, 'branch_id' => 'BR-01', 'transaction_credit' => 50.00],
            ],
        ])->assertStatus(422);
    }
}
