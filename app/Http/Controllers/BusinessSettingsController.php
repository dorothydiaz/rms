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
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
        ]);

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
}
