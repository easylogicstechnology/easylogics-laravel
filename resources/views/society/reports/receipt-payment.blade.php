@extends('layouts.app')
@section('title', $title ?? 'Receipt & Payment')
@section('content')
@include('society.reports._styles')
<?php $mp = $postData['MemberPayment'] ?? []; ?>
<div class="page-header">
    <h2>Receipt &amp; Payment</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.receiptPayment') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>For the Period</label><input type="date" name="payment_date" value="{{ $mp['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $mp['payment_date_to'] ?? '' }}"></div>
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_receipt_payment', 'file' => 'ReceiptPayment', 'sheet' => 'Receipt & Payment'])
                <a href="{{ route('society.reports.receiptPayment') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card ar-report">
    @include('society.reports._receipt_payment_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
