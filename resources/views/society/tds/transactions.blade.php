@extends('layouts.app')
@section('title', 'TDS Transactions')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">TDS Transactions</h6></div>
            <div class="pull-right"><a href="{{ route('society.tdsAddTransaction') }}" class="btn btn-primary">+ Add TDS Transaction</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-2"><select name="financial_year_id" class="form-control"><option value="">All FY</option>@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ (!empty($qs['financial_year_id']) && $qs['financial_year_id'] == $id) ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="tds_section_id" class="form-control"><option value="">All Sections</option>@foreach($sectionsList as $id => $code)<option value="{{ $id }}" {{ (!empty($qs['tds_section_id']) && $qs['tds_section_id'] == $id) ? 'selected' : '' }}>{{ $code }}</option>@endforeach</select></div>
                <div class="col-md-2"><select name="vendor_detail_id" class="form-control"><option value="">All Deductees</option>@foreach($vendorList as $id => $name)<option value="{{ $id }}" {{ (!empty($qs['vendor_detail_id']) && $qs['vendor_detail_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div>
                <div class="col-md-2">
                    <select name="challan_status" class="form-control">
                        <option value="">All Status</option>
                        @foreach(['Pending', 'Challan Prepared', 'Payment Pending', 'Paid', 'Challan Verified', 'Filed'] as $st)
                            <option value="{{ $st }}" {{ (!empty($qs['challan_status']) && $qs['challan_status'] == $st) ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-info">Filter</button></div>
            </form>
            <div class="clearfix"></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr><th>Sr</th><th>Deduction Date</th><th>Deductee</th><th>PAN</th><th>Invoice No</th><th>Section</th><th>Gross</th><th>Rate</th><th>TDS Amt</th><th>Net Amt</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($transactionsList as $i => $t)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $t->deduction_date }}</td>
                            <td>{{ $t->contact_person_name }}</td>
                            <td>{{ $t->pan_no }}</td>
                            <td>{{ $t->invoice_no }}</td>
                            <td>{{ $t->section_code }}</td>
                            <td class="text-right">{{ number_format($t->gross_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($t->tds_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($t->net_amount, 2) }}</td>
                            <td><span class="label label-info">{{ $t->challan_status }}</span>@if($t->is_reversed) <span class="label label-danger">Reversed</span>@endif</td>
                            <td class="text-nowrap">
                                <a href="{{ route('society.tdsAddTransaction', $t->id) }}" title="Edit">&#9998;</a>
                                @if(!$t->is_reversed && $t->challan_status == 'Pending')
                                    &nbsp;<a href="{{ route('society.tdsReverseTransaction', $t->id) }}" title="Reverse" class="text-danger">&#8630;</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    @if(!empty($totals))
                    <tfoot>
                    <tr class="text-bold">
                        <th colspan="6" class="text-right">Total</th>
                        <th class="text-right">{{ number_format($totals->total_gross ?? 0, 2) }}</th>
                        <th></th>
                        <th class="text-right">{{ number_format($totals->total_tds ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_net ?? 0, 2) }}</th>
                        <th colspan="2"></th>
                    </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
