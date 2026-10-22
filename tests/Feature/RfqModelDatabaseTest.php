<?php

namespace Tests\Feature;

use App\Models\Purchase\RfqHeader;
use App\Models\Purchase\RfqLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RfqModelDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_database_schema_has_all_required_lifecycle_columns()
    {
        $this->assertTrue(Schema::hasTable('rfq_header'), 'rfq_header table must exist');
        $this->assertTrue(Schema::hasTable('rfq_lines'), 'rfq_lines table must exist');

        // Check rfq_header columns
        $this->assertTrue(Schema::hasColumn('rfq_header', 'rfq_id'));
        $this->assertTrue(Schema::hasColumn('rfq_header', 'rfq_number'));
        $this->assertTrue(Schema::hasColumn('rfq_header', 'rfq_status'));
        $this->assertTrue(Schema::hasColumn('rfq_header', 'rejection_reason'));
        $this->assertTrue(Schema::hasColumn('rfq_header', 'revision_notes'));

        // Check rfq_lines columns
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'rfq_line_id'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'requested_quantity'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'quoted_quantity'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'target_unit_price'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'quoted_unit_price'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'quoted_line_total'));
        $this->assertTrue(Schema::hasColumn('rfq_lines', 'lead_time_days'));
    }

    public function test_rfq_header_and_line_models_relationships()
    {
        $header = new RfqHeader([
            'rfq_id' => (string) \Illuminate\Support\Str::uuid(),
            'rfq_number' => 'RFQ-TEST-001',
            'rfq_date' => now()->toDateString(),
            'rfq_status' => 'DRAFT',
            'rfq_approval_status' => 'PENDING',
        ]);

        $this->assertInstanceOf(RfqHeader::class, $header);
        $this->assertTrue(method_exists($header, 'lines'));
    }
}
