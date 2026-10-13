<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BusinessLegalDocument;
use App\Models\Company;
use App\Services\AuditLogger;
use App\Services\CompanyBranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessSettingsController extends Controller
{
    public function __construct(protected CompanyBranchContext $context)
    {
    }

    /**
     * Render the unified Business Settings configuration console.
     */
    public function index(Request $request): View
    {
        $company = $this->context->getActiveCompany() ?? Company::first();
        $branches = $company ? Branch::where('company_id', $company->company_id)->orderBy('branch_name')->get() : collect();
        $legalDocuments = $company ? BusinessLegalDocument::where('documentable_type', Company::class)
            ->where('documentable_id', $company->company_id)
            ->orderBy('expiration_date', 'asc')
            ->get() : collect();

        $allCompanies = Company::active()->orderBy('company_legal_name')->get();

        // Document vault statistics
        $vaultStats = [
            'total' => $legalDocuments->count(),
            'active' => $legalDocuments->where('expiration_status', 'Active')->count(),
            'expiring_soon' => $legalDocuments->where('expiration_status', 'Expiring Soon')->count(),
            'expired' => $legalDocuments->where('expiration_status', 'Expired')->count(),
        ];

        $currencies = ['PHP', 'USD', 'EUR', 'JPY', 'SGD', 'HKD', 'AUD'];
        $timezones = ['Asia/Manila', 'Asia/Singapore', 'Asia/Tokyo', 'UTC', 'America/New_York', 'America/Los_Angeles'];
        $branchTypes = ['Store / Dine-In', 'Takeout / Kiosk', 'Central Commissary', 'Warehouse', 'Head Office'];
        $taxTypes = ['VAT Registered', 'Non-VAT Registered', 'Tax Exempt', 'Special Economic Zone / PEZA'];
        $docCategories = [
            'Certificate of Registration (COR / BIR Form 2303)',
            "Mayor's / Business Permit",
            'SEC Certificate of Incorporation / DTI Registration',
            'Sanitary Permit & Health Clearances',
            'Fire Safety Inspection Certificate (FSIC)',
            'Tax Exemption / PEZA Certificate',
            'Commercial Lease Contract',
            'Barangay Business Clearance',
            'Other Legal Compliance Document',
        ];

        return view('config.business-settings', compact(
            'company',
            'branches',
            'legalDocuments',
            'allCompanies',
            'vaultStats',
            'currencies',
            'timezones',
            'branchTypes',
            'taxTypes',
            'docCategories'
        ));
    }

    /**
     * Render the Modular Companies & Branches directory or Company Details view.
     */
    public function companiesBranchesIndex(Request $request): View
    {
        $companies = Company::with(['branches', 'parentCompany', 'childCompanies', 'legalDocuments'])
            ->orderBy('company_legal_name')
            ->get();

        $allBranches = Branch::with('company')->orderBy('branch_name')->get();

        // Calculate telemetry metrics
        $metrics = [
            'total_companies' => $companies->count(),
            'total_branches' => $allBranches->count(),
            'active_companies' => $companies->where('company_is_active', true)->count(),
            'active_branches' => $allBranches->where('branch_is_active', true)->count(),
            'vat_registered' => $companies->filter(fn($c) => $c->company_is_vat_registered || $c->company_tax_type === 'VAT Registered')->count(),
            'holding_groups' => $companies->where('company_type', 'Holding Enterprise')->count(),
        ];

        // Determine view mode and selected company
        $currentView = $request->get('view', 'directory');
        $selectedCompanyId = $request->get('company_id');

        if ($selectedCompanyId) {
            $selectedCompany = $companies->firstWhere('company_id', (int) $selectedCompanyId) ?? $this->context->getActiveCompany() ?? $companies->first();
            $currentView = 'details';
        } else {
            $selectedCompany = $this->context->getActiveCompany() ?? $companies->first();
        }

        $branches = $selectedCompany ? $selectedCompany->branches : collect();
        $legalDocuments = $selectedCompany ? $selectedCompany->legalDocuments : collect();

        $vaultStats = [
            'total' => $legalDocuments->count(),
            'active' => $legalDocuments->where('expiration_status', 'Active')->count(),
            'expiring_soon' => $legalDocuments->where('expiration_status', 'Expiring Soon')->count(),
            'expired' => $legalDocuments->where('expiration_status', 'Expired')->count(),
        ];

        $currencies = ['PHP', 'USD', 'EUR', 'JPY', 'SGD', 'HKD', 'AUD'];
        $timezones = ['Asia/Manila', 'Asia/Singapore', 'Asia/Tokyo', 'UTC', 'America/New_York', 'America/Los_Angeles'];
        $branchTypes = ['Store / Dine-In', 'Takeout / Kiosk', 'Central Commissary', 'Warehouse', 'Head Office'];
        $taxTypes = ['VAT Registered', 'Non-VAT Registered', 'Tax Exempt', 'Special Economic Zone / PEZA'];
        $docCategories = [
            'Certificate of Registration (COR / BIR Form 2303)',
            "Mayor's / Business Permit",
            'SEC Certificate of Incorporation / DTI Registration',
            'Sanitary Permit & Health Clearances',
            'Fire Safety Inspection Certificate (FSIC)',
            'Tax Exemption / PEZA Certificate',
            'Commercial Lease Contract',
            'Barangay Business Clearance',
            'Other Legal Compliance Document',
        ];

        return view('config.companies-branches', compact(
            'companies',
            'allBranches',
            'metrics',
            'currentView',
            'selectedCompany',
            'branches',
            'legalDocuments',
            'vaultStats',
            'currencies',
            'timezones',
            'branchTypes',
            'taxTypes',
            'docCategories'
        ));
    }

    /**
     * Show Company Details view for specific company ID.
     */
    public function showCompany(int $id): View
    {
        $request = request();
        $request->merge(['company_id' => $id, 'view' => 'details']);
        return $this->companiesBranchesIndex($request);
    }

    /**
     * Store a new Company entity.
     */
    public function storeCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_code' => ['required', 'string', 'max:50', 'unique:companies,company_code'],
            'company_legal_name' => ['required', 'string', 'max:255'],
            'company_trade_name' => ['nullable', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'in:Company,Holding Enterprise,Subsidiary,Agency'],
            'parent_company_id' => ['nullable', 'exists:companies,company_id'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'company_branch_code' => ['nullable', 'string', 'max:20'],
            'company_rdo_code' => ['nullable', 'string', 'max:20'],
            'company_tax_type' => ['nullable', 'string', 'max:50'],
            'company_is_vat_registered' => ['nullable', 'boolean'],
            'company_base_currency_code' => ['required', 'string', 'size:3'],
            'company_timezone' => ['nullable', 'string', 'max:50'],
            'company_phone_number' => ['nullable', 'string', 'max:30'],
            'company_email_address' => ['nullable', 'email', 'max:255'],
            'company_website_url' => ['nullable', 'string', 'max:255'],
            'company_email_domain' => ['nullable', 'string', 'max:150'],
            'company_brand_color' => ['nullable', 'string', 'max:30'],
            'company_external_code' => ['nullable', 'string', 'max:100'],
            'company_address_line_1' => ['nullable', 'string', 'max:150'],
            'company_address_line_2' => ['nullable', 'string', 'max:150'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_state_province_code' => ['nullable', 'string', 'max:50'],
            'company_postal_code' => ['nullable', 'string', 'max:20'],
            'company_country_code' => ['nullable', 'string', 'size:2'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
        ]);

        $validated['company_type'] = $validated['company_type'] ?? 'Company';
        $validated['company_is_vat_registered'] = $request->has('company_is_vat_registered');
        if ($validated['company_is_vat_registered']) {
            $validated['company_tax_type'] = 'VAT Registered';
        }
        $validated['company_country_code'] = $validated['company_country_code'] ?? 'PH';
        $validated['company_timezone'] = $validated['company_timezone'] ?? 'Asia/Manila';
        $validated['company_is_active'] = true;
        $validated['company_created_by'] = Auth::id();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business_branding', 'public');
            $validated['company_logo'] = $path;
        }

        $company = Company::create($validated);
        AuditLogger::log('Create', 'Companies', $company->company_id, "Created company {$company->company_legal_name} ({$company->company_code})");

        return redirect()->route('config.companies-branches.show', $company->company_id)
            ->with('success', "Company '{$company->company_legal_name}' created successfully.");
    }

    /**
     * Update an existing Company entity.
     */
    public function updateCompany(Request $request, int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);

        $validated = $request->validate([
            'company_code' => ['required', 'string', 'max:50', Rule::unique('companies', 'company_code')->ignore($company->company_id, 'company_id')],
            'company_legal_name' => ['required', 'string', 'max:255'],
            'company_trade_name' => ['nullable', 'string', 'max:255'],
            'company_type' => ['nullable', 'string', 'in:Company,Holding Enterprise,Subsidiary,Agency'],
            'parent_company_id' => ['nullable', 'exists:companies,company_id'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'company_branch_code' => ['nullable', 'string', 'max:20'],
            'company_rdo_code' => ['nullable', 'string', 'max:20'],
            'company_tax_type' => ['nullable', 'string', 'max:50'],
            'company_is_vat_registered' => ['nullable', 'boolean'],
            'company_base_currency_code' => ['required', 'string', 'size:3'],
            'company_timezone' => ['nullable', 'string', 'max:50'],
            'company_phone_number' => ['nullable', 'string', 'max:30'],
            'company_email_address' => ['nullable', 'email', 'max:255'],
            'company_website_url' => ['nullable', 'string', 'max:255'],
            'company_email_domain' => ['nullable', 'string', 'max:150'],
            'company_brand_color' => ['nullable', 'string', 'max:30'],
            'company_external_code' => ['nullable', 'string', 'max:100'],
            'company_address_line_1' => ['nullable', 'string', 'max:150'],
            'company_address_line_2' => ['nullable', 'string', 'max:150'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_state_province_code' => ['nullable', 'string', 'max:50'],
            'company_postal_code' => ['nullable', 'string', 'max:20'],
            'company_country_code' => ['nullable', 'string', 'size:2'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
        ]);

        $validated['company_is_vat_registered'] = $request->has('company_is_vat_registered');
        if ($validated['company_is_vat_registered']) {
            $validated['company_tax_type'] = 'VAT Registered';
        }
        $validated['company_updated_by'] = Auth::id();

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business_branding', 'public');
            $validated['company_logo'] = $path;
        }

        $company->update($validated);
        AuditLogger::log('Update', 'Companies', $company->company_id, "Updated company profile for {$company->company_legal_name}");

        return redirect()->route('config.companies-branches.show', $company->company_id)
            ->with('success', "Company '{$company->company_legal_name}' details updated successfully.");
    }

    /**
     * Toggle company active status.
     */
    public function toggleCompanyStatus(int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        $company->update(['company_is_active' => !$company->company_is_active]);

        $statusStr = $company->company_is_active ? 'activated' : 'deactivated';
        AuditLogger::log('Update', 'Companies', $company->company_id, "{$statusStr} company '{$company->company_legal_name}'");

        return redirect()->back()->with('success', "Company '{$company->company_legal_name}' has been {$statusStr}.");
    }

    /**
     * Delete a company entity and all its child branch and document records.
     */
    public function destroyCompany(int $id): RedirectResponse
    {
        $company = Company::findOrFail($id);
        $name = $company->company_legal_name;

        // If this company is currently active in session context, reset or switch
        if (session('active_company_id') == $company->company_id) {
            session()->forget(['active_company_id', 'active_branch_id']);
            $fallback = Company::where('company_id', '!=', $id)->first();
            if ($fallback) {
                session(['active_company_id' => $fallback->company_id]);
                $fallbackBranch = Branch::where('company_id', $fallback->company_id)->first();
                if ($fallbackBranch) {
                    session(['active_branch_id' => $fallbackBranch->branch_id]);
                }
            }
        }

        // Clean up linked branches and their pivot entries
        $branchIds = $company->branches()->pluck('branch_id')->toArray();
        if (!empty($branchIds)) {
            DB::table('user_branches')->whereIn('branch_id', $branchIds)->delete();
            $company->branches()->delete();
        }

        // Clean up documents and files
        foreach ($company->legalDocuments as $doc) {
            if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
            $doc->delete();
        }

        DB::table('user_companies')->where('company_id', $company->company_id)->delete();

        // Delete logo if exists
        if ($company->company_logo && Storage::disk('public')->exists($company->company_logo)) {
            Storage::disk('public')->delete($company->company_logo);
        }

        $company->delete();
        AuditLogger::log('Delete', 'Companies', $id, "Deleted company '{$name}' and all associated records");

        return redirect()->route('config.companies-branches.index')
            ->with('success', "Company '{$name}' was successfully deleted.");
    }

    /**
     * Update Tab A: General Business Profile.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $company = $this->context->getActiveCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'No active company selected.');
        }

        $validated = $request->validate([
            'company_legal_name' => ['required', 'string', 'max:255'],
            'company_trade_name' => ['nullable', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50', Rule::unique('companies', 'company_code')->ignore($company->company_id, 'company_id')],
            'company_base_currency_code' => ['required', 'string', 'size:3'],
            'company_timezone' => ['required', 'string', 'max:50'],
            'fiscal_year_start_month' => ['required', 'integer', 'between:1,12'],
            'company_contact_person' => ['nullable', 'string', 'max:100'],
            'company_phone_number' => ['nullable', 'string', 'max:30'],
            'company_email_address' => ['nullable', 'email', 'max:255'],
            'company_website_url' => ['nullable', 'string', 'max:255'],
            'company_address_line_1' => ['nullable', 'string', 'max:150'],
            'company_address_line_2' => ['nullable', 'string', 'max:150'],
            'company_city' => ['nullable', 'string', 'max:100'],
            'company_state_province_code' => ['nullable', 'string', 'max:50'],
            'company_postal_code' => ['nullable', 'string', 'max:20'],
            'company_country_code' => ['required', 'string', 'size:2'],
            'company_branch_code' => ['nullable', 'string', 'max:20'],
            'company_email_domain' => ['nullable', 'string', 'max:150'],
            'company_brand_color' => ['nullable', 'string', 'max:30'],
            'company_external_code' => ['nullable', 'string', 'max:100'],
            'company_is_vat_registered' => ['nullable', 'boolean'],
            'company_tax_id' => ['nullable', 'string', 'max:50'],
            'company_rdo_code' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
        ]);

        if ($request->has('company_is_vat_registered')) {
            $validated['company_is_vat_registered'] = (bool) $request->input('company_is_vat_registered');
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('business_branding', 'public');
            $validated['company_logo'] = $path;
        }

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('business_branding', 'public');
            $validated['company_favicon'] = $path;
        }

        $company->update($validated);
        AuditLogger::log('Update', 'BusinessSettings', $company->company_id, "Updated general profile for {$company->company_legal_name}");

        return redirect()->route('config.business-settings', ['tab' => 'general'])
            ->with('success', 'General business profile updated successfully.');
    }

    /**
     * Update Tab B: Certificate & Legal Registration Hub.
     */
    public function updateLegalHub(Request $request): RedirectResponse
    {
        $company = $this->context->getActiveCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'No active company selected.');
        }

        $validated = $request->validate([
            'company_tax_id' => ['required', 'string', 'max:50'],
            'company_tax_type' => ['required', 'string', 'max:50'],
            'company_rdo_code' => ['nullable', 'string', 'max:20'],
            'company_registration_no' => ['nullable', 'string', 'max:100'],
            'bir_permit_number' => ['nullable', 'string', 'max:100'],
            'invoice_header_text' => ['nullable', 'string', 'max:1000'],
            'invoice_footer_text' => ['nullable', 'string', 'max:1000'],
        ]);

        $company->update($validated);
        AuditLogger::log('Update', 'BusinessSettings', $company->company_id, "Updated legal registration and tax defaults for {$company->company_legal_name}");

        return redirect()->route('config.business-settings', ['tab' => 'legal'])
            ->with('success', 'Certificate & Legal Registration Hub settings saved successfully.');
    }

    /**
     * Update Tab C: Hierarchy & Relationship Setup.
     */
    public function updateHierarchy(Request $request): RedirectResponse
    {
        $company = $this->context->getActiveCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'No active company selected.');
        }

        $validated = $request->validate([
            'parent_company_id' => ['nullable', 'exists:companies,company_id'],
            'company_type' => ['required', 'string', 'in:Company,Holding Enterprise,Subsidiary,Agency'],
            'share_chart_of_accounts' => ['nullable', 'boolean'],
            'share_customer_vendor_directory' => ['nullable', 'boolean'],
            'share_product_catalog' => ['nullable', 'boolean'],
        ]);

        $validated['share_chart_of_accounts'] = $request->has('share_chart_of_accounts');
        $validated['share_customer_vendor_directory'] = $request->has('share_customer_vendor_directory');
        $validated['share_product_catalog'] = $request->has('share_product_catalog');

        $company->update($validated);
        AuditLogger::log('Update', 'BusinessSettings', $company->company_id, "Updated hierarchy & sharing settings for {$company->company_legal_name}");

        return redirect()->route('config.business-settings', ['tab' => 'hierarchy'])
            ->with('success', 'Organizational hierarchy and sharing rules updated.');
    }

    /**
     * Store a document in the Document Vault.
     */
    public function storeDocument(Request $request): RedirectResponse
    {
        $company = $this->context->getActiveCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'No active company selected.');
        }

        $validated = $request->validate([
            'document_category' => ['required', 'string', 'max:100'],
            'document_name' => ['required', 'string', 'max:150'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:150'],
            'issue_date' => ['nullable', 'date'],
            'expiration_date' => ['nullable', 'date'],
            'renewal_reminder_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $file = $request->file('document_file');
        $filePath = $file->store('business_documents', 'public');

        BusinessLegalDocument::create([
            'documentable_type' => Company::class,
            'documentable_id' => $company->company_id,
            'document_category' => $validated['document_category'],
            'document_name' => $validated['document_name'],
            'document_number' => $validated['document_number'] ?? null,
            'issuing_authority' => $validated['issuing_authority'] ?? null,
            'issue_date' => $validated['issue_date'] ?? null,
            'expiration_date' => $validated['expiration_date'] ?? null,
            'renewal_reminder_days' => $validated['renewal_reminder_days'] ?? 30,
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size_kb' => (int) round($file->getSize() / 1024),
            'status' => 'Active',
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        AuditLogger::log('Create', 'DocumentVault', $company->company_id, "Uploaded legal document '{$validated['document_name']}'");

        return redirect()->route('config.business-settings', ['tab' => 'legal'])
            ->with('success', "Document '{$validated['document_name']}' securely stored in Document Vault.");
    }

    /**
     * Delete a document from the Document Vault.
     */
    public function destroyDocument(int $id): RedirectResponse
    {
        $doc = BusinessLegalDocument::findOrFail($id);
        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $name = $doc->document_name;
        $doc->delete();

        AuditLogger::log('Delete', 'DocumentVault', $id, "Deleted document '{$name}'");

        return redirect()->route('config.business-settings', ['tab' => 'legal'])
            ->with('success', "Document '{$name}' deleted from Document Vault.");
    }

    /**
     * Create an operational branch under current company.
     */
    public function storeBranch(Request $request): RedirectResponse
    {
        $company = $this->context->getActiveCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'No active company selected.');
        }

        $validated = $request->validate([
            'branch_code' => ['required', 'string', 'max:50', 'unique:branches,branch_code'],
            'branch_name' => ['required', 'string', 'max:150'],
            'branch_tax_code' => ['nullable', 'string', 'max:50'],
            'branch_type' => ['required', 'string', 'max:50'],
            'operating_hours' => ['nullable', 'string', 'max:150'],
            'branch_contact_person' => ['nullable', 'string', 'max:100'],
            'branch_phone_number' => ['nullable', 'string', 'max:30'],
            'branch_email_address' => ['nullable', 'email', 'max:255'],
            'branch_address_line_1' => ['nullable', 'string', 'max:150'],
            'branch_city' => ['nullable', 'string', 'max:100'],
            'branch_postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $validated['company_id'] = $company->company_id;
        $validated['branch_is_active'] = true;
        $validated['branch_created_by'] = Auth::id();

        $branch = Branch::create($validated);
        AuditLogger::log('Create', 'Branches', $branch->branch_id, "Created branch '{$branch->branch_name}' ({$branch->branch_code})");

        return redirect()->route('config.business-settings', ['tab' => 'branches'])
            ->with('success', "Branch '{$branch->branch_name}' created successfully.");
    }

    /**
     * Update an operational branch.
     */
    public function updateBranch(Request $request, int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'branch_code' => ['required', 'string', 'max:50', Rule::unique('branches', 'branch_code')->ignore($branch->branch_id, 'branch_id')],
            'branch_name' => ['required', 'string', 'max:150'],
            'branch_tax_code' => ['nullable', 'string', 'max:50'],
            'branch_type' => ['required', 'string', 'max:50'],
            'operating_hours' => ['nullable', 'string', 'max:150'],
            'branch_contact_person' => ['nullable', 'string', 'max:100'],
            'branch_phone_number' => ['nullable', 'string', 'max:30'],
            'branch_email_address' => ['nullable', 'email', 'max:255'],
            'branch_address_line_1' => ['nullable', 'string', 'max:150'],
            'branch_city' => ['nullable', 'string', 'max:100'],
            'branch_postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $branch->update($validated);
        AuditLogger::log('Update', 'Branches', $branch->branch_id, "Updated branch '{$branch->branch_name}'");

        return redirect()->route('config.business-settings', ['tab' => 'branches'])
            ->with('success', "Branch '{$branch->branch_name}' updated successfully.");
    }

    /**
     * Toggle branch active status.
     */
    public function toggleBranchStatus(int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);
        $branch->update(['branch_is_active' => !$branch->branch_is_active]);

        $statusStr = $branch->branch_is_active ? 'activated' : 'deactivated';
        AuditLogger::log('Update', 'Branches', $branch->branch_id, "{$statusStr} branch '{$branch->branch_name}'");

        return redirect()->route('config.business-settings', ['tab' => 'branches'])
            ->with('success', "Branch '{$branch->branch_name}' {$statusStr}.");
    }

    /**
     * Delete an operational branch.
     */
    public function destroyBranch(int $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);
        $name = $branch->branch_name;
        $companyId = $branch->company_id;

        if (session('active_branch_id') == $branch->branch_id) {
            session()->forget('active_branch_id');
            $fallback = Branch::where('company_id', $companyId)->where('branch_id', '!=', $id)->first();
            if ($fallback) {
                session(['active_branch_id' => $fallback->branch_id]);
            }
        }

        DB::table('user_branches')->where('branch_id', $branch->branch_id)->delete();
        $branch->delete();

        AuditLogger::log('Delete', 'Branches', $id, "Deleted branch '{$name}'");

        return redirect()->back()
            ->with('success', "Branch '{$name}' was successfully deleted.");
    }
}
