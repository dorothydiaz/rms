<?php

namespace Tests\Feature;

use App\Models\Accounting\AcctCustomer;
use App\Models\Accounting\AcctSalesInvoice;
use App\Models\Inventory\InventoryItem;
use App\Models\User;
use App\Services\Accounting\AccountingSeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesCustomerModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        app(AccountingSeedService::class)->ensureSeeded('1');

        $this->item = InventoryItem::create([
            'sku' => 'SKU-CUST-REF-01',
            'name' => 'Signature Roast Espresso Beans 1kg',
            'category' => 'Coffee',
            'uom' => 'BAG',
            'cost_price' => 450.00,
            'selling_price' => 1000.00,
            'current_stock' => 120.00,
            'min_stock' => 10.00,
            'max_stock' => 500.00,
            'storage_location' => 'WH-MAIN',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_detection_blocks_without_override_and_allows_with_flag(): void
    {
        $first = $this->actingAs($this->user)->postJson('/sales/api/customers/save', [
            'customer_name' => 'Pacific Crest Hospitality Inc.',
            'trade_name' => 'Pacific Crest',
            'tin' => '123-456-789-000',
            'email' => 'ap@pacificcrest.ph',
            'phone' => '+63 917 111 2222',
            'payment_terms_code' => 'NET_30',
            'credit_limit' => 250000,
            'account_stage' => 'ACTIVE',
        ]);
        $first->assertStatus(200)->assertJsonPath('success', true);
        $firstId = $first->json('data.customer_id');
        $this->assertNotEmpty($firstId);

        // Pre-flight duplicate check detects matching TIN and email
        $preflight = $this->actingAs($this->user)->postJson('/sales/api/customers/check-duplicates', [
            'customer_name' => 'Pacific Crest Subsidiary',
            'tin' => '123456789000',
            'email' => 'AP@PACIFICCREST.PH',
        ]);
        $preflight->assertStatus(200)->assertJsonPath('success', true);
        $this->assertCount(1, $preflight->json('duplicates'));
        $this->assertContains('TIN match', $preflight->json('duplicates.0.match_reasons'));
        $this->assertContains('Email match', $preflight->json('duplicates.0.match_reasons'));

        // Save without allow_duplicate -> HTTP 422 with duplicates list
        $blocked = $this->actingAs($this->user)->postJson('/sales/api/customers/save', [
            'customer_name' => 'Pacific Crest Duplicate',
            'tin' => '123-456-789-000',
            'email' => 'ap@pacificcrest.ph',
        ]);
        $blocked->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonCount(1, 'duplicates');

        // Save with allow_duplicate = true succeeds
        $allowed = $this->actingAs($this->user)->postJson('/sales/api/customers/save', [
            'customer_name' => 'Pacific Crest BGC Branch Billing',
            'tin' => '123-456-789-000',
            'email' => 'bgc@pacificcrest.ph',
            'parent_customer_id' => $firstId,
            'allow_duplicate' => true,
        ]);
        $allowed->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent_customer_id', $firstId);
    }

    public function test_hierarchy_self_parent_guard_prospect_conversion_and_archive_guard(): void
    {
        $prospectRes = $this->actingAs($this->user)->postJson('/sales/api/customers/save', [
            'customer_name' => 'Summit Bistro Holdings Corp.',
            'email' => 'procurement@summitbistro.ph',
            'account_stage' => 'PROSPECT',
            'payment_terms_code' => 'COD',
            'credit_limit' => 0,
        ]);
        $prospectRes->assertStatus(200)
            ->assertJsonPath('data.account_stage', 'PROSPECT');

        $custId = $prospectRes->json('data.customer_id');

        // Guard: cannot set self as parent_customer_id
        $selfParent = $this->actingAs($this->user)->postJson('/sales/api/customers/save', [
            'customer_id' => $custId,
            'customer_name' => 'Summit Bistro Holdings Corp.',
            'email' => 'procurement@summitbistro.ph',
            'parent_customer_id' => $custId,
        ]);
        $selfParent->assertStatus(422)->assertJsonPath('success', false);

        // Convert PROSPECT -> ACTIVE
        $conv = $this->actingAs($this->user)->postJson("/sales/api/customers/{$custId}/convert");
        $conv->assertStatus(200)
            ->assertJsonPath('data.account_stage', 'ACTIVE');
        $this->assertNotNull($conv->json('data.converted_at'));

        // Give open AR balance -> archive must be blocked (HTTP 422)
        AcctCustomer::where('customer_id', $custId)->update(['current_balance' => 15000.00]);
        $archBlocked = $this->actingAs($this->user)->postJson("/sales/api/customers/{$custId}/archive");
        $archBlocked->assertStatus(422)->assertJsonPath('success', false);

        // Clear AR balance -> archive succeeds, second call restores ACTIVE
        AcctCustomer::where('customer_id', $custId)->update(['current_balance' => 0.00]);
        $archived = $this->actingAs($this->user)->postJson("/sales/api/customers/{$custId}/archive");
        $archived->assertStatus(200)
            ->assertJsonPath('data.account_stage', 'ARCHIVED')
            ->assertJsonPath('data.is_active', false);

        $restored = $this->actingAs($this->user)->postJson("/sales/api/customers/{$custId}/archive");
        $restored->assertStatus(200)
            ->assertJsonPath('data.account_stage', 'ACTIVE')
            ->assertJsonPath('data.is_active', true);
    }

    public function test_contacts_addresses_price_lists_documents_activities_and_360_profile_analytics(): void
    {
        Storage::fake('public');

        $cust = AcctCustomer::create([
            'company_id' => '1',
            'customer_code' => 'CUST-360-01',
            'customer_name' => 'Golden Bay Resorts Corp.',
            'email' => 'finance@goldenbay.ph',
            'tin' => '888-777-666-000',
            'payment_terms_code' => 'NET_30',
            'credit_limit' => 500000.00,
            'current_balance' => 85000.00,
            'account_stage' => 'ACTIVE',
            'is_active' => true,
        ]);

        // 1. Save Primary Contact -> syncs back to acct_customers
        $contactRes = $this->actingAs($this->user)->postJson("/sales/api/customers/{$cust->customer_id}/contacts/save", [
            'full_name' => 'Elena Navarro',
            'role_title' => 'VP Procurement',
            'department' => 'Purchasing',
            'email' => 'elena@goldenbay.ph',
            'phone' => '+63 918 555 0001',
            'is_primary' => true,
            'receives_invoices' => true,
        ]);
        $contactRes->assertStatus(200)->assertJsonPath('data.customer.contacts.0.full_name', 'Elena Navarro');

        // 2. Save Shipping Address -> syncs shipping_address
        $addrRes = $this->actingAs($this->user)->postJson("/sales/api/customers/{$cust->customer_id}/addresses/save", [
            'address_type' => 'SHIPPING',
            'label' => 'Pasay Central Commissary',
            'address_line' => '88 Seaside Blvd, Bay Area',
            'city' => 'Pasay City',
            'receiving_contact' => 'Dock Supervisor Joey',
            'delivery_window' => '06:00 - 11:00',
            'is_default' => true,
        ]);
        $addrRes->assertStatus(200)->assertJsonPath('data.customer.addresses.0.label', 'Pasay Central Commissary');

        $cust->refresh();
        $this->assertSame('Elena Navarro', $cust->contact_person);
        $this->assertStringContainsString('88 Seaside Blvd', (string) $cust->shipping_address);

        // 3. Save Reference Contract Price List with Buyer SKU mapping (20% discount off 1000.00 = 800.00)
        $plRes = $this->actingAs($this->user)->postJson("/sales/api/customers/{$cust->customer_id}/price-list/save", [
            'inventory_item_id' => $this->item->id,
            'customer_sku' => 'gb-esp-1kg',
            'uom' => 'BAG',
            'contract_price' => 800.00,
            'min_qty' => 10,
        ]);
        $plRes->assertStatus(200)
            ->assertJsonPath('data.customer.price_lists.0.customer_sku', 'GB-ESP-1KG');
        $this->assertEquals(20.0, (float) $plRes->json('data.customer.price_lists.0.discount_pct'));

        // 4. Upload Compliance Documents (one Expiring Soon <= 30d, one Expired < today)
        $docSoon = $this->actingAs($this->user)->post("/sales/api/customers/{$cust->customer_id}/documents/save", [
            'doc_type' => 'TAX_EXEMPTION',
            'document_title' => 'PEZA Zero-Rated Certificate 2026',
            'reference_no' => 'PEZA-8812',
            'issue_date' => now()->subMonths(11)->toDateString(),
            'expiry_date' => now()->addDays(15)->toDateString(),
            'document_file' => UploadedFile::fake()->create('peza_cert.pdf', 120, 'application/pdf'),
        ]);
        $docSoon->assertStatus(200);
        $pezaDoc = collect($docSoon->json('data.customer.documents'))->firstWhere('reference_no', 'PEZA-8812');
        $this->assertSame('EXPIRING_SOON', $pezaDoc['expiry_status'] ?? null);

        $docExp = $this->actingAs($this->user)->postJson("/sales/api/customers/{$cust->customer_id}/documents/save", [
            'doc_type' => 'CONTRACT',
            'document_title' => 'Master Supply Agreement 2024',
            'expiry_date' => now()->subDays(5)->toDateString(),
        ]);
        $docExp->assertStatus(200);
        $msaDoc = collect($docExp->json('data.customer.documents'))->firstWhere('document_title', 'Master Supply Agreement 2024');
        $this->assertSame('EXPIRED', $msaDoc['expiry_status'] ?? null);

        // 5. Log CRM Activity
        $actRes = $this->actingAs($this->user)->postJson("/sales/api/customers/{$cust->customer_id}/activities/save", [
            'activity_type' => 'CALL',
            'subject' => 'Q4 Contract Renewal Call',
            'description' => 'Reviewed volume tier commitments.',
            'follow_up_date' => now()->addDays(7)->toDateString(),
        ]);
        $actRes->assertStatus(200);
        $callAct = collect($actRes->json('data.customer.activities'))->firstWhere('subject', 'Q4 Contract Renewal Call');
        $this->assertSame('CALL', $callAct['activity_type'] ?? null);

        // 6. Seed Aged AR Invoices (Current = 30k, 1-30d = 20k, 31-60d = 15k, 90+d = 20k => Total 85k)
        $invoicesToSeed = [
            ['offset' => 10, 'dueOffset' => 5, 'sub' => 26785.71, 'tot' => 30000.00],       // current (due in future)
            ['offset' => 35, 'dueOffset' => -15, 'sub' => 17857.14, 'tot' => 20000.00],    // 1-30 overdue
            ['offset' => 65, 'dueOffset' => -45, 'sub' => 13392.86, 'tot' => 15000.00],    // 31-60 overdue
            ['offset' => 120, 'dueOffset' => -105, 'sub' => 17857.14, 'tot' => 20000.00],  // 90+ overdue
        ];
        foreach ($invoicesToSeed as $idx => $spec) {
            AcctSalesInvoice::create([
                'invoice_id' => (string) Str::uuid(),
                'invoice_number' => 'SI-AGE-00' . ($idx + 1),
                'company_id' => '1',
                'branch_id' => 'BR-01',
                'customer_id' => $cust->customer_id,
                'customer_name' => $cust->customer_name,
                'invoice_date' => now()->addDays(-$spec['offset'])->toDateString(),
                'due_date' => now()->addDays($spec['dueOffset'])->toDateString(),
                'payment_terms_code' => 'NET_30',
                'currency_code' => 'PHP',
                'exchange_rate' => 1,
                'subtotal_amount' => $spec['sub'],
                'discount_amount' => 0,
                'tax_amount' => round($spec['tot'] - $spec['sub'], 2),
                'total_amount' => $spec['tot'],
                'paid_amount' => 0,
                'balance_due' => $spec['tot'],
                'status' => 'POSTED',
            ]);
        }

        // 7. Fetch 360° Profile & verify AR Aging buckets, SOA, Contacts, Addresses, Price List, Docs, CRM & Analytics
        $profileRes = $this->actingAs($this->user)->getJson("/sales/api/customers/{$cust->customer_id}/profile");
        $profileRes->assertStatus(200)->assertJsonPath('success', true);

        $buckets = $profileRes->json('data.ar_aging.buckets');
        $this->assertEquals(30000.0, (float) $buckets['current']);
        $this->assertEquals(20000.0, (float) $buckets['days_1_30']);
        $this->assertEquals(15000.0, (float) $buckets['days_31_60']);
        $this->assertEquals(0.0, (float) $buckets['days_61_90']);
        $this->assertEquals(20000.0, (float) $buckets['days_90_plus']);
        $this->assertEquals(85000.0, (float) $buckets['total_open_ar']);

        $soaRows = $profileRes->json('data.ar_aging.soa_rows');
        $this->assertCount(4, $soaRows);
        $this->assertEquals(85000.0, (float) end($soaRows)['running_balance']);

        $this->assertGreaterThan(75000.0, (float) $profileRes->json('data.analytics.clv_amount'));
        $this->assertSame(365, (int) $profileRes->json('data.analytics.dso_days'));

        // Directory enriched stats reflect CLV + doc expiry counters
        $dirRes = $this->actingAs($this->user)->getJson('/sales/api/customers');
        $dirRes->assertStatus(200);
        $card = collect($dirRes->json('data'))->firstWhere('customer_id', $cust->customer_id);
        $this->assertNotNull($card);
        $this->assertSame(1, (int) $card['expiring_docs_count']);
        $this->assertSame(1, (int) $card['expired_docs_count']);
    }

    public function test_customer_masterlist_blade_renders_360_shell_drawers_and_separated_tabs(): void
    {
        $res = $this->actingAs($this->user)->get('/sales/customer-masterlist');
        $res->assertStatus(200);

        $html = $res->getContent();
        $this->assertStringContainsString('id="customerDirectorySection"', $html);
        $this->assertStringContainsString('id="customerListTable"', $html);
        $this->assertStringContainsString('id="customerListTbody"', $html);
        $this->assertStringContainsString('id="custViewToggleList"', $html);
        $this->assertStringContainsString('id="custViewToggleCards"', $html);
        $this->assertStringContainsString('class="cm-filter-pill', $html);
        $this->assertStringContainsString('hr-btn-primary', $html);
        $this->assertStringContainsString('id="customerProfileWrapper" style="display:none;"', $html);
        $this->assertStringContainsString('id="customerFormDrawer"', $html);
        $this->assertStringContainsString('id="customerSoaDrawer"', $html);
        $this->assertStringContainsString('class="bom-drawer-container"', $html);
        $this->assertStringContainsString('data-uom-select', $html);
        $this->assertStringContainsString('id="custPaneOverview"', $html);
        $this->assertStringContainsString('id="custPanePipeline" style="display:none;"', $html);
        $this->assertStringContainsString('id="custPaneAr" style="display:none;"', $html);
        $this->assertStringContainsString('id="custPaneCompliance" style="display:none;"', $html);
        $this->assertStringContainsString('id="custPaneCrm" style="display:none;"', $html);
        $this->assertStringNotContainsString('rfq-modal-backdrop', $html);
    }
}
