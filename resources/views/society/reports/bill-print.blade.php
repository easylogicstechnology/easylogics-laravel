@extends('layouts.app')
@section('title', $title)
@section('content')
@include('society.reports._styles')
@include('society.reports._bill_styles')
<div class="page-header">
    <h2>{{ $title }}</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

@if(session('bill_flash'))
    <div class="card"><div class="ar-note" style="font-size:14px;color:#222">{{ session('bill_flash') }}</div></div>
@endif

<div class="card">
    @include('society.reports._bill_filter')
</div>

@if($post)
    @if(count($bills) > 0)
        <?php $societyLedgerHeadIds = array_keys($societyLedgerHeadTitleList ?? []); ?>
        <div class="card">
            <div class="ar-actions" style="margin-bottom:12px">
                @if(!empty($excelUrl))
                    <a href="{{ $excelUrl }}" target="_blank" class="btn btn-warning btn-sm"><i class="fa fa-download"></i> Export to Excel</a>
                @endif
                @if(!empty($pdfUrl))
                    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-success btn-sm"><i class="fa fa-file-pdf-o"></i> Export to PDF</a>
                @endif
                <button type="button" class="btn btn-success btn-sm" onclick="printBills('{{ $printId }}');"><i class="fa fa-print"></i> Print</button>
            </div>
            <div id="{{ $printId }}" class="bill-report">
                @include($body)
            </div>
        </div>
    @else
        <div class="card"><p class="ar-note">No bills found for the selected filter.</p></div>
    @endif
@endif
@endsection

@section('scripts')
@include('society.reports._bill_scripts')
@endsection
