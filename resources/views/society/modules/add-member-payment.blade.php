@extends('layouts.app')
@section('title', $editItem ? 'Edit Member Payment' : 'Add Member Payment')
@section('content')
<div class="page-header">
    <h2>{{ $editItem ? 'Edit Member Payment' : 'Make Payment' }}</h2>
    <a href="{{ route('society.memberPayments') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addMemberPayment', $editItem->id ?? '') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Society Member <span class="required">*</span></label>
                <select name="member_id" class="form-control" required>
                    <option value="">Select Member</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ ($editItem && $editItem->member_id == $m->id) ? 'selected' : '' }}>{{ trim($m->member_prefix . ' ' . $m->member_name) }} ({{ $m->flat_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" class="form-control" value="{{ ($editItem && $editItem->payment_date && $editItem->payment_date != '0000-00-00') ? $editItem->payment_date : date('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label>Amount Paid <span class="required">*</span></label>
                <input type="number" step="0.01" name="amount_paid" class="form-control" value="{{ $editItem->amount_paid ?? '0' }}" required>
            </div>
            <div class="form-group">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control">
                    @foreach($paymentModes as $mId => $mName)
                    <option value="{{ $mId }}" {{ ($editItem && $editItem->payment_mode == $mId) ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Society Bank</label>
                <select name="society_bank_id" class="form-control">
                    <option value="">Select Bank</option>
                    @foreach($societyBanks as $sb)
                    <option value="{{ $sb->id }}" {{ ($editItem && $editItem->society_bank_id == $sb->id) ? 'selected' : '' }}>{{ $sb->ledgerHead->title ?? '' }} - {{ $sb->account_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cheque / Reference No</label>
                <input type="text" name="cheque_reference_number" class="form-control" value="{{ $editItem->cheque_reference_number ?? '' }}">
            </div>
            <div class="form-group">
                <label>Credited Date</label>
                <input type="date" name="credited_date" class="form-control" value="{{ ($editItem && $editItem->credited_date && $editItem->credited_date != '0000-00-00') ? $editItem->credited_date : '' }}">
            </div>
            <div class="form-group">
                <label>Narration</label>
                <input type="text" name="narration" class="form-control" value="{{ $editItem->narration ?? '' }}">
            </div>
            <div class="form-group">
                <label>Bill Type</label>
                <select name="bill_type" class="form-control">
                    <option value="reg" {{ ($editItem && $editItem->bill_type == 'reg') ? 'selected' : '' }}>Regular</option>
                    <option value="sup" {{ ($editItem && $editItem->bill_type == 'sup') ? 'selected' : '' }}>Supplementary</option>
                </select>
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Payment' : 'Save Payment' }}</button>
            <a href="{{ route('society.memberPayments') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>
@endsection
