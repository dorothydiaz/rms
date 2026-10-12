<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\CompanyBranchContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyBranchContext
{
    public function __construct(protected CompanyBranchContext $context)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentCompany = $this->context->getActiveCompany();
        $currentBranch = $this->context->getActiveBranch();
        $allowedCompanies = $this->context->getAllowedCompanies();
        $isConsolidatedView = $this->context->isConsolidatedView();

        // Load nested branches for the navigation accordion
        $navCompaniesTree = Company::active()
            ->with(['branches' => fn($q) => $q->active()->orderBy('branch_name')])
            ->orderBy('company_legal_name')
            ->get();

        View::share([
            'currentCompany' => $currentCompany,
            'currentBranch' => $currentBranch,
            'allowedCompanies' => $allowedCompanies,
            'isConsolidatedView' => $isConsolidatedView,
            'navCompaniesTree' => $navCompaniesTree,
            'checkedCompanyIds' => $this->context->getCheckedCompanyIds(),
            'checkedBranchIds' => $this->context->getCheckedBranchIds(),
        ]);

        return $next($request);
    }
}
