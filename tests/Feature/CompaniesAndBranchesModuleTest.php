<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class CompaniesAndBranchesModuleTest extends TestCase
{
    protected User $user;
    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::firstOrCreate(
            ['company_code' => 'GOODFARMS'],
            [
                'company_legal_name' => 'GOODFARMS TRADING CORPORATION',
                'company_trade_name' => 'Goodfarms Depot',
                'company_tax_id' => '412-277-750-00000',
                'company_branch_code' => '00000',
                'company_rdo_code' => '098',
                'company_tax_type' => 'VAT Registered',
                'company_is_vat_registered' => true,
                'company_base_currency_code' => 'PHP',
                'company_timezone' => 'Asia/Manila',
                'fiscal_year_start_month' => 1,
                'company_country_code' => 'PH',
                'company_city' => 'Maramag',
                'company_state_province_code' => 'Bukidnon (PH)',
                'company_postal_code' => '8712',
                'company_address_line_1' => 'Purok-5',
                'company_address_line_2' => 'Camp 1',
                'company_email_domain' => 'goodfarms.odoo.com',
                'company_brand_color' => '#ef4444',
                'company_external_code' => 'Code used in work entry exports',
                'company_type' => 'Company',
                'company_is_active' => true,
            ]
        );

        $this->branch = Branch::firstOrCreate(
            ['branch_code' => 'GF-MAIN'],
            [
                'company_id' => $this->company->company_id,
                'branch_name' => 'Goodfarms Main Commissary',
                'branch_type' => 'Central Commissary',
                'branch_tax_code' => '00001',
                'operating_hours' => '08:00 AM - 08:00 PM',
                'branch_is_active' => true,
            ]
        );

        $this->user = User::firstOrCreate(
            ['username' => 'test_admin_companies'],
            [
                'full_name' => 'System Director',
                'email' => 'director@test.com',
                'password' => bcrypt('password123'),
                'role' => 'Admin',
                'status' => 'Active',
                'branch_id' => $this->branch->branch_id,
                'default_company_id' => $this->company->company_id,
                'default_branch_id' => $this->branch->branch_id,
            ]
        );
    }

    public function test_companies_and_branches_directory_renders_with_modular_table_and_kpi_cards(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->get(route('config.companies-branches.index'));

        $response->assertStatus(200);
        $response->assertSee('Companies &amp; Branches', false);
        $response->assertSee('Companies Directory');
        $response->assertSee('Total Companies');
        $response->assertSee('Connected Branches');
        $response->assertSee('Table Columns &amp; Sequence', false);
        $response->assertSee('GOODFARMS TRADING CORPORATION');
        $response->assertSee('GF-MAIN');
        $response->assertSee('modalConfirmDeleteCompany');
        $response->assertSee('modalConfirmDeleteBranch');
        $response->assertSee('branch-pills-wrap');
        $response->assertSee('confirmDeleteCompany');
        $response->assertSee('confirmDeleteBranch');
    }

    public function test_company_details_page_renders_with_general_info_and_branches_tabs(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_company_id' => $this->company->company_id, 'active_branch_id' => $this->branch->branch_id])
            ->get(route('config.companies-branches.show', $this->company->company_id));

        $response->assertStatus(200);
        $response->assertSee('GOODFARMS TRADING CORPORATION');
        $response->assertSee('General Information');
        $response->assertSee('Branches');
        $response->assertSee('Certificate &amp; Legal Hub', false);
        $response->assertSee('Delete Company');
        $response->assertSee('Delete Branch');
        $response->assertSee('Purok-5');
        $response->assertSee('Camp 1');
        $response->assertSee('Maramag');
        $response->assertSee('412-277-750-00000');
        $response->assertSee('00000');
        $response->assertSee('VAT Registered');
        $response->assertSee('goodfarms.odoo.com');
        $response->assertSee('Goodfarms Main Commissary');
    }

    public function test_can_create_new_company_with_extended_fields(): void
    {
        $uniqueCode = 'AGRI-' . strtoupper(bin2hex(random_bytes(2)));

        $payload = [
            'company_code' => $uniqueCode,
            'company_legal_name' => 'AgriFresh Producers Inc.',
            'company_trade_name' => 'AgriFresh Valley',
            'company_type' => 'Company',
            'company_tax_id' => '987-654-321-000',
            'company_branch_code' => '00000',
            'company_rdo_code' => '047',
            'company_is_vat_registered' => 1,
            'company_base_currency_code' => 'PHP',
            'company_email_domain' => 'agrifresh.ph',
            'company_brand_color' => '#10b981',
            'company_external_code' => 'AGRI-WORK-ENTRY',
            'company_city' => 'Davao City',
            'company_address_line_1' => 'KM 7 Lanang',
            'company_country_code' => 'PH',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('config.companies-branches.store'), $payload);

        $created = Company::where('company_code', $uniqueCode)->first();
        $this->assertNotNull($created);
        $this->assertEquals('AgriFresh Producers Inc.', $created->company_legal_name);
        $this->assertEquals('agrifresh.ph', $created->company_email_domain);
        $this->assertEquals('#10b981', $created->company_brand_color);
        $this->assertTrue($created->company_is_vat_registered);

        $response->assertRedirect(route('config.companies-branches.show', $created->company_id));
    }

    public function test_can_update_company_details_with_reference_fields(): void
    {
        $updatePayload = [
            'company_code' => $this->company->company_code,
            'company_legal_name' => 'GOODFARMS TRADING CORPORATION (EXPANDED)',
            'company_trade_name' => 'Goodfarms Commercial Trading',
            'company_type' => 'Company',
            'company_tax_id' => '412-277-750-00000',
            'company_branch_code' => '00000',
            'company_rdo_code' => '098',
            'company_is_vat_registered' => 1,
            'company_base_currency_code' => 'PHP',
            'company_phone_number' => '+63 88 123 4567',
            'company_email_address' => 'contact@goodfarms.odoo.com',
            'company_website_url' => 'https://goodfarms.odoo.com',
            'company_email_domain' => 'goodfarms.odoo.com',
            'company_brand_color' => '#ef4444',
            'company_external_code' => 'EXPORT-CODE-99',
            'company_address_line_1' => 'Purok-5 Expanded',
            'company_address_line_2' => 'Camp 1 Gate',
            'company_city' => 'Maramag',
            'company_state_province_code' => 'Bukidnon (PH)',
            'company_postal_code' => '8712',
            'company_country_code' => 'PH',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('config.companies-branches.update', $this->company->company_id), $updatePayload);

        $response->assertRedirect(route('config.companies-branches.show', $this->company->company_id));
        $this->company->refresh();
        $this->assertEquals('GOODFARMS TRADING CORPORATION (EXPANDED)', $this->company->company_legal_name);
        $this->assertEquals('EXPORT-CODE-99', $this->company->company_external_code);
        $this->assertEquals('#ef4444', $this->company->company_brand_color);
    }

    public function test_can_toggle_company_status(): void
    {
        $initialStatus = $this->company->company_is_active;

        $response = $this->actingAs($this->user)
            ->post(route('config.companies-branches.toggle', $this->company->company_id));

        $response->assertRedirect();
        $this->company->refresh();
        $this->assertEquals(!$initialStatus, $this->company->company_is_active);

        // Reset back
        $this->company->update(['company_is_active' => $initialStatus]);
    }

    public function test_sidebar_displays_companies_and_branches_tab(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('config.companies-branches.index'));

        $response->assertStatus(200);
        $response->assertSee('Companies &amp; Branches', false);
        $response->assertSee('data-group-id="config-biz"', false);
    }

    public function test_can_delete_company(): void
    {
        $comp = Company::create([
            'company_code' => 'DEL-COMP-' . uniqid(),
            'company_legal_name' => 'To Be Deleted Inc.',
            'company_type' => 'Company',
            'company_base_currency_code' => 'PHP',
            'company_country_code' => 'PH',
            'company_is_active' => true,
        ]);

        $br = Branch::create([
            'company_id' => $comp->company_id,
            'branch_code' => 'DEL-BR-' . uniqid(),
            'branch_name' => 'Branch of Deleted Company',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('config.companies-branches.destroy', $comp->company_id));

        $response->assertRedirect(route('config.companies-branches.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('companies', ['company_id' => $comp->company_id]);
        $this->assertDatabaseMissing('branches', ['branch_id' => $br->branch_id]);
    }

    public function test_can_delete_branch(): void
    {
        $br = Branch::create([
            'company_id' => $this->company->company_id,
            'branch_code' => 'DEL-SINGLE-BR-' . uniqid(),
            'branch_name' => 'Single Removable Branch',
            'branch_type' => 'Store / Dine-In',
            'branch_is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('config.companies-branches.branches.destroy', $br->branch_id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('branches', ['branch_id' => $br->branch_id]);
    }
}
