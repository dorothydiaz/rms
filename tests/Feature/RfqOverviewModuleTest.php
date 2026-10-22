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

        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));

        // Verify modular table elements and IDs exist
        $response->assertSee('id="rfqDirectoryTable"', false);
        $response->assertSee('id="rfqDirectoryTheadRow"', false);
        $response->assertSee('id="rfqTableSettingsDropdown"', false);
        $this->assertStringContainsString('id="btnRfqTableSettings"', $jsContent);

        // Verify modular CSS classes
        $this->assertStringContainsString('rfq-th-stacked', $cssContent);
        $this->assertStringContainsString('rfq-th-title', $cssContent);
        $this->assertStringContainsString('rfq-th-sub', $cssContent);
        $this->assertStringContainsString('rfq-th-settings-btn', $cssContent);
        $this->assertStringContainsString('rfq-col-resizer', $cssContent);
        $this->assertStringContainsString('rfq-dropdown-col-item', $cssContent);
        $this->assertStringContainsString('rfq-line-expansion-row', $cssContent);
        $this->assertStringContainsString('rfq-subtable', $cssContent);

        // Verify JavaScript state and LocalStorage keys
        $this->assertStringContainsString('DIRECTORY_COLUMNS', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_cols_v3', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_cols_order_v3', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_col_widths_v3', $jsContent);
        $this->assertStringContainsString('toggleRfqTableSettingsDropdown', $jsContent);
        $this->assertStringContainsString('reorderDirectoryColumns', $jsContent);
        $this->assertStringContainsString('initDirectoryColResize', $jsContent);
        $this->assertStringContainsString('toggleRfqRowExpansion', $jsContent);

        // Verify key groupings from RFQ Overview Status specification
        $this->assertStringContainsString('RFQ Info', $jsContent);
        $this->assertStringContainsString('Vendor & Contact', $jsContent);
        $this->assertStringContainsString('Submission Deadline', $jsContent);
        $this->assertStringContainsString('Payment Terms', $jsContent);
        $this->assertStringContainsString('RFQ Status', $jsContent);
        $this->assertStringContainsString('Issue Approval Status', $jsContent);
        $this->assertStringContainsString('Quantity', $jsContent);
    }
}
