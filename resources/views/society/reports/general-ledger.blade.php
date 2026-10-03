@extends('layouts.app')
@section('title', $title ?? 'General Ledger')
@section('content')
@include('society.reports._styles')
<?php $sp = $postData['SocietyPayment'] ?? []; ?>
<div class="page-header">
    <h2>General Ledger</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.generalLedger') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Report</label>
                <select name="report_type" required>
                    <option value="">Select</option>
                    <option selected value="Detail">Detail</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Ledger For</label>
                <select id="ledger_for" name="ledger_for" onchange="checkLedger()" required>
                    <option value="">Select</option>
                    @foreach(['Particular Subgroup', 'All', 'All(Excluding Bank,Cash,Member,Tariff)'] as $o)
                        <option value="{{ $o }}" {{ ($sp['ledger_for'] ?? '') == $o ? 'selected' : '' }}>{{ $o }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>A/c Name</label>
                <select id="account_name" name="account_name" class="ar-wide" {{ (isset($sp['ledger_for']) && $sp['ledger_for'] != 'Particular Subgroup') ? 'disabled' : '' }}>
                    <option value="">Select</option>
                    @foreach($societyLedgerHeads as $h)
                        <option value="{{ $h['id'] }}" {{ (isset($sp['account_name']) && $sp['account_name'] == $h['id']) ? 'selected' : '' }}>{{ $h['title'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field"><label>From</label><input type="date" name="payment_date" value="{{ $sp['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $sp['payment_date_to'] ?? '' }}"></div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Show</button>
                @include('society.reports._actions', ['printId' => 'print_general_ledger', 'file' => 'GeneralLedger', 'sheet' => 'General Ledger'])
                <a href="{{ route('society.reports.generalLedger') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card ar-report">
    @include('society.reports._general_ledger_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
<script>
function checkLedger() {
    var x = document.getElementById("ledger_for").value;
    if (x == 'Particular Subgroup') {
        document.getElementById("account_name").disabled = false;
    } else {
        document.getElementById("account_name").selectedIndex = 0;
        document.getElementById("account_name").disabled = true;
    }
}
</script>
@endsection
