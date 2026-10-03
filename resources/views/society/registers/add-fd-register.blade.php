@extends('layouts.app')
@section('title', $entry ? 'Edit FD Entry' : 'Add FD Entry')
@section('content')
@php
    // old() first (failed save), then the stored row
    $v = fn ($field) => old($field, $entry->{$field} ?? '');
    $statuses = ['Active', 'Matured', 'Renewed', 'Closed'];
@endphp
<div class="page-header">
    <h2>{{ $entry ? 'Edit FD Entry' : 'Add FD Entry' }}</h2>
    <a href="{{ route('society.fdRegister') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addFdRegister', $entry->id ?? '') }}" autocomplete="off">
        @csrf
        <input type="hidden" name="id" value="{{ $entry->id ?? '' }}">

        <h3 style="font-size:15px; margin-bottom:12px;">1. Investment/FD Identification</h3>
        <div class="grid-3">
            <div class="form-group">
                <label>FD No.</label>
                <input type="text" class="form-control" name="fd_no" value="{{ $v('fd_no') }}">
            </div>
            <div class="form-group">
                <label>Bank Name</label>
                <input type="text" class="form-control" name="bank_name" value="{{ $v('bank_name') }}">
            </div>
            <div class="form-group">
                <label>Branch</label>
                <input type="text" class="form-control" name="branch" value="{{ $v('branch') }}">
            </div>
            <div class="form-group">
                <label>FD Type</label>
                <input type="text" class="form-control" name="fd_type" value="{{ $v('fd_type') }}" placeholder="e.g. Fixed Deposit, Cumulative">
            </div>
            <div class="form-group">
                <label>Society Bank Account No.</label>
                <select class="form-control" name="society_bank_id">
                    <option value="">Select Bank Account</option>
                    @foreach($banks as $bankId => $accountNo)
                    <option value="{{ $bankId }}" {{ (string) $v('society_bank_id') === (string) $bankId ? 'selected' : '' }}>{{ $accountNo }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr style="margin:16px 0; border:0; border-top:1px solid #eee;">
        <h3 style="font-size:15px; margin-bottom:12px;">2. Amount</h3>
        <div class="grid-3">
            <div class="form-group">
                <label>Investment Date</label>
                <input type="date" class="form-control" name="investment_date" value="{{ $v('investment_date') }}">
            </div>
            <div class="form-group">
                <label>Principal Amount</label>
                <input type="number" step="0.01" class="form-control" name="principal_amount" value="{{ $v('principal_amount') }}">
            </div>
            <div class="form-group">
                <label>Interest Rate (%)</label>
                <input type="number" step="0.01" class="form-control" name="interest_rate" value="{{ $v('interest_rate') }}">
            </div>
            <div class="form-group">
                <label>Period/Tenure</label>
                <input type="text" class="form-control" name="period_tenure" value="{{ $v('period_tenure') }}" placeholder="e.g. 12 Months">
            </div>
            <div class="form-group">
                <label>Maturity Date</label>
                <input type="date" class="form-control" name="maturity_date" value="{{ $v('maturity_date') }}">
            </div>
            <div class="form-group">
                <label>Maturity Amount</label>
                <input type="number" step="0.01" class="form-control" name="maturity_amount" value="{{ $v('maturity_amount') }}">
            </div>
        </div>

        <hr style="margin:16px 0; border:0; border-top:1px solid #eee;">
        <h3 style="font-size:15px; margin-bottom:12px;">3. Interest</h3>
        <div class="grid-3">
            <div class="form-group">
                <label>Interest Earned</label>
                <input type="number" step="0.01" class="form-control" name="interest_earned" value="{{ $v('interest_earned') }}">
            </div>
            <div class="form-group">
                <label>TDS Deducted</label>
                <input type="number" step="0.01" class="form-control" name="tds_deducted" value="{{ $v('tds_deducted') }}">
            </div>
            <div class="form-group">
                <label>Net Interest Received</label>
                <input type="number" step="0.01" class="form-control" name="net_interest_received" value="{{ $v('net_interest_received') }}">
            </div>
            <div class="form-group">
                <label>Interest Receipt Date</label>
                <input type="date" class="form-control" name="interest_receipt_date" value="{{ $v('interest_receipt_date') }}">
            </div>
        </div>

        <hr style="margin:16px 0; border:0; border-top:1px solid #eee;">
        <h3 style="font-size:15px; margin-bottom:12px;">4. Closure/Renewal</h3>
        <div class="grid-3">
            <div class="form-group">
                <label>Renewal Date</label>
                <input type="date" class="form-control" name="renewal_date" value="{{ $v('renewal_date') }}">
            </div>
            <div class="form-group">
                <label>Renewed FD No.</label>
                <input type="text" class="form-control" name="renewed_fd_no" value="{{ $v('renewed_fd_no') }}">
            </div>
            <div class="form-group">
                <label>Closure Date</label>
                <input type="date" class="form-control" name="closure_date" value="{{ $v('closure_date') }}">
            </div>
            <div class="form-group">
                <label>Amount Received</label>
                <input type="number" step="0.01" class="form-control" name="amount_received" value="{{ $v('amount_received') }}">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="fd_status">
                    <option value="">Select Status</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ $v('fd_status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <textarea class="form-control" name="remarks" rows="2">{{ $v('remarks') }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('society.fdRegister') }}" class="btn btn-sm" style="background:#e0e0e0; color:#333;">Cancel</a>
    </form>
</div>
@endsection
