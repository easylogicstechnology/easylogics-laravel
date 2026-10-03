@extends('layouts.app')
@section('title', 'TDS Challan Management')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">TDS Challan Management</h6></div>
            <div class="pull-right"><a href="{{ route('society.tdsGenerateChallan') }}" class="btn btn-primary">+ Generate Challan</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>#</th><th>TAN</th><th>FY</th><th>AY</th><th>Quarter</th><th>Month</th><th class="text-right">Total TDS</th><th>Deductee Count</th><th>Challan No</th><th>BSR Code</th><th>Challan Date</th><th>CIN</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($challansList as $i => $c)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $c->tan_no }}</td>
                            <td>{{ $c->financial_year_id }}</td>
                            <td>{{ $c->assessment_year }}</td>
                            <td>{{ $c->quarter }}</td>
                            <td>{{ date('F', mktime(0, 0, 0, $c->month, 1)) }}</td>
                            <td class="text-right">{{ number_format($c->total_tds_amount, 2) }}</td>
                            <td>{{ $c->deductee_count }}</td>
                            <td>{{ $c->challan_number }}</td>
                            <td>{{ $c->bsr_code }}</td>
                            <td>{{ $c->challan_date }}</td>
                            <td>{{ $c->cin }}</td>
                            <td><span class="label label-info">{{ $c->payment_status }}</span></td>
                            <td><a href="{{ route('society.tdsUpdateChallan', $c->id) }}">&#9998; Update</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
