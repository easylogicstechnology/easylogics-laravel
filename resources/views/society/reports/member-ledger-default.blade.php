@extends('layouts.app')
@section('title', $title ?? 'Member Ledger Default')
@section('content')
@include('society.reports._styles')
<?php $mbs = $postData['MemberBillSummary'] ?? []; ?>
<div class="page-header">
    <h2>Member Ledger</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.memberLedgerDefault') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Type</label>
                <select name="report_type">
                    @foreach(['Default' => 'Default', 'reg' => 'Regular', 'sup' => 'Supplementary', 'Summary' => 'Summary'] as $v => $t)
                        <option value="{{ $v }}" {{ ($mbs['report_type'] ?? '') === $v ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field"><label>For the Period</label><input type="date" name="payment_date" value="{{ $mbs['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $mbs['payment_date_to'] ?? '' }}"></div>
            <div class="ar-field">
                <label>Member Record</label>
                <select name="member_record">
                    <option value="Current" {{ ($mbs['member_record'] ?? '') === 'Current' ? 'selected' : '' }}>Current Member</option>
                    <option value="Old" {{ ($mbs['member_record'] ?? '') === 'Old' ? 'selected' : '' }}>Old Member</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Member Record Range</label>
                <select name="member_record_range">
                    <option value="-1">Member Record Range</option>
                    @foreach([1 => '1 to 100', 2 => '101 to 200', 3 => '201 to 300', 4 => '301 to 400', 5 => '401 to 500', 6 => '501 to 600', 7 => '601 to 700'] as $n => $t)
                        <option value="{{ $n }}" {{ (string) ($mbs['member_record_range'] ?? '') === (string) $n ? 'selected' : '' }}>{{ $t }} Records</option>
                    @endforeach
                </select>
            </div>
        </div>
        <?php $input = $mbs; ?>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_member_ledger', 'file' => 'MemberLedger', 'sheet' => 'Member Ledger', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.memberLedgerDefault') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card ar-report">
    @include('society.reports._member_ledger_default_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
