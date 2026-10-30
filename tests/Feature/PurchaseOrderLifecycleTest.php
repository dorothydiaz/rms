<?php

namespace Tests\Feature;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_po_builder_contains_redesigned_rfq_style_progress_bar_top_banner_and_split_mockup()
    {
        $response = $this->actingAs($this->user)->get('/purchase/purchase-orders');
        $response->assertStatus(200);

        // 1. Verify Tracker KPI cluster, Batch Action Toolbar & Floating Column Settings Dropdown
        $response->assertSee('id="poBatchActionBar"', false);
        $response->assertSee('id="poTableSettingsDropdown"', false);
        $response->assertSee('id="selectAllPoCheckbox"', false);

        // 2. Verify Lifecycle Chevron Stepper & Context Ribbon in PO Builder
        $response->assertSee('id="poLifecycleStepperWrap"', false);
        $response->assertSee('id="poLifecycleProgressBar"', false);
        $response->assertSee('id="poStepIndicatorDraft"', false);
        $response->assertSee('id="poStepIndicatorApproval"', false);
        $response->assertSee('id="poStepIndicatorIssued"', false);
        $response->assertSee('id="poStepIndicatorStatus"', false);
        $response->assertSee('id="poStageBanner"', false);

        // 3. Verify 100% Width Top Identity & Operational Banner Card + Vendor Masterlist Redirect
        $response->assertSee('id="poTopBannerCard"', false);
        $response->assertSee('id="poRefDisplay"', false);
        $response->assertSee('id="poVendorCombobox"', false);
        $response->assertSee('id="poVendorComboTrigger"', false);
        $response->assertSee('id="poVendorRedirectBtn"', false);
        $response->assertSee('id="poVendorDirectLink"', false);

        // 4. Verify 70/30 Split Body & Sticky Live Document Mockup
        $response->assertSee('id="poBuilderSplitBody"', false);
        $response->assertSee('id="poMockupPanelCol"', false);
        $response->assertSee('id="poLiveMockupSheet"', false);

        // 5. Verify JS Engine contains Stage-Progressive Columns, Lifecycle Bar & Live Mockup functions
        $jsContent = file_get_contents(resource_path('js/modules/purchase-orders.js'));
        $this->assertStringContainsString('PO_ALL_COLUMNS', $jsContent);
        $this->assertStringContainsString('PO_DRAFT_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('PO_APPROVAL_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('PO_ISSUED_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('updatePoLifecycleProgressBar', $jsContent);
        $this->assertStringContainsString('transitionActivePoStage', $jsContent);
        $this->assertStringContainsString('updatePoLiveDocumentMockup', $jsContent);
        $this->assertStringContainsString('togglePoBuilderSettingsDropdown', $jsContent);
        $this->assertStringContainsString('togglePoTableSettingsDropdown', $jsContent);

        // 6. Verify CSS contains RFQ-parity layout classes
        $cssContent = file_get_contents(resource_path('css/modules/purchase-orders.css'));
        $this->assertStringContainsString('.po-lifecycle-bar-container', $cssContent);
        $this->assertStringContainsString('.po-top-banner-card', $cssContent);
        $this->assertStringContainsString('.po-builder-split-body', $cssContent);
        $this->assertStringContainsString('.po-live-mockup-container', $cssContent);
    }

    public function test_can_save_and_update_po_draft()
    {
        $poNum = 'PO-2026-' . random_int(1000, 9999);
        $payload = [
            'po_number' => $poNum,
            'vendor_name' => 'San Miguel Pure Foods Company Inc.',
            'vendor_id' => 'VND-001',
            'order_date' => now()->toDateString(),
            'expected_delivery' => now()->addDays(4)->toDateString(),
            'delivery_location' => 'Central Commissary - Receiving Dock 1',
            'special_notes' => 'Deliver before 10 AM',
            'items' => [
                [
                    'sku' => 'RAW-301',
                    'name' => 'Barista Whole Fresh Milk',
                    'unit' => 'L',
                    'quantity' => 80,
                    'unitPrice' => 85.50,
                    'specs' => 'Chilled 4C',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/orders/save-draft', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'Draft PO');

        $this->assertDatabaseHas('purchase_order_header', [
            'po_number' => $poNum,
            'status' => 'Draft PO',
        ]);
        $this->assertDatabaseHas('purchase_order_lines', [
            'sku' => 'RAW-301',
            'uom' => 'L',
            'quantity' => 80,
        ]);
    }

    public function test_po_lifecycle_fsm_transitions_and_guards()
    {
        $poNum = 'PO-2026-' . random_int(1000, 9999);
        $po = PurchaseOrder::create([
            'po_number' => $poNum,
            'vendor_name' => 'Pacific Flour Mills Inc.',
            'order_date' => now()->toDateString(),
            'status' => 'Draft PO',
            'subtotal' => 5000,
            'gross_total' => 5000,
            'balance_due' => 5000,
        ]);

        // 1. Invalid jump directly from Draft PO to Fully Received must be rejected (422)
        $resInvalid = $this->actingAs($this->user)->postJson('/purchase/api/orders/transition-status', [
            'po_number' => $poNum,
            'target_status' => 'Fully Received',
        ]);
        $resInvalid->assertStatus(422);

        // 2. Valid transition: Draft PO -> Pending Approval
        $resApproval = $this->actingAs($this->user)->postJson('/purchase/api/orders/transition-status', [
            'po_number' => $poNum,
            'target_status' => 'Pending Approval',
        ]);
        $resApproval->assertStatus(200);
        $this->assertEquals('Pending Approval', $po->fresh()->status);

        // 3. Valid transition: Pending Approval -> Approved / Issued
        $resIssued = $this->actingAs($this->user)->postJson('/purchase/api/orders/transition-status', [
            'po_number' => $poNum,
            'target_status' => 'Approved / Issued',
            'approved_by' => 'Operations Director',
        ]);
        $resIssued->assertStatus(200);
        $this->assertEquals('Approved / Issued', $po->fresh()->status);
        $this->assertEquals('Operations Director', $po->fresh()->approved_by);

        // 4. Valid transition: Approved / Issued -> Fully Received
        $resFulfilled = $this->actingAs($this->user)->postJson('/purchase/api/orders/transition-status', [
            'po_number' => $poNum,
            'target_status' => 'Fully Received',
        ]);
        $resFulfilled->assertStatus(200);
        $this->assertEquals('Fully Received', $po->fresh()->status);
    }

    public function test_rfq_to_po_conversion_returns_deep_link_and_copies_quoted_lines()
    {
        $rfqNum = 'RFQ-2026-' . random_int(1000, 9999);
        $rfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => $rfqNum,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'APPROVED',
            'rfq_payment_terms_code' => 'Net 30 Days',
        ]);

        RfqLine::create([
            'rfq_line_id' => (string) Str::uuid(),
            'rfq_id' => $rfq->rfq_id,
            'line_number' => 1,
            'item_id' => 'RAW-501',
            'requested_quantity' => 40,
            'quoted_quantity' => 40,
            'purchase_uom_code' => 'KG',
            'target_unit_price' => 250,
            'quoted_unit_price' => 235,
            'quoted_line_total' => 9400,
        ]);

        $response = $this->actingAs($this->user)->postJson('/purchase/api/rfq/convert-po', [
            'rfq_number' => $rfqNum,
            'vendor_name' => 'Highland Fresh Dairy Cooperative',
            'delivery_location' => 'Central Commissary - Receiving Dock 1',
            'approved_by' => 'Procurement Manager',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $poNumber = $response->json('data.po_number');
        $redirectUrl = $response->json('data.redirect_url');

        $this->assertNotEmpty($poNumber);
        $this->assertStringContainsString('/purchase/purchase-orders?po=' . $poNumber, $redirectUrl);

        $this->assertDatabaseHas('purchase_order_header', [
            'po_number' => $poNumber,
            'rfq_reference' => $rfqNum,
            'gross_total' => 9400.00,
        ]);
    }

    public function test_batch_delete_only_deletes_draft_pos_and_protects_issued_pos()
    {
        $draftPo = PurchaseOrder::create([
            'po_number' => 'PO-2026-9901',
            'vendor_name' => 'Vendor A',
            'status' => 'Draft PO',
            'gross_total' => 1000,
        ]);

        $issuedPo = PurchaseOrder::create([
            'po_number' => 'PO-2026-9902',
            'vendor_name' => 'Vendor B',
            'status' => 'Approved / Issued',
            'gross_total' => 2000,
        ]);

        $response = $this->actingAs($this->user)->postJson('/purchase/api/orders/batch-delete', [
            'po_numbers' => ['PO-2026-9901', 'PO-2026-9902'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('purchase_order_header', ['po_number' => 'PO-2026-9901']);
        $this->assertDatabaseHas('purchase_order_header', ['po_number' => 'PO-2026-9902']);
    }

    public function test_rfq_to_po_is_idempotent_when_po_already_created_and_supports_seeded_rfq_payload()
    {
        $seededRfqNum = 'RFQ-2026-' . random_int(5000, 8999);

        // 1. Convert an unpersisted/seeded Approved RFQ with inline items
        $resFirst = $this->actingAs($this->user)->postJson('/purchase/api/rfq/convert-po', [
            'rfq_number' => $seededRfqNum,
            'vendor_name' => 'Pacific Rim Seafoods Trading',
            'delivery_location' => 'Central Commissary - Receiving Dock 1',
            'items' => [
                [
                    'sku' => 'SEA-101',
                    'name' => 'Norwegian Atlantic Salmon',
                    'unit' => 'KG',
                    'quantity' => 15,
                    'unitPrice' => 820.00,
                ],
            ],
        ]);

        $resFirst->assertStatus(200);
        $resFirst->assertJsonPath('success', true);
        $resFirst->assertJsonPath('already_exists', false);
        $createdPoNum = $resFirst->json('data.po_number');
        $this->assertNotEmpty($createdPoNum);

        // 2. Calling convert-po again for the same RFQ must NOT create a duplicate PO; returns already_exists = true
        $resSecond = $this->actingAs($this->user)->postJson('/purchase/api/rfq/convert-po', [
            'rfq_number' => $seededRfqNum,
            'vendor_name' => 'Pacific Rim Seafoods Trading',
        ]);

        $resSecond->assertStatus(200);
        $resSecond->assertJsonPath('success', true);
        $resSecond->assertJsonPath('already_exists', true);
        $this->assertEquals($createdPoNum, $resSecond->json('data.po_number'));
        $this->assertEquals(1, PurchaseOrder::where('rfq_reference', $seededRfqNum)->count());
    }

    public function test_force_delete_removes_any_po_and_unlinks_associated_rfq()
    {
        $rfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => 'RFQ-2026-9955',
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'AWARDED',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-9955',
            'rfq_reference' => 'RFQ-2026-9955',
            'vendor_name' => 'Vendor C',
            'status' => 'Approved / Issued',
            'gross_total' => 5000,
        ]);

        $rfq->purchase_order_id = $po->id;
        $rfq->save();

        $res = $this->actingAs($this->user)->postJson('/purchase/api/orders/batch-delete', [
            'po_numbers' => ['PO-2026-9955'],
            'force' => true,
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('deleted_count', 1);

        $this->assertDatabaseMissing('purchase_order_header', ['po_number' => 'PO-2026-9955']);
        $this->assertEquals('APPROVED', $rfq->fresh()->rfq_status);
        $this->assertNull($rfq->fresh()->purchase_order_id);
    }
}

