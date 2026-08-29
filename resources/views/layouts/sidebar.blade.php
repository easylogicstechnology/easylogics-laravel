<aside class="sidebar">
    @if(Auth::user()->role === 'Admin')
        <div class="menu-label">Main</div>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>

        <div class="menu-label menu-toggle">Manage Societies <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.societies.create') }}" class="{{ request()->routeIs('admin.societies.create') ? 'active' : '' }}">Add Society</a>
            <a href="{{ route('admin.societies.index') }}" class="{{ request()->routeIs('admin.societies.index') ? 'active' : '' }}">Societies List</a>
            <a href="{{ route('admin.societies.assign') }}" class="{{ request()->routeIs('admin.societies.assign') ? 'active' : '' }}">Reseller Societies</a>
        </div>

        <div class="menu-label menu-toggle">Reseller Management <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.resellers.index') }}" class="{{ request()->routeIs('admin.resellers.index') ? 'active' : '' }}">Reseller Credits</a>
        </div>

        <div class="menu-label menu-toggle">Society Features <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.societies.features') }}" class="{{ request()->routeIs('admin.societies.features') ? 'active' : '' }}">SMS / WhatsApp / App</a>
        </div>

        <div class="menu-label menu-toggle">Parameters <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('admin.societies.parameters') }}" class="{{ request()->routeIs('admin.societies.parameters') ? 'active' : '' }}">Society Parameters</a>
        </div>

    @elseif(Auth::user()->role === 'Society')
        <div class="menu-label">Main</div>
        <a href="{{ route('society.dashboard') }}" class="{{ request()->routeIs('society.dashboard') ? 'active' : '' }}">Dashboard</a>

        <div class="menu-label menu-toggle">Society <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.identity') }}" class="{{ request()->routeIs('society.identity') ? 'active' : '' }}">Society Identity</a>
            <a href="{{ route('society.parameters') }}" class="{{ request()->routeIs('society.parameters') ? 'active' : '' }}">Society Parameters</a>
            <a href="{{ route('society.tariffDefinition') }}" class="{{ request()->routeIs('society.tariffDefinition') ? 'active' : '' }}">Tariff Definition</a>
            <a href="{{ route('society.headSubCategories') }}" class="{{ request()->routeIs('society.headSubCategories') ? 'active' : '' }}">Society Head Sub Group</a>
            <a href="{{ route('society.ledgerHeads') }}" class="{{ request()->routeIs('society.ledgerHeads') ? 'active' : '' }}">Society Ledger Heads</a>
            <a href="{{ route('society.tariffs') }}" class="{{ request()->routeIs('society.tariffs') ? 'active' : '' }}">Society Tariffs</a>
            <a href="{{ route('society.tariffOrders') }}" class="{{ request()->routeIs('society.tariffOrders') ? 'active' : '' }}">Society Tariff Order</a>
            <a href="{{ route('society.payments') }}" class="{{ request()->routeIs('society.payments') ? 'active' : '' }}">Society Payments</a>
            <a href="{{ route('society.importSocietyPayments') }}" class="{{ request()->routeIs('society.importSocietyPayments') ? 'active' : '' }}">Import Society Payments</a>
            <a href="{{ route('society.bankReconciliation') }}" class="{{ request()->routeIs('society.bankReconciliation') ? 'active' : '' }}">Bank Reconciliation</a>
            <a href="{{ route('society.cashContra') }}" class="{{ request()->routeIs('society.cashContra') ? 'active' : '' }}">Society Cash Contra</a>
            <a href="{{ route('society.generalReceipt') }}" class="{{ request()->routeIs('society.generalReceipt') ? 'active' : '' }}">General Receipt</a>
        </div>

        <div class="menu-label menu-toggle">Member <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.buildingIdentity') }}" class="{{ request()->routeIs('society.buildingIdentity') ? 'active' : '' }}">Building Identity</a>
            <a href="{{ route('society.wingIdentity') }}" class="{{ request()->routeIs('society.wingIdentity') ? 'active' : '' }}">Wing Identity</a>
            <a href="{{ route('society.memberIdentity') }}" class="{{ request()->routeIs('society.memberIdentity') ? 'active' : '' }}">Member Identity</a>
            <a href="{{ route('society.memberTariff') }}" class="{{ request()->routeIs('society.memberTariff') ? 'active' : '' }}">Member Tariff</a>
            <a href="{{ route('society.tenantMemberIdentity') }}" class="{{ request()->routeIs('society.tenantMemberIdentity') ? 'active' : '' }}">Tenant Member Identity</a>
            <a href="{{ route('society.memberPayments') }}" class="{{ request()->routeIs('society.memberPayments') ? 'active' : '' }}">Member Payments</a>
            <a href="{{ route('society.importMemberPayments') }}" class="{{ request()->routeIs('society.importMemberPayments') ? 'active' : '' }}">Import Member Payments</a>
            <a href="{{ route('society.importSocietyPayments') }}" class="{{ request()->routeIs('society.importSocietyPayments') ? 'active' : '' }}">Import Society Payments</a>
            <a href="{{ route('society.allGeneratedBills') }}" class="{{ request()->routeIs('society.allGeneratedBills') ? 'active' : '' }}">All Generated Bills</a>
            <a href="{{ route('society.createMemberLogins') }}" class="{{ request()->routeIs('society.createMemberLogins') ? 'active' : '' }}">Create Member Logins</a>
            <a href="{{ route('society.updateOpeningBalance') }}" class="{{ request()->routeIs('society.updateOpeningBalance') ? 'active' : '' }}">Update Opening Balance</a>
        </div>

        <div class="menu-label menu-toggle">Employee <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.employeeCategory') }}" class="{{ request()->routeIs('society.employeeCategory') ? 'active' : '' }}">Employee Category</a>
            <a href="{{ route('society.employeeSubCategory') }}" class="{{ request()->routeIs('society.employeeSubCategory') ? 'active' : '' }}">Employee Sub Category</a>
            <a href="{{ route('society.employeeDetails') }}" class="{{ request()->routeIs('society.employeeDetails') ? 'active' : '' }}">Employee Details</a>
        </div>

        @php
            $__soc = \App\Models\Society::where('user_id', Auth::id())->first();
        @endphp
        @if($__soc && $__soc->enable_sms === 'Y')
        <div class="menu-label menu-toggle">Send SMS <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="#">Account Dues From Member</a>
            <a href="#">Greetings</a>
            <a href="#">Member Tariff</a>
            <a href="#">Receipt Acknowledgment</a>
            <a href="#">All Generated Bills</a>
        </div>
        @endif

        <div class="menu-label menu-toggle">Reports <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.reportAccounts') }}" class="{{ request()->routeIs('society.reportAccounts') ? 'active' : '' }}">Accounts</a>
            <a href="{{ route('society.reportSociety') }}" class="{{ request()->routeIs('society.reportSociety') ? 'active' : '' }}">Society</a>
            <a href="{{ route('society.journalVoucher') }}" class="{{ request()->routeIs('society.journalVoucher') ? 'active' : '' }}">Journal Voucher</a>
            <a href="{{ route('society.closingBalances') }}" class="{{ request()->routeIs('society.closingBalances') ? 'active' : '' }}">Closing Balances</a>
            <a href="{{ route('society.ledgerClosingTrialBalance') }}" class="{{ request()->routeIs('society.ledgerClosingTrialBalance') ? 'active' : '' }}">Ledger - Closing vs Trial Balance</a>
        </div>

        <div class="menu-label menu-toggle">Utilities <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.updateBillDates') }}" class="{{ request()->routeIs('society.updateBillDates') ? 'active' : '' }}">Update Bill Dates</a>
        </div>

        <div class="menu-label menu-toggle">Bill Print <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('society.billWithReceiptTabular') }}" class="{{ request()->routeIs('society.billWithReceiptTabular') ? 'active' : '' }}">Bill With Receipt Tabular</a>
            <a href="{{ route('society.billTaxInvoiceGst') }}" class="{{ request()->routeIs('society.billTaxInvoiceGst') ? 'active' : '' }}">Bill Tax Invoice GST</a>
            <a href="{{ route('society.billFullPage') }}" class="{{ request()->routeIs('society.billFullPage') ? 'active' : '' }}">Bill Full Page</a>
            <a href="{{ route('society.billHalfPage') }}" class="{{ request()->routeIs('society.billHalfPage') ? 'active' : '' }}">Bill Half Page</a>
            <a href="{{ route('society.billWithInterestGst') }}" class="{{ request()->routeIs('society.billWithInterestGst') ? 'active' : '' }}">Bill With Interest GST</a>
            <a href="{{ route('society.billSummaryWithPrevData') }}" class="{{ request()->routeIs('society.billSummaryWithPrevData') ? 'active' : '' }}">Bill Summary With Prev Data</a>
            <a href="{{ route('society.billReceipt') }}" class="{{ request()->routeIs('society.billReceipt') ? 'active' : '' }}">Bill Receipt</a>
        </div>

    @elseif(Auth::user()->role === 'Reseller' || Auth::user()->role === 'ResellerUser')
        @php $__authUser = Auth::user(); @endphp
        <div class="menu-label">Main</div>
        <a href="{{ route('reseller.dashboard') }}" class="{{ request()->routeIs('reseller.dashboard') ? 'active' : '' }}">Dashboard</a>

        @if($__authUser->role === 'Reseller')
        <div class="menu-label menu-toggle">Manage Users <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('reseller.users.index') }}" class="{{ request()->routeIs('reseller.users.index') ? 'active' : '' }}">User List</a>
            <a href="{{ route('reseller.users.create') }}" class="{{ request()->routeIs('reseller.users.create') ? 'active' : '' }}">Create User</a>
            <a href="{{ route('reseller.users.permissions') }}" class="{{ request()->routeIs('reseller.users.permissions') ? 'active' : '' }}">Permissions</a>
        </div>
        @endif

        <div class="menu-label menu-toggle">My Profile <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('reseller.profile') }}" class="{{ request()->routeIs('reseller.profile') ? 'active' : '' }}">Personal Identity</a>
        </div>

        @if($__authUser->hasPermission('reports', 'can_view'))
        <div class="menu-label menu-toggle">Reports <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="#" class="text-muted" style="opacity:0.5;">Audit Reports</a>
        </div>
        @endif

        @if($__authUser->hasPermission('software', 'can_view'))
        <div class="menu-label menu-toggle">Societies <span class="caret-icon">&#9656;</span></div>
        <div class="submenu">
            <a href="{{ route('reseller.societies.assigned') }}" class="{{ request()->routeIs('reseller.societies.assigned') ? 'active' : '' }}">My Societies</a>
            @if($__authUser->hasPermission('software', 'can_add'))
            <a href="{{ route('reseller.societies.create') }}" class="{{ request()->routeIs('reseller.societies.create') ? 'active' : '' }}">Create Society</a>
            @endif
            @if($__authUser->hasPermission('settings', 'can_view'))
            <a href="{{ route('reseller.financeYearMapping') }}" class="{{ request()->routeIs('reseller.financeYearMapping') ? 'active' : '' }}">Society Finance Year Mapping</a>
            @endif
        </div>
        @endif

    @elseif(Auth::user()->role === 'Member')
        <div class="menu-label">Main</div>
        <a href="{{ route('member.dashboard') }}" class="{{ request()->routeIs('member.dashboard') ? 'active' : '' }}">Dashboard</a>
    @endif
</aside>
