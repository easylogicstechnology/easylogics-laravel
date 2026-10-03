@extends('layouts.app')
@section('title', 'TDS Report')
@section('content')
@include('society.tds._styles')
@php
    $qval = fn ($k) => $qs[$k] ?? '';
    $exportQuery = http_build_query($qs);
@endphp
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">TDS Report</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-2"><select name="financial_year_id" class="form-control"><option value="">All FY</option>@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ ($qs && isset($qs['financial_year_id']) && $qs['financial_year_id'] == $id) ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ $qval('date_from') }}" placeholder="Date From"></div>
                <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ $qval('date_to') }}" placeholder="Date To"></div>
                <div class="col-md-2"><select name="tds_section_id" class="form-control"><option value="">All Sections</option>@foreach($sectionsList as $id => $code)<option value="{{ $id }}" {{ ($qs && isset($qs['tds_section_id']) && $qs['tds_section_id'] == $id) ? 'selected' : '' }}>{{ $code }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="vendor_detail_id" class="form-control"><option value="">All Deductees</option>@foreach($vendorList as $id => $name)<option value="{{ $id }}" {{ ($qs && isset($qs['vendor_detail_id']) && $qs['vendor_detail_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-info">Show</button></div>
                <div class="col-md-12" style="margin-top:10px;">
                    <a class="btn btn-danger btn-sm" target="_blank" href="{{ route('society.tdsReportPdf') }}?{{ $exportQuery }}">Export PDF</a>
                    <a class="btn btn-success btn-sm" href="{{ route('society.tdsReportExcel') }}?{{ $exportQuery }}">Export Excel</a>
                </div>
            </form>
            <div class="clearfix"></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr><th>Sr</th><th>Deduction Date</th><th>Payment Date</th><th>Deductee</th><th>PAN</th><th>Invoice No</th><th>Section</th><th>Gross</th><th>Rate</th><th>TDS Amt</th><th>Net Amt</th><th>Challan No</th><th>Challan Date</th><th>BSR Code</th><th>CIN</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @foreach($reportData as $i => $t)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $t->deduction_date }}</td>
                            <td>{{ $t->payment_date }}</td>
                            <td>{{ $t->contact_person_name }}</td>
                            <td>{{ $t->pan_no }}</td>
                            <td>{{ $t->invoice_no }}</td>
                            <td>{{ $t->section_code }}</td>
                            <td class="text-right">{{ number_format($t->gross_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($t->tds_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($t->net_amount, 2) }}</td>
                            <td>{{ $t->challan_number }}</td>
                            <td>{{ $t->challan_date }}</td>
                            <td>{{ $t->bsr_code }}</td>
                            <td>{{ $t->cin }}</td>
                            <td>{{ $t->challan_status }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="text-bold">
                        <th colspan="7" class="text-right">Total</th>
                        <th class="text-right">{{ number_format($totals->total_gross ?? 0, 2) }}</th>
                        <th></th>
                        <th class="text-right">{{ number_format($totals->total_tds ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_net ?? 0, 2) }}</th>
                        <th colspan="5"></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
