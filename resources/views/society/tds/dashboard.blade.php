@extends('layouts.app')
@section('title', 'TDS Dashboard')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="page-header"><h2>TDS Dashboard</h2></div>
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Current FY TDS Deducted</h6><h3>Rs. {{ number_format($currentFyTdsDeducted, 2) }}</h3></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">TDS Payable</h6><h3 class="text-danger">Rs. {{ number_format($tdsPayable, 2) }}</h3></div></div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Challan Pending</h6><h3>{{ (int) $challanPendingCount }}</h3></div></div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Challan Paid</h6><h3 class="text-success">{{ (int) $challanPaidCount }}</h3></div></div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="panel"><div class="panel-body stat"><h6 class="text-muted">Deductee Count</h6><h3>{{ (int) $deducteeCount }}</h3></div></div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-sm-12">
            <div class="panel">
                <div class="panel-heading"><h6 class="panel-title">Section-wise TDS (Current Financial Year)</h6></div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead><tr><th>Section</th><th>Nature of Payment</th><th class="text-right">TDS Amount</th></tr></thead>
                            <tbody>
                            @forelse($sectionWise as $row)
                                <tr>
                                    <td>{{ $row->section_code }}</td>
                                    <td>{{ $row->nature_of_payment }}</td>
                                    <td class="text-right">Rs. {{ number_format($row->section_total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No TDS transactions found for the current financial year.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
