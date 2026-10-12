<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Services\CompanyBranchContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContextSwitcherController extends Controller
{
    public function __construct(protected CompanyBranchContext $context)
    {
    }

    /**
     * Switch the active working company and branch context.
     */
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,company_id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,branch_id'],
            'is_consolidated' => ['nullable', 'boolean'],
        ]);

        $companyId = (int) $validated['company_id'];
        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $isConsolidated = $request->boolean('is_consolidated');

        $success = $this->context->switchContext($companyId, $branchId, $isConsolidated);

        if (!$success) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized or invalid entity selection.'], 403);
            }
            return redirect()->back()->with('error', 'You do not have permission to switch to this entity.');
        }

        $company = Company::find($companyId);
        $branch = $branchId ? Branch::find($branchId) : null;
        $label = $isConsolidated 
            ? "{$company?->company_trade_name} (Consolidated View)" 
            : "{$company?->company_trade_name} > {$branch?->branch_name}";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Switched active context to {$label}",
                'company' => $company,
                'branch' => $branch,
                'is_consolidated' => $isConsolidated,
            ]);
        }

        return redirect()->back()->with('success', "Active context switched to {$label}.");
    }

    /**
     * Set user's default company and branch for automatic login landing.
     */
    public function setDefaultLanding(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'default_company_id' => ['required', 'integer', 'exists:companies,company_id'],
            'default_branch_id' => ['required', 'integer', 'exists:branches,branch_id'],
        ]);

        $this->context->setDefaultLanding(
            (int) $validated['default_company_id'],
            (int) $validated['default_branch_id']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Default landing location saved successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Default landing location saved successfully.');
    }

    /**
     * Update the multi-entity checkbox read/view scope.
     */
    public function updateScope(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', 'exists:companies,company_id'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,branch_id'],
        ]);

        $companyIds = $validated['company_ids'] ?? [];
        $branchIds = $validated['branch_ids'] ?? [];

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            $companyIds = array_values(array_filter($companyIds, fn($id) => $user->canAccessCompany((int)$id)));
            $branchIds = array_values(array_filter($branchIds, fn($id) => $user->canAccessBranch((int)$id)));
        }

        $this->context->setCheckedEntities($companyIds, $branchIds);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'View scope updated successfully.',
                'checked_company_ids' => $this->context->getCheckedCompanyIds(),
                'checked_branch_ids' => $this->context->getCheckedBranchIds(),
            ]);
        }

        return redirect()->back()->with('success', 'View scope updated successfully.');
    }
}
