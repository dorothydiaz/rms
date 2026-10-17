<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfqBuilderLifecycleUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rfq_builder_contains_progress_bar_and_customizable_headers()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');
        $response->assertStatus(200);

        // 1. Verify Progress Bar container and step elements exist
        $response->assertSee('id="rfqLifecycleStepperWrap"', false);
        $response->assertSee('id="rfqLifecycleProgressBar"', false);
        $response->assertSee('id="stepIndicatorDraft"', false);
        $response->assertSee('id="stepIndicatorSubmitted"', false);
        $response->assertSee('id="stepIndicatorApproval"', false);
        $response->assertSee('id="stepIndicatorStatus"', false);
        $response->assertSee('id="rfqStageBanner"', false);

        // 2. Verify JavaScript module exists and contains builder settings and lifecycle management
        $jsPath = resource_path('js/modules/request-quotations.js');
        $this->assertFileExists($jsPath);
        $jsContent = file_get_contents($jsPath);

        $this->assertStringContainsString('id="rfqBuilderSettingsDropdown"', $jsContent);
        $this->assertStringContainsString('id="builderColActiveCounter"', $jsContent);
        $this->assertStringContainsString('id="builderColCheckboxesList"', $jsContent);
        $this->assertStringContainsString('btnRfqBuilderSettings', $jsContent);

        $this->assertStringContainsString('updateLifecycleProgressBar', $jsContent);
        $this->assertStringContainsString('toggleRfqBuilderSettingsDropdown', $jsContent);
        $this->assertStringContainsString('transitionActiveRfqStage', $jsContent);
        $this->assertStringContainsString('calculateItemVariances', $jsContent);
        $this->assertStringContainsString('renderBuilderColumnDropdownChecklist', $jsContent);
        $this->assertStringContainsString('formatNumber', $jsContent);
        $this->assertStringContainsString('ensureApprovalColumnsVisible', $jsContent);
        $this->assertStringContainsString('moveToApprovalStage', $jsContent);
        $this->assertStringContainsString('handleApproverApprove', $jsContent);

        // 4. Verify stage default columns and view presets
        $this->assertStringContainsString('DRAFT_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('APPROVAL_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('applyStageDefaultColumns', $jsContent);
        $this->assertStringContainsString('ensureDraftColumnsVisible', $jsContent);

        // 5. Verify Creation of New RFQ functions & unique reference generation
        $this->assertStringContainsString('getNextUniqueRfqNumber', $jsContent);
        $this->assertStringContainsString('createNewRfq', $jsContent);
        $this->assertStringContainsString('handleBuilderTabClick', $jsContent);
    }

    public function test_rfq_directory_and_builder_data_matching_and_form_locks()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');
        $response->assertStatus(200);

        // 1. Verify Quote Validity Date and Lock Badge in Terms Card
        $response->assertSee('id="rfqQuoteValidityDate"', false);
        $response->assertSee('id="rfqTermsLockBadge"', false);

        // 2. Verify 5-card KPI Cluster covering all lifecycle stages
        $response->assertSee('id="kpiTotalRfqs"', false);
        $response->assertSee('id="kpiDraftRfqs"', false);
        $response->assertSee('id="kpiAwaitingRfqs"', false);
        $response->assertSee('id="kpiApprovalRfqs"', false);
        $response->assertSee('id="kpiAwardedRfqs"', false);

        // 3. Verify Stage Form Locks and Directory Syncing logic in JS module
        $jsPath = resource_path('js/modules/request-quotations.js');
        $this->assertFileExists($jsPath);
        $jsContent = file_get_contents($jsPath);

        $this->assertStringContainsString('applyStageFormLocks', $jsContent);
        $this->assertStringContainsString('syncRfqToDirectory', $jsContent);
        $this->assertStringContainsString('editRfqFromDirectory', $jsContent);
        $this->assertStringContainsString('getNormalizedRfqStage', $jsContent);

        // 4. Verify Server-Side configuration bridges for live data synchronization
        $response->assertSee('window.RfqConfig', false);
        $response->assertSee('initialRfqs', false);
        $response->assertSee('initialVendors', false);
        $response->assertSee('initialItems', false);
    }
}
