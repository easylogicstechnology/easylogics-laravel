@extends('layouts.app')
@section('title', 'GST Outward Supply Register')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Outward Supply Register</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-2"><select name="financial_year_id" class="form-control"><option value="">Current FY</option>@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ (!empty($qs['financial_year_id']) && $qs['financial_year_id'] == $id) ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-2"><input type="text" name="month" class="form-control" placeholder="Month (e.g. September)" value="{{ $qs['month'] ?? '' }}"></div>
                <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ $qs['date_from'] ?? '' }}"></div>
                <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ $qs['date_to'] ?? '' }}"></div>
                <div class="col-md-2"><input type="text" name="gstin" class="form-control" placeholder="GSTIN" value="{{ $qs['gstin'] ?? '' }}"></div>
                <div class="col-md-2">
                    <select name="supply_type" class="form-control">
                        <option value="">B2B/B2C</option>
                        <option value="B2B" {{ (!empty($qs['supply_type']) && $qs['supply_type'] == 'B2B') ? 'selected' : '' }}>B2B</option>
                        <option value="B2C" {{ (!empty($qs['supply_type']) && $qs['supply_type'] == 'B2C') ? 'selected' : '' }}>B2C</option>
                    </select>
                </div>
                <div class="col-md-12" style="margin-top:10px;">
                    <button type="submit" class="btn btn-info btn-sm">Show</button>
                    <a class="btn btn-danger btn-sm" target="_blank" href="{{ route('society.gstOutwardRegisterPdf') }}?{{ http_build_query($qs) }}">Export PDF</a>
                    <a class="btn btn-success btn-sm" href="{{ route('society.gstOutwardRegisterExcel') }}?{{ http_build_query($qs) }}">Export Excel</a>
                </div>
            </form>
            <div class="clearfix"></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr><th>Sr</th><th>Bill No</th><th>Bill Date</th><th>Member</th><th>GSTIN</th><th>Place of Supply</th><th>HSN/SAC</th><th>Taxable Value</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total GST</th><th>Invoice Total</th><th>Type</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $i => $m)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $m->bill_no }}</td>
                            <td>{{ $m->bill_generated_date }}</td>
                            <td>{{ $m->member_name }}</td>
                            <td>{{ $m->GSTIN ?? '' }}</td>
                            <td>{{ $m->place_of_supply ?? '' }}</td>
                            <td>{{ $m->hsn_code ?? '' }}@if(!$m->computed['classified']) <span class="label label-default">Unclassified</span>@endif</td>
                            <td class="text-right">{{ number_format($m->computed['taxable_value'], 2) }}</td>
                            <td class="text-right">{{ number_format($m->cgst_total, 2) }}</td>
                            <td class="text-right">{{ number_format($m->sgst_total, 2) }}</td>
                            <td class="text-right">{{ number_format($m->igst_total, 2) }}</td>
                            <td class="text-right">{{ number_format($m->tax_total, 2) }}</td>
                            <td class="text-right">{{ number_format($m->monthly_bill_amount, 2) }}</td>
                            <td><span class="label label-info">{{ $m->computed['supply_type'] }}</span></td>
                            <td><a href="{{ route('society.gstClassifyOutward', $m->id) }}" class="btn btn-xs btn-default">Classify</a></td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())
                        <tr><td colspan="15" class="text-center">No records found.</td></tr>
                    @endif
                    </tbody>
                    <tfoot>
                    <tr class="text-bold">
                        <th colspan="8" class="text-right">Total</th>
                        <th class="text-right">{{ number_format($totals->total_cgst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_sgst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_igst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_gst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_invoice ?? 0, 2) }}</th>
                        <th colspan="2"></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
