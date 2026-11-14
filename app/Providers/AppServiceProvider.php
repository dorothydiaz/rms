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

        // Memoize static asset mtime versions once per process/cache to avoid per-request NTFS stat calls
        static $memoizedAssetVersions = null;
        if ($memoizedAssetVersions === null) {
            $memoizedAssetVersions = Cache::remember('rms_asset_versions', 300, function () {
                $files = [
                    'logo-tab.svg' => 'assets/img/logo-tab.svg',
                    'theme-tokens.css' => 'assets/css/theme-tokens.css',
                    'styles.css' => 'assets/css/styles.css',
                    'uom-dropdown.css' => 'assets/css/uom-dropdown.css',
                    'script.js' => 'assets/js/script.js',
                    'uom-registry.js' => 'assets/js/uom-registry.js',
                    'erp-enterprise.css' => 'assets/css/erp-enterprise.css',
                    'erp-enterprise.js' => 'assets/js/erp-enterprise.js',
                ];
                $versions = [];
                foreach ($files as $key => $relPath) {
                    $fullPath = public_path($relPath);
                    $versions[$key] = file_exists($fullPath) ? (string) filemtime($fullPath) : '1.0';
                }
                return $versions;
            });
        }
        View::share('assetVersions', $memoizedAssetVersions);

        // Global Navigation & Context Hierarchy View Composer (Zero DB hits on repeat navigation)
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if ($user) {
                $context = app(CompanyBranchContext::class);
                $navCompaniesTree = Cache::remember('user_nav_tree_v2_' . ($user->id ?? 'default'), 3600, function () {
                    return Company::active()
                        ->withCount('childCompanies')
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
