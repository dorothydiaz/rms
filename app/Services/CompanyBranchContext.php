<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CompanyBranchContext
{
    protected ?Company $cachedCompany = null;
    protected ?Branch $cachedBranch = null;

    /**
     * Get the active company for the current session.
     */
    public function getActiveCompany(): ?Company
    {
        if ($this->cachedCompany) {
            return $this->cachedCompany;
        }

        $companyId = Session::get('active_company_id');
        $user = Auth::user();

        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && (!$user || $user->canAccessCompany($company->company_id))) {
                $this->cachedCompany = $company;
                return $company;
            }
        }

        // Fallback to user default
        if ($user && $user->default_company_id) {
            $company = Company::find($user->default_company_id);
            if ($company && $user->canAccessCompany($company->company_id)) {
                $this->setActiveCompany($company->company_id);
                $this->cachedCompany = $company;
                return $company;
            }
        }

        // Fallback to first allowed company
        $allowed = $this->getAllowedCompanies();
        if ($allowed->isNotEmpty()) {
            $company = $allowed->first();
            $this->setActiveCompany($company->company_id);
            $this->cachedCompany = $company;
            return $company;
        }

        // Absolute system fallback
        $company = Company::active()->first();
        if ($company) {
            $this->setActiveCompany($company->company_id);
            $this->cachedCompany = $company;
        }

        return $company;
    }

    /**
     * Get the active branch for the current session.
     */
    public function getActiveBranch(): ?Branch
    {
        if ($this->cachedBranch) {
            return $this->cachedBranch;
        }

        $branchId = Session::get('active_branch_id');
        $user = Auth::user();
        $company = $this->getActiveCompany();

        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && (!$company || $branch->company_id == $company->company_id) && (!$user || $user->canAccessBranch($branch->branch_id))) {
                $this->cachedBranch = $branch;
                return $branch;
            }
        }

        // Fallback to user default branch
        if ($user && $user->default_branch_id) {
            $branch = Branch::find($user->default_branch_id);
            if ($branch && (!$company || $branch->company_id == $company->company_id) && $user->canAccessBranch($branch->branch_id)) {
                $this->setActiveBranch($branch->branch_id);
                $this->cachedBranch = $branch;
                return $branch;
            }
        }

        // Fallback to first allowed branch under current company
        $allowed = $this->getAllowedBranches($company?->company_id);
        if ($allowed->isNotEmpty()) {
            $branch = $allowed->first();
            $this->setActiveBranch($branch->branch_id);
            $this->cachedBranch = $branch;
            return $branch;
        }

        // Fallback to any branch under current company
        if ($company) {
            $branch = Branch::where('company_id', $company->company_id)->active()->first();
            if ($branch) {
                $this->setActiveBranch($branch->branch_id);
                $this->cachedBranch = $branch;
                return $branch;
            }
        }

        $branch = Branch::active()->first();
        if ($branch) {
            $this->setActiveBranch($branch->branch_id);
            $this->cachedBranch = $branch;
        }

        return $branch;
    }

    /**
     * Check if user is in consolidated read-only view.
     */
    public function isConsolidatedView(): bool
    {
        return (bool) Session::get('is_consolidated_view', false);
    }

    /**
     * Get list of companies the authenticated user is allowed to access.
     */
    public function getAllowedCompanies(): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return Company::active()->orderBy('company_legal_name')->get();
        }

        if ($user->isSuperAdmin()) {
            return Company::active()->orderBy('company_legal_name')->get();
        }

        $userCompanies = $user->companies()->active()->orderBy('company_legal_name')->get();
        if ($userCompanies->isEmpty()) {
            return Company::active()->take(1)->get();
        }

        return $userCompanies;
    }

    /**
     * Get list of branches allowed for current user and company.
     */
    public function getAllowedBranches(?int $companyId = null): Collection
    {
        /** @var User|null $user */
        $user = Auth::user();
        $query = Branch::active();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!$user || $user->isSuperAdmin()) {
            return $query->orderBy('branch_name')->get();
        }

        $userBranchIds = $user->branches()->pluck('branches.branch_id')->toArray();
        if (!empty($userBranchIds)) {
            $query->whereIn('branch_id', $userBranchIds);
        }

        return $query->orderBy('branch_name')->get();
    }

    /**
     * Get checked company IDs defining the multi-entity read/view scope.
     * Falls back to the single active company if scope is not explicitly set.
     */
    public function getCheckedCompanyIds(): array
    {
        $checked = Session::get('checked_company_ids');
        if (is_array($checked) && !empty($checked)) {
            return array_map('intval', $checked);
        }

        $active = $this->getActiveCompany();
        return $active ? [(int) $active->company_id] : [];
    }

    /**
     * Get checked branch IDs defining the multi-entity read/view scope.
     * Falls back to the single active branch if scope is not explicitly set.
     */
    public function getCheckedBranchIds(): array
    {
        $checked = Session::get('checked_branch_ids');
        if (is_array($checked) && !empty($checked)) {
            return array_map('intval', $checked);
        }

        $active = $this->getActiveBranch();
        return $active ? [(int) $active->branch_id] : [];
    }

    /**
     * Check if a specific company is included in the current view scope.
     */
    public function isCompanyChecked(int $companyId): bool
    {
        return in_array($companyId, $this->getCheckedCompanyIds(), true);
    }

    /**
     * Check if a specific branch is included in the current view scope.
     */
    public function isBranchChecked(int $branchId): bool
    {
        return in_array($branchId, $this->getCheckedBranchIds(), true);
    }

    /**
     * Set the multi-entity view scope in session.
     */
    public function setCheckedEntities(array $companyIds, array $branchIds): void
    {
        $cleanCompIds = array_values(array_unique(array_filter(array_map('intval', $companyIds))));
        $cleanBranchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));

        Session::put('checked_company_ids', $cleanCompIds);
        Session::put('checked_branch_ids', $cleanBranchIds);
    }

    /**
     * Switch active company and/or branch in session.
     */
    public function switchContext(int $companyId, ?int $branchId = null, bool $isConsolidated = false): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && !$user->canAccessCompany($companyId)) {
            return false;
        }

        $company = Company::find($companyId);
        if (!$company) {
            return false;
        }

        $this->setActiveCompany($companyId);
        Session::put('is_consolidated_view', $isConsolidated);

        $targetBranchId = null;
        if ($branchId) {
            if ($user && !$user->canAccessBranch($branchId)) {
                return false;
            }
            $this->setActiveBranch($branchId);
            $targetBranchId = $branchId;
        } else {
            // Pick first allowed branch under this company
            $firstBranch = $this->getAllowedBranches($companyId)->first();
            if ($firstBranch) {
                $this->setActiveBranch($firstBranch->branch_id);
                $targetBranchId = $firstBranch->branch_id;
            }
        }

        // Ensure newly active entity is automatically included in view scope
        $currentCheckedComps = $this->getCheckedCompanyIds();
        if (!in_array($companyId, $currentCheckedComps, true)) {
            $currentCheckedComps[] = $companyId;
        }

        $currentCheckedBranches = $this->getCheckedBranchIds();
        if ($isConsolidated) {
            // In consolidated mode, include all allowed branches for this company
            $allBranchIds = $this->getAllowedBranches($companyId)->pluck('branch_id')->map(fn($id) => (int)$id)->toArray();
            $currentCheckedBranches = array_unique(array_merge($currentCheckedBranches, $allBranchIds));
        } elseif ($targetBranchId && !in_array($targetBranchId, $currentCheckedBranches, true)) {
            $currentCheckedBranches[] = $targetBranchId;
        }

        $this->setCheckedEntities($currentCheckedComps, $currentCheckedBranches);

        $this->cachedCompany = null;
        $this->cachedBranch = null;

        return true;
    }

    /**
     * Set persistent default landing location for user.
     */
    public function setDefaultLanding(int $companyId, int $branchId): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->update([
                'default_company_id' => $companyId,
                'default_branch_id' => $branchId,
            ]);
        }
    }

    protected function setActiveCompany(int $companyId): void
    {
        Session::put('active_company_id', $companyId);
    }

    protected function setActiveBranch(int $branchId): void
    {
        Session::put('active_branch_id', $branchId);
    }
}
