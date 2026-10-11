<?php

namespace Tests\Feature;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqLifecycleApiTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create();
    }

    public function test_can_save_rfq_draft()
    {
        $rfqNum = 'RFQ-TEST-' . strtoupper(Str::random(8));
        $payload = [
            'rfq_number' => $rfqNum,
            'rfq_date' => now()->toDateString(),
            'vendor_name' => 'San Miguel Pure Foods Company Inc.',
            'items' => [
                [
                    'item_id' => 'RAW-301',
                    'item_name' => 'Barista Whole Fresh Milk',
                    'purchase_uom_code' => 'L',
                    'requested_quantity' => 100,
                    'target_unit_price' => 85.00,
                    'rfq_line_notes' => 'Chilled delivery',
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/save-draft', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.rfq_status', 'DRAFT');

        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => $rfqNum,
            'rfq_status' => 'DRAFT',
        ]);
    }

    public function test_transition_status_fsm_validation()
    {
        $rfqNum = 'RFQ-TEST-' . strtoupper(Str::random(8));
        $rfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => $rfqNum,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'DRAFT',
        ]);

        // 1. DRAFT -> SUBMITTED
        $res1 = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $rfq->rfq_number,
            'target_status' => 'SUBMITTED',
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('SUBMITTED', $rfq->fresh()->rfq_status);

        // 2. Cannot directly jump from SUBMITTED to APPROVED without APPROVAL stage
        $resInvalid = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $rfq->rfq_number,
            'target_status' => 'APPROVED',
        ]);
        $resInvalid->assertStatus(422);

        // 3. SUBMITTED -> APPROVAL
        $res2 = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $rfq->rfq_number,
            'target_status' => 'APPROVAL',
        ]);
        $res2->assertStatus(200);
        $this->assertEquals('APPROVAL', $rfq->fresh()->rfq_status);

        // 4. APPROVAL -> APPROVED
        $res3 = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $rfq->rfq_number,
            'target_status' => 'APPROVED',
        ]);
        $res3->assertStatus(200);
        $this->assertEquals('APPROVED', $rfq->fresh()->rfq_status);
    }

    public function test_cannot_edit_rfq_lines_when_submitted()
    {
        $rfqNum = 'RFQ-TEST-' . strtoupper(Str::random(8));
        $rfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => $rfqNum,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'SUBMITTED',
        ]);

        $payload = [
            'rfq_number' => $rfq->rfq_number,
            'items' => [
                [
                    'item_id' => 'RAW-302',
                    'item_name' => 'Arabica Espresso Beans',
                    'purchase_uom_code' => 'KG',
                    'requested_quantity' => 50,
                    'target_unit_price' => 500.00,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/purchase/api/rfq/save-draft', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    public function test_can_convert_approved_rfq_to_purchase_order()
    {
        $rfqNum = 'RFQ-TEST-' . strtoupper(Str::random(8));
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
            'item_id' => 'RAW-301',
            'requested_quantity' => 50,
            'quoted_quantity' => 50,
            'purchase_uom_code' => 'L',
            'target_unit_price' => 85,
            'quoted_unit_price' => 85,
            'quoted_line_total' => 4250,
        ]);

        $response = $this->actingAs($this->user)->postJson('/purchase/api/rfq/convert-po', [
            'rfq_number' => $rfq->rfq_number,
            'vendor_name' => 'San Miguel Pure Foods Company Inc.',
            'delivery_location' => 'Central Commissary',
            'approved_by' => 'Procurement Manager',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $poNumber = $response->json('data.po_number');
        $this->assertNotEmpty($poNumber);

        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => $poNumber,
            'rfq_reference' => $rfq->rfq_number,
        ]);

        $this->assertEquals('AWARDED', $rfq->fresh()->rfq_status);
    }

    public function test_transition_unpersisted_rfq_auto_creates_header_and_succeeds_without_404()
    {
        $brandNewRfqNum = 'RFQ-NEW-' . strtoupper(Str::random(8));

        // Attempting to transition an unsaved RFQ to SUBMITTED must auto-create and succeed with 200 (not 404)
        $response = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $brandNewRfqNum,
            'target_status' => 'SUBMITTED',
            'vendor_name' => 'San Miguel Foods',
            'items' => [
                [
                    'item_id' => 'RAW-MILK',
                    'requested_quantity' => 20,
                    'target_unit_price' => 90,
                    'purchase_uom_code' => 'L',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => $brandNewRfqNum,
            'rfq_status' => 'SUBMITTED',
        ]);

        // Attempting to transition another unsaved RFQ directly to APPROVAL must also succeed without 404
        $anotherNewRfq = 'RFQ-APP-' . strtoupper(Str::random(8));
        $resApproval = $this->actingAs($this->user)->postJson('/purchase/api/rfq/transition-status', [
            'rfq_number' => $anotherNewRfq,
            'target_status' => 'APPROVAL',
            'items' => [
                [
                    'item_id' => 'RAW-COFFEE',
                    'requested_quantity' => 10,
                    'target_unit_price' => 300,
                ]
            ]
        ]);

        $resApproval->assertStatus(200);
        $resApproval->assertJsonPath('success', true);
        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => $anotherNewRfq,
            'rfq_status' => 'APPROVAL',
        ]);
    }
}

