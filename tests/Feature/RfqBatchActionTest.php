<?php

namespace Tests\Feature;

use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfqBatchActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    private function createSampleRfq(string $rfqNumber, string $status = 'DRAFT'): RfqHeader
    {
        $rfq = RfqHeader::create([
            'rfq_id' => (string) Str::uuid(),
            'rfq_number' => $rfqNumber,
            'rfq_date' => now()->toDateString(),
            'rfq_status' => $status,
            'rfq_approval_status' => 'PENDING',
            'rfq_currency_code' => 'PHP',
            'rfq_created_by' => 'Tester',
        ]);

        RfqLine::create([
            'line_id' => (string) Str::uuid(),
            'rfq_id' => $rfq->rfq_id,
            'item_id' => 'ITEM-001',
            'item_name' => 'Sample Item',
            'purchase_uom_code' => 'KG',
            'requested_quantity' => 10,
            'target_unit_price' => 100,
            'target_line_total' => 1000,
        ]);

        return $rfq;
    }

    public function test_can_batch_delete_rfqs()
    {
        $user = User::first();
        $rfq1 = $this->createSampleRfq('RFQ-DEL-001');
        $rfq2 = $this->createSampleRfq('RFQ-DEL-002');
        $rfq3 = $this->createSampleRfq('RFQ-DEL-003');

        $this->assertDatabaseCount('rfq_header', 3);
        $this->assertDatabaseCount('rfq_lines', 3);

        $response = $this->actingAs($user)->postJson('/purchase/api/rfq/batch-delete', [
            'rfq_numbers' => ['RFQ-DEL-001', 'RFQ-DEL-002'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $this->assertDatabaseMissing('rfq_header', ['rfq_number' => 'RFQ-DEL-001']);
        $this->assertDatabaseMissing('rfq_header', ['rfq_number' => 'RFQ-DEL-002']);
        $this->assertDatabaseHas('rfq_header', ['rfq_number' => 'RFQ-DEL-003']);
        $this->assertDatabaseCount('rfq_lines', 1);
    }

    public function test_can_batch_archive_rfqs()
    {
        $user = User::first();
        $rfq1 = $this->createSampleRfq('RFQ-ARC-001', 'SUBMITTED');
        $rfq2 = $this->createSampleRfq('RFQ-ARC-002', 'DRAFT');
        $rfq3 = $this->createSampleRfq('RFQ-ARC-003', 'APPROVED');

        $response = $this->actingAs($user)->postJson('/purchase/api/rfq/batch-archive', [
            'rfq_numbers' => ['RFQ-ARC-001', 'RFQ-ARC-002'],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'archived_count' => 2,
        ]);

        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => 'RFQ-ARC-001',
            'rfq_status' => 'ARCHIVED',
        ]);
        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => 'RFQ-ARC-002',
            'rfq_status' => 'ARCHIVED',
        ]);
        $this->assertDatabaseHas('rfq_header', [
            'rfq_number' => 'RFQ-ARC-003',
            'rfq_status' => 'APPROVED',
        ]);
    }

    public function test_validates_empty_rfq_numbers_on_batch_actions()
    {
        $user = User::first();

        $response1 = $this->actingAs($user)->postJson('/purchase/api/rfq/batch-delete', [
            'rfq_numbers' => [],
        ]);
        $response1->assertStatus(422);

        $response2 = $this->actingAs($user)->postJson('/purchase/api/rfq/batch-archive', [
            'rfq_numbers' => [],
        ]);
        $response2->assertStatus(422);
    }

    public function test_rfq_directory_view_has_selection_checkboxes_and_batch_bar()
    {
        $user = User::first();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');
        $response->assertStatus(200);

        // Check header select all checkbox & floating batch bar elements
        $response->assertSee('id="selectAllRfqCheckbox"', false);
        $response->assertSee('id="rfqBatchActionBar"', false);
        $response->assertSee('id="btnBatchDeleteRfq"', false);
        $response->assertSee('id="btnBatchArchiveRfq"', false);
        $response->assertSee('id="btnBatchExportRfq"', false);
        $response->assertSee('id="rfqBatchSelectedCount"', false);
    }
}
