<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BusinessLegalDocument;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsModuleTest extends TestCase
{
    protected User $user;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::where('company_code', 'TEST-CORP')->first();
        if (!$company) {
            $company = Company::create([
                'company_code' => 'TEST-CORP',
                'company_legal_name' => 'Test Corporation Inc.',
                'company_trade_name' => 'Test Cafe & Diner',
                'company_tax_id' => '999-888-777-000',
                'company_tax_type' => 'VAT Registered',
                'company_base_currency_code' => 'PHP',
                'company_timezone' => 'Asia/Manila',
                'fiscal_year_start_month' => 1,
                'company_country_code' => 'PH',
                'company_type' => 'Company',
                'company_is_active' => true,
            ]);
        } else {
            $company->update([
                'company_legal_name' => 'Test Corporation Inc.',
                'company_trade_name' => 'Test Cafe & Diner',
                'company_tax_id' => '999-888-777-000',
                'company_tax_type' => 'VAT Registered',
                'company_type' => 'Company',
                'company_is_active' => true,
            ]);
        }
        $this->company = $company;

        $this->branch = Branch::firstOrCreate(
            ['branch_code' => 'TEST-B1'],
            [
                'company_id' => $this->company->company_id,
                'branch_name' => 'Test Flagship Store',
                'branch_type' => 'Store / Dine-In',
                'branch_tax_code' => '00001',
                'operating_hours' => '09:00 AM - 09:00 PM',
                'branch_is_active' => true,
            ]
        );
        $this->branch->update(['company_id' => $this->company->company_id]);

        $this->user = User::firstOrCreate(
            ['username' => 'test_admin_settings'],
            [
                'full_name' => 'Settings Admin',
                'email' => 'settings.admin@test.com',
                'password' => bcrypt('password123'),
                'role' => 'Admin',
                'status' => 'Active',
                'branch_id' => $this->branch->branch_id,
                'default_company_id' => $this->company->company_id,
                'default_branch_id' => $this->branch->branch_id,
            ]
        );
        $this->user->update([
            'default_company_id' => $this->company->company_id,
            'default_branch_id' => $this->branch->branch_id,
        ]);
    }

    public function test_business_settings_page_renders_with_all_tabs(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->get(route('config.business-settings'));

        $response->assertStatus(200);
        $response->assertSee('General Business Profile');
        $response->assertSee('Certificate & Legal Hub', false);
        $response->assertSee('Hierarchy & Relationships', false);
        $response->assertSee('Branches & Sites Hub', false);
        $response->assertSee('Test Cafe & Diner');
    }

    public function test_can_update_general_business_profile(): void
    {
        Storage::fake('public');
        $logoFile = UploadedFile::fake()->image('brand_logo.png', 200, 200);
        $uniqueCode = 'BGH-' . strtoupper(bin2hex(random_bytes(3)));

        $updateData = [
            'company_legal_name' => 'Bistro Group Holdings Corp.',
            'company_trade_name' => 'Bistro Gourmet Kitchen',
            'company_code' => $uniqueCode,
            'company_base_currency_code' => 'PHP',
            'company_timezone' => 'Asia/Manila',
            'fiscal_year_start_month' => 1,
            'company_country_code' => 'PH',
            'company_contact_person' => 'Juan Dela Cruz',
            'company_phone_number' => '+63 2 8111 2233',
            'company_email_address' => 'corp@bistrogourmet.ph',
            'company_address_line_1' => '100 Ayala Avenue',
            'company_city' => 'Makati City',
            'logo' => $logoFile,
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->post(route('config.business-settings.general'), $updateData);

        $response->assertRedirect(route('config.business-settings', ['tab' => 'general']));
        $response->assertSessionHas('success');

        $this->company->refresh();
        $this->assertEquals('Bistro Gourmet Kitchen', $this->company->company_trade_name);
        $this->assertEquals($uniqueCode, $this->company->company_code);
        $this->assertNotNull($this->company->company_logo);
        Storage::disk('public')->assertExists($this->company->company_logo);

        // Reset company code back to TEST-CORP for idempotency
        $this->company->update(['company_code' => 'TEST-CORP']);
    }

    public function test_can_update_certificate_and_legal_hub_settings(): void
    {
        $legalData = [
            'company_tax_id' => '111-222-333-000',
            'company_tax_type' => 'VAT Registered',
            'company_rdo_code' => '047',
            'company_registration_no' => 'SEC-CS20261192',
            'bir_permit_number' => 'PTU-2026-9901',
            'invoice_header_text' => 'BISTRO GOURMET KITCHEN\nTIN: 111-222-333-000 VAT Reg',
            'invoice_footer_text' => 'THIS RECEIPT SHALL BE VALID FOR FIVE (5) YEARS',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->post(route('config.business-settings.legal'), $legalData);

        $response->assertRedirect(route('config.business-settings', ['tab' => 'legal']));
        $response->assertSessionHas('success');

        $this->company->refresh();
        $this->assertEquals('111-222-333-000', $this->company->company_tax_id);
        $this->assertEquals('047', $this->company->company_rdo_code);
        $this->assertStringContainsString('BISTRO GOURMET KITCHEN', $this->company->invoice_header_text);
    }

    public function test_can_upload_and_delete_legal_document_in_vault(): void
    {
        Storage::fake('public');
        $pdfFile = UploadedFile::fake()->create('bir_form_2303.pdf', 300, 'application/pdf');
        $uniqueDocName = 'BIR Form 2303 - ' . bin2hex(random_bytes(3));

        $docPayload = [
            'document_category' => 'Certificate of Registration (COR / BIR Form 2303)',
            'document_name' => $uniqueDocName,
            'document_number' => 'COR-2026-0091',
            'issuing_authority' => 'BIR RDO 047',
            'issue_date' => now()->subMonths(6)->toDateString(),
            'expiration_date' => now()->addMonths(6)->toDateString(),
            'renewal_reminder_days' => 30,
            'notes' => 'Registered for VAT, Withholding, Income Tax',
            'document_file' => $pdfFile,
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->post(route('config.business-settings.documents.store'), $docPayload);

        $response->assertRedirect(route('config.business-settings', ['tab' => 'legal']));
        $response->assertSessionHas('success');

        $doc = BusinessLegalDocument::where('document_name', $uniqueDocName)->first();
        $this->assertNotNull($doc);
        $this->assertEquals('Active', $doc->expiration_status);
        Storage::disk('public')->assertExists($doc->file_path);

        // Test deletion
        $delResponse = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->delete(route('config.business-settings.documents.destroy', $doc->id));

        $delResponse->assertRedirect(route('config.business-settings', ['tab' => 'legal']));
        $this->assertNull(BusinessLegalDocument::find($doc->id));
        Storage::disk('public')->assertMissing($doc->file_path);
    }

    public function test_can_update_hierarchy_and_shared_rules(): void
    {
        $hierarchyData = [
            'company_type' => 'Holding Enterprise',
            'parent_company_id' => null,
            'share_chart_of_accounts' => 1,
            'share_customer_vendor_directory' => 1,
            'share_product_catalog' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->post(route('config.business-settings.hierarchy'), $hierarchyData);

        $response->assertRedirect(route('config.business-settings', ['tab' => 'hierarchy']));
        $response->assertSessionHas('success');

        $this->company->refresh();
        $this->assertEquals('Holding Enterprise', $this->company->company_type);
        $this->assertTrue($this->company->share_chart_of_accounts);
        $this->assertTrue($this->company->share_customer_vendor_directory);
    }

    public function test_can_create_and_update_and_toggle_branch(): void
    {
        $uniqueBranchCode = 'BGC-' . strtoupper(bin2hex(random_bytes(3)));

        $branchData = [
            'branch_name' => 'BGC Uptown Kiosk',
            'branch_code' => $uniqueBranchCode,
            'branch_tax_code' => '00002',
            'branch_type' => 'Takeout / Kiosk',
            'operating_hours' => '10:00 AM - 10:00 PM',
            'branch_contact_person' => 'Mark Wilson',
            'branch_phone_number' => '+63 2 8555 1234',
            'branch_city' => 'Taguig City',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->post(route('config.business-settings.branches.store'), $branchData);

        $response->assertRedirect(route('config.business-settings', ['tab' => 'branches']));
        $response->assertSessionHas('success');

        $newBranch = Branch::where('branch_code', $uniqueBranchCode)->first();
        $this->assertNotNull($newBranch);
        $this->assertEquals('Takeout / Kiosk', $newBranch->branch_type);
        $this->assertTrue($newBranch->branch_is_active);

        // Update branch
        $updateData = array_merge($branchData, [
            'branch_name' => 'BGC Uptown Mall Flagship Kiosk',
            'operating_hours' => '09:00 AM - 11:00 PM',
        ]);

        $updateResponse = $this->actingAs($this->user)
            ->put(route('config.business-settings.branches.update', $newBranch->branch_id), $updateData);

        $updateResponse->assertRedirect(route('config.business-settings', ['tab' => 'branches']));
        $newBranch->refresh();
        $this->assertEquals('BGC Uptown Mall Flagship Kiosk', $newBranch->branch_name);

        // Toggle status
        $toggleResponse = $this->actingAs($this->user)
            ->post(route('config.business-settings.branches.toggle', $newBranch->branch_id));

        $toggleResponse->assertRedirect(route('config.business-settings', ['tab' => 'branches']));
        $newBranch->refresh();
        $this->assertFalse($newBranch->branch_is_active);
    }

    public function test_context_switcher_can_switch_company_and_branch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('context.switch'), [
                'company_id' => $this->company->company_id,
                'branch_id' => $this->branch->branch_id,
                'is_consolidated' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('active_company_id', $this->company->company_id);
        $response->assertSessionHas('active_branch_id', $this->branch->branch_id);
        $response->assertSessionHas('is_consolidated_view', false);
    }

    public function test_context_switcher_can_toggle_consolidated_read_only_mode(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('context.switch'), [
                'company_id' => $this->company->company_id,
                'branch_id' => null,
                'is_consolidated' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('is_consolidated_view', true);
    }

    public function test_can_set_default_landing_location(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('context.default-landing'), [
                'default_company_id' => $this->company->company_id,
                'default_branch_id' => $this->branch->branch_id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertEquals($this->company->company_id, $this->user->default_company_id);
        $this->assertEquals($this->branch->branch_id, $this->user->default_branch_id);
    }

    public function test_context_switcher_can_update_multi_entity_checkbox_scope(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('context.scope'), [
                'company_ids' => [$this->company->company_id],
                'branch_ids' => [$this->branch->branch_id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'checked_company_ids' => [$this->company->company_id],
            'checked_branch_ids' => [$this->branch->branch_id],
        ]);

        $response->assertSessionHas('checked_company_ids', [$this->company->company_id]);
        $response->assertSessionHas('checked_branch_ids', [$this->branch->branch_id]);
    }

    public function test_context_switcher_preserves_active_write_entity_when_unchecking_view_scope(): void
    {
        // 1. Establish initial active write entity
        $initialSession = [
            'active_company_id' => $this->company->company_id,
            'active_branch_id' => $this->branch->branch_id,
            'checked_company_ids' => [$this->company->company_id],
            'checked_branch_ids' => [$this->branch->branch_id],
        ];

        // 2. Uncheck all branches in view scope
        $response = $this->actingAs($this->user)
            ->withSession($initialSession)
            ->postJson(route('context.scope'), [
                'company_ids' => [$this->company->company_id],
                'branch_ids' => [], // Unchecked
            ]);

        $response->assertStatus(200);

        // 3. Confirm active writing entity remains unchanged
        $response->assertSessionHas('active_company_id', $this->company->company_id);
        $response->assertSessionHas('active_branch_id', $this->branch->branch_id);
        $response->assertSessionHas('checked_branch_ids', []);
    }
}
