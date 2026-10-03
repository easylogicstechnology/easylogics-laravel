@extends('layouts.app')
@section('title', 'Reverse TDS Transaction')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Reverse TDS Transaction #{{ $txn['id'] }}</h6></div>
        <div class="panel-body">
            <p>Gross Amount: <b>{{ number_format($txn['gross_amount'], 2) }}</b> &nbsp; TDS Amount: <b>{{ number_format($txn['tds_amount'], 2) }}</b></p>
            <p class="text-danger">This transaction will be marked reversed and excluded from all TDS reports, ledgers and challans. This action is logged in the audit trail and cannot be undone from the UI.</p>
            <form method="post" action="{{ route('society.tdsReverseTransaction', $txn['id']) }}">
                @csrf
                <div class="form-group">
                    <label class="control-label">Reason for Reversal<span class="required">*</span></label>
                    <textarea class="form-control" name="reversal_reason" required></textarea>
                </div>
                <button type="submit" class="btn btn-danger">Confirm Reversal</button>
                <a href="{{ route('society.tdsTransactions') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
