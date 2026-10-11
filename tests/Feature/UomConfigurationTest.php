<?php

namespace Tests\Feature;

use App\Models\Inventory\UnitOfMeasure;
use App\Models\Inventory\UomCategory;
use App\Models\User;
use Tests\TestCase;

class UomConfigurationTest extends TestCase
{
    /**
     * Test that an authenticated user can access the UOM Configuration view.
     */
    public function test_authenticated_user_can_access_uom_configuration_page()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/inventory/configuration/uom');

        $response->assertStatus(200);

        // Verify key modular table elements and IDs
        $response->assertSee('id="uomDirectoryTable"', false);
        $response->assertSee('id="uomTheadRow"', false);
        $response->assertSee('id="uomTableSettingsDropdown"', false);
        $response->assertSee('id="uomSettingsGearBtn"', false);

        // Verify modular stacked header styles
        $response->assertSee('uom-th-stacked', false);
        $response->assertSee('uom-th-title', false);
        $response->assertSee('uom-th-sub', false);
        $response->assertSee('uom-th-settings-btn', false);

        // Verify RFQ-style expansion buttons and subtable styling exist
        $response->assertSee('uom-row-expand-btn', false);
        $response->assertSee('uom-line-expansion-row', false);
        $response->assertSee('uom-subtable-container', false);
        $response->assertSee('uom-subtable', false);

        // Verify uom-tabs-row is removed
        $response->assertDontSee('uom-tabs-row', false);

        // Verify Rows-per-page is in the footer pagination bar
        $response->assertSee('id="uomPaginationBar"', false);
        $response->assertSee('id="uomPageSize"', false);

        // Verify column settings are switchable and Category Info is locked
        $response->assertSee('uom-switch', false);
        $response->assertSee('CATEGORY_COLUMNS', false);
        $response->assertSee('toggleCategoryExpansion', false);

        // Verify Drawer and Modals exist
        $response->assertSee('id="uomDrawer"', false);
        $response->assertSee('id="uomCategoryModal"', false);
    }

    /**
     * Test that apiGetUoms returns seeded categories and units.
     */
    public function test_api_returns_active_uoms_and_categories()
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->getJson('/inventory/api/uoms');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertNotEmpty($data['categories']);
        $this->assertNotEmpty($data['units']);

        // Check for standard categories from OUM.txt
        $categoryCodes = collect($data['categories'])->pluck('category_code')->toArray();
        $this->assertContains('COUNT', $categoryCodes);
        $this->assertContains('WEIGHT', $categoryCodes);
        $this->assertContains('VOLUME', $categoryCodes);
        $this->assertContains('PORTION', $categoryCodes);
        $this->assertContains('LENGTH', $categoryCodes);
        $this->assertContains('AREA', $categoryCodes);
        $this->assertContains('TIME', $categoryCodes);
        $this->assertContains('OVERHEAD', $categoryCodes);

        // Check for standard units from OUM.txt
        $uomCodes = collect($data['units'])->pluck('uom_code')->toArray();
        $this->assertContains('PCS', $uomCodes);
        $this->assertContains('KG', $uomCodes);
        $this->assertContains('L', $uomCodes);
        $this->assertContains('PORT', $uomCodes);
        $this->assertContains('M', $uomCodes);
        $this->assertContains('SQM', $uomCodes);
        $this->assertContains('HR', $uomCodes);
        $this->assertContains('KWH', $uomCodes);
    }

    /**
     * Test creating a new category and a new UOM with quantity ratio.
     */
    public function test_user_can_create_category_and_uom_with_quantity()
    {
        $user = User::first() ?? User::factory()->create();
        $uniqueSuffix = strtoupper(\Illuminate\Support\Str::random(4));
        $catCode = 'CONT_' . $uniqueSuffix;
        $uomCodeBase = 'PLT_' . $uniqueSuffix;
        $uomCodeDerived = 'STK_' . $uniqueSuffix;

        // 1. Create a custom category
        $catResponse = $this->actingAs($user)->postJson('/inventory/api/uom-categories/store', [
            'category_code' => $catCode,
            'category_name' => 'Shipping & Storage Containers ' . $uniqueSuffix,
            'category_description' => 'Industrial shipping pallets and containers.',
            'base_uom_code' => $uomCodeBase,
        ]);

        $catResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('uom_categories', [
            'category_code' => $catCode,
        ]);

        // 2. Create Base Unit for category
        $baseUomResponse = $this->actingAs($user)->postJson('/inventory/api/uoms/store', [
            'uom_code' => $uomCodeBase,
            'uom_name' => 'Standard Pallet',
            'uom_category' => $catCode,
            'base_ratio' => 1.0,
            'is_base_unit' => true,
        ]);

        $baseUomResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // 3. Create Derived Unit with quantity
        $derivedResponse = $this->actingAs($user)->postJson('/inventory/api/uoms/store', [
            'uom_code' => $uomCodeDerived,
            'uom_name' => 'Stack of 4 Pallets',
            'uom_category' => $catCode,
            'base_ratio' => 4.0,
            'is_base_unit' => false,
        ]);

        $derivedResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('units_of_measure', [
            'uom_code' => $uomCodeDerived,
            'base_ratio' => 4.000000,
        ]);
    }

    /**
     * Test toggling active status on a UOM.
     */
    public function test_user_can_toggle_uom_active_status()
    {
        $user = User::first() ?? User::factory()->create();

        $uom = UnitOfMeasure::where('uom_code', 'PCS')->first();
        $this->assertNotNull($uom);

        $initialStatus = $uom->uom_is_active;

        $response = $this->actingAs($user)->postJson("/inventory/api/uoms/{$uom->uom_id}/toggle-active");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_active' => !$initialStatus,
            ]);

        // Toggle back to active
        $response2 = $this->actingAs($user)->postJson("/inventory/api/uoms/{$uom->uom_id}/toggle-active");
        $response2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_active' => $initialStatus,
            ]);
    }

    /**
     * Test that the Blade UOM Dropdown component renders with custom attributes and fallback select.
     */
    public function test_custom_uom_dropdown_component_renders_correctly()
    {
        $rendered = (string) $this->blade('<x-uom-dropdown name="test_uom" id="testUomId" value="KG" category="WEIGHT" placeholder="Select Weight Unit" required />');

        $this->assertStringContainsString('class="uom-dropdown-wrapper', $rendered);
        $this->assertStringContainsString('data-uom-dropdown', $rendered);
        $this->assertStringContainsString('data-uom-target="#testUomId"', $rendered);
        $this->assertStringContainsString('data-uom-category="WEIGHT"', $rendered);
        $this->assertStringContainsString('name="test_uom"', $rendered);
        $this->assertStringContainsString('id="testUomId"', $rendered);
        $this->assertStringContainsString('value="KG"', $rendered);
        $this->assertStringContainsString('data-uom-select', $rendered);
    }

    /**
     * Test toggling active status on a Category cascades deactivation to all its child units.
     */
    public function test_user_can_toggle_category_active_status_and_cascade_to_uoms()
    {
        $user = User::first() ?? User::factory()->create();

        // Create dedicated test category with child units
        $suffix = strtoupper(\Illuminate\Support\Str::random(4));
        $catCode = 'TEST_CAT_' . $suffix;

        $cat = UomCategory::create([
            'category_id' => (string) \Illuminate\Support\Str::uuid(),
            'category_code' => $catCode,
            'category_name' => 'Cascade Test ' . $suffix,
            'category_is_active' => true,
        ]);

        $uom1 = UnitOfMeasure::create([
            'uom_id' => (string) \Illuminate\Support\Str::uuid(),
            'uom_code' => 'U1_' . $suffix,
            'uom_name' => 'Unit 1 ' . $suffix,
            'uom_category' => $catCode,
            'base_ratio' => 1.0,
            'is_base_unit' => true,
            'uom_is_active' => true,
        ]);

        $uom2 = UnitOfMeasure::create([
            'uom_id' => (string) \Illuminate\Support\Str::uuid(),
            'uom_code' => 'U2_' . $suffix,
            'uom_name' => 'Unit 2 ' . $suffix,
            'uom_category' => $catCode,
            'base_ratio' => 5.0,
            'is_base_unit' => false,
            'uom_is_active' => true,
        ]);

        // Toggle category to inactive
        $response = $this->actingAs($user)->postJson("/inventory/api/uom-categories/{$cat->category_id}/toggle-active");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_active' => false,
                'affected_units_count' => 2,
            ]);

        $this->assertFalse($cat->fresh()->category_is_active);
        $this->assertFalse($uom1->fresh()->uom_is_active);
        $this->assertFalse($uom2->fresh()->uom_is_active);
    }

    /**
     * Test that apiGetUoms filters inactive by default but includes them when all=1 or include_inactive=1.
     */
    public function test_api_get_uoms_supports_all_parameter_to_include_inactive()
    {
        $user = User::first() ?? User::factory()->create();

        $suffix = strtoupper(\Illuminate\Support\Str::random(4));
        $catCode = 'FILTER_CAT_' . $suffix;

        UomCategory::create([
            'category_id' => (string) \Illuminate\Support\Str::uuid(),
            'category_code' => $catCode,
            'category_name' => 'Filter Cat ' . $suffix,
            'category_is_active' => true,
        ]);

        $inactiveUnit = UnitOfMeasure::create([
            'uom_id' => (string) \Illuminate\Support\Str::uuid(),
            'uom_code' => 'INACT_' . $suffix,
            'uom_name' => 'Inactive Unit ' . $suffix,
            'uom_category' => $catCode,
            'base_ratio' => 1.0,
            'uom_is_active' => false,
        ]);

        // Default endpoint: should NOT contain the inactive unit
        $defaultResp = $this->actingAs($user)->getJson('/inventory/api/uoms');
        $defaultCodes = collect($defaultResp->json('units'))->pluck('uom_code')->toArray();
        $this->assertNotContains('INACT_' . $suffix, $defaultCodes);

        // With all=1: SHOULD contain the inactive unit
        $allResp = $this->actingAs($user)->getJson('/inventory/api/uoms?all=1');
        $allCodes = collect($allResp->json('units'))->pluck('uom_code')->toArray();
        $this->assertContains('INACT_' . $suffix, $allCodes);
    }

    /**
     * Test that units belonging to inactive categories are strictly excluded from default apiGetUoms.
     */
    public function test_api_excludes_units_from_inactive_categories_and_returns_only_active_units()
    {
        $user = User::first() ?? User::factory()->create();

        $suffix = strtoupper(\Illuminate\Support\Str::random(4));
        $catCode = 'INACT_CAT_' . $suffix;

        UomCategory::create([
            'category_id' => (string) \Illuminate\Support\Str::uuid(),
            'category_code' => $catCode,
            'category_name' => 'Inactive Parent Cat ' . $suffix,
            'category_is_active' => false, // Inactive category
        ]);

        UnitOfMeasure::create([
            'uom_id' => (string) \Illuminate\Support\Str::uuid(),
            'uom_code' => 'CHILD_' . $suffix,
            'uom_name' => 'Child Unit ' . $suffix,
            'uom_category' => $catCode,
            'base_ratio' => 1.0,
            'uom_is_active' => true, // Even though unit is active, parent category is inactive!
        ]);

        $resp = $this->actingAs($user)->getJson('/inventory/api/uoms');
        $resp->assertStatus(200);

        $returnedCategories = collect($resp->json('categories'))->pluck('category_code')->toArray();
        $returnedUnits = collect($resp->json('units'))->pluck('uom_code')->toArray();

        $this->assertNotContains($catCode, $returnedCategories);
        $this->assertNotContains('CHILD_' . $suffix, $returnedUnits);
    }
}
