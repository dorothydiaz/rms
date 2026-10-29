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

        // 1. Verify Progress Bar container and step elements exist in DOM
        $response->assertSee('id="rfqLifecycleStepperWrap"', false);
        $response->assertSee('id="rfqLifecycleProgressBar"', false);
        $response->assertSee('id="stepIndicatorDraft"', false);
        $response->assertSee('id="stepIndicatorSubmitted"', false);
        $response->assertSee('id="stepIndicatorApproval"', false);
        $response->assertSee('id="stepIndicatorStatus"', false);
        $response->assertSee('id="rfqStageBanner"', false);

        // 2. Verify Top Banner ribbon elements & Vendor selection
        $response->assertSee('id="rfqTopBannerCard"', false);
        $response->assertSee('id="rfqRefDisplay"', false);
        $response->assertSee('id="vendorSelect"', false);
        $response->assertSee('id="rfqVendorCombobox"', false);
        $response->assertSee('id="rfqVendorComboTrigger"', false);
        $response->assertSee('id="rfqVendorRedirectBtn"', false);
        $response->assertSee('id="rfqVendorDirectLink"', false);

        // 3. Verify JavaScript module contains lifecycle & column management functions
        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $this->assertStringContainsString('id="btnRfqBuilderSettings"', $jsContent);
        $this->assertStringContainsString('id="rfqBuilderSettingsDropdown"', $jsContent);
        $this->assertStringContainsString('updateLifecycleProgressBar', $jsContent);
        $this->assertStringContainsString('toggleRfqBuilderSettingsDropdown', $jsContent);
        $this->assertStringContainsString('transitionActiveRfqStage', $jsContent);
        $this->assertStringContainsString('calculateItemVariances', $jsContent);
        $this->assertStringContainsString('renderBuilderTableHeader', $jsContent);
        $this->assertStringContainsString('formatNumber', $jsContent);
        $this->assertStringContainsString('ensureApprovalColumnsVisible', $jsContent);
        $this->assertStringContainsString('ensureSubmittedColumnsVisible', $jsContent);
        $this->assertStringContainsString('ensureDraftColumnsVisible', $jsContent);
        $this->assertStringContainsString('moveToApprovalStage', $jsContent);
        $this->assertStringContainsString('handleApproverApprove', $jsContent);

        // 4. Verify stage default columns and view preset constants
        $this->assertStringContainsString('DRAFT_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('SUBMITTED_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('APPROVAL_STAGE_DEFAULT_COLUMNS', $jsContent);
        $this->assertStringContainsString('applyStageDefaultColumns', $jsContent);

        // 5. Verify CSS rules for layout and top ribbon styling
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));
        $this->assertStringContainsString('.rfq-top-banner-card', $cssContent);
        $this->assertStringContainsString('.rfq-ref-id-display', $cssContent);
        $this->assertStringContainsString('white-space: nowrap !important;', $cssContent);

        // 6. Verify Creation of New RFQ functions & unique reference generation in JS
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

        // 3. Verify Server-Side bridges for live data synchronization
        $response->assertSee('window.RfqConfig', false);
        $response->assertSee('initialRfqs', false);
        $response->assertSee('initialVendors', false);
        $response->assertSee('initialItems', false);

        // 4. Verify Stage Form Locks and Directory Syncing logic in JS
        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $this->assertStringContainsString('applyStageFormLocks', $jsContent);
        $this->assertStringContainsString('syncRfqToDirectory', $jsContent);
        $this->assertStringContainsString('editRfqFromDirectory', $jsContent);
        $this->assertStringContainsString('getNormalizedRfqStage', $jsContent);

        // 5. Verify status pill CSS rules exist for all lifecycle statuses
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));
        $this->assertStringContainsString('.status-pill.approved', $cssContent);
        $this->assertStringContainsString('.status-pill.submitted', $cssContent);
        $this->assertStringContainsString('.status-pill.approval', $cssContent);
    }

    public function test_vendor_masterlist_receives_database_vendors_and_rfq_mockup_and_ui_layering()
    {
        $user = User::first() ?? User::factory()->create();

        // 1. Verify Vendor Masterlist passes real database vendors to Blade config
        $vendorResponse = $this->actingAs($user)->get('/purchase/vendor-masterlist');
        $vendorResponse->assertStatus(200);
        $vendorResponse->assertSee('window.VendorMasterlistConfig', false);
        $vendorResponse->assertSee('initialVendors', false);

        // 2. Verify RFQ View passes initialVendors to RfqConfig
        $rfqResponse = $this->actingAs($user)->get('/purchase/request-quotations');
        $rfqResponse->assertStatus(200);
        $rfqResponse->assertSee('initialVendors', false);

        // 3. Verify CSS rules for select cutout fix and dropdown z-index layering
        $cssContent = file_get_contents(resource_path('css/modules/request-quotations.css'));
        $this->assertStringContainsString('line-height: normal !important;', $cssContent);
        $this->assertStringContainsString('.rfq-top-banner-card.has-dropdown-open', $cssContent);
        $this->assertStringContainsString('.rfq-vendor-combo-dropdown', $cssContent);
        $this->assertStringContainsString('z-index: 999999 !important;', $cssContent);

        // 4. Verify JavaScript module includes mockup vendor response logic and live updates
        $jsContent = file_get_contents(resource_path('js/modules/request-quotations.js'));
        $this->assertStringContainsString('updateLiveDocumentMockup', $jsContent);
        $this->assertStringContainsString('updateItemRowFieldLive', $jsContent);
        $this->assertStringContainsString('buildVendorProfileUrl', $jsContent);
        $this->assertStringContainsString('hasQuotedPrice', $jsContent);
        $this->assertStringContainsString('Vendor Quoted Grand Total', $jsContent);
    }
}
