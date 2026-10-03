@extends('layouts.app')
@section('title', 'GST Dashboard')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="page-header"><h2>GST Dashboard</h2></div>
    @if(empty($gstMaster) || empty($gstMaster->gstin))
        <div class="alert alert-warning">
            GST Master is not set up yet. <a href="{{ route('society.gstMasterSetup') }}">Set up GST Master</a> to record your society's GSTIN, state and return frequency.
        </div>
    @endif
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Current Tax Period</h6><h4>{{ $currentPeriod }}</h4></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Taxable Turnover</h6><h3>Rs. {{ number_format($taxableTurnover, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Output GST</h6><h3>Rs. {{ number_format($outputGst, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Net GST Liability</h6><h3 class="text-danger">Rs. {{ number_format($netLiability, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">ITC Available</h6><h3>Rs. {{ number_format($eligibleItc, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">ITC Claimed</h6><h3 class="text-success">Rs. {{ number_format($itcClaimed, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">ITC Balance</h6><h3>Rs. {{ number_format($itcBalance, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Advance GST</h6><h3>Rs. {{ number_format($advanceGst, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">GST Paid</h6><h3 class="text-success">Rs. {{ number_format($gstPaid, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">GST Outstanding</h6><h3 class="text-danger">Rs. {{ number_format($gstOutstanding, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel">
                <div class="panel-body stat">
                    <h6 class="text-muted">Return Status ({{ $currentPeriodValue }})</h6>
                    <form method="post" action="{{ route('society.gstUpdateReturnStatus') }}" id="returnStatusForm">
                        @csrf
                        <input type="hidden" name="period_type" value="Month">
                        <input type="hidden" name="period_value" value="{{ $currentPeriodValue }}">
                        <select class="form-control" name="status" onchange="document.getElementById('returnStatusForm').submit();" style="max-width:200px;margin:0 auto;">
                            @foreach(['Draft', 'Ready for Review', 'Reconciled', 'Ready to File', 'Filed', 'Amended'] as $st)
                                <option value="{{ $st }}" {{ $returnStatus == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="panel">
                <div class="panel-heading"><h6 class="panel-title">Quick Links</h6></div>
                <div class="panel-body">
                    <a href="{{ route('society.gstHsnMaster') }}" class="btn btn-default">HSN/SAC Master</a>
                    <a href="{{ route('society.gstOutwardRegister') }}" class="btn btn-default">Outward Register</a>
                    <a href="{{ route('society.gstInputRegister') }}" class="btn btn-default">Input / ITC Register</a>
                    <a href="{{ route('society.gstItcSummary') }}" class="btn btn-default">ITC Summary</a>
                    <a href="{{ route('society.gstLiability') }}" class="btn btn-default">GST Liability</a>
                    <a href="{{ route('society.gstLedger') }}" class="btn btn-default">GST Ledger</a>
                    <a href="{{ route('society.gstAdvanceReceipts') }}" class="btn btn-default">Advance Receipts</a>
                    <a href="{{ route('society.gstCreditDebitNotes') }}" class="btn btn-default">Credit/Debit Notes</a>
                    <a href="{{ route('society.gstPayments') }}" class="btn btn-default">GST Payment/Challan</a>
                    <a href="{{ route('society.gstReconciliation') }}" class="btn btn-default">Reconciliation</a>
                    <a href="{{ route('society.gstReturnReports') }}" class="btn btn-default">Return Reports</a>
                    <a href="{{ route('society.gstYearEndReport') }}" class="btn btn-default">Year-End Report</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
