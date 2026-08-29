<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\SocietyController as AdminSociety;
use App\Http\Controllers\Society\DashboardController as SocietyDashboard;
use App\Http\Controllers\Society\SocietyModuleController;
use App\Http\Controllers\Reseller\DashboardController as ResellerDashboard;
use App\Http\Controllers\Reseller\SocietyController as ResellerSociety;
use App\Http\Controllers\Reseller\ProfileController as ResellerProfile;
use App\Http\Controllers\Reseller\UserController as ResellerUser;
use App\Http\Controllers\Member\DashboardController as MemberDashboard;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\CheckModulePermission;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::get('/clear-session', function () { \Illuminate\Support\Facades\Session::flush(); \Illuminate\Support\Facades\Auth::logout(); return redirect('/login'); });

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', EnsureRole::class . ':Admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('admin.dashboard');

    Route::get('/societies', [AdminSociety::class, 'index'])->name('admin.societies.index');
    Route::get('/societies/create/{id?}', [AdminSociety::class, 'create'])->name('admin.societies.create');
    Route::post('/societies/store', [AdminSociety::class, 'store'])->name('admin.societies.store');
    Route::delete('/societies/{id}', [AdminSociety::class, 'destroy'])->name('admin.societies.destroy');
    Route::get('/societies/parameters', [AdminSociety::class, 'parameters'])->name('admin.societies.parameters');
    Route::match(['get', 'post'], '/societies/assign', [AdminSociety::class, 'assignSocieties'])->name('admin.societies.assign');
    Route::post('/societies/get-assigned', [AdminSociety::class, 'getAssignedSocieties'])->name('admin.societies.getAssigned');

    Route::get('/resellers', [AdminSociety::class, 'resellers'])->name('admin.resellers.index');
    Route::post('/resellers/update-credit', [AdminSociety::class, 'updateResellerCredit'])->name('admin.resellers.updateCredit');

    Route::get('/societies/features', [AdminSociety::class, 'societyFeatures'])->name('admin.societies.features');
    Route::post('/societies/update-features', [AdminSociety::class, 'updateSocietyFeatures'])->name('admin.societies.updateFeatures');
});

Route::middleware(['auth', EnsureRole::class . ':Reseller,ResellerUser'])->prefix('reseller')->group(function () {
    Route::get('/dashboard', [ResellerDashboard::class, 'index'])->name('reseller.dashboard');

    Route::match(['get', 'post'], '/profile', [ResellerProfile::class, 'updateProfile'])->name('reseller.profile');

    // Society routes — ResellerUser needs software permission
    Route::middleware(CheckModulePermission::class . ':software,can_view')->group(function () {
        Route::get('/societies/assigned', [ResellerSociety::class, 'assigned'])->name('reseller.societies.assigned');
        Route::post('/societies/switch/{societyId}', [ResellerSociety::class, 'switchToSociety'])->name('reseller.societies.switch');
        Route::get('/societies/years/{societyId}', [ResellerSociety::class, 'getAssignedYears'])->name('reseller.societies.years');
    });
    Route::match(['get', 'post'], '/societies/create', [ResellerSociety::class, 'create'])
        ->middleware(CheckModulePermission::class . ':software,can_add')
        ->name('reseller.societies.create');
    Route::match(['get', 'post'], '/finance-year-mapping', [ResellerProfile::class, 'financeYearMapping'])
        ->middleware(CheckModulePermission::class . ':settings,can_view')
        ->name('reseller.financeYearMapping');

    // User management — Reseller only (ResellerUser cannot manage users)
    Route::middleware(EnsureRole::class . ':Reseller')->group(function () {
        Route::get('/users', [ResellerUser::class, 'index'])->name('reseller.users.index');
        Route::get('/users/create', [ResellerUser::class, 'create'])->name('reseller.users.create');
        Route::get('/users/permissions', [ResellerUser::class, 'permissions'])->name('reseller.users.permissions');
        Route::post('/users/permissions', [ResellerUser::class, 'savePermissions'])->name('reseller.users.savePermissions');
        Route::post('/users', [ResellerUser::class, 'store'])->name('reseller.users.store');
        Route::get('/users/{id}/edit', [ResellerUser::class, 'edit'])->name('reseller.users.edit');
        Route::put('/users/{id}', [ResellerUser::class, 'update'])->name('reseller.users.update');
        Route::delete('/users/{id}', [ResellerUser::class, 'destroy'])->name('reseller.users.destroy');
    });
});

Route::middleware('auth')->post('/reseller/switch-back', [ResellerSociety::class, 'switchBack'])->name('reseller.switchBack');

Route::middleware('auth')->group(function () {
    Route::get('/society/dashboard', [SocietyDashboard::class, 'index'])
        ->middleware(EnsureRole::class . ':Society')
        ->name('society.dashboard');

    Route::get('/society/change-year/{yearId}', [SocietyDashboard::class, 'changeFinancialYear'])
        ->middleware(EnsureRole::class . ':Society')
        ->name('society.changeYear');

    Route::get('/member/dashboard', [MemberDashboard::class, 'index'])
        ->middleware(EnsureRole::class . ':Member')
        ->name('member.dashboard');
});

// ─── Society Module Routes ─────────────────────────────────────────────
Route::middleware(['auth', EnsureRole::class . ':Society'])->prefix('society')->group(function () {
    // Society section
    Route::match(['get', 'post'], '/identity', [SocietyModuleController::class, 'identity'])->name('society.identity');
    Route::match(['get', 'post'], '/parameters', [SocietyModuleController::class, 'parameters'])->name('society.parameters');
    Route::get('/tariff-definition', [SocietyModuleController::class, 'tariffDefinition'])->name('society.tariffDefinition');
    Route::match(['get', 'post'], '/head-sub-categories/{id?}', [SocietyModuleController::class, 'headSubCategories'])->name('society.headSubCategories');
    Route::get('/get-account-heads', [SocietyModuleController::class, 'getAccountHeads'])->name('society.getAccountHeads');
    Route::get('/get-sub-group-details', [SocietyModuleController::class, 'getSubGroupDetails'])->name('society.getSubGroupDetails');
    Route::get('/ledger-heads', [SocietyModuleController::class, 'ledgerHeads'])->name('society.ledgerHeads');
    Route::match(['get', 'post'], '/ledger-heads/add/{id?}', [SocietyModuleController::class, 'addLedgerHead'])->name('society.addLedgerHead');
    Route::delete('/ledger-heads/{id}', [SocietyModuleController::class, 'deleteLedgerHead'])->name('society.deleteLedgerHead');
    Route::get('/tariffs', [SocietyModuleController::class, 'tariffs'])->name('society.tariffs');
    Route::get('/tariff-orders', [SocietyModuleController::class, 'tariffOrders'])->name('society.tariffOrders');
    Route::post('/tariff-orders/save', [SocietyModuleController::class, 'saveTariffOrder'])->name('society.saveTariffOrder');
    Route::match(['get', 'post'], '/payments', [SocietyModuleController::class, 'payments'])->name('society.payments');
    Route::match(['get', 'post'], '/payments/add/{id?}', [SocietyModuleController::class, 'addPayment'])->name('society.addPayment');
    Route::delete('/payments/{id}', [SocietyModuleController::class, 'deletePayment'])->name('society.deletePayment');
    Route::get('/bank-reconciliation', [SocietyModuleController::class, 'bankReconciliation'])->name('society.bankReconciliation');
    Route::get('/cash-contra', [SocietyModuleController::class, 'cashContra'])->name('society.cashContra');
    Route::get('/general-receipt', [SocietyModuleController::class, 'generalReceipt'])->name('society.generalReceipt');

    // Member section
    Route::match(['get', 'post'], '/building-identity', [SocietyModuleController::class, 'buildingIdentity'])->name('society.buildingIdentity');
    Route::match(['get', 'post'], '/wing-identity', [SocietyModuleController::class, 'wingIdentity'])->name('society.wingIdentity');
    Route::get('/member-identity', [SocietyModuleController::class, 'memberIdentity'])->name('society.memberIdentity');
    Route::match(['get', 'post'], '/member-identity/add/{id?}', [SocietyModuleController::class, 'addMember'])->name('society.addMember');
    Route::delete('/member-identity/{id}', [SocietyModuleController::class, 'deleteMember'])->name('society.deleteMember');
    Route::delete('/member-identity-delete-all', [SocietyModuleController::class, 'deleteAllMembers'])->name('society.deleteAllMembers');
    Route::get('/member-identity/download-template', [SocietyModuleController::class, 'downloadMemberTemplate'])->name('society.downloadMemberTemplate');
    Route::get('/member-identity/download-opening-balance', [SocietyModuleController::class, 'downloadMemberOpeningBalance'])->name('society.downloadMemberOpeningBalance');
    Route::post('/member-identity/upload-opening-balance', [SocietyModuleController::class, 'uploadMemberOpeningBalance'])->name('society.uploadMemberOpeningBalance');
    Route::post('/member-identity/upload-csv', [SocietyModuleController::class, 'uploadMemberCsv'])->name('society.uploadMemberCsv');
    Route::match(['get', 'post'], '/member-tariff', [SocietyModuleController::class, 'memberTariff'])->name('society.memberTariff');
    Route::get('/get-member-tariff-data', [SocietyModuleController::class, 'getMemberTariffData'])->name('society.getMemberTariffData');
    Route::match(['get', 'post'], '/member-tariff-upload', [SocietyModuleController::class, 'memberTariffUpload'])->name('society.memberTariffUpload');
    Route::post('/download-member-tariff-data', [SocietyModuleController::class, 'downloadMemberTariffData'])->name('society.downloadMemberTariffData');
    Route::get('/get-wings-by-building', [SocietyModuleController::class, 'getWingsByBuilding'])->name('society.getWings');
    Route::post('/get-all-member-tariff-details', [SocietyModuleController::class, 'getAllMemberTariffDetails'])->name('society.getAllMemberTariffDetails');
    Route::post('/update-all-member-tariff-details', [SocietyModuleController::class, 'updateAllMemberTariffDetails'])->name('society.updateAllMemberTariffDetails');
    Route::get('/tenant-member-identity', [SocietyModuleController::class, 'tenantMemberIdentity'])->name('society.tenantMemberIdentity');
    Route::match(['get', 'post'], '/tenant-member-identity/add/{id?}', [SocietyModuleController::class, 'addTenant'])->name('society.addTenant');
    Route::delete('/tenant-member-identity/{id}', [SocietyModuleController::class, 'deleteTenant'])->name('society.deleteTenant');
    Route::get('/member-payments', [SocietyModuleController::class, 'memberPayments'])->name('society.memberPayments');
    Route::match(['get', 'post'], '/member-payments/add/{id?}', [SocietyModuleController::class, 'addMemberPayment'])->name('society.addMemberPayment');
    Route::delete('/member-payments/{id}', [SocietyModuleController::class, 'deleteMemberPayment'])->name('society.deleteMemberPayment');
    Route::get('/member-receipt', [SocietyModuleController::class, 'memberReceipt'])->name('society.memberReceipt');
    Route::post('/load-member-payment-receipts', [SocietyModuleController::class, 'loadMemberPaymentReceipts'])->name('society.loadMemberPaymentReceipts');
    Route::post('/save-member-payment-receipts', [SocietyModuleController::class, 'saveMemberPaymentReceipts'])->name('society.saveMemberPaymentReceipts');
    Route::get('/import-member-payments', [SocietyModuleController::class, 'importMemberPayments'])->name('society.importMemberPayments');
    Route::get('/import-society-payments', [SocietyModuleController::class, 'importSocietyPayments'])->name('society.importSocietyPayments');
    Route::match(['get', 'post'], '/all-generated-bills', [SocietyModuleController::class, 'allGeneratedBills'])->name('society.allGeneratedBills');
    Route::match(['get', 'post'], '/create-member-logins', [SocietyModuleController::class, 'createMemberLogins'])->name('society.createMemberLogins');
    Route::match(['get', 'post'], '/update-opening-balance', [SocietyModuleController::class, 'updateOpeningBalance'])->name('society.updateOpeningBalance');
    Route::get('/download-member-details', [SocietyModuleController::class, 'downloadMemberDetails'])->name('society.downloadMemberDetails');

    // Employee section
    Route::match(['get', 'post'], '/employee-category/{id?}', [SocietyModuleController::class, 'employeeCategory'])->name('society.employeeCategory');
    Route::delete('/employee-category/{id}', [SocietyModuleController::class, 'deleteEmployeeCategory'])->name('society.deleteEmployeeCategory');
    Route::match(['get', 'post'], '/employee-sub-category/{id?}', [SocietyModuleController::class, 'employeeSubCategory'])->name('society.employeeSubCategory');
    Route::delete('/employee-sub-category/{id}', [SocietyModuleController::class, 'deleteEmployeeSubCategory'])->name('society.deleteEmployeeSubCategory');
    Route::get('/employee-details', [SocietyModuleController::class, 'employeeDetails'])->name('society.employeeDetails');
    Route::match(['get', 'post'], '/employee-details/add/{id?}', [SocietyModuleController::class, 'addEmployee'])->name('society.addEmployee');
    Route::delete('/employee-details/{id}', [SocietyModuleController::class, 'deleteEmployee'])->name('society.deleteEmployee');
    Route::get('/get-employee-sub-categories', [SocietyModuleController::class, 'getEmployeeSubCategories'])->name('society.getEmployeeSubCategories');

    // Reports section
    Route::get('/report-accounts', [SocietyModuleController::class, 'reportAccounts'])->name('society.reportAccounts');
    Route::get('/report-society', [SocietyModuleController::class, 'reportSociety'])->name('society.reportSociety');
    Route::get('/journal-voucher', [SocietyModuleController::class, 'journalVoucher'])->name('society.journalVoucher');
    Route::get('/closing-balances', [SocietyModuleController::class, 'closingBalances'])->name('society.closingBalances');
    Route::get('/ledger-closing-trial-balance', [SocietyModuleController::class, 'ledgerClosingTrialBalance'])->name('society.ledgerClosingTrialBalance');

    // Utilities
    Route::get('/update-bill-dates', [SocietyModuleController::class, 'updateBillDates'])->name('society.updateBillDates');

    // Modal AJAX endpoints
    Route::get('/get-ledger-head-details', [SocietyModuleController::class, 'getLedgerHeadDetails'])->name('society.getLedgerHeadDetails');
    Route::post('/update-ledger-head', [SocietyModuleController::class, 'updateLedgerHead'])->name('society.updateLedgerHead');
    Route::post('/get-member-details', [SocietyModuleController::class, 'getMemberDetails'])->name('society.getMemberDetails');
    Route::post('/get-member-by-flat-no', [SocietyModuleController::class, 'getMemberDetailsByFlatNo'])->name('society.getMemberByFlatNo');
    Route::post('/update-member-details', [SocietyModuleController::class, 'updateSocietyMemberDetails'])->name('society.updateMemberDetails');
    Route::get('/get-bill-details', [SocietyModuleController::class, 'getBillDetails'])->name('society.getBillDetails');
    Route::post('/get-all-members-bill-summary-details', [SocietyModuleController::class, 'getAllMembersBillSummaryDetails'])->name('society.getAllMembersBillSummaryDetails');
    Route::post('/update-member-bill-summary', [SocietyModuleController::class, 'updateMemberBillSummaryById'])->name('society.updateMemberBillSummaryById');
    Route::post('/generate-bill', [SocietyModuleController::class, 'generateBill'])->name('society.generateBill');
    Route::post('/save-payment-entry', [SocietyModuleController::class, 'savePaymentEntry'])->name('society.savePaymentEntry');
    Route::post('/save-member-receipt', [SocietyModuleController::class, 'saveMemberReceiptAjax'])->name('society.saveMemberReceipt');

    // Bill Print
    Route::get('/bill-with-receipt-tabular', [SocietyModuleController::class, 'billWithReceiptTabular'])->name('society.billWithReceiptTabular');
    Route::get('/bill-tax-invoice-gst', [SocietyModuleController::class, 'billTaxInvoiceGst'])->name('society.billTaxInvoiceGst');
    Route::get('/bill-full-page', [SocietyModuleController::class, 'billFullPage'])->name('society.billFullPage');
    Route::get('/bill-half-page', [SocietyModuleController::class, 'billHalfPage'])->name('society.billHalfPage');
    Route::get('/bill-with-interest-gst', [SocietyModuleController::class, 'billWithInterestGst'])->name('society.billWithInterestGst');
    Route::get('/bill-summary-with-prev-data', [SocietyModuleController::class, 'billSummaryWithPrevData'])->name('society.billSummaryWithPrevData');
    Route::get('/bill-receipt', [SocietyModuleController::class, 'billReceipt'])->name('society.billReceipt');
});
