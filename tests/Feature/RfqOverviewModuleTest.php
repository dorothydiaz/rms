<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqOverviewModuleTest extends TestCase
{
    /**
     * Test that an authenticated user can access the RFQ directory and receive the modular table structures.
     */
    public function test_authenticated_user_can_access_rfq_module_with_modular_table_and_settings()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');

        $response->assertStatus(200);

        // Verify modular table elements and IDs exist
        $response->assertSee('id="rfqDirectoryTable"', false);
        $response->assertSee('id="rfqDirectoryTheadRow"', false);
        $response->assertSee('id="rfqTableSettingsDropdown"', false);
        $response->assertSee('id="btnRfqTableSettings"', false);

        // Verify modular CSS classes
        $response->assertSee('rfq-th-stacked', false);
        $response->assertSee('rfq-th-title', false);
        $response->assertSee('rfq-th-sub', false);
        $response->assertSee('rfq-th-settings-btn', false);
        $response->assertSee('rfq-col-resizer', false);
        $response->assertSee('rfq-dropdown-col-item', false);
        $response->assertSee('rfq-line-expansion-row', false);
        $response->assertSee('rfq-subtable', false);

        // Verify JavaScript state and LocalStorage keys
        $response->assertSee('DIRECTORY_COLUMNS', false);
        $response->assertSee('rms_rfq_directory_cols_v3', false);
        $response->assertSee('rms_rfq_directory_cols_order_v3', false);
        $response->assertSee('rms_rfq_directory_col_widths_v3', false);
        $response->assertSee('toggleRfqTableSettingsDropdown', false);
        $response->assertSee('reorderDirectoryColumns', false);
        $response->assertSee('initDirectoryColResize', false);
        $response->assertSee('toggleRfqRowExpansion', false);

        // Verify key groupings from RFQ Overview Status specification
        $response->assertSee('RFQ Info', false);
        $response->assertSee('Vendor & Contact', false);
        $response->assertSee('Submission Deadline', false);
        $response->assertSee('Payment Terms', false);
        $response->assertSee('RFQ Status', false);
        $response->assertSee('Issue Approval Status', false);
        $response->assertSee('Quantity', false);
    }
}
