@extends('layouts.app')

@section('title', 'Help - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Help &amp; Instructions</h2>
</div>

@if($helpVideo && $helpVideo->video_path)
<div class="card">
    @if($helpVideo->title)
        <h3 style="margin-bottom:10px;">{{ $helpVideo->title }}</h3>
    @endif
    <video src="{{ asset($helpVideo->video_path) }}" controls style="width:100%; max-width:640px; border-radius:8px; display:block;"></video>
</div>
@endif

<div class="card">
    <h3>Dashboard</h3>
    <p>Your dashboard shows a quick summary of your business: total societies assigned to you, how many are active, new societies added this year, and total members across all your societies. If your subscription is close to expiring or has expired, a banner appears at the top — renew from the <a href="{{ route('reseller.paymentDashboard') }}">Payment Dashboard</a> or contact Admin. If Admin has resolved a complaint you raised, you'll see a banner asking you to confirm it.</p>
</div>

<div class="card">
    <h3>Manage Societies</h3>
    <ul style="margin-left:18px; line-height:1.8;">
        <li><strong>My Societies</strong> — lists every society assigned to you, their member counts, and status. Click "Open Society" to log in as that society and manage its billing/accounts directly.</li>
        <li><strong>Create Society</strong> — add a brand-new society under your account. This creates its login and a blank society record for you to fill in.</li>
        <li><strong>Society Finance Year Mapping</strong> — assign which financial years each of your societies can operate in.</li>
    </ul>
</div>

<div class="card">
    <h3>Manage Users</h3>
    <p>Create additional login accounts (sub-users) for your own team — for example, an accountant who should only see certain societies. Assign permissions per module (view/add/edit) from the Permissions screen.</p>
</div>

<div class="card">
    <h3>Complaints</h3>
    <p>Log a complaint against any of your assigned societies if something needs Admin's attention. You can also mark it "I fixed it" yourself if you resolve it without Admin's help. When Admin marks a complaint resolved, it shows as "Admin says fixed" — review it and either <strong>Confirm Fixed</strong> (closes it) or <strong>Not Fixed</strong> (sends it back to Admin as pending, with your note on what's still wrong).</p>
</div>

<div class="card">
    <h3>Payment Dashboard</h3>
    <p>Shows your software purchase date, current subscription expiry, and a read-only history of renewal payments Admin has logged against your account. To renew, contact Admin — they will log the payment here and your subscription will extend automatically.</p>
</div>

<div class="card">
    <h3>My Profile</h3>
    <p>Update your personal identity details (name, contact info, address) under Personal Identity.</p>
</div>

<div class="card">
    <h3>Working Inside a Society (after "Open Society")</h3>
    <p style="color:#999; font-size:13px; margin-bottom:10px;">These steps apply once you've opened one of your societies from My Societies.</p>
    <ul style="margin-left:18px; line-height:1.9;">
        <li>
            <strong>1. Import Members via Excel</strong> &mdash; go to <strong>Member &rsaquo; Member Identity</strong>. Click <strong>Download Template</strong> to get the correct column format, fill it in Excel, then use <strong>Upload CSV</strong> to import/update members in bulk.
        </li>
        <li>
            <strong>2. Member Payment Bulk Paste</strong> &mdash;
            <span style="background:#fff3cd; color:#856404; padding:2px 8px; border-radius:10px; font-size:12px;">Not available yet</span>
            in this new system. For now, add member payments one at a time from <strong>Member Payments</strong>. This is on the list to be added.
        </li>
        <li>
            <strong>3. Society Payment / Expense Entry</strong> &mdash; go to <strong>Society &rsaquo; Society Payments</strong> and click Add. Choose the Ledger Head, enter the amount, tax (if any), payment date and mode, then save.
        </li>
        <li>
            <strong>4. Generate Bills</strong> &mdash; use the <strong>Generate Bill</strong> button in the top bar. Select Building/Wing (or leave blank for all), the bill month, bill date and due date, then submit. Bills for all matching members are created in one go.
        </li>
        <li>
            <strong>5. Member Tariff Rate</strong> &mdash; go to <strong>Member &rsaquo; Member Tariff</strong> to set the maintenance/other tariff amounts that apply to each member or building &mdash; these rates are what get pulled into a bill when you generate it.
        </li>
    </ul>
</div>

<div class="card">
    <h3>Need more help?</h3>
    <p>Contact Admin directly for anything not covered here, or if you're stuck on a specific screen.</p>
</div>
@endsection
