@extends('layouts.app')
@section('title', 'Bill')
@section('content')
@include('society.reports._styles')
@include('society.reports._bill_styles')
<div class="page-header">
    <h2>Bill</h2>
    <a href="{{ route('society.billWithReceiptTabular') }}" class="btn btn-primary btn-sm">Back to Print Bill</a>
</div>
@if(count($printBillDetails) > 0)
    <div class="card">
        <div class="ar-actions" style="margin-bottom:12px">
            <button type="button" class="btn btn-success btn-sm" onclick="printBills('society_print_bill');"><i class="fa fa-print"></i> Print</button>
            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-file-pdf-o"></i> Export to PDF</a>
        </div>
        <div class="bill-report">
            @include('society.reports._member_bill_body')
        </div>
    </div>
@else
    <div class="card"><p class="ar-note">No bills found for the selected filter.</p></div>
@endif
@endsection

@section('scripts')
@include('society.reports._bill_scripts')
@endsection
