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

        // Verify modular table elements and IDs exist in Blade view
        $response->assertSee('id="rfqDirectoryTable"', false);
        $response->assertSee('id="rfqDirectoryTheadRow"', false);
        $response->assertSee('id="rfqTableSettingsDropdown"', false);
        $response->assertSee('id="selectAllRfqCheckbox"', false);
        $response->assertSee('id="rfqBatchActionBar"', false);
        $response->assertSee('id="rfqDirectorySearch"', false);
        $response->assertSee('id="rfqHeaderFilterPopover"', false);

        // Verify JS bundle module is referenced
        $response->assertSee('request-quotations', false);

        // Verify the external JavaScript module exists and contains modular engine definitions
        $jsPath = resource_path('js/modules/request-quotations.js');
        $this->assertFileExists($jsPath);
        $jsContent = file_get_contents($jsPath);
        $this->assertStringContainsString('DIRECTORY_COLUMNS', $jsContent);
        $this->assertStringContainsString('toggleRfqTableSettingsDropdown', $jsContent);
        $this->assertStringContainsString('reorderDirectoryColumns', $jsContent);
        $this->assertStringContainsString('initDirectoryColResize', $jsContent);
        $this->assertStringContainsString('toggleRfqRowExpansion', $jsContent);
        $this->assertStringContainsString('toggleRfqRowSelection', $jsContent);
        $this->assertStringContainsString('toggleSelectAllRfqs', $jsContent);
        $this->assertStringContainsString('executeBatchDelete', $jsContent);
        $this->assertStringContainsString('executeBatchArchive', $jsContent);
        $this->assertStringContainsString('executeBatchExport', $jsContent);
        $this->assertStringContainsString('showOverlaySpinner', $jsContent);
        $this->assertStringContainsString('hideOverlaySpinner', $jsContent);
    }
}
