<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\CreditsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Restaurant Management System (RMS)
|--------------------------------------------------------------------------
*/

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Session Termination
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/logout', [AuthController::class, 'logout']); // Fallback GET redirect

    // Main Operations Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // HR Operations (Full HRIS Suite)
    Route::prefix('hr')->name('hr.')->group(function () {
        // 1. Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Hr\HrDashboardController::class, 'index'])->name('dashboard');

        // 2. People Administration
        Route::prefix('people')->name('people.')->group(function () {
            Route::get('/employees', [\App\Http\Controllers\Hr\PeopleController::class, 'employeesIndex'])->name('employees');
            Route::post('/employees', [\App\Http\Controllers\Hr\PeopleController::class, 'employeeStore'])->name('employees.store');
            Route::get('/employees/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'employeeShow'])->name('employees.show');
            Route::put('/employees/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'employeeUpdate'])->name('employees.update');
            Route::delete('/employees/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'employeeDestroy'])->name('employees.destroy');

            Route::get('/departments', [\App\Http\Controllers\Hr\PeopleController::class, 'departmentsIndex'])->name('departments');
            Route::post('/departments', [\App\Http\Controllers\Hr\PeopleController::class, 'departmentStore'])->name('departments.store');
            Route::put('/departments/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'departmentUpdate'])->name('departments.update');

            Route::get('/positions', [\App\Http\Controllers\Hr\PeopleController::class, 'positionsIndex'])->name('positions');
            Route::post('/positions', [\App\Http\Controllers\Hr\PeopleController::class, 'positionStore'])->name('positions.store');
            Route::put('/positions/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'positionUpdate'])->name('positions.update');

            Route::get('/branches', [\App\Http\Controllers\Hr\PeopleController::class, 'branchesIndex'])->name('branches');
            Route::post('/branches', [\App\Http\Controllers\Hr\PeopleController::class, 'branchStore'])->name('branches.store');
            Route::put('/branches/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'branchUpdate'])->name('branches.update');

            Route::get('/companies', [\App\Http\Controllers\Hr\PeopleController::class, 'companiesIndex'])->name('companies');
            Route::post('/companies', [\App\Http\Controllers\Hr\PeopleController::class, 'companyStore'])->name('companies.store');
            Route::put('/companies/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'companyUpdate'])->name('companies.update');
            Route::delete('/companies/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'companyDestroy'])->name('companies.destroy');

            Route::get('/documents', [\App\Http\Controllers\Hr\PeopleController::class, 'documentsIndex'])->name('documents');
            Route::post('/documents', [\App\Http\Controllers\Hr\PeopleController::class, 'documentStore'])->name('documents.store');
            Route::get('/documents/{id}/download', [\App\Http\Controllers\Hr\PeopleController::class, 'documentDownload'])->name('documents.download');
            Route::delete('/documents/{id}', [\App\Http\Controllers\Hr\PeopleController::class, 'documentDestroy'])->name('documents.destroy');
        });

        // 3. Recruitment
        Route::prefix('recruitment')->name('recruitment.')->group(function () {
            Route::get('/vacancies', [\App\Http\Controllers\Hr\RecruitmentController::class, 'vacanciesIndex'])->name('vacancies');
            Route::post('/vacancies', [\App\Http\Controllers\Hr\RecruitmentController::class, 'vacancyStore'])->name('vacancies.store');
            Route::put('/vacancies/{id}', [\App\Http\Controllers\Hr\RecruitmentController::class, 'vacancyUpdate'])->name('vacancies.update');

            Route::get('/applicants', [\App\Http\Controllers\Hr\RecruitmentController::class, 'applicantsIndex'])->name('applicants');
            Route::post('/applicants', [\App\Http\Controllers\Hr\RecruitmentController::class, 'applicantStore'])->name('applicants.store');
            Route::put('/applicants/{id}', [\App\Http\Controllers\Hr\RecruitmentController::class, 'applicantUpdate'])->name('applicants.update');
            Route::post('/applicants/{id}/convert', [\App\Http\Controllers\Hr\RecruitmentController::class, 'applicantConvertToEmployee'])->name('applicants.convert');

            Route::get('/interviews', [\App\Http\Controllers\Hr\RecruitmentController::class, 'interviewsIndex'])->name('interviews');
            Route::post('/interviews', [\App\Http\Controllers\Hr\RecruitmentController::class, 'interviewStore'])->name('interviews.store');
            Route::put('/interviews/{id}', [\App\Http\Controllers\Hr\RecruitmentController::class, 'interviewUpdate'])->name('interviews.update');
        });

        // 4. Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/timekeeping', [\App\Http\Controllers\Hr\AttendanceController::class, 'timekeepingIndex'])->name('timekeeping');
            Route::post('/timekeeping', [\App\Http\Controllers\Hr\AttendanceController::class, 'timekeepingStore'])->name('timekeeping.store');

            Route::get('/dtr', [\App\Http\Controllers\Hr\AttendanceController::class, 'dtrIndex'])->name('dtr');
            Route::get('/dtr/tags', [\App\Http\Controllers\Hr\AttendanceController::class, 'dtrTags'])->name('dtr.tags');
            Route::post('/dtr/export-pdf', [\App\Http\Controllers\Hr\AttendanceController::class, 'dtrExportPdf'])->name('dtr.export-pdf');

            Route::get('/schedules', [\App\Http\Controllers\Hr\AttendanceController::class, 'schedulesIndex'])->name('schedules');
            Route::post('/schedules', [\App\Http\Controllers\Hr\AttendanceController::class, 'scheduleStore'])->name('schedules.store');
            Route::post('/shifts', [\App\Http\Controllers\Hr\AttendanceController::class, 'shiftTemplateStore'])->name('shifts.store');

            Route::get('/overtime', [\App\Http\Controllers\Hr\AttendanceController::class, 'overtimeIndex'])->name('overtime');

            Route::get('/corrections', [\App\Http\Controllers\Hr\AttendanceController::class, 'correctionsIndex'])->name('corrections');
            Route::post('/corrections', [\App\Http\Controllers\Hr\AttendanceController::class, 'correctionStore'])->name('corrections.store');
            Route::post('/corrections/{id}/review', [\App\Http\Controllers\Hr\AttendanceController::class, 'correctionReview'])->name('corrections.review');
        });

        // 5. Leave & Absence
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/requests', [\App\Http\Controllers\Hr\LeaveController::class, 'requestsIndex'])->name('requests');
            Route::post('/requests', [\App\Http\Controllers\Hr\LeaveController::class, 'requestStore'])->name('requests.store');
            Route::post('/requests/{id}/review', [\App\Http\Controllers\Hr\LeaveController::class, 'requestReview'])->name('requests.review');

            Route::get('/types', [\App\Http\Controllers\Hr\LeaveController::class, 'typesIndex'])->name('types');
            Route::post('/types', [\App\Http\Controllers\Hr\LeaveController::class, 'typeStore'])->name('types.store');
            Route::put('/types/{id}', [\App\Http\Controllers\Hr\LeaveController::class, 'typeUpdate'])->name('types.update');

            Route::get('/credits', [\App\Http\Controllers\Hr\LeaveController::class, 'creditsIndex'])->name('credits');
            Route::post('/credits/adjust', [\App\Http\Controllers\Hr\LeaveController::class, 'creditsAdjust'])->name('credits.adjust');

            Route::get('/reports', [\App\Http\Controllers\Hr\LeaveController::class, 'reportsIndex'])->name('reports');
        });

        // 6. Payroll
        Route::prefix('payroll')->name('payroll.')->group(function () {
            Route::get('/periods', [\App\Http\Controllers\Hr\PayrollController::class, 'periodsIndex'])->name('periods');
            Route::post('/periods', [\App\Http\Controllers\Hr\PayrollController::class, 'periodStore'])->name('periods.store');
            Route::post('/periods/{id}/status', [\App\Http\Controllers\Hr\PayrollController::class, 'periodStatusUpdate'])->name('periods.status');

            Route::get('/process', [\App\Http\Controllers\Hr\PayrollController::class, 'processIndex'])->name('process');
            Route::post('/process/run', [\App\Http\Controllers\Hr\PayrollController::class, 'processRun'])->name('process.run');

            Route::get('/register', [\App\Http\Controllers\Hr\PayrollController::class, 'registerIndex'])->name('register');

            Route::get('/payslips', [\App\Http\Controllers\Hr\PayrollController::class, 'payslipsIndex'])->name('payslips');
            Route::get('/payslips/{id}', [\App\Http\Controllers\Hr\PayrollController::class, 'payslipShow'])->name('payslips.show');

            Route::post('/adjustments', [\App\Http\Controllers\Hr\PayrollController::class, 'adjustmentStore'])->name('adjustments.store');

            Route::get('/statutory-rules', [\App\Http\Controllers\Hr\PayrollController::class, 'statutoryRulesIndex'])->name('statutory-rules');
            Route::put('/statutory-rules/{id}', [\App\Http\Controllers\Hr\PayrollController::class, 'statutoryRuleUpdate'])->name('statutory-rules.update');

            Route::get('/reports', [\App\Http\Controllers\Hr\PayrollController::class, 'registerIndex'])->name('reports');
        });

        // 7. Performance
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/periods', [\App\Http\Controllers\Hr\PerformanceController::class, 'periodsIndex'])->name('periods');
            Route::post('/periods', [\App\Http\Controllers\Hr\PerformanceController::class, 'periodStore'])->name('periods.store');

            Route::get('/criteria', [\App\Http\Controllers\Hr\PerformanceController::class, 'criteriaIndex'])->name('criteria');
            Route::post('/criteria', [\App\Http\Controllers\Hr\PerformanceController::class, 'criterionStore'])->name('criteria.store');

            Route::get('/evaluations', [\App\Http\Controllers\Hr\PerformanceController::class, 'evaluationsIndex'])->name('evaluations');
            Route::get('/evaluations/{id}/evaluate', [\App\Http\Controllers\Hr\PerformanceController::class, 'evaluationCreate'])->name('evaluations.form');
            Route::post('/evaluations', [\App\Http\Controllers\Hr\PerformanceController::class, 'evaluationStore'])->name('evaluations.store');

            Route::get('/reports', [\App\Http\Controllers\Hr\PerformanceController::class, 'reportsIndex'])->name('reports');
        });

        // 8. Training
        Route::prefix('training')->name('training.')->group(function () {
            Route::get('/programs', [\App\Http\Controllers\Hr\TrainingController::class, 'programsIndex'])->name('programs');
            Route::post('/programs', [\App\Http\Controllers\Hr\TrainingController::class, 'programStore'])->name('programs.store');
            Route::put('/programs/{id}', [\App\Http\Controllers\Hr\TrainingController::class, 'programUpdate'])->name('programs.update');

            Route::get('/records', [\App\Http\Controllers\Hr\TrainingController::class, 'recordsIndex'])->name('records');
            Route::post('/records', [\App\Http\Controllers\Hr\TrainingController::class, 'enrollmentStore'])->name('records.store');
            Route::post('/enrollments', [\App\Http\Controllers\Hr\TrainingController::class, 'enrollmentStore'])->name('enrollments.store');

            Route::get('/reports', [\App\Http\Controllers\Hr\TrainingController::class, 'reportsIndex'])->name('reports');
        });

        // 9. Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hr\HrReportController::class, 'index'])->name('index');
            Route::get('/export/employees', [\App\Http\Controllers\Hr\HrReportController::class, 'exportEmployees'])->name('export.employees');
            Route::get('/export/attendance', [\App\Http\Controllers\Hr\HrReportController::class, 'exportAttendance'])->name('export.attendance');
            Route::get('/export/payroll', [\App\Http\Controllers\Hr\HrReportController::class, 'exportPayroll'])->name('export.payroll');
        });

        // 10. Administration
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [\App\Http\Controllers\Hr\HrAdminController::class, 'usersIndex'])->name('users');
            Route::post('/users', [\App\Http\Controllers\Hr\HrAdminController::class, 'userStore'])->name('users.store');
            Route::put('/users/{id}', [\App\Http\Controllers\Hr\HrAdminController::class, 'userUpdate'])->name('users.update');
            Route::post('/users/{id}/reset-password', [\App\Http\Controllers\Hr\HrAdminController::class, 'userPasswordReset'])->name('users.reset-password');

            Route::get('/roles', [\App\Http\Controllers\Hr\HrAdminController::class, 'rolesIndex'])->name('roles');
            Route::post('/roles', [\App\Http\Controllers\Hr\HrAdminController::class, 'roleStore'])->name('roles.store');
            Route::delete('/roles/{id}', [\App\Http\Controllers\Hr\HrAdminController::class, 'roleDestroy'])->name('roles.destroy');
            Route::post('/roles/{id}/permissions', [\App\Http\Controllers\Hr\HrAdminController::class, 'roleUpdatePermissions'])->name('roles.permissions');
            Route::post('/roles/employees/{id}/permissions', [\App\Http\Controllers\Hr\HrAdminController::class, 'employeeUpdatePermissions'])->name('roles.employee-permissions');

            Route::get('/settings', [\App\Http\Controllers\Hr\HrAdminController::class, 'settingsIndex'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Hr\HrAdminController::class, 'settingsUpdate'])->name('settings.update');

            Route::get('/audit-logs', [\App\Http\Controllers\Hr\HrAdminController::class, 'auditLogsIndex'])->name('audit-logs');
            Route::get('/audit_logs', [\App\Http\Controllers\Hr\HrAdminController::class, 'auditLogsIndex'])->name('audit_logs');
            Route::get('/notifications/{id}/read', [\App\Http\Controllers\Hr\HrAdminController::class, 'markNotificationRead'])->name('notifications.read');
        });

        // Legacy compatibility routes mapped to modern HR controllers
        Route::get('/employee', [\App\Http\Controllers\Hr\PeopleController::class, 'employeesIndex'])->name('employee');
        Route::get('/users-auth', [\App\Http\Controllers\Hr\HrAdminController::class, 'usersIndex'])->name('users-auth');
        Route::get('/attendance-schedule', [\App\Http\Controllers\Hr\AttendanceController::class, 'schedulesIndex'])->name('attendance-schedule');
        Route::get('/attendance-checkin', [\App\Http\Controllers\Hr\AttendanceController::class, 'timekeepingIndex'])->name('attendance-checkin');
        Route::get('/employee-leave', [\App\Http\Controllers\Hr\LeaveController::class, 'requestsIndex'])->name('employee-leave');
    });

    // Sales Operations
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/dashboard', [SalesController::class, 'dashboard'])->name('dashboard');
        Route::get('/daily-sales', [SalesController::class, 'dailySales'])->name('daily-sales');
        Route::get('/payment-report', [SalesController::class, 'paymentReport'])->name('payment-report');
        Route::get('/reconciliations', [SalesController::class, 'reconciliations'])->name('reconciliations');
        Route::get('/discount-config', [SalesController::class, 'discountConfig'])->name('discount-config');
        Route::get('/voucher-config', [SalesController::class, 'voucherConfig'])->name('voucher-config');
        Route::get('/bundle-promotions', [SalesController::class, 'bundlePromotions'])->name('bundle-promotions');
        Route::get('/customer-masterlist', [SalesController::class, 'customerMasterlist'])->name('customer-masterlist');
    });

    // Inventory Operations
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/dashboard', [InventoryController::class, 'dashboard'])->name('dashboard');
        Route::get('/stocks-overview', [InventoryController::class, 'stocksOverview'])->name('stocks-overview');
        Route::get('/beg-balance', [InventoryController::class, 'begBalance'])->name('beg-balance');
        Route::get('/stock-in', [InventoryController::class, 'stockIn'])->name('stock-in');
        Route::get('/stock-out', [InventoryController::class, 'stockOut'])->name('stock-out');
        Route::get('/stock-adjustment', [InventoryController::class, 'stockAdjustment'])->name('stock-adjustment');
        Route::get('/waste-expiry', [InventoryController::class, 'wasteExpiry'])->name('waste-expiry');
        Route::get('/product-categories', [InventoryController::class, 'productCategories'])->name('product-categories');
        Route::get('/recipe-management', [InventoryController::class, 'recipeManagement'])->name('recipe-management');
    });

    // Purchase Operations
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/dashboard', [PurchaseController::class, 'dashboard'])->name('dashboard');
        Route::get('/request-quotations', [PurchaseController::class, 'requestQuotations'])->name('request-quotations');
        Route::get('/purchase-orders', [PurchaseController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/vendor-masterlist', [PurchaseController::class, 'vendorMasterlist'])->name('vendor-masterlist');
        Route::get('/vendor-bills', [PurchaseController::class, 'vendorBills'])->name('vendor-bills');
    });

    // Settings (Business Configuration)
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/business-settings', [ConfigController::class, 'businessSettings'])->name('business-settings');
    });

    // User Account Settings
    Route::get('/account-settings', [ConfigController::class, 'accountSettings'])->name('account-settings');
    Route::post('/account-settings/profile', [ConfigController::class, 'updateProfile'])->name('account-settings.profile');
    Route::post('/account-settings/password', [ConfigController::class, 'updatePassword'])->name('account-settings.password');

    // Credits & Support
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/tickets', [CreditsController::class, 'tickets'])->name('tickets');
        Route::get('/developers', [CreditsController::class, 'developers'])->name('developers');
    });
});
