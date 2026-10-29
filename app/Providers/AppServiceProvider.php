<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\CompanyBranchContext;
use App\Models\Company;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CompanyBranchContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.custom');

        // Global Navigation & Context Hierarchy View Composer (Zero DB hits on repeat navigation)
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if ($user) {
                $context = app(CompanyBranchContext::class);
                $navCompaniesTree = Cache::remember('user_nav_tree_' . ($user->id ?? 'default'), 3600, function () {
                    return Company::active()
                        ->with(['branches' => fn($q) => $q->active()->orderBy('branch_name')])
                        ->orderBy('company_legal_name')
                        ->get();
                });

                $view->with([
                    'currentCompany' => $context->getActiveCompany(),
                    'currentBranch' => $context->getActiveBranch(),
                    'navCompaniesTree' => $navCompaniesTree,
                    'allowedCompanies' => $context->getAllowedCompanies(),
                    'checkedCompanyIds' => $context->getCheckedCompanyIds(),
                    'checkedBranchIds' => $context->getCheckedBranchIds(),
                    'isConsolidatedView' => $context->isConsolidatedView(),
                ]);
            }
        });
    }
}
