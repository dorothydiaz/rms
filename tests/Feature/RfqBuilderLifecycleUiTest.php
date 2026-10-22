<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class RfqBuilderLifecycleUiTest extends TestCase
{
    public function test_rfq_builder_contains_progress_bar_and_customizable_headers()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');
        $response->assertStatus(200);

        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));

        // 1. Verify Progress Bar container and step elements exist
        $response->assertSee('id="rfqLifecycleStepperWrap"', false);
        $response->assertSee('id="rfqLifecycleProgressBar"', false);
        $response->assertSee('id="stepIndicatorDraft"', false);
        $response->assertSee('id="stepIndicatorSubmitted"', false);
        $response->assertSee('id="stepIndicatorApproval"', false);
        $response->assertSee('id="stepIndicatorStatus"', false);
        $response->assertSee('id="rfqStageBanner"', false);

        // 2. Verify Gear Icon Dropdown and Settings Button for Builder Table in JS module
        $this->assertStringContainsString('id="btnRfqBuilderSettings"', $jsContent);
        $this->assertStringContainsString('id="rfqBuilderSettingsDropdown"', $jsContent);
        $this->assertStringContainsString('id="builderColActiveCounter"', $jsContent);
        $this->assertStringContainsString('id="builderColCheckboxesList"', $jsContent);

        // 3. Verify JavaScript functions for lifecycle and column management
        $this->assertStringContainsString('updateLifecycleProgressBar', $jsContent);
        $this->assertStringContainsString('toggleRfqBuilderSettingsDropdown', $jsContent);
        $this->assertStringContainsString('transitionActiveRfqStage', $jsContent);
        $this->assertStringContainsString('calculateItemVariances', $jsContent);
        $this->assertStringContainsString('renderBuilderTableHeader', $jsContent);
        $this->assertStringContainsString('rms_rfq_builder_cols_v4', $jsContent);
        $this->assertStringContainsString('formatNumber', $jsContent);
        $this->assertStringContainsString('ensureApprovalColumnsVisible', $jsContent);
        $this->assertStringContainsString('moveToApprovalStage', $jsContent);
        $this->assertStringContainsString('handleApproverApprove', $jsContent);

        // 4. Verify stage default columns and view preset buttons
        $this->assertStringContainsString('DRAFT_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('APPROVAL_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('applyStageDefaultColumns', $jsContent);
        $this->assertStringContainsString('ensureDraftColumnsVisible', $jsContent);
        $this->assertStringContainsString('rms_rfq_builder_cols_v6', $jsContent);
        $this->assertStringContainsString('Draft View', $jsContent);
        $this->assertStringContainsString('Approval View', $jsContent);
        $this->assertStringContainsString('Default View', $jsContent);

        // 5. Verify 30% left / 70% right grid width distribution with sidebar expansion
        $this->assertStringContainsString('minmax(260px, 30%) minmax(0, 1fr)', $cssContent);
        $this->assertStringContainsString('#app-sidebar.collapsed ~ .main-content .rfq-builder-workspace-grid', $cssContent);

        // 6. Verify Creation of New RFQ functions & unique reference generation
        $this->assertStringContainsString('getNextUniqueRfqNumber', $jsContent);
        $this->assertStringContainsString('createNewRfq', $jsContent);
        $this->assertStringContainsString('handleBuilderTabClick', $jsContent);
        $this->assertStringContainsString('rms_last_rfq_counter', $jsContent);
    }

    public function test_rfq_directory_and_builder_data_matching_and_form_locks()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/purchase/request-quotations');
        $response->assertStatus(200);

        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));

        // 1. Verify Quote Validity Date and Lock Badge in Terms Card
        $response->assertSee('id="rfqQuoteValidityDate"', false);
        $response->assertSee('id="rfqTermsLockBadge"', false);

        // 2. Verify 5-card KPI Cluster covering all lifecycle stages
        $response->assertSee('id="kpiTotalRfqs"', false);
        $response->assertSee('id="kpiDraftRfqs"', false);
        $response->assertSee('id="kpiAwaitingRfqs"', false);
        $response->assertSee('id="kpiApprovalRfqs"', false);
        $response->assertSee('id="kpiAwardedRfqs"', false);

        // 3. Verify Stage Form Locks and Directory Syncing logic
        $this->assertStringContainsString('applyStageFormLocks', $jsContent);
        $this->assertStringContainsString('syncRfqToDirectory', $jsContent);
        $this->assertStringContainsString('editRfqFromDirectory', $jsContent);
        $this->assertStringContainsString('getNormalizedRfqStage', $jsContent);

        // 4. Verify Server-Side bridges for live data synchronization
        $this->assertStringContainsString('window.__SERVER_RFQS__', $jsContent);
        $this->assertStringContainsString('window.__SERVER_VENDORS__', $jsContent);
        $this->assertStringContainsString('window.__SERVER_ITEMS__', $jsContent);

        // 5. Verify status pill CSS rules exist for all lifecycle statuses
        $this->assertStringContainsString('.status-pill.approved', $cssContent);
        $this->assertStringContainsString('.status-pill.submitted', $cssContent);
        $this->assertStringContainsString('.status-pill.approval', $cssContent);
    }
}
