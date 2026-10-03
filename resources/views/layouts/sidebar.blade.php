<aside class="sidebar">
    @if(Auth::user()->role === 'Admin')
        <div class="menu-label">Main</div>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i>Dashboard</a>

        <div class="menu-label menu-toggle"><span><i class="fa fa-university"></i>Manage Societies</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.societies.create') }}" class="{{ request()->routeIs('admin.societies.create') ? 'active' : '' }}">Add Society</a>
            <a href="{{ route('admin.societies.assign') }}" class="{{ request()->routeIs('admin.societies.assign') ? 'active' : '' }}">Reseller Societies</a>
            <a href="{{ route('admin.societies.index') }}" class="{{ request()->routeIs('admin.societies.index') ? 'active' : '' }}">Societies List</a>
        </div>

        <a href="{{ route('admin.societies.parameters') }}" class="{{ request()->routeIs('admin.societies.parameters') ? 'active' : '' }}"><i class="fa fa-sliders"></i>Society Parameters</a>

        <div class="menu-label menu-toggle"><span><i class="fa fa-bar-chart"></i>Reports</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.reports.trialBalanceDiff') }}" class="{{ request()->routeIs('admin.reports.trialBalanceDiff') ? 'active' : '' }}">Trial Balance Diff Report</a>
            <a href="{{ route('admin.reports.resellerSociety') }}" class="{{ request()->routeIs('admin.reports.resellerSociety') ? 'active' : '' }}">Reseller Society Report</a>
            <a href="{{ route('admin.reports.resellerPayments') }}" class="{{ request()->routeIs('admin.reports.resellerPayments') ? 'active' : '' }}">Reseller Payments</a>
            <a href="{{ route('admin.reports.resellerPlans') }}" class="{{ request()->routeIs('admin.reports.resellerPlans*') ? 'active' : '' }}">Reseller Plans</a>
            <a href="{{ route('admin.reports.paymentSetup') }}" class="{{ request()->routeIs('admin.reports.paymentSetup') ? 'active' : '' }}">Payment Setup Check</a>
            <a href="{{ route('admin.reports.complaints') }}" class="{{ request()->routeIs('admin.reports.complaints') ? 'active' : '' }}">Complaint Register</a>
        </div>

        <div class="menu-label menu-toggle"><span><i class="fa fa-globe"></i>Website Content</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.website.founder') }}" class="{{ request()->routeIs('admin.website.founder') ? 'active' : '' }}">Manage Founder</a>
            <a href="{{ route('admin.website.partners') }}" class="{{ request()->routeIs('admin.website.partners*') ? 'active' : '' }}">Manage Partners</a>
            <a href="{{ route('admin.website.helpVideo') }}" class="{{ request()->routeIs('admin.website.helpVideo') ? 'active' : '' }}">Manage Help Video</a>
        </div>

    @elseif(Auth::user()->role === 'Society')
        <div class="menu-label">Main</div>
        <a href="{{ route('society.dashboard') }}" class="{{ request()->routeIs('society.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i>Dashboard</a>

        @if(\App\Support\SocietyMenuAccess::section('Society'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-building"></i>Society</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Identity'))
            <a href="{{ route('society.identity') }}" class="{{ request()->routeIs('society.identity') ? 'active' : '' }}">Society Identity</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Parameters'))
            <a href="{{ route('society.parameters') }}" class="{{ request()->routeIs('society.parameters') ? 'active' : '' }}">Society Parameters</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Month-wise Interest Rates'))
            <a href="{{ route('society.monthInterestRates') }}" class="{{ request()->routeIs('society.monthInterestRates') ? 'active' : '' }}">Month-wise Interest Rates</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Tariff Definition'))
            <a href="{{ route('society.tariffDefinition') }}" class="{{ request()->routeIs('society.tariffDefinition') ? 'active' : '' }}">Tariff Defination</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Head Sub Group'))
            <a href="{{ route('society.headSubCategories') }}" class="{{ request()->routeIs('society.headSubCategories') ? 'active' : '' }}">Society Head Sub Group</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Ledger Heads'))
            <a href="{{ route('society.ledgerHeads') }}" class="{{ request()->routeIs('society.ledgerHeads') ? 'active' : '' }}">Society Ledger Heads</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Vendor Detail'))
            <a href="{{ route('society.vendorDetails') }}" class="{{ request()->routeIs('society.vendorDetails') ? 'active' : '' }}">Vendor Detail</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Vendor Billing'))
            <a href="{{ route('society.vendorBillings') }}" class="{{ request()->routeIs('society.vendorBillings') ? 'active' : '' }}">Vendor Billing</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Tariffs'))
            <a href="{{ route('society.tariffs') }}" class="{{ request()->routeIs('society.tariffs') ? 'active' : '' }}">Society Tariffs</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Tariff Order'))
            <a href="{{ route('society.tariffOrders') }}" class="{{ request()->routeIs('society.tariffOrders') ? 'active' : '' }}">Society Tariff Order</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Payments'))
            <a href="{{ route('society.payments') }}" class="{{ request()->routeIs('society.payments') ? 'active' : '' }}">Society Payments</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Import Society Payments'))
            <a href="{{ route('society.importSocietyPayments') }}" class="{{ request()->routeIs('society.importSocietyPayments') ? 'active' : '' }}">Import Society Payments</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Bank Reconciliation'))
            <a href="{{ route('society.bankReconciliation') }}" class="{{ request()->routeIs('society.bankReconciliation') ? 'active' : '' }}">Bank Reconciliation</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Society Cash Contra'))
            <a href="{{ route('society.cashContra') }}" class="{{ request()->routeIs('society.cashContra') ? 'active' : '' }}">Society Cash Contra</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'General Receipt'))
            <a href="{{ route('society.generalReceipt') }}" class="{{ request()->routeIs('society.generalReceipt') ? 'active' : '' }}">General Receipt</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Society', 'Debit Note / Credit Note'))
            <a href="{{ route('society.debitCreditNotes') }}" class="{{ request()->routeIs('society.debitCreditNotes*') ? 'active' : '' }}">Debit Note / Credit Note</a>
            @endif
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Member'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-users"></i>Member</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Building Identity'))
            <a href="{{ route('society.buildingIdentity') }}" class="{{ request()->routeIs('society.buildingIdentity') ? 'active' : '' }}">Building Identity</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Wing Identity'))
            <a href="{{ route('society.wingIdentity') }}" class="{{ request()->routeIs('society.wingIdentity') ? 'active' : '' }}">Wing Identity</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Member Identity'))
            <a href="{{ route('society.memberIdentity') }}" class="{{ request()->routeIs('society.memberIdentity') ? 'active' : '' }}">Member Identity</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Member Tariff'))
            <a href="{{ route('society.memberTariff') }}" class="{{ request()->routeIs('society.memberTariff') ? 'active' : '' }}">Member Tariff</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Tenant Member Identity'))
            <a href="{{ route('society.tenantMemberIdentity') }}" class="{{ request()->routeIs('society.tenantMemberIdentity') ? 'active' : '' }}">Tenant Member Identity</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Member Payments'))
            <a href="{{ route('society.memberPayments') }}" class="{{ request()->routeIs('society.memberPayments') ? 'active' : '' }}">Member Payments</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Import Member Payments'))
            <a href="{{ route('society.importMemberPayments') }}" class="{{ request()->routeIs('society.importMemberPayments') ? 'active' : '' }}">Import Member Payments</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Import Society Payments'))
            <a href="{{ route('society.importSocietyPayments') }}" class="{{ request()->routeIs('society.importSocietyPayments') ? 'active' : '' }}">Import Society Payments</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'All Generated Bills'))
            <a href="{{ route('society.allGeneratedBills') }}" class="{{ request()->routeIs('society.allGeneratedBills') ? 'active' : '' }}">All Generated Bills</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Create Member Logins'))
            <a href="{{ route('society.createMemberLogins') }}" class="{{ request()->routeIs('society.createMemberLogins') ? 'active' : '' }}">Create Member Logins</a>
            @endif
            @if(\App\Support\SocietyMenuAccess::item('Member', 'Update Opening Balance'))
            <a href="{{ route('society.updateOpeningBalance') }}" class="{{ request()->routeIs('society.updateOpeningBalance') ? 'active' : '' }}">Update Opening Balance</a>
            @endif
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Registers'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-book"></i>Registers</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.registers', 'fd-register') }}" class="{{ request()->is('*registers/fd-register') ? 'active' : '' }}">FD Register</a>
            <a href="{{ route('society.registers', 'shares-register') }}" class="{{ request()->is('*registers/shares-register') ? 'active' : '' }}">Shares Register</a>
            <a href="{{ route('society.registers', 'lien-register') }}" class="{{ request()->is('*registers/lien-register') ? 'active' : '' }}">Lien Register</a>
            <a href="{{ route('society.registers', 'nominee-register') }}" class="{{ request()->is('*registers/nominee-register') ? 'active' : '' }}">Nominee Register</a>
            <a href="{{ route('society.registers', 'form-i') }}" class="{{ request()->is('*registers/form-i') ? 'active' : '' }}">Form I</a>
            <a href="{{ route('society.registers', 'form-j') }}" class="{{ request()->is('*registers/form-j') ? 'active' : '' }}">Form J</a>
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Employee'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-id-card"></i>Employee</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.employeeCategory') }}" class="{{ request()->routeIs('society.employeeCategory') ? 'active' : '' }}">Employee Category</a>
            <a href="{{ route('society.employeeSubCategory') }}" class="{{ request()->routeIs('society.employeeSubCategory') ? 'active' : '' }}">Employee Sub Category</a>
            <a href="{{ route('society.employeeDetails') }}" class="{{ request()->routeIs('society.employeeDetails') ? 'active' : '' }}">Employee Details</a>
        </div>
        @endif

        @php
            $__soc = \App\Models\Society::where('user_id', Auth::id())->first();
        @endphp
        @if($__soc && $__soc->enable_sms === 'Y')
        @if(\App\Support\SocietyMenuAccess::section('Send Sms'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-comment-o"></i>Send Sms</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="#">account dues from member</a>
            <a href="#">Greetings</a>
            <a href="#">Member Tariff</a>
            <a href="#">Receipt Acknowledgment</a>
            <a href="#">All Generated Bills</a>
        </div>
        @endif
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Reports'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-bar-chart"></i>Reports</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.reportAccounts') }}" class="{{ request()->routeIs('society.reportAccounts') ? 'active' : '' }}">Accounts</a>
            <a href="{{ route('society.reportSociety') }}" class="{{ request()->routeIs('society.reportSociety') ? 'active' : '' }}">Society</a>
            <a href="{{ route('society.journalVoucher') }}" class="{{ request()->routeIs('society.journalVoucher') ? 'active' : '' }}">Journal Voucher</a>
            <a href="{{ route('society.closingBalances') }}" class="{{ request()->routeIs('society.closingBalances') ? 'active' : '' }}">Closing Balances</a>
            <a href="{{ route('society.ledgerClosingTrialBalance') }}" class="{{ request()->routeIs('society.ledgerClosingTrialBalance') ? 'active' : '' }}">Ledger - closing vs trial balance</a>
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('TDS'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-money"></i>TDS</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.tds', 'dashboard') }}" class="{{ request()->is('*tds/dashboard') ? 'active' : '' }}">TDS Dashboard</a>
            <a href="{{ route('society.tds', 'sections') }}" class="{{ request()->is('*tds/sections') ? 'active' : '' }}">TDS Sections</a>
            <a href="{{ route('society.tds', 'deductees') }}" class="{{ request()->is('*tds/deductees') ? 'active' : '' }}">TDS Deductees</a>
            <a href="{{ route('society.tds', 'transactions') }}" class="{{ request()->is('*tds/transactions') ? 'active' : '' }}">TDS Transactions</a>
            <a href="{{ route('society.tds', 'report') }}" class="{{ request()->is('*tds/report') ? 'active' : '' }}">TDS Report</a>
            <a href="{{ route('society.tds', 'ledger') }}" class="{{ request()->is('*tds/ledger') ? 'active' : '' }}">TDS Ledger</a>
            <a href="{{ route('society.tds', 'challans') }}" class="{{ request()->is('*tds/challans') ? 'active' : '' }}">Challan Management</a>
            <a href="{{ route('society.tds', 'certificates') }}" class="{{ request()->is('*tds/certificates') ? 'active' : '' }}">TDS Certificates</a>
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('GST'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-calculator"></i>GST</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.gst', 'dashboard') }}" class="{{ request()->is('*gst/dashboard') ? 'active' : '' }}">GST Dashboard</a>
            <a href="{{ route('society.gst', 'master-setup') }}" class="{{ request()->is('*gst/master-setup') ? 'active' : '' }}">GST Master</a>
            <a href="{{ route('society.gst', 'hsn-master') }}" class="{{ request()->is('*gst/hsn-master') ? 'active' : '' }}">HSN/SAC Master</a>
            <a href="{{ route('society.gst', 'outward-register') }}" class="{{ request()->is('*gst/outward-register') ? 'active' : '' }}">Outward Register</a>
            <a href="{{ route('society.gst', 'input-register') }}" class="{{ request()->is('*gst/input-register') ? 'active' : '' }}">Input / Purchase Register</a>
            <a href="{{ route('society.gst', 'itc-summary') }}" class="{{ request()->is('*gst/itc-summary') ? 'active' : '' }}">ITC Summary</a>
            <a href="{{ route('society.gst', 'liability') }}" class="{{ request()->is('*gst/liability') ? 'active' : '' }}">GST Liability</a>
            <a href="{{ route('society.gst', 'ledger') }}" class="{{ request()->is('*gst/ledger') ? 'active' : '' }}">GST Ledger</a>
            <a href="{{ route('society.gst', 'advance-receipts') }}" class="{{ request()->is('*gst/advance-receipts') ? 'active' : '' }}">Advance Receipts</a>
            <a href="{{ route('society.gst', 'credit-debit-notes') }}" class="{{ request()->is('*gst/credit-debit-notes') ? 'active' : '' }}">Credit/Debit Notes</a>
            <a href="{{ route('society.gst', 'payments') }}" class="{{ request()->is('*gst/payments') ? 'active' : '' }}">GST Payment/Challan</a>
            <a href="{{ route('society.gst', 'reconciliation') }}" class="{{ request()->is('*gst/reconciliation') ? 'active' : '' }}">Reconciliation</a>
            <a href="{{ route('society.gst', 'return-reports') }}" class="{{ request()->is('*gst/return-reports') ? 'active' : '' }}">Return Reports</a>
            <a href="{{ route('society.gst', 'year-end-report') }}" class="{{ request()->is('*gst/year-end-report') ? 'active' : '' }}">Year-End Report</a>
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Utilities'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-wrench"></i>Utilities</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.updateBillDates') }}" class="{{ request()->routeIs('society.updateBillDates') ? 'active' : '' }}">update bill dates</a>
        </div>
        @endif

        @if(\App\Support\SocietyMenuAccess::section('Bill Print'))
        <div class="menu-label menu-toggle"><span><i class="fa fa-print"></i>Bill Print</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.billWithReceiptTabular') }}" class="{{ request()->routeIs('society.billWithReceiptTabular') ? 'active' : '' }}">Bill With Receipt Tabular</a>
            <a href="{{ route('society.reports.billTaxInvoiceGst') }}" class="{{ request()->routeIs('society.reports.billTaxInvoiceGst') ? 'active' : '' }}">Bill Tax Invoice Gst</a>
            <a href="{{ route('society.reports.billFullPage') }}" class="{{ request()->routeIs('society.reports.billFullPage') ? 'active' : '' }}">Bill Full Page</a>
            <a href="{{ route('society.reports.billHalfPage') }}" class="{{ request()->routeIs('society.reports.billHalfPage') ? 'active' : '' }}">Bill Half Page</a>
            <a href="{{ route('society.reports.billWithInterestGst') }}" class="{{ request()->routeIs('society.reports.billWithInterestGst') ? 'active' : '' }}">Bill With Interest Gst</a>
            <a href="{{ route('society.reports.billSummaryWithPrevData') }}" class="{{ request()->routeIs('society.reports.billSummaryWithPrevData') ? 'active' : '' }}">Bill Summary With Prev Data</a>
            <a href="{{ route('society.billReceipt') }}" class="{{ request()->routeIs('society.billReceipt') ? 'active' : '' }}">Bill Receipt</a>
        </div>
        @endif

        <a href="{{ route('society.helpGuide') }}" class="{{ request()->routeIs('society.helpGuide') ? 'active' : '' }}"><i class="fa fa-question-circle"></i>Help &amp; Guide</a>

    @elseif(Auth::user()->role === 'Reseller')
        {{-- CakePHP MenuComponent, Reseller: flat list in this order ('Audit reports' is hidden there too - that action was never written). --}}
        <div class="menu-label">Main</div>
        <a href="{{ route('reseller.dashboard') }}" class="{{ request()->routeIs('reseller.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i>Dashboard</a>
        <a href="{{ route('reseller.profile') }}" class="{{ request()->routeIs('reseller.profile') ? 'active' : '' }}"><i class="fa fa-user"></i>Personal Identity</a>

        <div class="menu-label menu-toggle"><span><i class="fa fa-users"></i>Manage Users</span> <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('reseller.users.index') }}" class="{{ request()->routeIs('reseller.users.index', 'reseller.users.edit') ? 'active' : '' }}">User List</a>
            <a href="{{ route('reseller.users.create') }}" class="{{ request()->routeIs('reseller.users.create') ? 'active' : '' }}">Create User</a>
            <a href="{{ route('reseller.users.permissions') }}" class="{{ request()->routeIs('reseller.users.permissions') ? 'active' : '' }}">Permissions</a>
        </div>

        <a href="{{ route('reseller.societies.assigned') }}" class="{{ request()->routeIs('reseller.societies.assigned') ? 'active' : '' }}"><i class="fa fa-university"></i>My Societies</a>
        <a href="{{ route('reseller.societies.create') }}" class="{{ request()->routeIs('reseller.societies.create') ? 'active' : '' }}"><i class="fa fa-plus-square"></i>Create Society</a>
        <a href="{{ route('reseller.financeYearMapping') }}" class="{{ request()->routeIs('reseller.financeYearMapping') ? 'active' : '' }}"><i class="fa fa-calendar"></i>Society Finance Year Mapping</a>
        <a href="{{ route('reseller.complaints') }}" class="{{ request()->routeIs('reseller.complaints*') ? 'active' : '' }}"><i class="fa fa-exclamation-circle"></i>Complaints</a>
        <a href="{{ route('reseller.paymentDashboard') }}" class="{{ request()->routeIs('reseller.paymentDashboard') ? 'active' : '' }}"><i class="fa fa-money"></i>Payment Dashboard</a>
        <a href="{{ route('reseller.help') }}" class="{{ request()->routeIs('reseller.help') ? 'active' : '' }}"><i class="fa fa-question-circle"></i>Help &amp; Guide</a>
    @elseif(Auth::user()->role === 'SubReseller')
        {{-- A reseller's team login (CakePHP MenuComponent, SubReseller): Manage Users, Personal Identity, Create Society and
             Payment Dashboard are reseller-account actions and are never shown; the rest follows the granted permissions. --}}
        @php $__authUser = Auth::user(); @endphp
        <div class="menu-label">Main</div>
        <a href="{{ route('reseller.dashboard') }}" class="{{ request()->routeIs('reseller.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i>Dashboard</a>

        @if($__authUser->hasPermission('software', 'view'))
        <a href="{{ route('reseller.societies.assigned') }}" class="{{ request()->routeIs('reseller.societies.assigned') ? 'active' : '' }}"><i class="fa fa-university"></i>My Societies</a>
        @endif
        @if($__authUser->hasPermission('settings', 'view'))
        <a href="{{ route('reseller.financeYearMapping') }}" class="{{ request()->routeIs('reseller.financeYearMapping') ? 'active' : '' }}"><i class="fa fa-calendar"></i>Society Finance Year Mapping</a>
        @endif

        <a href="{{ route('reseller.complaints') }}" class="{{ request()->routeIs('reseller.complaints*') ? 'active' : '' }}"><i class="fa fa-exclamation-circle"></i>Complaints</a>
        <a href="{{ route('reseller.help') }}" class="{{ request()->routeIs('reseller.help') ? 'active' : '' }}"><i class="fa fa-question-circle"></i>Help &amp; Guide</a>

    @elseif(Auth::user()->role === 'Member')
        <div class="menu-label">Main</div>
        <a href="{{ route('member.dashboard') }}" class="{{ request()->routeIs('member.dashboard') ? 'active' : '' }}"><i class="fa fa-tachometer"></i>Dashboard</a>
    @endif
</aside>
