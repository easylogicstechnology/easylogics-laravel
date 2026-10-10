<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\SocietyController as AdminSociety;
use App\Http\Controllers\Admin\ReportsController as AdminReports;
use App\Http\Controllers\Admin\WebsiteContentController as AdminWebsiteContent;
use App\Http\Controllers\Society\DashboardController as SocietyDashboard;
use App\Http\Controllers\Society\SocietyModuleController;
use App\Http\Controllers\Reseller\DashboardController as ResellerDashboard;
use App\Http\Controllers\Reseller\SocietyController as ResellerSociety;
use App\Http\Controllers\Reseller\ProfileController as ResellerProfile;
use App\Http\Controllers\Reseller\UserController as ResellerUser;
use App\Http\Controllers\Reseller\ComplaintController as ResellerComplaint;
use App\Http\Controllers\Member\DashboardController as MemberDashboard;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\CheckModulePermission;
use App\Http\Middleware\CheckSubResellerModule;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!\Illuminate\Support\Facades\Auth::check()) {
        return app(HomeController::class)->index(request());
    }

    return match (\Illuminate\Support\Facades\Auth::user()->role) {
        'Admin' => redirect()->route('admin.dashboard'),
        'Society' => redirect()->route('society.dashboard'),
        'Reseller', 'SubReseller' => redirect()->route('reseller.dashboard'),
        'Member' => redirect()->route('member.dashboard'),
        // An authenticated user with no matching role must not be sent back to
        // /login: guest middleware redirects an authenticated session straight
        // back to '/', which loops forever (ERR_TOO_MANY_REDIRECTS). Logging out
        // first breaks the loop.
        default => tap(redirect()->route('login'), function () {
            \Illuminate\Support\Facades\Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }),
    };
});
Route::post('/submit-enquiry', [HomeController::class, 'submitEnquiry'])->name('enquiry.submit');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// CakePHP AdminController::dashboard() has no role check of its own: it just sends a Society or a Reseller on to
// their own dashboard. Every other role is refused here (403) - Cake would show them the admin dashboard.
Route::middleware('auth')->get('/admin/dashboard', function (\Illuminate\Http\Request $request) {
    return match ($request->user()->role) {
        'Admin' => app(AdminDashboard::class)->index($request),
        'Society' => redirect()->route('society.dashboard'),
        'Reseller' => redirect()->route('reseller.dashboard'),
        default => abort(403, 'You are not authorized to access this page.'),
    };
})->name('admin.dashboard');

Route::middleware(['auth', EnsureRole::class . ':Admin'])->prefix('admin')->group(function () {

    // Bill Summary Monitor on the admin dashboard (JSON for the dashboard widget)
    Route::get('/bill-monitor/months', [\App\Http\Controllers\Admin\BillMonitorController::class, 'months'])->name('admin.billMonitor.months');
    Route::get('/bill-monitor/month', [\App\Http\Controllers\Admin\BillMonitorController::class, 'month'])->name('admin.billMonitor.month');
    Route::get('/bill-monitor/check', [\App\Http\Controllers\Admin\BillMonitorController::class, 'check'])->name('admin.billMonitor.check');
    Route::post('/bill-monitor/reconcile', [\App\Http\Controllers\Admin\BillMonitorController::class, 'reconcile'])->name('admin.billMonitor.reconcile');

    Route::get('/societies', [AdminSociety::class, 'index'])->name('admin.societies.index');
    Route::get('/societies/create/{id?}', [AdminSociety::class, 'create'])->name('admin.societies.create');
    Route::post('/societies/store', [AdminSociety::class, 'store'])->name('admin.societies.store');
    Route::delete('/societies/{id}', [AdminSociety::class, 'destroy'])->name('admin.societies.destroy');
    Route::get('/societies/parameters', [AdminSociety::class, 'parameters'])->name('admin.societies.parameters');
    Route::match(['get', 'post'], '/societies/assign', [AdminSociety::class, 'assignSocieties'])->name('admin.societies.assign');
    Route::post('/societies/get-assigned', [AdminSociety::class, 'getAssignedSocieties'])->name('admin.societies.getAssigned');

    Route::get('/reports/trial-balance-diff', [AdminReports::class, 'trialBalanceDiffReport'])->name('admin.reports.trialBalanceDiff');
    Route::get('/reports/reseller-society', [AdminReports::class, 'resellerSocietyReport'])->name('admin.reports.resellerSociety');
    Route::match(['get', 'post'], '/reports/reseller-payments', [AdminReports::class, 'resellerPayments'])->name('admin.reports.resellerPayments');
    Route::get('/reports/reseller-plans', [AdminReports::class, 'resellerPlans'])->name('admin.reports.resellerPlans');
    Route::post('/reports/reseller-plans/{planId?}', [AdminReports::class, 'saveResellerPlan'])->whereNumber('planId')->name('admin.reports.resellerPlans.save');
    Route::get('/reports/search-resellers', [AdminReports::class, 'searchResellers'])->name('admin.reports.searchResellers');
    Route::match(['get', 'post'], '/reports/payment-setup', [AdminReports::class, 'paymentSetup'])->name('admin.reports.paymentSetup');
    Route::get('/reports/complaints', [AdminReports::class, 'complaintsRegister'])->name('admin.reports.complaints');
    Route::post('/reports/complaints/{id}/solve', [AdminReports::class, 'solveComplaint'])->name('admin.reports.complaints.solve');
    Route::post('/reports/complaints/{id}/close', [AdminReports::class, 'closeComplaint'])->name('admin.reports.complaints.close');
    Route::post('/reports/complaints/{id}/reopen', [AdminReports::class, 'reopenComplaint'])->name('admin.reports.complaints.reopen');

    Route::match(['get', 'post'], '/website/founder', [AdminWebsiteContent::class, 'manageFounder'])->name('admin.website.founder');
    Route::match(['get', 'post'], '/website/partners', [AdminWebsiteContent::class, 'managePartners'])->name('admin.website.partners');
    Route::get('/website/partners/{id}/edit', [AdminWebsiteContent::class, 'editPartner'])->name('admin.website.partners.edit');
    Route::put('/website/partners/{id}', [AdminWebsiteContent::class, 'editPartner'])->name('admin.website.partners.update');
    Route::delete('/website/partners/{id}', [AdminWebsiteContent::class, 'deletePartner'])->name('admin.website.partners.destroy');
    Route::match(['get', 'post'], '/website/help-video', [AdminWebsiteContent::class, 'manageHelpVideo'])->name('admin.website.helpVideo');
    Route::delete('/website/help-video', [AdminWebsiteContent::class, 'deleteHelpVideo'])->name('admin.website.helpVideo.destroy');
});

Route::middleware(['auth', EnsureRole::class . ':Reseller,SubReseller'])->prefix('reseller')->group(function () {
    Route::get('/dashboard', [ResellerDashboard::class, 'index'])->name('reseller.dashboard');

    Route::match(['get', 'post'], '/profile', [ResellerProfile::class, 'updateProfile'])->name('reseller.profile');

    Route::get('/payment-dashboard', [ResellerDashboard::class, 'paymentDashboard'])->name('reseller.paymentDashboard');
    Route::get('/help', function () {
        $helpVideo = \App\Models\HelpVideo::where('source_app', 'laravel')->where('display_status', 1)->first();
        return view('reseller.help', compact('helpVideo'));
    })->name('reseller.help');

    Route::match(['get', 'post'], '/complaints', [ResellerComplaint::class, 'index'])->name('reseller.complaints');
    Route::post('/complaints/{id}/resolve', [ResellerComplaint::class, 'resolve'])->name('reseller.complaints.resolve');
    Route::post('/complaints/{id}/confirm', [ResellerComplaint::class, 'confirm'])->name('reseller.complaints.confirm');
    Route::post('/complaints/{id}/reject', [ResellerComplaint::class, 'reject'])->name('reseller.complaints.reject');

    // Society routes — a team login (SubReseller) needs the software view permission
    Route::middleware(CheckModulePermission::class . ':software,view')->group(function () {
        Route::get('/societies/assigned', [ResellerSociety::class, 'assigned'])->name('reseller.societies.assigned');
        Route::post('/societies/switch/{societyId}', [ResellerSociety::class, 'switchToSociety'])->name('reseller.societies.switch');
        Route::get('/societies/years/{societyId}', [ResellerSociety::class, 'getAssignedYears'])->name('reseller.societies.years');
    });
    Route::match(['get', 'post'], '/societies/create', [ResellerSociety::class, 'create'])
        ->middleware(CheckModulePermission::class . ':software,add')
        ->name('reseller.societies.create');
    Route::match(['get', 'post'], '/finance-year-mapping', [ResellerProfile::class, 'financeYearMapping'])
        ->middleware(CheckModulePermission::class . ':settings,view')
        ->name('reseller.financeYearMapping');

    // Manage Users — Reseller only (a team login, SubReseller, never manages users)
    Route::middleware(EnsureRole::class . ':Reseller')->group(function () {
        Route::get('/users', [ResellerUser::class, 'index'])->name('reseller.users.index');
        Route::get('/users/create', [ResellerUser::class, 'create'])->name('reseller.users.create');
        Route::get('/users/permissions', [ResellerUser::class, 'permissions'])->name('reseller.users.permissions');
        Route::post('/users/permissions', [ResellerUser::class, 'savePermissions'])->name('reseller.users.savePermissions');
        Route::post('/users', [ResellerUser::class, 'store'])->name('reseller.users.store');
        Route::get('/users/{id}/edit', [ResellerUser::class, 'edit'])->name('reseller.users.edit');
        Route::put('/users/{id}', [ResellerUser::class, 'update'])->name('reseller.users.update');
        Route::post('/users/{id}/deactivate', [ResellerUser::class, 'deactivate'])->name('reseller.users.deactivate');
    });
});

Route::middleware('auth')->post('/reseller/switch-back', [ResellerSociety::class, 'switchBack'])->name('reseller.switchBack');

Route::middleware(['auth', EnsureRole::class . ':Society,Reseller,SubReseller'])->group(function () {
    Route::post('/chat/send', [\App\Http\Controllers\ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/reset', [\App\Http\Controllers\ChatController::class, 'reset'])->name('chat.reset');
});

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
    Route::match(['get', 'post'], '/parameters', [SocietyModuleController::class, 'parameters'])->name('society.parameters')->middleware(CheckSubResellerModule::class . ':sm_societyparameters,edit,post');
    Route::match(['get', 'post'], '/month-interest-rates', [\App\Http\Controllers\Society\MonthInterestRateController::class, 'index'])->name('society.monthInterestRates');
    Route::get('/tariff-definition', [SocietyModuleController::class, 'tariffDefinition'])->name('society.tariffDefinition');
    // Debit Note / Credit Note (CakePHP debit_credit_notes)
    Route::match(['get', 'post'], '/debit-credit-notes', [\App\Http\Controllers\Society\DebitCreditNoteController::class, 'index'])->name('society.debitCreditNotes');
    Route::get('/debit-credit-notes/latest-bill/{memberKey}', [\App\Http\Controllers\Society\DebitCreditNoteController::class, 'latestBill'])->name('society.debitCreditNotes.latestBill');
    Route::post('/debit-credit-notes/delete/{voucherNo}', [\App\Http\Controllers\Society\DebitCreditNoteController::class, 'delete'])->whereNumber('voucherNo')->name('society.debitCreditNotes.delete');
    // Vendor Detail / Vendor Billing (CakePHP societys/vendor_details, vendor_billings + the societys_ajax vendor actions)
    Route::get('/vendor-detail', [\App\Http\Controllers\Society\VendorController::class, 'details'])->name('society.vendorDetails');
    Route::get('/vendor-billing', [\App\Http\Controllers\Society\VendorController::class, 'billings'])->name('society.vendorBillings');
    Route::prefix('vendor')->name('society.vendor.')->group(function () {
        Route::post('/detail-form', [\App\Http\Controllers\Society\VendorController::class, 'detailForm'])->name('detailForm');
        Route::post('/save-detail', [\App\Http\Controllers\Society\VendorController::class, 'saveDetail'])->name('saveDetail');
        Route::post('/save-facility', [\App\Http\Controllers\Society\VendorController::class, 'saveFacility'])->name('saveFacility');
        Route::post('/vendor-list', [\App\Http\Controllers\Society\VendorController::class, 'vendorList'])->name('vendorList');
        Route::post('/billing-form', [\App\Http\Controllers\Society\VendorController::class, 'billingForm'])->name('billingForm');
        Route::post('/save-bill', [\App\Http\Controllers\Society\VendorController::class, 'saveBill'])->name('saveBill');
        Route::get('/print-bill/{vendorBillId}', [\App\Http\Controllers\Society\VendorController::class, 'printBill'])->whereNumber('vendorBillId')->name('printBill');
        Route::post('/particular-heads', [\App\Http\Controllers\Society\VendorController::class, 'particularHeads'])->name('particularHeads');
        Route::post('/sub-categories', [\App\Http\Controllers\Society\VendorController::class, 'subCategories'])->name('subCategories');
        Route::post('/account-categories', [\App\Http\Controllers\Society\VendorController::class, 'accountCategories'])->name('accountCategories');
        Route::post('/account-heads', [\App\Http\Controllers\Society\VendorController::class, 'accountHeads'])->name('accountHeads');
        Route::post('/save-ledger-head', [\App\Http\Controllers\Society\VendorController::class, 'saveLedgerHeadInline'])->name('saveLedgerHeadInline');
    });
    Route::match(['get', 'post'], '/head-sub-categories/{id?}', [SocietyModuleController::class, 'headSubCategories'])->name('society.headSubCategories');
    Route::get('/get-account-heads', [SocietyModuleController::class, 'getAccountHeads'])->name('society.getAccountHeads');
    Route::get('/get-sub-group-details', [SocietyModuleController::class, 'getSubGroupDetails'])->name('society.getSubGroupDetails');
    Route::get('/ledger-heads', [SocietyModuleController::class, 'ledgerHeads'])->name('society.ledgerHeads');
    Route::match(['get', 'post'], '/ledger-heads/add/{id?}', [SocietyModuleController::class, 'addLedgerHead'])->name('society.addLedgerHead')->middleware(CheckSubResellerModule::class . ':sm_societyledgerheads,auto');
    Route::delete('/ledger-heads/{id}', [SocietyModuleController::class, 'deleteLedgerHead'])->name('society.deleteLedgerHead')->middleware(CheckSubResellerModule::class . ':sm_societyledgerheads,delete');
    Route::get('/tariffs', [SocietyModuleController::class, 'tariffs'])->name('society.tariffs');
    Route::get('/tariff-orders', [SocietyModuleController::class, 'tariffOrders'])->name('society.tariffOrders');
    Route::post('/tariff-orders/save', [SocietyModuleController::class, 'saveTariffOrder'])->name('society.saveTariffOrder');
    Route::match(['get', 'post'], '/payments', [SocietyModuleController::class, 'payments'])->name('society.payments');
    Route::match(['get', 'post'], '/payments/add/{id?}', [SocietyModuleController::class, 'addPayment'])->name('society.addPayment')->middleware(CheckSubResellerModule::class . ':sm_societypayments,auto');
    Route::delete('/payments/{id}', [SocietyModuleController::class, 'deletePayment'])->name('society.deletePayment')->middleware(CheckSubResellerModule::class . ':sm_societypayments,delete');
    Route::post('/payments/update-field', [SocietyModuleController::class, 'updatePaymentField'])->name('society.updatePaymentField')->middleware(CheckSubResellerModule::class . ':sm_societypayments,edit,json');
    Route::get('/payments/bulk-paste', [SocietyModuleController::class, 'bulkPastePayments'])->name('society.bulkPastePayments');
    Route::post('/payments/bulk-paste/save', [SocietyModuleController::class, 'saveBulkPastePayments'])->name('society.saveBulkPastePayments');
    Route::get('/payments/bulk-paste/sample-template', [SocietyModuleController::class, 'downloadSampleSocietyPaymentTemplate'])->name('society.downloadSampleSocietyPaymentTemplate');
    Route::get('/bank-reconciliation', [SocietyModuleController::class, 'bankReconciliation'])->name('society.bankReconciliation');
    Route::get('/cash-contra', [SocietyModuleController::class, 'cashContra'])->name('society.cashContra');
    Route::match(['get', 'post'], '/cash-contra/add/{id?}', [SocietyModuleController::class, 'addCashContra'])->name('society.addCashContra')->middleware(CheckSubResellerModule::class . ':sm_societycashcontra,auto');
    Route::delete('/cash-contra/{id}', [SocietyModuleController::class, 'deleteCashContra'])->name('society.deleteCashContra')->middleware(CheckSubResellerModule::class . ':sm_societycashcontra,delete');
    Route::get('/registers/fd-register', [\App\Http\Controllers\Society\RegisterController::class, 'fdRegister'])->name('society.fdRegister');
    Route::match(['get', 'post'], '/registers/add-fd-register/{id?}', [\App\Http\Controllers\Society\RegisterController::class, 'addFdRegister'])->name('society.addFdRegister');
    Route::get('/registers/shares-register', [\App\Http\Controllers\Society\RegisterController::class, 'sharesRegister'])->name('society.sharesRegister');
    Route::get('/registers/lien-register', [\App\Http\Controllers\Society\RegisterController::class, 'lienRegister'])->name('society.lienRegister');
    Route::get('/registers/nominee-register', [\App\Http\Controllers\Society\RegisterController::class, 'nomineeRegister'])->name('society.nomineeRegister');
    Route::match(['get', 'post'], '/registers/form-i', [\App\Http\Controllers\Society\RegisterController::class, 'formI'])->name('society.formI');
    Route::get('/registers/form-j', [\App\Http\Controllers\Society\RegisterController::class, 'formJ'])->name('society.formJ');
    // Cake registers/index redirected to the FD register
    Route::get('/registers', fn () => redirect()->route('society.fdRegister'));
    Route::get('/registers/{page?}', [SocietyModuleController::class, 'registersPage'])->name('society.registers');
    // TDS module (port of CakePHP TdsController) - specific routes first, the wildcard below only keeps the sidebar URLs
    Route::prefix('/tds')->controller(\App\Http\Controllers\Society\TdsController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('society.tdsDashboard');
        Route::get('/sections', 'sections')->name('society.tdsSections');
        Route::match(['get', 'post'], '/add-section/{id?}', 'addSection')->name('society.tdsAddSection');
        Route::post('/toggle-section-status/{id}', 'toggleSectionStatus')->name('society.tdsToggleSectionStatus');
        Route::get('/deductees', 'deductees')->name('society.tdsDeductees');
        Route::match(['get', 'post'], '/add-deductee/{vendorDetailId}', 'addDeductee')->name('society.tdsAddDeductee');
        Route::get('/transactions', 'transactions')->name('society.tdsTransactions');
        Route::match(['get', 'post'], '/add-transaction/{id?}', 'addTransaction')->name('society.tdsAddTransaction');
        Route::get('/calculate-tds-ajax', 'calculateTdsAjax')->name('society.tdsCalculateAjax');
        Route::match(['get', 'post'], '/reverse-transaction/{id}', 'reverseTransaction')->name('society.tdsReverseTransaction');
        Route::get('/report', 'report')->name('society.tdsReport');
        Route::get('/report-pdf', 'reportPdf')->name('society.tdsReportPdf');
        Route::get('/report-excel', 'reportExcel')->name('society.tdsReportExcel');
        Route::get('/ledger', 'ledger')->name('society.tdsLedger');
        Route::get('/challans', 'challans')->name('society.tdsChallans');
        Route::match(['get', 'post'], '/generate-challan', 'generateChallan')->name('society.tdsGenerateChallan');
        Route::match(['get', 'post'], '/update-challan/{id}', 'updateChallan')->name('society.tdsUpdateChallan');
        Route::get('/certificates', 'certificates')->name('society.tdsCertificates');
        Route::post('/generate-certificate', 'generateCertificate')->name('society.tdsGenerateCertificate');
        Route::get('/certificate-pdf/{vendorDetailId}/{financialYearId}', 'certificatePdf')->name('society.tdsCertificatePdf');
    });
    Route::get('/tds/{page?}', [SocietyModuleController::class, 'tdsPage'])->name('society.tds');
    // GST module (port of CakePHP GstController) - specific routes first, the wildcard below only keeps the sidebar URLs
    Route::prefix('/gst')->controller(\App\Http\Controllers\Society\GstController::class)->group(function () {
        Route::get('/dashboard', 'dashboard')->name('society.gstDashboard');
        Route::match(['get', 'post'], '/master-setup', 'gstMasterSetup')->name('society.gstMasterSetup');
        Route::get('/hsn-master', 'hsnMaster')->name('society.gstHsnMaster');
        Route::match(['get', 'post'], '/add-hsn-master/{id?}', 'addHsnMaster')->name('society.gstAddHsnMaster');
        Route::post('/toggle-hsn-status/{id}', 'toggleHsnStatus')->name('society.gstToggleHsnStatus');
        Route::get('/outward-register', 'outwardRegister')->name('society.gstOutwardRegister');
        Route::get('/outward-register-pdf', 'outwardRegisterPdf')->name('society.gstOutwardRegisterPdf');
        Route::get('/outward-register-excel', 'outwardRegisterExcel')->name('society.gstOutwardRegisterExcel');
        Route::match(['get', 'post'], '/classify-outward/{id}', 'classifyOutward')->name('society.gstClassifyOutward');
        Route::get('/input-register', 'inputRegister')->name('society.gstInputRegister');
        Route::get('/input-register-pdf', 'inputRegisterPdf')->name('society.gstInputRegisterPdf');
        Route::get('/input-register-excel', 'inputRegisterExcel')->name('society.gstInputRegisterExcel');
        Route::match(['get', 'post'], '/classify-itc/{id}', 'classifyItc')->name('society.gstClassifyItc');
        Route::get('/itc-summary', 'itcSummary')->name('society.gstItcSummary');
        Route::match(['get', 'post'], '/liability', 'liability')->name('society.gstLiability');
        Route::get('/ledger', 'ledger')->name('society.gstLedger');
        Route::get('/advance-receipts', 'advanceReceipts')->name('society.gstAdvanceReceipts');
        Route::match(['get', 'post'], '/add-advance-receipt/{id?}', 'addAdvanceReceipt')->name('society.gstAddAdvanceReceipt');
        Route::match(['get', 'post'], '/adjust-advance/{id}', 'adjustAdvance')->name('society.gstAdjustAdvance');
        Route::get('/credit-debit-notes', 'creditDebitNotes')->name('society.gstCreditDebitNotes');
        Route::match(['get', 'post'], '/add-credit-debit-note/{id?}', 'addCreditDebitNote')->name('society.gstAddCreditDebitNote');
        Route::get('/payments', 'payments')->name('society.gstPayments');
        Route::match(['get', 'post'], '/prepare-payment', 'preparePayment')->name('society.gstPreparePayment');
        Route::match(['get', 'post'], '/update-payment/{id}', 'updatePayment')->name('society.gstUpdatePayment');
        Route::match(['get', 'post'], '/reconciliation', 'reconciliation')->name('society.gstReconciliation');
        Route::post('/update-return-status', 'updateReturnStatus')->name('society.gstUpdateReturnStatus');
        Route::get('/return-reports', 'returnReports')->name('society.gstReturnReports');
        Route::get('/year-end-report', 'yearEndReport')->name('society.gstYearEndReport');
    });
    Route::get('/gst/{page?}', [SocietyModuleController::class, 'gstPage'])->name('society.gst');
    Route::get('/help-guide', [SocietyModuleController::class, 'helpGuide'])->name('society.helpGuide');
    Route::get('/general-receipt', [SocietyModuleController::class, 'generalReceipt'])->name('society.generalReceipt');
    Route::match(['get', 'post'], '/general-receipt/add/{id?}', [SocietyModuleController::class, 'addGeneralReceipt'])->name('society.addGeneralReceipt');
    Route::delete('/general-receipt/{id}', [SocietyModuleController::class, 'deleteGeneralReceipt'])->name('society.deleteGeneralReceipt');
    Route::get('/general-receipt/bulk-paste', [SocietyModuleController::class, 'bulkPasteGeneralReceipt'])->name('society.bulkPasteGeneralReceipt');
    Route::post('/general-receipt/bulk-paste/save', [SocietyModuleController::class, 'saveBulkPasteGeneralReceipt'])->name('society.saveBulkPasteGeneralReceipt');
    Route::post('/general-receipt/get-details', [SocietyModuleController::class, 'getGeneralReceiptDetails'])->name('society.getGeneralReceiptDetails');
    Route::post('/general-receipt/update', [SocietyModuleController::class, 'updateGeneralReceipt'])->name('society.updateGeneralReceipt');

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
    Route::match(['get', 'post'], '/member-tariff', [SocietyModuleController::class, 'memberTariff'])->name('society.memberTariff')->middleware(CheckSubResellerModule::class . ':mm_membertariff,add|edit,post');
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
    Route::match(['get', 'post'], '/member-payments/add/{id?}', [SocietyModuleController::class, 'addMemberPayment'])->name('society.addMemberPayment')->middleware(CheckSubResellerModule::class . ':mm_memberpayments,auto');
    Route::delete('/member-payments/{id}', [SocietyModuleController::class, 'deleteMemberPayment'])->name('society.deleteMemberPayment')->middleware(CheckSubResellerModule::class . ':mm_memberpayments,delete');
    Route::get('/member-payments/download-sample-template', [SocietyModuleController::class, 'downloadSampleMemberPaymentTemplate'])->name('society.downloadSampleMemberPaymentTemplate');
    Route::get('/member-payments/{id}/voucher', [SocietyModuleController::class, 'memberPaymentVoucher'])->name('society.memberPaymentVoucher');
    Route::get('/member-payments/{id}/voucher/pdf', [SocietyModuleController::class, 'memberPaymentVoucherPdf'])->name('society.memberPaymentVoucherPdf');
    Route::get('/member-receipt', [SocietyModuleController::class, 'memberReceipt'])->name('society.memberReceipt');
    Route::post('/load-member-payment-receipts', [SocietyModuleController::class, 'loadMemberPaymentReceipts'])->name('society.loadMemberPaymentReceipts');
    Route::post('/save-member-payment-receipts', [SocietyModuleController::class, 'saveMemberPaymentReceipts'])->name('society.saveMemberPaymentReceipts');
    Route::get('/member-receipt/bulk-paste', [SocietyModuleController::class, 'memberReceiptBulkPaste'])->name('society.memberReceiptBulkPaste');
    Route::post('/member-receipt/bulk-paste/save', [SocietyModuleController::class, 'saveMemberReceiptBulkPaste'])->name('society.saveMemberReceiptBulkPaste')->middleware(CheckSubResellerModule::class . ':tb_bulkpaste,add,json');
    Route::get('/recalculate-bills', [SocietyModuleController::class, 'recalculateBills'])->name('society.recalculateBills');
    Route::get('/import-member-payments', [SocietyModuleController::class, 'importMemberPayments'])->name('society.importMemberPayments')->middleware(CheckSubResellerModule::class . ':mm_importmemberpayments,add');
    Route::get('/import-society-payments', [SocietyModuleController::class, 'importSocietyPayments'])->name('society.importSocietyPayments')->middleware(CheckSubResellerModule::class . ':mm_importsocietypayments,add');
    Route::match(['get', 'post'], '/all-generated-bills', [SocietyModuleController::class, 'allGeneratedBills'])->name('society.allGeneratedBills');
    Route::post('/all-generated-bills/delete-all', [SocietyModuleController::class, 'deleteAllBillsAndPayments'])->name('society.deleteAllBillsAndPayments')->middleware(CheckSubResellerModule::class . ':mm_allgeneratedbills,delete');
    Route::match(['get', 'post'], '/create-member-logins', [SocietyModuleController::class, 'createMemberLogins'])->name('society.createMemberLogins');
    Route::match(['get', 'post'], '/update-opening-balance', [SocietyModuleController::class, 'updateOpeningBalance'])->name('society.updateOpeningBalance');
    Route::get('/download-member-details', [SocietyModuleController::class, 'downloadMemberDetails'])->name('society.downloadMemberDetails');
    Route::post('/upload-member-details', [SocietyModuleController::class, 'uploadMemberDetails'])->name('society.uploadMemberDetails');

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
    Route::match(['get', 'post'], '/report-accounts/bank-book', [\App\Http\Controllers\Society\AccountReportController::class, 'bankBook'])->name('society.reports.bankBook');
    Route::match(['get', 'post'], '/report-accounts/cash-book', [\App\Http\Controllers\Society\AccountReportController::class, 'cashBook'])->name('society.reports.cashBook');
    // The other account reports: one route per config/account_reports.php entry that names its controller 'action'.
    foreach (config('account_reports') as $accountReport) {
        if (!empty($accountReport['action'])) {
            Route::match(['get', 'post'], '/report-accounts/' . $accountReport['slug'], [\App\Http\Controllers\Society\AccountReportController::class, $accountReport['action']])
                ->name('society.reports.' . $accountReport['action']);
        }
    }
    Route::get('/report-accounts/trial-balance-diff-dr-vs-cr/range', [\App\Http\Controllers\Society\AccountReportController::class, 'trialBalanceDiffRange'])->name('society.reports.trialBalanceDiffRange');
    Route::get('/report-accounts/trial-balance-diff-dr-vs-cr/day', [\App\Http\Controllers\Society\AccountReportController::class, 'trialBalanceDay'])->name('society.reports.trialBalanceDay');
    Route::post('/report-accounts/trial-balance-difference/bulk-recalculate', [\App\Http\Controllers\Society\AccountReportController::class, 'bulkRecalculate'])->name('society.reports.bulkRecalculate');
    Route::get('/report-accounts/ledger-head-details/{headId}', [\App\Http\Controllers\Society\AccountReportController::class, 'ledgerHeadDetails'])->whereNumber('headId')->name('society.reports.ledgerHeadDetails');
    Route::get('/report-accounts/balance-sheet/dues-from-members', [\App\Http\Controllers\Society\AccountReportController::class, 'balanceDueFromMembers'])->name('society.reports.balanceDueFromMembers');
    Route::get('/report-accounts/balance-sheet/advance-from-members', [\App\Http\Controllers\Society\AccountReportController::class, 'balanceDuesAdvance'])->name('society.reports.balanceDuesAdvance');
    Route::post('/report-accounts/bill-summary-update/fix-balance-mismatches', [\App\Http\Controllers\Society\AccountReportController::class, 'fixBalanceMismatches'])->name('society.reports.fixBalanceMismatches');
    Route::post('/report-accounts/bill-summary-update/reconcile-preview', [\App\Http\Controllers\Society\AccountReportController::class, 'reconcilePreview'])->name('society.reports.reconcilePreview');
    Route::post('/report-accounts/bill-summary-update/reconcile-apply', [\App\Http\Controllers\Society\AccountReportController::class, 'reconcileApply'])->name('society.reports.reconcileApply');
    Route::match(['get', 'post'], '/report-accounts/statutory-audit-report/year/{financialYearId}', [\App\Http\Controllers\Society\AccountReportController::class, 'auditReport'])->whereNumber('financialYearId')->name('society.reports.auditReportYear');
    Route::get('/report-accounts/statutory-audit-report/pdf/{id}/{lang?}', [\App\Http\Controllers\Society\AccountReportController::class, 'auditReportPdf'])->whereNumber('id')->name('society.reports.auditReportPdf');
    Route::get('/report-accounts/bill-half-page/pdf', [\App\Http\Controllers\Society\AccountReportController::class, 'billHalfPagePdf'])->name('society.reports.billHalfPagePdf');
    Route::get('/report-accounts/bill-half-page/excel', [\App\Http\Controllers\Society\AccountReportController::class, 'billHalfPageExcel'])->name('society.reports.billHalfPageExcel');
    Route::get('/report-accounts/{report}', [SocietyModuleController::class, 'reportAccountsItem'])->where('report', '[a-z0-9-]+')->name('society.reportAccountsItem');
    Route::get('/report-society', [SocietyModuleController::class, 'reportSociety'])->name('society.reportSociety');
    Route::match(['get', 'post'], '/journal-voucher/{voucherNo?}', [SocietyModuleController::class, 'journalVoucher'])->name('society.journalVoucher');
    Route::delete('/journal-voucher/{voucherNo}', [SocietyModuleController::class, 'deleteJournalVoucher'])->name('society.deleteJournalVoucher');
    Route::get('/closing-balances', [SocietyModuleController::class, 'closingBalances'])->name('society.closingBalances');
    Route::get('/ledger-closing-trial-balance', [SocietyModuleController::class, 'ledgerClosingTrialBalance'])->name('society.ledgerClosingTrialBalance');

    // Utilities
    Route::get('/update-bill-dates', [SocietyModuleController::class, 'updateBillDates'])->name('society.updateBillDates');

    // Modal AJAX endpoints
    Route::get('/get-ledger-head-details', [SocietyModuleController::class, 'getLedgerHeadDetails'])->name('society.getLedgerHeadDetails');
    Route::post('/update-ledger-head', [SocietyModuleController::class, 'updateLedgerHead'])->name('society.updateLedgerHead');
    Route::post('/get-member-details', [SocietyModuleController::class, 'getMemberDetails'])->name('society.getMemberDetails');
    Route::get('/get-member-op-balance/{memberId}', [SocietyModuleController::class, 'getMemberOpBalance'])->whereNumber('memberId')->name('society.getMemberOpBalance');
    Route::post('/get-member-by-flat-no', [SocietyModuleController::class, 'getMemberDetailsByFlatNo'])->name('society.getMemberByFlatNo');
    Route::post('/update-member-details', [SocietyModuleController::class, 'updateSocietyMemberDetails'])->name('society.updateMemberDetails');
    Route::get('/get-bill-details', [SocietyModuleController::class, 'getBillDetails'])->name('society.getBillDetails');
    Route::post('/get-all-members-bill-summary-details', [SocietyModuleController::class, 'getAllMembersBillSummaryDetails'])->name('society.getAllMembersBillSummaryDetails');
    Route::post('/update-member-bill-summary', [SocietyModuleController::class, 'updateMemberBillSummaryById'])->name('society.updateMemberBillSummaryById');
    Route::post('/update-current-member-bill-summary', [SocietyModuleController::class, 'updateCurrentMemberBillSummaryById'])->name('society.updateCurrentMemberBillSummaryById');
    Route::post('/generate-bill', [SocietyModuleController::class, 'generateBill'])->name('society.generateBill')->middleware(CheckSubResellerModule::class . ':tb_generatebill,generate,json');
    Route::post('/save-payment-entry', [SocietyModuleController::class, 'savePaymentEntry'])->name('society.savePaymentEntry')->middleware(CheckSubResellerModule::class . ':tb_paymententry,add,json');
    Route::post('/save-member-receipt', [SocietyModuleController::class, 'saveMemberReceiptAjax'])->name('society.saveMemberReceipt');

    // Bill Print
    Route::get('/bill-with-receipt-tabular', [\App\Http\Controllers\Society\AccountReportController::class, 'billWithReceiptTabular'])->name('society.billWithReceiptTabular');
    Route::get('/bill-with-receipt-tabular/print', [\App\Http\Controllers\Society\AccountReportController::class, 'printMemberBills'])->name('society.printMemberBills');
    Route::get('/bill-with-receipt-tabular/pdf', [\App\Http\Controllers\Society\AccountReportController::class, 'printMemberBillsPdf'])->name('society.printMemberBillsPdf');
    Route::get('/bill-receipt', [SocietyModuleController::class, 'billReceipt'])->name('society.billReceipt');
});
