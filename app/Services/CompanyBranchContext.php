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
    protected bool $companyResolved = false;
    protected ?Company $cachedCompany = null;
    protected bool $branchResolved = false;
    protected ?Branch $cachedBranch = null;
    protected ?Collection $cachedAllowedCompanies = null;
    protected array $cachedAllowedBranches = [];
    protected ?int $cachedForUserId = null;
    protected ?int $cachedSessionCompanyId = null;
    protected ?int $cachedSessionBranchId = null;

    protected function syncCacheUser(): void
    {
        $userId = Auth::id();
        $sessionCompanyId = Session::has('active_company_id') ? (int) Session::get('active_company_id') : null;
        $sessionBranchId = Session::has('active_branch_id') ? (int) Session::get('active_branch_id') : null;

        if (
            $this->cachedForUserId !== $userId ||
            ($sessionCompanyId !== null && $this->cachedSessionCompanyId !== $sessionCompanyId) ||
            ($sessionBranchId !== null && $this->cachedSessionBranchId !== $sessionBranchId)
        ) {
            $this->clearCache();
            $this->cachedForUserId = $userId;
            $this->cachedSessionCompanyId = $sessionCompanyId;
            $this->cachedSessionBranchId = $sessionBranchId;
        }
    }

    public function clearCache(): void
    {
        $this->companyResolved = false;
        $this->cachedCompany = null;
        $this->branchResolved = false;
        $this->cachedBranch = null;
        $this->cachedAllowedCompanies = null;
        $this->cachedAllowedBranches = [];
    }

    /**
     * Get the active company for the current session.
     */
    public function getActiveCompany(): ?Company
    {
        $this->syncCacheUser();
        if ($this->companyResolved) {
            return $this->cachedCompany;
        }

        $companyId = Session::get('active_company_id');
        $user = Auth::user();

        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && (!$user || $user->canAccessCompany($company->company_id))) {
                $this->cachedCompany = $company;
                $this->companyResolved = true;
                return $company;
            }
        }

        // Fallback to user default
        if ($user && $user->default_company_id) {
            $company = Company::find($user->default_company_id);
            if ($company && $user->canAccessCompany($company->company_id)) {
                $this->setActiveCompany($company->company_id);
                $this->cachedCompany = $company;
                $this->companyResolved = true;
                return $company;
            }
        }

        // Fallback to first allowed company
        $allowed = $this->getAllowedCompanies();
        if ($allowed->isNotEmpty()) {
            $company = $allowed->first();
            $this->setActiveCompany($company->company_id);
            $this->cachedCompany = $company;
            $this->companyResolved = true;
            return $company;
        }

        // Absolute system fallback
        $company = Company::active()->first();
        if ($company) {
            $this->setActiveCompany($company->company_id);
        }

        $this->cachedCompany = $company;
        $this->companyResolved = true;

        return $company;
    }

    /**
     * Get the active branch for the current session.
     */
    public function getActiveBranch(): ?Branch
    {
        $this->syncCacheUser();
        if ($this->branchResolved) {
            return $this->cachedBranch;
        }

        $branchId = Session::get('active_branch_id');
        $user = Auth::user();
        $company = $this->getActiveCompany();

        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && (!$company || $branch->company_id == $company->company_id) && (!$user || $user->canAccessBranch($branch->branch_id))) {
                $this->cachedBranch = $branch;
                $this->branchResolved = true;
                return $branch;
            }
        }

        // Fallback to user default branch
        if ($user && $user->default_branch_id) {
            $branch = Branch::find($user->default_branch_id);
            if ($branch && (!$company || $branch->company_id == $company->company_id) && $user->canAccessBranch($branch->branch_id)) {
                $this->setActiveBranch($branch->branch_id);
                $this->cachedBranch = $branch;
                $this->branchResolved = true;
                return $branch;
            }
        }

        // Fallback to first allowed branch under current company
        $allowed = $this->getAllowedBranches($company?->company_id);
        if ($allowed->isNotEmpty()) {
            $branch = $allowed->first();
            $this->setActiveBranch($branch->branch_id);
            $this->cachedBranch = $branch;
            $this->branchResolved = true;
            return $branch;
        }

        // Fallback to any branch under current company
        if ($company) {
            $branch = Branch::where('company_id', $company->company_id)->active()->first();
            if ($branch) {
                $this->setActiveBranch($branch->branch_id);
                $this->cachedBranch = $branch;
                $this->branchResolved = true;
                return $branch;
            }
        }

        $branch = Branch::active()->first();
        if ($branch) {
            $this->setActiveBranch($branch->branch_id);
        }

        $this->cachedBranch = $branch;
        $this->branchResolved = true;

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
        $this->syncCacheUser();
        if ($this->cachedAllowedCompanies !== null) {
            return $this->cachedAllowedCompanies;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return $this->cachedAllowedCompanies = Company::active()->orderBy('company_legal_name')->get();
        }

        if ($user->isSuperAdmin()) {
            return $this->cachedAllowedCompanies = Company::active()->orderBy('company_legal_name')->get();
        }

        $userCompanies = $user->companies()->active()->orderBy('company_legal_name')->get();
        if ($userCompanies->isEmpty()) {
            return $this->cachedAllowedCompanies = Company::active()->take(1)->get();
        }

        return $this->cachedAllowedCompanies = $userCompanies;
    }

    /**
     * Get list of branches allowed for current user and company.
     */
    public function getAllowedBranches(?int $companyId = null): Collection
    {
        $this->syncCacheUser();
        $cacheKey = $companyId ?? 'all';
        if (array_key_exists($cacheKey, $this->cachedAllowedBranches)) {
            return $this->cachedAllowedBranches[$cacheKey];
        }

        /** @var User|null $user */
        $user = Auth::user();
        $query = Branch::active();

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!$user || $user->isSuperAdmin()) {
            return $this->cachedAllowedBranches[$cacheKey] = $query->orderBy('branch_name')->get();
        }

        $userBranchIds = $user->branches()->pluck('branches.branch_id')->toArray();
        if (!empty($userBranchIds)) {
            $query->whereIn('branch_id', $userBranchIds);
        }

        return $this->cachedAllowedBranches[$cacheKey] = $query->orderBy('branch_name')->get();
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
     * Set the multi-entity view scope in session and optionally promote the newly ticked entity to Active Write Target.
     */
    public function setCheckedEntities(
        array $companyIds,
        array $branchIds,
        ?int $preferredActiveCompanyId = null,
        ?int $preferredActiveBranchId = null
    ): void {
        $cleanCompIds = array_values(array_unique(array_filter(array_map('intval', $companyIds))));
        $cleanBranchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));

        /** @var User|null $user */
        $user = Auth::user();

        if ($preferredActiveBranchId !== null) {
            $branch = Branch::find($preferredActiveBranchId);
            if ($branch && (!$user || ($user->canAccessBranch((int) $branch->branch_id) && $user->canAccessCompany((int) $branch->company_id)))) {
                $this->setActiveCompany((int) $branch->company_id);
                $this->setActiveBranch((int) $branch->branch_id);
                Session::put('is_consolidated_view', false);

                if (!in_array((int) $branch->company_id, $cleanCompIds, true)) {
                    $cleanCompIds[] = (int) $branch->company_id;
                }
                if (!in_array((int) $branch->branch_id, $cleanBranchIds, true)) {
                    $cleanBranchIds[] = (int) $branch->branch_id;
                }
            }
        } elseif ($preferredActiveCompanyId !== null) {
            $company = Company::find($preferredActiveCompanyId);
            if ($company && (!$user || $user->canAccessCompany((int) $company->company_id))) {
                $this->setActiveCompany((int) $company->company_id);
                Session::put('is_consolidated_view', false);

                if (!in_array((int) $company->company_id, $cleanCompIds, true)) {
                    $cleanCompIds[] = (int) $company->company_id;
                }

                $allowedBranches = $this->getAllowedBranches((int) $company->company_id);
                $checkedCompanyBranch = $allowedBranches->first(fn ($b) => in_array((int) $b->branch_id, $cleanBranchIds, true));
                $targetBranch = $checkedCompanyBranch ?? $allowedBranches->first();

                if ($targetBranch) {
                    $this->setActiveBranch((int) $targetBranch->branch_id);
                    if (!in_array((int) $targetBranch->branch_id, $cleanBranchIds, true)) {
                        $cleanBranchIds[] = (int) $targetBranch->branch_id;
                    }
                }
            }
        } elseif (!empty($cleanBranchIds)) {
            $currentBranchId = Session::has('active_branch_id') ? (int) Session::get('active_branch_id') : null;
            if ($currentBranchId === null || !in_array($currentBranchId, $cleanBranchIds, true)) {
                $fallbackBranchId = (int) end($cleanBranchIds);
                $branch = Branch::find($fallbackBranchId);
                if ($branch && (!$user || $user->canAccessBranch((int) $branch->branch_id))) {
                    $this->setActiveCompany((int) $branch->company_id);
                    $this->setActiveBranch((int) $branch->branch_id);
                    Session::put('is_consolidated_view', false);
                    if (!in_array((int) $branch->company_id, $cleanCompIds, true)) {
                        $cleanCompIds[] = (int) $branch->company_id;
                    }
                }
            }
        }

        Session::put('checked_company_ids', $cleanCompIds);
        Session::put('checked_branch_ids', $cleanBranchIds);
        $this->clearCache();
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

        $this->clearCache();

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
        $this->companyResolved = false;
        $this->cachedCompany = null;
    }

    protected function setActiveBranch(int $branchId): void
    {
        Session::put('active_branch_id', $branchId);
        $this->branchResolved = false;
        $this->cachedBranch = null;
    }
}
