<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EasyLogics Technology')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; }
        .navbar { background: #2c3e50; color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; position: fixed; top: 0; left: 0; right: 0; z-index: 100; }
        .navbar h1 { font-size: 18px; font-weight: 600; display: flex; align-items: center; }
        .navbar .user-info { display: flex; align-items: center; gap: 16px; font-size: 14px; }
        .navbar .role-badge { background: rgba(255,255,255,0.15); padding: 4px 10px; border-radius: 4px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .navbar form button { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 6px 14px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .navbar form button:hover { background: rgba(255,255,255,0.2); }

        .society-topbar { background: #34495e; position: fixed; top: 52px; left: 240px; right: 0; z-index: 99; display: flex; align-items: center; padding: 6px 16px; gap: 4px; flex-wrap: wrap; border-bottom: 1px solid #2c3e50; }
        .society-topbar .menu-buttons { display: flex; gap: 4px; flex-wrap: wrap; }
        .society-topbar .menu-buttons a { display: inline-flex; align-items: center; gap: 5px; padding: 7px 13px; background: #b10058; color: #fff; border-radius: 3px; text-decoration: none; font-size: 14px; font-weight: 500; white-space: nowrap; }
        .society-topbar .menu-buttons a:hover { opacity: 0.85; }

        .has-society-topbar .app-wrapper { margin-top: var(--topbar-h, 92px); }
        .has-society-topbar .sidebar { top: var(--topbar-h, 92px); }

        .app-wrapper { display: flex; margin-top: 52px; min-height: calc(100vh - 52px); }

        .sidebar { width: 240px; background: #34495e; color: #ecf0f1; position: fixed; top: 52px; bottom: 0; overflow-y: auto; padding-top: 8px; }
        .sidebar .menu-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #95a5a6; padding: 12px 16px 4px; }
        .sidebar .menu-label.menu-toggle { font-size: 16px; text-transform: capitalize; letter-spacing: normal; color: #bdc3c7; padding: 11px 16px; font-weight: 400; }
        .sidebar a i.fa, .sidebar .menu-toggle i.fa { width: 20px; margin-right: 8px; text-align: center; font-size: 15px; color: #95a5a6; }
        .sidebar a.active i.fa { color: #fff; }
        .sidebar .menu-toggle > span:first-child { display: flex; align-items: center; }
        .sidebar a { display: block; padding: 11px 16px; color: #bdc3c7; text-decoration: none; font-size: 16px; text-transform: capitalize; transition: background 0.15s; }
        .sidebar a:hover { background: #2c3e50; color: #fff; }
        .sidebar a.active { background: #2c3e50; color: #fff; border-left: 3px solid #3498db; padding-left: 13px; }
        .sidebar .submenu { display: none; overflow: hidden; }
        .sidebar .submenu.submenu-open { display: block; }
        .sidebar .submenu a { padding-left: 32px; }
        .sidebar .menu-toggle { cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; }
        .sidebar .menu-toggle:hover { color: #ecf0f1; background: rgba(0,0,0,0.1); }
        .sidebar .menu-toggle .caret-icon { font-size: 10px; transition: transform 0.2s; pointer-events: none; }
        .sidebar .menu-toggle.open .caret-icon { transform: rotate(90deg); }

        .main-content { flex: 1; margin-left: 240px; padding: 24px; max-width: calc(100% - 240px); }
        .no-sidebar .main-content { margin-left: 0; max-width: 100%; }
        .no-sidebar .sidebar { display: none; }

        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; font-size: 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .alert-info { background: #b10058; color: #fff; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-close { cursor: pointer; font-size: 18px; line-height: 1; opacity: 0.8; }
        .alert-close:hover { opacity: 1; }
        .card { background: #fff; border-radius: 6px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card h3 { font-size: 14px; color: #666; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .card .value { font-size: 28px; font-weight: 700; color: #2c3e50; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table th { background: #f8f9fa; padding: 8px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        table td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .fy-info { background: #e8f4fd; padding: 8px 16px; border-radius: 4px; font-size: 13px; color: #1a5276; margin-bottom: 16px; }

        .btn { display: inline-block; padding: 8px 16px; border-radius: 4px; font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: background 0.15s; }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: #fff; }
        .btn-success:hover { background: #219a52; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 4px; }
        .form-group label .required { color: #e74c3c; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-control:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 2px rgba(52,152,219,0.2); }
        select.form-control { appearance: auto; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-header h2 { font-size: 20px; color: #2c3e50; }

        .text-danger { color: #e74c3c; }
        .text-muted { color: #999; }

        .modal-overlay { position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:1000; display:flex; align-items:flex-start; justify-content:center; padding-top:40px; overflow-y:auto; }
        .modal-box { background:#fff; border-radius:6px; width:100%; margin:0 16px 40px; box-shadow:0 4px 20px rgba(0,0,0,0.3); }
        .modal-header-bar { background:#dc0030; color:#fff; padding:10px 16px; font-size:16px; font-weight:600; display:flex; justify-content:space-between; align-items:center; border-radius:6px 6px 0 0; }
        .modal-close { cursor:pointer; font-size:22px; font-weight:bold; line-height:1; }
        .modal-close:hover { opacity:0.7; }
        .modal-body-content { padding:16px; background:#f8f8f8; border:1px solid #f8f8f8; margin:15px; }

        /* Bootstrap-compatible grid */
        .row { margin-left:-15px; margin-right:-15px; }
        .row::before, .row::after { display:table; content:" "; }
        .row::after { clear:both; }
        .col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11,.col-md-12,
        .col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11,.col-sm-12 {
            position:relative; min-height:1px; padding-left:15px; padding-right:15px; float:left;
        }
        .col-sm-1{width:8.333%}.col-sm-2{width:16.666%}.col-sm-3{width:25%}.col-sm-4{width:33.333%}.col-sm-5{width:41.666%}.col-sm-6{width:50%}
        .col-sm-7{width:58.333%}.col-sm-8{width:66.666%}.col-sm-9{width:75%}.col-sm-10{width:83.333%}.col-sm-11{width:91.666%}.col-sm-12{width:100%}
        .col-md-1{width:8.333%}.col-md-2{width:16.666%}.col-md-3{width:25%}.col-md-4{width:33.333%}.col-md-5{width:41.666%}.col-md-6{width:50%}
        .col-md-7{width:58.333%}.col-md-8{width:66.666%}.col-md-9{width:75%}.col-md-10{width:83.333%}.col-md-11{width:91.666%}.col-md-12{width:100%}
        .clearfix::before,.clearfix::after { display:table; content:" "; }
        .clearfix::after { clear:both; }
        .text-right { text-align:right; }

        /* Bootstrap-compatible tabs */
        .nav-tabs { list-style:none; padding:0; margin:0; border-bottom:2px solid #ddd; display:flex; }
        .nav-tabs > li { margin-bottom:-2px; }
        .nav-tabs > li > a { display:block; padding:8px 15px; text-decoration:none; color:#333; border:1px solid #ddd; border-radius:4px 4px 0 0; font-size:13px; cursor:pointer; background:#f8f8f8; }
        .nav-tabs > li.active > a, .nav-tabs > li > a.active { color:#333; border-color:#ddd #ddd #fff; background:#fff !important; font-weight:600; }
        .nav-tabs > li > a:hover { background:#fff; color:#333; }
        .tab-content { padding:10px 0; background:#fff; }
        .tab-content > .tab-pane { display:none; }
        .tab-content > .tab-pane.active { display:block; }

        /* Table variants */
        .table { width:100%; border-collapse:collapse; font-size:13px; }
        .table th, .table td { padding:5px 8px; color:#333; }
        .table th { padding:8px; background:#f8f9fa; }
        .table-bordered { border:1px solid #ddd; }
        .table-bordered th, .table-bordered td { border:1px solid #ddd !important; }
        .table-striped tbody tr:nth-child(odd) { background:#f9f9f9; }
        .table-hover tbody tr:hover { background:#f0f0f0; }
        .table-responsive { overflow-x:auto; }

        .btn-warning { background:#f0ad4e; color:#333; }
        .btn-warning:hover { background:#ec971f; }

        .control-label { font-size:13px; font-weight:600; color:#555; margin-bottom:4px; }
        .input-height-30 { height:30px; padding:4px 8px; }

        /* Chat widget */
        #chat-toggle-btn { position:fixed; bottom:24px; right:24px; width:56px; height:56px; border-radius:50%; background:#2c3e50; color:#fff; border:none; box-shadow:0 4px 12px rgba(0,0,0,0.3); cursor:pointer; font-size:24px; z-index:500; display:flex; align-items:center; justify-content:center; }
        #chat-toggle-btn:hover { background:#34495e; }
        #chat-window { position:fixed; bottom:92px; right:24px; width:340px; max-width:calc(100vw - 32px); height:460px; max-height:calc(100vh - 120px); background:#fff; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,0.3); display:none; flex-direction:column; z-index:500; overflow:hidden; }
        #chat-window.open { display:flex; }
        #chat-header { background:#2c3e50; color:#fff; padding:12px 16px; font-weight:600; display:flex; justify-content:space-between; align-items:center; font-size:14px; }
        #chat-header .chat-close { cursor:pointer; font-size:18px; line-height:1; }
        #chat-messages { flex:1; overflow-y:auto; padding:12px; background:#f8f9fa; }
        .chat-msg { margin-bottom:10px; font-size:13px; line-height:1.4; max-width:85%; padding:8px 12px; border-radius:10px; white-space:pre-wrap; }
        .chat-msg.user { background:#2c3e50; color:#fff; margin-left:auto; border-bottom-right-radius:2px; }
        .chat-msg.assistant { background:#e9ecef; color:#333; margin-right:auto; border-bottom-left-radius:2px; }
        .chat-msg.system { background:transparent; color:#999; font-size:12px; text-align:center; max-width:100%; }
        #chat-input-row { display:flex; border-top:1px solid #eee; padding:8px; gap:6px; }
        #chat-input-row input { flex:1; border:1px solid #ddd; border-radius:16px; padding:8px 14px; font-size:13px; }
        #chat-input-row button { background:#2c3e50; color:#fff; border:none; border-radius:16px; padding:8px 16px; font-size:13px; cursor:pointer; }
        #chat-input-row button:disabled { opacity:0.6; cursor:default; }

        /* Admin theme: matches the CakePHP admin panel's white/red color scheme */
        .admin-theme .navbar { background: #fff; color: #212121; border-bottom: 1px solid #eee; }
        .admin-theme .navbar h1 { color: #212121; }
        .admin-theme .navbar .role-badge { background: #f1f1f1; color: #555; }
        .admin-theme .navbar form button { background: #fff; border: 1px solid #ddd; color: #555; }
        .admin-theme .navbar form button:hover { background: #f5f5f5; }
        .admin-theme .sidebar { background: #fff; color: #212121; border-right: 1px solid #eee; }
        .admin-theme .sidebar .menu-label { color: #dc0030; }
        .admin-theme .sidebar .menu-label.menu-toggle { color: #212121; }
        .admin-theme .sidebar a, .admin-theme .sidebar .menu-toggle { color: #212121; }
        .admin-theme .sidebar a i.fa, .admin-theme .sidebar .menu-toggle i.fa { color: #878787; }
        .admin-theme .sidebar a:hover, .admin-theme .sidebar .menu-toggle:hover { background: rgba(33,33,33,0.05); color: #212121; }
        .admin-theme .sidebar a.active { background: #dc0030; color: #fff; border-left: none; padding-left: 16px; }
        .admin-theme .sidebar a.active i.fa { color: #fff; }

        /* Admin dashboard stat cards, colored to match the CakePHP dashboard */
        .stat-cards-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat-card { border-radius: 4px; padding: 16px 18px; color: #fff; min-height: 110px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
        .stat-card-red { background: #dc0030; }
        .stat-card-yellow { background: #f2b701; }
        .stat-card-green { background: #09a275; }
        .stat-card-blue { background: #0f4fa8; }
        .stat-card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-card-value { font-size: 28px; font-weight: 700; line-height: 1; }
        .stat-card-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; font-weight: 500; }
        .stat-card-icon { font-size: 42px; opacity: 0.85; }
        .stat-card-sub { margin-top: 14px; font-size: 13px; }
        .stat-card-sub strong { font-weight: 700; }
    </style>
</head>
@php
    $isSocietyRole = Auth::check() && Auth::user()->role === 'Society';
    $isAdminRole = Auth::check() && Auth::user()->role === 'Admin';
    $showChatWidget = Auth::check() && in_array(Auth::user()->role, ['Society', 'Reseller', 'SubReseller'], true);
@endphp
<body class="{{ $isSocietyRole ? 'has-society-topbar' : '' }} {{ $isAdminRole ? 'admin-theme' : '' }}">
    <nav class="navbar">
        <h1><img src="{{ asset('images/fvcon.png') }}" alt="" style="height:32px; vertical-align:middle; margin-right:8px;">Easy Logics</h1>
        @auth
        <div class="user-info">
            @if($isSocietyRole)
                @php
                    $__socId = Auth::id();
                    $__society = \App\Models\Society::where('user_id', $__socId)->first();
                    $__assignedYearIds = \App\Models\SocietyYearMapping::where('society_id', $__socId)->where('is_active', 1)->pluck('year_id')->toArray();
                    $__assignedYears = !empty($__assignedYearIds) ? \App\Models\FinancialYearMaster::whereIn('id', $__assignedYearIds)->where('is_active', 1)->orderBy('year_start_date')->get() : collect([]);
                    $__curYearId = session('fy.year_id');
                @endphp
                @if($__assignedYears->count() > 1)
                <select onchange="if(this.value){window.location.href='{{ url('society/change-year') }}/'+this.value;}" style="padding:4px 8px; border:1px solid rgba(255,255,255,0.4); border-radius:4px; font-weight:600; font-size:13px; cursor:pointer; background:rgba(255,255,255,0.15); color:#fff;">
                    @foreach($__assignedYears as $__yr)
                    <option value="{{ $__yr->id }}" style="color:#000;" {{ $__curYearId == $__yr->id ? 'selected' : '' }}>{{ $__yr->year }}</option>
                    @endforeach
                </select>
                @elseif($__assignedYears->count() == 1)
                <span style="font-weight:600;">{{ $__assignedYears->first()->year }}</span>
                @endif
                @if(session('reseller_id'))
                <form method="POST" action="{{ route('reseller.switchBack') }}" style="display:inline;">
                    @csrf
                    <button type="submit" style="background:#e67e22; border-color:#e67e22;">&#8634; Switch Society</button>
                </form>
                @endif
            @endif
            <span>{{ Auth::user()->username }}</span>
            <span class="role-badge">{{ Auth::user()->role }}</span>
            @if(session('reseller_id') && !$isSocietyRole)
                <form method="POST" action="{{ route('reseller.switchBack') }}">
                    @csrf
                    <button type="submit" style="background:#e67e22; border-color:#e67e22;">Back to Reseller</button>
                </form>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
        @endauth
    </nav>


    @if($isSocietyRole)
    <div class="society-topbar">
        <div class="menu-buttons">
            @if(\App\Support\SocietyMenuAccess::topBar('tb_ledgerheads')) <a href="javascript:void(0);" onclick="openModal('ledgerHeadsModal')">&#9745; Ledger Heads</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_flatshop')) <a href="javascript:void(0);" onclick="openFlatShopModal();">&#9776; Flat / Shop Detail</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_bill')) <a href="javascript:void(0);" onclick="openModal('billModal')">&#128196; Bill</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_memberreceipts')) <a href="{{ route('society.memberReceipt') }}">&#128203; Member Receipts</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_paymententry')) <a href="javascript:void(0);" onclick="openModal('paymentEntryModal')">&#8377; Payment Entry</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_generatebill')) <a href="javascript:void(0);" onclick="openModal('generateBillModal')">&#128220; Generate Bill</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_printbill')) <a href="javascript:void(0);" onclick="openModal('printBillModal')">&#128424; Print Bill</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_emailbill')) <a href="javascript:void(0);" onclick="openModal('emailBillModal')">&#9993; Email Bill</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_whatsappbill')) <a href="#" style="background:#25D366;">&#128172; Send WhatsApp</a> @endif
            @if(\App\Support\SocietyMenuAccess::topBar('tb_sendsms')) <a href="#" style="background:#3498db;">&#128233; Send SMS</a> @endif
        </div>
    </div>
    @endif

    <div class="app-wrapper @if(!Auth::check() || !in_array(Auth::user()->role, ['Admin','Society','Reseller','SubReseller','Member'])) no-sidebar @endif">
        @auth
            @include('layouts.sidebar')
        @endauth

        <div class="main-content">
            @if(session('info'))
                <div class="alert alert-info"><span>{{ session('info') }}</span><span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><span>{{ session('error') }}</span><span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span></div>
            @endif
            @if(session('success'))
                <div class="alert alert-success"><span>{{ session('success') }}</span><span class="alert-close" onclick="this.parentElement.style.display='none'">&times;</span></div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    @foreach($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </div>
    </div>
@if($isSocietyRole)
    @include('layouts.modals.ledger-heads')
    @include('layouts.modals.flat-shop-details')
    @include('layouts.modals.bill-details')
    @include('layouts.modals.member-receipt')
    @include('layouts.modals.payment-entry')
    @include('layouts.modals.generate-bill')
    @include('layouts.modals.print-bill')
    @include('layouts.modals.email-bill')
@endif

<script>
(function() {
    var toggles = document.querySelectorAll('.sidebar .menu-toggle');
    for (var i = 0; i < toggles.length; i++) {
        toggles[i].onclick = function() {
            this.classList.toggle('open');
            var next = this.nextElementSibling;
            if (next) {
                if (next.style.display === 'block') {
                    next.style.display = 'none';
                } else {
                    next.style.display = 'block';
                }
            }
        };
    }
})();

function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) { e.target.style.display = 'none'; }
});

var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
var memberPaymentEditUrlBase = "{{ rtrim(route('society.addMemberPayment', ''), '/') }}";

// The society-topbar wraps its buttons onto more rows as they don't fit the
// width available, so its height isn't fixed - a hardcoded 92px left a gap
// (or an overlap) between it and the sidebar/main content whenever it wrapped
// to more than one row. Measure it for real instead.
function syncTopbarHeight() {
    var navbar = document.querySelector('.navbar');
    var topbar = document.querySelector('.society-topbar');
    if (!navbar || !topbar) return;
    var total = navbar.offsetHeight + topbar.offsetHeight;
    document.documentElement.style.setProperty('--topbar-h', total + 'px');
}
syncTopbarHeight();
window.addEventListener('load', syncTopbarHeight);
window.addEventListener('resize', syncTopbarHeight);

function loadWings(buildingId, selectId) {
    var sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">Select Wing</option>';
    if (!buildingId) return;
    fetch('/society/get-wings-by-building?building_id=' + buildingId, {headers:{'X-CSRF-TOKEN': csrfToken}})
        .then(function(r) { return r.json(); })
        .then(function(wings) {
            for (var wId in wings) {
                var opt = document.createElement('option');
                opt.value = wId; opt.textContent = wings[wId];
                sel.appendChild(opt);
            }
        });
}

function generateRegularBill() {
    var msg = document.getElementById('gb_message');
    msg.innerHTML = '<span style="color:#999;">Generating...</span>';
    fetch('/society/generate-bill', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify({
            building_id: document.getElementById('gb_building_id').value,
            wing_id: document.getElementById('gb_wing_id').value,
            month: document.getElementById('gb_month').value,
            year_id: document.getElementById('gb_year').value,
            bill_date: document.getElementById('gb_bill_date').value,
            due_date: document.getElementById('gb_due_date').value,
            starting_no: document.getElementById('gb_starting_no').value,
            supplementary: document.getElementById('gb_supplementary').checked ? 1 : 0
        })
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error === 0) { msg.innerHTML = '<span style="color:green;">' + (d.error_message || d.message) + '</span>'; }
        else { msg.innerHTML = '<span style="color:red;">' + (d.error_message || d.message) + '</span>'; }
    }).catch(function() { msg.innerHTML = '<span style="color:red;">Error occurred</span>'; });
}

function loadLedgerHeadDetails(ledgerId) {
    if (!ledgerId) return;
    fetch('/society/get-ledger-head-details?ledger_head_id=' + ledgerId, {headers:{'X-CSRF-TOKEN': csrfToken}})
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('lh_sr').value = d.id || '';
            document.getElementById('lh_short_code').value = d.short_code || '';
            document.getElementById('lh_opening_balance').value = d.opening_amount || 0;
            document.getElementById('lh_filter_amount').value = d.opening_amount || 0;
            if (d.society_head_sub_category_id) document.getElementById('lh_subgroup').value = d.society_head_sub_category_id;
            if (d.account_category_id) {
                document.getElementById('lh_category').value = d.account_category_id;
                loadAccountHeads(d.account_category_id, d.account_head_id);
            }
            document.getElementById('lh_is_bill_charges').checked = d.is_in_bill_charges == 1;
            document.getElementById('lh_is_tax_applicable').checked = d.is_tax_applicable == 1;
            document.getElementById('lh_is_rebate_applicable').checked = d.is_rebate_applicable == 1;
            document.getElementById('lh_is_interest_free').checked = d.is_interest_free == 1;
            var tbody = document.getElementById('lh_ledger_table');
            tbody.innerHTML = '';
            if (d.ledger_entries) {
                var balance = 0;
                var opRow = '<tr style="background:#d4edda;"><td>' + (d.fy_start || '') + '</td><td><a href="#" style="color:#e67e22;">Opening Balance</a></td><td style="text-align:right;">' + (parseFloat(d.opening_amount) > 0 ? d.opening_amount : 0) + '</td><td style="text-align:right;">' + (parseFloat(d.opening_amount) < 0 ? Math.abs(d.opening_amount) : 0) + '</td><td style="text-align:right;">' + d.opening_amount + ' Dr.</td></tr>';
                tbody.innerHTML = opRow;
                balance = parseFloat(d.opening_amount) || 0;
                for (var i = 0; i < d.ledger_entries.length; i++) {
                    var e = d.ledger_entries[i];
                    balance += (parseFloat(e.dr_amount) || 0) - (parseFloat(e.cr_amount) || 0);
                    var suffix = balance >= 0 ? ' Dr' : ' Cr';
                    tbody.innerHTML += '<tr><td>' + e.date + '</td><td>' + e.particular + '</td><td style="text-align:right;">' + (e.dr_amount || '0.00') + '</td><td style="text-align:right;">' + (e.cr_amount || '0.00') + '</td><td style="text-align:right;">' + Math.abs(balance).toFixed(2) + suffix + '</td></tr>';
                }
            }
        });
}

function loadAccountHeads(categoryId, selectedId) {
    var sel = document.getElementById('lh_group');
    sel.innerHTML = '<option value="">Select</option>';
    if (!categoryId) return;
    fetch('/society/get-account-heads?account_category_id=' + categoryId, {headers:{'X-CSRF-TOKEN': csrfToken}})
        .then(function(r) { return r.json(); })
        .then(function(heads) {
            for (var hId in heads) {
                var opt = document.createElement('option');
                opt.value = hId; opt.textContent = heads[hId];
                if (selectedId && hId == selectedId) opt.selected = true;
                sel.appendChild(opt);
            }
        });
}

function updateLedgerHead() {
    var ledgerId = document.getElementById('lh_select').value;
    if (!ledgerId) { alert('Select a ledger head first'); return; }
    fetch('/society/update-ledger-head', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify({
            id: ledgerId,
            short_code: document.getElementById('lh_short_code').value,
            opening_amount: document.getElementById('lh_opening_balance').value,
            society_head_sub_category_id: document.getElementById('lh_subgroup').value,
            account_head_id: document.getElementById('lh_group').value,
            account_category_id: document.getElementById('lh_category').value,
            is_in_bill_charges: document.getElementById('lh_is_bill_charges').checked ? 1 : 0,
            is_tax_applicable: document.getElementById('lh_is_tax_applicable').checked ? 1 : 0,
            is_rebate_applicable: document.getElementById('lh_is_rebate_applicable').checked ? 1 : 0,
            is_interest_free: document.getElementById('lh_is_interest_free').checked ? 1 : 0
        })
    }).then(function(r) { return r.json(); })
    .then(function(d) { alert(d.message || 'Updated'); });
}

function printLedgerHead() {
    var ledgerId = document.getElementById('lh_select').value;
    if (!ledgerId) return;
    window.open('/society/ledger-heads?print=1&id=' + ledgerId, '_blank');
}

function openFlatShopModal() {
    document.getElementById('flat_shop_details').style.display = 'flex';
    var mid = document.getElementById('society_member_id').value;
    if (!mid || mid == '0') {
        var firstOpt = document.querySelector('#fs_unit_options .fs-unit-option');
        if (firstOpt) {
            var flatNo = firstOpt.getAttribute('data-value');
            var memberId = firstOpt.getAttribute('data-member-id');
            if (typeof fsUpdateSelectedUnit === 'function') fsUpdateSelectedUnit(flatNo);
            if (memberId) {
                getSocietyMemberDetails(memberId);
            } else {
                memberunitno();
            }
        }
    }
}

function getSocietyMemberDetails(societyMemberId) {
    if (!societyMemberId) return;
    fetch('/society/get-member-details', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: 'member_id=' + societyMemberId
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (!d.Member || d.Member.status == 0) return;
        var m = d.Member;
        document.getElementById('society_member_id').value = m.id;
        document.getElementById('society_member_name').value = m.member_name || '';
        document.getElementById('member_serial_no').value = m.id;
        document.getElementById('society_member_previous').value = m.previous_id || 0;
        document.getElementById('society_member_next').value = m.next_id || 0;

        document.getElementById('society_member_previous_button').style.display = m.previous == 1 ? 'none' : '';
        document.getElementById('society_member_next_button').style.display = m.next == 1 ? 'none' : '';

        if (m.building_id) document.getElementById('member_building_id').value = m.building_id;
        if (m.wing_id) document.getElementById('member_wing_id').value = m.wing_id;
        document.getElementById('member_floor_no').value = m.floor_no || 0;

        var unitType = m.unit_type || '';
        if (unitType == 'C') unitType = 'Commercial';
        else if (unitType == 'R') unitType = 'Residential';
        else if (unitType == 'B') unitType = 'Both';
        document.getElementById('member_unit_type').value = unitType;

        document.getElementById('member_flat_area').value = m.area || 0;
        document.getElementById('member_carpet').value = m.carpet || 0;
        document.getElementById('member_commercial').value = m.commercial || 0;
        document.getElementById('member_residential').value = m.residential || 0;
        document.getElementById('member_terrace').value = m.terrace || 0;
        document.getElementById('member_principal').value = m.op_principal || 0;
        document.getElementById('member_interest').value = m.op_interest || 0;
        document.getElementById('member_tax').value = m.op_tax || 0;
        document.getElementById('member_sup_principal').value = m.supplementary_principal || 0;
        document.getElementById('member_sup_interest').value = m.supplementary_interest || 0;
        document.getElementById('member_sup_tax').value = m.supplementary_tax || 0;

        if (m.flat_no && typeof fsUpdateSelectedUnit === 'function') {
            fsUpdateSelectedUnit(m.flat_no);
        }

        if (d.Building) document.getElementById('print_header_section_bulding_name').textContent = d.Building.building_name || '';
        if (d.Wing) document.getElementById('print_header_section_bulding_wing').textContent = d.Wing.wing_name || '';
        document.getElementById('print_header_section_member_name').textContent = m.member_name || '';
        document.getElementById('print_header_section_unit_no').textContent = m.flat_no || '';
        if (d.Society) {
            document.getElementById('fs_society_name').textContent = d.Society.society_name || '';
            document.getElementById('fs_society_reg_no').textContent = d.Society.registration_no || '';
            document.getElementById('fs_society_address').textContent = d.Society.address || '';
        }

        var op_principal = parseFloat(m.op_principal) || 0;
        var op_interest = parseFloat(m.op_interest) || 0;
        var op_tax = parseFloat(m.op_tax) || 0;
        var op_balance = op_principal + op_interest + op_tax;
        var indBalance = op_balance;
        var openingCr = 0, openingDr = 0;
        var monthlyTotalAmount = 0, paymentTotalAmount = 0;
        var posNeg;

        if (op_balance < 0) {
            openingDr = Math.abs(op_balance);
            posNeg = 'Cr';
            paymentTotalAmount = op_balance;
        } else {
            openingCr = Math.abs(op_balance);
            posNeg = 'Dr';
            monthlyTotalAmount = op_balance;
        }

        var tbody = document.getElementById('flat_details');
        tbody.innerHTML = '<tr>' +
            '<td><a href="javascript:void(0);">' + (d.openingdate || '') + '</a></td>' +
            '<td class="opening-bal">Opening Balance</td>' +
            '<td class="text-right total_debit">' + openingCr.toFixed(2) + '</td>' +
            '<td class="text-right total_credit">' + openingDr.toFixed(2) + '</td>' +
            '<td class="text-right">' + Math.abs(op_balance).toFixed(2) + ' ' + posNeg + '</td>' +
            '</tr>';

        if (d.memberBillPayment && d.memberBillPayment.length > 0) {
            for (var i = 0; i < d.memberBillPayment.length; i++) {
                var entry = d.memberBillPayment[i];
                var debit = parseFloat(entry.debit) || 0;
                var credit = parseFloat(entry.credit) || 0;

                if (entry.flag === 'bill' || entry.flag === 'jv' && debit > 0) {
                    monthlyTotalAmount += debit;
                    indBalance += debit;
                } else if (entry.flag === '' && debit > 0) {
                    monthlyTotalAmount += debit;
                    indBalance += debit;
                }

                if (credit > 0) {
                    paymentTotalAmount += credit;
                    indBalance -= credit;
                }

                posNeg = indBalance < 0 ? 'Cr' : 'Dr';

                var particularHtml = entry.particular || '';
                if (entry.flag === 'bill') {
                    particularHtml = '<a href="javascript:void(0);" onclick="loadBillModalFromFlatShop(' + entry.bill_id + ',' + entry.month + ');">' + particularHtml + '</a>';
                } else if (entry.flag === 'receipt' && entry.payment_id) {
                    // Same place the CakePHP app's cheque-return flow lives: opens the
                    // payment's own edit page, which carries the Cheque Return Date/
                    // Reason fields for marking that cheque as bounced.
                    particularHtml = '<a href="' + memberPaymentEditUrlBase + '/' + entry.payment_id + '" title="Edit payment / mark cheque returned">' + particularHtml + '</a>';
                }

                tbody.innerHTML += '<tr>' +
                    '<td>' + (entry.formattedDate || '') + '</td>' +
                    '<td>' + particularHtml + '</td>' +
                    '<td class="text-right">' + debit.toFixed(2) + '</td>' +
                    '<td class="text-right">' + credit.toFixed(2) + '</td>' +
                    '<td class="text-right">' + Math.abs(indBalance).toFixed(2) + ' ' + posNeg + '</td>' +
                    '</tr>';
            }
        }

        var totalBal = monthlyTotalAmount - Math.abs(paymentTotalAmount);
        posNeg = totalBal < 0 ? 'Cr' : 'Dr';
        tbody.innerHTML += '<tr style="font-weight:bold;">' +
            '<td colspan="2" class="text-right">Total</td>' +
            '<td class="text-right">' + monthlyTotalAmount.toFixed(2) + '</td>' +
            '<td class="text-right">' + Math.abs(paymentTotalAmount).toFixed(2) + '</td>' +
            '<td class="text-right" style="font-weight:bold;">' + Math.abs(totalBal).toFixed(2) + ' ' + posNeg + '</td>' +
            '</tr>';

        document.getElementById('old_ledger_name').textContent = '';
        document.getElementById('old_flat_details').innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';
        switchFsTab('ledger', document.querySelector('#flatShopTabs li:first-child a'));
    });
}

function societyMemberNext() {
    var nextId = document.getElementById('society_member_next').value;
    if (nextId && nextId != '0') {
        getSocietyMemberDetails(nextId);
    }
}

function societyMemberPrevious() {
    var prevId = document.getElementById('society_member_previous').value;
    if (prevId && prevId != '0') {
        getSocietyMemberDetails(prevId);
    }
}

var _memberUnitBusy = false;
function memberunitno() {
    if (_memberUnitBusy) return;
    _memberUnitBusy = true;

    var flat_no = document.getElementById('member_unit_no').value;
    var buildingId = document.getElementById('member_building_id').value;
    var wingId = document.getElementById('member_wing_id').value;

    if (!flat_no) { _memberUnitBusy = false; return; }

    fetch('/society/get-member-by-flat-no', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: 'flat_no=' + encodeURIComponent(flat_no) + '&building_id=' + buildingId + '&wing_id=' + wingId
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        _memberUnitBusy = false;
        if (d.id && d.id != 0) {
            getSocietyMemberDetails(d.id);
        } else {
            alert('This flat no does not exist!');
        }
    })
    .catch(function() { _memberUnitBusy = false; });
}

function switchFsTab(tabId, clickedEl) {
    var panes = document.querySelectorAll('#flat_shop_details .tab-pane');
    for (var i = 0; i < panes.length; i++) panes[i].classList.remove('active');
    document.getElementById(tabId).classList.add('active');
    var links = document.querySelectorAll('#flatShopTabs a');
    for (var i = 0; i < links.length; i++) links[i].classList.remove('active');
    if (clickedEl) clickedEl.classList.add('active');
}

function loadOldMemberLedger() {
    var memberId = document.getElementById('society_member_id').value;
    if (!memberId) return;
    document.getElementById('old_flat_details').innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';
    fetch('/society/get-member-details', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: 'member_id=' + memberId + '&oldMember=1'
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) { renderOldMemberLedger(resp); })
    .catch(function() { document.getElementById('old_flat_details').innerHTML = '<tr><td colspan="5" style="text-align:center;">Could not load old ledger.</td></tr>'; });
}

function renderOldMemberLedger(resp) {
    var tb = document.getElementById('old_flat_details');
    tb.innerHTML = '';
    if (!resp || !resp.Member || resp.Member.status == 0) {
        tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">No previous owner for this unit.</td></tr>';
        return;
    }
    document.getElementById('old_ledger_name').textContent = resp.Member.member_name ? '- ' + resp.Member.member_name : '';
    var f = function(x) { x = parseFloat(x); return isNaN(x) ? 0 : x; };
    var opBal = f(resp.Member.op_principal) + f(resp.Member.op_interest) + f(resp.Member.op_tax)
              + f(resp.Member.supplementary_principal) + f(resp.Member.supplementary_interest) + f(resp.Member.supplementary_tax);
    var openingCr = 0, openingDr = 0;
    if (opBal < 0) { openingDr = Math.abs(opBal); } else { openingCr = Math.abs(opBal); }
    var indBalance = opBal;
    var np = function(v) { return v < 0 ? 'Cr' : 'Dr'; };
    tb.innerHTML += '<tr><td>' + (resp.openingdate || '') + '</td><td class="opening-bal">Opening Balance</td><td class="text-right old_dr">' + openingCr.toFixed(2) + '</td><td class="text-right old_cr">' + openingDr.toFixed(2) + '</td><td class="text-right">' + Math.abs(opBal).toFixed(2) + ' ' + np(opBal) + '</td></tr>';
    if (resp.memberBillPayment && resp.memberBillPayment.length > 0) {
        for (var i = 0; i < resp.memberBillPayment.length; i++) {
            var e = resp.memberBillPayment[i];
            indBalance = f(indBalance) + f(e.debit) - f(e.credit);
            tb.innerHTML += '<tr><td>' + (e.formattedDate || '') + '</td><td>' + (e.particular || '') + '</td><td class="text-right old_dr">' + f(e.debit).toFixed(2) + '</td><td class="text-right old_cr">' + f(e.credit).toFixed(2) + '</td><td class="text-right">' + Math.abs(indBalance).toFixed(2) + ' ' + np(indBalance) + '</td></tr>';
        }
    }
    var td = 0, tc = 0;
    var drCells = tb.querySelectorAll('.old_dr');
    var crCells = tb.querySelectorAll('.old_cr');
    for (var i = 0; i < drCells.length; i++) td += f(drCells[i].textContent);
    for (var i = 0; i < crCells.length; i++) tc += f(crCells[i].textContent);
    var totBal = td - tc;
    tb.innerHTML += '<tr style="font-weight:bold;"><td colspan="2" class="text-right">Total</td><td class="text-right">' + td.toFixed(2) + '</td><td class="text-right">' + tc.toFixed(2) + '</td><td class="text-right">' + Math.abs(totBal).toFixed(2) + ' ' + np(totBal) + '</td></tr>';
}

function printActiveLedger() {
    var activeTab = document.querySelector('#flat_shop_details .tab-pane.active');
    var printId = (activeTab && activeTab.id === 'old_ledger') ? 'print_old_member_ledger' : 'print_individual_member_ledger';
    var content = document.getElementById(printId);
    if (!content) return;
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>Print</title><style>body{font-family:Arial,sans-serif;font-size:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #999;padding:4px 6px;} th{background:#e9ecef;} .opening-bal{color:#0066cc;font-weight:500;} .text-right{text-align:right;} .text-center{text-align:center;} .societyinfo-section{display:block !important;text-align:center;margin-bottom:10px;overflow:hidden;} .report-bill{text-align:center;font-size:16px;font-weight:bold;color:#000;} .report-address-heading{text-align:center;font-size:13px;} .building-name{float:left;margin-right:20px;} .wing-name{float:left;margin-right:10px;} .member-name{float:left;margin-right:20px;} .flat-no{float:left;margin-right:10px;}</style></head><body>');
    w.document.write(content.innerHTML);
    w.document.write('</body></html>');
    w.document.close();
    w.print();
}

function updateOpBill() {
    var updateSup = document.getElementById('checkbox_supplementary').checked ? 'Y' : 'N';
    var memberId = document.getElementById('member_serial_no').value;
    var op_principal = document.getElementById('member_principal').value;
    var op_interest = document.getElementById('member_interest').value;
    var op_tax = document.getElementById('member_tax').value;
    var sup_op_principal = document.getElementById('member_sup_principal').value;
    var sup_op_interest = document.getElementById('member_sup_interest').value;
    var sup_op_tax = document.getElementById('member_sup_tax').value;

    var postString = 'memberId=' + memberId + '&op_principal=' + op_principal + '&op_interest=' + op_interest + '&op_tax=' + op_tax + '&sup_op_principal=' + sup_op_principal + '&sup_op_interest=' + sup_op_interest + '&sup_op_tax=' + sup_op_tax + '&updateSuplementary=' + updateSup;
    fetch('/society/update-opening-balance', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: postString
    })
    .then(function(r) { return r.text(); })
    .then(function() { location.reload(true); });
}

function getAllBuildingWings(buildingId) {
    if (!buildingId) return;
    fetch('/society/get-wings-by-building?building_id=' + buildingId, {headers:{'X-CSRF-TOKEN': csrfToken}})
    .then(function(r) { return r.json(); })
    .then(function(wings) {
        var sel = document.getElementById('member_wing_id');
        sel.innerHTML = '<option value="">Select Wing</option>';
        if (wings && wings.length) {
            for (var i = 0; i < wings.length; i++) {
                sel.innerHTML += '<option value="' + wings[i].id + '">' + wings[i].wing_name + '</option>';
            }
        }
    });
}

function getAllMembersBillSummaryDetails(postStr) {
    fetch('/society/get-all-members-bill-summary-details', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: postStr
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp.error === 0) {
            var totalAmt = 0;
            var monthlyBillAmount = 0;
            var totalarrears = 0;

            document.getElementById('society_member_summary_id').value = resp.MemberBillSummary.id;
            document.getElementById('bill_id').value = resp.MemberBillSummary.id;
            document.getElementById('society_member_bill_previous').value = resp.MemberBillSummary.previous_id;
            document.getElementById('society_member_bill_next').value = resp.MemberBillSummary.next_id;
            document.getElementById('society_member_bill_previous_month').value = resp.MemberBillSummary.previous_month;
            document.getElementById('society_member_bill_next_month').value = resp.MemberBillSummary.next_month;

            document.getElementById('society_member_bill_previous_button').style.display = resp.MemberBillSummary.previous == 1 ? '' : 'none';
            document.getElementById('society_member_bill_next_button').style.display = resp.MemberBillSummary.next == 1 ? '' : 'none';

            var delBtn = document.getElementById('delMemSum');
            if (delBtn) {
                delBtn.setAttribute('ifpayment', resp.ifSettlement);
                delBtn.style.display = resp.ifDelete == 1 ? '' : 'none';
            }

            // Ported from Cake's getAllMembersBillSummaryDetails success handler
            // (society_bills.js): oldUpdateAvailable only when the Current Bill Update
            // parameter has never been configured (NULL); currentBillUpdateEnabled only
            // when it's explicitly Yes, and then only the member's actual latest bill
            // (isCurrentBill) gets the button - any other bill shows the locked label.
            var updateBtn = document.getElementById('updateMemSum');
            var manualBtn = document.getElementById('updateManualSum');
            var currentBtn = document.getElementById('currentBillUpdateMemSum');
            var lockedLabel = document.getElementById('historicalBillLockedLabel');
            if (updateBtn) updateBtn.style.display = resp.oldUpdateAvailable == 1 ? '' : 'none';
            if (resp.currentBillUpdateEnabled == 1 && resp.isCurrentBill == 1) {
                if (currentBtn) currentBtn.style.display = '';
                if (lockedLabel) lockedLabel.style.display = 'none';
                if (manualBtn) manualBtn.style.display = '';
            } else if (resp.currentBillUpdateEnabled == 1) {
                if (currentBtn) currentBtn.style.display = 'none';
                if (lockedLabel) lockedLabel.style.display = '';
                if (manualBtn) manualBtn.style.display = 'none';
            } else {
                if (currentBtn) currentBtn.style.display = 'none';
                if (lockedLabel) lockedLabel.style.display = 'none';
                if (manualBtn) manualBtn.style.display = resp.oldUpdateAvailable == 1 ? '' : 'none';
            }

            document.getElementById('bill_remarks').value = resp.MemberBillSummary.remarks || '';
            document.getElementById('member_bill_summary_month').value = resp.MemberBillSummary.month;
            document.getElementById('member_bill_summary_bill_no').value = resp.MemberBillSummary.bill_no;
            document.getElementById('member_bill_summary_bill_generated_date').value = resp.MemberBillSummary.bill_generated_date || '';
            document.getElementById('member_bill_summary_bill_due_date').value = resp.MemberBillSummary.bill_due_date || '';
            document.getElementById('member_bill_summary_member_id').value = resp.MemberBillSummary.member_id;
            document.getElementById('member_bill_summary_area').value = resp.Member.area || '';
            document.getElementById('member_bill_summary_bill_flat_no').value = resp.Member.flat_no || '';
            document.getElementById('member_bill_summary_building_id').value = resp.Member.building_id || '';
            document.getElementById('member_bill_summary_bill_wing_id').innerHTML = '';
            if (resp.Wing && resp.Wing.id) {
                var opt = document.createElement('option');
                opt.value = resp.Wing.id;
                opt.textContent = resp.Wing.wing_name || '';
                document.getElementById('member_bill_summary_bill_wing_id').appendChild(opt);
            }

            var tariffBody = document.getElementById('bill_member_tariff_details');
            tariffBody.innerHTML = '';
            if (resp.MemberTariff && resp.MemberTariff.length > 0) {
                for (var i = 0; i < resp.MemberTariff.length; i++) {
                    var item = resp.MemberTariff[i];
                    if (item.amount) {
                        totalAmt += parseFloat(item.amount);
                        tariffBody.innerHTML += '<tr><td>' + item.tariff_serial + '</td><td>' + item.title + '</td><td><input type="text" class="form-control text-right tariff-amount-input" data-ledger-head-id="' + item.ledger_head_id + '" value="' + parseFloat(item.amount).toFixed(2) + '"></td></tr>';
                    }
                }
                tariffBody.innerHTML += '<tr style="font-weight:bold;"><td></td><td>Total</td><td style="text-align:right;">' + totalAmt.toFixed(2) + '</td></tr>';
            }

            document.getElementById('member_bill_summary_bill_monthly_principal_amount').value = resp.MemberBillSummary.monthly_principal_amount || '';
            document.getElementById('member_bill_summary_bill_interest_on_due_amount').value = resp.MemberBillSummary.interest_on_due_amount || '';
            document.getElementById('member_bill_summary_monthly_principal_amount').value = resp.MemberBillSummary.monthly_amount || '';
            document.getElementById('member_bill_summary_interest_on_due_amount').value = resp.MemberBillSummary.interest_on_due_amount || '';
            document.getElementById('member_bill_summary_bill_discount').value = resp.MemberBillSummary.discount || '';
            document.getElementById('member_bill_summary_bill_principal_adjusted').value = resp.MemberBillSummary.principal_adjusted || '';
            document.getElementById('member_bill_summary_bill_interest_adjusted').value = resp.MemberBillSummary.interest_adjusted || '';

            monthlyBillAmount = (resp.MemberBillSummary.monthly_bill_amount * 1) + (resp.MemberBillSummary.tax_total * 1);
            totalarrears = (resp.MemberBillSummary.op_principal_arrears * 1) + (resp.MemberBillSummary.op_tax_arrears * 1) + (resp.MemberBillSummary.op_interest_arrears * 1);

            document.getElementById('member_bill_summary_bill_principal_arrears').value = (resp.MemberBillSummary.op_principal_arrears * 1);
            document.getElementById('member_bill_summary_bill_monthly_bill_amount').value = (resp.MemberBillSummary.monthly_bill_amount * 1);
            document.getElementById('member_bill_summary_bill_amount_payable').value = resp.MemberBillSummary.amount_payable;
            document.getElementById('member_bill_summary_bill_interest_balance').value = resp.MemberBillSummary.op_interest_arrears;
            document.getElementById('member_bill_summary_bill_op_total_errears').value = totalarrears;

            if (parseFloat(resp.MemberBillSummary.amount_payable) < 0) {
                document.getElementById('amtPayableExcess').textContent = 'Excess Received';
            } else {
                document.getElementById('amtPayableExcess').textContent = 'Amount Payable';
            }

            document.getElementById('bill_principle_paid').textContent = resp.MemberBillSummary.principal_paid || '0.00';
            document.getElementById('bill_principal_adjusted').textContent = resp.MemberBillSummary.principal_adjusted || '0.00';
            document.getElementById('bill_principal_bal').textContent = resp.MemberBillSummary.principal_balance || '0.00';
            document.getElementById('bill_interest_paid').textContent = resp.MemberBillSummary.interest_paid || '0.00';
            document.getElementById('bill_interest_adjusted').textContent = resp.MemberBillSummary.interest_adjusted || '0.00';
            document.getElementById('bill_interest_balance').textContent = resp.MemberBillSummary.interest_balance || '0.00';
            document.getElementById('bill_tax_paid').textContent = resp.MemberBillSummary.tax_paid || '0.00';
            document.getElementById('bill_tax_adjusted').textContent = resp.MemberBillSummary.tax_adjusted || '0.00';
            document.getElementById('bill_tax_balance').textContent = resp.MemberBillSummary.tax_balance || '0.00';
            document.getElementById('bill_balance_amount').textContent = resp.MemberBillSummary.balance_amount || '0.00';

            document.getElementById('member_bill_op_tax_arrears').value = resp.MemberBillSummary.op_tax_arrears || '0.00';
            document.getElementById('bill_igst_total').textContent = resp.MemberBillSummary.igst_total || '0.00';
            document.getElementById('bill_cgst_total').textContent = resp.MemberBillSummary.cgst_total || '0.00';
            document.getElementById('bill_sgst_total').textContent = resp.MemberBillSummary.sgst_total || '0.00';
            document.getElementById('bill_tax_total').textContent = resp.MemberBillSummary.tax_total || '0.00';

            var interestBody = document.getElementById('bill_interest_details');
            if (interestBody) {
                interestBody.innerHTML = '';
                if (resp.InterestDetails && resp.InterestDetails.interest_amount > 0) {
                    var id = resp.InterestDetails;
                    var fmtDate = function(d) { if (!d) return ''; var p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; };
                    interestBody.innerHTML = '<tr>' +
                        '<td>1</td>' +
                        '<td>Interest (' + (id.interest_type || '') + ' - ' + (id.interest_method || '') + ')</td>' +
                        '<td>' + fmtDate(id.due_date) + '</td>' +
                        '<td>' + fmtDate(id.bill_date) + '</td>' +
                        '<td>' + (id.cycle_days || id.delay_days || 0) + '</td>' +
                        '<td>' + (id.interest_rate || 0) + '%</td>' +
                        '<td style="text-align:right;">' + parseFloat(id.interest_amount || 0).toFixed(2) + '</td>' +
                        '</tr>' +
                        '<tr style="background:#f0f0f0;font-size:12px;">' +
                        '<td colspan="7">Principal Arrears: ' + parseFloat(id.principal_arrears || 0).toFixed(2) +
                        ' | Delay Days: ' + (id.delay_days || 0) +
                        ' | Cycle Days: ' + (id.cycle_days || 0) +
                        (id.prev_bill_date ? ' | Prev Bill: ' + fmtDate(id.prev_bill_date) : '') +
                        '</td></tr>';
                } else {
                    interestBody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No interest applied on this bill</td></tr>';
                }
            }
        } else {
            document.getElementById('society_member_bill_summary').reset();
            document.getElementById('bill_member_tariff_details').innerHTML = '';
            alert('Bill records not found.');
        }
    });
}

function loadBillModalFromFlatShop(billId, month) {
    openModal('billModal');
    getAllMembersBillSummaryDetails('id=' + billId + '&month=' + month);
}

function societyMemberBillPrevious() {
    var id = document.getElementById('society_member_bill_previous').value;
    var month = document.getElementById('society_member_bill_previous_month').value;
    if (id) {
        getAllMembersBillSummaryDetails('id=' + id + '&month=' + month);
    } else {
        alert('Select month');
    }
}

function societyMemberBillNext() {
    var id = document.getElementById('society_member_bill_next').value;
    var month = document.getElementById('society_member_bill_next_month').value;
    if (id) {
        getAllMembersBillSummaryDetails('id=' + id + '&month=' + month);
    } else {
        alert('Select month');
    }
}

// Ported from Cake's $('#society_member_bill_summary').serialize(), which carries every
// data[MemberTariff][ledger_head_id][amount] input in that form - the Particulars/Amount
// lines are the only thing meant to be hand-edited on a bill; Bill Amount, arrears and
// Amount Payable (readonly in the markup) are always derived from these plus the society's
// current parameters, never typed directly.
function collectTariffFieldsQueryString() {
    var qs = '&bill_no=' + encodeURIComponent(document.getElementById('member_bill_summary_bill_no').value || '')
        + '&month=' + encodeURIComponent(document.getElementById('member_bill_summary_month').value || '')
        + '&bill_generated_date=' + encodeURIComponent(document.getElementById('member_bill_summary_bill_generated_date').value || '');
    var inputs = document.querySelectorAll('.tariff-amount-input');
    for (var i = 0; i < inputs.length; i++) {
        qs += '&tariff[' + encodeURIComponent(inputs[i].getAttribute('data-ledger-head-id')) + ']=' + encodeURIComponent(inputs[i].value || 0);
    }
    return qs;
}

function updateMemberBillSummaryById() {
    var summaryId = document.getElementById('society_member_summary_id').value;
    if (!summaryId) { alert('No bill selected'); return; }
    var postStr = 'id=' + summaryId
        + '&discount=' + (document.getElementById('member_bill_summary_bill_discount').value || 0)
        + '&principal_adjusted=' + (document.getElementById('member_bill_summary_bill_principal_adjusted').value || 0)
        + '&interest_adjusted=' + (document.getElementById('member_bill_summary_bill_interest_adjusted').value || 0)
        + '&interest_on_due_amount=' + (document.getElementById('member_bill_summary_interest_on_due_amount').value || 0)
        + '&member_id=' + (document.getElementById('member_bill_summary_member_id').value || '')
        + collectTariffFieldsQueryString();
    fetch('/society/update-member-bill-summary', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: postStr
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp.error === 0) {
            alert('Bill updated successfully.');
            getAllMembersBillSummaryDetails('id=' + summaryId);
        } else {
            alert(resp.error_message || 'Could not update bill.');
        }
    })
    .catch(function() { alert('Network error. Please try again.'); });
}

function updateCurrentMemberBillSummaryById() {
    var summaryId = document.getElementById('society_member_summary_id').value;
    if (!summaryId) { alert('No bill selected'); return; }
    if (!confirm('Recalculate only this current/latest bill using the latest parameters? Previous bills will not be changed.')) { return; }
    var postStr = 'id=' + summaryId
        + '&discount=' + (document.getElementById('member_bill_summary_bill_discount').value || 0)
        + '&principal_adjusted=' + (document.getElementById('member_bill_summary_bill_principal_adjusted').value || 0)
        + '&interest_adjusted=' + (document.getElementById('member_bill_summary_bill_interest_adjusted').value || 0)
        + '&interest_on_due_amount=' + (document.getElementById('member_bill_summary_interest_on_due_amount').value || 0)
        + '&member_id=' + (document.getElementById('member_bill_summary_member_id').value || '')
        + collectTariffFieldsQueryString();
    fetch('/society/update-current-member-bill-summary', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken},
        body: postStr
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
        if (resp.error === 0) {
            alert(resp.error_message || 'Current bill updated successfully.');
            getAllMembersBillSummaryDetails('id=' + summaryId);
        } else {
            alert(resp.error_message || 'Current bill could not be updated.');
        }
    })
    .catch(function() { alert('Network error. Please try again.'); });
}

function updateManualInterest() {
    alert('Manual interest update will be available soon.');
}

function deleteMemberBillSummaryById() {
    var summaryId = document.getElementById('society_member_summary_id').value;
    if (!summaryId) return;
    var ifPayment = document.getElementById('delMemSum').getAttribute('ifpayment');
    if (ifPayment == '1') {
        if (!confirm('This bill has payment settlements. Receipt will be deleted along with bill. Do you really want to delete?')) return;
    }
    alert('Delete functionality will be available soon.');
}

function saveBillRemark() {
    var remarks = document.getElementById('bill_remarks').value;
    var billId = document.getElementById('bill_id').value;
    if (!billId) { alert('No bill selected'); return; }
    alert('Remark save will be available soon.');
}

function switchTab(tabGroupId, targetPaneId) {
    var group = document.getElementById(tabGroupId);
    if (!group) return;
    var tabList = group.previousElementSibling;
    if (tabList) {
        var lis = tabList.querySelectorAll('li');
        for (var i = 0; i < lis.length; i++) lis[i].classList.remove('active');
    }
    var panes = group.querySelectorAll('.tab-pane');
    for (var i = 0; i < panes.length; i++) panes[i].classList.remove('active');
    var target = document.getElementById(targetPaneId);
    if (target) target.classList.add('active');
    var link = tabList ? tabList.querySelector('a[href="#' + targetPaneId + '"]') : null;
    if (link && link.parentElement) link.parentElement.classList.add('active');
}

function saveMemberReceipt() { alert('Save receipt functionality will be available soon.'); }

function toggleChequeFields() {
    var type = document.getElementById('pe_type').value;
    var cols = document.querySelectorAll('.pe-cheque-col');
    for (var i = 0; i < cols.length; i++) { cols[i].style.display = type === 'Cash' ? 'none' : ''; }
}

function saveSocietyPayment() {
    var rows = [];
    for (var i = 1; i <= 9; i++) {
        var ledger = document.querySelector('[name="pe_ledger_' + i + '"]');
        var amount = document.querySelector('[name="pe_amount_' + i + '"]');
        if (ledger && ledger.value && amount && parseFloat(amount.value) > 0) {
            rows.push({
                ledger_head_id: ledger.value,
                amount: amount.value,
                payment_date: document.querySelector('[name="pe_pay_date_' + i + '"]').value,
                cheque_reference_number: document.querySelector('[name="pe_cheque_no_' + i + '"]').value,
                cheque_date: document.querySelector('[name="pe_cheque_date_' + i + '"]').value,
                particulars: document.querySelector('[name="pe_particulars_' + i + '"]').value,
                notes: document.querySelector('[name="pe_remark_' + i + '"]').value,
                is_tax_applicable: document.querySelector('[name="pe_tax_' + i + '"]').checked ? 1 : 0
            });
        }
    }
    if (rows.length === 0) { alert('Enter at least one payment'); return; }
    var msg = document.getElementById('pe_message');
    msg.innerHTML = '<span style="color:#999;">Saving...</span>';
    fetch('/society/save-payment-entry', {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify({
            payment_date: document.getElementById('pe_date').value,
            payment_by_ledger_id: document.getElementById('pe_by_ledger').value,
            payment_type: document.getElementById('pe_type').value,
            single_voucher: document.getElementById('pe_single_voucher').checked ? 1 : 0,
            entries: rows
        })
    }).then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.error === 0) { msg.innerHTML = '<span style="color:green;">' + (d.error_message || d.message) + '</span>'; }
        else { msg.innerHTML = '<span style="color:red;">' + (d.error_message || d.message) + '</span>'; }
    }).catch(function() { msg.innerHTML = '<span style="color:red;">Error occurred</span>'; });
}

function printMemberBill() {
    // same query the CakePHP Print Bill form sends to society_bills/print_member_bills
    var params = 'month=' + encodeURIComponent(document.getElementById('pb_month').value || '') +
        '&flat_no=' + encodeURIComponent(document.getElementById('pb_flat_no').value || '') +
        '&bill_from=' + encodeURIComponent(document.getElementById('pb_bill_from').value || '') +
        '&bill_to=' + encodeURIComponent(document.getElementById('pb_bill_to').value || '') +
        '&receipt_from=' + encodeURIComponent(document.getElementById('pb_receipt_from').value || '') +
        '&receipt_to=' + encodeURIComponent(document.getElementById('pb_receipt_to').value || '') +
        '&unit_type=' + encodeURIComponent(document.getElementById('pb_unit_type').value || 'Unit No') +
        '&words_type=English&member_record=Current&bill_format_type=-1&billFormat=' +
        '&bill_type=' + encodeURIComponent(document.getElementById('pb_bill_type').value || 'reg');
    window.open('/society/bill-with-receipt-tabular/print?' + params, '_blank');
    closeModal('printBillModal');
}

function emailMemberBill() {
    alert('Email bill functionality will be available soon.');
}
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
@yield('scripts')

@if($showChatWidget)
<button id="chat-toggle-btn" onclick="toggleChatWidget()" aria-label="Support chat">&#128172;</button>
<div id="chat-window">
    <div id="chat-header">
        <span>Support Assistant</span>
        <span class="chat-close" onclick="toggleChatWidget()">&times;</span>
    </div>
    <div id="chat-messages">
        <div class="chat-msg system">Ask me anything about using EasyLogics.</div>
    </div>
    <div id="chat-input-row">
        <input type="text" id="chat-input" placeholder="Type a message..." onkeydown="if(event.key==='Enter')sendChatMessage()">
        <button id="chat-send-btn" onclick="sendChatMessage()">Send</button>
    </div>
</div>
<script>
function toggleChatWidget() {
    document.getElementById('chat-window').classList.toggle('open');
}

function appendChatMessage(text, role) {
    var box = document.getElementById('chat-messages');
    var div = document.createElement('div');
    div.className = 'chat-msg ' + role;
    div.textContent = text;
    box.appendChild(div);
    box.scrollTop = box.scrollHeight;
}

function sendChatMessage() {
    var input = document.getElementById('chat-input');
    var btn = document.getElementById('chat-send-btn');
    var message = input.value.trim();
    if (!message) return;

    appendChatMessage(message, 'user');
    input.value = '';
    btn.disabled = true;

    fetch('{{ route("chat.send") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: message })
    })
    .then(function(r) { return r.json().then(function(data) { return { status: r.status, data: data }; }); })
    .then(function(res) {
        btn.disabled = false;
        if (res.status !== 200) {
            appendChatMessage(res.data.error || 'Something went wrong.', 'system');
            return;
        }
        appendChatMessage(res.data.reply, 'assistant');
    })
    .catch(function() {
        btn.disabled = false;
        appendChatMessage('Network error. Please try again.', 'system');
    });
}
</script>
@endif
</body>
</html>
