<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqOverviewModuleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that an authenticated user can access the RFQ directory and receive the modular table structures.
     */
    public function test_authenticated_user_can_access_rfq_module_with_modular_table_and_settings()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');

        $response->assertStatus(200);

        // Verify modular table elements and IDs exist in Blade HTML
        $response->assertSee('id="rfqDirectoryTable"', false);
        $response->assertSee('id="rfqDirectoryTheadRow"', false);

        // Verify key groupings from RFQ Overview Status specification in Vite JS module
        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $this->assertStringContainsString('RFQ Info', $jsContent);
        $this->assertStringContainsString('Vendor & Contact', $jsContent);
        $this->assertStringContainsString('Submission Deadline', $jsContent);
        $this->assertStringContainsString('Payment Terms', $jsContent);
        $this->assertStringContainsString('RFQ Status', $jsContent);
        $this->assertStringContainsString('Issue Approval Status', $jsContent);
        $this->assertStringContainsString('Quantity', $jsContent);

        // Verify JavaScript state, functions, and LocalStorage keys in Vite JS module
        $this->assertStringContainsString('DIRECTORY_COLUMNS', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_cols_v3', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_cols_order_v3', $jsContent);
        $this->assertStringContainsString('rms_rfq_directory_col_widths_v3', $jsContent);
        $this->assertStringContainsString('toggleRfqTableSettingsDropdown', $jsContent);
        $this->assertStringContainsString('reorderDirectoryColumns', $jsContent);
        $this->assertStringContainsString('initDirectoryColResize', $jsContent);
        $this->assertStringContainsString('toggleRfqRowExpansion', $jsContent);

        // Verify modular CSS classes in stylesheet
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));
        $this->assertStringContainsString('.rfq-th-stacked', $cssContent);
        $this->assertStringContainsString('.rfq-th-title', $cssContent);
        $this->assertStringContainsString('.rfq-th-sub', $cssContent);
        $this->assertStringContainsString('.rfq-col-resizer', $cssContent);
        $this->assertStringContainsString('.rfq-dropdown-col-item', $cssContent);
        $this->assertStringContainsString('.rfq-line-expansion-row', $cssContent);
        $this->assertStringContainsString('.rfq-subtable', $cssContent);
    }
}
