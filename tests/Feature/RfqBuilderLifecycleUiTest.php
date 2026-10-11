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

        // 1. Verify Progress Bar container and step elements exist
        $response->assertSee('id="rfqLifecycleStepperWrap"', false);
        $response->assertSee('id="rfqLifecycleProgressBar"', false);
        $response->assertSee('id="stepIndicatorDraft"', false);
        $response->assertSee('id="stepIndicatorSubmitted"', false);
        $response->assertSee('id="stepIndicatorApproval"', false);
        $response->assertSee('id="stepIndicatorStatus"', false);
        $response->assertSee('id="rfqStageBanner"', false);

        // 2. Verify Gear Icon Dropdown and Settings Button for Builder Table
        $response->assertSee('id="btnRfqBuilderSettings"', false);
        $response->assertSee('id="rfqBuilderSettingsDropdown"', false);
        $response->assertSee('id="builderColActiveCounter"', false);
        $response->assertSee('id="builderColCheckboxesList"', false);

        // 3. Verify JavaScript functions for lifecycle and column management
        $response->assertSee('updateLifecycleProgressBar', false);
        $response->assertSee('toggleRfqBuilderSettingsDropdown', false);
        $response->assertSee('transitionActiveRfqStage', false);
        $response->assertSee('calculateItemVariances', false);
        $response->assertSee('renderBuilderTableHeader', false);
        $response->assertSee('rms_rfq_builder_cols_v4', false);
        $response->assertSee('formatNumber', false);
        $response->assertSee('ensureApprovalColumnsVisible', false);
        $response->assertSee('moveToApprovalStage', false);
        $response->assertSee('handleApproverApprove', false);

        // 4. Verify stage default columns and view preset buttons
        $response->assertSee('DRAFT_STAGE_DEFAULT_COLUMNS', false);
        $response->assertSee('APPROVAL_STAGE_DEFAULT_COLUMNS', false);
        $response->assertSee('applyStageDefaultColumns', false);
        $response->assertSee('ensureDraftColumnsVisible', false);
        $response->assertSee('rms_rfq_builder_cols_v6', false);
        $response->assertSee('Draft View', false);
        $response->assertSee('Approval View', false);
        $response->assertSee('Default View', false);

        // 5. Verify 30% left / 70% right grid width distribution with sidebar expansion
        $response->assertSee('minmax(260px, 30%) minmax(0, 1fr)', false);
        $response->assertSee('#app-sidebar.collapsed ~ .main-content .rfq-builder-workspace-grid', false);

        // 6. Verify Creation of New RFQ functions & unique reference generation
        $response->assertSee('getNextUniqueRfqNumber', false);
        $response->assertSee('createNewRfq', false);
        $response->assertSee('handleBuilderTabClick', false);
        $response->assertSee('rms_last_rfq_counter', false);
    }
}
